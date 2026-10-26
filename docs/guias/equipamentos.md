# Equipamentos

**Objetivo:** manter o inventário de radares, câmeras, balanças, cancelas, totens, veículos e patinetes: cadastrar,
editar, mudar situação, acompanhar manutenção e histórico, arquivar.
**Como acessar:** menu **Equipamentos**; `/equipamentos` (lista), `/equipamentos/{id}` (ficha), `/equipamentos/novo`,
`/equipamentos/{id}/editar`. Qualquer conta ativa consulta e altera (operador ou administrador).
**Antes de começar:** os órgãos e bairros vêm da carga de demonstração; não são editados pela interface.

## Lista (`/equipamentos`)

| Elemento | Significado |
|---|---|
| Título "tipo · endereço" + plaqueta de código | código `PREFIXO-NNNN` gerado na criação (ex.: `RAD-0007`, texto com zeros à esquerda) |
| Bairro, marca/modelo, situação | situação com texto e símbolo |
| "Sinal atrasado" · "Sem sinal recente (não indica falha)" · "Sem localização cadastrada" | atualidade do sinal ([`Freshness`](../referencia/codigo/domain/situacoes-e-atualidade.md#freshness)) |
| Filtros | busca (`q`, até 60), tipo, situação; **Mais filtros**: bairro, órgão, ordenação (`codigo`, `tipo`, `situacao`, `bairro`, `atualizado`) e **Mostrar só arquivados** |

Paginação de 20. Lista vazia com filtro: "nenhum resultado"; os filtros ficam na URL.

## Ficha (`/equipamentos/{id}`)

De cima para baixo: o que é e onde está; situação e atualidade; **Próxima ação** (uma só: concluir a intervenção em
andamento, decidir reposição ou baixa de um furtado, iniciar uma ordem urgente, abrir corretiva, registrar instalação
ou entrada em operação, avaliar a inspeção sugerida pela revisão recomendada, ou "nada pendente"); manutenção (todas
as ordens agendadas e em execução, as três encerradas mais recentes e "Ver ordens encerradas mais antigas", até 30);
histórico (40 eventos); à direita: onde está (**Ver no mapa**), cadastro, revisão recomendada, alertas e **Mais
ações** (mudar situação, simular falha, arquivar). Inexistente → 404.

**Histórico:** cada item diz quando aconteceu e quem registrou (pessoa, simulador, sistema, carga). Linha tracejada =
registro que **não alterou** a situação atual (`OUTCOME = HISTORICO`): evento atrasado, situação definida por pessoa,
equipamento arquivado ou nada mudou.

## Cadastrar um equipamento

1. **Equipamentos → Cadastrar** (`/equipamentos/novo?tipo=RADAR`; padrão: radar, não instalado, local planejado).
2. **1. O que é:** tipo, situação inicial, marca e modelo (obrigatórios), órgão, série (opcional, única **por
   fabricante**), patrimônio (opcional, único).
3. **2. Onde fica:** "este ponto é" local de instalação, planejado ou base; **clique no mapa** para marcar ou use o
   endereço (CEP `17500-000` como texto, rua, número ou referência como "km 3"). O local é obrigatório até para o
   planejado; coordenadas nunca são digitadas; o bairro vem do ponto. Com endereço, o ponto fica dentro dele.
4. **3. Características** do tipo (só as dele aparecem; trocar o tipo troca o bloco) — tabela completa em
   [HTTP](../http.md#equipamento--post-equipamentos-e-post-equipamentosid) e
   [catálogo](../referencia/codigo/domain/catalogo-de-equipamentos.md). Radar: funções obrigatórias; limite de velocidade
   20–120 km/h **só** quando "Velocidade" está marcada; modalidade fixo/portátil (opcional). Também periodicidade da
   preventiva (7–730 dias), data de instalação, observações.
5. **Salvar** (o botão vira "Salvando…" e ignora cliques repetidos).

**Resultado esperado:** 303 para a ficha com "Equipamento cadastrado."; código gerado; histórico `CADASTRO`; alertas
reconciliados (ex.: cadastrado já em falha abre alerta `FALHA`). Conferir na ficha e na Visão geral.

| Erro comum | Mensagem | Como resolver |
|---|---|---|
| Faltou local | "Informe logradouro, referência ou coordenadas." | marcar no mapa ou preencher o endereço |
| Radar de velocidade sem limite | "Obrigatório para este tipo." | informar 20–120 km/h |
| Série repetida na mesma marca | "Já existe equipamento deste fabricante com esta série." | corrigir ou deixar em branco |
| Veículo como local de instalação | "Recurso móvel usa o endereço como base…" | escolher "Base" |
| Não instalado marcado como instalado | "Equipamento ainda não instalado: use 'planejado'." | "Local planejado" |
| Coordenada fora da cidade | "Fora da área de Marília/SP atendida pela demonstração." | marcar no mapa |
| Rua não achada / provedor de endereço fora | erro no campo; digitação mantida | marcar o ponto no mapa |

Com erro (422), a página volta com **tudo o que foi digitado**, resumo no topo com links e mensagem ao lado do campo.

## Editar e resolver conflito de edição

1. Ficha → **Editar** (`/equipamentos/{id}/editar`; arquivado → 409). O formulário leva `version` (versão do cadastro).
2. Salvar.

**Resultado esperado:** "Alterações salvas."; histórico `ALTERACAO` com antes/depois por campo. A situação **não** muda
por aqui.

**Conflito.** Se alguém salvou o cadastro depois que você abriu o formulário: 409 "O cadastro mudou enquanto você
editava", tabela **Salvo agora × Sua versão** e a última mudança registrada; seus dados continuam no formulário.
**Salvar alterações** grava a sua versão sobre a atual (decisão consciente); **descarte suas alterações** recarrega.
Posição, sinal e mudança de situação **não** causam conflito; concluir uma instalação (planejado → instalado) causa
([ADR-0010](../adr/0010-versao-do-cadastro-separada-da-telemetria.md)). Arquivado durante a edição → volta à ficha com
aviso.

## Mudar situação

1. Ficha → **Mais ações → Mudar situação**: só aparecem as transições possíveis
   ([tabela](../dominio.md#situações-e-transições)).
2. Escolher e informar o **motivo** (1–300, obrigatório).

**Resultado esperado:** situação nova, evento `SITUACAO` (de, para, motivo) e alertas reconciliados (ex.: `FURTADO`
abre alerta crítico; sair de `FALHA` resolve o alerta de falha). Efeitos: instalar ("Não instalado" → "Operacional")
transforma o local planejado em local de instalação e grava a data; em veículo/patinete, a data de entrada em operação;
repor um furtado ou desativado ("Não instalado") volta o ponto a planejado.

| Situação | Resposta |
|---|---|
| Intervenção em execução | recusado: "a situação será definida ao concluí-la" ([manutenção](manutencao.md)) |
| Transição fora da tabela | "Transição não permitida: X → Y." |
| Motivo vazio | erro no campo, ficha reapresentada |

**Telemetria × situação administrativa:** falha e recuperação vindas do simulador só valem entre `OPERACIONAL` e
`FALHA`; manutenção, desativado, furtado e não instalado definidos por pessoa **prevalecem** — o evento fica só no
histórico ([ADR-0005](../adr/0005-estado-atual-versus-historico.md)).

## Simular falha

**Mais ações → Simular falha** (ou na ficha resumida da Visão geral): evento fictício `FALHA` pelo mesmo caminho do
simulador. Em situação administrativa ou já em falha → "Evento registrado só no histórico…". Use só em demonstração.

## Arquivar

1. **Mais ações → Arquivar**, motivo 1–300.
2. Com ordens só agendadas: marcar "Cancelar também as ordens agendadas (#…)".

**Resultado esperado:** some da Visão geral, não recebe ordens nem gera alertas; ficha, ordens, alertas e histórico
continuam em **Mostrar só arquivados**; cada ordem agendada fica `CANCELADA` com o motivo e seu nome. Não há
desarquivamento pela interface.

| Situação | Resposta |
|---|---|
| Intervenção em execução | **não arquiva**: conclua a ordem com o resultado real |
| Ordens agendadas sem a confirmação | recusado, listando as ordens |
| Já arquivado | recusado |

Bancos anteriores a 18/10/2026 podem ter ordens abertas em equipamentos arquivados; a consulta para localizá-las está no
[guia de uso](../guia-de-uso.md#arquivamento).

## Regras da funcionalidade

- Local obrigatório para todo equipamento; Marília/SP fixa (`CK_EQUIPMENT_CITY`); móvel só `BASE`, fixo nunca `BASE`;
  não instalado não pode ser `INSTALADO` ([`EquipmentService::validate`](../referencia/codigo/application/equipamentos.md#equipmentservicevalidate)).
- Série única por fabricante, garantida pelo índice `UX_EQUIPMENT_SERIAL` mesmo com cadastros simultâneos.
- Campos de outros tipos são descartados; características validadas por [`EquipmentType::validateSpecs`](../referencia/codigo/domain/catalogo-de-equipamentos.md#equipmenttypevalidatespecs) e gravadas como JSON ([ADR-0003](../adr/0003-caracteristicas-por-tipo-em-json-validado.md)).
- Uma intervenção em execução por vez e arquivamento explícito ([ADR-0009](../adr/0009-uma-intervencao-por-vez-e-arquivamento-explicito.md)).

## Como funciona no código

Cadastrar: `POST /equipamentos` → `EquipmentController::store` →
[`EquipmentService::create`](../referencia/codigo/application/equipamentos.md#equipmentservicecreate) →
[`AddressPoint::resolve`](../referencia/codigo/application/endereco.md#addresspointresolve) (ponto × endereço, CEP e
geocodificação com provedores [externos](../referencia/codigo/infrastructure/provedores-externos.md)) →
`EquipmentType::validateSpecs` → transação: `EquipmentRepository::nextCode`, `INSERT` em `EQUIPMENT`, `History`
(evento `CADASTRO` em `RESOURCE_EVENT`), [`AlertService::reconcile`](../referencia/codigo/application/alertas.md#alertservicereconcile)
(tabela `ALERT`) → 303 com aviso. Editar: `EquipmentController::update` →
[`EquipmentService::update`](../referencia/codigo/application/equipamentos.md#equipmentserviceupdate) →
`updateVersioned` (`WHERE VERSION_NO = ?`) → `ConcurrencyConflict` vira a tela de conflito com
[`pendingChanges`](../referencia/codigo/application/equipamentos.md#equipmentservicependingchanges). Situação:
`POST /equipamentos/{id}/situacao` → [`changeStatus`](../referencia/codigo/application/equipamentos.md#equipmentservicechangestatus)
(`SELECT … WITH LOCK`) → [`applyStatus`](../referencia/codigo/application/equipamentos.md#equipmentserviceapplystatus)
→ [`EquipmentStatus`](../referencia/codigo/domain/situacoes-e-atualidade.md#equipmentstatus) → reconciliação.
Arquivar: [`EquipmentService::archive`](../referencia/codigo/application/equipamentos.md#equipmentservicearchive).
Telas: `templates/equipment/` ([mapa das telas](../referencia/codigo/templates/macros-e-telas.md#mapa-das-telas)).
Dados: [`EQUIPMENT`](../referencia/dicionario-de-dados.md#equipment), [`RESOURCE_EVENT`](../referencia/dicionario-de-dados.md#resource_event).

## Verificação e limites

Testes: `InventoryTest` (13 casos: especificações, códigos, série por fabricante e concorrente, versão antiga,
telemetria que não invalida formulário, transições, arquivamento, base × posição), `EquipmentLocationTest`,
`WebSecurityTest::testEditConflictKeepsTypingShowsDifferencesAndCarriesTheCurrentVersion`,
`testEquipmentFormAsksForLocationByMapOrAddress`, `MaintenanceInvariantsTest` (arquivamento com ordens),
`ReviewFlowsHttpTest` (próxima ação) — [catálogo](../referencia/codigo/testes/casos.md). Evidências:
[18/10](../evidencias/2026-10-18-correcoes-da-auditoria.md), [24/10](../evidencias/2026-10-24-revisao-logica-e-fluxos.md).
Limites: equipamentos e sinais são fictícios; nenhum equipamento real é consultado; órgãos e bairros não têm tela de
cadastro; não há desarquivamento.

## Relacionados

[Manutenção](manutencao.md) · [Alertas](alertas-e-acompanhamento.md) · [Revisões recomendadas](revisoes-recomendadas.md) ·
[Domínio](../dominio.md) · [HTTP](../http.md)
