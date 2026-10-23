# Rotas HTTP, permissões e contratos

Todas as rotas ficam sob `APP_BASE_PATH` (padrão `/promotheusfiscaliza`). Redirecionamentos, formulários, `fetch`,
assets e cookie de sessão usam o prefixo. **Não há leitura pública** ([ADR-0014](adr/0014-acesso-somente-com-conta-cadastrada.md)):
"conta" = qualquer conta ativa (operador ou administrador), cadastrada pelo administrador; sem ela, GET de página →
`302 /entrar?voltar=…` e as demais requisições (inclusive a API) → 401. Só `/entrar`, `POST /sair` e `/saude` são abertas. Escrita = `POST` com token CSRF (`_csrf` no corpo ou `X-CSRF-Token`) e,
se presente, `Origin` igual a `APP_ORIGIN` (ou à origem da requisição).

| Método | Caminho | Acesso | Resposta |
|---|---|---|---|
| GET | `/` | conta | Visão geral (HTML renderizado no servidor + JSON inicial embutido). Query: os mesmos filtros da API, mais `visao=lista` e `selecionar=EQUIPAMENTO:<id>` ou `AGENTE:<id>` (abre a ficha resumida). |
| GET | `/api/central` | conta | Instantâneo JSON (ver abaixo). `Cache-Control: no-store`. Sem conta: 401 `{"error", "reference"}`, sem dados. |
| GET | `/api/cep/{cep}` | conta | Rua pelo CEP para o cadastro de agente ([contrato](#consulta-de-cep-get-apicepcep)). Sem conta: 401, sem chamada externa. |
| GET | `/api/localizar?rua=&cep=` | conta | Ponto da base pela rua (ou pela rua do CEP), para quem não consegue marcar no mapa ([contrato](#ponto-pelo-endereço-get-apilocalizar)). Sem conta: 401. |
| GET | `/equipamentos` | conta | Lista paginada (20). Query: `q`, `categoria[]`, `situacao[]`, `bairro[]`, `orgao[]`, `ordem` ∈ {codigo, tipo, situacao, bairro, atualizado}, `arquivados=1`, `pagina`. |
| GET | `/equipamentos/{id}` | conta | Ficha: o que é, onde está, situação e atualidade, próxima ação, manutenção, alertas, revisão recomendada, histórico. |
| GET | `/equipamentos/novo` · `/equipamentos/{id}/editar` | operador | Formulários. Visitante → `302 /entrar?voltar=…`. Arquivado → 409. |
| POST | `/equipamentos` · `/equipamentos/{id}` | operador | Cria / altera. Ver [Escrita](#escrita). |
| POST | `/equipamentos/{id}/situacao` | operador | Mudança manual de situação. |
| POST | `/equipamentos/{id}/arquivar` | operador | Arquivamento. |
| POST | `/equipamentos/{id}/simular-falha` | operador | Evento fictício de telemetria pelo caminho do simulador (demonstração). |
| POST | `/equipamentos/{id}/manutencoes` | operador | Abre ordem. |
| POST | `/manutencoes/{id}/iniciar` · `/concluir` · `/cancelar` | operador | Ciclo da ordem. |
| POST | `/alertas/{id}/reconhecer` | operador | Registra ciência. Com `Accept: application/json` responde JSON. |
| GET | `/agentes`, `/agentes/{id}` | conta | Lista e ficha. Query da lista: `q`, `categoria[]` (`AG_TRANSITO`…), `situacao[]`, `orgao[]`, `ordem` ∈ {codigo, nome, funcao, situacao, bairro}, `arquivados=1`, `pagina`. |
| GET/POST | `/agentes/novo`, `/agentes`, `/agentes/{id}/editar`, `/agentes/{id}`, `/agentes/{id}/situacao`, `/agentes/{id}/arquivar` | operador | Como nos equipamentos. |
| GET | `/multas` | conta | Lista paginada (20) com três resumos do mesmo filtro (registros, a conferir, pagamento informado). Query: `q` (código interno `DEMO-MUL-…`, AIT, placa ou RENAVAM; até 60), `origem` ∈ {RADAR, AGENTE}, `etapa`, `orgao` (autuador), `radar`, `agente` (agente da constatação ou operador do radar), `documentacao` (na ocorrência), `pagamento`, `de`/`ate` (datas locais inclusivas; inválidas ou invertidas → 422 com o filtro preservado), `cadastro` ∈ {ativo (padrão: rascunho + registrado), rascunho, registrado, cancelado, todos}, `ordem` ∈ {ocorrencia, codigo, atualizado}, `pagina`. |
| GET | `/multas/{id}` | conta | Ficha: ocorrência, origem (snapshot gravado + link ao cadastro atual), veículo autuado, documentação na ocorrência e posterior, andamento, histórico paginado (`eventos_pagina`). `voltar` = query da lista, validada e reconstruída (nunca URL). |
| GET | `/multas/novo` · `/multas/{id}/editar` | operador | Formulário (rascunho / retificação). `?radar=<id>` ou `?agente=<id>` só pré-selecionam a origem. Cancelado → 409. |
| POST | `/multas` · `/multas/{id}` · `/multas/{id}/registrar` · `/conferencias` · `/andamento` · `/cancelar` | operador | Ver [Multas](#multas). |
| GET | `/manutencao` | conta | Lista por andamento e urgência e encerradas recentes. |
| GET | `/antecipacao` | conta | "Revisões recomendadas": última análise e retrospecto. `POST /antecipacao/recalcular`: operador. |
| GET | `/planejar` | conta | Planejar reforço: previsão da execução importada mais recente. Query: `metodo` ∈ {baseline, poisson} (padrão: o escolhido no ajuste), `setor` ∈ {NO, NE, SO, SE}, `quadrados` ∈ {1…4} (padrão 4; os quadrados de 2 km só aparecem se a grade foi importada). Sem execução: estado vazio, sem números. |
| GET | `/planejar/retrospecto` | conta | Semana do teste com a previsão congelada. Query: `semana` (AAAA-MM-DD de uma semana do teste), `metodo`, `revelar=1` (só então os registros observados entram no HTML). |
| POST | `/planejar/missoes` · `/planejar/missoes/{id}/concluir` · `/cancelar` | operador | Missão fictícia (ver [Escrita](#planejar-reforço)). |
| GET | `/sobre` | conta | O que é real, simulado e implementado; ajuda administrativa. |
| GET/POST | `/entrar` · POST `/sair` | aberto | Login (sessão regenerada; volta ao `voltar` interno) e logout (sessão destruída; leva ao login). |
| GET/POST | `/contas`, POST `/contas/{id}/situacao` | administrador | Lista, cria (senha exibida uma vez), ativa/desativa. |
| GET | `/saude` | aberto | `{"status":"ok"}` ou 503 — sem versões, caminhos ou dados. |

Códigos: 401 (precisa entrar), 403 (papel insuficiente, CSRF inválido ou `Origin` estranho), 404, 405, 409 (regra ou
conflito), 422 (validação), 503 (banco indisponível), 500 (inesperado). Páginas de erro trazem só mensagem segura e
um **código de referência** que também aparece no log privado.

## Escrita

Campos de texto: espaços nas pontas e caracteres de controle são removidos; vazio = ausente. Campos não listados são
ignorados. Mensagens de validação vêm por campo (`422`, formulário reapresentado com tudo o que foi digitado e um
resumo de erros no topo). Regras de negócio violadas vêm como mensagem única (`409` no formulário; nas ações da ficha,
redirecionamento 303 com aviso).

### Equipamento — `POST /equipamentos` e `POST /equipamentos/{id}`

| Campo | Regra |
|---|---|
| `type_code` | obrigatório; `RADAR`, `CAMERA`, `BALANCA`, `CANCELA`, `TOTEM`, `VEICULO`, `PATINETE`. |
| `status` | só na criação; uma das situações (padrão `NAO_INSTALADO`). Na edição, a situação muda pela ficha. |
| `manufacturer`, `model` | obrigatórios, até 80. |
| `serial_number` | opcional, até 60; único por fabricante (sem diferenciar maiúsculas/espaços). |
| `asset_tag` | opcional, até 30, único. |
| `agency_id` | obrigatório; órgão existente. |
| `street` (≤120), `address_ref` (≤160) | opcionais, mas é preciso **logradouro, referência ou coordenadas**. |
| `cep` | opcional; `00000-000` (aceita sem hífen). |
| `region_id` | opcional; bairro existente. |
| `lat`, `lng` | opcionais, **juntos ou nenhum**; graus decimais (vírgula aceita) dentro da área de Marília; gravados com 6 casas. |
| `location_kind` | obrigatório: `INSTALADO`, `PLANEJADO` ou `BASE`. Recurso móvel (veículo, patinete) só `BASE`; fixo nunca `BASE`; não instalado não pode ser `INSTALADO`. |
| `maint_interval_days` | opcional, 7–730. |
| `installed_on` | opcional, `AAAA-MM-DD`. |
| `notes` | opcional, até 500. |
| `specs[...]` | características do tipo escolhido (tabela abaixo); as de outros tipos são descartadas. |
| `version` | só na edição: versão do cadastro lida ao abrir o formulário. |

Características (`specs`): radar — `functions[]` obrigatório (`VELOCIDADE`, `AVANCO_SEMAFORO`, `PARADA_FAIXA`,
`CONVERSAO_PROIBIDA`, `FAIXA_EXCLUSIVA`), `speed_limit_kmh` 20–120 obrigatório se houver `VELOCIDADE`, `lanes` 1–8,
`direction` ≤80; câmera — `resolution` obrigatório (`720P`, `1080P`, `4MP`, `4K`), `night_vision`, `color_image`, `ptz`
(sim/não), `field_of_view_deg` 30–360; balança — `weighing_mode` obrigatório (`ESTATICA`, `DINAMICA`), `max_load_t`
1–200 obrigatório, `lanes` 1–4; cancela — `access_control` obrigatório (`TAG`, `TICKET`, `REMOTO`, `MANUAL`),
`arm_length_m` 2–8, `anti_crush`; totem — `services[]` obrigatório (`INFORMACAO`, `BOTAO_AJUDA`, `WIFI`, `RECARGA`),
`screen_in` 7–86, `accessible_height`; veículo — `vehicle_role` obrigatório (`FISCALIZACAO`, `TRANSPORTE`, `EQUIPE`),
`call_sign` ≤20 obrigatório (fictício, nunca placa), `seats` 1–60, `fuel` (`FLEX`, `DIESEL`, `ELETRICO`, `HIBRIDO`);
patinete — `range_km` 5–120 obrigatório, `max_speed_kmh` 6–32. Fonte: [`EquipmentCatalog`](../src/Domain/EquipmentCatalog.php).

Respostas: `303` → ficha ("Equipamento cadastrado." / "Alterações salvas."); `422` com erros por campo;
`409` com **conflito de edição** quando a versão do cadastro mudou depois que o formulário foi aberto: a tela mantém
a digitação, mostra "salvo agora" × "sua versão" por campo e passa a carregar a versão atual (reenviar = manter a
própria versão). Posição, sinal e situação **não** causam conflito. Se o equipamento foi arquivado no meio da edição,
redireciona à ficha com aviso.

### Agente — `POST /agentes` e `POST /agentes/{id}`

| Campo | Regra |
|---|---|
| `call_sign` | obrigatório, até 40 (fictício). |
| `role` | obrigatório: `TRANSITO`, `GUARDA`, `POLICIA`. |
| `agency_id` | obrigatório; órgão existente. |
| `status` | só na criação (padrão `FORA_DE_SERVICO`). |
| `base_name` | obrigatório, até 80. |
| `base_lat`, `base_lng` | graus decimais (vírgula aceita) dentro da área de Marília. Na tela, nunca digitados (somente leitura): vêm do mapa. **Sem eles**, o servidor localiza a base pela `base_street` ou, sem ela, pela rua do `base_cep` (só com o resto válido); sem ponto e sem endereço → 422 em `base_lat`; rua não achada ou CEP sem rua → 422 em `base_street`; provedor fora do ar → 422 em `base_lat`, com o que foi digitado mantido. |
| `base_cep` | opcional; `00000-000` (aceita sem hífen). |
| `base_street` | opcional, até 120: a rua que a consulta do CEP preencheu (editável) ou a digitada. |
| `version` | só na edição. |

`base_ref` (número, opcional, até 160) também ajuda a localizar pelo endereço. Com ponto **e** endereço novo ou
alterado, o servidor localiza o endereço e, se o ponto estiver fora da área dele, grava o ponto do endereço (provedor
fora do ar: grava o ponto enviado). `region_id` enviado é ignorado
([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)): o bairro é o de centro mais próximo do ponto (na
edição, só é recalculado se o ponto mudar ou se não houver bairro). Respostas como no equipamento (303, 422, 409).

### Situação, arquivamento e simulação

| Rota | Campos | Regras e efeito |
|---|---|---|
| `…/situacao` | `status`, `note` (1–300, obrigatório) | Só transições permitidas (tabela em [domínio](dominio.md#situações-e-transições)). **Recusado com intervenção em execução** ("a situação será definida ao concluí-la"). Grava histórico com o motivo. |
| `…/arquivar` | `reason` (1–300), `cancelar_agendadas=1` | Recusado com intervenção em execução. Com ordens agendadas, exige `cancelar_agendadas=1`; cada uma é cancelada com o motivo do arquivamento e autoria. Aviso de sucesso lista as ordens canceladas. |
| `…/simular-falha` | — | Evento `FALHA` pelo ingestor. Em situação administrativa ou já em falha, fica só no histórico ("Evento registrado só no histórico…"). |

### Manutenção

| Rota | Campos | Regras e efeito |
|---|---|---|
| `POST /equipamentos/{id}/manutencoes` | `kind` (`CORRETIVA`/`PREVENTIVA`), `priority` (`IMEDIATA`/`PROGRAMADA`), `due_date` (obrigatória se programada; não no passado), `reason` (1–300) | Não muda a situação. Recusado em equipamento arquivado. |
| `…/iniciar` | `voltar` (opcional, caminho interno) | Só ordem `ABERTA`; **recusado se outra ordem do mesmo equipamento estiver em execução**. Equipamento passa a "Em manutenção". |
| `…/concluir` | `resolution` (1–500), `result_status` (`OPERACIONAL`, `FALHA`, `DESATIVADO`) | Só ordem `EM_EXECUCAO`. A situação informada passa a valer (concluir em `FALHA` é aceito: "continua com mau funcionamento"). Preventiva com periodicidade agenda a próxima. |
| `…/cancelar` | `reason` (1–500) | Só ordem `ABERTA`; iniciada precisa ser concluída com o resultado real. |

`voltar` aceita apenas `/manutencao`, `/central` ou `/equipamentos/{id}`, com âncora opcional; qualquer outro valor
volta para `/manutencao`.

### Alertas, análise, contas e login

| Rota | Campos | Efeito |
|---|---|---|
| `POST /alertas/{id}/reconhecer` | `note` (opcional) | Registra ciência ("reconhecido"). Não resolve: o alerta fecha sozinho quando a causa acaba. Já resolvido → aviso (JSON: 409). |
| `POST /antecipacao/recalcular` | — | Nova execução da análise. |
| `POST /entrar` | `username`, `password`, `voltar` | Mensagem única para conta inexistente, senha errada ou conta inativa; 5 falhas por usuário (20 por IP) em 15 min bloqueiam, inclusive com tentativas simultâneas. `voltar` só aceita caminho interno. |
| `POST /contas` | `username` (3–40: letras, números, `.`, `-`, `_`; único), `display_name` (≤80), `role` (`OPERADOR`/`ADMIN`) | Senha gerada exibida uma vez. |
| `POST /contas/{id}/situacao` | `active` (`0`/`1`) | Não desativa a própria conta nem o último administrador ativo. |

### Planejar reforço

| Rota | Campos | Efeito |
|---|---|---|
| `POST /planejar/missoes` | `run_id`, `sector_id`, `method`, `agent_id`, `suggested_agent_id` (o sugerido que a tela mostrou; só decide se a justificativa é exigida), `confirm=1` (obrigatório), `justification` (≤300; obrigatória se o agente não for o sugerido mostrado nem o primeiro elegível do momento), `support_point` (≤160), `note` (≤300) | O servidor recalcula tudo: a execução precisa ser a mais recente e o período não pode ter terminado; o agente é travado e reconferido (trânsito, não arquivado, em atividade, sem missão); estimativa, posição e motivo vêm do banco, nunca do navegador; autor = conta da sessão. Campo inválido → 422 com a tela no passo 3, o erro junto do campo e o que foi digitado. Conflito (outra pessoa reservou o agente) → aviso, nada gravado. Sem comunicação externa. |
| `POST /planejar/missoes/{id}/concluir` · `/cancelar` | `reason` (1–300) | Só missão `CONFIRMADA`; já encerrada → aviso. Libera o agente e grava a transição. |

### Multas

Todo formulário leva `command_uid` (32 hexadecimais emitidos pelo servidor; não é o CSRF) e, nas mudanças de um registro
existente, `version`. Reenvio idêntico (mesma chave, conta e dados) devolve o resultado já gravado; a mesma chave com
outros dados ou outra conta → 409. Versão desatualizada → 409 com a digitação mantida (no formulário de edição, a tabela
"Salvo agora × Sua versão"). Campos como autoria, origem `SEMENTE`, estado ou snapshot enviados no POST são ignorados.
Nada consulta órgão, emite auto, cobra ou protocola.

| Rota | Campos | Efeito |
|---|---|---|
| `POST /multas` · `POST /multas/{id}` | `description` (5–240, sempre); para registrar: `occurred_at` (datetime-local de Marília, não futura), `infraction_kind`, `legal_reference` (≤120), `agency_id`, `external_ait` (≤40) ou `ait_missing_reason` (≤160), `street` (≤120) ou `address_ref` (≤160), `source_kind` (`RADAR` + `equipment_id` do tipo radar, opcional `operator_agent_id`; ou `AGENTE` + `agent_id` de trânsito), velocidades `speed_limit_kmh`/`speed_measured_kmh` (só em excesso de velocidade; a considerada é calculada pelo servidor e qualquer valor enviado é ignorado), `vehicle_plate` (AAA1234 ou AAA1A23), `vehicle_uf`, `vehicle_manufacturer`, `vehicle_model`, `vehicle_species`, `vehicle_renavam` (11 dígitos) ou `renavam_missing_reason`. Opcionais: `infraction_code`, `infraction_detail`, `region_id`, `lat`/`lng`, `notes` (≤1000), `source_note` (≤300; exigido se o órgão autuador diferir do órgão do recurso), `radar_meter_kind`, `radar_inmetro_record`, `radar_certificate`, `radar_verified_on`, `evidence_reference`, `vehicle_color`, `vehicle_year`. Registrado: `reason` (≤300) obrigatório. | Cria rascunho (303 → ficha) ou salva a edição/retificação com antes/depois. Radar enviado com origem Agente (ou agente principal com origem Radar) → 422. Origem Radar sem `vehicle_plate` → 422, inclusive em rascunho. Mudar placa, UF, RENAVAM, marca, modelo, espécie ou data/hora → nova versão do sujeito ("Não verificada" até nova conferência). |
| `POST /multas/{id}/registrar` | `version`, `command_uid` | Confere os campos mínimos e a coerência (velocidade considerada acima da regulamentada; velocidade só por radar), trava e reconfere radar/agente e grava o snapshot da origem. Falta algo → 422 na ficha com links para cada campo. |
| `POST /multas/{id}/conferencias` | `scope` (`OCORRENCIA`/`POSTERIOR`), `effective_on` (posterior: entre a ocorrência e hoje), `checked_at` (não futuro, não antes da data de referência), `document_status`, `source_type`, `source_reference` (≤240; obrigatória se houver resultado), `crlv_exercise`, `pending_items` (≤500; obrigatória para pendência), `review_on`, `check_reason` (≤300; obrigatório para "Não verificada") | Acrescenta a constatação (imutável) e recalcula a situação na ocorrência. |
| `POST /multas/{id}/andamento` | `process_stage`, `payment_status`, `contest_status`, `contest_kind`, `nominal_amount`/`paid_amount` (reais: `1.234,56`), datas de expedição/recebimento/vencimento/prazos, `paid_on`, `payment_reference`, `contest_submitted_on`, `contest_reference`, `contest_decided_on`, `contest_result_note`, `closed_on`, `closure_reference`, `closure_note`, `progress_reason` | Três dimensões independentes; condicionais (pagamento informado, contestação, encerramento) exigem data e referência; datas de fatos não futuras nem antes da ocorrência; recebimento antes da expedição ou resultado antes do protocolo → 422; corrigir pagamento/resultado/encerramento já informados exige `progress_reason`. Sequências incomuns → aviso. |
| `POST /multas/{id}/cancelar` | `reason` (1–300) | Cancelamento interno: o registro fica somente leitura; não é anulação do auto. |

## Consulta de CEP (`GET /api/cep/{cep}`)

`{cep}` com 8 dígitos (com ou sem hífen), validado **antes** de qualquer chamada ao provedor
([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). Toda resposta é JSON com as mesmas chaves —
`cep` (normalizado, `00000-000`), `situacao`, `rua`, `bairro`, `mensagem` (pronta para a tela) — e
`Cache-Control: no-store`. Detalhes do provedor nunca aparecem; o motivo de uma falha vai só para o log privado.

| `situacao` | HTTP | `rua` | Quando |
|---|---|---|---|
| `ENCONTRADO` | 200 | a rua | CEP de Marília/SP com logradouro. |
| `SEM_RUA` | 200 | vazia | CEP geral da cidade (sem logradouro). |
| `OUTRA_CIDADE` | 200 | vazia | CEP existe, mas não é de Marília/SP (a mensagem cita a cidade). |
| `NAO_ENCONTRADO` | 404 | vazia | O provedor diz que o CEP não existe. |
| `INVALIDO` | 422 | vazia | Formato inválido; nenhuma chamada externa. |
| `INDISPONIVEL` | 503 | vazia | Provedor fora do ar, sem rede, tempo esgotado (3 s) ou resposta fora do formato. |

Em tudo que não é `ENCONTRADO`, a tela mostra a mensagem e o campo "Rua" para digitar; o envio do formulário não
depende desta rota.

## Ponto pelo endereço (`GET /api/localizar`)

Query: `rua` (até 120) e/ou `cep` (8 dígitos); `numero` opcional (com dígitos iniciais, tenta a casa antes da rua). Sem nenhum dos dois, ou com algum inválido → 422 `INVALIDO`, sem
chamada externa. Usa a rua informada ou, sem ela, a rua do CEP (ViaCEP); depois o Nominatim, preso ao retângulo de
Marília ([ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). Resposta JSON com `situacao`, `lat`, `lng`
(texto com 6 casas, ou `null`), `rua`, `caixa` (área do endereço `[sul, norte, oeste, leste]`: um ponto já marcado fora
dela é levado ao endereço pela tela) e `mensagem`; `Cache-Control: no-store`.

| `situacao` | HTTP | Quando |
|---|---|---|
| `ENCONTRADO` | 200 | Ponto aproximado da rua (meio da rua). |
| `SEM_RUA` | 200 | Só CEP, e ele não indica rua de Marília (geral, de outra cidade ou inexistente). |
| `NAO_ENCONTRADO` | 404 | A rua não foi achada dentro da área atendida. |
| `INVALIDO` | 422 | Nada informado ou formato inválido. |
| `INDISPONIVEL` | 503 | Provedor fora do ar, sem rede, tempo esgotado (3 s) ou resposta fora do formato. |

## Contrato de `GET /api/central`

Query: `categoria[]`, `situacao[]`, `bairro[]`, `orgao[]`, `q` (até 60 caracteres; sem diferenciar maiúsculas e
acentos; procura em código, nome, tipo, endereço, bairro, órgão, marca/modelo) e `atencao=1`. Todos os blocos da
resposta vêm do **mesmo** conjunto filtrado.

```jsonc
{
  "generated_at": "2026-10-18T18:06:13-03:00",   // instante do instantâneo (America/Sao_Paulo)
  "filters": {"categoria": [], "situacao": [], "bairro": [], "orgao": [], "q": "", "atencao": ""},   // filtros efetivamente aplicados
  "scope": {"equipment": true, "agents": true},
  "kpis": {"equipment": {"total": 48, "operational": 38, "in_service": 43, "availability": 0.8837, "availability_confirmed": 0.8372, …},
           "agents": {"total": 21, "active": 14, "position_not_current": 1, …}},
  "needs_attention": 11,          // recursos com algum motivo de atenção
  "without_location": 1,          // recursos sem coordenadas (só na lista)
  "resources": [{"kind": "EQUIPAMENTO", "id": 7, "code": "RAD-0007", "category": "RADAR", "category_label": "Radar de fiscalização",
                 "type_short": "Radar", "headline": "Radar · Avenida das Esmeraldas, Antes da rotatória", "place": "Avenida das Esmeraldas, Antes da rotatória",
                 "title": "RAD-0007", "status": "OPERACIONAL", "status_label": "Operacional", "freshness": "ATUAL", "last_signal_at": "…",
                 "position": {"lat": -22.2287, "lng": -49.9195, "kind": "INSTALADO", "observed_at": null, "freshness": null},
                 "maintenance": {"urgency": "PROXIMA", "label": "Próxima", "due_date": "2026-10-23", "state": "ABERTA", …} | null,
                 "alerts": 0, "attention": [],   // motivos em texto, ex.: ["Mau funcionamento", "Sem sinal recente (não confirma falha)"]
                 "highlights": [{"label": "…", "value": "…"}], …}],
  "regions": [{"name": "Fragata", "equipment": 9, "unavailable": 2, "availability": 0.7778, "agents_active": 3, "alerts": 2, …}],
  "maintenance": {"urgent": […], "upcoming": […]},
  "alerts": [{"id": 3, "kind": "EQUIPAMENTO", "resource_id": 7, "severity": "ALTA", "state": "ABERTO", "message": "…", …}],
  "sync": {"last_event_id": 5198, "last_event_received_at": "…", "simulator_last_tick_at": "…", "simulator_running": true,
           "duplicates_ignored": 4, "late_events": 3}
}
```

`position.kind`: `INSTALADO`, `PLANEJADO`, `BASE` ou `ULTIMA_POSICAO` (com `observed_at` e `freshness`). Recurso sem
coordenadas vem com `lat: null` e aparece só na lista. `headline` nunca inventa endereço: sem logradouro/referência,
usa o bairro ou "sem endereço cadastrado". Volume de demonstração: dezenas de recursos, sem paginação oculta. Texto do
banco é escapado (`JSON_HEX_TAG`/`JSON_HEX_AMP`) e o cliente o insere com `textContent`.

### Atualidade

| Informação | Atual | Atrasada | Depois | Não se aplica |
|---|---|---|---|---|
| Sinal de equipamento (`freshness`) | ≤ 10 min | ≤ 60 min | `SEM_INFORMACAO` | não instalado, manutenção, desativado, furtado |
| Posição de recurso móvel ou agente (`position.freshness`) | ≤ 5 min | ≤ 30 min | `SEM_INFORMACAO` | agente fora de turno (indisponível, fora de serviço) |

`SEM_INFORMACAO` é ausência de informação, não falha confirmada; gera alerta de comunicação/posição, mostrado como tal.
