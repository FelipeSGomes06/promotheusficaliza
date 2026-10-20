"""Treino CPU de contagens semanais, com ajuste temporal e exportacao JSON sem pickle."""
from __future__ import annotations

import os
for variable in ("OMP_NUM_THREADS", "OPENBLAS_NUM_THREADS", "MKL_NUM_THREADS"):
    os.environ.setdefault(variable, "2")

import argparse
import calendar
import csv
import datetime as dt
import json
import math
from pathlib import Path
import platform
import time
import warnings

import numpy as np
import sklearn
from sklearn.exceptions import ConvergenceWarning
from sklearn.linear_model import PoissonRegressor
from sklearn.metrics import mean_absolute_error, mean_poisson_deviance
from sklearn.preprocessing import StandardScaler

from preparar_dados import ROOT, load_config, sha256, write_json


def date(value: str) -> dt.date:
    return dt.date.fromisoformat(value)


def availability_cutoff(as_of: dt.date, lag: int) -> dt.date:
    """Exclusivo: em outubro com lag=2, dados anteriores a 1/agosto."""
    month_index = as_of.year * 12 + as_of.month - 1 - lag
    year, month = divmod(month_index, 12)
    return dt.date(year, month + 1, 1)


class Counts:
    def __init__(self, config: dict):
        self.config = config
        self.sectors = [s["id"] for s in config["sectors"]]
        self.start = date(config["events_from"])
        self.until = date(config["data_through"]) + dt.timedelta(days=1)
        self.daily = np.zeros(((self.until - self.start).days, len(self.sectors)), dtype=np.int64)
        file = ROOT / "dados" / "contagens_diarias.csv"
        manifest = json.loads((ROOT / "dados" / "manifesto.json").read_text(encoding="utf-8"))
        if sha256(file) != manifest["daily_sha256"] or manifest["source"]["source_sha256"] != config["source_sha256"]:
            raise ValueError("Dados/configuracao divergentes do manifesto.")
        expected_rows = self.daily.size
        seen = set()
        with file.open(encoding="utf-8", newline="") as handle:
            for row in csv.DictReader(handle):
                i = (date(row["date"]) - self.start).days
                j = self.sectors.index(row["sector_id"])
                if not 0 <= i < len(self.daily) or (i, j) in seen or int(row["count"]) < 0:
                    raise ValueError("Contagem diaria duplicada ou fora do intervalo.")
                seen.add((i, j))
                self.daily[i, j] = int(row["count"])
        if len(seen) != expected_rows:
            raise ValueError("Grade diaria incompleta: faltam periodos com zero.")
        self.cumulative = np.vstack([np.zeros((1, len(self.sectors))), np.cumsum(self.daily, axis=0)])

    def total(self, begin: dt.date, end: dt.date, sector: int) -> float:
        if not self.start <= begin <= end <= self.until:
            raise ValueError("Consulta fora da cobertura do snapshot.")
        return float(self.cumulative[(end-self.start).days, sector] - self.cumulative[(begin-self.start).days, sector])

    def features(self, as_of: dt.date, sector: int) -> tuple[list[float], dict]:
        end = min(availability_cutoff(as_of, self.config["availability_lag_months"]), self.until)
        if (end - self.start).days < 364:
            raise ValueError("Historico insuficiente para 52 semanas.")
        long_start, short_start = end - dt.timedelta(days=364), end - dt.timedelta(days=91)
        count52 = self.total(long_start, end, sector)
        count13 = self.total(short_start, end, sector)
        rate52, rate13 = (count52 + 1) / 53, (count13 + 1) / 14
        days_in_year = 366 if calendar.isleap(as_of.year) else 365
        phase = 2 * math.pi * ((as_of - dt.date(as_of.year, 1, 1)).days / days_in_year)
        values = [float(sector == i) for i in range(len(self.sectors))]
        values += [math.log1p(rate13), math.log1p(rate52), math.sin(phase), math.cos(phase),
                   (as_of - dt.date(2020, 1, 1)).days / 365.25]
        evidence = {"history_through": (end-dt.timedelta(days=1)).isoformat(), "history_start": long_start.isoformat(),
                    "count_13w": int(count13), "count_52w": int(count52),
                    "baseline_expected_count": rate52, "data_age_days": (as_of-end).days,
                    "availability": "assumed_publication_delay_not_observed"}
        return values, evidence


def feature_names(config: dict) -> list[str]:
    return ["sector_" + s["id"] for s in config["sectors"]] + [
        "log1p_rate_13w", "log1p_rate_52w", "annual_sin", "annual_cos", "years_since_2020"]


