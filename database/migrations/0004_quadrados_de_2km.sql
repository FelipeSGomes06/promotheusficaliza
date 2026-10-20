-- Migração 0004 — quadrados de 2 km do planejamento por setores (ml/marilia-grade-2km/RESULTADO.md).
-- Propósito: estimativa semanal por quadrado (8 × 8 no retângulo de estudo), pela média simples das 52 semanas,
--            calculada na importação a partir das contagens reais agregadas (Infosiga-SP) e ligada à execução.
-- Dependências: 0003.
-- Risco de perda de dados: nenhum (somente CREATE). Recuperação: restaurar backup gbak anterior.

CREATE TABLE URBAN_CELL (
    RUN_ID      INTEGER NOT NULL,
    CELL_NO     SMALLINT NOT NULL,
    SECTOR_ID   CHAR(2) NOT NULL,
    SOUTH       DECIMAL(9,6) NOT NULL,
    NORTH       DECIMAL(9,6) NOT NULL,
    WEST        DECIMAL(9,6) NOT NULL,
    EAST        DECIMAL(9,6) NOT NULL,
    COUNT_13W   INTEGER NOT NULL,
    COUNT_52W   INTEGER NOT NULL,
    EXPECTED    DECFLOAT(34) NOT NULL,
    RANK_NO     SMALLINT NOT NULL,
    CONSTRAINT PK_URBAN_CELL PRIMARY KEY (RUN_ID, CELL_NO),
    CONSTRAINT FK_URBAN_CELL_SECTOR FOREIGN KEY (RUN_ID, SECTOR_ID) REFERENCES URBAN_SECTOR (RUN_ID, SECTOR_ID),
    CONSTRAINT UQ_URBAN_CELL_RANK UNIQUE (RUN_ID, RANK_NO),
    CONSTRAINT CK_URBAN_CELL_VALUES CHECK (CELL_NO BETWEEN 0 AND 63 AND RANK_NO BETWEEN 1 AND 64 AND SOUTH < NORTH AND WEST < EAST
        AND COUNT_13W >= 0 AND COUNT_52W >= COUNT_13W AND EXPECTED > 0)
);
