# ADR-0012 — Acesso ao sistema somente com conta cadastrada pelo administrador

Data: 19/10/2026 · Situação: aceita · Substitui: "leitura pública" (decisão anterior, sem ADR próprio)

**Contexto.** Até aqui, qualquer visitante consultava a visão geral, o mapa, a API `/api/central`, as fichas, a
manutenção e as revisões recomendadas; só a escrita pedia conta. O autor do projeto decidiu que **ninguém acessa o
sistema antes de ser cadastrado pelo usuário administrativo**. Não havia (e continua não havendo) cadastro aberto:
contas só nascem pelo terminal do servidor (`user:create`) ou na tela "Contas", que é restrita a administradores.

**Decisão.**

- Todas as telas e a API passam pelo mesmo `RequireRole` da escrita. Ficam abertas só `/entrar`, `POST /sair` e
  `/saude` (resposta `{"status":"ok"}`, sem dados, para monitoramento).
- Visitante em página GET recebe `302 /entrar?voltar=…` (sem corpo e sem cookie de sessão); demais requisições, inclusive
  a API, recebem 401 em JSON sem dados. Depois do login, a pessoa volta para a página que pediu.
- A primeira conta de administrador é criada no servidor (`php bin/fiscaliza user:create nome --role=ADMIN`); a partir
  dela o administrador cadastra e desativa as demais. Conta desativada perde o acesso na requisição seguinte, já que
  a conta é recarregada do banco a cada requisição.
- Sem conta, o layout não mostra menu nem links internos; o login explica que o acesso depende de cadastro feito pelo
  administrador. Sair leva de volta ao login.
- Se a sessão existe mas o banco está fora (a conta não pôde ser conferida), a resposta é 503 com a página de
  indisponibilidade, em vez de mandar a pessoa ao login sem motivo.
- A visão geral, ao receber 401 na atualização periódica (sessão expirada ou conta desativada), recarrega a página e
  o servidor leva ao login.

**Consequências.** Os papéis continuam dois (operador e administrador): toda conta ativa consulta; a diferença entre
eles segue sendo a gestão de contas. Quem avalia a demonstração precisa receber uma conta. Coberto por
`WebSecurityTest` (todas as telas e a API sem conta, cadastro pelo administrador → login → acesso, desativação com
efeito imediato, logout, banco fora com sessão aberta).
