-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema sgi
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema sgi
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `sgi` DEFAULT CHARACTER SET utf8mb4 ;
USE `sgi` ;

-- -----------------------------------------------------
-- Table `sgi`.`locais`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`locais` (
  `id_local` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_local` VARCHAR(45) NOT NULL,
  `disponivel_local` ENUM('0', '1') NOT NULL DEFAULT '1',
  `carga_local` INT(11) NULL DEFAULT NULL,
  `status_local` ENUM('1', '0') NOT NULL,
  PRIMARY KEY (`id_local`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`interclasses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`interclasses` (
  `id_interclasse` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_interclasse` VARCHAR(45) NULL DEFAULT NULL,
  `ano_interclasse` DATETIME NULL DEFAULT NULL,
  `regulamento_interclasse` VARCHAR(255) NULL DEFAULT NULL,
  `status_interclasse` ENUM('1', '0') NOT NULL,
  PRIMARY KEY (`id_interclasse`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sgi`.`categorias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`categorias` (
  `id_categoria` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_categoria` VARCHAR(45) NULL DEFAULT NULL,
  `status_categoria` ENUM('1', '0') NOT NULL,
  `interclasses_id_interclasse` INT(11) NOT NULL,
  PRIMARY KEY (`id_categoria`),
  INDEX `fk_categorias_interclasses1_idx` (`interclasses_id_interclasse` ASC) ,
  CONSTRAINT `fk_categorias_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `sgi`.`interclasses` (`id_interclasse`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sgi`.`tipos_modalidades`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`tipos_modalidades` (
  `id_tipo_modalidade` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_tipo_modalidade` VARCHAR(45) NOT NULL,
  `status_tipo_modalidade` ENUM('1', '0') NOT NULL,
  PRIMARY KEY (`id_tipo_modalidade`))
ENGINE = InnoDB
AUTO_INCREMENT = 5
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`modalidades`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`modalidades` (
  `id_modalidade` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_modalidade` VARCHAR(45) NOT NULL,
  `genero_modalidade` ENUM('FEM', 'MASC', 'MISTO') NOT NULL,
  `max_inscrito_modalidade` INT(11) NULL DEFAULT NULL,
  `status_modalidade` ENUM('1', '0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` INT(11) NOT NULL,
  `categorias_id_categoria` INT(11) NOT NULL,
  `interclasses_id_interclasse` INT(11) NOT NULL,
  PRIMARY KEY (`id_modalidade`),
  INDEX `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade` ASC) ,
  INDEX `fk_modalidades_categorias1_idx` (`categorias_id_categoria` ASC) ,
  INDEX `fk_modalidades_interclasses1_idx` (`interclasses_id_interclasse` ASC) ,
  CONSTRAINT `fk_modalidades_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `sgi`.`categorias` (`id_categoria`),
  CONSTRAINT `fk_modalidades_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `sgi`.`interclasses` (`id_interclasse`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_modalidades_tipos_modalidades1`
    FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`)
    REFERENCES `sgi`.`tipos_modalidades` (`id_tipo_modalidade`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`jogos`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`jogos` (
  `id_jogo` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_jogo` VARCHAR(45) NOT NULL,
  `data_jogo` DATE NOT NULL,
  `inicio_jogo` TIME NOT NULL,
  `termino_jogo` TIME NULL DEFAULT NULL,
  `status_jogo` ENUM('Agendado', 'Iniciado', 'Pausado', 'Concluido') NOT NULL,
  `modalidades_id_modalidade` INT(11) NOT NULL,
  `locais_id_local` INT(11) NOT NULL,
  PRIMARY KEY (`id_jogo`),
  INDEX `fk_jogos_modalidades1_idx` (`modalidades_id_modalidade` ASC) ,
  INDEX `fk_jogos_locais1_idx` (`locais_id_local` ASC) ,
  CONSTRAINT `fk_jogos_locais1`
    FOREIGN KEY (`locais_id_local`)
    REFERENCES `sgi`.`locais` (`id_local`),
  CONSTRAINT `fk_jogos_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `sgi`.`modalidades` (`id_modalidade`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`turmas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`turmas` (
  `id_turma` INT(11) NOT NULL AUTO_INCREMENT,
  `interclasses_id_interclasse` INT(11) NOT NULL,
  `nome_turma` VARCHAR(45) NULL DEFAULT NULL,
  `turno_turma` ENUM('manha', 'tarde', 'noite', 'integral') NULL DEFAULT NULL,
  `nome_fantasia_turma` VARCHAR(45) NULL DEFAULT NULL,
  `status_turma` ENUM('1', '0') NULL DEFAULT NULL,
  `categorias_id_categoria` INT(11) NOT NULL,
  PRIMARY KEY (`id_turma`),
  INDEX `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse` ASC) ,
  INDEX `fk_turmas_categorias1_idx` (`categorias_id_categoria` ASC) ,
  CONSTRAINT `fk_turmas_categorias1`
    FOREIGN KEY (`categorias_id_categoria`)
    REFERENCES `sgi`.`categorias` (`id_categoria`),
  CONSTRAINT `fk_turmas_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `sgi`.`interclasses` (`id_interclasse`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4;


-- -----------------------------------------------------
-- Table `sgi`.`usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`usuarios` (
  `id_usuario` INT(11) NOT NULL AUTO_INCREMENT,
  `sigla_usuario` ENUM('RM', 'SS', 'SN') NULL DEFAULT NULL,
  `matricula_usuario` VARCHAR(20) NOT NULL,
  `nome_usuario` VARCHAR(45) NOT NULL,
  `senha_usuario` VARCHAR(200) NOT NULL,
  `nivel_usuario` ENUM('0', '1', '2') NOT NULL DEFAULT '0',
  `competidor_usuario` ENUM('0', '1') NOT NULL DEFAULT '0',
  `mesario_usuario` ENUM('0', '1') NOT NULL DEFAULT '0',
  `genero_usuario` ENUM('FEM', 'MASC') NOT NULL,
  `data_nasc_usuario` DATE NOT NULL,
  `foto_usuario` VARCHAR(255) NOT NULL,
  `status_usuario` ENUM('0', '1') NOT NULL,
  `turmas_id_turma` INT(11) NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE INDEX `matricula_usuario_UNIQUE` (`matricula_usuario` ASC) ,
  UNIQUE INDEX `nome_usuario` (`nome_usuario` ASC) ,
  UNIQUE INDEX `senha_usuario` (`senha_usuario` ASC) ,
  INDEX `fk_usuarios_turmas1_idx` (`turmas_id_turma` ASC) ,
  CONSTRAINT `fk_usuarios_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `sgi`.`turmas` (`id_turma`))
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`artilheiros`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`artilheiros` (
  `id_artilheiro` INT(11) NOT NULL AUTO_INCREMENT,
  `usuarios_id_usuario` INT(11) NOT NULL,
  `jogos_id_jogo` INT(11) NOT NULL,
  `num_gol` INT(11) NOT NULL,
  PRIMARY KEY (`id_artilheiro`),
  INDEX `fk_usuarios_has_jogos_jogos1_idx` (`jogos_id_jogo` ASC) ,
  INDEX `fk_usuarios_has_jogos_usuarios1_idx` (`usuarios_id_usuario` ASC) ,
  CONSTRAINT `fk_usuarios_has_jogos_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `sgi`.`jogos` (`id_jogo`),
  CONSTRAINT `fk_usuarios_has_jogos_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sgi`.`usuarios` (`id_usuario`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`equipes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`equipes` (
  `id_equipe` INT(11) NOT NULL AUTO_INCREMENT,
  `status_equipe` ENUM('1', '0') NOT NULL,
  `modalidades_id_modalidade` INT(11) NOT NULL,
  `turmas_id_turma` INT(11) NOT NULL,
  PRIMARY KEY (`id_equipe`),
  INDEX `fk_equipes_modalidades1_idx` (`modalidades_id_modalidade` ASC) ,
  INDEX `fk_equipes_turmas1_idx` (`turmas_id_turma` ASC) ,
  CONSTRAINT `fk_equipes_modalidades1`
    FOREIGN KEY (`modalidades_id_modalidade`)
    REFERENCES `sgi`.`modalidades` (`id_modalidade`),
  CONSTRAINT `fk_equipes_turmas1`
    FOREIGN KEY (`turmas_id_turma`)
    REFERENCES `sgi`.`turmas` (`id_turma`))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`equipes_has_usuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`equipes_has_usuarios` (
  `equipes_id_equipe` INT(11) NOT NULL,
  `usuarios_id_usuario` INT(11) NOT NULL,
  PRIMARY KEY (`equipes_id_equipe`, `usuarios_id_usuario`),
  INDEX `fk_equipes_has_usuarios_usuarios1_idx` (`usuarios_id_usuario` ASC) ,
  INDEX `fk_equipes_has_usuarios_equipes1_idx` (`equipes_id_equipe` ASC) ,
  CONSTRAINT `fk_equipes_has_usuarios_equipes1`
    FOREIGN KEY (`equipes_id_equipe`)
    REFERENCES `sgi`.`equipes` (`id_equipe`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_equipes_has_usuarios_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sgi`.`usuarios` (`id_usuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`ocorrencias`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`ocorrencias` (
  `id_ocorrecia` INT(11) NOT NULL AUTO_INCREMENT,
  `titulo_ocorrecia` VARCHAR(45) NOT NULL,
  `descricao_ocorrecia` LONGTEXT NOT NULL,
  `data_ocorrecia` DATETIME NOT NULL,
  `penalidade` INT(11) NULL DEFAULT 0,
  `status_ocorrencia` ENUM('1', '0') NOT NULL,
  `usuarios_id_usuario` INT(11) NOT NULL,
  PRIMARY KEY (`id_ocorrecia`),
  INDEX `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario` ASC) ,
  CONSTRAINT `fk_ocorrencias_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sgi`.`usuarios` (`id_usuario`))
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`partidas`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`partidas` (
  `id_partida` INT(11) NOT NULL AUTO_INCREMENT,
  `jogos_id_jogo` INT(11) NOT NULL,
  `equipes_id_equipe` INT(11) NOT NULL,
  `resultado_partida` INT(11) NOT NULL DEFAULT 0,
  `status_pardida` ENUM('1', '0') NOT NULL,
  PRIMARY KEY (`id_partida`),
  INDEX `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe` ASC) ,
  INDEX `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo` ASC) ,
  CONSTRAINT `fk_jogos_has_equipes_equipes1`
    FOREIGN KEY (`equipes_id_equipe`)
    REFERENCES `sgi`.`equipes` (`id_equipe`),
  CONSTRAINT `fk_jogos_has_equipes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `sgi`.`jogos` (`id_jogo`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`pontuacoes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`pontuacoes` (
  `id_pontuacao` INT(11) NOT NULL AUTO_INCREMENT,
  `nome_pontuacao` VARCHAR(45) NULL DEFAULT NULL,
  `valor_pontuacao` INT(11) NULL DEFAULT NULL,
  `jogos_id_jogo` INT(11) NULL DEFAULT NULL,
  `usuarios_id_usuario` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id_pontuacao`),
  INDEX `fk_pontuacoes_jogos1_idx` (`jogos_id_jogo` ASC) ,
  INDEX `fk_pontuacoes_usuarios1_idx` (`usuarios_id_usuario` ASC) ,
  CONSTRAINT `fk_pontuacoes_jogos1`
    FOREIGN KEY (`jogos_id_jogo`)
    REFERENCES `sgi`.`jogos` (`id_jogo`),
  CONSTRAINT `fk_pontuacoes_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sgi`.`usuarios` (`id_usuario`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `sgi`.`usuarios_has_interclasses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `sgi`.`usuarios_has_interclasses` (
  `usuarios_id_usuario` INT(11) NOT NULL,
  `interclasses_id_interclasse` INT(11) NOT NULL,
  `dt_hr_aceita` DATETIME NULL DEFAULT NULL,
  `aceito_termo` ENUM('sim', 'não') NULL DEFAULT 'não',
  `status_termo` VARCHAR(45) NOT NULL,
  INDEX `fk_usuarios_has_interclasses_interclasses1_idx` (`interclasses_id_interclasse` ASC) ,
  INDEX `fk_usuarios_has_interclasses_usuarios1_idx` (`usuarios_id_usuario` ASC) ,
  CONSTRAINT `fk_usuarios_has_interclasses_interclasses1`
    FOREIGN KEY (`interclasses_id_interclasse`)
    REFERENCES `sgi`.`interclasses` (`id_interclasse`),
  CONSTRAINT `fk_usuarios_has_interclasses_usuarios1`
    FOREIGN KEY (`usuarios_id_usuario`)
    REFERENCES `sgi`.`usuarios` (`id_usuario`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
