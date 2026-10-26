# Testes HTTP

Suíte `http`: requisições PSR-7 entregues à aplicação Slim completa (`WebApp::create(...)->handle()`), com sessão em
memória ([`ArraySession`](../../docs/referencia/codigo/testes/suporte.md#falsos-e-sessão-em-memória)) e o banco de
testes — sem servidor web. Provam middlewares, CSRF, controle de acesso, telas sem JavaScript, escape e respostas JSON.
Catálogo: [casos de teste — HTTP](../../docs/referencia/codigo/testes/casos.md#suíte-http-testshttp).

| Arquivo | Tema |
|---|---|
| `AgentsHttpTest.php` | cadastro de agente pela web, CEP e localização |
| `FinesHttpTest.php` | aba Multas com formulários comuns, conflitos, campos condicionais |
| `LoginQuotaTest.php` | cota atômica de tentativas de login, inclusive concorrente |
| `PlanningHttpTest.php` | Planejar reforço: página, mapa de calor, confirmação, retrospecto |
| `ReviewFlowsHttpTest.php` | fluxos revistos em 24/10 (próxima ação, ordens antigas, senha, missões) |
| `WebSecurityTest.php` | acesso só com conta, cabeçalhos, CSRF, origem, escape, conflitos de edição |
