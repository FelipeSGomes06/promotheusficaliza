# Dicionário de dados

<!-- fontes: database/migrations/0001_esquema_inicial.sql database/migrations/0002_invariantes_e_cota_de_login.sql database/migrations/0003_planejamento_por_setores.sql database/migrations/0004_quadrados_de_2km.sql database/migrations/0005_atualizacao_mensal_infosiga.sql database/migrations/0006_missao_por_quadrado.sql database/migrations/0007_multas.sql database/migrations/0008_pendencia_regularizada_depois.sql database/migrations/0009_acompanhamento_de_alertas.sql database/migrations/0010_lembrete_da_ultima_conferencia.sql database/migrations/0011_redefinicao_de_senha.sql docker/firebird/10-fiscaliza-init.sh -->

Esquema do Firebird 5 (dialeto 3, `DEFAULT CHARACTER SET UTF8`) tal como as onze migrações o deixam. A fonte de
verdade são os arquivos em [`database/migrations/`](../../database/migrations/README.md); esta página explica cada
tabela, quem escreve nela e as regras que o **próprio banco** garante (CHECK, índices únicos, privilégios). As regras
de negócio que ficam no PHP estão na [referência de código](codigo/README.md).

## Convenções

| Convenção | Significado |
|---|---|
| Sufixo `_AT` | `TIMESTAMP` em **UTC** (convertido para o horário de Marília só na exibição — [`Time`](codigo/domain/tempo.md)). |
| Sufixo `_ON` e `DUE_DATE` | `DATE` **local** de Marília (sem fuso). |
| `LAT`/`LNG`, `POS_*`, `BASE_*`, `CENTER_*` | WGS84 em graus decimais, `DECIMAL(9,6)`, ordem (latitude, longitude). |
| `VERSION_NO` | bloqueio otimista: o formulário devolve a versão lida; a gravação usa `WHERE VERSION_NO = ?` e incrementa. Versão diferente → conflito sem perder a digitação. |
| `ARCHIVED_AT` | preenchido = fora da operação atual; histórico preservado (não há exclusão de equipamento ou agente). |
| `SOURCE` / `CREATED_SOURCE` | como o dado entrou: `MANUAL` (pessoa logada), `SIMULADOR`, `SEMENTE` (carga de demonstração), `SISTEMA` (reação automática). |
| `*_SNAPSHOT`, `PARAMS`, `PAYLOAD`, `FACTORS`, `EVALUATION`, `MODEL`, `AUDIT` | `BLOB SUB_TYPE TEXT` com JSON gravado pelo servidor. |
| Valores monetários | `INTEGER` em **centavos**. |
| Identificadores com zeros à esquerda | texto: `VEHICLE_RENAVAM VARCHAR(11)` (ex.: `'00123456789'`), `CEP CHAR(9)` (ex.: `'17500-000'`). Nunca número. |
| `DECFLOAT(34)` | estimativas do planejamento: o texto mais curto do `double` do JSON volta idêntico (o `DOUBLE PRECISION` perdia o último bit pelo PDO). |
| Intervalos | `[FROM, UNTIL)` — fim exclusivo. |

### Privilégios do usuário da aplicação

