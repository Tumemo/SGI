ALTER TABLE usuarios
    ADD COLUMN senha_troca_pendente TINYINT(1) NOT NULL DEFAULT 0 AFTER senha_usuario;

UPDATE usuarios
SET senha_troca_pendente = 1
WHERE nivel_usuario = '3';
