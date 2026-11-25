-- Modalidades antigas mantêm o registro de artilharia habilitado.
SET @sgi_artilharia_ddl = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE modalidades ADD COLUMN contabiliza_artilharia TINYINT(1) NOT NULL DEFAULT 1 AFTER interclasses_id_interclasse',
    'SELECT 1')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'modalidades' AND column_name = 'contabiliza_artilharia'
);
PREPARE sgi_artilharia_statement FROM @sgi_artilharia_ddl;
EXECUTE sgi_artilharia_statement;
DEALLOCATE PREPARE sgi_artilharia_statement;

ALTER TABLE artilheiros MODIFY usuarios_id_usuario INT(11) NULL;
