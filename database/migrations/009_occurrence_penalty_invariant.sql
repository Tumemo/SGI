-- Penalties are stored as magnitudes; ranking applies the subtraction once.
-- Normalize legacy rows before enforcing the invariant.
UPDATE ocorrencias_turmas
SET pontos_descontados = ABS(pontos_descontados)
WHERE pontos_descontados < 0;

UPDATE ocorrencias
SET penalidade = ABS(penalidade)
WHERE penalidade < 0;

ALTER TABLE ocorrencias_turmas
    ADD CONSTRAINT chk_ocorrencias_turmas_pontos_nonnegative
    CHECK (pontos_descontados >= 0);

ALTER TABLE ocorrencias
    ADD CONSTRAINT chk_ocorrencias_penalidade_nonnegative
    CHECK (penalidade IS NULL OR penalidade >= 0);
