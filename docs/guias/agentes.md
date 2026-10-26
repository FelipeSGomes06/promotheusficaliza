# Agentes

**Objetivo:** manter as equipes de campo fictícias (trânsito, guarda, polícia): cadastro, base de trabalho, situação,
última posição, alertas de posição e missões.
**Como acessar:** menu **Agentes**; `/agentes` (lista), `/agentes/{id}` (ficha), `/agentes/novo`,
`/agentes/{id}/editar`. Qualquer conta ativa.

> **Agente ≠ conta.** Um agente é uma equipe **mostrada no mapa** (ex.: "Hermes 06"); não entra no sistema. Quem entra é
> uma **conta de acesso**, criada em [Contas](acesso-e-contas.md). Uma conta pode ser **responsável** pelo
> acompanhamento de um alerta de agente; um agente nunca é responsável por nada.

## Lista (`/agentes`)

Título "função · indicativo", código gerado na criação por função (`AGT-NNN` trânsito, `GCM-NNN` guarda, `POL-NNN` polícia; texto, ex.: `AGT-006`), órgão, bairro da base, situação e
atualidade da posição (só para quem está em turno). Filtros: `q`, função (`AG_TRANSITO`, …), situação, órgão,
ordenação (`codigo`, `nome`, `funcao`, `situacao`, `bairro`), `arquivados=1`. Paginação de 20.

## Ficha (`/agentes/{id}`)

**Próxima ação** (a mesma da ficha resumida da Visão geral, com o tempo sem posição e uma orientação), **Acompanhamento
do alerta** (ciência, responsável, próxima conferência — ver [alertas](alertas-e-acompanhamento.md)), alertas pendentes
(todos) e encerrados (20, com o total), **missão vigente** com link "Concluir ou cancelar em Planejar reforço", base,
última posição, histórico e **Mais ações** (mudar situação, simular recebimento de posição, arquivar).

| Dado | Significado |
|---|---|
| Situação | `EM_ATIVIDADE` · `EM_PAUSA` (as duas = **em turno**) · `INDISPONIVEL` · `FORA_DE_SERVICO` |
| Base de trabalho | ponto **fixo** cadastrado onde a equipe se apresenta (obrigatório) |
| Última posição recebida | o que o rádio simulado informou, com data e hora; só aparece no mapa enquanto em turno |
| "Sem informação recente" | em turno e sem posição há mais de 30 min → alerta `SEM_POSICAO`; **não é falha do agente** |

## Base de trabalho: mapa, CEP e rua

O cadastro tem duas etapas: **quem é** (indicativo, função, órgão, situação inicial — padrão "Fora de serviço") e
**base de trabalho** ([ADR-0021](../adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)).

1. **Clique no mapa** para marcar a base (teclado: setas movem o mapa, Enter marca o centro). Latitude e longitude
   aparecem abaixo e nunca são digitadas.
2. Não achou no mapa? Digite o **CEP** (`17501-000`): a rua aparece sozinha ("Rua: …"); se ainda não houver ponto, a
   base vai para o endereço (ponto aproximado; um clique ajusta).
3. Ponto marcado fora do endereço informado (CEP, rua e número) → vai para o endereço; dentro → fica. Com endereço
   preenchido, o clique só ajusta **dentro** dele; para outro lugar, mude ou apague o endereço. "Levar a base até este
   endereço" faz isso a qualquer momento.
4. CEP sem rua (inexistente, geral da cidade, de outra cidade ou consulta fora do ar) → aparece o campo **Rua** para
   digitar.
5. Sem JavaScript: informe CEP e rua; a base vai para o endereço ao salvar.

**Resultado esperado:** "Agente cadastrado."; código gerado; bairro = o de centro mais próximo do ponto (bairro enviado
pelo navegador é ignorado); histórico `CADASTRO`. A base **não** indica onde o agente está agora.

| Resposta da consulta de CEP (`GET /api/cep/{cep}`) | Tela |
|---|---|
| `ENCONTRADO` | preenche a rua |
| `SEM_RUA`, `OUTRA_CIDADE`, `NAO_ENCONTRADO` (404) | mensagem e campo "Rua" |
| `INVALIDO` (422) | recusado **antes** de chamar o provedor |
| `INDISPONIVEL` (503) | mensagem; o envio do formulário não depende desta rota |

