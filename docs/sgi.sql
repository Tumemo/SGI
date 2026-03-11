-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 08/03/2026 às 04:12
-- Versão do servidor: 8.0.45
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
-- Estrutura para tabela `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` int NOT NULL,
  `modalidades_id_modalidade` int NOT NULL,
  `usuarios_id_usuario1` int NOT NULL,
  `turmas_id_turma` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `equipes`
--

INSERT INTO `equipes` (`id_equipe`, `modalidades_id_modalidade`, `usuarios_id_usuario1`, `turmas_id_turma`) VALUES
(11, 27, 37, 14),
(12, 27, 38, 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `interclasses`
--

CREATE TABLE `interclasses` (
  `id_interclasse` int NOT NULL,
  `nome_interclasse` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `interclasses`
--

INSERT INTO `interclasses` (`id_interclasse`, `nome_interclasse`, `ano_interclasse`) VALUES
(1, 'Interclasse Verão 2026', '2026-03-01 08:00:00'),
(2, 'Interclasse Inverno 2026', '2026-07-15 09:00:00'),
(3, 'Torneio de Integração', '2026-04-10 10:00:00'),
(4, 'Copa dos Calouros', '2026-02-20 14:00:00'),
(5, 'Masters SGI', '2026-11-05 08:30:00');

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogos`
--

CREATE TABLE `jogos` (
  `id_jogo` int NOT NULL,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `terminno_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `modalidades_id_modalidade` int NOT NULL,
  `locais_id_local` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `jogos`
--

INSERT INTO `jogos` (`id_jogo`, `nome_jogo`, `data_jogo`, `inicio_jogo`, `terminno_jogo`, `status_jogo`, `modalidades_id_modalidade`, `locais_id_local`) VALUES
(19, 'jogo 1', '2026-03-03', '15:52:19', '00:00:00', 'Agendado', 27, 17);

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id_local` int NOT NULL,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id_local`, `nome_local`, `disponivel_local`, `carga_local`) VALUES
(13, 'Ginásio Poliesportivo', '1', 500),
(14, 'Quadra de Areia', '1', 100),
(15, 'Campo de Futebol', '1', 1000),
(16, 'Sala de Jogos', '1', 30),
(17, 'Auditório Principal', '0', 200);

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidades`
--

CREATE TABLE `modalidades` (
  `id_modalidade` int NOT NULL,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `categoria_modalidade` varchar(45) DEFAULT NULL,
  `max_inscrito_modalidade` int DEFAULT NULL,
  `tipos_modalidades_id_tipo_modalidade` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `modalidades`
--

INSERT INTO `modalidades` (`id_modalidade`, `nome_modalidade`, `genero_modalidade`, `categoria_modalidade`, `max_inscrito_modalidade`, `tipos_modalidades_id_tipo_modalidade`) VALUES
(27, 'Futebol', 'FEM', 'adulto', 10, 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id_ocorrecia` int NOT NULL,
  `titulo_ocorrecia` varchar(45) NOT NULL,
  `descricao_ocorrecia` longtext NOT NULL,
  `data_ocorrecia` date NOT NULL,
  `hora_ocorrecia` time NOT NULL,
  `usuarios_id_usuario` int NOT NULL,
  `penalidade` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id_ocorrecia`, `titulo_ocorrecia`, `descricao_ocorrecia`, `data_ocorrecia`, `hora_ocorrecia`, `usuarios_id_usuario`, `penalidade`) VALUES
(3, 'Comeu comida d+', 'Não pode deixar todos os amiguinnhos sem comida', '2026-03-04', '15:32:41', 37, 100);

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int NOT NULL,
  `jogos_id_jogo` int NOT NULL,
  `equipes_id_equipe` int NOT NULL,
  `resultado__partida` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `partidas`
--

INSERT INTO `partidas` (`id_partida`, `jogos_id_jogo`, `equipes_id_equipe`, `resultado__partida`) VALUES
(3, 19, 11, 10),
(4, 19, 12, 11);

-- --------------------------------------------------------

--
-- Estrutura para tabela `pontuacoes`
--

CREATE TABLE `pontuacoes` (
  `id_pontuacao` int NOT NULL,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int DEFAULT NULL,
  `jogos_id_jogo` int NOT NULL,
  `usuarios_id_usuario` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `pontuacoes`
--

INSERT INTO `pontuacoes` (`id_pontuacao`, `nome_pontuacao`, `valor_pontuacao`, `jogos_id_jogo`, `usuarios_id_usuario`) VALUES
(4, '', 10, 19, 37),
(5, '', 5, 19, 41),
(6, 'ponto', 12, 19, 38);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_modalidades`
--

CREATE TABLE `tipos_modalidades` (
  `id_tipo_modalidade` int NOT NULL,
  `nome_tipo_modalidade` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `tipos_modalidades`
--

INSERT INTO `tipos_modalidades` (`id_tipo_modalidade`, `nome_tipo_modalidade`) VALUES
(10, 'Coletivo'),
(11, 'Individual'),
(12, 'E-Sports'),
(13, 'Atletismo'),
(14, 'Mesa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id_turma` int NOT NULL,
  `nome_turma` varchar(45) DEFAULT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') DEFAULT NULL,
  `cat_turma` varchar(45) DEFAULT NULL,
  `nome_fantasia_turma` varchar(45) DEFAULT NULL,
  `interclasses_id_interclasse` int NOT NULL,
  `pontos_turma` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id_turma`, `nome_turma`, `turno_turma`, `cat_turma`, `nome_fantasia_turma`, `interclasses_id_interclasse`, `pontos_turma`) VALUES
(1, '3º Info A', 'manha', 'Informatica', 'Lobos de silício', 1, 10),
(2, '2º Mec B', 'tarde', 'Mecanica', 'Engrenagem Louca', 1, 0),
(3, '1º Edif C', 'noite', 'Edificacoes', 'Concreto Armado', 2, 0),
(4, '3º Adm D', 'integral', 'Administracao', 'Tubaroes do Mercado', 1, 0),
(5, '2º Eletro E', 'manha', 'Eletrotecnica', 'Alta Tensão', 3, 0),
(6, '3º Info A', 'manha', 'Informatica', 'Lobos de silício', 1, 0),
(7, '2º Mec B', 'tarde', 'Mecanica', 'Engrenagem Louca', 1, 0),
(8, '1º Edif C', 'noite', 'Edificacoes', 'Concreto Armado', 2, 0),
(9, '3º Adm D', 'integral', 'Administracao', 'Tubaroes do Mercado', 1, 0),
(10, '2º Eletro E', 'manha', 'Eletrotecnica', 'Alta Tensão', 3, 0),
(11, '3º Info A', 'manha', 'Informatica', 'Lobos de silício', 1, 0),
(12, '2º Mec B', 'tarde', 'Mecanica', 'Engrenagem Louca', 1, 0),
(13, '1º Edif C', 'noite', 'Edificacoes', 'Concreto Armado', 2, 0),
(14, '3º Adm D', 'integral', 'Administracao', 'Tubaroes do Mercado', 1, 0),
(15, '2º Eletro E', 'manha', 'Eletrotecnica', 'Alta Tensão', 3, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL,
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
  `turmas_id_turma` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `foto_ususario`, `nivel_usuario`, `competidor_usuario`, `mesario_usuario`, `genero_usuario`, `data_nasc_usuario`, `turmas_id_turma`) VALUES
(37, 'RM', '2026001', 'Alice Oliveira', 'senha123', 'default.jpg', '0', '1', '0', 'FEM', '2008-05-12', 1),
(38, 'RM', '2026002', 'Bruno Silva', 'senha456', 'default.jpg', '0', '1', '1', 'MASC', '2007-11-20', 1),
(39, 'SS', '2026003', 'Carla Souza', 'senha789', 'default.jpg', '1', '0', '1', 'FEM', '2008-01-15', 2),
(40, 'SN', '2026004', 'Diego Santos', 'senhaabc', 'default.jpg', '2', '0', '0', 'MASC', '1995-03-10', 3),
(41, 'RM', '2026005', 'Eduarda Lima', 'senhaxyz', 'default.jpg', '0', '1', '0', 'FEM', '2009-08-30', 4);

--
-- Índices para tabelas despejadas
--

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
  ADD KEY `fk_turmas_interclasses1_idx` (`interclasses_id_interclasse`);

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
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id_jogo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrecia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id_turma` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `equipes`
--
ALTER TABLE `equipes`
  ADD CONSTRAINT `fk_equipes_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`),
  ADD CONSTRAINT `fk_equipes_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`),
  ADD CONSTRAINT `fk_equipes_usuarios1` FOREIGN KEY (`usuarios_id_usuario1`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `jogos`
--
ALTER TABLE `jogos`
  ADD CONSTRAINT `fk_jogos_locais1` FOREIGN KEY (`locais_id_local`) REFERENCES `locais` (`id_local`),
  ADD CONSTRAINT `fk_jogos_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`);

--
-- Restrições para tabelas `modalidades`
--
ALTER TABLE `modalidades`
  ADD CONSTRAINT `fk_modalidades_tipos_modalidades1` FOREIGN KEY (`tipos_modalidades_id_tipo_modalidade`) REFERENCES `tipos_modalidades` (`id_tipo_modalidade`);

--
-- Restrições para tabelas `ocorrencias`
--
ALTER TABLE `ocorrencias`
  ADD CONSTRAINT `fk_ocorrencias_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `partidas`
--
ALTER TABLE `partidas`
  ADD CONSTRAINT `fk_jogos_has_equipes_equipes1` FOREIGN KEY (`equipes_id_equipe`) REFERENCES `equipes` (`id_equipe`),
  ADD CONSTRAINT `fk_jogos_has_equipes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`);

--
-- Restrições para tabelas `pontuacoes`
--
ALTER TABLE `pontuacoes`
  ADD CONSTRAINT `fk_pontuacoes_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`),
  ADD CONSTRAINT `fk_pontuacoes_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `turmas`
--
ALTER TABLE `turmas`
  ADD CONSTRAINT `fk_turmas_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
