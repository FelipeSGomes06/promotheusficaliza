# Rotas HTTP, permissões e contratos

Todas as rotas ficam sob `APP_BASE_PATH` (padrão `/promotheusfiscaliza`). Redirecionamentos, formulários, `fetch`,
assets e cookie de sessão usam o prefixo. Escrita = `POST` com token CSRF (`_csrf` no corpo ou `X-CSRF-Token`) e,
se presente, `Origin` igual a `APP_ORIGIN` (ou à origem da requisição).

| Método | Caminho | Acesso | Resposta |
|---|---|---|---|
| GET | `/` | público | Visão geral (HTML renderizado no servidor + JSON inicial embutido). Query: os mesmos filtros da API, mais `visao=lista` e `selecionar=EQUIPAMENTO:<id>` ou `AGENTE:<id>` (abre a ficha resumida). |
| GET | `/api/central` | público | Instantâneo JSON (ver abaixo). `Cache-Control: no-store`. |
| GET | `/equipamentos` | público | Lista paginada (20). Query: `q`, `categoria[]`, `situacao[]`, `bairro[]`, `orgao[]`, `ordem` ∈ {codigo, tipo, situacao, bairro, atualizado}, `arquivados=1`, `pagina`. |
| GET | `/equipamentos/{id}` | público | Ficha: o que é, onde está, situação e atualidade, próxima ação, manutenção, alertas, revisão recomendada, histórico. |
| GET | `/equipamentos/novo` · `/equipamentos/{id}/editar` | operador | Formulários. Visitante → `302 /entrar?voltar=…`. Arquivado → 409. |
| POST | `/equipamentos` · `/equipamentos/{id}` | operador | Cria / altera. Ver [Escrita](#escrita). |
| POST | `/equipamentos/{id}/situacao` | operador | Mudança manual de situação. |
| POST | `/equipamentos/{id}/arquivar` | operador | Arquivamento. |
| POST | `/equipamentos/{id}/simular-falha` | operador | Evento fictício de telemetria pelo caminho do simulador (demonstração). |
| POST | `/equipamentos/{id}/manutencoes` | operador | Abre ordem. |
| POST | `/manutencoes/{id}/iniciar` · `/concluir` · `/cancelar` | operador | Ciclo da ordem. |
| POST | `/alertas/{id}/reconhecer` | operador | Registra ciência. Com `Accept: application/json` responde JSON. |
| GET | `/agentes`, `/agentes/{id}` | público | Lista e ficha. Query da lista: `q`, `categoria[]` (`AG_TRANSITO`…), `situacao[]`, `orgao[]`, `ordem` ∈ {codigo, nome, funcao, situacao, bairro}, `arquivados=1`, `pagina`. |
| GET/POST | `/agentes/novo`, `/agentes`, `/agentes/{id}/editar`, `/agentes/{id}`, `/agentes/{id}/situacao`, `/agentes/{id}/arquivar` | operador | Como nos equipamentos. |
| GET | `/manutencao` | público | Lista por andamento e urgência e encerradas recentes. |
| GET | `/antecipacao` | público | "Revisões recomendadas": última análise e retrospecto. `POST /antecipacao/recalcular`: operador. |
| GET | `/sobre` | público | O que é real, simulado e implementado; ajuda administrativa. |
| GET/POST | `/entrar` · POST `/sair` | público | Login (sessão regenerada) e logout (sessão destruída). |
| GET/POST | `/contas`, POST `/contas/{id}/situacao` | administrador | Lista, cria (senha exibida uma vez), ativa/desativa. |
| GET | `/saude` | público | `{"status":"ok"}` ou 503 — sem versões, caminhos ou dados. |

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
