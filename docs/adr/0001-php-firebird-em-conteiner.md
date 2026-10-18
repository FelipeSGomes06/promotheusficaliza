# ADR-0001 — PHP 8.5 + Firebird 5 em contêineres, com prova antes do código

Data: 18/10/2026 · Situação: aceita

**Contexto.** O proprietário prefere Firebird; a hospedagem do portal não comprova servidor Firebird nem `pdo_firebird`.
**Decisão.** PHP 8.5.11 (Apache) e Firebird 5.0.4 em Docker Compose, rede interna, sem porta do banco publicada. Antes de
escrever a aplicação, um roteiro comprovou conexão, UTF-8, tipos, `RETURNING`, paginação, transações e concorrência
([evidência](../evidencias/2026-10-18-prova-firebird.md)). fbclient 4.0.5 do Debian conversa com o servidor 5.0.4.
**Consequências.** Instalação reproduzível em qualquer máquina com Docker; o mesmo `compose.yaml` serve de base para
VPS. Achados viraram regra: timeout de instrução por conexão, sem reutilizar parâmetro nomeado, decimais como texto.
**Alternativas.** SQLite/MySQL (descartadas: contrariam a preferência e não provam Firebird); Firebird embarcado na
hospedagem compartilhada (frágil, sem garantia de extensão).