def rows(counts: Counts) -> list[dict]:
    result = []
    day = date(counts.config["training_rows_from"])
    if day.weekday() != 0:
        raise ValueError("Cortes de treinamento devem comecar numa segunda-feira.")
    while day + dt.timedelta(days=7) <= counts.until:
        for j, sid in enumerate(counts.sectors):
            features, evidence = counts.features(day, j)
            result.append({"from": day.isoformat(), "until": (day+dt.timedelta(days=7)).isoformat(),
                           "sector_id": sid, "x": features, "evidence": evidence,
                           "y": counts.total(day, day+dt.timedelta(days=7), j)})
        day += dt.timedelta(days=7)
    return result


def labels_available(all_rows: list[dict], issued_at: dt.date, config: dict) -> list[dict]:
    cutoff = availability_cutoff(issued_at, config["availability_lag_months"])
    return [r for r in all_rows if date(r["until"]) <= cutoff]


def fit(selected: list[dict], alpha: float) -> dict:
    if not selected:
        raise ValueError("Treino vazio.")
    x = np.array([r["x"] for r in selected], dtype=float)
    y = np.array([r["y"] for r in selected], dtype=float)
    scaler = StandardScaler().fit(x)
    model = PoissonRegressor(alpha=alpha, max_iter=2000, tol=1e-7)
    with warnings.catch_warnings():
        warnings.simplefilter("error", ConvergenceWarning)
        model.fit(scaler.transform(x), y)
    return {"scaler": scaler, "model": model, "alpha": alpha,
            "training_from": min(r["from"] for r in selected),
            "training_labels_until": max(r["until"] for r in selected), "training_rows": len(selected)}


def predict(fitted: dict, selected: list[dict]) -> np.ndarray:
    return fitted["model"].predict(fitted["scaler"].transform(np.array([r["x"] for r in selected])))


def baseline(selected: list[dict]) -> np.ndarray:
    return np.array([r["evidence"]["baseline_expected_count"] for r in selected], dtype=float)


def metrics(selected: list[dict], predicted: np.ndarray, budget: int) -> dict:
    observed = np.array([r["y"] for r in selected], dtype=float)
    weekly = {}
    for i, r in enumerate(selected):
        weekly.setdefault(r["from"], []).append(i)
    captured = 0.0
    for indices in weekly.values():
        chosen = sorted(indices, key=lambda i: (-float(predicted[i]), selected[i]["sector_id"]))[:budget]
        captured += float(sum(observed[i] for i in chosen))
    total = float(sum(observed))
    return {"mae": float(mean_absolute_error(observed, predicted)),
            "mean_poisson_deviance": float(mean_poisson_deviance(observed, np.maximum(predicted, 1e-12))),
            "observed_count": int(total), "predicted_count": float(sum(predicted)),
            "rows": len(selected), "weeks": len(weekly), "top_sectors_budget": budget,
            "top_budget_captured": int(captured), "top_budget_capture_fraction": captured/total if total else None,
            "from": min(r["from"] for r in selected), "until": max(r["until"] for r in selected)}


def portable(fitted: dict, config: dict) -> dict:
    return {"kind": "poisson_log_link_standardized", "alpha": fitted["alpha"],
            "feature_names": feature_names(config), "mean": fitted["scaler"].mean_.tolist(),
            "scale": fitted["scaler"].scale_.tolist(), "coefficients": fitted["model"].coef_.tolist(),
            "intercept": float(fitted["model"].intercept_),
            "training_from": fitted["training_from"], "training_labels_until": fitted["training_labels_until"],
            "training_rows": fitted["training_rows"]}


def portable_predict(model: dict, x: list[float]) -> float:
    value = model["intercept"] + sum(c*(v-m)/s for v, m, s, c in zip(
        x, model["mean"], model["scale"], model["coefficients"], strict=True))
    return math.exp(value)


def result_rows(selected: list[dict], predicted: np.ndarray, base: np.ndarray) -> list[dict]:
    return [{"from": r["from"], "until": r["until"], "sector_id": r["sector_id"],
             "expected_count_ml": float(predicted[i]), "expected_count_baseline": float(base[i]),
             "observed_count": int(r["y"]), "evidence": r["evidence"]} for i, r in enumerate(selected)]


