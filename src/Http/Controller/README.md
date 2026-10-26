# Controladores (`src/Http/Controller/`)

Traduzem HTTP em chamadas aos serviços e escolhem a resposta: página, redirecionamento **303** com aviso (POST → 303 →
GET, funciona sem JavaScript) ou JSON. Sem regra de negócio. Contratos de cada método:
[controladores](../../../docs/referencia/codigo/http/controladores.md); rotas e campos: [HTTP](../../../docs/http.md).

| Arquivo | Rotas | Guia |
|---|---|---|
| `Controller.php` | base: render, redirect sob o prefixo, JSON `no-store`, sessão, corpo, conta | — |
| `CentralController.php` | `/`, `/api/central` | [Visão geral](../../../docs/guias/visao-geral.md) |
| `EquipmentController.php` | `/equipamentos…` | [Equipamentos](../../../docs/guias/equipamentos.md) |
| `AgentController.php` | `/agentes…` (inclui ciência, responsável e providências) | [Agentes](../../../docs/guias/agentes.md), [Alertas](../../../docs/guias/alertas-e-acompanhamento.md) |
| `AddressController.php` | `/api/cep/{cep}`, `/api/localizar` | [Agentes](../../../docs/guias/agentes.md#base-de-trabalho-mapa-cep-e-rua) |
| `OperationsController.php` | `/manutencao`, `/manutencoes/{id}/…`, `/equipamentos/{id}/manutencoes`, `/alertas/{id}/reconhecer`, `/antecipacao…` | [Manutenção](../../../docs/guias/manutencao.md), [Revisões](../../../docs/guias/revisoes-recomendadas.md) |
| `FineController.php` | `/multas…` | [Multas](../../../docs/guias/multas.md) |
| `PlanningController.php` | `/planejar…` | [Planejar reforço](../../../docs/guias/planejar-reforco.md) |
| `AuthController.php` | `/entrar`, `/sair`, `/contas…` | [Acesso e contas](../../../docs/guias/acesso-e-contas.md) |
| `PageController.php` | `/sobre`, `/saude` | [Sobre](../../../docs/guias/sobre-e-interface.md) |
