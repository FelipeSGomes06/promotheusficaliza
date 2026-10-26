# Planejamento preventivo de Marília — comece aqui

Pacote de dados e treino do Planejar reforço, preparado em 20/10/2026. Contém o preparo das contagens agregadas do
Infosiga-SP, o treino separado da aplicação, os contratos dos arquivos e duas execuções verificadas. A aplicação PHP
importa este pacote com `php bin/fiscaliza urban:import`.

O fluxo atendido é: **previsão semanal por setor → explicação e comparação → sugestão de agente → confirmação do gestor
→ missão fictícia → retrospecto**.

## Treino no seu computador

Dê dois cliques em **TREINAR.cmd** para instalar/conferir dependências, verificar o protocolo e executar novamente. A
janela mostra as etapas e permanece aberta no final. Os resultados ficam numa nova pasta dentro de `runs/`.

Alternativa no PowerShell, a partir desta pasta:

```powershell
& ./.venv/Scripts/python.exe treino.py --modo completo --as-of 2026-10-20
```

Requisitos: Python **3.12 ou superior** e internet para as dependências. O pacote já inclui as contagens agregadas; o
download bruto não é necessário para treinar. O Python **3.14.6** foi verificado.

O volume é pequeno: a execução de treino/avaliação leva menos de um segundo depois de importar as bibliotecas. Repetir
com os mesmos dados não melhora o modelo.

## Resultado já disponível

Leia [RESULTADO.md](runs/primeiro-treino-verificado/RESULTADO.md). Foram usados **8.419 registros elegíveis**,
transformados em contagens diárias e **1.372 observações de semana/setor**, incluindo zeros.

- O ajuste de 2024 escolheu a **referência histórica** como padrão; o candidato Poisson ficou com `alpha=0.1`.
- No teste separado de 2025, o Poisson teve ganho de **2,8%** no desvio e cobertura de **39,3%** dos registros futuros
  ao priorizar um setor, contra **37,2%** da referência. Isso não mede acidentes evitados.
- A escolha feita no ajuste foi preservada; a interface identifica os métodos e mostra o candidato como experimental.
- A igualdade das previsões Python/PHP foi conferida em **20 vetores**; quatro testes de protocolo passaram.

## Arquivos consumidos pela aplicação

- `dados/setores.geojson`: os quatro setores experimentais; não são bairros oficiais.
- `runs/primeiro-treino-verificado/previsao.json`: previsão e evidências para 20 a 27/10/2026, fim exclusivo.
- `runs/primeiro-treino-verificado/avaliacao.json`: métricas, parâmetros e limitações.
- `runs/primeiro-treino-verificado/retrospecto.json`: previsões semanais e resultados do teste histórico.
- `runs/primeiro-treino-verificado/modelo.json`: coeficientes e pré-processamento, sem pickle.
- `runs/primeiro-treino-verificado/vetores_paridade.json`: exemplos numéricos para validar a integração PHP.

Contratos: [03-CONTRATO-DOS-ARQUIVOS.md](03-CONTRATO-DOS-ARQUIVOS.md), [04-METODO-E-LIMITES.md](04-METODO-E-LIMITES.md),
[05-VERIFICACAO-E-CONTEXTO.md](05-VERIFICACAO-E-CONTEXTO.md).

A data de referência usada pela previsão é **31/07/2026**, por uma hipótese conservadora de atraso de publicação. O
snapshot contém registros até setembro, mas a simulação retém dois meses completos. A base oficial é mensal; os
recursos e missões da demonstração são fictícios.
