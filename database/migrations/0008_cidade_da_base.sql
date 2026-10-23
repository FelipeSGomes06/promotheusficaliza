-- Migração 0008 — a base do agente guarda a cidade digitada (padrão Marília; cadastros antigos ficam sem e a tela mostra Marília).
-- Propósito: no cadastro de agente, a cidade deixou de ser texto fixo (ADR-0021).
-- Dependências: 0007.
-- Risco de perda de dados: nenhum (só acrescenta coluna anulável). Recuperação: restaurar backup gbak anterior.

ALTER TABLE AGENT ADD BASE_CITY VARCHAR(80);
