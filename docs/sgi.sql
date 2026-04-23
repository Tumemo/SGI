-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: sgi
-- Tempo de geração: 14/04/2026 às 18:36
-- Versão do servidor: 8.0.45
-- Versão do PHP: 8.3.30

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
  `id_artilheiro` int NOT NULL,
  `usuarios_id_usuario` int NOT NULL,
  `jogos_id_jogo` int NOT NULL,
  `num_gol` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int NOT NULL,
  `nome_categoria` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nome_categoria`) VALUES
(1, 'Série A'),
(2, 'Técnico'),
(3, 'EJA');

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
(1, 2, 1, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `interclasses`
--

CREATE TABLE `interclasses` (
  `id_interclasse` int NOT NULL,
  `nome_interclasse` varchar(45) DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL,
  `regulamento_interclasse` varchar(255) DEFAULT NULL,
  `status_interclasse` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `interclasses`
--

INSERT INTO `interclasses` (`id_interclasse`, `nome_interclasse`, `ano_interclasse`, `regulamento_interclasse`, `status_interclasse`) VALUES
(1, 'Interclasse de Inverno 2026', '2026-07-15 00:00:00', NULL, '1'),
(2, 'Torneio 2', '2026-06-01 00:00:00', NULL, '0'),
(3, 'Torneio 3', '2026-08-01 00:00:00', NULL, '0');

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogos`
--

