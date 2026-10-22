# Evidência — Multas: menos campos à vista nos três formulários (22/10/2026)

Pedido do autor: "revisar campos demais na multa". Quem cadastra uma multa enfrentava três formulários longos: o
cadastro (`templates/fines/form.twig`), a conferência dos documentos e a situação da multa (os dois em
`templates/fines/show.twig`). Este documento traz o inventário feito **antes** de editar, campo a campo, com quem
exige cada um no código, e depois o que mudou e como foi verificado.

Regra seguida: nenhum campo que o domínio ou o banco exigem saiu; esconder é só da vista. A validação do servidor
não mudou, nenhuma coluna saiu e não houve migração. Sem JavaScript, todos os campos continuam na página (os
recolhidos abrem com um clique). Com JavaScript, um bloco escondido **continua sendo enviado** e reaparece sozinho
quando tem algo digitado ou um erro: nada gravado fica fora da vista e nada é apagado por estar escondido.

Legenda das decisões: **manter** · **só quando necessário** (divulgação progressiva) · **preencher sozinho** ·
**juntar** · **retirar da tela**.

## Inventário

### 1. Cadastro e edição (`form.twig`)

"Exige" cita o código: `missing` = `FineRules::missingForRegistration` (para registrar); `CK` = restrição de
`database/migrations/0007_multas.sql`; `validate` = `FineService::validate` (vale desde o rascunho).

