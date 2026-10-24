-- Migração 0008 — recontagem de "A conferir" das multas: pendência documental no dia da multa deixa de contar quando
-- uma conferência posterior da mesma identidade informa "Em dia" (FineRules::attention, revisão de 24/10/2026).
-- Propósito: ATTENTION_COUNT é gravado na escrita; registros já existentes contavam a pendência regularizada.
-- Dependências: 0007. Não muda esquema, VERSION_NO, conferências nem histórico: só o contador derivado.
-- Risco de perda de dados: nenhum (o contador é recalculável). Recuperação: restaurar backup gbak anterior.
-- shortcut: desconta 1 da contagem gravada pela regra anterior; uma multa regravada pelo código novo entre o git pull e
-- o migrate seria descontada duas vezes (volta ao certo na próxima escrita dela). Se isso importar, recontar pelo
-- FineRules em PHP em vez de SQL.

UPDATE FINE f SET ATTENTION_COUNT = ATTENTION_COUNT - 1
WHERE f.RECORD_STATE <> 'CANCELADO' AND f.OCC_DOC_STATUS = 'PENDENCIA_INFORMADA' AND f.ATTENTION_COUNT > 0
  AND (SELECT FIRST 1 c.DOCUMENT_STATUS FROM FINE_DOCUMENT_CHECK c
       WHERE c.FINE_ID = f.ID AND c.SCOPE = 'POSTERIOR' AND c.SUBJECT_VERSION = f.SUBJECT_VERSION
       ORDER BY c.CHECKED_AT DESC, c.ID DESC) = 'REGULARIDADE_INFORMADA';
