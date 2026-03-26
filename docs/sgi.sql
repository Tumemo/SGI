-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 26/03/2026 às 17:59
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sgi`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `artilheiros`
--

CREATE TABLE `artilheiros` (
  `id_artilheiro` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `num_gol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `artilheiros`
--

INSERT INTO `artilheiros` (`id_artilheiro`, `usuarios_id_usuario`, `jogos_id_jogo`, `num_gol`) VALUES
(1, 1, 1, 2),
(2, 3, 1, 1),
(3, 5, 5, 5),
(4, 1, 2, 0),
(5, 2, 2, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nome_categoria`) VALUES
(1, 'Sub-15'),
(2, 'Sub-17'),
(3, 'Sub-19'),
(4, 'Livre'),
(5, 'E-Sports');

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `usuarios_id_usuario1` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `equipes`
--

INSERT INTO `equipes` (`id_equipe`, `modalidades_id_modalidade`, `usuarios_id_usuario1`, `turmas_id_turma`) VALUES
(1, 1, 1, 1),
(2, 1, 3, 3),
(3, 2, 2, 2),
(4, 2, 4, 4),
(5, 5, 5, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `interclasses`
--

CREATE TABLE `interclasses` (
  `id_interclasse` int(11) NOT NULL,
  `nome_interclasse` varchar(45) DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `interclasses`
--

INSERT INTO `interclasses` (`id_interclasse`, `nome_interclasse`, `ano_interclasse`) VALUES
(1, 'Interclasse 2025', '2025-01-01 00:00:00'),
(2, 'Interclasse 2026', '2026-01-01 00:00:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogos`
--

CREATE TABLE `jogos` (
  `id_jogo` int(11) NOT NULL,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `terminno_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `locais_id_local` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `jogos`
--

INSERT INTO `jogos` (`id_jogo`, `nome_jogo`, `data_jogo`, `inicio_jogo`, `terminno_jogo`, `status_jogo`, `modalidades_id_modalidade`, `locais_id_local`) VALUES
(1, 'Futsal 1A x 2A', '2025-10-10', '08:00:00', '09:00:00', 'Concluido', 1, 1),
(2, 'Vôlei 2A x 3A', '2025-10-10', '09:00:00', '10:00:00', 'Agendado', 2, 2),
(3, 'Tênis Mesa Livre', '2025-10-11', '10:00:00', '11:00:00', 'Agendado', 3, 3),
(4, 'Xadrez Livre', '2026-10-12', '14:00:00', '15:00:00', 'Iniciado', 4, 3),
(5, 'FIFA Final', '2026-10-13', '15:00:00', '16:00:00', 'Agendado', 5, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id_local` int(11) NOT NULL,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id_local`, `nome_local`, `disponivel_local`, `carga_local`) VALUES
(1, 'Quadra Poliesportiva 1', '1', 100),
(2, 'Quadra Poliesportiva 2', '1', 80),
(3, 'Sala de Jogos', '1', 30),
(4, 'Laboratório de Informática', '1', 40),
(5, 'Campo de Futebol', '0', 200);

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidades`
--

CREATE TABLE `modalidades` (
  `id_modalidade` int(11) NOT NULL,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `categoria_modalidade` varchar(45) DEFAULT NULL,
  `max_inscrito_modalidade` int(11) DEFAULT NULL,
  `tipos_modalidades_id_tipo_modalidade` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `modalidades`
--

INSERT INTO `modalidades` (`id_modalidade`, `nome_modalidade`, `genero_modalidade`, `categoria_modalidade`, `max_inscrito_modalidade`, `tipos_modalidades_id_tipo_modalidade`) VALUES
(1, 'Futsal', 'MASC', 'Sub-17', 10, 1),
(2, 'Vôlei', 'FEM', 'Sub-17', 12, 1),
(3, 'Tênis de Mesa', 'MISTO', 'Livre', 2, 3),
(4, 'Xadrez', 'MISTO', 'Livre', 1, 4),
(5, 'FIFA 24', 'MISTO', 'E-Sports', 1, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id_ocorrecia` int(11) NOT NULL,
  `titulo_ocorrecia` varchar(45) NOT NULL,
  `descricao_ocorrecia` longtext NOT NULL,
  `data_ocorrecia` date NOT NULL,
  `hora_ocorrecia` time NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL,
  `penalidade` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id_ocorrecia`, `titulo_ocorrecia`, `descricao_ocorrecia`, `data_ocorrecia`, `hora_ocorrecia`, `usuarios_id_usuario`, `penalidade`) VALUES
(1, 'Atraso', 'Equipe chegou atrasada na quadra.', '2025-10-10', '08:15:00', 1, 1),
(2, 'Reclamação', 'Atleta xingou o árbitro.', '2025-10-10', '08:45:00', 3, 5),
(3, 'Uniforme Incompleto', 'Atleta entrou sem caneleira.', '2026-10-12', '14:00:00', 4, 2),
(4, 'Comemoração', 'Tirou a camisa na comemoração do gol.', '2026-10-13', '15:30:00', 5, 1),
(5, 'Conduta Antidesportiva', 'Jogou a bola na arquibancada propositalmente.', '2025-10-11', '10:30:00', 2, 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `partidas`
--

INSERT INTO `partidas` (`id_partida`, `jogos_id_jogo`, `equipes_id_equipe`, `resultado_partida`) VALUES
(1, 1, 1, 3),
(2, 1, 2, 1),
(3, 2, 3, 0),
(4, 2, 4, 0),
(5, 5, 5, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pontuacoes`
--

CREATE TABLE `pontuacoes` (
  `id_pontuacao` int(11) NOT NULL,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int(11) DEFAULT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `pontuacoes`
--

INSERT INTO `pontuacoes` (`id_pontuacao`, `nome_pontuacao`, `valor_pontuacao`, `jogos_id_jogo`, `usuarios_id_usuario`) VALUES
(1, 'Vitória Futsal', 3, 1, 1),
(2, 'Participação Futsal', 1, 1, 3),
(3, 'MVP Vôlei', 5, 2, 2),
(4, 'Fair Play Xadrez', 2, 4, 4),
(5, 'Campeão FIFA', 10, 5, 5);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_modalidades`
--

CREATE TABLE `tipos_modalidades` (
  `id_tipo_modalidade` int(11) NOT NULL,
  `nome_tipo_modalidade` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `tipos_modalidades`
--

INSERT INTO `tipos_modalidades` (`id_tipo_modalidade`, `nome_tipo_modalidade`) VALUES
(1, 'Quadra'),
(2, 'Campo'),
(3, 'Mesa'),
(4, 'Tabuleiro'),
(5, 'Virtual');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id_turma` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `nome_turma` varchar(45) DEFAULT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') DEFAULT NULL,
  `cat_turma` varchar(45) DEFAULT NULL,
  `nome_fantasia_turma` varchar(45) DEFAULT NULL,
  `pontuacao_turma` int(11) DEFAULT NULL,
  `categorias_id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id_turma`, `interclasses_id_interclasse`, `nome_turma`, `turno_turma`, `cat_turma`, `nome_fantasia_turma`, `pontuacao_turma`, `categorias_id_categoria`) VALUES
(1, 1, '1º Ano A', 'manha', 'Sub-15', 'Os Feras', 0, 1),
(2, 1, '2º Ano A', 'manha', 'Sub-17', 'Titãs', 10, 2),
(3, 1, '3º Ano A', 'manha', 'Sub-19', 'Invictos', 20, 3),
(4, 2, '1º Ano B', 'tarde', 'Sub-15', 'Relâmpagos', 0, 1),
(5, 2, '2º Ano B', 'tarde', 'Sub-17', 'Trovão', 5, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `sigla_usuario` enum('RM','SS','SN') DEFAULT NULL,
  `matricula_usuario` varchar(20) NOT NULL,
  `nome_usuario` varchar(45) NOT NULL,
  `senha_usuario` varchar(200) NOT NULL,
  `foto_ususario` varchar(255) DEFAULT 'default.jpg',
  `nivel_usuario` enum('0','1','2') NOT NULL DEFAULT '0',
  `competidor_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `mesario_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `genero_usuario` enum('FEM','MASC') NOT NULL,
  `data_nasc_usuario` date NOT NULL,
  `turmas_id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `foto_ususario`, `nivel_usuario`, `competidor_usuario`, `mesario_usuario`, `genero_usuario`, `data_nasc_usuario`, `turmas_id_turma`) VALUES
(1, 'RM', '12345', 'João Silva', 'senha123', 'default.jpg', '0', '1', '0', 'MASC', '2008-05-10', 1),
(2, 'RM', '12346', 'Maria Souza', 'senha123', 'default.jpg', '1', '1', '1', 'FEM', '2007-08-20', 2),
(3, 'RM', '12347', 'Pedro Santos', 'senha123', 'default.jpg', '0', '1', '0', 'MASC', '2006-11-15', 3),
(4, 'RM', '12348', 'Ana Lima', 'senha123', 'default.jpg', '2', '1', '1', 'FEM', '2008-02-25', 4),
(5, 'RM', '12349', 'Lucas Oliveira', 'senha123', 'default.jpg', '0', '1', '0', 'MASC', '2007-12-05', 5);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `artilheiros`
--
ALTER TABLE `artilheiros`
  ADD PRIMARY KEY (`id_artilheiro`),
  ADD KEY `fk_usuarios_has_jogos_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `fk_usuarios_has_jogos_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Índices de tabela `equipes`
--
ALTER TABLE `equipes`
  ADD PRIMARY KEY (`id_equipe`),
  ADD KEY `fk_equipes_modalidades1_idx` (`modalidades_id_modalidade`),
  ADD KEY `fk_equipes_usuarios1_idx` (`usuarios_id_usuario1`),
  ADD KEY `fk_equipes_turmas1_idx` (`turmas_id_turma`);

--
-- Índices de tabela `interclasses`
--
ALTER TABLE `interclasses`
  ADD PRIMARY KEY (`id_interclasse`);

--
-- Índices de tabela `jogos`
--
ALTER TABLE `jogos`
  ADD PRIMARY KEY (`id_jogo`),
  ADD KEY `fk_jogos_modalidades1_idx` (`modalidades_id_modalidade`),
  ADD KEY `fk_jogos_locais1_idx` (`locais_id_local`);

--
-- Índices de tabela `locais`
--
ALTER TABLE `locais`
  ADD PRIMARY KEY (`id_local`);

--
-- Índices de tabela `modalidades`
--
ALTER TABLE `modalidades`
  ADD PRIMARY KEY (`id_modalidade`),
  ADD KEY `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade`);

--
-- Índices de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD PRIMARY KEY (`id_ocorrecia`),
  ADD KEY `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Índices de tabela `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`),
  ADD KEY `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe`),
  ADD KEY `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo`);

--
-- Índices de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  ADD PRIMARY KEY (`id_pontuacao`),
  ADD KEY `fk_pontuacoes_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `fk_pontuacoes_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Índices de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  ADD PRIMARY KEY (`id_tipo_modalidade`);

--
-- Índices de tabela `turmas`
--
ALTER TABLE `turmas`
  ADD PRIMARY KEY (`id_turma`),
  ADD KEY `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse`),
  ADD KEY `fk_turmas_categorias1_idx` (`categorias_id_categoria`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `fk_usuarios_turmas1_idx` (`turmas_id_turma`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `artilheiros`
--
ALTER TABLE `artilheiros`
  MODIFY `id_artilheiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrecia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `artilheiros`
--
ALTER TABLE `artilheiros`
  ADD CONSTRAINT `fk_usuarios_has_jogos_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuarios_has_jogos_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `equipes`
--
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_equipes_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_equipes_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_equipes_usuarios1` FOREIGN KEY (`usuarios_id_usuario1`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `jogos`
--
ALTER TABLE `jogos`
  ADD CONSTRAINT `fk_jogos_locais1` FOREIGN KEY (`locais_id_local`) REFERENCES `locais` (`id_local`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_jogos_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `modalidades`
--
ALTER TABLE `modalidades`
  ADD CONSTRAINT `fk_modalidades_tipos_modalidades1` FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`) REFERENCES `tipos_modalidades` (`id_tipo_modalidade`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `fk_ocorrencias_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `partidas`
--
ALTER TABLE `partidas`
  ADD CONSTRAINT `fk_jogos_has_equipes_equipes1` FOREIGN KEY (`equipes_id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_jogos_has_equipes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `pontuacoes`
--
ALTER TABLE `pontuacoes`
  ADD CONSTRAINT `fk_pontuacoes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_pontuacoes_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `fk_turmas_categorias1` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_turmas_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
