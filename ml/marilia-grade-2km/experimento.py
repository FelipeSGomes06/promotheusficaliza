"""Experimento: grade de 2 km x 4 setores (ver PROTOCOLO.md). Etapas: preparar -> validar -> testar (uma vez)."""
from __future__ import annotations

import argparse
import calendar
import csv
import datetime as dt
import hashlib
import io
import json
import math
import unicodedata
import warnings
import zipfile
from pathlib import Path

import numpy as np
from sklearn.exceptions import ConvergenceWarning
from sklearn.linear_model import PoissonRegressor
from sklearn.metrics import mean_poisson_deviance
from sklearn.preprocessing import StandardScaler

ROOT = Path(__file__).resolve().parent
SECTORS_KIT = ROOT.parent / "marilia-setores"
SOURCE_SHA256 = "009481bdf80a030b4261e1b4f1585c6e85dfb20fe59f7244b9652b07d8efc9c8"
BOUNDS = {"south": -22.28, "north": -22.14, "west": -50.02, "east": -49.86}
ROWS = COLS = 8
START, LAST = dt.date(2019, 1, 1), dt.date(2026, 9, 30)
FIRST_MONDAY = dt.date(2020, 3, 2)
ALPHAS = [0.01, 0.1, 1.0, 10.0]
VALIDATION_ISSUED, TEST_ISSUED = dt.date(2025, 1, 1), dt.date(2026, 1, 1)
TEST_COMPLETE_UNTIL = dt.date(2026, 8, 1)
BUDGETS = [1, 2, 4, 16]
FEATURES = ["log1p_rate_13w", "log1p_rate_52w", "log1p_neighbors_rate_52w", "log1p_rate_since_2019",
            "annual_sin", "annual_cos", "years_since_2020"]
CELLS = ROWS * COLS


def cell_of(lat: float, lng: float) -> int | None:
    b = BOUNDS
    if not (b["south"] <= lat <= b["north"] and b["west"] <= lng <= b["east"]):
        return None
    # Mesma regra de borda dos setores: linha divisória vai para o norte/leste; bordas externas incluídas.
    r = min(int((lat - b["south"]) / ((b["north"] - b["south"]) / ROWS) + 1e-9), ROWS - 1)
    c = min(int((lng - b["west"]) / ((b["east"] - b["west"]) / COLS) + 1e-9), COLS - 1)
    return r * COLS + c


