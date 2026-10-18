# ADR-0004 — Atualização da central por consulta periódica a um instantâneo

Data: 18/10/2026 · Situação: aceita

**Contexto.** A central precisa refletir eventos persistidos; SSE/WebSocket dependem de proxy e processos longos.
**Decisão.** O navegador consulta `/api/central` a cada 5 s. O servidor monta o instantâneo inteiro numa transação
SNAPSHOT somente leitura, com horário de geração e do último evento. A consulta pausa com a aba oculta, recua em falha
(até 60 s) e pode ser pausada pelo usuário; o mapa nunca se move sozinho.
**Consequências.** Latência declarada de até um intervalo + rede; payload de ~66 KB (comprimível) no volume atual.
Para escala maior: consulta por área visível e agrupamento.
**Alternativas.** SSE (mais imediato, exige configuração do proxy); diferenças incrementais por versão (mais complexo).
