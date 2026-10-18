# ADR-0006 — Recomendar origem dedicada para a publicação

Data: 18/10/2026 · Situação: aceita (proposta)

**Contexto.** O endereço pedido é uma subpasta do portal (mesma origem) e a hospedagem do portal não oferece Firebird.
**Decisão (proposta, depende do proprietário).** Publicar o Compose num VPS/contêiner sob `fiscaliza.<domínio>` e
fazer `/promotheusfiscaliza` redirecionar para lá. Se a subpasta for mantida via proxy reverso, registrar o limite de
mesma origem. A aplicação já funciona com qualquer `APP_BASE_PATH` (inclusive vazio).
**Consequências.** Isolamento real de navegador e banco; custo de um servidor pequeno. Nada foi publicado.
