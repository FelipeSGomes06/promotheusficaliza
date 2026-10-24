# Domínio, modelo de dados e indicadores

Esquema: [`0001_esquema_inicial.sql`](../database/migrations/0001_esquema_inicial.sql) e
[`0002_invariantes_e_cota_de_login.sql`](../database/migrations/0002_invariantes_e_cota_de_login.sql)
(Firebird 5, dialeto 3, UTF8). Integridade relevante fica no banco (PK, FK, UNIQUE, CHECK, índices únicos por
expressão), não só na interface.

## Tabelas

| Tabela | Conteúdo | Restrições importantes |
|---|---|---|
| `AGENCY` | Órgãos fictícios (trânsito, guarda, polícia, manutenção, transporte). | `CODE` único; `KIND` em lista fechada. |
| `REGION` | Bairro de referência, zona de agrupamento (não oficial) e centro aproximado. | `NAME` único. |
| `EQUIPMENT` | Equipamento: código interno, patrimônio, tipo, fabricante, modelo, série, órgão, situação (+ instante), endereço, coordenadas, natureza do local, última posição (móveis), último sinal, periodicidade de preventiva, `SPECS` (JSON validado), observações, instalação, arquivamento, versão. | `CODE` e `ASSET_TAG` únicos; **índice único `UPPER(TRIM(fabricante))‖UPPER(TRIM(série))`** (série nula não colide); situação, tipo e natureza do local em listas fechadas; cidade/UF fixas (`Marília`/`SP`); CEP `00000-000`; coordenadas em faixa e em par; periodicidade 7–730. |
| `AGENT` | Agente fictício: código, indicativo, função, órgão, situação, base (endereço + coordenadas obrigatórias), última posição observada/recebida, arquivamento, versão. | `CODE` único; função e situação em listas fechadas; base obrigatória. |
| `MAINTENANCE_ORDER` | Ordem corretiva/preventiva, prioridade imediata/programada, estado, data local, motivo, resolução, situação resultante, origem, autoria e instantes. | Programada exige data; estado encerrado ⇔ `CLOSED_AT`; resultado em lista fechada; **no máximo uma `EM_EXECUCAO` por equipamento** (índice único parcial `UX_MAINT_ONE_RUNNING`). |
| `RESOURCE_EVENT` | Histórico append-only: uid, recurso, tipo, origem, autor, ocorrido, recebido, resultado (`APLICADO`/`HISTORICO`), resumo e antes/depois (JSON). | `EVENT_UID` único (duplicatas); o usuário da aplicação só tem `SELECT, INSERT`. |
| `ALERT` | Alerta por recurso e motivo, gravidade, estado, reconhecimento e resolução. | `OPEN_KEY` único e nulo quando resolvido ⇒ um alerta aberto por causa, mesmo sob concorrência. |
| `ALERT_FOLLOWUP` | Acompanhamento do alerta: providências (resultado, relato, próxima conferência) e atribuições (de/para conta), com autor e instante. | Aplicação só lê e insere; responsável atual, próxima conferência e versão (`FOLLOWUP_NO`) ficam em `ALERT`; `RESOLVED_REASON` diz por que encerrou ([ADR-0022](adr/0022-acompanhamento-dos-alertas-de-agentes.md)). |
| `ANALYSIS_RUN`, `ANALYSIS_RESULT` | Execuções da análise (método, parâmetros, período, avaliação) e resultado por equipamento. | Resultado único por execução/equipamento. |
| `APP_USER`, `LOGIN_ATTEMPT` | Contas (operador/admin) e tentativas de login guardadas por hash: cada verificação reserva uma vaga numerada (`SLOT_NO`) antes do hashing; login correto acrescenta uma linha de quitação (`SETTLES_ID`). | Usuário único sem diferenciar maiúsculas; vaga única por chave (índice parcial); a aplicação só lê e acrescenta tentativas. |
| `URBAN_RUN` | Execução importada do treino por setores: período `[AS_OF, UNTIL_DATE)`, referência dos dados, método escolhido, ganho do candidato, emissão do modelo congelado, aviso, avaliação/modelo/proveniência (JSON original) e SHA-256 dos arquivos. | `BUNDLE_SHA256` único (reimportação idempotente); período de 7 dias; aplicação só lê e insere. |
| `URBAN_SECTOR`, `URBAN_FORECAST`, `URBAN_REPLAY` | Retângulos dos setores experimentais; previsão por setor (ordem importada, duas estimativas, evidências de 13/52 semanas); retrospecto do teste por semana/setor com o observado. | Estimativas `DECFLOAT(34)` não negativas (o texto do double do JSON volta idêntico); posição única por execução; semana de 7 dias com histórico anterior; aplicação só lê e insere. Dados **reais agregados** (Infosiga-SP). |
| `URBAN_CELL` | Quadrados de 2 km da execução (8 × 8): setor, retângulo, registros em 13 e 52 semanas, estimativa (média simples) e posição. | Um por quadrado e execução; posição única; soma por setor conferida na importação; aplicação só lê e insere. Dados **reais agregados**. |
| `URBAN_SNAPSHOT`, `URBAN_DAILY` | Cada arquivo mensal do Infosiga baixado (SHA-256, origem, data, período guardado, contagem de filtros) e as contagens de registros elegíveis por dia e quadrado dos últimos 12 meses. | SHA-256 único; contagem positiva; quadrado 0–63; aplicação só lê e insere. Dados **reais agregados**; o arquivo bruto não é guardado. |
| `URBAN_MISSION`, `URBAN_MISSION_LOG` | Missão **fictícia**: setor, quadrado de 2 km (opcional), período, método e estimativa congelados, agente, sugerido/manual (+ justificativa), ponto de apoio, observação, estado `CONFIRMADA`/`CONCLUIDA`/`CANCELADA`, autoria; histórico das transições. | **No máximo uma `CONFIRMADA` por agente** (índice único parcial `UX_URBAN_MISSION_ACTIVE`); encerrada ⇔ `CLOSED_AT` + motivo; histórico só leitura e inserção. Fora da tabela `AGENT` (o simulador não a toca). |
| `FINE` | Multa (registro **demonstrativo**, [ADR-0017](adr/0017-aba-multas.md)): estado `RASCUNHO`/`REGISTRADO`/`CANCELADO`, ocorrência (data UTC, tipo, enquadramento transcrito, órgão autuador, AIT externo + chave normalizada, local), origem (`RADAR` com `EQUIPMENT_ID` e operador opcional em `AGENT_ID`, ou `AGENTE`), snapshot da origem gravado no registro (JSON), velocidades transcritas, dados do medidor (JSON de chaves fechadas), veículo autuado (placa, UF, marca, modelo, espécie, cor, ano, RENAVAM texto), andamento (etapa, pagamento, contestação, datas, valores em centavos), projeção documental na ocorrência, `ATTENTION_COUNT`, `VERSION_NO`, `SUBJECT_VERSION`, autoria e cancelamento. | AIT único por órgão (índice parcial `UX_FINE_AIT`, nulos não colidem); `CHECK` de campos mínimos e origem coerente quando registrado, velocidade considerada > regulamentada e ≤ medida, placa e RENAVAM no formato, UF, valores 0–1.000.000,00, pagamento/contestação/encerramento com data e referência, cancelado com motivo e origem (conta obrigatória salvo `SEMENTE`). Sem `ON DELETE CASCADE`. |
| `FINE_DOCUMENT_CHECK` | Conferência documental: escopo (ocorrência/posterior), data de referência, instante da conferência, resultado (`NAO_VERIFICADA`, `REGULARIDADE_INFORMADA`, `PENDENCIA_INFORMADA`), fonte da evidência, referência, exercício do CRLV, pendência, revisão, motivo, versão do sujeito, origem e autoria. | Resultado exige fonte e referência; pendência exige descrição; "Não verificada" exige motivo; aplicação só lê e insere. |
| `FINE_EVENT` | Histórico das multas: chave do comando (`EVENT_UID`), impressão digital (`REQUEST_HASH`), tipo, origem, autor, instante, resumo, motivo, antes/depois (JSON). | `EVENT_UID` único (repetição idempotente); autor obrigatório salvo `SEMENTE`; aplicação só lê e insere. Fora de `RESOURCE_EVENT`, alertas e central. |
| `APP_META` | Marcador do conjunto sintético, ciclo do simulador, contadores de duplicatas/atrasos, versão da carga de multas (`fines_seed`). | — |
| `SCHEMA_MIGRATION` | Migrações aplicadas (inacessível ao usuário da aplicação). | — |

