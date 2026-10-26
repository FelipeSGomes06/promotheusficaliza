# Protocolo — grade de 2 km (registrado em 20/10/2026, antes de qualquer cálculo em 2026)

Pergunta: dividir a área de estudo em quadrados de ~2 km faz o reforço preventivo cair onde mais sinistros são
registrados do que os 4 setores atuais? Experimento separado de `ml/marilia-setores/` (que não é alterado).

## Dados
- Mesmo snapshot e mesmos filtros do pacote `ml/marilia-setores/` (Infosiga-SP/Detran-SP, CC BY 4.0, SHA-256
  `009481bd…c9c8`): Marília, sinistro fatal/não fatal, vias urbanas, coordenada no retângulo
  lat −22,28 a −22,14 × long −50,02 a −49,86, 2019-01-01 a 2026-09-30.
- Grade: 8 × 8 = 64 quadrados (0,0175° lat × 0,02° long ≈ 1,95 × 2,06 km). Cada setor atual = 4 × 4 quadrados,
  com a mesma regra de borda. Conferência obrigatória: a soma diária dos quadrados = contagem diária dos setores do
  pacote `ml/marilia-setores/`.
- Mesmo atraso assumido: cada corte só usa dados anteriores ao 1º dia do mês, dois meses antes da emissão.

## Métodos (definidos antes de rodar)
- **Setores (referência atual):** taxa de 52 semanas do setor, (n+1)/53; orçamento = 1 setor = 16 quadrados.
- **Grade — referência:** taxa de 52 semanas do quadrado, (n + 0,1)/52,1.
- **Grade — histórico fixo:** total do quadrado desde 2019 até o corte (o "ponto quente de sempre").
- **Grade — Poisson:** regressão de Poisson regularizada com: ritmo de 13 e 52 semanas do quadrado, ritmo de 52
  semanas dos 8 vizinhos, ritmo do quadrado desde 2019, época do ano (seno/cosseno), anos desde 2020. Alpha em
  {0,01; 0,1; 1; 10}, escolhido pelo menor desvio de Poisson na validação. Empate na ordem: código do quadrado.

## Períodos
| Etapa | Emissão | Rótulos de treino até (excl.) | Semanas avaliadas |
|---|---|---|---|
| Validação (escolha do alpha) | 01/01/2025 | 01/11/2024 | 2025, até 01/11/2025 |
| **Teste (uma única vez)** | 01/01/2026 | 01/11/2025 | **segundas de 05/01/2026 com fim ≤ 01/08/2026** |
| Teste secundário | idem | idem | semanas de agosto e setembro/2026 (podem estar incompletas no snapshot) |

2025 já foi visto no trabalho anterior; aqui serve só como validação. **2026 nunca foi olhado** por nenhum método
antes desta execução; o modelo de teste fica congelado em 01/01/2026.

## Medidas (definidas antes de rodar)
- **Principal:** fração dos registros elegíveis do período que caíram na área priorizada, com **a mesma área**:
  1 setor (16 quadrados) × os 16 quadrados de maior estimativa de cada método da grade.
- **Operacional:** fração capturada com 1, 2 e 4 quadrados (≈ 4, 8 e 16 km²).
- Intervalo de 95% por reamostragem de semanas (2.000 reamostras, semente 2026) para a diferença grade − setores.
- Desvio médio de Poisson por quadrado/semana, só entre os métodos da grade.
- "Acerta mais" só se a diferença na medida principal for positiva **e** o intervalo não incluir zero. Resultado
  fraco ou negativo é relatado do mesmo jeito; nada é reajustado depois de ver 2026.
