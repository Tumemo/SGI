-- Vincula locais à edição do interclasse + locais padrão por edição ativa

ALTER TABLE `locais`
  ADD COLUMN `interclasses_id_interclasse` int(11) DEFAULT NULL AFTER `status_local`;

UPDATE `locais` l
SET l.`interclasses_id_interclasse` = (
    SELECT i.`id_interclasse` FROM `interclasses` i
    WHERE i.`status_interclasse` = '1'
    ORDER BY i.`id_interclasse` DESC LIMIT 1
)
WHERE l.`interclasses_id_interclasse` IS NULL;

ALTER TABLE `locais`
  MODIFY COLUMN `interclasses_id_interclasse` int(11) NOT NULL,
  ADD KEY `fk_locais_interclasses_idx` (`interclasses_id_interclasse`),
  ADD UNIQUE KEY `uk_local_interclasse` (`nome_local`, `interclasses_id_interclasse`),
  ADD CONSTRAINT `fk_locais_interclasses`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION;

INSERT INTO `locais` (`nome_local`, `disponivel_local`, `status_local`, `interclasses_id_interclasse`)
SELECT v.nome, '1', '1', i.id_interclasse
FROM `interclasses` i
CROSS JOIN (
    SELECT 'Quadra' AS nome UNION ALL
    SELECT 'Biblioteca' UNION ALL
    SELECT 'Pátio 1' UNION ALL
    SELECT 'Pátio 2'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `locais` l
    WHERE l.nome_local = v.nome AND l.interclasses_id_interclasse = i.id_interclasse
);
