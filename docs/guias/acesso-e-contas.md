# Acesso e contas

**Objetivo:** entrar e sair do sistema; para o administrador, dar e retirar acesso e devolver o acesso a quem perdeu a
senha.
**Como acessar:** `/entrar` (aberta), **Sair** no menu (`POST /sair`), menu **Contas** (`/contas`, só administrador).
**Antes de começar:** a primeira conta é criada no servidor:

```bash
docker compose exec app php bin/fiscaliza user:create avaliador --role=ADMIN --name="Avaliação"
```

O comando imprime a senha **uma única vez** ([console](../referencia/codigo/cli/console.md)).

> **Landing ≠ aplicação.** A página pública do domínio (`/`) é a [landing](landing.md): sem conta, sem sessão, sem
> cookies. A aplicação (`/promotheusfiscaliza/`) **não tem leitura pública**: sem conta, toda página leva ao login e a
> API responde 401 ([ADR-0014](../adr/0014-acesso-somente-com-conta-cadastrada.md)). Não há cadastro aberto.
>
> **Conta ≠ agente.** Conta é quem entra no sistema; [agente](agentes.md) é a equipe fictícia no mapa.

## Entrar

1. Abra qualquer endereço da aplicação; sem sessão, vai para `/entrar?voltar=<caminho>`.
2. Usuário e senha. **Entrar**.

**Resultado esperado:** sessão nova (identificador regenerado) e volta à página pedida (`voltar` só aceita caminho
interno; qualquer outro → Visão geral).

| Situação | O que a tela informa | Como proceder |
|---|---|---|
| Usuário inexistente, senha errada ou conta desativada | **uma única mensagem**: "Usuário ou senha inválidos." (tempo de resposta igual nos três casos) | conferir os dados ou falar com o administrador |
| 5 falhas da mesma conta (ou 20 do mesmo IP) em 15 min | "Muitas tentativas. Aguarde 15 minutos e tente novamente." — vale também para tentativas **simultâneas** | aguardar |
| Esqueceu a senha | "Esqueceu a senha?" explica: peça ao administrador para **redefinir** | — |
| Banco fora do ar | página de indisponibilidade (503), não o login | tentar mais tarde |
| Sessão parada há mais de 30 min ou com mais de 8 h | volta ao login com "Sua sessão expirou…" | entrar de novo |
| Senha redefinida por um administrador | sessões abertas terminam na próxima requisição: "A senha desta conta foi redefinida pelo administrador. Entre com a senha nova." | entrar com a senha nova |
| Conta desativada durante o uso | perde o acesso na requisição seguinte | — |

## Sair

**Sair** (`POST /sair`, com token CSRF): sessão destruída e volta ao login.

## Papéis

| Papel | Pode |
|---|---|
| **Operador** | consultar tudo; cadastrar e editar equipamentos, agentes e multas; manutenção; alertas; recalcular revisões; missões |
| **Administrador** | tudo do operador **e** Contas |

## Contas (`/contas`, administrador)

| Ação | Como | Efeito | Restrições |
|---|---|---|---|
| **Criar conta** | usuário (3–40: letras, números, `.`, `-`, `_`; único sem diferenciar maiúsculas), nome (≤ 80), papel | senha gerada (20 caracteres) **mostrada uma única vez** | "Nome de usuário já existe." |
| **Desativar / reativar** | botão na linha | desativada perde o acesso na hora; vínculos (autoria, responsável por alerta) ficam | não desativa **a própria conta** nem o **último administrador ativo** (inclusive com duas desativações cruzadas ao mesmo tempo) |
| **Redefinir senha** | botão na linha (confirma o efeito antes) | **a mesma conta** (ID, usuário, papel, histórico) recebe senha nova mostrada uma vez; a anterior deixa de valer; as sessões abertas dessa conta terminam; a lista mostra quando e por quem | não redefine a própria (peça a outro administrador); não reativa conta desativada |

Não cadastre a pessoa de novo quando a senha se perde: isso criaria **outra** conta, sem o histórico. A senha nunca é
gravada em claro, em log nem em histórico; só o hash Argon2id.

## Regras da funcionalidade

- Cota de login **atômica**: cada verificação reserva uma vaga numerada por usuário e por IP **antes** do hash; o
  índice `UX_LOGIN_ATTEMPT_SLOT` impede que tentativas simultâneas passem do limite; um login correto acrescenta uma
  linha de quitação (nada é apagado) ([ADR-0011](../adr/0011-cota-de-login-atomica.md)).
- O banco guarda só o **hash** de `u:<usuário>` e `ip:<endereço>` em `LOGIN_ATTEMPT`, não os valores.
- A conta ativa é relida do banco **a cada requisição**: desativação, papel e redefinição valem imediatamente.
- Escritas exigem token CSRF e, se houver `Origin`, origem igual a `APP_ORIGIN` ou à da requisição.

## Como funciona no código

**Entrar, redefinir senha e a sessão seguinte:** `POST /entrar` → `AuthController::login` →
[`AuthService::attempt`](../referencia/codigo/application/autenticacao.md#authserviceattempt) →
[`reserve`](../referencia/codigo/application/autenticacao.md#authservicereserve) (`LOGIN_ATTEMPT`) → verificação →
quitação e `LAST_LOGIN_AT` → `Session::regenerate` e grava `user_id` e `credential_no` → 303 para `voltar`.
O administrador: `POST /contas/{id}/senha` → `AuthController::resetPassword` →
[`AuthService::resetPassword`](../referencia/codigo/application/autenticacao.md#authserviceresetpassword) →
`APP_USER.PASSWORD_HASH`, `CREDENTIAL_NO + 1`, `PASSWORD_RESET_AT/BY` → 200 com a senha nova uma vez. Na próxima
requisição da sessão antiga, [`SessionContext`](../referencia/codigo/http/middlewares.md#sessioncontext) compara o
`credential_no` guardado com o do banco, encerra a sessão e mostra o aviso; [`RequireRole`](../referencia/codigo/http/middlewares.md#requirerole)
manda ao login. Contas: [`createUser`](../referencia/codigo/application/autenticacao.md#authservicecreateuser),
[`setActive`](../referencia/codigo/application/autenticacao.md#authservicesetactive) (trava todos os administradores ativos
antes de contar). Dados: [`APP_USER` e `LOGIN_ATTEMPT`](../referencia/dicionario-de-dados.md#app_user-e-login_attempt).

## Verificação e limites

Testes: `WebSecurityTest` (acesso só com conta, login genérico e limitado, CSRF, origem, papéis),
`LoginQuotaTest` (5, inclusive processos e logins HTTP concorrentes), `AccountsTest` (último administrador, também
cruzado), `ReviewFlowsHttpTest::testAdminResetsAPasswordOnTheSameAccountAndOpenSessionsEnd`,
`FinesHttpTest::testDeactivatedAccountLosesAccessOnTheNextRequest` ([catálogo](../referencia/codigo/testes/casos.md)).
Evidência: [acesso somente com conta, 19/10](../evidencias/2026-10-19-identidade-com-acesso-por-conta.md). Controles e
riscos: [segurança](../seguranca.md). Limites: não há recuperação de senha por e-mail, autenticação de dois fatores nem
cadastro aberto.

## Relacionados

[Segurança](../seguranca.md) · [Operação: primeira conta](../operacao.md) · [Sobre e interface](sobre-e-interface.md)