**Versão do cadastro.** `VERSION_NO` de equipamentos e agentes protege o formulário contra duas edições simultâneas e
só avança quando muda um campo do cadastro (ou com o arquivamento). Posição, sinal e situação não a alteram; a
instalação (local planejado → instalado) altera ([ADR-0010](adr/0010-versao-do-cadastro-separada-da-telemetria.md)).

**Convenções.** Instantes `*_AT` em UTC (`TIMESTAMP`), exibidos em America/Sao_Paulo. `DUE_DATE` e `INSTALLED_ON`
são datas locais de Marília. Coordenadas WGS84 `DECIMAL(9,6)`, ordem `(lat, lng)` em todas as fronteiras (o JSON
usa objetos `{lat, lng}`, sem GeoJSON).

## Características por tipo (`SPECS`)

JSON validado no servidor contra o esquema de [`EquipmentCatalog`](../src/Domain/EquipmentCatalog.php) — não é campo
livre. Campos desconhecidos são descartados. Exemplos: radar (`functions` múltipla, `speed_limit_kmh` 20–120
obrigatório **se** fiscaliza velocidade, `lanes`, `direction`); câmera (`resolution`, `night_vision`, `color_image`,
`ptz`, `field_of_view_deg`); balança (`weighing_mode`, `max_load_t`); cancela; totem; veículo (prefixo fictício, nunca
placa real); patinete. **Novo tipo:** acrescentar no catálogo, ampliar `CK_EQUIPMENT_TYPE` numa migração nova e
desenhar ícone (`icons.svg#i-<TIPO>`) e miniatura (`miniaturas/<tipo>.svg`). Escolha registrada no
[ADR-0003](adr/0003-caracteristicas-por-tipo-em-json-validado.md).

