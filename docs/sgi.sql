-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 28/04/2026 às 20:16
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

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(45) DEFAULT NULL,
  `status_categoria` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL,
  `status_equipe` enum('1','0') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_has_usuarios`
--

CREATE TABLE `equipes_has_usuarios` (
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `interclasses`
--

CREATE TABLE `interclasses` (
  `id_interclasse` int(11) NOT NULL,
  `nome_interclasse` varchar(45) DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL,
  `regulamento_interclasse` varchar(255) DEFAULT NULL,
  `status_interclasse` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `jogos`
--

CREATE TABLE `jogos` (
  `id_jogo` int(11) NOT NULL,
  `nome_jogo` varchar(45) NOT NULL,
  `data_jogo` date NOT NULL,
  `inicio_jogo` time NOT NULL,
  `termino_jogo` time DEFAULT NULL,
  `status_jogo` enum('Agendado','Iniciado','Pausado','Concluido') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `locais_id_local` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id_local` int(11) NOT NULL,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int(11) DEFAULT NULL,
  `status_local` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `modalidades`
--

CREATE TABLE `modalidades` (
  `id_modalidade` int(11) NOT NULL,
  `nome_modalidade` varchar(45) NOT NULL,
  `genero_modalidade` enum('FEM','MASC','MISTO') NOT NULL,
  `max_inscrito_modalidade` int(11) DEFAULT NULL,
  `status_modalidade` enum('1','0') NOT NULL,
  `tipos_modalidades_id_tipo_modalidade` int(11) NOT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

CREATE TABLE `ocorrencias` (
  `id_ocorrecia` int(11) NOT NULL,
  `titulo_ocorrecia` varchar(45) NOT NULL,
  `descricao_ocorrecia` longtext NOT NULL,
  `data_ocorrecia` datetime NOT NULL,
  `penalidade` int(11) DEFAULT 0,
  `status_ocorrencia` enum('1','0') NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0,
  `status_pardida` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pontuacoes`
--

CREATE TABLE `pontuacoes` (
  `id_pontuacao` int(11) NOT NULL,
  `nome_pontuacao` varchar(45) DEFAULT NULL,
  `valor_pontuacao` int(11) DEFAULT NULL,
  `jogos_id_jogo` int(11) DEFAULT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_modalidades`
--

CREATE TABLE `tipos_modalidades` (
  `id_tipo_modalidade` int(11) NOT NULL,
  `nome_tipo_modalidade` varchar(45) NOT NULL,
  `status_tipo_modalidade` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id_turma` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `nome_turma` varchar(45) DEFAULT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') DEFAULT NULL,
  `nome_fantasia_turma` varchar(45) DEFAULT NULL,
  `status_turma` enum('1','0') DEFAULT NULL,
  `categorias_id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `nivel_usuario` enum('0','1','2') NOT NULL DEFAULT '0',
  `competidor_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `mesario_usuario` enum('0','1') NOT NULL DEFAULT '0',
  `genero_usuario` enum('FEM','MASC') NOT NULL,
  `data_nasc_usuario` date NOT NULL,
  `foto_usuario` varchar(255) NOT NULL,
  `status_usuario` enum('0','1') NOT NULL,
  `turmas_id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios_has_interclasses`
--

CREATE TABLE `usuarios_has_interclasses` (
  `usuarios_id_usuario` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `dt_hr_aceita` datetime DEFAULT NULL,
  `aceito_termo` enum('sim','não') DEFAULT 'não',
  `status_termo` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

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
  ADD KEY `fk_equipes_turmas1_idx` (`turmas_id_turma`);

--
-- Índices de tabela `equipes_has_usuarios`
--
ALTER TABLE `equipes_has_usuarios`
  ADD PRIMARY KEY (`equipes_id_equipe`,`usuarios_id_usuario`),
  ADD KEY `fk_equipes_has_usuarios_usuarios1_idx` (`usuarios_id_usuario`),
  ADD KEY `fk_equipes_has_usuarios_equipes1_idx` (`equipes_id_equipe`);

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
  ADD KEY `fk_modalidades_categorias1_idx` (`categorias_id_categoria`),
  ADD KEY `fk_modalidades_interclasses1_idx` (`interclasses_id_interclasse`);

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
  ADD UNIQUE KEY `nome_usuario` (`nome_usuario`),
  ADD UNIQUE KEY `senha_usuario` (`senha_usuario`),
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
  MODIFY `id_artilheiro` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrecia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `fk_equipes_turmas1` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`);

--
-- Restrições para tabelas `equipes_has_usuarios`
--
ALTER TABLE `equipes_has_usuarios`
  ADD CONSTRAINT `fk_equipes_has_usuarios_equipes1` FOREIGN KEY (`equipes_id_equipe`) REFERENCES `equipes` (`id_equipe`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_equipes_has_usuarios_usuarios1` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
  ADD CONSTRAINT `fk_modalidades_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
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
