# Visão geral

**Objetivo:** ver no mapa de Marília os equipamentos e agentes da demonstração, saber o que precisa de atenção e agir
sobre um recurso sem sair da tela.
**Como acessar:** item **Visão geral** do menu; rota `/` (sob `APP_BASE_PATH`, padrão `/promotheusfiscaliza/`).
Qualquer conta ativa. Sem conta → `302 /entrar?voltar=/`.
**Antes de começar:** a demonstração carregada (`demo:seed`); para ver mudanças acontecendo, o simulador ligado
([demonstração e simulação](demonstracao-e-simulacao.md)).

## O que aparece na tela

| Área | Significado | Origem ou regra |
|---|---|---|
| "Atualizado às…" com ponto verde/âmbar | hora do último instantâneo aplicado; âmbar = atrasado ou sem conexão | `central.renderSync` ([frontend](../referencia/codigo/frontend/central.md#sincronização)) |
| **Atualizar agora** · **Pausar/Retomar** | busca imediata · liga/desliga a atualização automática | `central.refreshNow`, `central.togglePause` |
| Resumo **operacionais** | equipamentos operacionais entre os **em serviço**, com quantos têm sinal recente | `Indicators::compute`: `operational ÷ in_service`; em serviço = operacional + falha + manutenção + furtado; não instalados e desativados **fora do denominador**; sem nenhum em serviço → "não se aplica" ([fórmulas](../referencia/codigo/domain/indicadores-e-alertas.md#indicatorscompute)) |
| Resumo **precisam de atenção** | recursos com algum motivo (toque filtra) | `needs_attention` do instantâneo |
| Resumo **manutenções urgentes** | ordens imediatas ou vencidas que **ainda não começaram** | leva à seção Urgentes da Manutenção (a iniciada fica em "Em andamento") |
| ⓘ dos resumos | fórmulas e a tabela por bairro | `regions` do instantâneo |
| Filtros (chips) | busca, Tipo, Situação, Precisa de atenção, Mais filtros (bairro, órgão) | os mesmos filtros de `/api/central`; mapa, lista e números vêm do **mesmo conjunto** |
| Lista | primeiro o que precisa de atenção (motivo em vermelho), depois os demais, radares primeiro; começa curta ("Ver os outros N recursos") | `CentralQuery::snapshot` |
| Mapa ou Lista | os mesmos resultados; a escolha fica em `?visao=lista` | `central.init` |
| Pontos do mapa | quadrado = local de instalação; borda tracejada = planejado; círculo = última posição de agente/veículo; pontilhado com ⌛ = posição antiga; "B" = base, sem posição recente; "!" = atenção | `central.pinHtml`; legenda na tela |
| Números redondos | grupos de recursos próximos (34 px até o zoom 17, depois 6 px); tocar aproxima ou, no mesmo ponto, abre uma lista | `central.groups`, `central.openGroup` |
| Ficha resumida | o que é, situação, atualidade, onde está, motivos, alertas, manutenção, código, **Ver ficha completa** | `central.select`, `central.panelVM` |

Situações e atualidade, com texto e símbolo além da cor: ver [guia de uso](../guia-de-uso.md). **"Sem informação
recente" não quer dizer quebrado:** sinal de equipamento atual ≤ 10 min, atrasado ≤ 60 min; posição atual ≤ 5 min,
atrasada ≤ 30 min ([`Freshness`](../referencia/codigo/domain/situacoes-e-atualidade.md#freshness)).

**Que posição o ponto representa?** Equipamento fixo: o local cadastrado (instalado ou planejado; furtado = de onde foi
levado). Recurso móvel e agente: a **última posição recebida** enquanto for recente; sem ela, a **base de trabalho**
com "B". Agente fora de turno não mostra posição. Recurso sem coordenadas aparece só na lista (o mapa diz quantos).

## Encontrar um recurso e abrir a ficha

1. Use a busca (endereço, tipo, código ou nome, sem precisar de acentos) ou os chips **Tipo**/**Situação**. Cada
   filtro ativo vira um chip com ×; **Limpar tudo** volta ao início.
2. Toque no ponto ou na linha. O ponto ganha destaque, o mapa centraliza e a ficha abre ao lado (no celular, sobe de
   baixo). Trocar de recurso com a ficha aberta só troca o conteúdo.
3. `Esc` ou × fecha e devolve o foco a quem abriu.

**Resultado esperado:** a URL guarda os filtros e a visão; `?selecionar=EQUIPAMENTO:<id>` (ou `AGENTE:<id>`) abre a ficha
direto. Nada é gravado.

## Registrar ciência ou simular falha pela ficha

1. Na ficha resumida, **Registrar ciência** de um alerta (ou **Simular falha**, recurso da demonstração).
2. O botão mostra "Registrando…"/"Simulando…"; o aviso verde só aparece depois da resposta do servidor.

**Resultado esperado:** ciência → alerta `RECONHECIDO`, com seu nome e horário, **mas continua pendente** até a causa
acabar ([alertas](alertas-e-acompanhamento.md)). Simular falha → evento fictício de telemetria pelo mesmo caminho do
simulador: equipamento em `FALHA` e alerta `FALHA`; em situação administrativa (manutenção, desativado, furtado, não
instalado) ou já em falha, fica só no histórico. Conferir na ficha completa (histórico) e na lista.

## Atualização periódica e perda de conexão

A cada `POLL_INTERVAL_SECONDS` (mín. 3 s), a página pede `/api/central` com os filtros atuais:

- **Um único dono do polling.** Um pedido novo cancela o anterior (`AbortController`); cada resposta leva um número de
  sequência e **só a mais recente é aplicada** — uma resposta antiga que chegue depois é descartada
  ([ADR-0004](../adr/0004-atualizacao-por-consulta-periodica.md)).
- Ao aplicar, nada se mexe sem motivo: mapa, filtros, recurso escolhido e foco ficam onde estavam; só o que mudou pisca
  (linha turquesa por um instante, número do resumo troca).
- Alertas novos viram **um** aviso por vez, com "Ver no mapa"; mudar o filtro não anuncia alertas antigos.
- Aba oculta: pausa; volta: retoma. Saída pela navegação (bfcache): para tudo e retoma ao voltar.

| Situação | O que a tela informa | Como proceder |
|---|---|---|
| Servidor não responde | aviso vermelho "Sem conexão com o servidor" com os dados da tela e o tempo até a nova tentativa (espera dobra até 60 s) | **Tentar agora**; quando volta, "Conexão restabelecida" |
| Sem sucesso há mais de 3 intervalos | estado "desatualizado" (âmbar) | conferir a rede; os dados mostrados são os da hora indicada |
| Ação enviada e conexão caiu antes da resposta | **resultado não confirmado — Atualize os dados antes de tentar novamente**; a central recarrega | conferir na ficha se a ação aparece **antes de repetir** ([ADR-0023, R05](../adr/0023-revisao-de-logica-e-fluxos.md)) |
| Sessão expirou (401/403 numa ação) | "Sua sessão expirou…" | entrar de novo |
| Mapa de fundo não carregou | faixa "Mapa de fundo indisponível…" | pontos, lista e resumos seguem atualizados |
| Leaflet não carregou / sem JavaScript | aviso; a lista renderizada pelo servidor tem os mesmos recursos | usar a lista |
| Filtro sem resultados | lista vazia e resumos em zero ou "não se aplica" | **Limpar tudo** |
| Parâmetro malformado na URL | filtro desconhecido é descartado | — (`WebSecurityTest::testMalformedQueryParametersDegradeSafely`) |

## Regras da funcionalidade

- Mapa, lista e indicadores são calculados **juntos, do mesmo conjunto filtrado** (`CentralQuery::snapshot` →
  `Indicators::compute`); agentes nunca são somados a equipamentos.
- Arquivados não aparecem. Não instalados e desativados aparecem, mas não contam na disponibilidade.
- Texto vindo do banco é escapado no JSON e inserido com `textContent`.
- A ficha resumida e a ficha completa do agente usam a **mesma** decisão de "próxima ação"
  ([`AlertFollowUp::nextAction`](../referencia/codigo/domain/indicadores-e-alertas.md#alertfollowupnextaction)).

## Como funciona no código

`GET /` → [`CentralController::page`](../referencia/codigo/http/controladores.md#centralcontroller) →
[`Filters`](../referencia/codigo/application/validacao-e-filtros.md#filters) normaliza a query →
[`CentralQuery::snapshot`](../referencia/codigo/application/central.md#centralquerysnapshot) lê equipamentos, agentes,
ordens, alertas e eventos (repositórios em [inventário](../referencia/codigo/infrastructure/repositorios-inventario.md) e
[operação](../referencia/codigo/infrastructure/repositorios-operacao.md); tabelas `EQUIPMENT`, `AGENT`,
`MAINTENANCE_ORDER`, `ALERT`, `RESOURCE_EVENT`, `APP_META`) → aplica [`Freshness`](../referencia/codigo/domain/situacoes-e-atualidade.md#freshness),
[`MaintenanceUrgency`](../referencia/codigo/domain/situacoes-e-atualidade.md#maintenanceurgency) e
[`Indicators`](../referencia/codigo/domain/indicadores-e-alertas.md#indicators) → `central.twig` com o instantâneo
embutido em `#snapshot-data` ([telas](../referencia/codigo/templates/macros-e-telas.md#centraltwig)) →
[componente `central`](../referencia/codigo/frontend/central.md) assume. Atualização: `GET /api/central` →
`CentralController::snapshot` (mesmo instantâneo, JSON `no-store`; contrato em [HTTP](../http.md#contrato-de-get-apicentral)).
Ciência: `POST /alertas/{id}/reconhecer` (JSON com `X-CSRF-Token`) → `OperationsController::acknowledge` →
[`AlertService::acknowledge`](../referencia/codigo/application/alertas.md#alertserviceacknowledge). Simular falha:
`POST /equipamentos/{id}/simular-falha` → `EquipmentController::simulateFailure` →
[`EventIngestor::equipmentStatus`](../referencia/codigo/application/ingestao.md#eventingestorequipmentstatus) +
reconciliação.

## Verificação e limites

Testes: `CentralAndAnalysisTest::testMapListAndIndicatorsComeFromTheSameFilteredSet`,
`testSearchAndAttentionFilterTheWholeSnapshot`, `testNoEquipmentInServiceShowsNotApplicable`,
`testResourceWithoutCoordinatesIsListedButNotPlaced`; `DomainRulesTest` (indicadores e atualidade);
`WebSecurityTest::testAcknowledgeAlertViaJsonUsesHeaderToken`, `testSimulateFailureViaJsonReportsTheServerOutcome`
([catálogo](../referencia/codigo/testes/casos.md)). No navegador: [evidência de 18/10/2026](../evidencias/2026-10-18-identidade-e-motion.md)
(foco, seleção e mapa preservados durante a atualização) e regressão A01 da auditoria de 25/10
(`scripts/verificacao-interface/auditoria.cjs`, grupo do mapa acompanhando o polling).
Limites: tudo é fictício; "ao vivo" é consulta periódica, não *push*; latência em rede real não medida; o volume de
demonstração (dezenas de recursos) é servido sem paginação.

## Relacionados

[Equipamentos](equipamentos.md) · [Agentes](agentes.md) · [Alertas](alertas-e-acompanhamento.md) ·
[Manutenção](manutencao.md) · [Interface comum](sobre-e-interface.md) · [Manual](../MANUAL-DO-PROJETO.md)
