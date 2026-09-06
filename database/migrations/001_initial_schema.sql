SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";







CREATE TABLE `artilheiros` (
  `id_artilheiro` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `num_gol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(45) NOT NULL,
  `status_categoria` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL,
  `status_equipe` enum('1','0') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL,
  `nome_equipe` varchar(90) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `equipes_has_usuarios` (
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `historico_arrecadacoes` (
  `id_historico` int(11) NOT NULL,
  `id_turma` int(11) NOT NULL,
  `id_interclasse` int(11) NOT NULL,
  `quantidade` decimal(10,2) NOT NULL,
  `pontos_adicionados` int(11) NOT NULL,
  `data_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `registrado_por` int(11) DEFAULT NULL,
  `status_historico` enum('1','0') NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `interclasses` (
  `id_interclasse` int(11) NOT NULL,
  `nome_interclasse` varchar(45) NOT NULL,
  `ano_interclasse` datetime NOT NULL,
  `regulamento_interclasse` varchar(255) NOT NULL,
  `status_interclasse` enum('1','0') NOT NULL,
  `ponto_1_lugar` int(11) NOT NULL DEFAULT 10,
  `ponto_2_lugar` int(11) NOT NULL DEFAULT 7,
  `ponto_3_lugar` int(11) NOT NULL DEFAULT 5,
  `valor_item_arrecadacao` int(11) NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
DELIMITER $$
CREATE TRIGGER `tr_atualiza_pontos_arrecadacao` AFTER UPDATE ON `interclasses` FOR EACH ROW BEGIN
    IF OLD.valor_item_arrecadacao <> NEW.valor_item_arrecadacao THEN
        UPDATE turmas
        SET pontuacao_turma = qtd_itens_arrecadados * NEW.valor_item_arrecadacao
        WHERE interclasses_id_interclasse = NEW.id_interclasse;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_sincroniza_status_usuarios` AFTER UPDATE ON `interclasses` FOR EACH ROW BEGIN
    IF NEW.status_interclasse <> OLD.status_interclasse THEN
        UPDATE usuarios
        SET status_usuario = NEW.status_interclasse
        WHERE interclasses_id_interclasse = NEW.id_interclasse
          AND nivel_usuario = '3';
    END IF;
END
$$
DELIMITER ;

CREATE TABLE `jogos` (
  `id_jogo` int(11) NOT NULL,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `termino_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `tempo_restante_jogo` int(11) DEFAULT NULL COMMENT 'Segundos restantes salvos no ultimo snapshot (pausa/save)',
  `duracao_jogo` int(11) DEFAULT NULL COMMENT 'Duracao total programada em segundos',
  `tempo_extra_jogo` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de segundos extras (acrescimos/prorrogacao)',
  `data_inicio_real` datetime DEFAULT NULL COMMENT 'Data/hora do inicio real da partida (iniciar ou retomar)',
  `modalidades_id_modalidade` int(11) NOT NULL,
  `locais_id_local` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `locais` (
  `id_local` int(11) NOT NULL,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int(11) DEFAULT NULL,
  `status_local` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `modalidades` (
  `id_modalidade` int(11) NOT NULL,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `max_inscrito_modalidade` int(11) DEFAULT NULL,
  `max_equipes` int(11) DEFAULT NULL,
  `status_modalidade` enum('1','0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` int(11) NOT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `ocorrencias` (
  `id_ocorrencia` int(11) NOT NULL,
  `titulo_ocorrencia` varchar(45) NOT NULL,
  `descricao_ocorrencia` longtext NOT NULL,
  `data_ocorrencia` datetime NOT NULL,
  `hora_ocorrencia` time DEFAULT NULL,
  `penalidade` int(11) DEFAULT 0,
  `status_ocorrencia` enum('1','0') NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `ocorrencias_turmas` (
  `id_ocorrencia_turma` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `titulo_ocorrencia` varchar(255) NOT NULL,
  `descricao_ocorrencia` longtext DEFAULT NULL,
  `pontos_descontados` int(11) NOT NULL DEFAULT 0,
  `data_ocorrencia` date NOT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0,
  `status_partida` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `pontuacoes` (
  `id_pontuacao` int(11) NOT NULL,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int(11) DEFAULT NULL,
  `jogos_id_jogo` int(11) DEFAULT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `tipos_modalidades` (
  `id_tipo_modalidade` int(11) NOT NULL,
  `nome_tipo_modalidade` varchar(45) NOT NULL,
  `status_tipo_modalidade` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `turmas` (
  `id_turma` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `nome_turma` varchar(45) NOT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') NOT NULL,
  `nome_fantasia_turma` varchar(45),
  `status_turma` enum('1','0') NOT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `pontuacao_turma` int(11) NOT NULL DEFAULT 0,
  `qtd_itens_arrecadados` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `sigla_usuario` enum('RM','SS','SN') NOT NULL,
  `matricula_usuario` varchar(45) NOT NULL,
  `nome_usuario` varchar(45) NOT NULL,
  `senha_usuario` varchar(200) NOT NULL,
  `nivel_usuario` enum('0','1','2','3') NOT NULL DEFAULT '0',
  `genero_usuario` enum('FEM','MASC') NOT NULL,
  `data_nasc_usuario` date NOT NULL,
  `foto_usuario` varchar(255) NOT NULL,
  `status_usuario` enum('0','1') NOT NULL,
  `turmas_id_turma` int(11) DEFAULT NULL,
  `interclasses_id_interclasse` int(11) DEFAULT NULL,
  `chave_usuario_edicao` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE `usuarios_has_interclasses` (
  `usuarios_id_usuario` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `dt_hr_aceita` datetime DEFAULT NULL,
  `aceito_termo` enum('sim','não') DEFAULT 'não',
  `status_termo` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
ALTER TABLE `artilheiros`
  ADD PRIMARY KEY (`id_artilheiro`),
  ADD KEY `fk_usuarios_has_jogos_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `fk_usuarios_has_jogos_usuarios1_idx` (`usuarios_id_usuario`);
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `fk_categorias_interclasses1_idx` (`interclasses_id_interclasse`);
ALTER TABLE `equipes`
  ADD PRIMARY KEY (`id_equipe`),
  ADD KEY `fk_equipes_modalidades1_idx` (`modalidades_id_modalidade`),
  ADD KEY `fk_equipes_turmas1_idx` (`turmas_id_turma`);
ALTER TABLE `equipes_has_usuarios`
  ADD PRIMARY KEY (`equipes_id_equipe`,`usuarios_id_usuario`),
  ADD KEY `fk_equipes_has_usuarios_usuarios1_idx` (`usuarios_id_usuario`),
  ADD KEY `fk_equipes_has_usuarios_equipes1_idx` (`equipes_id_equipe`);
ALTER TABLE `historico_arrecadacoes`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `fk_hist_arrec_turmas_idx` (`id_turma`),
  ADD KEY `fk_hist_arrec_interclasses_idx` (`id_interclasse`),
  ADD KEY `fk_hist_arrec_usuarios_idx` (`registrado_por`);
ALTER TABLE `interclasses`
  ADD PRIMARY KEY (`id_interclasse`);
ALTER TABLE `jogos`
  ADD PRIMARY KEY (`id_jogo`),
  ADD KEY `fk_jogos_modalidades1_idx` (`modalidades_id_modalidade`),
  ADD KEY `fk_jogos_locais1_idx` (`locais_id_local`);
ALTER TABLE `locais`
  ADD PRIMARY KEY (`id_local`),
  ADD KEY `fk_locais_interclasses_idx` (`interclasses_id_interclasse`);
ALTER TABLE `modalidades`
  ADD PRIMARY KEY (`id_modalidade`),
  ADD KEY `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade`),
  ADD KEY `fk_modalidades_categorias1_idx` (`categorias_id_categoria`),
  ADD KEY `fk_modalidades_interclasses1_idx` (`interclasses_id_interclasse`);
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id_ocorrencia`),
  ADD KEY `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario`);
ALTER TABLE `ocorrencias_turmas`
  ADD PRIMARY KEY (`id_ocorrencia_turma`),
  ADD KEY `idx_turma` (`turmas_id_turma`),
  ADD KEY `idx_interclasse` (`interclasses_id_interclasse`),
  ADD KEY `fk_ot_usuarios` (`usuarios_id_usuario`);
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`),
  ADD KEY `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe`),
  ADD KEY `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `idx_partidas_usuarios` (`usuarios_id_usuario`);
ALTER TABLE `pontuacoes`
  ADD PRIMARY KEY (`id_pontuacao`),
  ADD KEY `fk_pontuacoes_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `fk_pontuacoes_usuarios1_idx` (`usuarios_id_usuario`);
ALTER TABLE `tipos_modalidades`
  ADD PRIMARY KEY (`id_tipo_modalidade`);
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id_turma`),
  ADD KEY `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse`),
  ADD KEY `fk_turmas_categorias1_idx` (`categorias_id_categoria`);
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `chave_usuario_edicao_UNIQUE` (`chave_usuario_edicao`),
  ADD UNIQUE KEY `uk_matricula_interclasse` (`matricula_usuario`,`interclasses_id_interclasse`),
  ADD KEY `fk_usuarios_turmas1_idx` (`turmas_id_turma`),
  ADD KEY `fk_usuarios_interclasses1_idx` (`interclasses_id_interclasse`);
ALTER TABLE `usuarios_has_interclasses`
  ADD KEY `fk_usuarios_has_interclasses_interclasses1_idx` (`interclasses_id_interclasse`),
  ADD KEY `fk_usuarios_has_interclasses_usuarios1_idx` (`usuarios_id_usuario`);
ALTER TABLE `artilheiros`
  MODIFY `id_artilheiro` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `equipes`
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `historico_arrecadacoes`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `jogos`
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `locais`
  MODIFY `id_local` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrencia` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `ocorrencias_turmas`
  MODIFY `id_ocorrencia_turma` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
ALTER TABLE `turmas`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `artilheiros`
  ADD CONSTRAINT `fk_usuarios_has_jogos_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`),
  ADD CONSTRAINT `fk_usuarios_has_jogos_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_equipes_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`),
  ADD CONSTRAINT `fk_equipes_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`);
ALTER TABLE `equipes_has_usuarios`
  ADD CONSTRAINT `fk_equipes_has_usuarios_equipes1` FOREIGN KEY (`equipes_id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_equipes_has_usuarios_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `historico_arrecadacoes`
  ADD CONSTRAINT `fk_hist_arrec_interclasses` FOREIGN KEY (`id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_hist_arrec_turmas` FOREIGN KEY (`id_turma`) REFERENCES `turmas` (`id_turma`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_hist_arrec_usuarios` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `jogos`
  ADD CONSTRAINT `fk_jogos_locais1` FOREIGN KEY (`locais_id_local`) REFERENCES `locais` (`id_local`),
  ADD CONSTRAINT `fk_jogos_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`);
ALTER TABLE `locais`
  ADD CONSTRAINT `fk_locais_interclasses` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `modalidades`
  ADD CONSTRAINT `fk_modalidades_categorias1` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`),
  ADD CONSTRAINT `fk_modalidades_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_modalidades_tipos_modalidades1` FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`) REFERENCES `tipos_modalidades` (`id_tipo_modalidade`);
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `fk_ocorrencias_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
ALTER TABLE `ocorrencias_turmas`
  ADD CONSTRAINT `fk_ot_interclasses` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`),
  ADD CONSTRAINT `fk_ot_turmas` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`),
  ADD CONSTRAINT `fk_ot_usuarios` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
ALTER TABLE `partidas`
  ADD CONSTRAINT `fk_jogos_has_equipes_equipes1` FOREIGN KEY (`equipes_id_equipe`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_jogos_has_equipes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`);
ALTER TABLE `pontuacoes`
  ADD CONSTRAINT `fk_pontuacoes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`),
  ADD CONSTRAINT `fk_pontuacoes_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
ALTER TABLE `turmas`
  ADD CONSTRAINT `fk_turmas_categorias1` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`),
  ADD CONSTRAINT `fk_turmas_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`);
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuarios_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`);
ALTER TABLE `usuarios_has_interclasses`
  ADD CONSTRAINT `fk_usuarios_has_interclasses_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`),
  ADD CONSTRAINT `fk_usuarios_has_interclasses_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
CREATE TABLE `sincronizacoes_idempotentes` (
  `id_sincronizacao` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rota` varchar(80) NOT NULL,
  `chave_mutacao` varchar(180) NOT NULL,
  `status_http` smallint(5) UNSIGNED NOT NULL DEFAULT 200,
  `resposta_json` longtext NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sincronizacao`),
  UNIQUE KEY `uk_sincronizacao_rota_chave` (`rota`, `chave_mutacao`),
  KEY `idx_sincronizacao_criado` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
