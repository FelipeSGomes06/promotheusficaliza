# Especificação de integração e aceite

## Objetivo

Transformar uma projeção de contagem de sinistros registrados em uma decisão operacional demonstrável. A versão inicial usa quatro quadrantes experimentais fixos e horizonte de sete dias. Escopo: módulo autenticado PHP/Slim/Twig, Firebird, mapa Leaflet e componentes visuais já existentes; treino Python separado fornecido neste pacote.

## Fluxos do produto

### Planejar reforço

- Acrescentar entrada de navegação com o nome **Planejar reforço**. Pode aproveitar o agrupamento de Antecipação; preservar a função atual de revisão de equipamentos.
- No topo, exibir período previsto, última referência de dados, método escolhido no ajuste e aviso da origem real/sintética de cada camada.
- Desenhar os quatro polígonos de `setores.geojson`, contorno, legenda e prioridade com texto e cor. São setores experimentais; o retângulo estudado não representa o limite municipal e não cobre necessariamente todo o município.
- Cada setor mostra contagem estimada em sete dias, posição na ordenação, histórico de 13/52 semanas, referência e uma explicação baseada nessas evidências. Arredondar para uma casa decimal na tela; preservar valor numérico no armazenamento.
- O método de referência é o padrão desta execução. Permitir comparar com o **Poisson experimental**, identificando o método e recalculando ordenação/rótulos conforme seus valores, sem sobrescrever a execução importada. A evidência explica associações, sem inventar causalidade ou atribuições SHAP que não foram calculadas.
- Chamar o destaque de prioridade relativa de planejamento. Rank 1 não significa emergência; não usar porcentagem de chance de acidente nem alertas vermelhos que simulem incidente em curso.
- Informar ausência de execução ou dados muito antigos com estado útil, sem números fabricados. A lista textual deve permitir usar os mesmos recursos do mapa.

### Sugerir e confirmar equipe

- Selecionar apenas agentes de trânsito (`TRANSITO`) ativos, não arquivados, em atividade e sem outra missão vigente. O sistema atual também tem guarda e polícia: não os escalar como trânsito por coincidência de proximidade.
- Usar posição recente conforme as regras de Freshness existentes. Posição ausente/antiga ou somente base deve ter identificação explícita; não a apresentar como localização atual confirmada. Permitir uma alternativa manual pelo gestor quando justificada.
- Ordenar candidatos por disponibilidade e distância aproximada em linha reta à área de atenção. Isso é uma regra operacional simples, separada do modelo de previsão. Mostrar se é distância à área/centro de referência; não chamar de tempo de chegada ou melhor rota.
- O destino inicial é um setor de atenção. O centro geométrico é uma referência de visualização, não uma indicação automática de posto ou local seguro de parada. O gestor define um ponto de apoio/descrição se necessário.
- Criar uma sugestão com o setor, período, previsão, método, agente e motivo. Exigir confirmação explícita na tela e registrar usuário autenticado, horário e observação.
- Estados mínimos: `CONFIRMADA`, `CONCLUIDA`, `CANCELADA`; a sugestão anterior à confirmação pode ser calculada sem persistência. Guardar histórico das transições e motivo de cancelamento/conclusão.
- Reservar o agente em transação; duas confirmações simultâneas para o mesmo agente devem produzir uma missão confirmada e um conflito útil. Validar no servidor a elegibilidade imediatamente antes da confirmação.
- Armazenar a missão separada da situação de telemetria do agente. O simulador não deve liberar uma reserva ao atualizar posição/status. Concluir/cancelar libera a reserva para novas sugestões.
- Exibir sempre que são agentes e missões fictícios. A operação não envia comunicação externa.

### Retrospecto

- Selecionar uma semana de teste disponível em `retrospecto.json` e o método para comparar.
- Exibir os quatro setores com as previsões do modelo congelado no início do teste e os dados de entrada disponíveis no corte simulado. Não usar o modelo operacional retreinado de 2026 para prever 2025.
- Um controle revela o que foi registrado depois e mostra comparação com a referência. Nomear corretamente o denominador: registros elegíveis na área de estudo, com orçamento de um setor por semana.
- Mostrar resultado global do período de teste, janela e limitações. Evitar escolher somente uma semana favorável como prova de eficácia. O usuário pode navegar todas as semanas do teste.

## Persistência e serviços

O Claude define nomes e módulos conforme as convenções atuais. São necessários conceitos separados de execução importada, previsão por setor, evidências/métricas e missão. Reusar eventos/autoria quando adequados; a previsão não é evento de falha nem altera disponibilidade do equipamento.

A importação deve ocorrer por comando CLI concreto, por exemplo `php bin/fiscaliza urban:import --dir=...`; o nome final pode acompanhar o padrão do projeto. Validar todos os arquivos antes da transação, tamanho total, versão, IDs, intervalos, métodos, hashes e números finitos não negativos. Tratar JSON como dados. Uma segunda importação dos mesmos bytes deve ser idempotente. Arquivos inválidos não podem deixar execução parcial.

Importar somente JSON e GeoJSON das execuções completas; o site não precisa receber ZIP, CSV bruto, venv, biblioteca Python ou objeto pickle. Manter os arquivos de origem e proveniência fora da raiz HTTP. A aplicação mostra os valores importados sem disparar shell/treino.

O mapa de equipamentos e agentes permanece baseado em suas observações fictícias; a camada histórica/preditiva recebe identificação Infosiga-SP. Revisar o aviso global de que tudo é fictício para não contradizer a nova camada de dados reais agregados.

## Critérios de aceite verificáveis

| ID | Critério |
|---|---|
| ML-01 | Importar a execução fornecida, com quatro setores e previsão atual, preservando precisão numérica. |
| ML-02 | Reimportar os mesmos arquivos sem duplicar registros; arquivo truncado, versão desconhecida, setor inválido, NaN, negativo ou geometria fora do contrato recusados sem escrita parcial. |
| ML-03 | Mostrar previsão, método, período e referência; distinguir contagem esperada, prioridade relativa e incidente atual. |
| ML-04 | Métodos de referência e Poisson identificados e comparáveis; trocar método mantém valores corretos e muda a ordenação de acordo com o método selecionado. |
| ML-05 | Retrospecto usa as previsões congeladas de teste, não o modelo final de 2026; todos os setores e semanas ficam disponíveis. |
| ML-06 | Sugestão exclui agentes de outros papéis, arquivados, fora de atividade ou já reservados; posição desatualizada tratada explicitamente. |
| ML-07 | POST exige autenticação, autorização e CSRF; autoria e agente elegível conferidos no servidor. |
| ML-08 | Teste com duas confirmações concorrentes do mesmo agente resulta em uma única reserva vigente; cancelar/concluir libera a reserva com histórico. |
| ML-09 | Tick do simulador não remove reserva nem altera método/evidência da missão confirmada. |
| ML-10 | Dados e missões são utilizáveis em desktop e celular por lista/mapa; estados vazios/erro têm mensagem útil e a tela não depende de cor apenas. |
| ML-11 | Testes, estilo, análise estática e builds pertinentes passam; há captura visual dos fluxos e exemplo de importação reproduzível. |
| ML-12 | README, matriz de requisitos, autoria e atribuição dos dados descrevem o estado final; sem promessa de probabilidade calibrada, prevenção causal ou precisão não medida. |

Testes de domínio, integração Firebird, HTTP e concorrência devem proteger essas propriedades. Aproveitar a infraestrutura existente, mantendo bases de teste separadas do banco da demonstração.
