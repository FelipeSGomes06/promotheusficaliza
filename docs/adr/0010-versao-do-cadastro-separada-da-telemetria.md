# ADR-0010 — Versão do cadastro separada de posição e situação

Data: 18/10/2026 · Situação: aceita · Origem: achado OP-03 da auditoria de 18/10/2026

**Contexto.** `VERSION_NO` protege formulários contra duas edições humanas simultâneas. Mas toda atualização de estado
(posição recebida, telemetria, mudança de situação) também a incrementava: com o simulador ligado, editar marca ou
modelo de um veículo terminava em "outra pessoa alterou", e a tela de conflito mantinha a versão antiga, de modo que
salvar de novo falhava outra vez.

**Decisão.**

- `VERSION_NO` passa a ser a **versão do cadastro**: só avança quando muda uma coluna que o formulário edita (tipo,
  marca, modelo, série, patrimônio, órgão, endereço, coordenadas, natureza do local, periodicidade, características,
  observações, data de instalação) ou com o arquivamento. A regra fica num só lugar
  (`EquipmentRepository::updateState`, lista `CADASTRAL`); em agentes, só o arquivamento avança a versão fora do
  formulário.
- Posição, sinal e situação não avançam a versão. Exceção intencional: concluir a instalação troca o local planejado
  pelo instalado (coluna cadastral) e por isso invalida um formulário aberto antes.
- Conflito real: a tela mantém tudo o que a pessoa digitou, mostra campo a campo "salvo agora" × "sua versão"
  (`EquipmentService::pendingChanges`), indica a última mudança registrada e passa a carregar a versão atual. Salvar
  de novo é então a decisão consciente de manter a própria versão; também é possível descartar e recarregar.

**Consequências.** Duas edições humanas continuam protegidas (teste `testStaleFormVersionIsRejected…`); posição e
telemetria durante a edição não geram conflito (`testPositionAndTelemetryDoNotInvalidate…`). O controle de versão não
foi removido nem afrouxado para o cadastro.
