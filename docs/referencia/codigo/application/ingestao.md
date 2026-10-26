# Ingestão de observações de campo

<!-- fontes: src/Application/EventIngestor.php -->

Porta de entrada das observações **simuladas** (sinal, situação reportada pela telemetria, posição). Separa **evento
ocorrido**, **evento recebido** e **estado atual** ([ADR-0005](../../../adr/0005-estado-atual-versus-historico.md)):
o histórico guarda tudo o que chegou; o estado atual só muda quando a observação é nova, válida e não contradiz uma
decisão humana.

Quem chama: [`Simulator::tick`](../demo/simulador.md#simulatortick), `EquipmentController::simulateFailure` ("Simular
falha") e `AgentController::simulatePosition` ("Simular recebimento de posição"). Nenhuma rota recebe telemetria
externa: não há integração com equipamentos reais.

| Resultado | Constante | Significado |
|---|---|---|
| `APLICADO` | `APPLIED` | mudou o estado atual |
| `HISTORICO` | `HISTORICAL` | gravado só no histórico (linha tracejada na ficha, com o motivo) |
| `DUPLICADO` | `DUPLICATE` | mesmo `EVENT_UID` já gravado: nada muda, contador `duplicates_ignored` + 1 |

## `EventIngestor::equipmentStatus`

`equipmentStatus(string $uid, int $equipmentId, EquipmentStatus $reported, DateTimeImmutable $occurredAt, string $detail, string $source = 'SIMULADOR', ?int $actor = null): string`

Telemetria só informa `OPERACIONAL` ou `FALHA` (outra situação → `InvalidArgumentException`). Numa transação:

1. `EVENT_UID` já existe → `DUPLICADO`.
2. Bloqueia o equipamento e decide, na ordem:
   arquivado → `HISTORICO` ("equipamento arquivado"); situação **administrativa** (não instalado, manutenção,
   desativado, furtado) → `HISTORICO` ("estado administrativo prevalece"); ocorreu antes de `STATUS_CHANGED_AT` →
   `HISTORICO` ("evento atrasado: estado atual é mais recente", contador `late_events` + 1); mesma situação →
   `HISTORICO` ("sem mudança de situação"); senão `APLICADO` (situação e `STATUS_CHANGED_AT = ocorrido`).
3. Qualquer observação conta como **sinal de vida**: `LAST_SEEN_AT = min(ocorrido, agora)`, só avança.
4. Grava `SITUACAO` em `RESOURCE_EVENT` com `occurred_at`, `received_at`, resultado, resumo e
   `{de, para, motivo, ignorado_porque}`. Se outra conexão gravou o mesmo UID entre a verificação e o `INSERT`
   (`UQ_RESOURCE_EVENT_UID`), lança `DuplicateEvent` e a transação inteira — inclusive a mudança de estado — é desfeita.

## `EventIngestor::position`

`position(string $uid, string $kind, int $resourceId, string $lat, string $lng, DateTimeImmutable $occurredAt, bool $keepHistory, string $source = 'SIMULADOR', ?int $actor = null, string $detail = ''): string`

- `kind = AGENTE`: `AgentRepository::movePosition` só grava se a observação for mais nova que a atual
  (`POS_OBSERVED_AT`); posição antiga não regride a atual.
- `kind = EQUIPAMENTO` (veículo, patinete): bloqueia, grava `POS_*` se for mais nova e conta como sinal.
- Resultado `APLICADO` se moveu, `HISTORICO` se era antiga.
- Com `$keepHistory` (o simulador grava uma amostra a cada 6 ciclos; as ações de demonstração sempre): verifica
  duplicata e grava `POSICAO` com o resumo "Posição lat, lng" ou "Posição antiga … (não substitui a atual)" mais
  `$detail` (ex.: "recebimento simulado pela demonstração"). Sem histórico, a duplicata não é verificada (só o estado
  atual muda).

Depois da ingestão, quem chamou reconcilia os alertas (posição recente encerra `SEM_POSICAO` como `POSICAO_RECEBIDA`).

| Método | Contrato |
|---|---|
| `EventIngestor::__construct` | `(Database, EquipmentRepository, AgentRepository, EventRepository, MetaRepository, Clock)`. |
| `EventIngestor::equipmentStatus` | ver acima. |
| `EventIngestor::position` | ver acima. |
| `EventIngestor::duplicate` | `private (): string` — incrementa `duplicates_ignored` em `APP_META` e devolve `DUPLICADO`. |

Silêncio do aparelho não passa por aqui: ele só deixa de atualizar `LAST_SEEN_AT`, e a central mostra "sem informação
recente" ([`Freshness`](../domain/situacoes-e-atualidade.md#freshness)). Testes: `OperationsTest` (duplicata, atraso,
estado administrativo, 40 ciclos do simulador), `AlertFollowUpTest` (posição nova, antiga e fora de ordem).

## Relacionados

[Domínio: eventos](../../../dominio.md#eventos) · [Simulador](../demo/simulador.md) · [Repositório de eventos](../infrastructure/repositorios-operacao.md#eventrepository)
