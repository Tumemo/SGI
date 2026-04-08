-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE SCHEMA IF NOT EXISTS `sgi` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `sgi` ;

CREATE TABLE IF NOT EXISTS `locais` (
  `id_local` INT NOT NULL AUTO_INCREMENT,
  `nome_local` VARCHAR(45) NOT NULL,
  `disponivel_local` ENUM('0', '1') NOT NULL DEFAULT '1',
  `carga_local` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id_local`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id_categoria` INT NOT NULL AUTO_INCREMENT,
  `nome_categoria` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tipos_modalidades` (
  `id_tipo_modalidade` INT NOT NULL AUTO_INCREMENT,
  `nome_tipo_modalidade` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id_tipo_modalidade`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `modalidades` (
  `id_modalidade` INT NOT NULL AUTO_INCREMENT,
  `nome_modalidade` VARCHAR(45) NOT NULL,
  `genero_modalidade` ENUM('FEM', 'MASC', 'MISTO') NOT NULL,
  `max_inscrito_modalidade` INT NULL DEFAULT NULL,
  `status_modalidade` ENUM('1', '0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` INT NOT NULL,
  `categorias_id_categoria` INT NOT NULL,
  PRIMARY KEY (`id_modalidade`),
  INDEX `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade` ASC),
  INDEX `fk_modalidades_categorias1_idx` (`categorias_id_categoria` ASC),
  CONSTRAINT `fk_modalidades_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `fk_modalidades_tipos_modalidades1`
    FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`)
    REFERENCES `tipos_modalidades` (`id_tipo_modalidade`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `jogos` (
  `id_jogo` INT NOT NULL AUTO_INCREMENT,
  `nome_jogo` VARCHAR(45) NOT NULL,
  `data_jogo` DATE NOT NULL,
  `inicio_jogo` TIME NOT NULL,
  `termino_jogo` TIME NULL DEFAULT NULL,
  `status_jogo` ENUM('Agendado', 'Iniciado', 'Pausado', 'Concluido') NOT NULL,
  `modalidades_id_modalidade` INT NOT NULL,
  `locais_id_local` INT NOT NULL,
  PRIMARY KEY (`id_jogo`),
  INDEX `fk_jogos_modalidades1_idx` (`modalidades_id_modalidade` ASC),
  INDEX `fk_jogos_locais1_idx` (`locais_id_local` ASC),
  CONSTRAINT `fk_jogos_locais1`
    FOREIGN KEY (`locais_id_local`)
    REFERENCES `locais` (`id_local`),
  CONSTRAINT `fk_jogos_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `modalidades` (`id_modalidade`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `interclasses` (
  `id_interclasse` INT NOT NULL AUTO_INCREMENT,
  `nome_interclasse` VARCHAR(45) NULL DEFAULT NULL,
  `ano_interclasse` DATETIME NULL DEFAULT NULL,
  `regulamento_interclasse` VARCHAR(255) NULL DEFAULT NULL,
  `status_interclasse` ENUM('1', '0') NOT NULL,
  PRIMARY KEY (`id_interclasse`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `turmas` (
  `id_turma` INT NOT NULL AUTO_INCREMENT,
  `interclasses_id_interclasse` INT NOT NULL,
  `nome_turma` VARCHAR(45) NULL DEFAULT NULL,
  `turno_turma` ENUM('manha', 'tarde', 'noite', 'integral') NULL DEFAULT NULL,
  `nome_fantasia_turma` VARCHAR(45) NULL DEFAULT NULL,
  `categorias_id_categoria` INT NOT NULL,
  PRIMARY KEY (`id_turma`),
  INDEX `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse` ASC),
  INDEX `fk_turmas_categorias1_idx` (`categorias_id_categoria` ASC),
  CONSTRAINT `fk_turmas_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `fk_turmas_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT,
  `sigla_usuario` ENUM('RM', 'SS', 'SN') NULL DEFAULT NULL,
  `matricula_usuario` VARCHAR(20) NOT NULL,
  `nome_usuario` VARCHAR(45) NOT NULL,
  `senha_usuario` VARCHAR(200) NOT NULL,
  `nivel_usuario` ENUM('0', '1', '2') NOT NULL DEFAULT '0',
  `competidor_usuario` ENUM('0', '1') NOT NULL DEFAULT '0',
  `mesario_usuario` ENUM('0', '1') NOT NULL DEFAULT '0',
  `genero_usuario` ENUM('FEM', 'MASC') NOT NULL,
  `data_nasc_usuario` DATE NOT NULL,
  `status_usuario` ENUM('0', '1') NOT NULL,
  `turmas_id_turma` INT NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `matricula_usuario_UNIQUE` (`matricula_usuario` ASC),
  INDEX `fk_usuarios_turmas1_idx` (`turmas_id_turma` ASC),
  CONSTRAINT `fk_usuarios_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `turmas` (`id_turma`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `artilheiros` (
  `id_artilheiro` INT NOT NULL AUTO_INCREMENT,
  `usuarios_id_usuario` INT NOT NULL,
  `jogos_id_jogo` INT NOT NULL,
  `num_gol` INT NOT NULL,
  PRIMARY KEY (`id_artilheiro`),
  INDEX `fk_usuarios_has_jogos_jogos1_idx` (`jogos_id_jogo` ASC),
  INDEX `fk_usuarios_has_jogos_usuarios1_idx` (`usuarios_id_usuario` ASC),
  CONSTRAINT `fk_usuarios_has_jogos_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`),
  CONSTRAINT `fk_usuarios_has_jogos_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `equipes` (
  `id_equipe` INT NOT NULL AUTO_INCREMENT,
  `modalidades_id_modalidade` INT NOT NULL,
  `usuarios_id_usuario1` INT NOT NULL,
  `turmas_id_turma` INT NOT NULL,
  PRIMARY KEY (`id_equipe`),
  INDEX `fk_equipes_modalidades1_idx` (`modalidades_id_modalidade` ASC),
  INDEX `fk_equipes_usuarios1_idx` (`usuarios_id_usuario1` ASC),
  INDEX `fk_equipes_turmas1_idx` (`turmas_id_turma` ASC),
  CONSTRAINT `fk_equipes_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `modalidades` (`id_modalidade`),
  CONSTRAINT `fk_equipes_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `turmas` (`id_turma`),
  CONSTRAINT `fk_equipes_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario1`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `ocorrencias` (
  `id_ocorrecia` INT NOT NULL AUTO_INCREMENT,
  `titulo_ocorrecia` VARCHAR(45) NOT NULL,
  `descricao_ocorrecia` LONGTEXT NOT NULL,
  `data_ocorrecia` DATETIME NOT NULL,
  `penalidade` INT NULL DEFAULT '0',
  `usuarios_id_usuario` INT NOT NULL,
  PRIMARY KEY (`id_ocorrecia`),
  INDEX `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario` ASC),
  CONSTRAINT `fk_ocorrencias_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `partidas` (
  `id_partida` INT NOT NULL AUTO_INCREMENT,
  `jogos_id_jogo` INT NOT NULL,
  `equipes_id_equipe` INT NOT NULL,
  `resultado_partida` INT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_partida`),
  INDEX `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe` ASC),
  INDEX `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo` ASC),
  CONSTRAINT `fk_jogos_has_equipes_equipes1`
    FOREIGN KEY (`equipes_id_equipe`)
    REFERENCES `equipes` (`id_equipe`),
  CONSTRAINT `fk_jogos_has_equipes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `pontuacoes` (
  `id_pontuacao` INT NOT NULL AUTO_INCREMENT,
  `nome_pontuacao` VARCHAR(45) NULL DEFAULT NULL,
  `valor_pontuacao` INT NULL DEFAULT NULL,
  `jogos_id_jogo` INT NULL DEFAULT NULL,
  `usuarios_id_usuario` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id_pontuacao`),
  INDEX `fk_pontuacoes_jogos1_idx` (`jogos_id_jogo` ASC),
  INDEX `fk_pontuacoes_usuarios1_idx` (`usuarios_id_usuario` ASC),
  CONSTRAINT `fk_pontuacoes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `jogos` (`id_jogo`),
  CONSTRAINT `fk_pontuacoes_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

CREATE TABLE IF NOT EXISTS `usuarios_has_interclasses` (
  `usuarios_id_usuario` INT NOT NULL,
  `interclasses_id_interclasse` INT NOT NULL,
  `dt_hr_aceita` DATETIME NULL DEFAULT NULL,
  `aceito_termos` ENUM('sim', 'não') NULL DEFAULT 'não',
  INDEX `fk_usuarios_has_interclasses_interclasses1_idx` (`interclasses_id_interclasse` ASC),
  INDEX `fk_usuarios_has_interclasses_usuarios1_idx` (`usuarios_id_usuario` ASC),
  CONSTRAINT `fk_usuarios_has_interclasses_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `interclasses` (`id_interclasse`),
  CONSTRAINT `fk_usuarios_has_interclasses_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;