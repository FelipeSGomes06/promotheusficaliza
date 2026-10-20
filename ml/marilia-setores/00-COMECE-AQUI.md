# Pacote para o Claude: planejamento preventivo de Marília

Pronto em 20/10/2026. Este pacote fica fora do projeto do Claude. O Codex preparou os dados, o treino separado e os contratos; **o Claude implementará a integração PHP/Firebird e a interface**.

## O que entregar ao Claude

Abra [01-PROMPT-PARA-CLAUDE.md](01-PROMPT-PARA-CLAUDE.md) e cole o conteúdo no Claude que mantém o projeto. Se ele não tiver acesso a esta pasta, anexe `ml-marilia-para-claude.zip`, disponibilizado ao lado da pasta deste pacote. O ZIP contém o código de treino, dados agregados reais e uma execução completa. A pasta `.venv` e a fonte bruta de 141 MB ficam fora do ZIP.

O fluxo solicitado é: **previsão semanal por setor → explicação e comparação → sugestão de agente → confirmação do gestor → missão fictícia → retrospecto**.

## Treino no seu computador

Nesta máquina o ambiente e os dados já estão preparados. Dê dois cliques em **TREINAR.cmd** para instalar/conferir dependências, verificar o protocolo e executar novamente. A janela mostra as etapas e permanece aberta no final. Os resultados ficam numa nova pasta dentro de `runs/`.

Alternativa no PowerShell, a partir desta pasta:

```powershell
& ./.venv/Scripts/python.exe treino.py --modo completo --as-of 2026-10-20
```

Em outro computador: Python **3.12 ou superior**, internet para as dependências e `TREINAR.cmd`. O pacote já inclui as contagens agregadas; o download bruto não é necessário para treinar. O Python **3.14.6** foi verificado nesta máquina.

Este volume é pequeno: a primeira execução de treino/avaliação levou menos de um segundo após importar bibliotecas. Preparar o ambiente pode demorar alguns minutos. Deixar rodando por horas ou repetir a execução com os mesmos dados não melhora o modelo.

## Resultado já disponível

Leia [RESULTADO.md](runs/primeiro-treino-verificado/RESULTADO.md). Foram usados **8.419 registros elegíveis**, transformados em contagens diárias e **1.372 observações de semana/setor**, incluindo zeros.

- O ajuste de 2024 escolheu a **referência histórica** como padrão; o candidato de ML ficou com `alpha=0.1`.
- No teste separado de 2025, o Poisson teve ganho de **2,8%** no desvio e cobertura de **39,3%** dos registros futuros ao priorizar um setor, contra **37,2%** da referência. Isso não mede acidentes evitados.
- A escolha feita no ajuste foi preservada. A interface deverá identificar os métodos e permitir comparar o candidato experimental, sem afirmar uma vantagem comprovada/generalizada.
- A igualdade das previsões Python/PHP foi conferida em **20 vetores**; quatro testes de protocolo passaram.

## Arquivos que o Claude vai consumir

- `dados/setores.geojson`: os quatro setores experimentais; não são bairros oficiais.
- `runs/primeiro-treino-verificado/previsao.json`: previsão e evidências para 20 a 27/10/2026, fim exclusivo.
- `runs/primeiro-treino-verificado/avaliacao.json`: métricas, parâmetros e limitações.
- `runs/primeiro-treino-verificado/retrospecto.json`: previsões semanais e resultados do teste histórico.
- `runs/primeiro-treino-verificado/modelo.json`: coeficientes e pré-processamento, sem pickle.
- `runs/primeiro-treino-verificado/vetores_paridade.json`: exemplos numéricos para validar integração PHP.

Contrato e aceite: [02-ESPECIFICACAO-PARA-CLAUDE.md](02-ESPECIFICACAO-PARA-CLAUDE.md), [03-CONTRATO-DOS-ARQUIVOS.md](03-CONTRATO-DOS-ARQUIVOS.md), [04-METODO-E-LIMITES.md](04-METODO-E-LIMITES.md).

A data de referência usada pela previsão é **31/07/2026**, por uma hipótese conservadora de atraso de publicação. O snapshot contém registros até setembro, mas a simulação retém dois meses completos. A base oficial é mensal; os recursos e missões da demonstração continuam fictícios.