| Campo (`name`) | Quem exige | Decisão | Motivo |
|---|---|---|---|
| Descrição curta (`description`) | `validate` (sempre, 5–240) · `DESCRIPTION NOT NULL` | **preencher sozinho** | Com JavaScript, vem sugerida a partir do tipo + logradouro ("Excesso de velocidade na Avenida …") e continua editável; foi para depois de "Onde", quando tipo e local já foram escolhidos. Não pode sair: identifica a multa na lista e no histórico. |
| Tipo de infração (`infraction_kind`) | `missing` · `CK_FINE_REGISTERED` | manter | Mínimo para registrar; também decide o bloco de velocidades. |
| Data e hora da ocorrência (`occurred_at`) | `missing` · `CK_FINE_REGISTERED` · `FineRules::IDENTITY` | manter | Mínimo para registrar; base da conferência. |
| Enquadramento informado (`legal_reference`) | `missing` · `CK_FINE_REGISTERED` | manter | Mínimo para registrar. |
| Código (`infraction_code`) | ninguém (opcional, só exibido na ficha) | **só quando necessário** | Recolhido junto com o desdobramento em "Código da infração no documento (opcional)"; abre sozinho se já tiver valor ou erro. |
| Desdobramento (`infraction_detail`) | ninguém | **só quando necessário** (com o código) | Ver "Deixado para o autor": juntar os dois num campo só mudaria o que é gravado. |
| Órgão autuador (`agency_id`) | `missing` · `CK_FINE_REGISTERED` | manter | Mínimo para registrar. |
| Número do AIT (`external_ait`) | `missing` (AIT **ou** motivo) · `CK_FINE_REGISTERED` | manter | Mínimo para registrar (ou o motivo). |
| Se não há AIT: por quê (`ait_missing_reason`) | `missing` (alternativa ao AIT) · `CK_FINE_REGISTERED` | **só quando necessário** | Recolhido em "Não tem o número do AIT?", logo abaixo do AIT; com JavaScript some quando o AIT é digitado (o servidor já descarta o motivo quando há AIT). Abre sozinho com erro no AIT ou se já tiver valor. |
| Logradouro (`street`) | `missing` (logradouro **ou** referência) · `CK_FINE_REGISTERED` | manter | Mínimo para registrar. Busca por CEP fica para a T05. |
| Número ou referência (`address_ref`) | idem | manter | Idem. |
| Bairro (`region_id`) | ninguém | manter | Opcional e curto; a T05/T06 decidem o local e as listas de escolha. |
| Mapa (ponto ilustrativo) | ninguém | manter | Fica para a T05 (CEP/mapa). |
| Latitude, Longitude (`lat`, `lng`) | `CK_FINE_COORDS` (as duas ou nenhuma) | manter (já recolhidas) | Já estavam em "Opções avançadas"; T05. |
| Relato factual (`notes`) | ninguém | manter | Único lugar do que foi observado; aparece em destaque na ficha. |
| Origem principal (`source_kind`) | `missing` · `CK_FINE_SOURCE_IDS` | manter | Mínimo para registrar; decide os blocos. |
| Radar do inventário (`equipment_id`) | `missing` com Radar · `CK_FINE_REGISTERED` | manter (já condicional) | Só aparece com origem Radar desde a ADR-0017. |
| Agente operador associado (`operator_agent_id`) | ninguém (opcional) | **só quando necessário** | Recolhido em "Medidor portátil operado por um agente?" dentro do bloco do radar; abre sozinho se já tiver valor ou erro. |
| Velocidade regulamentada e medida (`speed_limit_kmh`, `speed_measured_kmh`) | `missing` com Velocidade · `CK_FINE_REGISTERED_SPEED` | manter (já condicional) | Só aparecem com Radar + Excesso de velocidade. A "considerada" é calculada, não é campo. |
| Tipo de medidor, Registro no Inmetro, Certificado, Última verificação, Referência da evidência (`radar_*`, `evidence_reference`) | ninguém para registrar; `FineRules::attention` marca "A conferir" sem eles (Velocidade + Radar) | manter (já recolhidos) | Já ficavam recolhidos em "Dados do medidor e evidência" no bloco do radar. |
| Agente da constatação (`agent_id`) | `missing` com Agente · `CK_FINE_REGISTERED` | manter (já condicional) | Só aparece com origem Agente. |
| Observação sobre a origem (`source_note`) | `FineService::divergence` ao registrar/retificar, só quando o órgão autuador difere do órgão do radar/agente | **só quando necessário** | Com JavaScript, aparece quando os órgãos diferem (a mesma comparação do servidor, com o órgão de cada radar/agente na opção) ou se já tiver valor ou erro. |
| Placa (`vehicle_plate`) | `missing` · `validate` com Radar (desde o rascunho) · `CK_FINE_PLATE` | manter | Mínimo para registrar. |
| UF, Marca, Modelo, Espécie (`vehicle_uf`, `vehicle_manufacturer`, `vehicle_model`, `vehicle_species`) | `missing` · `CK_FINE_REGISTERED` · `IDENTITY` | manter | Mínimos para registrar e identidade da conferência. |
| Cor, Ano (`vehicle_color`, `vehicle_year`) | ninguém (opcionais, só exibidos) | **só quando necessário** | Recolhidos em "Cor e ano (opcional)"; abrem sozinhos se já tiverem valor ou erro. |
| RENAVAM (`vehicle_renavam`) | `missing` (RENAVAM **ou** motivo) · `CK_FINE_RENAVAM` | manter | Mínimo para registrar (ou o motivo). |
| Se não há RENAVAM: por quê (`renavam_missing_reason`) | `missing` (alternativa) | **só quando necessário** | Como o do AIT: "Não tem o RENAVAM?", some com o RENAVAM digitado. |
| Passo 4 "Documentação na ocorrência" (só texto) | — | **retirar da tela** | A frase útil (a conferência é feita na ficha; mudar placa, UF, RENAVAM, marca, modelo, espécie ou data/hora pede uma nova; a situação atual, ao editar) foi para a dica do passo 3, "Veículo autuado". |
| Passo 5 "Revisão" (quase só texto) | — | **retirar da tela** | O aviso "salvar não emite autuação oficial" e "a ficha mostra o que falta" ficaram numa linha logo acima do botão de salvar. |
| Motivo da retificação (`reason`, só em registro) | `FineService::update` (registrado) | manter | Continua num cartão próprio, só na retificação. |