CREATE TABLE `jogos` (
  `id_jogo` int NOT NULL,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `termino_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `modalidades_id_modalidade` int NOT NULL,
  `locais_id_local` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `jogos`
--

INSERT INTO `jogos` (`id_jogo`, `nome_jogo`, `data_jogo`, `inicio_jogo`, `termino_jogo`, `status_jogo`, `modalidades_id_modalidade`, `locais_id_local`) VALUES
(1, 'Final Futsal', '2026-05-10', '10:00:00', NULL, 'Agendado', 1, 1),
(2, 'Vôlei R1', '2026-05-11', '14:30:00', NULL, 'Agendado', 2, 1),
(3, 'FIFA Final', '2026-05-12', '09:00:00', NULL, 'Agendado', 3, 3);

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
(1, 'Ginásio', '1', 500),
(2, 'Ginásio Poliesportivo Principal', '1', 100),
(3, 'Lab', '1', 40);

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidades`
--

CREATE TABLE `modalidades` (
  `id_modalidade` int NOT NULL,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `max_inscrito_modalidade` int DEFAULT NULL,
  `status_modalidade` enum('1','0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` int NOT NULL,
  `categorias_id_categoria` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `modalidades`
--

INSERT INTO `modalidades` (`id_modalidade`, `nome_modalidade`, `genero_modalidade`, `max_inscrito_modalidade`, `status_modalidade`, `tipos_modalidades_id_tipo_modalidade`, `categorias_id_categoria`) VALUES
(1, 'Futsal', 'MASC', 12, '1', 1, 1),
(2, 'Vôlei', 'FEM', 10, '1', 1, 1),
(3, 'FIFA', 'MISTO', 1, '1', 3, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id_ocorrecia` int NOT NULL,
  `titulo_ocorrecia` varchar(45) NOT NULL,
  `descricao_ocorrecia` longtext NOT NULL,
  `data_ocorrecia` datetime NOT NULL,
  `penalidade` int DEFAULT '0',
  `usuarios_id_usuario` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `ocorrencias`
--

INSERT INTO `ocorrencias` (`id_ocorrecia`, `titulo_ocorrecia`, `descricao_ocorrecia`, `data_ocorrecia`, `penalidade`, `usuarios_id_usuario`) VALUES
(1, 'Ocorrência atualizada', 'Descrição do teste', '2026-04-14 14:51:18', 15, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int NOT NULL,
  `jogos_id_jogo` int NOT NULL,
  `equipes_id_equipe` int NOT NULL,
  `resultado_partida` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pontuacoes`
--

CREATE TABLE `pontuacoes` (
  `id_pontuacao` int NOT NULL,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int DEFAULT NULL,
  `jogos_id_jogo` int DEFAULT NULL,
  `usuarios_id_usuario` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

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
(1, 'Coletiva'),
(2, 'Individual'),
(3, 'E-Sports'),
(4, 'Coletiva');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id_turma` int NOT NULL,
  `interclasses_id_interclasse` int NOT NULL,
  `nome_turma` varchar(45) DEFAULT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') DEFAULT NULL,
  `nome_fantasia_turma` varchar(45) DEFAULT NULL,
  `categorias_id_categoria` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id_turma`, `interclasses_id_interclasse`, `nome_turma`, `turno_turma`, `nome_fantasia_turma`, `categorias_id_categoria`) VALUES
(1, 1, '3B ADM', 'manha', 'Novos Tubarões', 1),
(2, 1, '2B ADM', 'tarde', 'Sharks', 1),
(3, 1, '1C MEC', 'noite', 'Gears', 2);

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
  `nivel_usuario` enum('0','1','2') NOT NULL DEFAULT '0',
  `competidor_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `mesario_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `genero_usuario` enum('FEM','MASC') NOT NULL,
  `data_nasc_usuario` date NOT NULL,
  `status_usuario` enum('0','1') NOT NULL,
  `turmas_id_turma` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `nivel_usuario`, `competidor_usuario`, `mesario_usuario`, `genero_usuario`, `data_nasc_usuario`, `status_usuario`, `turmas_id_turma`) VALUES
(1, 'RM', '2026001', 'João Silva', '123', '0', '1', '0', 'MASC', '2008-05-10', '1', 1),
(2, 'SS', '2026002', 'Maria Oliveira', '123', '0', '1', '0', 'FEM', '2009-02-15', '1', 2),
(3, 'SN', '2026003', 'Carlos Souza', '123', '1', '1', '1', 'MASC', '2007-11-20', '1', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_has_interclasses`
--

CREATE TABLE `usuarios_has_interclasses` (
  `usuarios_id_usuario` int NOT NULL,
  `interclasses_id_interclasse` int NOT NULL,
  `dt_hr_aceita` datetime DEFAULT NULL,
  `aceito_termos` enum('sim','não') DEFAULT 'não'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

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
  ADD KEY `fk_modalidades_tipos_modalidades1_idx` (`tipos_modalidades_id_tipo_modalidade`),
  ADD KEY `fk_modalidades_categorias1_idx` (`categorias_id_categoria`);

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
  ADD UNIQUE KEY `matricula_usuario_UNIQUE` (`matricula_usuario`),
  ADD KEY `fk_usuarios_turmas1_idx` (`turmas_id_turma`);

--
-- Índices de tabela `usuarios_has_interclasses`
--
ALTER TABLE `usuarios_has_interclasses`
  ADD KEY `fk_usuarios_has_interclasses_interclasses1_idx` (`interclasses_id_interclasse`),
  ADD KEY `fk_usuarios_has_interclasses_usuarios1_idx` (`usuarios_id_usuario`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `artilheiros`
--
ALTER TABLE `artilheiros`
  MODIFY `id_artilheiro` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id_jogo` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrecia` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id_turma` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `artilheiros`
--
ALTER TABLE `artilheiros`
  ADD CONSTRAINT `fk_usuarios_has_jogos_jogos1` FOREIGN KEY (`jogos_id_jogo`) REFERENCES `jogos` (`id_jogo`),
  ADD CONSTRAINT `fk_usuarios_has_jogos_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

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
  ADD CONSTRAINT `fk_modalidades_categorias1` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`),
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
  ADD CONSTRAINT `fk_turmas_categorias1` FOREIGN KEY (`categorias_id_categoria`) REFERENCES `categorias` (`id_categoria`),
  ADD CONSTRAINT `fk_turmas_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`);

--
-- Restrições para tabelas `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`);

--
-- Restrições para tabelas `usuarios_has_interclasses`
--
ALTER TABLE `usuarios_has_interclasses`
  ADD CONSTRAINT `fk_usuarios_has_interclasses_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`),
  ADD CONSTRAINT `fk_usuarios_has_interclasses_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
