-- Migração 0010 — lembrete de conferência dos documentos vale para os dois momentos (revisão de 24/10/2026, R04; ADR-0023).
-- Propósito: o formulário pergunta "Quer ser lembrado de conferir de novo?" nas conferências "no dia da multa" e
-- "depois da multa", mas a projeção só guardava o lembrete da primeira (OCC_DOC_REVIEW_ON). Agora vale o lembrete da
-- conferência anotada por último na identidade atual do veículo, de qualquer momento: DOC_REVIEW_ON guarda a data e
-- DOC_REVIEW_SCOPE de qual momento ela veio. A situação no dia da multa (OCC_DOC_STATUS) não muda.
-- Dependências: 0007. Não muda VERSION_NO, conferências nem histórico: só a projeção derivada do lembrete.
-- Risco de perda de dados: nenhum (a coluna é renomeada, não apagada; o valor é recalculável a partir das conferências).
-- Recuperação: restaurar backup gbak anterior.

ALTER TABLE FINE ALTER COLUMN OCC_DOC_REVIEW_ON TO DOC_REVIEW_ON;
ALTER TABLE FINE ADD DOC_REVIEW_SCOPE VARCHAR(12);
ALTER TABLE FINE ADD CONSTRAINT CK_FINE_DOC_REVIEW_SCOPE CHECK (DOC_REVIEW_SCOPE IS NULL OR DOC_REVIEW_SCOPE IN ('OCORRENCIA', 'POSTERIOR'));

-- Mesma escolha de FineDocumentStatus::reminder: a última conferência lançada (maior ID) da identidade atual.
UPDATE FINE f SET
    DOC_REVIEW_ON = (SELECT FIRST 1 c.REVIEW_ON FROM FINE_DOCUMENT_CHECK c
        WHERE c.FINE_ID = f.ID AND c.SUBJECT_VERSION = f.SUBJECT_VERSION ORDER BY c.ID DESC),
    DOC_REVIEW_SCOPE = (SELECT FIRST 1 IIF(c.REVIEW_ON IS NULL, NULL, c.SCOPE) FROM FINE_DOCUMENT_CHECK c
        WHERE c.FINE_ID = f.ID AND c.SUBJECT_VERSION = f.SUBJECT_VERSION ORDER BY c.ID DESC);
