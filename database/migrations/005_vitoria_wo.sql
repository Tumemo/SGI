-- Instalações novas já possuem o campo no schema inicial; instalações existentes o recebem aqui.
SET @sgi_wo_ddl = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE jogos ADD COLUMN vencedor_wo_equipe_id INT NULL AFTER exige_vinculo_ponto, ADD KEY idx_jogos_vencedor_wo (vencedor_wo_equipe_id), ADD CONSTRAINT fk_jogos_vencedor_wo FOREIGN KEY (vencedor_wo_equipe_id) REFERENCES equipes (id_equipe) ON DELETE RESTRICT',
    'SELECT 1')
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'jogos' AND column_name = 'vencedor_wo_equipe_id'
);
PREPARE sgi_wo_statement FROM @sgi_wo_ddl;
EXECUTE sgi_wo_statement;
DEALLOCATE PREPARE sgi_wo_statement;
