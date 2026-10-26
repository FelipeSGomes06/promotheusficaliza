# Guias por funcionalidade

Um guia por aba ou conjunto de funcionalidades. Cada um segue o mesmo roteiro: **objetivo e acesso → o que aparece na
tela → tarefas passo a passo (onde começar, o que preencher, o que muda, onde conferir, o que fazer se falhar) →
estados e exceções → regras → como funciona no código (rota → controlador → serviço → regra → tabela → resposta) →
testes, evidências e limites**.

O [guia de uso](../guia-de-uso.md) continua sendo o passeio rápido pelas telas, com os textos da interface; os guias
daqui aprofundam cada funcionalidade e ligam a experiência à implementação. Contratos detalhados de funções ficam na
[referência de código](../referencia/codigo/README.md); rotas e campos, em [HTTP](../http.md).

## Antes de tudo: o que é real

| Real | Fictício (demonstração) |
|---|---|
| Contagens **agregadas** de sinistros de Marília do Infosiga-SP (Detran-SP, CC BY 4.0), usadas só em Planejar reforço | Equipamentos, agentes, posições, sinais, alertas, ordens, missões e multas |
| As regras, validações, históricos e controles de concorrência do sistema | Qualquer integração com prefeitura, polícia, radares, Detran/Senatran ou cobrança — **não existe nenhuma** |

Duas distinções atravessam todos os guias: **conta de acesso** (quem entra no sistema, em Contas) ≠ **agente de campo**
(equipe fictícia mostrada no mapa); **base de trabalho** (ponto fixo cadastrado) ≠ **última posição** (o que o rádio
simulado informou por último).

## Índice

| Guia | Rotas principais | Quem acessa |
|---|---|---|
| [Visão geral](visao-geral.md) | `/`, `/api/central` | qualquer conta |
| [Equipamentos](equipamentos.md) | `/equipamentos…` | consulta: qualquer conta · escrita: qualquer conta (operador ou administrador) |
| [Agentes](agentes.md) | `/agentes…`, `/api/cep`, `/api/localizar` | idem |
| [Alertas e acompanhamento](alertas-e-acompanhamento.md) | `/alertas/{id}/reconhecer`, `/agentes/{id}/alertas/…` | idem |
| [Manutenção](manutencao.md) | `/manutencao`, `/manutencoes/{id}/…` | idem |
| [Multas](multas.md) | `/multas…` | idem |
| [Revisões recomendadas](revisoes-recomendadas.md) | `/antecipacao` | idem |
| [Planejar reforço e retrospecto](planejar-reforco.md) | `/planejar`, `/planejar/retrospecto`, `/planejar/missoes…` | idem |
| [Acesso e contas](acesso-e-contas.md) | `/entrar`, `/sair`, `/contas…` | login: aberto · Contas: só administrador |
| [Sobre, ajuda e interface](sobre-e-interface.md) | `/sobre`; comportamentos comuns a todas as telas | qualquer conta |
| [Landing pública](landing.md) | `/` do domínio (fora da aplicação) | qualquer visitante |
| [Demonstração e simulação](demonstracao-e-simulacao.md) | `bin/fiscaliza demo:*`, `sim:*` | quem opera o servidor |
| [Dados reais e ML](dados-e-ml.md) | `urban:import`, `urban:update`, `ml/` | quem opera o servidor |
| [Vídeo e automações](video-e-automacoes.md) | `video/`, `scripts/` | quem desenvolve |

"Qualquer conta" = conta ativa, de operador ou administrador ([ADR-0014](../adr/0014-acesso-somente-com-conta-cadastrada.md)).
As rotas de consulta e as de escrita exigem conta (`RequireRole('OPERADOR')`, que o administrador também satisfaz);
só Contas exige administrador. Não há leitura pública.

## Fluxos de ponta a ponta

| Fluxo | Onde está explicado |
|---|---|
| Abrir a Visão geral e aplicar uma atualização que chegou depois de outra | [Visão geral → atualização periódica](visao-geral.md#atualização-periódica-e-perda-de-conexão) |
| Cadastrar/editar equipamento, mudar situação e reconciliar alertas | [Equipamentos → como funciona no código](equipamentos.md#como-funciona-no-código) |
| Abrir/iniciar/concluir manutenção e tentativa concorrente | [Manutenção → como funciona no código](manutencao.md#como-funciona-no-código) |
| Criar/retificar/registrar multa, conferir documentação e repetir um envio | [Multas → como funciona no código](multas.md#como-funciona-no-código) |
| Ciência e acompanhamento de alerta de agente até a resolução | [Alertas → como funciona no código](alertas-e-acompanhamento.md#como-funciona-no-código) |
| Confirmar missão com disputa pelo mesmo agente | [Planejar reforço → como funciona no código](planejar-reforco.md#como-funciona-no-código) |
| Atualizar dados urbanos e disponibilizar a previsão | [Dados reais e ML → atualização automática](dados-e-ml.md#atualização-automática-urbanupdate) |
| Entrar, redefinir senha e a sessão seguinte | [Acesso e contas → como funciona no código](acesso-e-contas.md#como-funciona-no-código) |
