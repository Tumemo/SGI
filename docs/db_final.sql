-- =============================================================
-- SGI v2 - Banco de dados final (corrigido)
-- =============================================================
-- Correções aplicadas:
--   - ocorrecia → ocorrencia (colunas da tabela ocorrencias)
--   - pardida → partida (coluna status_partida na tabela partidas)
--   - Adicionada coluna hora_ocorrencia (usada no PHP)
--   - UNIQUE composta matricula_usuario + interclasses_id_interclasse
--   - Triggers de unicidade de interclasse ativo e sincronização de status
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "-03:00";

-- -----------------------------------------------------------
-- Banco de dados
-- -----------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `sgi`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `sgi`;

-- -----------------------------------------------------------
-- Limpar tabelas (ordem reversa de FK)
-- -----------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `usuarios_has_interclasses`;
DROP TABLE IF EXISTS `artilheiros`;
DROP TABLE IF EXISTS `pontuacoes`;
DROP TABLE IF EXISTS `ocorrencias`;
DROP TABLE IF EXISTS `equipes_has_usuarios`;
DROP TABLE IF EXISTS `partidas`;
DROP TABLE IF EXISTS `equipes`;
DROP TABLE IF EXISTS `jogos`;
DROP TABLE IF EXISTS `modalidades`;
DROP TABLE IF EXISTS `tipos_modalidades`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `turmas`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `locais`;
DROP TABLE IF EXISTS `interclasses`;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------
-- Tabela: interclasses
-- -----------------------------------------------------------
CREATE TABLE `interclasses` (
  `id_interclasse` int(11) NOT NULL AUTO_INCREMENT,
  `nome_interclasse` varchar(45) DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL,
  `regulamento_interclasse` varchar(255) DEFAULT NULL,
  `status_interclasse` enum('1','0') NOT NULL,
  `ponto_1_lugar` int(11) NOT NULL DEFAULT 10,
  `ponto_2_lugar` int(11) NOT NULL DEFAULT 7,
  `ponto_3_lugar` int(11) NOT NULL DEFAULT 5,
  `valor_item_arrecadacao` int(11) NOT NULL DEFAULT 2,
  PRIMARY KEY (`id_interclasse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER $$
CREATE TRIGGER `tr_atualiza_pontos_arrecadacao` AFTER UPDATE ON `interclasses` FOR EACH ROW
BEGIN
    IF OLD.valor_item_arrecadacao <> NEW.valor_item_arrecadacao THEN
        UPDATE turmas
        SET pontuacao_turma = qtd_itens_arrecadados * NEW.valor_item_arrecadacao
        WHERE interclasses_id_interclasse = NEW.id_interclasse;
    END IF;
END
$$

CREATE TRIGGER `tr_unico_interclasse_ativo` BEFORE UPDATE ON `interclasses` FOR EACH ROW
BEGIN
    IF NEW.status_interclasse = '1' AND OLD.status_interclasse = '0' THEN
        UPDATE interclasses
        SET status_interclasse = '0'
        WHERE id_interclasse <> NEW.id_interclasse
          AND status_interclasse = '1';
    END IF;
END
$$

-- Trigger: sincroniza status dos usuários com o interclasse
CREATE TRIGGER `tr_sincroniza_status_usuarios` AFTER UPDATE ON `interclasses` FOR EACH ROW
BEGIN
    IF NEW.status_interclasse <> OLD.status_interclasse THEN
        UPDATE usuarios
        SET status_usuario = NEW.status_interclasse
        WHERE interclasses_id_interclasse = NEW.id_interclasse;
    END IF;
END
$$
DELIMITER ;

-- -----------------------------------------------------------
-- Tabela: locais
-- -----------------------------------------------------------
CREATE TABLE `locais` (
  `id_local` int(11) NOT NULL AUTO_INCREMENT,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int(11) DEFAULT NULL,
  `status_local` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  PRIMARY KEY (`id_local`),
  UNIQUE KEY `uk_local_interclasse` (`nome_local`, `interclasses_id_interclasse`),
  KEY `fk_locais_interclasses_idx` (`interclasses_id_interclasse`),
  CONSTRAINT `fk_locais_interclasses`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: categorias
-- -----------------------------------------------------------
CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL AUTO_INCREMENT,
  `nome_categoria` varchar(45) DEFAULT NULL,
  `status_categoria` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  PRIMARY KEY (`id_categoria`),
  KEY `fk_categorias_interclasses1_idx` (`interclasses_id_interclasse`),
  CONSTRAINT `fk_categorias_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: turmas
-- -----------------------------------------------------------
CREATE TABLE `turmas` (
  `id_turma` int(11) NOT NULL AUTO_INCREMENT,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `nome_turma` varchar(45) DEFAULT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') DEFAULT NULL,
  `nome_fantasia_turma` varchar(45) DEFAULT NULL,
  `status_turma` enum('1','0') DEFAULT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `pontuacao_turma` int(11) NOT NULL DEFAULT 0,
  `qtd_itens_arrecadados` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_turma`),
  KEY `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse`),
  KEY `fk_turmas_categorias1_idx` (`categorias_id_categoria`),
  CONSTRAINT `fk_turmas_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `fk_turmas_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------
-- Tabela: tipos_modalidades
-- -----------------------------------------------------------
CREATE TABLE `tipos_modalidades` (
  `id_tipo_modalidade` int(11) NOT NULL AUTO_INCREMENT,
  `nome_tipo_modalidade` varchar(45) NOT NULL,
  `status_tipo_modalidade` enum('1','0') NOT NULL,
  PRIMARY KEY (`id_tipo_modalidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: usuarios
-- -----------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `sigla_usuario` enum('RM','SS','SN') DEFAULT NULL,
  `matricula_usuario` varchar(20) NOT NULL,
  `nome_usuario` varchar(45) NOT NULL,
  `senha_usuario` varchar(200) NOT NULL,
  `nivel_usuario` enum('0','1','2') NOT NULL DEFAULT '0',
  `competidor_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `mesario_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `genero_usuario` enum('FEM','MASC') NOT NULL,
  `data_nasc_usuario` date NOT NULL,
  `foto_usuario` varchar(255) NOT NULL,
  `status_usuario` enum('0','1') NOT NULL,
  `turmas_id_turma` int(11) DEFAULT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uk_matricula_interclasse` (`matricula_usuario`, `interclasses_id_interclasse`),
  KEY `fk_usuarios_turmas1_idx` (`turmas_id_turma`),
  KEY `fk_usuarios_interclasses1_idx` (`interclasses_id_interclasse`),
  CONSTRAINT `fk_usuarios_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `turmas` (`id_turma`),
  CONSTRAINT `fk_usuarios_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Nenhum trigger adicional em usuarios.
-- O trigger tr_sincroniza_status_usuarios (em interclasses) gerencia o status automaticamente.

-- -----------------------------------------------------------
-- Tabela: modalidades
-- -----------------------------------------------------------
CREATE TABLE `modalidades` (
  `id_modalidade` int(11) NOT NULL AUTO_INCREMENT,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `max_inscrito_modalidade` int(11) DEFAULT NULL,
  `status_modalidade` enum('1','0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` int(11) NOT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  PRIMARY KEY (`id_modalidade`),
  KEY `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade`),
  KEY `fk_modalidades_categorias1_idx` (`categorias_id_categoria`),
  KEY `fk_modalidades_interclasses1_idx` (`interclasses_id_interclasse`),
  CONSTRAINT `fk_modalidades_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `fk_modalidades_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
    ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_modalidades_tipos_modalidades1`
    FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`)
    REFERENCES `tipos_modalidades` (`id_tipo_modalidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: jogos
-- -----------------------------------------------------------
CREATE TABLE `jogos` (
  `id_jogo` int(11) NOT NULL AUTO_INCREMENT,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `termino_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `locais_id_local` int(11) NOT NULL,
  PRIMARY KEY (`id_jogo`),
  KEY `fk_jogos_modalidades1_idx` (`modalidades_id_modalidade`),
  KEY `fk_jogos_locais1_idx` (`locais_id_local`),
  CONSTRAINT `fk_jogos_locais1`
    FOREIGN KEY (`locais_id_local`)
    REFERENCES `locais` (`id_local`),
  CONSTRAINT `fk_jogos_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `modalidades` (`id_modalidade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: equipes
-- -----------------------------------------------------------
CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL AUTO_INCREMENT,
  `status_equipe` enum('1','0') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL,
  PRIMARY KEY (`id_equipe`),
  KEY `fk_equipes_modalidades1_idx` (`modalidades_id_modalidade`),
  KEY `fk_equipes_turmas1_idx` (`turmas_id_turma`),
  CONSTRAINT `fk_equipes_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `modalidades` (`id_modalidade`),
  CONSTRAINT `fk_equipes_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `turmas` (`id_turma`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: equipes_has_usuarios
-- -----------------------------------------------------------
CREATE TABLE `equipes_has_usuarios` (
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`equipes_id_equipe`, `usuarios_id_usuario`),
  KEY `fk_equipes_has_usuarios_usuarios1_idx` (`usuarios_id_usuario`),
  KEY `fk_equipes_has_usuarios_equipes1_idx` (`equipes_id_equipe`),
  CONSTRAINT `fk_equipes_has_usuarios_equipes1`
    FOREIGN KEY (`equipes_id_equipe`)
    REFERENCES `equipes` (`id_equipe`)
    ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_equipes_has_usuarios_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
    ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: partidas  (status_pardida → status_partida)
-- -----------------------------------------------------------
CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL AUTO_INCREMENT,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0,
  `status_partida` enum('1','0') NOT NULL,
  PRIMARY KEY (`id_partida`),
  KEY `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe`),
  KEY `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo`),
  CONSTRAINT `fk_jogos_has_equipes_equipes1`
    FOREIGN KEY (`equipes_id_equipe`)
    REFERENCES `equipes` (`id_equipe`),
  CONSTRAINT `fk_jogos_has_equipes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: artilheiros
-- -----------------------------------------------------------
CREATE TABLE `artilheiros` (
  `id_artilheiro` int(11) NOT NULL AUTO_INCREMENT,
  `usuarios_id_usuario` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `num_gol` int(11) NOT NULL,
  PRIMARY KEY (`id_artilheiro`),
  KEY `fk_usuarios_has_jogos_jogos1_idx` (`jogos_id_jogo`),
  KEY `fk_usuarios_has_jogos_usuarios1_idx` (`usuarios_id_usuario`),
  CONSTRAINT `fk_usuarios_has_jogos_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`),
  CONSTRAINT `fk_usuarios_has_jogos_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: pontuacoes
-- -----------------------------------------------------------
CREATE TABLE `pontuacoes` (
  `id_pontuacao` int(11) NOT NULL AUTO_INCREMENT,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int(11) DEFAULT NULL,
  `jogos_id_jogo` int(11) DEFAULT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_pontuacao`),
  KEY `fk_pontuacoes_jogos1_idx` (`jogos_id_jogo`),
  KEY `fk_pontuacoes_usuarios1_idx` (`usuarios_id_usuario`),
  CONSTRAINT `fk_pontuacoes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`),
  CONSTRAINT `fk_pontuacoes_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: ocorrencias  (ocorrecia → ocorrencia)
-- -----------------------------------------------------------
CREATE TABLE `ocorrencias` (
  `id_ocorrencia` int(11) NOT NULL AUTO_INCREMENT,
  `titulo_ocorrencia` varchar(45) NOT NULL,
  `descricao_ocorrencia` longtext NOT NULL,
  `data_ocorrencia` datetime NOT NULL,
  `hora_ocorrencia` time DEFAULT NULL,
  `penalidade` int(11) DEFAULT 0,
  `status_ocorrencia` enum('1','0') NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  PRIMARY KEY (`id_ocorrencia`),
  KEY `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario`),
  CONSTRAINT `fk_ocorrencias_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- -----------------------------------------------------------
-- Tabela: usuarios_has_interclasses
-- -----------------------------------------------------------
CREATE TABLE `usuarios_has_interclasses` (
  `usuarios_id_usuario` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `dt_hr_aceita` datetime DEFAULT NULL,
  `aceito_termo` enum('sim','não') DEFAULT 'não',
  `status_termo` varchar(45) NOT NULL,
  KEY `fk_usuarios_has_interclasses_interclasses1_idx` (`interclasses_id_interclasse`),
  KEY `fk_usuarios_has_interclasses_usuarios1_idx` (`usuarios_id_usuario`),
  CONSTRAINT `fk_usuarios_has_interclasses_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`),
  CONSTRAINT `fk_usuarios_has_interclasses_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

COMMIT;
