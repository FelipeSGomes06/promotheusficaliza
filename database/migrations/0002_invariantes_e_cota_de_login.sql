-- Migração 0002 — invariantes de manutenção, cota atômica de login e classe "não avaliado" da análise.
-- Propósito:
--   * no máximo uma intervenção em execução por equipamento, garantida pelo banco mesmo sob concorrência;
--   * cada verificação de senha reserva uma vaga numerada por chave (usuário e IP) ANTES do hashing, de modo que
--     tentativas simultâneas não ultrapassem o limite; um login bem-sucedido acrescenta uma linha de quitação
--     (a tabela continua só SELECT/INSERT para o usuário da aplicação);
--   * resultados de equipamentos fora de serviço deixam de aparecer como "BAIXA" e passam a "NAO_AVALIADO".
-- Dependências: 0001 e Firebird 5 (índice parcial).
-- Risco de perda de dados: nenhum (somente acréscimos). A criação de UX_MAINT_ONE_RUNNING falha se já houver duas
--   ordens EM_EXECUCAO para o mesmo equipamento; verifique antes com:
--   SELECT EQUIPMENT_ID FROM MAINTENANCE_ORDER WHERE STATE = 'EM_EXECUCAO' GROUP BY EQUIPMENT_ID HAVING COUNT(*) > 1
--   e conclua a intervenção excedente com o resultado real. Recuperação: restaurar backup gbak anterior.

CREATE UNIQUE INDEX UX_MAINT_ONE_RUNNING ON MAINTENANCE_ORDER (EQUIPMENT_ID) WHERE STATE = 'EM_EXECUCAO';

-- SLOT_NO: número sequencial da tentativa por chave (único entre as reservas). SETTLES_ID: esta linha quita a
-- tentativa indicada (login correto). Linhas antigas e de quitação não têm SLOT_NO: o índice é parcial porque o
-- Firebird considera repetida a chave composta (KEY_HASH, NULL) num índice único comum.
ALTER TABLE LOGIN_ATTEMPT ADD SLOT_NO INTEGER;
ALTER TABLE LOGIN_ATTEMPT ADD SETTLES_ID BIGINT;
CREATE UNIQUE DESCENDING INDEX UX_LOGIN_ATTEMPT_SLOT ON LOGIN_ATTEMPT (KEY_HASH, SLOT_NO) WHERE SLOT_NO IS NOT NULL;
CREATE INDEX IX_LOGIN_ATTEMPT_SETTLES ON LOGIN_ATTEMPT (SETTLES_ID);

ALTER TABLE ANALYSIS_RESULT DROP CONSTRAINT CK_RESULT_ATTENTION;
ALTER TABLE ANALYSIS_RESULT ADD CONSTRAINT CK_RESULT_ATTENTION CHECK (ATTENTION IN ('ALTA', 'MODERADA', 'BAIXA', 'INSUFICIENTE', 'CORRETIVA', 'NAO_AVALIADO'));