def run(output: Path, as_of: dt.date, mode: str) -> dict:
    started = time.perf_counter()
    if output.exists():
        raise ValueError("Diretorio de saida ja existe; escolha outro nome para preservar a execucao anterior.")
    output.mkdir(parents=True)
    config = load_config()
    counts = Counts(config)
    all_rows = rows(counts)
    val_issued, test_issued = date(config["validation_issued_at"]), date(config["test_issued_at"])
    val_train = labels_available(all_rows, val_issued, config)
    val_until = availability_cutoff(test_issued, config["availability_lag_months"])
    validation = [r for r in all_rows if date(r["from"]) >= val_issued and date(r["until"]) <= val_until]
    budget = config["top_sectors_budget"]
    val_base = metrics(validation, baseline(validation), budget)
    candidates = []
    print(f"[1/4] {len(all_rows)} linhas semana/setor; {len(val_train)} treino, {len(validation)} ajuste.", flush=True)
    for alpha in config["alpha_candidates"]:
        fitted = fit(val_train, alpha)
        measured = metrics(validation, predict(fitted, validation), budget)
        candidates.append({"alpha": alpha, "validation": measured})
        write_json(output / "progresso.json", {"phase": "validation", "candidates_done": candidates})
        print(f"alpha={alpha:g} desvio={measured['mean_poisson_deviance']:.4f} MAE={measured['mae']:.4f}", flush=True)
    best = min(candidates, key=lambda c: (c["validation"][config["selection_metric"]], c["alpha"]))
    # Esta escolha acontece ANTES da consulta ao teste final de 2025.
    chosen = "poisson" if best["validation"][config["selection_metric"]] < val_base[config["selection_metric"]] else "baseline"
    selection = {"selected_before_test": chosen, "selected_alpha": best["alpha"],
                 "metric": config["selection_metric"], "baseline_validation": val_base, "candidates": candidates,
                 "validation_label_availability_cutoff": val_until.isoformat()}
    write_json(output / "selecao.json", selection)
    print(f"[2/4] Escolha por ajuste: {chosen}, alpha candidato={best['alpha']:g}.", flush=True)
    if mode == "piloto":
        write_json(output / "status.json", {"status": "pilot_complete", "final_test_evaluated": False,
                                            "selection": selection, "seconds": time.perf_counter()-started})
        return selection

    test_train = labels_available(all_rows, test_issued, config)
    test = [r for r in all_rows if date(r["from"]) >= test_issued and date(r["until"]) <= date(config["test_until"])]
    frozen = fit(test_train, best["alpha"])
    test_ml, test_base = predict(frozen, test), baseline(test)
    metrics_ml, metrics_base = metrics(test, test_ml, budget), metrics(test, test_base, budget)
    evaluation = {"schema_version": 1, "protocol_version": config["protocol_version"],
                  "config_sha256": sha256(ROOT / "config.json"),
                  "selected_before_test": chosen, "selected_alpha": best["alpha"],
                  "test_model": portable(frozen, config), "poisson": metrics_ml, "baseline": metrics_base,
                  "poisson_deviance_relative_gain": 1-metrics_ml["mean_poisson_deviance"]/metrics_base["mean_poisson_deviance"],
                  "limitations": ["Retrospecto de snapshot revisado; primeira publicacao de cada registro nao e conhecida.",
                                  "Dois meses completos retidos simulam atraso de publicacao; e uma hipotese.",
                                  "Dados/teste real separados temporalmente; sem validacao prospectiva nem causal.",
                                  "Cobertura: registros elegiveis no retangulo; setores nao sao bairros oficiais."]}
    write_json(output / "avaliacao.json", evaluation)
    write_json(output / "retrospecto.json", {"schema_version": 1, "mode": "historical_replay",
               "model_issued_at": test_issued.isoformat(), "selected_method": chosen,
               "rows": result_rows(test, test_ml, test_base)})
    print(f"[3/4] Teste: MAE ML={metrics_ml['mae']:.4f}, referencia={metrics_base['mae']:.4f}.", flush=True)

    if as_of < test_issued or as_of < date(config["test_until"]):
        raise ValueError("Previsao operacional requer data posterior ao teste. Para o passado use retrospecto.json.")
    issue_cutoff = availability_cutoff(as_of, config["availability_lag_months"])
    reference = min(issue_cutoff, counts.until)
    if (as_of - reference).days > 180:
        raise ValueError("Base muito antiga para esta emissao (>180 dias); atualizar e revalidar dados.")
    final = fit(labels_available(all_rows, as_of, config), best["alpha"])
    export = portable(final, config)
    current = []
    for i, sid in enumerate(counts.sectors):
        x, evidence = counts.features(as_of, i)
        current.append({"from": as_of.isoformat(), "until": (as_of+dt.timedelta(days=7)).isoformat(),
                        "sector_id": sid, "x": x, "evidence": evidence})
    prediction_ml, prediction_base = predict(final, current), baseline(current)
    selected = prediction_ml if chosen == "poisson" else prediction_base
    rank = sorted(range(len(current)), key=lambda i: (-selected[i], current[i]["sector_id"]))
    ordinal = {i: n+1 for n, i in enumerate(rank)}
    forecast = {"schema_version": 1, "protocol_version": config["protocol_version"],
                "mode": "forecast", "as_of": as_of.isoformat(), "until": (as_of+dt.timedelta(days=7)).isoformat(),
                "generated_at_utc": dt.datetime.now(dt.timezone.utc).isoformat(),
                "source_snapshot_sha256": config["source_sha256"], "data_reference_through": (reference-dt.timedelta(days=1)).isoformat(),
                "selected_method": chosen, "candidate_method": "poisson", "unit": "expected_registered_crashes_in_7_days",
                "test_status": "experimental_retrospective", "test_relative_gain": evaluation["poisson_deviance_relative_gain"],
                "sectors": [{"sector_id": r["sector_id"], "rank": ordinal[i],
                             "priority": "PRIORITARIA" if ordinal[i] <= budget else "ACOMPANHAR",
                             "expected_count": float(selected[i]), "expected_count_ml": float(prediction_ml[i]),
                             "expected_count_baseline": float(prediction_base[i]), "evidence": r["evidence"]}
                            for i, r in enumerate(current)],
                "notice": "Prioridade relativa. Nao e probabilidade individual. Fonte mensal; equipes ficticias na demonstracao."}
    vectors = current + test[:16]
    expected = predict(final, vectors)
    tests = [{"x": r["x"], "expected": float(expected[i])} for i, r in enumerate(vectors)]
    for vector in tests:
        if not math.isclose(portable_predict(export, vector["x"]), vector["expected"], rel_tol=1e-10, abs_tol=1e-10):
            raise ValueError("Exportacao numerica diverge do modelo.")
    write_json(output / "modelo.json", export | {"protocol_version": config["protocol_version"], "config": config})
    write_json(output / "vetores_paridade.json", {"model": export, "vectors": tests})
    write_json(output / "previsao.json", forecast)
    write_json(output / "status.json", {"status": "complete", "final_test_evaluated": True,
               "seconds": time.perf_counter()-started, "python": platform.python_version(),
               "numpy": np.__version__, "sklearn": sklearn.__version__, "selected_method": chosen})
    report = ["# Resultado do treino", "", f"Metodo escolhido no ajuste: **{chosen}**. Candidato Poisson alpha={best['alpha']}.", "",
              "| Teste temporal 2025 | Poisson | Referencia |", "|---|---:|---:|",
              f"| Erro absoluto medio por setor/semana | {metrics_ml['mae']:.4f} | {metrics_base['mae']:.4f} |",
              f"| Desvio medio de Poisson (menor e melhor) | {metrics_ml['mean_poisson_deviance']:.4f} | {metrics_base['mean_poisson_deviance']:.4f} |",
              f"| Fracao de registros no setor priorizado por semana | {metrics_ml['top_budget_capture_fraction']:.1%} | {metrics_base['top_budget_capture_fraction']:.1%} |", "",
              f"Ganho relativo de desvio do candidato: {evaluation['poisson_deviance_relative_gain']:.1%}.", "",
              "Estes resultados sao retrospectivos, com atraso de publicacao assumido e snapshot revisado. Nao demonstram efeito de enviar agentes.", "",
              f"Previsao: {forecast['as_of']} a {forecast['until']} (fim exclusivo); dados de entrada ate {forecast['data_reference_through']}.", "",
              f"Execucao: {time.perf_counter()-started:.2f} segundos. Nenhuma GPU ou servico remoto utilizado."]
    (output / "RESULTADO.md").write_text("\n".join(report)+"\n", encoding="utf-8")
    print(f"[4/4] Concluido em {time.perf_counter()-started:.2f}s: {output}", flush=True)
    return evaluation


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--modo", choices=["piloto", "completo"], default="completo")
    parser.add_argument("--as-of", type=date, default=dt.date.today())
    parser.add_argument("--saida", type=Path)
    args = parser.parse_args()
    output = args.saida or ROOT / "runs" / dt.datetime.now().strftime("%Y%m%d-%H%M%S-%f")
    run(output.resolve(), args.as_of, args.modo)


if __name__ == "__main__":
    main()