O ponto pela rua (`GET /api/localizar`) segue o mesmo padrão ([contratos](../http.md#ponto-pelo-endereço-get-apilocalizar)).
Só o CEP ou o nome da rua saem do servidor (ViaCEP e Nominatim, HTTPS, no máximo 1 consulta/s ao Nominatim); sem conta,
nenhuma chamada externa acontece.

| Erro ao salvar | Campo |
|---|---|
| sem ponto e sem endereço | `base_lat` |
| rua não achada ou CEP sem rua | `base_street` |
| provedor fora do ar | `base_lat`, com a digitação mantida |
| fora da área de Marília | `base_lat`/`base_lng` |

**Agente antigo** (cadastrado antes de 23/10/2026): abre com a rua dele; mantém o bairro escolhido até o ponto mudar; sem
bairro, ganha um no próximo salvamento.

## Editar, mudar situação e arquivar

- **Editar:** como em equipamentos, com versão do cadastro e conflito "Confira a ficha atual antes de salvar de novo".
  Situação e posição **não** invalidam o formulário aberto.
- **Mudar situação:** qualquer situação, motivo obrigatório (1–300). Sair de turno encerra o alerta `SEM_POSICAO` com o
  aviso de que ele "deixou de se aplicar com a saída de turno; isso não indica que a comunicação voltou".
- **Arquivar:** motivo 1–300; o alerta pendente se resolve como `ARQUIVADO`. Missões vigentes **não** são encerradas: o
  agente continua reservado em Planejar reforço até alguém concluir ou cancelar a missão.

## Simular recebimento de posição

**Mais ações → Simular recebimento de posição:** uma posição fictícia até ~200 m da base pelo mesmo caminho da
telemetria, identificada no histórico ("recebimento simulado pela demonstração"); nenhuma mensagem é enviada. Em turno,
encerra o alerta por "posição recente recebida"; como é pontual, se o rádio simulado seguir sem transmitir (Hermes 06),
um alerta novo abre depois de 30 min. Fora de turno, a posição não aparece no mapa.

## Regras da funcionalidade

- Função ∈ trânsito, guarda, polícia; só **agentes de trânsito em atividade e sem missão** podem ser sugeridos em
  [Planejar reforço](planejar-reforco.md).
- Não há tabela de transições de situação para agentes (qualquer mudança com motivo).
- Posição mais antiga não substitui a mais nova; evento repetido é ignorado e contado
  ([ingestão](../referencia/codigo/application/ingestao.md#eventingestorposition)).
- Base e posição ficam em colunas separadas (`BASE_*` × `POS_*`): editar a base não move a posição e vice-versa.

## Como funciona no código

Cadastro: `POST /agentes` → `AgentController::store` → [`AgentService::create`](../referencia/codigo/application/agentes.md#agentservicecreate)
→ [`AddressPoint::resolve`](../referencia/codigo/application/endereco.md#addresspointresolve) (ponto × endereço) com
[`ViaCepClient`/`NominatimGeocoder`](../referencia/codigo/infrastructure/provedores-externos.md) →
[`NearestRegion`](../referencia/codigo/domain/setores-e-regioes.md#nearestregion) → `INSERT` em `AGENT`, evento em
`RESOURCE_EVENT`, `AlertService::reconcile` → 303. CEP: `GET /api/cep/{cep}` → `AddressController::cep` →
`CepLookup` ([endereço](../referencia/codigo/application/endereco.md)); no navegador, [`lib/cep.js`](../referencia/codigo/frontend/entrada-e-bibliotecas.md#libcepjs)
e [`lib/picker.js`](../referencia/codigo/frontend/entrada-e-bibliotecas.md#libpickerjs). Situação:
[`AgentService::changeStatus`](../referencia/codigo/application/agentes.md#agentservicechangestatus). Posição simulada:
`AgentController::simulatePosition` → [`EventIngestor::position`](../referencia/codigo/application/ingestao.md#eventingestorposition).
Ficha: `AgentController::page` → [`AlertFollowUp::nextAction`](../referencia/codigo/domain/indicadores-e-alertas.md#alertfollowupnextaction).
Dados: [`AGENT`](../referencia/dicionario-de-dados.md#agent).

## Verificação e limites

Testes: `AgentBaseTest` (7), `AgentsHttpTest` (9), `InventoryTest::testMobileResourceKeepsBaseSeparateFromObservedPosition`,
`testAgentStatusChangeDoesNotInvalidateAnOpenAgentForm`, `OperationsTest::testOlderPositionDoesNotReplaceNewerOne`,
`AlertFollowUpTest` ([catálogo](../referencia/codigo/testes/casos.md)). Evidências:
[base pelo mapa e CEP, 23/10](../evidencias/2026-10-23-agente-base-pelo-mapa-e-cep.md),
[acompanhamento, 24/10](../evidencias/2026-10-24-acompanhamento-de-alertas.md). Limites: agentes, rádio e posições são
fictícios; ViaCEP e Nominatim são serviços externos reais (só CEP/rua saem); os testes usam falsos e não acessam a rede.

## Relacionados

[Alertas e acompanhamento](alertas-e-acompanhamento.md) · [Planejar reforço](planejar-reforco.md) ·
[Acesso e contas](acesso-e-contas.md) · [HTTP](../http.md#agente--post-agentes-e-post-agentesid)
