# ADR-0011 — Cota de login reservada antes do hashing

Data: 18/10/2026 · Situação: aceita · Origem: achado SEC-01 (gravidade baixa, CWE-362) da auditoria de 18/10/2026

**Contexto.** O limite de 5 falhas por usuário (20 por IP) em 15 minutos era conferido antes do `password_verify` e
registrado depois. Dois processos que partiram de 4 falhas chegaram a 6. A tabela `LOGIN_ATTEMPT` é só
`SELECT/INSERT` para o usuário da aplicação, e o hashing (Argon2id) não deve ficar dentro de uma transação longa.

**Decisão.**

- Cada verificação **reserva uma vaga numerada** por chave (usuário e IP) numa transação curta, antes do hashing:
  `INSERT … SLOT_NO = MAX(SLOT_NO) + 1`. O índice único parcial `UX_LOGIN_ATTEMPT_SLOT (KEY_HASH, SLOT_NO) WHERE
  SLOT_NO IS NOT NULL` faz duas tentativas simultâneas que calcularam a mesma vaga colidirem; a segunda espera a
  primeira, recebe a violação, reconta e é bloqueada se a cota acabou. Após 5 disputas seguidas, recusa (falha fechada).
- A reserva conta como falha até ser quitada. Login correto acrescenta uma linha de quitação (`SETTLES_ID`) — nada é
  alterado nem apagado, e a tabela continua append-only para a aplicação.
- Usuário inexistente segue o mesmo caminho (reserva + hash fictício), sem revelar se a conta existe.

**Consequências.** Verificado com processos separados, no serviço e pelo HTTP com duas sessões distintas e token CSRF
válido obtido do formulário (`LoginQuotaTest`). Linhas antigas (sem vaga) continuam contando como falha. Uma reserva
de um processo que morra durante o hashing fica como falha (lado seguro). O índice precisa ser parcial: o Firebird
trata `(chave, NULL)` repetido como duplicata num índice único composto — defeito encontrado na verificação no
navegador e coberto por `testRepeatedSuccessfulLoginsAndOlderFailureRowsCoexist`.
