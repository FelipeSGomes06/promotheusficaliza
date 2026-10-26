# Dados e treino do planejamento (`ml/`)

Trabalho de dados **separado da aplicação**: Python no computador de quem desenvolve, nunca no servidor. A aplicação
consome os arquivos daqui por `php bin/fiscaliza urban:import` e, depois, se atualiza sozinha em PHP com
`urban:update` (download mensal do Infosiga-SP + previsão semanal). Contratos de cada função e números do resultado:
[referência — treino e experimento](../docs/referencia/codigo/ml/treino-e-experimento.md). Como a tela usa isso:
[planejamento por setores](../docs/planejamento-por-setores.md).

| Pasta | Origem | O que tem | Usado pela aplicação |
|---|---|---|---|
| `marilia-setores/` | pacote de treino de 20/10/2026 — **não editar** (o `UrbanImportTest` confere os 26 arquivos contra `ENTREGA-MANIFESTO.json`) | preparo dos dados, treino Poisson × referência por 4 setores, testes de protocolo, paridade PHP, duas execuções verificadas; leia primeiro `00-COMECE-AQUI.md` | `runs/primeiro-treino-verificado/` é a execução padrão de `urban:import` |
| `marilia-grade-2km/` | experimento do projeto, protocolo registrado antes de rodar | grade 8 × 8 de ~2 km, contagens por quadrado e dia, validação e teste únicos | `dados/contagens_grade.csv` é a grade padrão de `urban:import` |

## Contrato dos dados

| Arquivo | Conteúdo | Origem |
|---|---|---|
| `marilia-setores/config.json` | protocolo `marilia-setores-v1`, URL e SHA-256 do snapshot, retângulo de estudo, setores, períodos, alphas, métrica | autoria do pacote |
| `marilia-setores/dados/contagens_diarias.csv` | `date,sector_id,count` — grade **completa** de 2019-01-01 a 2026-09-30 × 4 setores, zeros incluídos | agregado do Infosiga-SP |
| `marilia-setores/dados/manifesto.json` | fonte, filtros, auditoria de cada filtro (12.725 → 8.419 elegíveis), SHA-256 do CSV, limitações | gerado por `preparar_dados.py` |
| `marilia-setores/dados/setores.geojson` | 4 retângulos experimentais (`official_neighborhood: false`) | gerado |
| `marilia-setores/runs/*/` | seleção, avaliação, retrospecto, modelo portátil, vetores de paridade, previsão | gerado por `treino.py` |
| `marilia-grade-2km/dados/contagens_grade.csv` | `date,cell,count` — só dias/quadrados com registro (ausência = zero) | agregado do mesmo snapshot |
| `marilia-grade-2km/resultados/` | `validacao.json`, `teste.json`, `teste-saida.txt` | gerado por `experimento.py` |

Fonte: Infosiga-SP / Detran-SP, licença **CC BY 4.0** ([licenças e fontes](../docs/licencas-e-fontes.md)). Só contagens
agregadas por dia e área: nenhuma vítima, veículo ou linha bruta é guardada. O arquivo bruto (~141 MB) e o ambiente
`.venv/` ficam fora do Git.

## Limites que valem para tudo aqui

- Os números são de um **snapshot revisado**; não se sabe quando cada registro foi publicado pela primeira vez. O
  atraso de dois meses é uma **hipótese**.
- Medem onde **registros** se concentram, não risco por veículo, e **não** provam que reforço evita sinistros.
- Setores e quadrados são recortes experimentais do retângulo de estudo, não bairros nem limite municipal.
- Na tela, a previsão usada é a **referência histórica**; o Poisson dos setores aparece só como comparação
  experimental, e o Poisson da grade não é usado.

## Rodar de novo

Treino dos setores (Windows): dois cliques em `marilia-setores/TREINAR.cmd` (cria `.venv`, instala
`requirements.txt` só com binários, roda os testes e treina numa pasta nova de `runs/`). Requer Python ≥ 3.12. Rodar de
novo com os mesmos dados não melhora nada. O teste da grade **não** se repete (o script recusa).