### 2. Conferência dos documentos (`show.twig`, "Anotar conferência dos documentos")

Exigências em `FineService::addCheck`; `FINE_DOCUMENT_CHECK` tem `SCOPE`, `CHECKED_AT`, `DOCUMENT_STATUS` e
`SOURCE_TYPE` `NOT NULL`.

| Campo | Quem exige | Decisão | Motivo |
|---|---|---|---|
| A conferência se refere a (`scope`) | `addCheck` (obrigatório) · `NOT NULL` | manter | Pergunta curta, já vem em "No dia da multa". |
| Data a que se refere (`effective_on`) | `addCheck` com "Depois da multa" | manter (já condicional) | Já só aparecia com "Depois da multa". |
| Resultado (`document_status`) | `addCheck` · `NOT NULL` | manter | É a pergunta principal; os outros campos dependem dela. |
| Conferido em (`checked_at`) | `addCheck` (obrigatório, não pode estar no futuro) · `NOT NULL` | **preencher sozinho** | Já vinha com "agora"; agora fica recolhido numa linha "Conferido em 22/10/2026, às 13:44 · mudar", que abre sozinha com erro. |
| Fonte (`source_type`) | `addCheck`: diferente de "Não informado" com "Em dia" ou "Com pendência" | **só quando necessário** | Aparece com "Em dia" ou "Com pendência" (ou se a referência já tiver valor ou houver erro). |
| Referência da fonte (`source_reference`) | `addCheck`: com "Em dia" ou "Com pendência" | **só quando necessário** | Junto com a fonte. |
| Exercício do CRLV (`crlv_exercise`) | ninguém (opcional) | **só quando necessário** | Só com fonte "O motorista mostrou o documento" ou "Consultado no site oficial". |
| O que estava errado (`pending_items`) | `addCheck` com "Com pendência"; descartado nos outros resultados | **só quando necessário** | Só com "Com pendência". |
| Motivo / observação (`check_reason`) | `addCheck` com "Não conferidos" | **só quando necessário** | Só com "Não conferidos" (ou se já tiver valor). Ver "Deixado para o autor". |
| Quer ser lembrado de conferir de novo? (`review_choice`, `review_on`) | `FineDocumentStatus::reviewOn` | manter (T03) | Já só aparece depois do resultado; não foi mexido. |

### 3. Situação da multa (`show.twig`, "Atualizar situação da multa")

Exigências em `FineService::updateProgress` e nos `CHECK` de `FINE` indicados. Datas e valores opcionais nunca são
calculados (ADR-0017).

| Campo | Quem exige | Decisão | Motivo |
|---|---|---|---|
| Fase (`process_stage`) | `updateProgress` · `CK_FINE_STAGE` | manter | Uma das três perguntas que abrem o resto. |
| Valor da multa (`nominal_amount`) | ninguém | **só quando necessário** | Com fase diferente de "não informada" ou pagamento "A pagar"/"Paga". |
| Aviso de infração enviado/recebido em, Prazo para defesa | ninguém (avisos em `FineRules::progressWarnings`) | **só quando necessário** | Com fase "Aviso de infração enviado", "Multa aplicada" ou "Encerrada". |
| Aviso da multa enviado/recebido em, Prazo para recurso | ninguém | **só quando necessário** | Com fase "Multa aplicada" ou "Encerrada". |
| Encerrada em, Documento do encerramento, Como terminou | `updateProgress` com "Encerrada" · `CK_FINE_CLOSED` | **só quando necessário** | Só com fase "Encerrada"; foram para junto da fase. |
| Pagamento (`payment_status`) | `updateProgress` · `CK_FINE_PAYMENT` | manter | Pergunta principal do pagamento. |
| Vencimento (`payment_due_on`) | ninguém | **só quando necessário** | Com pagamento "A pagar" ou "Paga". |
| Pago em, Valor pago, Comprovante | `updateProgress` com "Paga" · `CK_FINE_PAID` | **só quando necessário** | Só com "Paga". |
| Defesa ou recurso (`contest_status`) | `updateProgress` · `CK_FINE_CONTEST` | manter | Pergunta principal do recurso. |
| Tipo, Entregue em, Número do protocolo | `updateProgress` com "Em análise"/"Aceito"/"Negado" · `CK_FINE_CONTESTED` | **só quando necessário** | Só nesses três. |
| Resposta em, O que o órgão respondeu | `updateProgress` com "Aceito"/"Negado" · `CK_FINE_DECIDED` | **só quando necessário** | Só nesses dois. |
| Motivo da correção (`progress_reason`) | `updateProgress` ao corrigir pagamento, resposta ou encerramento já informados | **só quando necessário** | Aparece sempre que já há algo informado que possa ser corrigido; numa multa sem nada disso, só se tiver valor ou erro. |