## Situações e transições

| De \ Para | Operacional | Falha | Manutenção | Desativado | Furtado | Não instalado |
|---|---|---|---|---|---|---|
| Não instalado | ✓ (instalação) | | | ✓ | | |
| Operacional | | ✓ | ✓ | ✓ | ✓ | |
| Falha | ✓ | | ✓ | ✓ | ✓ | |
| Manutenção | ✓ | ✓ | | ✓ | | |
| Desativado | ✓ | | | | | ✓ |
| Furtado | | | | ✓ | | ✓ (reposição) |

- Instalar move o local "planejado" para "instalado" e registra a data. Voltar para "Não instalado" (reposição de
  furtado ou desativado) faz o inverso: o ponto continua, como local planejado. Equipamento fixo em serviço
  (operacional, falha, manutenção, furtado) só aceita local instalado; não instalado, só planejado; desativado, os dois.
- `INSTALLED_ON` é o início de operação no ponto: instalação, para fixo; entrada em operação, para veículo e patinete
  (registrada ao passar de "Não instalado" para "Operacional", se ainda não houver data).
- **Telemetria** só alterna Operacional ↔ Falha. Manutenção, desativado, furtado e não instalado são
  **administrativos**: eventos de telemetria contra eles ficam só no histórico.
- Agendar manutenção não muda a situação; **iniciar** a intervenção põe em manutenção; **concluir** exige informar a
  situação resultante (operacional, ainda em falha ou desativado). Cancelar só ordens não iniciadas.
- **Uma intervenção em execução por equipamento.** Outras ordens podem ficar agendadas, mas só começam depois da
  conclusão da atual. Enquanto houver intervenção em execução, a situação não muda à mão: muda na conclusão
  ([ADR-0009](adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)).
- Concluir preventiva de equipamento com periodicidade agenda a próxima (`hoje local + intervalo`, origem SISTEMA).
- Arquivar retira da operação (visão geral, alertas, novas ordens) e preserva histórico, ordens e alertas. É recusado
  com intervenção em execução; ordens agendadas só são canceladas com confirmação explícita, cada uma com o motivo do
  arquivamento e autoria. Não há desarquivamento pela interface.

## Eventos

| Situação | Resultado |
|---|---|
| Mesmo `EVENT_UID` de novo | DUPLICADO: nada muda, contador `duplicates_ignored` +1. Corrida entre conexões: o índice único decide e a perdedora desfaz. |
| Ocorreu antes de `STATUS_CHANGED_AT` | HISTORICO ("evento atrasado"), contador `late_events` +1, estado atual preservado. |
| Situação administrativa vigente | HISTORICO ("estado administrativo prevalece"). |
| Posição mais antiga que a atual | HISTORICO; a posição atual não regride. |
| Sinal (heartbeat) | Atualiza só `LAST_SEEN_AT`, sem linha de histórico (controle de volume). Posições entram no histórico a cada 6 ciclos. |

Silêncio do dispositivo não vira "falha": vira atualidade "sem informação" e um alerta de comunicação.

Agente sem posição há mais de 30 min **em turno** (atividade ou pausa) abre `SEM_POSICAO`; entrar em pausa não encerra
o alerta. Ele encerra com posição recente (`POSICAO_RECEBIDA`) ou quando deixa de se aplicar (`FORA_DE_TURNO`,
`ARQUIVADO`). Ciência e providências não encerram a causa.

