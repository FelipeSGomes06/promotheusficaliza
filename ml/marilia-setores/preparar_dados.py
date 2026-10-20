"""Prepara somente contagens diarias publicas, sem extrair pessoas/veiculos do ZIP."""
from __future__ import annotations

import argparse
import collections
import csv
import datetime as dt
import hashlib
import io
import json
from pathlib import Path
import unicodedata
import urllib.request
import zipfile

ROOT = Path(__file__).resolve().parent
MEMBERS = tuple(f"sinistros_{period}.csv" for period in ("2015-2021", "2022-2024", "2025-2026"))


def write_json(path: Path, value: object) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp = path.with_suffix(path.suffix + ".tmp")
    tmp.write_text(json.dumps(value, ensure_ascii=False, indent=2, allow_nan=False) + "\n", encoding="utf-8")
    tmp.replace(path)


def load_config() -> dict:
    return json.loads((ROOT / "config.json").read_text(encoding="utf-8"))


def sector_for(lat: float, lng: float, config: dict) -> str | None:
    b = config["bounds"]
    if not (b["south"] <= lat <= b["north"] and b["west"] <= lng <= b["east"]):
        return None
    vertical = "N" if lat >= (b["south"] + b["north"]) / 2 else "S"
    horizontal = "O" if lng < (b["west"] + b["east"]) / 2 else "E"
    return vertical + horizontal


def sector_geojson(config: dict) -> dict:
    b = config["bounds"]
    middle_lat = (b["south"] + b["north"]) / 2
    middle_lng = (b["west"] + b["east"]) / 2
    features = []
    for sector in config["sectors"]:
        sid = sector["id"]
        south, north = (middle_lat, b["north"]) if sid[0] == "N" else (b["south"], middle_lat)
        west, east = (b["west"], middle_lng) if sid[1] == "O" else (middle_lng, b["east"])
        features.append({"type": "Feature", "properties": sector | {
            "experimental": True, "official_neighborhood": False,
            "centroid": {"lat": (south + north) / 2, "lng": (west + east) / 2},
        }, "geometry": {"type": "Polygon", "coordinates": [[
            [west, south], [east, south], [east, north], [west, north], [west, south],
        ]]}})
    return {"type": "FeatureCollection", "features": features}


def normalize(value: str) -> str:
    return "".join(c for c in unicodedata.normalize("NFD", value.upper()) if unicodedata.category(c) != "Mn").strip()


def sha256(path: Path) -> str:
    with path.open("rb") as handle:
        return hashlib.file_digest(handle, "sha256").hexdigest()


def obtain_source(path: Path, config: dict) -> None:
    if path.exists():
        return
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(".download")
    request = urllib.request.Request(config["source_url"], headers={"User-Agent": "PromotheusPesquisa/1.0"})
    size = 0
    with urllib.request.urlopen(request, timeout=60) as response, temporary.open("wb") as output:
        while chunk := response.read(1024 * 1024):
            size += len(chunk)
            if size > 512 * 1024 * 1024:
                raise ValueError("Fonte excede o limite de 512 MiB; conferir manualmente.")
            output.write(chunk)
    if sha256(temporary) != config["source_sha256"]:
        raise ValueError("A fonte mudou. Atualizar/revalidar o protocolo antes de usar outro snapshot. Arquivo .download preservado.")
    temporary.replace(path)


def prepare(path: Path, config: dict) -> dict:
    if sha256(path) != config["source_sha256"]:
        raise ValueError("SHA-256 diferente do snapshot verificado em config.json.")
    counts = collections.Counter()
    audit = collections.Counter()
    years = collections.Counter()
    seen = set()
    start = dt.date.fromisoformat(config["events_from"])
    end = dt.date.fromisoformat(config["data_through"])
    with zipfile.ZipFile(path) as archive:
        for name in MEMBERS:
            info = archive.getinfo(name)
            if info.file_size > 256 * 1024 * 1024:
                raise ValueError("CSV excede limite de leitura do protocolo.")
            with archive.open(name) as raw, io.TextIOWrapper(raw, encoding="cp1252", errors="strict", newline="") as handle:
                reader = csv.DictReader(handle, delimiter=";")
                for row in reader:
                    if normalize(row["municipio"]) != "MARILIA":
                        continue
                    audit["marilia_records"] += 1
                    key = row["id_sinistro"]
                    if key in seen:
                        audit["duplicate_id"] += 1
                        continue
                    seen.add(key)
                    date = dt.datetime.strptime(row["data_sinistro"], "%d/%m/%Y").date()
                    if not start <= date <= end:
                        audit["outside_dates"] += 1
                        continue
                    if row["tipo_registro"] not in {"SINISTRO FATAL", "SINISTRO NAO FATAL"}:
                        audit["notification_or_other"] += 1
                        continue
                    if row["tipo_via"] != "VIAS URBANAS":
                        audit["nonurban_or_unknown"] += 1
                        continue
                    try:
                        sid = sector_for(float(row["latitude"].replace(",", ".")), float(row["longitude"].replace(",", ".")), config)
                    except ValueError:
                        audit["missing_or_invalid_gps"] += 1
                        continue
                    if sid is None:
                        audit["outside_study_rectangle"] += 1
                        continue
                    counts[(date.isoformat(), sid)] += 1
                    years[date.year] += 1
                    audit["eligible"] += 1
    data = ROOT / "dados"
    data.mkdir(exist_ok=True)
    with (data / "contagens_diarias.csv").open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(["date", "sector_id", "count"])
        date = start
        while date <= end:
            for sector in config["sectors"]:
                writer.writerow([date.isoformat(), sector["id"], counts[(date.isoformat(), sector["id"])]])
            date += dt.timedelta(days=1)
    manifest = {"schema_version": 1, "protocol_version": config["protocol_version"],
                "source": {k: config[k] for k in ("source_url", "source_catalog", "source_sha256", "license", "snapshot_date", "data_through")},
                "filters": {"municipality": "MARILIA", "from": start.isoformat(), "through": end.isoformat(),
                            "record_types": ["SINISTRO FATAL", "SINISTRO NAO FATAL"], "road_type": "VIAS URBANAS", "bounds": config["bounds"]},
                "counts": dict(audit), "eligible_by_year": dict(sorted(years.items())),
                "daily_sha256": sha256(data / "contagens_diarias.csv"),
                "limitations": ["Setores experimentais; retangulo nao e limite municipal.",
                                "Zeros sao ausencia de registro elegivel no snapshot, nao prova de ausencia de acidente.",
                                "Snapshot revisado: nao existem datas de primeira publicacao por registro."]}
    write_json(data / "manifesto.json", manifest)
    write_json(data / "setores.geojson", sector_geojson(config))
    return manifest


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--zip", type=Path, default=ROOT / "fonte" / "dados_infosiga.zip")
    args = parser.parse_args()
    config = load_config()
    obtain_source(args.zip, config)
    result = prepare(args.zip, config)
    print(json.dumps(result["counts"], ensure_ascii=True), flush=True)
    print("Dados agregados prontos em dados/.", flush=True)


if __name__ == "__main__":
    main()