def sector_of(cell: int) -> str:
    r, c = divmod(cell, COLS)
    return ("N" if r >= ROWS // 2 else "S") + ("O" if c < COLS // 2 else "E")


def cutoff(as_of: dt.date) -> dt.date:
    index = as_of.year * 12 + as_of.month - 1 - 2
    year, month = divmod(index, 12)
    return dt.date(year, month + 1, 1)


def norm(text: str) -> str:
    return unicodedata.normalize("NFKD", text or "").encode("ascii", "ignore").decode().upper().strip()


def write_json(path: Path, data: object) -> None:
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def prepare(zip_path: Path) -> None:
    digest = hashlib.sha256(zip_path.read_bytes()).hexdigest()
    if digest != SOURCE_SHA256:
        raise SystemExit("Snapshot diferente do usado pelo pacote dos setores.")
    counts: dict[tuple[str, int], int] = {}
    seen = set()
    with zipfile.ZipFile(zip_path) as archive:
        for name in ("sinistros_2015-2021.csv", "sinistros_2022-2024.csv", "sinistros_2025-2026.csv"):
            with archive.open(name) as raw, io.TextIOWrapper(raw, encoding="cp1252", newline="") as handle:
                for row in csv.DictReader(handle, delimiter=";"):
                    if norm(row["municipio"]) != "MARILIA" or row["id_sinistro"] in seen:
                        continue
                    seen.add(row["id_sinistro"])
                    day = dt.datetime.strptime(row["data_sinistro"], "%d/%m/%Y").date()
                    if not START <= day <= LAST or row["tipo_registro"] not in {"SINISTRO FATAL", "SINISTRO NAO FATAL"} \
                            or row["tipo_via"] != "VIAS URBANAS":
                        continue
                    try:
                        cell = cell_of(float(row["latitude"].replace(",", ".")), float(row["longitude"].replace(",", ".")))
                    except ValueError:
                        continue
                    if cell is not None:
                        counts[(day.isoformat(), cell)] = counts.get((day.isoformat(), cell), 0) + 1
    data = ROOT / "dados"
    data.mkdir(exist_ok=True)
    with (data / "contagens_grade.csv").open("w", encoding="utf-8", newline="") as handle:
        writer = csv.writer(handle)
        writer.writerow(["date", "cell", "count"])
        for (day, cell), n in sorted(counts.items()):
            writer.writerow([day, cell, n])
    # Conferência: os quadrados somam exatamente as contagens diárias dos 4 setores do pacote ml/marilia-setores.
    sectors: dict[tuple[str, str], int] = {}
    for (day, cell), n in counts.items():
        sectors[(day, sector_of(cell))] = sectors.get((day, sector_of(cell)), 0) + n
    with (SECTORS_KIT / "dados" / "contagens_diarias.csv").open(encoding="utf-8", newline="") as handle:
        for row in csv.DictReader(handle):
            if sectors.get((row["date"], row["sector_id"]), 0) != int(row["count"]):
                raise SystemExit(f"Divergência com os setores em {row['date']} {row['sector_id']}.")
    print(f"Grade preparada: {sum(counts.values())} registros; soma por setor/dia idêntica ao pacote dos setores.")


class Grid:
    def __init__(self) -> None:
        days = (LAST - START).days + 1
        self.daily = np.zeros((days, CELLS), dtype=np.int64)
        with (ROOT / "dados" / "contagens_grade.csv").open(encoding="utf-8", newline="") as handle:
            for row in csv.DictReader(handle):
                self.daily[(dt.date.fromisoformat(row["date"]) - START).days, int(row["cell"])] += int(row["count"])
        self.cum = np.vstack([np.zeros((1, CELLS), dtype=np.int64), np.cumsum(self.daily, axis=0)])
        self.neighbors = []
        for cell in range(CELLS):
            r, c = divmod(cell, COLS)
            self.neighbors.append([rr * COLS + cc for rr in range(r - 1, r + 2) for cc in range(c - 1, c + 2)
                                   if 0 <= rr < ROWS and 0 <= cc < COLS and (rr, cc) != (r, c)])

    def total(self, begin: dt.date, end: dt.date) -> np.ndarray:
        """Contagem por quadrado em [begin, end)."""
        return self.cum[(end - START).days] - self.cum[(begin - START).days]

    def week_rows(self, monday: dt.date) -> list[dict]:
        end = cutoff(monday)
        c52 = self.total(end - dt.timedelta(days=364), end)
        c13 = self.total(end - dt.timedelta(days=91), end)
        since = self.total(START, end)
        years_known = (end - START).days / 365.25
        days_in_year = 366 if calendar.isleap(monday.year) else 365
        phase = 2 * math.pi * (monday - dt.date(monday.year, 1, 1)).days / days_in_year
        label = self.total(monday, monday + dt.timedelta(days=7))
        rows = []
        for cell in range(CELLS):
            neighbors = float(np.mean([c52[n] for n in self.neighbors[cell]]))
            rows.append({
                "week": monday.isoformat(), "cell": cell, "sector": sector_of(cell), "y": int(label[cell]),
                "c52": int(c52[cell]),
                "x": [math.log1p(c13[cell] / 13), math.log1p(c52[cell] / 52), math.log1p(neighbors / 52),
                      math.log1p(since[cell] / (years_known * 52.18)), math.sin(phase), math.cos(phase),
                      (monday - dt.date(2020, 1, 1)).days / 365.25],
                "baseline": (c52[cell] + 0.1) / 52.1,
                "static": float(since[cell]),
            })
        return rows


def mondays(first: dt.date, label_until: dt.date) -> list[dt.date]:
    result, day = [], first
    while day + dt.timedelta(days=7) <= label_until:
        result.append(day)
        day += dt.timedelta(days=7)
    return result


def fit(grid: Grid, issued: dt.date, alpha: float) -> tuple[StandardScaler, PoissonRegressor]:
    rows = [r for m in mondays(FIRST_MONDAY, cutoff(issued)) for r in grid.week_rows(m)]
    x = np.array([r["x"] for r in rows])
    y = np.array([r["y"] for r in rows], dtype=float)
    scaler = StandardScaler().fit(x)
    model = PoissonRegressor(alpha=alpha, max_iter=3000, tol=1e-8)
    with warnings.catch_warnings():
        warnings.simplefilter("error", ConvergenceWarning)
        model.fit(scaler.transform(x), y)
    return scaler, model


def evaluate(grid: Grid, weeks: list[dt.date], scaler: StandardScaler, model: PoissonRegressor) -> dict:
    """Capturas por semana para cada método; a fração é sobre os registros elegíveis da área de estudo."""
    per_week = []
    deviance: dict[str, list[tuple[float, float]]] = {"grade_referencia": [], "grade_poisson": []}
    for monday in weeks:
        rows = grid.week_rows(monday)
        poisson = model.predict(scaler.transform(np.array([r["x"] for r in rows])))
        y = np.array([r["y"] for r in rows])
        scores = {"grade_referencia": np.array([r["baseline"] for r in rows]),
                  "grade_historico": np.array([r["static"] for r in rows]),
                  "grade_poisson": poisson}
        week = {"week": monday.isoformat(), "observed": int(y.sum())}
        for name, s in scores.items():
            order = sorted(range(CELLS), key=lambda i: (-float(s[i]), i))
            for k in BUDGETS:
                week[f"{name}_{k}"] = int(y[order[:k]].sum())
            if name != "grade_historico":
                deviance[name] += list(zip(y.tolist(), s.tolist()))
        sector_rate = {sid: (sum(r["c52"] for r in rows if r["sector"] == sid) + 1) / 53 for sid in ("NE", "NO", "SE", "SO")}
        top = sorted(sector_rate, key=lambda sid: (-sector_rate[sid], sid))[0]
        week["setores_16"] = int(sum(r["y"] for r in rows if r["sector"] == top))
        week["setor_escolhido"] = top
        per_week.append(week)
    total = sum(w["observed"] for w in per_week)
    summary = {"weeks": len(per_week), "from": weeks[0].isoformat(), "observed": total}
    for key in [k for k in per_week[0] if k not in ("week", "observed", "setor_escolhido")]:
        captured = sum(w[key] for w in per_week)
        summary[key] = {"captured": captured, "fraction": captured / total if total else None}
    for name, pairs in deviance.items():
        obs, pred = zip(*pairs)
        summary[f"desvio_{name}"] = float(mean_poisson_deviance(np.array(obs), np.maximum(np.array(pred), 1e-9)))
    return {"summary": summary, "weeks": per_week}


def bootstrap(weeks: list[dict], a: str, b: str, seed: int = 2026, draws: int = 2000) -> dict:
    rng = np.random.default_rng(seed)
    obs = np.array([w["observed"] for w in weeks], dtype=float)
    ca, cb = np.array([w[a] for w in weeks], dtype=float), np.array([w[b] for w in weeks], dtype=float)
    diffs = []
    for _ in range(draws):
        i = rng.integers(0, len(weeks), len(weeks))
        if obs[i].sum() > 0:
            diffs.append((ca[i].sum() - cb[i].sum()) / obs[i].sum())
    point = (ca.sum() - cb.sum()) / obs.sum()
    return {"diferenca": float(point), "ic95": [float(np.percentile(diffs, 2.5)), float(np.percentile(diffs, 97.5))]}


def validate() -> None:
    grid = Grid()
    weeks = mondays(dt.date(2025, 1, 6), cutoff(TEST_ISSUED))
    candidates = []
    for alpha in ALPHAS:
        scaler, model = fit(grid, VALIDATION_ISSUED, alpha)
        result = evaluate(grid, weeks, scaler, model)
        candidates.append({"alpha": alpha, "desvio": result["summary"]["desvio_grade_poisson"], "summary": result["summary"]})
        print(f"alpha={alpha:g} desvio={candidates[-1]['desvio']:.4f}")
    best = min(candidates, key=lambda c: (c["desvio"], c["alpha"]))
    out = ROOT / "resultados"
    out.mkdir(exist_ok=True)
    write_json(out / "validacao.json", {"alpha_escolhido": best["alpha"], "candidatos": candidates})
    print(f"Alpha escolhido na validação: {best['alpha']:g}")


def test() -> None:
    out = ROOT / "resultados"
    if (out / "teste.json").exists():
        raise SystemExit("O teste já foi executado uma vez; não se repete (PROTOCOLO.md).")
    alpha = json.loads((out / "validacao.json").read_text(encoding="utf-8"))["alpha_escolhido"]
    grid = Grid()
    scaler, model = fit(grid, TEST_ISSUED, alpha)
    main = evaluate(grid, mondays(dt.date(2026, 1, 5), TEST_COMPLETE_UNTIL), scaler, model)
    secondary = evaluate(grid, [m for m in mondays(dt.date(2026, 1, 5), LAST + dt.timedelta(days=1))
                                if m + dt.timedelta(days=7) > TEST_COMPLETE_UNTIL], scaler, model)
    comparisons = {f"{name}_16_menos_setores": bootstrap(main["weeks"], f"{name}_16", "setores_16")
                   for name in ("grade_referencia", "grade_historico", "grade_poisson")}
    comparisons["poisson_4_menos_referencia_4"] = bootstrap(main["weeks"], "grade_poisson_4", "grade_referencia_4")
    write_json(out / "teste.json", {"alpha": alpha, "modelo_emitido": TEST_ISSUED.isoformat(),
                                    "rotulos_treino_ate": cutoff(TEST_ISSUED).isoformat(),
                                    "principal": main, "comparacoes": comparisons, "secundario_ago_set": secondary})
    print(json.dumps({"resumo": main["summary"], "comparacoes": comparisons,
                      "secundario": secondary["summary"]}, ensure_ascii=False, indent=1))


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("etapa", choices=["preparar", "validar", "testar"])
    parser.add_argument("--zip", type=Path)
    args = parser.parse_args()
    if args.etapa == "preparar":
        prepare(args.zip)
    elif args.etapa == "validar":
        validate()
    else:
        test()