**Histórico na ficha.** Itens com linha tracejada são registros que **não alteraram** a situação atual (`OUTCOME =
HISTORICO`); o motivo aparece no item: evento atrasado (estado atual mais recente), situação definida por pessoa
(manutenção, desativado, furtado, não instalado), equipamento arquivado ou nenhuma mudança (já estava naquela
situação); para posições, posição mais antiga que a atual.

**Atualidade.** Sinal de equipamento: atual até 10 min, atrasado até 60 min, depois "sem informação recente". Posição
de agente/recurso móvel: atual até 5 min, atrasada até 30 min, depois "sem informação recente". Só se aplica a quem
deveria comunicar (equipamento operacional ou em falha; agente em turno).

## Indicadores da central

Calculados em [`Indicators`](../src/Domain/Indicators.php) sobre o conjunto filtrado (o mesmo do mapa e da lista):

| Indicador | Fórmula |
|---|---|
| Equipamentos | não arquivados no filtro (inclui não instalados e desativados, exibidos à parte). |
| Operacionais | situação operacional. "Sem sinal recente": operacionais com sinal há mais de 10 min. |
| Indisponíveis | falha + manutenção + furtado. |
| Em serviço (denominador) | operacionais + falha + manutenção + furtado. |
| Disponibilidade registrada | operacionais ÷ em serviço. |
| Disponibilidade confirmada | operacionais com sinal atual ÷ em serviço. |
| Sem denominador | "não se aplica" (nunca 0% ou 100%). |
| Agentes ativos | agentes não arquivados "em atividade" no filtro (unidade própria). |
| Manutenção urgente | ordens agendadas (ainda não iniciadas) imediatas ou vencidas — o mesmo conjunto da seção Urgentes da Manutenção; próximas: agendadas de hoje até 7 dias. A já iniciada conta em "Em andamento" e o equipamento, em manutenção, entra em "precisam de atenção". |
| Precisam de atenção | recursos em mau funcionamento, manutenção ou furto, agente indisponível, manutenção imediata/vencida ou com alerta aberto (inclusive "sem sinal/posição recente", que não confirma falha). Cada recurso traz os motivos em texto. |

**Escopo dos filtros.** Equipamentos entram se o filtro de tipo (quando houver) tiver algum tipo de equipamento e o
de situação (quando houver) alguma situação de equipamento; o mesmo vale para agentes. Ex.: filtrar "Radar" exclui
agentes, e "Agentes ativos" mostra 0 com aviso. Bairro do agente = bairro da base, derivado do ponto marcado (centro de bairro mais próximo;
[ADR-0021](adr/0021-base-do-agente-pelo-mapa-e-rua-pelo-cep.md)). Disponibilidade **histórica** não é
oferecida: exigiria durações de estado com tratamento de lacunas, fora do escopo.

## Dados de demonstração

[`DemoScenario`](../src/Demo/DemoScenario.php): 5 órgãos, 22 bairros e dois perfis
([ADR-0013](adr/0013-perfil-compacto-de-demonstracao.md)). **Compacto (padrão): 17 equipamentos e 7 agentes** — todas
as categorias e situações; manutenção em execução, imediata, vencida, próxima e futura (as três últimas com data fixa
relativa à carga); sinal atual, atrasado (concentrador com 25 min de defasagem) e sem informação; recurso móvel furtado
com posição antiga; evento atrasado permanente; câmera com histórico curto; agente em turno sem posição recente;
radares com modalidade (lombadas e demais fixos; um portátil).
**Completo: 48 e 21**, para volume e para a metodologia da análise. `DEMO_PROFILE` ou `demo:seed --perfil=…` escolhem. [`DemoSeeder`](../src/Demo/DemoSeeder.php)
gera 240 dias de histórico com semente fixa (`SIM_SEED`): mesmo cenário relativo à data da carga.

**Multas** ([`FineScenario`](../src/Demo/FineScenario.php)): 12 casos no perfil compacto e 40 no completo, gravados pelos
casos de uso (origem `SEMENTE`): radar de velocidade completo; radar portátil com agente operador, num trecho de
60 km/h, e dados metrológicos a conferir; avanço de sinal sem velocidades; agente com placa antiga e RENAVAM com zeros; RENAVAM ausente justificado;
pendência documental (com revisão vencida); pendência na ocorrência e regularidade posterior; penalidade + pagamento
informado + recurso em análise; rascunho; cancelamento interno; radar hoje em falha com o retrato da ocorrência; e
retificação da placa com a conferência antiga só no histórico. Placas, RENAVAM, AIT e marcas são fictícios.
