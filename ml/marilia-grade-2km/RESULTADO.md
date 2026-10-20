# Resultado — grade de 2 km × 4 setores (20/10/2026)

Protocolo registrado antes de rodar: [PROTOCOLO.md](PROTOCOLO.md). Dados: Infosiga-SP / Detran-SP (CC BY 4.0),
snapshot de 20/10/2026; agregação e modelo pelo projeto Promotheus Fiscaliza. `dados/contagens_grade.csv` tem só
contagens por quadrado e dia; a soma bate exatamente com as contagens diárias dos 4 setores (8.419 registros).

Comandos (Python com numpy e scikit-learn, como em `ml/marilia-setores/requirements.txt`):

```powershell
python experimento.py preparar --zip <caminho>\dados_infosiga.zip
python experimento.py validar     # escolhe o alpha em 2025
python experimento.py testar      # 2026; recusa rodar uma segunda vez
```

Validação (2025): alpha 0,01. Teste rodado **uma vez**, modelo congelado em 01/01/2026 (rótulos até 01/11/2025).

## Teste principal: 29 semanas de 05/01 a 26/07/2026, 452 registros elegíveis

Fração dos registros que caíram na área priorizada a cada semana:

| Área priorizada | Setores (atual) | Grade · referência | Grade · histórico fixo | Grade · Poisson |
|---|---:|---:|---:|---:|
| 1 quadrado (~4 km²) | — | 27,2% | 27,2% | 27,2% |
| 2 quadrados (~8 km²) | — | 40,0% | 40,0% | 40,0% |
| 4 quadrados (~16 km²) | — | 51,5% | 51,3% | 51,5% |
| 1 setor = 16 quadrados (~64 km²) | **39,8%** | **91,2%** | 91,8% | 89,8% |

Diferença grade − setores com a mesma área: **+51 pontos** (IC 95% de +46 a +56) na referência; +50 no Poisson.
Poisson × referência com 4 quadrados: diferença 0 (escolheram os mesmos quadrados). Desvio médio de Poisson:
referência 0,375; Poisson 0,521 (pior).

Secundário, agosto e setembro/2026 (9 semanas, 150 registros, possivelmente incompletos): mesmo padrão. Setores
44,7%; grade 26,7% com 1 quadrado, 50,7% com 4 e 92,7% com 16.

## Leitura honesta

1. **A grade acerta muito mais, e o motivo é geográfico.** O centro de Marília (quadrado em torno de
   −22,219 / −49,95) fica quase no cruzamento dos quatro setores, que o cortavam em pedaços. Só esse quadrado
   concentrou 24% dos registros de 2024–2025. Com 4 quadrados (um quarto da área de um setor), a grade já pega mais
   registros (51%) que o setor inteiro (40%).
2. **O Poisson não acrescentou nada.** Escolheu os mesmos quadrados que a média simples e o "histórico de sempre", e
   errou mais na quantidade. O ganho vem de dividir melhor, não de um modelo mais sofisticado.
3. **A previsão é quase fixa.** O setor escolhido foi o sudoeste em todas as 29 semanas, e os quadrados do topo
   mudam pouco. Na prática, isso é um mapa dos pontos que concentram registros, não algo que muda de semana a semana.
   Para variar por semana seria preciso outra informação (turno, dia da semana, eventos).
4. **Com 16 quadrados, a comparação fica fácil demais:** só 31 dos 64 quadrados tiveram registro em 2024–2025, então
   16 quadrados cobrem quase toda a parte urbanizada. A comparação útil é a de 1 a 4 quadrados.
5. Continuam valendo: snapshot revisado, atraso de publicação assumido, 29 semanas (amostra pequena), contagem de
   registros (não de risco por veículo) e nenhuma prova de que reforço evita sinistros.

**Integrado em 20/10/2026** na tela Planejar reforço (seção "Onde concentrar o reforço", 1 a 4 quadrados, média
simples), via `urban:import`. **Recomendação original:** usar a grade de 2 km com a **média simples** das 52 semanas e mostrar de 1 a 4 quadrados
prioritários; não usar o Poisson nessa grade. O teste de 2026 agora também é conhecido: uma melhoria nova só pode ser
confirmada com semanas que ainda vão ser publicadas.
