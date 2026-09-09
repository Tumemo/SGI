ALTER TABLE interclasses
    ADD COLUMN ranking_publicado_em DATETIME NULL AFTER status_interclasse,
    ADD COLUMN ranking_publicado_por INT NULL AFTER ranking_publicado_em;

UPDATE interclasses
SET ranking_publicado_em = COALESCE(ranking_publicado_em, CURRENT_TIMESTAMP)
WHERE status_interclasse = '0';

DROP TRIGGER IF EXISTS tr_sincroniza_status_usuarios;