## Campos visíveis antes e depois

Campos que a pessoa vê ao abrir a tela, contados no navegador (Chromium, `checkVisibility()`): sem campos ocultos do
sistema nem os de um bloco recolhido; um grupo de opções conta como um campo. "Antes" é a mesma contagem com os
templates e o JavaScript de `e35d450` (`main` com a T03), no mesmo banco. Conferência e situação: multa
DEMO-AIT-26-000103 (registrada, sem andamento informado).

| Formulário e situação | Antes | Depois |
|---|---|---|
| Cadastro novo, com JavaScript (origem "Ainda não sei", tipo em branco) | 24 | 17 |
| Cadastro com Radar + Excesso de velocidade | 28 | 20 |
| Cadastro sem JavaScript | 29 | 22 (mais 7 a um clique) |
| Conferência, com JavaScript, antes do resultado | 8 | 2 |
| Conferência com "Em dia" e fonte "O motorista mostrou o documento" | 8 + lembrete | 5 + lembrete |
| Conferência sem JavaScript | 11 | 10 (mais 1 a um clique) |
| Situação sem nada informado, com JavaScript | 23 | 3 |
| Situação sem JavaScript | 23 | 23 |

## O que mudou na tela

- **Cadastro:** três etapas em vez de cinco. "Documentação na ocorrência" e "Revisão" saíram; suas frases úteis
  foram para a dica de "Veículo autuado" e para uma linha acima do botão de salvar. A descrição curta foi para o fim
  da etapa 1 e vem sugerida a partir do tipo e do logradouro (só preenche se estiver vazia ou ainda for a sugestão
  anterior). Código/desdobramento, operador associado e cor/ano ficam recolhidos; "Não tem o número do AIT?" e "Não
  tem o RENAVAM?" ficam recolhidos e somem quando o número é digitado; a observação sobre a origem só aparece quando
  o órgão autuador difere do órgão do radar ou do agente escolhido.
- **Conferência:** só "A conferência se refere a" e "Resultado" à vista. "Em dia" e "Com pendência" trazem fonte e
  referência (e o exercício do CRLV quando a fonte é o documento ou a consulta oficial); "Com pendência" traz "O que
  estava errado"; "Não conferidos" traz o motivo. "Conferido em" vem preenchido com agora, recolhido numa linha que
  mostra a data e abre para mudar.
- **Situação:** só Fase, Pagamento e Defesa ou recurso à vista; cada um abre os seus campos (datas do aviso de
  infração, do aviso da multa, encerramento; vencimento, dados do pagamento; tipo, entrega, protocolo, resposta).
  Os campos de encerramento foram para junto da fase. O motivo da correção aparece quando há algo já informado.
- **Comum aos três:** um bloco escondido continua sendo enviado e reaparece sozinho se tiver valor ou erro; sem
  JavaScript tudo aparece (os recolhidos abrem com um clique). Regras de registro, estados, validação do servidor e
  banco não mudaram.
