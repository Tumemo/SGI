-- Chave única: matrícula + interclasse (única restrição de unicidade em usuarios)
ALTER TABLE `usuarios`
  ADD COLUMN `chave_usuario_edicao` varchar(50) DEFAULT NULL AFTER `interclasses_id_interclasse`;

UPDATE `usuarios`
SET `chave_usuario_edicao` = CONCAT(`matricula_usuario`, '-', `interclasses_id_interclasse`)
WHERE `chave_usuario_edicao` IS NULL OR `chave_usuario_edicao` = '';

ALTER TABLE `usuarios`
  MODIFY COLUMN `chave_usuario_edicao` varchar(50) NOT NULL;

ALTER TABLE `usuarios`
  DROP INDEX `uk_matricula_interclasse`;

ALTER TABLE `usuarios`
  ADD UNIQUE KEY `uk_chave_usuario_edicao` (`chave_usuario_edicao`);

-- Arrecadação com quantidades decimais (ex.: 5,5 itens → 11 pontos com multiplicador 2)
ALTER TABLE `turmas`
  MODIFY COLUMN `qtd_itens_arrecadados` decimal(10,2) NOT NULL DEFAULT 0.00;
