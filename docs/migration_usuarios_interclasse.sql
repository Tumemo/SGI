-- =============================================================
-- Migração: Alinhar tabela usuarios com gestão de Interclasses
-- =============================================================
-- Esta migração:
--   1. Adiciona a coluna interclasses_id_interclasse em usuarios
--   2. Remove UNIQUE desnecessárias (nome_usuario, senha_usuario)
--   3. Substitui UNIQUE simples de matricula_usuario por UNIQUE composta
--   4. Garante que apenas um interclasse fique ativo por vez (trigger)
--   5. Protege dados de edições encerradas (trigger BEFORE UPDATE)
-- =============================================================

START TRANSACTION;

-- -----------------------------------------------------------
-- 1. Adicionar coluna interclasses_id_interclasse em usuarios
-- -----------------------------------------------------------
ALTER TABLE `usuarios`
  ADD COLUMN `interclasses_id_interclasse` int(11) DEFAULT NULL AFTER `turmas_id_turma`;

-- Popular com o interclasse ativo (ou o mais recente) para dados existentes
UPDATE `usuarios` u
  SET u.`interclasses_id_interclasse` = (
    SELECT i.`id_interclasse` FROM `interclasses` i
    WHERE i.`status_interclasse` = '1'
    ORDER BY i.`id_interclasse` DESC LIMIT 1
  )
  WHERE u.`interclasses_id_interclasse` IS NULL;

-- Tornar NOT NULL após popular
ALTER TABLE `usuarios`
  MODIFY COLUMN `interclasses_id_interclasse` int(11) NOT NULL;

-- -----------------------------------------------------------
-- 2. Remover UNIQUE desnecessárias
-- -----------------------------------------------------------
ALTER TABLE `usuarios`
  DROP INDEX `nome_usuario`,
  DROP INDEX `senha_usuario`;

-- -----------------------------------------------------------
-- 3. Substituir UNIQUE simples por UNIQUE composta
-- -----------------------------------------------------------
ALTER TABLE `usuarios`
  DROP INDEX `matricula_usuario_UNIQUE`,
  ADD UNIQUE KEY `uk_matricula_interclasse` (`matricula_usuario`, `interclasses_id_interclasse`);

-- -----------------------------------------------------------
-- 4. Adicionar FK para interclasses
-- -----------------------------------------------------------
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION;

-- -----------------------------------------------------------
-- 5. Trigger: garantir apenas um interclasse ativo
--    (reforço ao código PHP, proteção no banco)
-- -----------------------------------------------------------
DELIMITER $$
CREATE TRIGGER `tr_unico_interclasse_ativo`
BEFORE UPDATE ON `interclasses`
FOR EACH ROW
BEGIN
    IF NEW.`status_interclasse` = '1' AND OLD.`status_interclasse` = '0' THEN
        UPDATE `interclasses`
        SET `status_interclasse` = '0'
        WHERE `id_interclasse` <> NEW.`id_interclasse`
          AND `status_interclasse` = '1';
    END IF;
END
$$
DELIMITER ;

-- -----------------------------------------------------------
-- 6. Trigger: sincronizar status dos usuários com o interclasse
--    Quando interclasse muda de status, atualiza todos os seus usuários
-- -----------------------------------------------------------
DELIMITER $$
CREATE TRIGGER `tr_sincroniza_status_usuarios`
AFTER UPDATE ON `interclasses`
FOR EACH ROW
BEGIN
    IF NEW.`status_interclasse` <> OLD.`status_interclasse` THEN
        UPDATE `usuarios`
        SET `status_usuario` = NEW.`status_interclasse`
        WHERE `interclasses_id_interclasse` = NEW.`id_interclasse`;
    END IF;
END
$$
DELIMITER ;

COMMIT;
