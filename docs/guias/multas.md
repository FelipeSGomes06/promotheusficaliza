# Multas

**Objetivo:** registrar **de forma demonstrativa** infrações de trânsito informadas à mão — de onde vieram (radar do
inventário ou agente), qual veículo, a conferência dos documentos e o andamento informado — com histórico completo.
**Como acessar:** menu **Multas**; `/multas` (lista), `/multas/{id}` (ficha), `/multas/novo`, `/multas/{id}/editar`.
Qualquer conta ativa ([ADR-0017](../adr/0017-aba-multas.md)).

> **O que esta aba não faz.** Nada é emitido, cobrado, notificado, consultado ou protocolado. Não há ligação com
> Detran, Senatran, prefeitura ou banco. "Registrar" é concluir o cadastro **neste demonstrador**, não lavrar um auto;
> "pagamento", "defesa/recurso" e "conferência" são informações **anotadas por quem usa**; "cancelar" é interno e não
> anula auto nenhum. Use só dados fictícios.

## Lista (`/multas`)

| Elemento | Significado | Regra |
|---|---|---|
| **registros no filtro** · **a conferir** · **pagas** | três contagens **do mesmo filtro da lista** | "pagas" = anotadas aqui com pagamento informado; "a conferir" = [`FineRules::attention`](../referencia/codigo/domain/multas.md#finerulesattention) |
| **Ver só as N a conferir** / "Só as que estão a conferir" | mesma regra do resumo | `conferir=1` |
| Busca | código interno (`DEMO-MUL-000023`), AIT, placa ou RENAVAM (até 60) | — |
| Filtros | origem, etapa; **Mais filtros**: período da ocorrência (datas locais inclusivas), órgão autuador, radar, agente (da constatação ou operador do radar), documentação, pagamento, rascunhos/cancelados (`cadastro`), ordem | inválido ou invertido → 422 com o filtro preservado |
| Perguntas frequentes | AIT, RENAVAM, velocidade considerada, Inmetro, CRLV, "Não verificada", autuação, penalidade, defesa prévia, recurso, enquadramento | no fim da lista e da ficha |

A URL guarda os filtros; a ficha volta para eles (`voltar` é reconstruído, nunca uma URL livre).

**"A conferir" inclui:** rascunhos; documentos no dia da multa não conferidos; pendência no dia sem regularização
informada depois; lembrete de conferir de novo que chegou (data de Marília, a partir do próprio dia); em excesso de
velocidade por radar, dados do medidor ou referência da evidência faltando, verificação posterior à ocorrência ou com
mais de 12 meses. Nenhum motivo significa multa inválida.

## Cadastrar (rascunho)

1. **Cadastrar multa** (`/multas/novo`; `?radar=<id>` ou `?agente=<id>` só pré-selecionam a origem). Três etapas:
   ocorrência, como foi constatada, veículo.
2. **Ocorrência:** descrição (5–240, a única obrigatória no rascunho; com JavaScript vem sugerida pelo tipo e pelo
   logradouro, sem trocar o que você escreveu), data/hora (Marília, não futura), tipo, base legal, órgão autuador, AIT
   (como está no documento) **ou** "Não tem o número do AIT?" (motivo), logradouro ou referência.
3. **Como foi constatada:** **Radar** (do inventário; pode indicar o agente operador do medidor) ou **Agente de
   trânsito**. Com JavaScript, o bloco da outra origem some e não é enviado (o digitado volta se mudar de ideia); sem
   JavaScript, os dois aparecem — preencha só o da origem escolhida. Em **excesso de velocidade**: limite e medida do
   documento; a **considerada é calculada** (medida − 7 km/h até 107 km/h; −7% acima — Resolução Contran 798/2020), com
   prévia enquanto digita; o servidor ignora qualquer valor enviado.
4. **Veículo autuado** (independente das viaturas do inventário): placa (`ABC1234` ou `ABC1D23`; pontuação de
   apresentação é removida), UF, marca, modelo, espécie, RENAVAM com 11 dígitos **como texto** (`'00123456789'` mantém
   os zeros) **ou** "Não tem o RENAVAM?" (motivo), cor e ano. Com origem **Radar**, a placa lida na imagem é obrigatória
   **desde o rascunho** (sem placa legível o radar não gera registro).
5. **Observação sobre a origem** aparece (e é exigida) só quando o órgão autuador difere do órgão do radar/agente, ou o
   radar não combina com o fato: sem a função da infração, nunca instalado, ou fixo com operador indicado. A lista de
   radares mostra "fixo", "portátil" e "(não instalado)".
6. **Salvar** → rascunho.

**Resultado esperado:** ficha do rascunho com código interno `DEMO-MUL-NNNNNN` (texto) — que **nunca substitui** o AIT —,
documentação "Não verificada" e evento `CRIACAO`. AIT repetido no mesmo órgão → erro no campo AIT ("Abra o registro
anterior em vez de criar outro").

## Registrar após conferência

A **próxima ação** da ficha diz o que falta. **Registrar após conferência** aparece quando os campos com * estão
completos: ocorrência, tipo, base legal, órgão, AIT ou motivo, local, origem coerente (radar com equipamento; agente
com agente de trânsito), placa, UF, marca, modelo, espécie, RENAVAM ou motivo; em velocidade, origem radar e
considerada **acima** do limite.

**Resultado esperado:** `REGISTRADO` e o **retrato (snapshot) da origem** gravado naquele instante: se o radar for
editado ou arquivado depois, o registro continua mostrando como ele era (e um link ao cadastro atual). Evento
`REGISTRO`. Falta algo → 422 na ficha com um link para cada campo.

## Conferir documentos

**Registrar conferência documental** acrescenta uma constatação **imutável**:

| Campo | Regra |
|---|---|
| Momento | **no dia da multa** (`OCORRENCIA`) ou **depois da multa** (`POSTERIOR`, com "Data a que se refere" entre a ocorrência e hoje) |
| Resultado | "Em dia" · "Com pendência" (o que estava errado, obrigatório) · "Não conferidos" (motivo obrigatório) |
| Fonte e referência | CRLV apresentado, consulta feita **fora do sistema**, relato; referência obrigatória com resultado ("sem dados pessoais") |
| Conferido em | já vem com a hora atual; não futuro, não antes da data a que se refere |
| **Quer ser lembrado de conferir de novo?** | Não precisa · Em 30 dias · Em 90 dias · Escolher data — aparece depois do resultado; com "Em dia" vem recolhida em "Não precisa" |

Regras: "Não verificada" é o ponto de partida e **não** significa regular. Uma regularidade posterior aparece separada e
**não corrige** a situação no dia. Vale o lembrete da conferência anotada **por último** (de qualquer momento): uma nova
substitui o atual, "Não precisa" o retira; a ficha mostra o **lembrete em vigor** e de qual conferência veio. É um
lembrete seu, não um prazo legal.

## Retificar e nova conferência

**Mais ações → Retificar** (registrado: **motivo obrigatório**; o original fica no histórico). Corrigir **placa, UF,
RENAVAM, marca, modelo, espécie ou data/hora** muda a identidade do veículo (`SUBJECT_VERSION + 1`): a documentação
volta a "Não verificada" até uma nova conferência; as anteriores ficam em "Todas as conferências", marcadas como
anteriores à retificação. A nova data da ocorrência **não pode ficar depois de fatos já anotados** no andamento (avisos
expedidos/recebidos, pagamento, protocolo, resposta, encerramento — prazos e vencimentos não contam): o formulário diz
quais e até que data vale, mantém o digitado e oferece abrir a situação da multa em outra aba.

## Andamento informado

Três perguntas independentes — **fase** ("Fase ainda não informada", "Aviso de infração enviado (autuação)", "Multa
aplicada (penalidade)", "Encerrada"), **pagamento** ("Ainda não informado", "Não há valor a pagar", "A pagar", "Paga")
e **defesa ou recurso** ("Ainda não informado", "Motorista não recorreu", "Em análise", "Aceito (multa cancelada pelo
órgão)", "Negado"; tipos: defesa prévia, recurso à JARI, recurso ao conselho estadual) — todas **informadas por quem
usa**, nunca decididas pelo sistema; cada resposta abre só os seus campos (ex.: "Paga" abre data, valor e
comprovante). Pagamento informado e recurso em análise podem coexistir. Valores em reais (`1.234,56`, até
R$ 1.000.000,00; gravados em centavos). Datas de fatos não futuras nem antes da ocorrência; recebimento antes da
expedição ou resposta antes do protocolo → erro; corrigir pagamento, resposta ou encerramento já informados exige
motivo. Sequências incomuns viram **avisos**, não erros. O sistema **não calcula** prazos, descontos nem decide nada.

## Cancelar registro

**Mais ações → Cancelar registro**, motivo 1–300: interno (engano, duplicidade); o registro fica **somente leitura**,
sai de "a conferir" e não é anulação do auto. Cancelados conservam o AIT (não pode ser reutilizado no mesmo órgão).

## Estados e exceções

| Situação | O que a tela informa | Como proceder |
|---|---|---|
| Campo inválido | 422: tudo o que foi digitado volta, resumo com links | corrigir |
| Outra pessoa salvou antes | 409 "Salvo agora × Sua versão" | salvar de novo é decisão consciente |
| Mesmo formulário enviado duas vezes (duplo clique, recarregar) | o resultado já gravado; nada duplica | — |
| Chave de formulário usada com outros dados ou por outra conta | 409 "Este formulário já foi usado para outra operação" | abrir o formulário de novo |
| Cancelada | só leitura; edição → 409 | — |
| Radar enviado com origem Agente (ou o contrário) | 422 | escolher a origem certa |
| GET de id inexistente | 404 | — |
| Conta desativada | perde o acesso na requisição seguinte | — |

## Como funciona no código

Criar: `POST /multas` → `FineController::store` → [`FineService::create`](../referencia/codigo/application/multas.md#fineservicecreate)
→ [`validate`](../referencia/codigo/application/multas.md#fineservicevalidate) ([`FineRules`](../referencia/codigo/domain/multas.md#finerules):
placa, AIT, centavos, datas, velocidade considerada) → [`once`](../referencia/codigo/application/multas.md#fineserviceonce)
(chave `command_uid` + impressão digital SHA-256 em `FINE_EVENT.EVENT_UID`/`REQUEST_HASH`) → `INSERT` em `FINE`
(`UX_FINE_AIT` resolve AIT repetido mesmo com criações simultâneas) → evento → 303.
Retificar: `POST /multas/{id}` → [`update`](../referencia/codigo/application/multas.md#fineserviceupdate)
(trava multa → equipamento → agente; confere `VERSION_NO`; [`assertFactsNotBefore`](../referencia/codigo/application/multas.md#fineserviceassertfactsnotbefore)).
Registrar: [`register`](../referencia/codigo/application/multas.md#fineserviceregister) →
[`lockSources`](../referencia/codigo/application/multas.md#fineservicelocksources) →
[`missingForRegistration`](../referencia/codigo/domain/multas.md#finerulesmissingforregistration),
[`registrationConflicts`](../referencia/codigo/domain/multas.md#finerulesregistrationconflicts),
[`divergence`](../referencia/codigo/application/multas.md#fineservicedivergence) →
[`snapshot`](../referencia/codigo/application/multas.md#fineservicesnapshot). Conferência:
[`addCheck`](../referencia/codigo/application/multas.md#fineserviceaddcheck) → `FINE_DOCUMENT_CHECK` + projeção
([`FineDocumentStatus`](../referencia/codigo/domain/multas.md#finedocumentstatus)). Andamento:
[`updateProgress`](../referencia/codigo/application/multas.md#fineserviceupdateprogress). Cancelar:
[`cancel`](../referencia/codigo/application/multas.md#fineservicecancel). Lista:
[`FineQuery`](../referencia/codigo/application/multas.md#finequery) → [`FineRepository::search`](../referencia/codigo/infrastructure/repositorios-multas.md#finerepositorysearch)
(lista e resumos com o mesmo `WHERE`). Telas: `templates/fines/` e [`pages/multas.js`](../referencia/codigo/frontend/paginas.md#pagesmultasjs-páginas-multas-e-multas-form)
(blocos condicionais que espelham o servidor). Dados: [`FINE`, `FINE_DOCUMENT_CHECK`, `FINE_EVENT`](../referencia/dicionario-de-dados.md#fine).

**Repetir um envio.** O servidor emite `command_uid` no formulário. O primeiro envio grava o efeito e um evento com essa
chave e a impressão digital da operação; um reenvio idêntico encontra a chave e devolve o resultado já gravado; dois
envios **simultâneos** da mesma chave são resolvidos pela unicidade de `EVENT_UID` (um grava, o outro vira repetição) —
`FinesTest::testSameCommandSentTwiceAtTheSameTimeIsAReplayNotAConflict`.

## Verificação e limites

Testes: `FinesTest` (22), `FineRulesTest` (16), `FinesHttpTest` (9), `ReviewFlowsHttpTest::testFineRetificationConflictKeepsTypingAndPointsToTheProgress`
([catálogo](../referencia/codigo/testes/casos.md)). Carga de demonstração: [multas de demonstração](../referencia/codigo/demo/multas-de-demonstracao.md)
(12 casos no compacto, 40 no completo). Evidências: [21/10](../evidencias/2026-10-21-multas.md),
[campos, 22/10](../evidencias/2026-10-22-multas-campos.md), [lembrete, 22/10](../evidencias/2026-10-22-lembrete-da-conferencia.md).
Limites: tudo é demonstrativo; não há upload de documentos (uploads desligados no PHP) nem consulta a órgãos; a regra da
velocidade considerada é a da tolerância do Contran transcrita no código, sem outras margens.

## Relacionados

[Equipamentos (radares)](equipamentos.md) · [Agentes](agentes.md) · [HTTP: multas](../http.md#multas) · [Domínio](../dominio.md)
