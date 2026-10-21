-- Migração 0006 — a missão fictícia guarda o quadrado de 2 km escolhido (opcional; missões antigas ficam sem).
-- Propósito: a tela mostra em cada quadrado se já há agente designado, e a distância do agente é até o quadrado.
-- Dependências: 0005.
-- Risco de perda de dados: nenhum (só acrescenta coluna anulável). Recuperação: restaurar backup gbak anterior.

ALTER TABLE URBAN_MISSION ADD CELL_NO SMALLINT;
ALTER TABLE URBAN_MISSION ADD CONSTRAINT CK_URBAN_MISSION_CELL CHECK (CELL_NO IS NULL OR CELL_NO BETWEEN 0 AND 63);
