# ADR-0002 — Slim 4 + Twig + PDO direto, sem ORM

Data: 18/10/2026 · Situação: aceita

**Contexto.** ORMs populares não garantem suporte a Firebird; o desafio pede monólito modular simples.
**Decisão.** Slim 4 (roteamento, middlewares, prefixo), Twig 3 (escape automático) e PDO_FIREBIRD direto em
repositórios pequenos com SQL Firebird. Migrações em arquivos `.sql` numerados aplicadas por `bin/fiscaliza migrate`.
Sem contêiner de injeção: `Services` compõe os objetos.
**Consequências.** Poucas dependências (3 de produção), SQL explícito e testado no banco real; mais código de
mapeamento manual. Sem framework próprio nem autenticação criptográfica própria (usa `password_hash`).
**Alternativas.** Laravel (Eloquent/migrations sem Firebird oficial); Doctrine DBAL (suporte Firebird de terceiros);
PHP sem framework (roteador e middleware próprios = framework caseiro).
