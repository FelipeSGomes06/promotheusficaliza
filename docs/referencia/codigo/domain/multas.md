# Regras das multas

<!-- fontes: src/Domain/FineRules.php src/Domain/FineDocumentStatus.php -->

Regras puras da aba **Multas** ([ADR-0017](../../../adr/0017-aba-multas.md)): vocabulário fechado, identificadores,
campos mínimos para registrar, coerência das velocidades, motivos de "A conferir", avisos de andamento e a escolha da
conferência documental que vale. "Registrar" aqui é um **cadastro interno conferido**: não emite, não certifica, não
cobra e não protocola nada; nenhum dado é consultado em órgão externo.

O caso de uso que usa estas regras é [`FineService`](../application/multas.md#fineservice).

## `FineRules`

### Vocabulário (constantes)

| Constante | Chave → rótulo |
|---|---|
| `STATES` | `RASCUNHO` Rascunho · `REGISTRADO` Registrado · `CANCELADO` Registro cancelado |
| `KINDS` | `VELOCIDADE` Excesso de velocidade · `AVANCO_SEMAFORO` Avanço de sinal vermelho · `PARADA_FAIXA` Parada sobre a faixa de pedestres · `CONVERSAO_PROIBIDA` · `FAIXA_EXCLUSIVA` · `OUTRA`. São categorias de interface, **não** o catálogo nacional de enquadramentos. |
| `SOURCES` | `RADAR` Radar · `AGENTE` Agente de trânsito |
| `STAGES` | `NAO_INFORMADA` · `AUTUACAO` (aviso de infração enviado) · `PENALIDADE_NOTIFICADA` (multa aplicada) · `ENCERRAMENTO_INFORMADO` (encerrada) |
| `PAYMENTS` | `NAO_INFORMADO` · `SEM_COBRANCA_INFORMADA` · `EM_ABERTO` (a pagar) · `PAGAMENTO_INFORMADO` (paga) |
| `CONTESTS` | `NAO_INFORMADA` · `NAO_APRESENTADA` · `EM_ANALISE` · `DEFERIDA` (aceito) · `INDEFERIDA` (negado) |
| `CONTEST_KINDS` | `DEFESA_PREVIA` · `RECURSO_1` (JARI) · `RECURSO_2` (conselho estadual) |
| `UFS` | as 27 UFs |
| `IDENTITY` | `vehicle_plate`, `vehicle_uf`, `vehicle_renavam`, `vehicle_manufacturer`, `vehicle_model`, `vehicle_species`, `occurred_at` — mudar qualquer um cria **nova versão do sujeito** e pede nova conferência |
| `LABELS` | rótulos de todos os campos do cadastro (formulário, resumo de erros, pendências, histórico) |
| `SPEED_RULE` | texto da regra da velocidade considerada, mostrado na ficha |

### Métodos

| Método | Contrato |
|---|---|
| `FineRules::consideredSpeed` | `static (int $measured): int` — velocidade considerada = medida − erro admitido: até 107 km/h medidos, −7 km/h (mín. 0); acima, −7% com arredondamento matemático (`intdiv(m·93 + 50, 100)`). Resolução Contran 798/2020. Ex.: 71 → 64; 104 → 97; 120 → 112. O valor enviado pelo navegador é ignorado: o servidor sempre recalcula. |
| `FineRules::code` | `static (int $id): string` — código interno `DEMO-MUL-%06d` (zeros à esquerda: `DEMO-MUL-000023`). Não é coluna: deriva do ID. |
| `FineRules::idFromCode` | `static (string $text): ?int` — ID a partir do código (com ou sem zeros, sem diferenciar maiúsculas); `null` se não for código ou fora de 1…2147483647. Usado pela busca da lista. |
| `FineRules::plate` | `static (string $text): string` — placa sem hífen nem espaços, em maiúsculas. |
| `FineRules::isPlate` | `static (string $plate): bool` — formato antigo `AAA1234` ou Mercosul `AAA1A23` (`^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$`); confere só o formato. |
| `FineRules::aitKey` | `static (?string $ait): ?string` — chave de comparação do AIT: aparada e em maiúsculas; pontuação significativa é mantida. `null` para vazio. Gravada em `AIT_KEY` (índice único por órgão). |
| `FineRules::missingForRegistration` | ver abaixo. |
| `FineRules::registrationConflicts` | ver abaixo. |
| `FineRules::attention` | ver abaixo. |
| `FineRules::progressWarnings` | ver abaixo. |
| `FineRules::brDate` | `private static (string $date): string` — `AAAA-MM-DD` → `DD/MM/AAAA` nos textos. |

### `FineRules::missingForRegistration`

`static missingForRegistration(array $f): array<string, string>` — o que falta para registrar (campo → orientação). Um
rascunho pode ficar incompleto; registrado, não.

Exige: `occurred_at`, `infraction_kind`, `legal_reference`, `agency_id`; `external_ait` **ou** `ait_missing_reason`;
`street` **ou** `address_ref` (o ponto no mapa sozinho não basta); `source_kind` e, conforme a origem, `equipment_id`
(radar) ou `agent_id` (agente); em `VELOCIDADE`, `speed_limit_kmh` e `speed_measured_kmh`; `vehicle_plate`,
`vehicle_uf`, `vehicle_manufacturer`, `vehicle_model`, `vehicle_species`; `vehicle_renavam` **ou**
`renavam_missing_reason`. Testes: `FineRulesTest`.

### `FineRules::registrationConflicts`

`static registrationConflicts(array $f): array<string, string>` — incoerências que impedem registrar (o rascunho
continua): em `VELOCIDADE`, origem diferente de `RADAR` ("medidor operado por agente: escolha Radar e indique o agente
operador") e velocidade considerada ≤ regulamentada ("não há excesso para registrar"). Não calcula enquadramento.

### `FineRules::attention`

`static attention(array $f, ?string $today = null): list<string>` — motivos de **"A conferir"**. Nenhum motivo
significa multa inválida: são pendências de cadastro ou de conferência.

| Motivo | Quando |
|---|---|
| "Rascunho: faltam N informação(ões)…" ou "Rascunho completo: falta registrar…" | `record_state = RASCUNHO` |
| "Documentos do carro no dia da multa ainda não conferidos." | `occ_doc_status = NAO_VERIFICADA` |
| "…com pendência no dia da multa, sem regularização informada depois." | pendência na ocorrência e a última conferência posterior não é regularidade |
| "Documentos: precisam ser conferidos de novo (lembrete para DD/MM/AAAA…)" | só com `$today`: `doc_review_on ≤ hoje` (data de Marília), o lembrete em vigor |
| "Dados metrológicos do radar ou referência da evidência a conferir." | velocidade por radar sem registro Inmetro, certificado, data de verificação ou referência da evidência |
| "Última verificação do medidor informada é posterior à ocorrência…" | `verified_on` depois da data local da ocorrência |
| "No dia da ocorrência, a última verificação do radar tinha mais de 12 meses (vencida)…" | `verified_on` anterior a 12 meses antes da ocorrência |

Cancelado nunca tem motivos. **Sem `$today`**, só os motivos que não dependem do dia: é a base da contagem gravada em
`FINE.ATTENTION_COUNT`; a lista soma o lembrete vencido na própria consulta (`DOC_REVIEW_ON <= hoje`), então o resumo
"a conferir" muda de um dia para o outro sem regravar a multa ([`FineRepository::search`](../infrastructure/repositorios-multas.md#finerepositorysearch)).
Testes: `FineRulesTest` (pendência × regularização posterior, lembrete no próprio dia), `FinesTest`.

### `FineRules::progressWarnings`

`static progressWarnings(array $p): list<string>` — sequências administrativas incomuns, só como **aviso** (a
informação é gravada como foi informada): data de penalidade com etapa anterior à penalidade; pagamento informado sem
etapa; valor pago diferente do nominal ("o sistema não calcula descontos").

## `FineDocumentStatus`

Situação documental **informada manualmente**. "Não verificada" é um estado legítimo — nunca equivale a regular — e uma
regularidade posterior **não corrige** a condição informada para a data da ocorrência.

| Constante | Conteúdo |
|---|---|
| `NOT_CHECKED`, `REGULAR`, `PENDING` | `NAO_VERIFICADA` (Não conferidos), `REGULARIDADE_INFORMADA` (Em dia), `PENDENCIA_INFORMADA` (Com pendência) |
| `LABELS` | rótulos acima |
| `SCOPES` | `OCORRENCIA` "No dia da multa" · `POSTERIOR` "Depois da multa" |
| `SOURCE_TYPES` | `CRLV_APRESENTADO`, `CONSULTA_OFICIAL_INFORMADA` (feita fora do sistema), `RELATO_MANUAL`, `NAO_INFORMADA` — nenhuma faz consulta externa |
| `REVIEW_CHOICES` | `NAO` Não precisa · `30` Em 30 dias · `90` Em 90 dias · `DATA` Escolher data |

### `FineDocumentStatus::reviewOn`

`static reviewOn(?string $choice, ?string $date, string $today): ?string` — data do lembrete (`REVIEW_ON`) a partir da
resposta a "Quer ser lembrado de conferir de novo?":

| `$choice` | `$date` | Resultado |
|---|---|---|
| `null` (formulário sem JavaScript ou chamada antiga) | qualquer | `$date` |
| `DATA` | informada | `$date`; ausente → `ValidationFailed` "Informe a data do lembrete." |
| `NAO`, `30`, `90` | informada | `ValidationFailed` "Use só uma resposta…" (nada é descartado em silêncio) |
| `NAO` | vazia | `null` |
| `30` / `90` | vazia | hoje + 30/90 dias |

### `FineDocumentStatus::select`

`static select(array $checks, int $subjectVersion, string $scope): ?array` — a constatação que **vale** para um
escopo: a mais recente pelo **instante da conferência** (`checked_at`, desempate pelo ID) entre as da versão atual do
sujeito. Um lançamento atrasado não substitui uma conferência mais recente só por ter chegado depois; conferências de
uma identidade anterior (antes da retificação) não valem para a nova.

### `FineDocumentStatus::reminder`

`static reminder(array $checks, int $subjectVersion): ?array` — conferência cujo **lembrete está em vigor**: a anotada
por último (maior ID) na versão atual do sujeito, de qualquer escopo. Cada conferência responde de novo à pergunta do
lembrete; a resposta mais recente substitui a anterior, inclusive "Não precisa"
([ADR-0023](../../../adr/0023-revisao-de-logica-e-fluxos.md)).

Testes: `FineRulesTest`, `FinesTest` (lembrete posterior, virada do dia local, última conferência, nova identidade).

## Relacionados

[`FineService`](../application/multas.md) · [Guia: Multas](../../../guias/multas.md) ·
[Contratos HTTP das multas](../../../http.md#multas) · [Dicionário: `FINE`](../../dicionario-de-dados.md#fine)
