-- Migração 0011 — redefinição de senha pelo administrador (revisão de 24/10/2026, R06; ADR-0023).
-- Propósito: devolver o acesso à MESMA conta (ID, usuário, papel e todo o histórico vinculado) quando a senha se perde.
--   * CREDENTIAL_NO: versão da credencial; a sessão guarda a do login e deixa de valer quando ela muda (as sessões
--     abertas da conta são encerradas na próxima requisição);
--   * PASSWORD_RESET_AT / PASSWORD_RESET_BY: instante e autoria da última redefinição. A senha nunca é gravada em claro,
--     em log nem em histórico.
-- Dependências: 0001. Não altera linhas existentes além do valor padrão (CREDENTIAL_NO = 0, igual ao de sessões antigas).
-- Risco de perda de dados: nenhum (só acrescenta colunas). Recuperação: restaurar backup gbak anterior.

ALTER TABLE APP_USER ADD CREDENTIAL_NO INTEGER DEFAULT 0 NOT NULL;
ALTER TABLE APP_USER ADD PASSWORD_RESET_AT TIMESTAMP;
ALTER TABLE APP_USER ADD PASSWORD_RESET_BY INTEGER;
ALTER TABLE APP_USER ADD CONSTRAINT FK_APP_USER_RESET_BY FOREIGN KEY (PASSWORD_RESET_BY) REFERENCES APP_USER (ID);