A aplicação conecta com um usuário **sem papel administrativo e sem DDL** (criado por
[`docker/firebird/10-fiscaliza-init.sh`](../../docker/README.md), que também revoga o papel `ADMIN` do dono do banco).
Os privilégios vêm de [`Migrator::grantApplicationAccess`](codigo/infrastructure/banco-e-migracoes.md#migrator), a
cada `php bin/fiscaliza migrate`:

| Grupo | Privilégios | Tabelas |
|---|---|---|
| Projeções e cadastros | `SELECT, INSERT, UPDATE, DELETE` | `APP_META`, `AGENCY`, `REGION`, `APP_USER`, `EQUIPMENT`, `AGENT`, `MAINTENANCE_ORDER`, `ALERT`, `ANALYSIS_RUN`, `ANALYSIS_RESULT`, `URBAN_MISSION`, `FINE` |
| **Só leitura e acréscimo** (`Migrator::APPEND_ONLY`) | `SELECT, INSERT` (e `UPDATE`/`DELETE` revogados) | `RESOURCE_EVENT`, `LOGIN_ATTEMPT`, `ALERT_FOLLOWUP`, `FINE_EVENT`, `FINE_DOCUMENT_CHECK`, `URBAN_RUN`, `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_CELL`, `URBAN_SNAPSHOT`, `URBAN_DAILY`, `URBAN_MISSION_LOG` |
| Controle de migração | nenhum | `SCHEMA_MIGRATION (NAME, APPLIED_AT)` |

Consequência: mesmo um defeito no PHP não consegue reescrever histórico, tentativas de login, conferências de multa ou
execuções importadas. A carga de demonstração (`demo:seed`, que **apaga** tudo) roda com a identidade privilegiada do
dono — ver [console](codigo/cli/console.md).

## Mapa das tabelas

| Tabela | Ligações |
|---|---|
| `AGENCY` | órgão de `EQUIPMENT`, `AGENT` e `FINE` (órgão autuador) |
| `REGION` | bairro de `EQUIPMENT`, `AGENT` e `FINE` |
| `EQUIPMENT` | → `MAINTENANCE_ORDER`, `ANALYSIS_RESULT` (→ `ANALYSIS_RUN`), `FINE` (radar) |
| `AGENT` | → `FINE` (agente), `URBAN_MISSION` (→ `URBAN_MISSION_LOG`) |
| `APP_USER` | autoria (`OPENED_BY`, `ACK_BY`, `CREATED_BY`…), `ALERT.ASSIGNEE_ID`; `LOGIN_ATTEMPT` só por hash, sem FK |
| `RESOURCE_EVENT`, `ALERT` | `(RESOURCE_KIND, RESOURCE_ID)` → equipamento ou agente, sem FK (referência polimórfica) |
| `ALERT` | → `ALERT_FOLLOWUP` |
| `FINE` | → `FINE_DOCUMENT_CHECK`, `FINE_EVENT` |
| `URBAN_RUN` | → `URBAN_SECTOR` → `URBAN_FORECAST`, `URBAN_REPLAY`, `URBAN_CELL`, `URBAN_MISSION` |
| `URBAN_SNAPSHOT` | → `URBAN_DAILY` |
| `APP_META` | chave–valor, sem ligações |

## Inventário e operação

### `APP_META`

Chave–valor da instalação (0001). `NAME` é a chave primária; `TEXT_VALUE`, `NUM_VALUE`, `UPDATED_AT`. Escrita por
[`MetaRepository`](codigo/infrastructure/repositorios-operacao.md). Chaves usadas pelo código:

| Chave | Valor | Quem grava |
|---|---|---|
| `dataset`, `profile`, `seeded_at` | versão/perfil do cenário e instante da carga | `demo:seed` ([cenário](codigo/demo/cenario-e-carga.md)) |
| `sim_tick`, `last_sim_tick_at` | número do último ciclo e quando rodou | [simulador](codigo/demo/simulador.md) |
| `duplicates_ignored`, `late_events` | contadores de entregas repetidas e atrasadas | [ingestão](codigo/application/ingestao.md) |
| `fines_seed` | versão da carga de multas de demonstração (repetir não duplica) | [multas de demonstração](codigo/demo/multas-de-demonstracao.md) |
| `infosiga_ultima_checagem` | último download do Infosiga-SP bem-sucedido, mesmo sem arquivo novo | [`UrbanUpdater`](codigo/application/atualizacao-urbana.md) |

### `AGENCY` e `REGION`

`AGENCY` (0001): órgãos — `CODE` único (até 20), `NAME`, `KIND` ∈ `TRANSITO`, `GUARDA`, `POLICIA`, `MANUTENCAO`,
`TRANSPORTE`. `REGION` (0001): bairro de referência — `NAME` único, `ZONE` ∈ `CENTRO`, `NORTE`, `SUL`, `LESTE`, `OESTE`
(agrupamento **de demonstração**, não divisão oficial) e o centro (`CENTER_LAT`, `CENTER_LNG`) usado para sugerir o
bairro mais próximo ([`NearestRegion`](codigo/domain/setores-e-regioes.md)). Ambas só são escritas pela carga de
demonstração; a interface não as edita.

### `EQUIPMENT`

Projeção atual de cada equipamento (0001).

| Grupo | Colunas | Regras do banco |
|---|---|---|
| Identidade | `ID`, `CODE` (único), `ASSET_TAG` (patrimônio, único quando presente), `TYPE_CODE`, `MANUFACTURER`, `MODEL`, `SERIAL_NUMBER` | `TYPE_CODE` ∈ `RADAR`, `CAMERA`, `BALANCA`, `CANCELA`, `TOTEM`, `VEICULO`, `PATINETE`; `UX_EQUIPMENT_SERIAL` = `UPPER(TRIM(fabricante))‖'|'‖UPPER(TRIM(série))` único — a série é única **por fabricante**; série nula não colide |
| Responsável | `AGENCY_ID` → `AGENCY` | obrigatório |
| Situação | `STATUS`, `STATUS_CHANGED_AT` | `STATUS` ∈ `NAO_INSTALADO`, `OPERACIONAL`, `FALHA`, `MANUTENCAO`, `DESATIVADO`, `FURTADO`; transições válidas no PHP ([`EquipmentStatus`](codigo/domain/situacoes-e-atualidade.md)) |
| Endereço | `CEP` (`99999-999`), `STREET`, `ADDRESS_REF`, `REGION_ID`, `CITY`, `UF` | `CITY = 'Marília'` e `UF = 'SP'` (CHECK) |
| Local cadastrado | `LAT`, `LNG`, `LOCATION_KIND` | `LOCATION_KIND` ∈ `INSTALADO`, `PLANEJADO`, `BASE` (móveis usam a base); ambos nulos ou ambos válidos |
| Última posição | `POS_LAT`, `POS_LNG`, `POS_OBSERVED_AT`, `POS_RECEIVED_AT` | posição exige `POS_OBSERVED_AT` |
| Sinal | `LAST_SEEN_AT` | atualizado pelo batimento; sem evento no histórico |
| Manutenção | `MAINT_INTERVAL_DAYS`, `INSTALLED_ON` | intervalo 7–730 dias |
| Outros | `SPECS` (JSON de especificações), `NOTES`, `ARCHIVED_AT`, `VERSION_NO`, `CREATED_AT`, `UPDATED_AT` | `VERSION_NO ≥ 1` |

Índice: `IX_EQUIPMENT_STATUS`. Escrito por [`EquipmentService`](codigo/application/equipamentos.md),
[`EventIngestor`](codigo/application/ingestao.md) e [`MaintenanceService`](codigo/application/manutencao.md);
repositório em [repositórios do inventário](codigo/infrastructure/repositorios-inventario.md).

### `AGENT`

Projeção atual do **agente de campo** (0001) — pessoa/equipe fictícia em campo, **não** uma conta de acesso
(`APP_USER`). Colunas: `CODE` (único), `CALL_SIGN` (indicativo, ex.: "Hermes 06"), `ROLE` ∈ `TRANSITO`, `GUARDA`,
`POLICIA`, `AGENCY_ID`, `STATUS` ∈ `EM_ATIVIDADE`, `EM_PAUSA`, `INDISPONIVEL`, `FORA_DE_SERVICO`, `STATUS_CHANGED_AT`.

A **base de trabalho** (`BASE_NAME`, `BASE_STREET`, `BASE_REF`, `BASE_CEP`, `REGION_ID`, `BASE_LAT`, `BASE_LNG` —
coordenadas obrigatórias) é onde o agente se apresenta; a **última posição** (`POS_LAT`, `POS_LNG`,
`POS_OBSERVED_AT`, `POS_RECEIVED_AT`) é o que o rádio/telemetria simulada informou por último. As duas nunca se
confundem: editar a base não move a posição, e uma posição nova não altera a base. Mais `ARCHIVED_AT`, `VERSION_NO`,
`CREATED_AT`, `UPDATED_AT`. Serviço: [`AgentService`](codigo/application/agentes.md).

### `MAINTENANCE_ORDER`

Ordem de manutenção (0001, 0002).

| Coluna | Significado / regra |
|---|---|
| `EQUIPMENT_ID` | equipamento atendido |
| `KIND` | `CORRETIVA` ou `PREVENTIVA` |
| `PRIORITY` | `IMEDIATA` ou `PROGRAMADA`; **programada sempre tem `DUE_DATE`** (`CK_MAINT_DUE`) |
| `STATE` | `ABERTA` → `EM_EXECUCAO` → `CONCLUIDA`; `CANCELADA` a partir de aberta |
| `DUE_DATE` | data local prevista |
| `REASON`, `RESOLUTION` | motivo de abertura (obrigatório) e resolução |
| `RESULT_STATUS` | situação em que o equipamento ficou ao concluir: `OPERACIONAL`, `FALHA` ou `DESATIVADO` |
| `SOURCE` | `MANUAL`, `SIMULADOR`, `SEMENTE`, `SISTEMA` |
| `OPENED_AT`, `STARTED_AT`, `CLOSED_AT`, `OPENED_BY`, `CLOSED_BY` | `CLOSED_AT` preenchido **se e somente se** concluída ou cancelada (`CK_MAINT_CLOSED`) |
| `VERSION_NO` | bloqueio otimista |

`UX_MAINT_ONE_RUNNING` (0002, índice único **parcial** `WHERE STATE = 'EM_EXECUCAO'`): no máximo uma intervenção em
execução por equipamento, mesmo com dois cliques simultâneos — o segundo recebe a violação traduzida em conflito
([`Database::translate`](codigo/infrastructure/banco-e-migracoes.md#databasetranslate)). Índice `IX_MAINT_EQUIPMENT`.

### `RESOURCE_EVENT`

Histórico **só de acréscimo** de equipamentos e agentes (0001).

| Coluna | Significado |
|---|---|
| `EVENT_UID` | chave única da entrega (idempotência: a mesma entrega duas vezes vira `DuplicateEvent` e é contada em `duplicates_ignored`) |
| `RESOURCE_KIND`, `RESOURCE_ID` | `EQUIPAMENTO` ou `AGENTE` + id (sem FK) |
| `EVENT_TYPE` | tipos gravados pelo código: `CADASTRO`, `ALTERACAO`, `SITUACAO`, `POSICAO`, `ARQUIVAMENTO`, `MANUTENCAO_ABERTA`, `MANUTENCAO_INICIADA`, `MANUTENCAO_CONCLUIDA`, `MANUTENCAO_CANCELADA` |
| `SOURCE`, `ACTOR_USER_ID` | origem e conta (quando manual) |
| `OCCURRED_AT`, `RECEIVED_AT` | quando aconteceu × quando chegou |
| `OUTCOME` | `APLICADO` (mudou o estado atual) ou `HISTORICO` (ficou só no histórico: atrasado ou sobreposto por situação administrativa) |
| `SUMMARY`, `PAYLOAD` | frase exibida e JSON (por exemplo `alteracoes` com antes/depois) |

Índices `IX_EVENT_RESOURCE (RESOURCE_KIND, RESOURCE_ID, OCCURRED_AT)` e `IX_EVENT_TYPE_OCCURRED`. Gravado por
[`History`](codigo/application/equipamentos.md) e [`EventIngestor`](codigo/application/ingestao.md); lido pela ficha, pela
Visão geral e pela [análise preventiva](codigo/application/analise-preventiva.md).

### `ALERT` e `ALERT_FOLLOWUP`

`ALERT` (0001, 0009) é a projeção de cada alerta.

| Coluna | Significado / regra |
|---|---|
| `RESOURCE_KIND`, `RESOURCE_ID` | recurso afetado |
| `REASON`, `SEVERITY` | causa (ex.: sem sinal, sem posição, falha) e `CRITICA`/`ALTA`/`MEDIA` — regras em [`AlertRules`](codigo/domain/indicadores-e-alertas.md) |
| `STATE` | `ABERTO` → `RECONHECIDO` (alguém tomou **ciência**) → `RESOLVIDO` |
| `OPEN_KEY` | `"<tipo>:<id>:<motivo>"`, único; existe **só** enquanto não resolvido (`CK_ALERT_OPEN_KEY`). Impede dois alertas abertos para a mesma causa, mesmo sob concorrência |
| `MESSAGE`, `OPENED_AT` | texto e abertura |
| `ACK_AT`, `ACK_BY`, `ACK_NOTE` | ciência: quem, quando, nota. **Ciência não resolve a causa**: o alerta segue até a causa acabar ou alguém encerrá-lo com motivo |
| `RESOLVED_AT`, `RESOLVED_REASON` | encerramento; motivo ∈ `POSICAO_RECEBIDA`, `FORA_DE_TURNO`, `ARQUIVADO`, `CAUSA_ENCERRADA` (só com `STATE = 'RESOLVIDO'`). Alertas anteriores à 0009 ficam com motivo nulo ("motivo não registrado") |
| `ASSIGNEE_ID` | responsável: uma **conta** (`APP_USER`), não um agente |
| `NEXT_CHECK_AT` | próxima conferência combinada |
| `FOLLOWUP_NO` | versão do acompanhamento (incrementa a cada atribuição ou providência); formulário com versão antiga é recusado sem perder a digitação |

`ALERT_FOLLOWUP` (0009, **só acréscimo**): `KIND = 'PROVIDENCIA'` com `ACTION_CODE` ∈ `CONTATO_TENTADO`,
`CONTATO_CONFIRMADO`, `COMUNICACAO_VERIFICADA`, `OUTRA` (esta exige `NOTE`) — registro do que alguém fez **por outro
meio** (o sistema não envia mensagens); ou `KIND = 'ATRIBUICAO'` com `ASSIGNEE_FROM` → `ASSIGNEE_TO` (diferentes).
`CREATED_BY`, `CREATED_AT`, `NEXT_CHECK_AT`. Índices `IX_ALERT_STATE`, `IX_FOLLOWUP_ALERT`. Serviço:
[`AlertService`](codigo/application/alertas.md); ADR-0022.

## Contas e acesso

### `APP_USER` e `LOGIN_ATTEMPT`

`APP_USER` (0001, 0011) — **conta de acesso** ao sistema: `USERNAME` (único sem diferenciar maiúsculas,
`UX_APP_USER_USERNAME` = `UPPER(USERNAME)`), `DISPLAY_NAME`, `PASSWORD_HASH` (hash do `password_hash` do PHP; a senha
nunca é gravada em claro, em log nem em histórico), `ROLE` ∈ `OPERADOR`, `ADMIN`, `ACTIVE`, `LAST_LOGIN_AT`,
`CREATED_AT`; desde a 0011, `CREDENTIAL_NO` (versão da credencial: a sessão guarda a do login e deixa de valer quando ela
muda), `PASSWORD_RESET_AT` e `PASSWORD_RESET_BY`.

`LOGIN_ATTEMPT` (0001, 0002; **só acréscimo**): limite de tentativas. `KEY_HASH` = SHA-256 de `u:<usuário>` ou
`ip:<endereço>` (o valor em si não é guardado), `ATTEMPTED_AT`, `SLOT_NO` (vaga numerada reservada **antes** do hash da
senha; `UX_LOGIN_ATTEMPT_SLOT (KEY_HASH, SLOT_NO)` parcial e descendente impede que tentativas simultâneas passem do
limite) e `SETTLES_ID` (linha de quitação gravada no login correto, sem apagar nada). Serviço:
[`AuthService`](codigo/application/autenticacao.md); repositório em [contas](codigo/infrastructure/repositorio-contas.md).

## Análise preventiva

### `ANALYSIS_RUN` e `ANALYSIS_RESULT`

`ANALYSIS_RUN` (0001): uma execução reprodutível da heurística "Revisões recomendadas" — `METHOD_VERSION` (ex.:
`atencao-v1`), `AS_OF`, `HORIZON_DAYS` (1–90), `DATA_FROM`, `DATA_TO`, `EVENTS_USED`, `PARAMS` (pesos e limiares em
JSON), `EVALUATION` (avaliação retrospectiva), `CREATED_AT`, `CREATED_BY`.

`ANALYSIS_RESULT` (0001, 0002): um por equipamento e execução (`UQ_RESULT_RUN_EQUIPMENT`) — `ATTENTION` ∈ `ALTA`,
`MODERADA`, `BAIXA`, `INSUFICIENTE`, `CORRETIVA`, `NAO_AVALIADO` (fora de serviço, desde a 0002), `SCORE` 0–100 (nulo
quando não se aplica), `FACTORS` (JSON com os fatores que pesaram), `RECOMMENDATION`, `HISTORY_DAYS`. Apagar a execução
apaga os resultados (`ON DELETE CASCADE`). Método em [atenção preventiva](codigo/domain/atencao-preventiva.md).

## Multas (demonstrativas)

Registro **manual e demonstrativo**: nada aqui emite, cobra, consulta ou protocola multa em sistema oficial. "Pagamento",
"contestação" e "conferência" são **informações anotadas** por quem usa o sistema.

### `FINE`

Projeção atual de cada multa (0007, 0008, 0010).

| Grupo | Colunas | Regras do banco |
|---|---|---|
| Ciclo do registro | `RECORD_STATE` ∈ `RASCUNHO`, `REGISTRADO`, `CANCELADO`; `REGISTERED_AT`; `CANCELLED_AT`, `CANCELLED_BY`, `CANCEL_SOURCE`, `CANCEL_REASON` | `CK_FINE_REGISTERED`: registrado exige ocorrência, tipo, base legal, órgão, AIT **ou** motivo da falta, logradouro **ou** referência, snapshot da origem, placa, UF, fabricante, modelo, espécie, RENAVAM **ou** motivo, e origem coerente (agente com `AGENT_ID`, radar com `EQUIPMENT_ID`). `CK_FINE_CANCELLED`: dados do cancelamento existem se e somente se cancelado |
| Fato | `DESCRIPTION`, `OCCURRED_AT`, `INFRACTION_KIND` ∈ `VELOCIDADE`, `AVANCO_SEMAFORO`, `PARADA_FAIXA`, `CONVERSAO_PROIBIDA`, `FAIXA_EXCLUSIVA`, `OUTRA`; `LEGAL_REFERENCE`, `INFRACTION_CODE`, `INFRACTION_DETAIL` | — |
| Órgão e AIT | `AGENCY_ID` (órgão autuador), `EXTERNAL_AIT` (como digitado), `AIT_KEY` (normalizado), `AIT_MISSING_REASON` | `UX_FINE_AIT (AGENCY_ID, AIT_KEY)` parcial: o mesmo AIT no mesmo órgão só uma vez (cancelados conservam a chave) |
| Local | `STREET`, `ADDRESS_REF`, `REGION_ID`, `LAT`, `LNG` | coordenadas ambas ou nenhuma |
| Origem | `SOURCE_KIND` ∈ `RADAR`, `AGENTE`; `EQUIPMENT_ID`, `AGENT_ID`, `SOURCE_NOTE` (observação quando órgão/radar divergem), `SOURCE_SNAPSHOT` (JSON da origem **no momento do registro**, gravado pelo servidor) | sem origem → sem recurso; agente sem radar; radar pode ter agente operador |
| Velocidade | `SPEED_LIMIT_KMH`, `SPEED_MEASURED_KMH`, `SPEED_CONSIDERED_KMH`, `RADAR_DETAILS`, `EVIDENCE_REFERENCE` | 1–350; considerada ≤ medida; `CK_FINE_REGISTERED_SPEED`: velocidade registrada exige radar e considerada > limite |
| Veículo autuado | `VEHICLE_PLATE` (`AAA9A99`/`AAA9999`), `VEHICLE_UF`, `VEHICLE_MANUFACTURER`, `VEHICLE_MODEL`, `VEHICLE_SPECIES`, `VEHICLE_COLOR`, `VEHICLE_YEAR` (1900–2200), `VEHICLE_RENAVAM` (texto de 11 dígitos), `RENAVAM_MISSING_REASON` | independente das viaturas do inventário |
| Andamento informado | `PROCESS_STAGE` ∈ `NAO_INFORMADA`, `AUTUACAO`, `PENALIDADE_NOTIFICADA`, `ENCERRAMENTO_INFORMADO`; `PAYMENT_STATUS` ∈ `NAO_INFORMADO`, `SEM_COBRANCA_INFORMADA`, `EM_ABERTO`, `PAGAMENTO_INFORMADO`; `CONTEST_STATUS` ∈ `NAO_INFORMADA`, `NAO_APRESENTADA`, `EM_ANALISE`, `DEFERIDA`, `INDEFERIDA`; `CONTEST_KIND` ∈ `DEFESA_PREVIA`, `RECURSO_1`, `RECURSO_2` | `CK_FINE_PAID`: pagamento informado exige data, valor e referência; `CK_FINE_CONTESTED`/`CK_FINE_DECIDED`; `CK_FINE_CLOSED`: encerramento exige data, referência e nota |
| Datas e valores | `NOMINAL_AMOUNT_CENTS`, `AUTUATION_ISSUED_ON`, `AUTUATION_RECEIVED_ON`, `PENALTY_ISSUED_ON`, `PENALTY_RECEIVED_ON`, `PAYMENT_DUE_ON`, `DEFENSE_DUE_ON`, `APPEAL_DUE_ON`, `PAID_ON`, `PAID_AMOUNT_CENTS`, `PAYMENT_REFERENCE`, `CONTEST_SUBMITTED_ON`, `CONTEST_REFERENCE`, `CONTEST_DECIDED_ON`, `CONTEST_RESULT_NOTE`, `CLOSED_ON`, `CLOSURE_REFERENCE`, `CLOSURE_NOTE` | valores até R$ 1.000.000,00 |
| Projeções derivadas | `OCC_DOC_STATUS` ∈ `NAO_VERIFICADA`, `REGULARIDADE_INFORMADA`, `PENDENCIA_INFORMADA` (documentos **no dia da multa**); `DOC_REVIEW_ON` + `DOC_REVIEW_SCOPE` (lembrete da conferência anotada por último; renomeado de `OCC_DOC_REVIEW_ON` na 0010); `ATTENTION_COUNT` (motivos "A conferir" que não dependem da data de hoje) | recalculadas pelo servidor na mesma transação da mudança |
| Versões e autoria | `VERSION_NO` (bloqueio otimista), `SUBJECT_VERSION` (identidade do veículo: muda quando placa/RENAVAM/etc. mudam e torna antigas as conferências anteriores), `CREATED_AT`, `UPDATED_AT`, `CREATED_BY`, `UPDATED_BY`, `CREATED_SOURCE` ∈ `MANUAL`, `SEMENTE` | conta obrigatória salvo `SEMENTE` |

Índices: `IX_FINE_OCCURRED` (descendente), `IX_FINE_AGENCY`, `IX_FINE_EQUIPMENT`, `IX_FINE_AGENT`, `IX_FINE_PLATE`,
`IX_FINE_RENAVAM`. Regras no PHP: [`FineRules`/`FineDocumentStatus`](codigo/domain/multas.md); serviço:
[`FineService`](codigo/application/multas.md); repositórios: [multas](codigo/infrastructure/repositorios-multas.md).

### `FINE_DOCUMENT_CHECK`

Constatação documental **imutável** (0007; só acréscimo): `FINE_ID`, `SUBJECT_VERSION` (identidade do veículo a que se
refere), `SCOPE` ∈ `OCORRENCIA` (no dia da multa) ou `POSTERIOR` (depois), `EFFECTIVE_ON` (data a que se refere),
`CHECKED_AT`, `DOCUMENT_STATUS` (mesmos três valores de `OCC_DOC_STATUS`), `SOURCE_TYPE` ∈ `CRLV_APRESENTADO`,
`CONSULTA_OFICIAL_INFORMADA`, `RELATO_MANUAL`, `NAO_INFORMADA` (a evidência **apresentada** — o sistema não consulta
nada), `SOURCE_REFERENCE`, `CRLV_EXERCISE`, `PENDING_ITEMS`, `REVIEW_ON` (lembrete, ≥ `EFFECTIVE_ON`), `REASON`,
`SOURCE` ∈ `MANUAL`, `SEMENTE`, `ACTOR_USER_ID`, `CREATED_AT`.

Regras: conclusão exige fonte e referência; pendência exige a descrição; "Não verificada" explícita exige motivo.
A 0008 recontou `ATTENTION_COUNT` para que uma pendência no dia da multa regularizada por conferência posterior da mesma
identidade deixe de contar (atalho documentado no próprio arquivo: uma multa regravada entre o `git pull` e o `migrate`
seria descontada duas vezes até a próxima escrita).

### `FINE_EVENT`

Histórico das multas (0007; só acréscimo): `EVENT_UID` único (chave do comando emitida pelo servidor no formulário —
reenviar o mesmo formulário não duplica), `REQUEST_HASH` (impressão digital SHA-256 da operação: tipo, alvo, versão,
ator e dados normalizados; mesma chave com dados diferentes é recusada), `EVENT_TYPE` ∈ `CRIACAO`, `EDICAO_RASCUNHO`,
`REGISTRO`, `RETIFICACAO`, `CONFERENCIA_DOCUMENTAL`, `ANDAMENTO`, `CANCELAMENTO`, `SOURCE`, `ACTOR_USER_ID`,
`RECORDED_AT`, `SUMMARY`, `REASON`, `PAYLOAD` (antes/depois). Índice `IX_FINE_EVENT_FINE`.

## Tabelas do planejamento (`URBAN_*`)

As contagens são **reais e agregadas** do Infosiga-SP (Detran-SP, CC BY 4.0) para Marília; nenhuma vítima, veículo ou
arquivo bruto é guardado. As **missões** de reforço são **fictícias**. Nada destas tabelas vira evento de recurso, e o
simulador nunca as toca. Explicação do método em [planejamento por setores](../planejamento-por-setores.md).

| Tabela | Migração | Conteúdo | Regras |
|---|---|---|---|
| `URBAN_RUN` | 0003 | uma execução importada: `BUNDLE_SHA256` (único), `PROTOCOL_VERSION`, semana `[AS_OF, UNTIL_DATE)`, `DATA_REFERENCE_THROUGH` (corte de informação), `GENERATED_AT`, `SOURCE_SNAPSHOT_SHA256`, `SELECTED_METHOD` ∈ `baseline`, `poisson`, `CANDIDATE_METHOD = 'poisson'`, `TEST_RELATIVE_GAIN`, `MODEL_ISSUED_AT`, `NOTICE`, `EVALUATION`, `MODEL`, `PROVENANCE`, `FILES`, `IMPORTED_AT` | semana de exatamente 7 dias; corte anterior ao início |
| `URBAN_SECTOR` | 0003 | 4 setores experimentais `NO`, `NE`, `SO`, `SE` (retângulos; **não** são bairros nem limite municipal) com `SOUTH`/`NORTH`/`WEST`/`EAST` e centro | caixa válida |
| `URBAN_FORECAST` | 0003 | previsão da semana por setor: `RANK_NO` 1–4, `PRIORITY` (`PRIORITARIA` só no 1º, senão `ACOMPANHAR`), `EXPECTED_COUNT`, `EXPECTED_ML` (Poisson, experimental), `EXPECTED_BASELINE`, janela do histórico, `COUNT_13W`, `COUNT_52W`, `DATA_AGE_DAYS` | ranking único por execução |
| `URBAN_REPLAY` | 0003 | retrospecto: por semana de teste e setor, as duas estimativas congeladas e o `OBSERVED_COUNT` registrado depois | histórico termina antes da semana |
| `URBAN_CELL` | 0004 | grade 8 × 8 de quadrados de 2 km (`CELL_NO` 0–63, setor, caixa, `COUNT_13W`, `COUNT_52W`, `EXPECTED` > 0, `RANK_NO` 1–64 único) | — |
| `URBAN_SNAPSHOT` | 0005 | cada download mensal: `SHA256` (único), `SOURCE_URL`, `DOWNLOADED_AT`, `BYTES`, `DATA_FROM`/`DATA_THROUGH`, `ELIGIBLE`, `AUDIT` (JSON) | — |
| `URBAN_DAILY` | 0005 | contagem por dia e quadrado (`COUNT_N` > 0; ausência = zero) | `CELL_NO` 0–63 |
| `URBAN_MISSION` | 0003, 0006 | missão **fictícia**: execução, setor, período, `METHOD`, `EXPECTED_COUNT` e `RANK_NO` congelados, `AGENT_ID`, `SELECTION` ∈ `SUGERIDO`, `MANUAL` (manual exige `JUSTIFICATION`), `REASON`, `SUPPORT_POINT`, `NOTE`, `STATE` ∈ `CONFIRMADA`, `CONCLUIDA`, `CANCELADA`, confirmação/encerramento com autoria, `CELL_NO` (quadrado, opcional desde a 0006) | `UX_URBAN_MISSION_ACTIVE`: no máximo **uma missão vigente por agente**, mesmo com confirmações simultâneas |
| `URBAN_MISSION_LOG` | 0003 | transições de estado com autor, instante e nota | só acréscimo |

Gravação: [`UrbanImporter`](codigo/application/importacao-urbana.md) (importação de pacote),
[`UrbanUpdater`](codigo/application/atualizacao-urbana.md) (download mensal + previsão semanal),
[`PlanningService`](codigo/application/planejamento.md) (missões); repositórios em
[planejamento](codigo/infrastructure/repositorios-planejamento.md).

## Relacionados

[`database/migrations/`](../../database/migrations/README.md) · [Banco e migrações](codigo/infrastructure/banco-e-migracoes.md) ·
[Domínio](../dominio.md) · [Segurança](../seguranca.md)