- **Defeito corrigido no caminho:** a lista "Órgão autuador" do cadastro montava as opções com `merge`, que renumera
  chaves numéricas: as opções saíam com valores 0, 1, 2… em vez do ID do órgão. Na edição, o órgão gravado aparecia
  errado ou em branco e salvar podia trocá-lo. Agora usa `column('name', 'id')`. O mesmo padrão existe em
  `templates/equipment/form.twig` (só na tabela de conflito) e em `templates/planning/replay.twig`; não foram
  mexidos aqui.

Capturas: `capturas-2026-10-22-multas-campos/` (cadastro com radar, observação da origem, situação só com o que se
aplica, conferência "Em dia", cadastro a 360 px).

## Verificação

Ambiente: Windows 11 + Docker Desktop, projeto Compose isolado `pf-t04` (porta 8098), PHP 8.5.11 e Firebird 5.0.4,
`demo:seed`, conta de teste descartável. Nada foi publicado.

| O quê | Resultado |
|---|---|
| `composer check` no contêiner (estilo, PHPStan, PHPUnit) | 0 de 111 arquivos · 0 erros · **213 testes OK** |
| `FinesHttpTest::testFieldsShownOnlyInSomeSituationsAppearExactlyWhenTheServerDemandsThem` (novo) | para "Paga", "Em análise", "Aceito", "Encerrada", "Em dia", "Com pendência" e "Não conferidos": o 422 aponta exatamente os campos do bloco que essa situação mostra, com `aria-invalid`, e esses campos ficam fora da vista na situação inicial; sem JavaScript todos estão na página; motivo da correção à vista depois de algo informado |
| `FinesHttpTest::testRegistrationFormHidesOnlyOptionalOrAlternativeFieldsAndOpensThemWhenTheyMatter` (novo) | todos os campos do cadastro na página; três etapas; recolhidos fechados num cadastro novo e abertos com valor ou erro (retificação sem AIT e sem RENAVAM); órgão de cada radar na opção e órgão autuador com o ID certo (falhava antes da correção) |
| `npm run build` | sem erros |
| `scripts/verificacao-interface/multas.cjs` (Chromium, 1440/390/360 px) | **45 verificações OK**, sem erro no console; 13 novas (recolhidos do cadastro, AIT digitado, descrição sugerida e não trocada, observação da origem, conferência por resultado, situação com três perguntas e com "Paga"/"Aceito", dados gravados à vista, sem JavaScript tudo alcançável). Cenários 422, 409, sem JavaScript e 360/390 px seguem OK |

Não verificado: leitores de tela reais; o envio pelo navegador de uma situação ou conferência completa (coberto
pelos testes HTTP e de integração).

## Deixado para o autor decidir

- **Código + desdobramento num campo só:** não foi feito. Exigiria dividir o texto no servidor ("745-50") e, numa
  edição sem mudança, um código antigo com hífen (ex.: "A-1" sem desdobramento) seria regravado dividido. Ficaram os
  dois campos, recolhidos juntos.
- **Descrição curta preenchida pelo servidor:** hoje a sugestão é só no navegador e a descrição continua obrigatória
  desde o rascunho (`DESCRIPTION NOT NULL`). Gerar no servidor quando vier vazia mudaria a validação.
- **Motivo / observação da conferência** só aparece com "Não conferidos" (ou se já tiver texto): com JavaScript, quem
  quiser uma observação livre num "Em dia" não a vê. Se isso for usado, dá para mostrá-lo sempre, recolhido.
- **"(opcional)" em campos que passam a ser exigidos** (dados do pagamento, recurso, encerramento): os rótulos não
  mudaram, para não invadir a T08; agora esses campos só aparecem justamente quando são exigidos.
- **Dados do medidor e evidência:** continuam recolhidos para qualquer radar; poderiam abrir sozinhos em excesso de
  velocidade, que é quando contam para "A conferir".
- **Bairro, mapa e coordenadas:** sem mudança (T05).

