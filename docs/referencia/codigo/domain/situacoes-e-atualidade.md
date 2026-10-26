# Situações, funções, atualidade e urgência

<!-- fontes: src/Domain/EquipmentStatus.php src/Domain/AgentStatus.php src/Domain/AgentRole.php src/Domain/Freshness.php src/Domain/MaintenanceUrgency.php -->

Enums puros de `src/Domain/` (sem banco, sem HTTP) que dão nome, rótulo e regra a quatro perguntas que todas as telas
fazem: **em que situação está o recurso**, **que função tem o agente**, **a informação recebida ainda é recente** e
**quão urgente é uma ordem de manutenção**. Templates, JavaScript e serviços leem estes enums; nenhuma tela inventa
rótulo ou transição próprios. Visão de conjunto em [domínio](../../../dominio.md#situações-e-transições).

Os valores (`NAO_INSTALADO`, `EM_ATIVIDADE`…) são os mesmos gravados no banco (colunas `STATUS`, `ROLE`) e aceitos
pelos `CHECK` das migrações ([dicionário de dados](../../dicionario-de-dados.md)).

## `EquipmentStatus`

Situação operacional do equipamento. É **independente** da necessidade de manutenção (ordens) e da atualidade do sinal
([`Freshness`](#freshness)): um equipamento operacional pode ter preventiva vencida ou estar sem sinal recente.

| Caso | Valor | Rótulo (`label`) | Símbolo (`glyph`) | Em serviço | Indisponível | Administrativo | Espera sinal |
|---|---|---|---|:-:|:-:|:-:|:-:|
| `NaoInstalado` | `NAO_INSTALADO` | Não instalado | ◌ | | | ✓ | |
| `Operacional` | `OPERACIONAL` | Operacional | ✓ | ✓ | | | ✓ |
| `Falha` | `FALHA` | Mau funcionamento | ! | ✓ | ✓ | | ✓ |
| `Manutencao` | `MANUTENCAO` | Em manutenção | ⚙ | ✓ | ✓ | ✓ | |
| `Desativado` | `DESATIVADO` | Desativado | – | | | ✓ | |
| `Furtado` | `FURTADO` | Furtado | ✕ | ✓ | ✓ | ✓ | |

| Método | Contrato |
|---|---|
| `EquipmentStatus::label` | `(): string` — rótulo em português usado em selos, listas e histórico. |
| `EquipmentStatus::isInService` | `(): bool` — deveria estar prestando serviço; entra no **denominador** da disponibilidade ([`Indicators`](indicadores-e-alertas.md#indicators)). |
| `EquipmentStatus::isUnavailable` | `(): bool` — em serviço, mas sem funcionar (falha, manutenção, furto). Conta em "Indisponíveis" e em "precisam de atenção". |
| `EquipmentStatus::isAdministrative` | `(): bool` — situação definida por pessoa; a telemetria simulada **não a sobrescreve** ([`EventIngestor`](../application/ingestao.md#eventingestorequipmentstatus)). |
| `EquipmentStatus::expectsSignal` | `(): bool` — recebe batimento de sinal, logo tem atualidade de sinal (operacional e falha). Nos demais, a atualidade é "não se aplica". |
| `EquipmentStatus::allowedTransitions` | `(): list<self>` — transições **manuais** permitidas a partir do estado (tabela abaixo). Única fonte da tabela: serviço, ficha e ajuda "?" a leem. |
| `EquipmentStatus::canTransitionTo` | `(self $target): bool` — `in_array($target, allowedTransitions())`. |
| `EquipmentStatus::maintenanceStartBlocker` | `(): ?string` — motivo, em texto pronto para a tela, pelo qual uma ordem agendada **não pode ser iniciada** nesta situação; `null` quando pode. Ver abaixo. |
| `EquipmentStatus::glyph` | `(): string` — símbolo textual que acompanha a cor (a cor nunca é o único indicador). |

**Tabela de transições** (`allowedTransitions`):

| De \ Para | Operacional | Falha | Manutenção | Desativado | Furtado | Não instalado |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| Não instalado | ✓ (instalação ou entrada em operação) | | | ✓ | | |
| Operacional | | ✓ | ✓ | ✓ | ✓ | |
| Falha | ✓ | | ✓ | ✓ | ✓ | |
| Manutenção | ✓ | ✓ | | ✓ | | |
| Desativado | ✓ | | | | | ✓ (reposição) |
| Furtado | | | | ✓ (baixa) | | ✓ (reposição) |

### `EquipmentStatus::maintenanceStartBlocker`

Iniciar uma intervenção leva o equipamento a "Em manutenção"; por isso vale a mesma tabela de transições. O método
devolve o texto que a ficha, a Manutenção e o serviço mostram quando a transição para manutenção não existe:

| Situação | Retorno |
|---|---|
| `NAO_INSTALADO` | "A intervenção só pode começar depois da instalação (ou da entrada em operação…). Até lá, a ordem fica agendada." |
| `DESATIVADO` | "…só pode começar se ele voltar a uso. Se não for voltar, cancele a ordem." |
| `FURTADO` | "…só pode começar depois da reposição e da nova instalação. Se não houver reposição, cancele a ordem." |
| `OPERACIONAL`, `FALHA`, `MANUTENCAO` | `null` |

Quem chama: [`MaintenanceService::start`](../application/manutencao.md#maintenanceservicestart) (recusa com
`RuleViolation`), `OperationsController::maintenance` (coluna `start_blocker` da fila) e `templates/equipment/show.twig`
(próxima ação). Assim a interface nunca oferece um início que o servidor recusaria. Teste:
`DomainRulesTest` (impedimento × tabela de transições), `MaintenanceInvariantsTest` (início por situação).

## `AgentStatus`

Situação do **agente em campo** (recurso operacional fictício). Não é conta de acesso: contas ficam em `APP_USER`
([autenticação](../application/autenticacao.md)).

| Caso | Valor | Rótulo | Símbolo | Ativo (`isActive`) | Em turno (`isOnShift`) |
|---|---|---|---|:-:|:-:|
| `EmAtividade` | `EM_ATIVIDADE` | Em atividade | ● | ✓ | ✓ |
| `EmPausa` | `EM_PAUSA` | Em pausa | ❚❚ | | ✓ |
| `Indisponivel` | `INDISPONIVEL` | Indisponível | ! | | |
| `ForaDeServico` | `FORA_DE_SERVICO` | Fora de serviço | – | | |

| Método | Contrato |
|---|---|
| `AgentStatus::label` | `(): string` — rótulo da tela. |
| `AgentStatus::isActive` | `(): bool` — só "Em atividade". Usado em "Agentes ativos" e na elegibilidade do [Planejar reforço](../application/planejamento.md#planningservicecandidates). |
| `AgentStatus::isOnShift` | `(): bool` — atividade ou pausa: **posição recebida é esperada**. Decide a atualidade da posição e o alerta `SEM_POSICAO` ([`AlertRules`](indicadores-e-alertas.md#alertrulesconditions)). Entrar em pausa não encerra o alerta. |
| `AgentStatus::glyph` | `(): string` — símbolo textual. |

Não há tabela de transições para agentes: qualquer situação pode ir para qualquer outra
([`AgentService::changeStatus`](../application/agentes.md#agentservicechangestatus)), sempre com motivo quando a origem é
`MANUAL`.

## `AgentRole`

| Caso | Valor | Rótulo | Categoria de filtro (`category`) | Prefixo do código |
|---|---|---|---|---|
| `Transito` | `TRANSITO` | Agente de trânsito | `AG_TRANSITO` | `AGT-` |
| `Guarda` | `GUARDA` | Guarda municipal | `AG_GUARDA` | `GCM-` |
| `Policia` | `POLICIA` | Policial | `AG_POLICIA` | `POL-` |

| Método | Contrato |
|---|---|
| `AgentRole::label` | `(): string` — rótulo. |
| `AgentRole::category` | `(): string` — `'AG_' . value`: código usado no filtro "Tipo" da central, ao lado dos tipos de equipamento (`categoria[]=AG_TRANSITO`). |
| `AgentRole::fromCategory` | `static (string $category): ?self` — inverso de `category()`; `null` se o texto não começa com `AG_` ou não é função conhecida. Usado por [`Filters::agentRoles`](../application/validacao-e-filtros.md#filters). |

O prefixo do código (`AGT-001`) é definido em `AgentService::PREFIX`, não no enum. Só agentes `TRANSITO` entram em
multas (origem Agente ou operador de radar) e no reforço preventivo.

## `Freshness`

Atualidade da **última observação** — sinal de equipamento ou posição de agente/recurso móvel —, derivada do tempo
decorrido. **Silêncio do dispositivo não é falha confirmada**: vira "sem informação recente" e um alerta de comunicação.

| Caso | Valor | Rótulo |
|---|---|---|
| `Atual` | `ATUAL` | Atual |
| `Atrasada` | `ATRASADA` | Atrasada |
| `SemInformacao` | `SEM_INFORMACAO` | Sem informação recente |
| `NaoSeAplica` | `NAO_SE_APLICA` | Não se aplica |

| Constante | Valor | Uso |
|---|---|---|
| `SIGNAL_CURRENT` | 600 s (10 min) | sinal de equipamento atual até aqui |
| `SIGNAL_STALE` | 3600 s (60 min) | sinal atrasado até aqui; depois, sem informação |
| `POSITION_CURRENT` | 300 s (5 min) | posição atual até aqui |
| `POSITION_STALE` | 1800 s (30 min) | posição atrasada até aqui; depois, sem informação |

### `Freshness::of`

`static of(?DateTimeImmutable $observedAt, DateTimeImmutable $now, bool $applicable = true, int $currentSeconds = self::SIGNAL_CURRENT, int $staleSeconds = self::SIGNAL_STALE): self`

| Parâmetro | Significado |
|---|---|
| `$observedAt` | Instante da última observação (UTC, lido de `LAST_SEEN_AT` ou `POS_OBSERVED_AT`); `null` = nunca recebida. |
| `$now` | Relógio da aplicação ([`Clock`](tempo.md#clock)). |
| `$applicable` | `false` devolve `NaoSeAplica` sem olhar o tempo (equipamento que não espera sinal; agente fora de turno). |
| `$currentSeconds`, `$staleSeconds` | Limites; passe `POSITION_CURRENT`/`POSITION_STALE` para posição. |

Algoritmo: não aplicável → `NaoSeAplica`; sem observação → `SemInformacao`; idade ≤ limite atual → `Atual`; ≤ limite
de atraso → `Atrasada`; senão `SemInformacao`. A comparação é inclusiva (exatamente 10 min ainda é atual).
Testes: `DomainRulesTest`.

| Método | Contrato |
|---|---|
| `Freshness::of` | ver acima. |
| `Freshness::label` | `(): string` — rótulo. |

Quem chama: `CentralQuery` (cada recurso), `AlertRules` (abertura de `SEM_COMUNICACAO`/`SEM_POSICAO`),
`EquipmentController`/`AgentController` (listas e fichas), `PlanningService::candidate` (camada da posição do agente).

## `MaintenanceUrgency`

Urgência de uma ordem de manutenção **em aberto**, comparando **datas locais de Marília** como texto ISO
(`AAAA-MM-DD`), sem conversão de fuso: `DUE_DATE` já é um dia do calendário local
([convenção de tempo](tempo.md#time)).

| Caso | Valor | Rótulo | Urgente (`isUrgent`) | `rank` |
|---|---|---|:-:|:-:|
| `Imediata` | `IMEDIATA` | Imediata | ✓ | 0 |
| `Vencida` | `VENCIDA` | Vencida | ✓ | 1 |
| `Proxima` | `PROXIMA` | Próxima | | 2 |
| `Futura` | `FUTURA` | Programada | | 3 |

### `MaintenanceUrgency::classify`

`static classify(string $priority, ?string $dueDate, string $todayLocal, int $windowDays = self::DEFAULT_WINDOW_DAYS): self`

- `$priority === 'IMEDIATA'` → `Imediata`.
- Programada sem data → `Imediata` por segurança (o banco já impede: `CK_MAINT_DUE`).
- `$dueDate < $todayLocal` → `Vencida`; `$dueDate <= hoje + 7 dias` → `Proxima` (hoje inclusive); senão `Futura`.

`DEFAULT_WINDOW_DAYS = 7`. Exemplo: hoje `2026-10-26`, ordem programada para `2026-11-02` → `Proxima`; para
`2026-11-03` → `Futura`; para `2026-10-25` → `Vencida`.

| Método | Contrato |
|---|---|
| `MaintenanceUrgency::classify` | ver acima. |
| `MaintenanceUrgency::label` | `(): string`. |
| `MaintenanceUrgency::isUrgent` | `(): bool` — imediata ou vencida: alimenta "Urgentes" da Manutenção, o resumo "manutenções urgentes" da central e os alertas `MANUTENCAO_IMEDIATA`/`MANUTENCAO_VENCIDA`. |
| `MaintenanceUrgency::rank` | `(): int` — menor = mais urgente; escolhe a ordem mais urgente de um equipamento na central. |

Quem chama: `OperationsController::maintenance` (agrupamento da fila), `CentralQuery` (ordem principal do recurso,
listas `urgent`/`upcoming`), `AlertRules::conditions`. Testes: `DomainRulesTest`, `OperationsTest` (resumo de urgentes).

## Relacionados

- [Catálogo de tipos de equipamento](catalogo-de-equipamentos.md) · [Indicadores e alertas](indicadores-e-alertas.md)
- [Guia: Equipamentos](../../../guias/equipamentos.md) · [Guia: Agentes](../../../guias/agentes.md) · [Guia: Manutenção](../../../guias/manutencao.md)
- ADRs: [0005 estado atual × histórico](../../../adr/0005-estado-atual-versus-historico.md), [0009 uma intervenção por vez](../../../adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md), [0023 revisão de lógica](../../../adr/0023-revisao-de-logica-e-fluxos.md)
