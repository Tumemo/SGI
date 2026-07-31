-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/07/2026 às 14:27
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
(1, 139, 1, 1),
(2, 69, 1, 1),
(3, 138, 1, 1),
(4, 232, 4, 1),
(5, 203, 4, 1),
(6, 232, 4, 1),
(7, 232, 10, 1),
(8, 165, 11, 1),
(9, 232, 9, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id_categoria` int(11) NOT NULL,
  `nome_categoria` varchar(45) NOT NULL,
  `status_categoria` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id_categoria`, `nome_categoria`, `status_categoria`, `interclasses_id_interclasse`) VALUES
(1, 'Categoria I', '1', 1),
(2, 'Categoria II', '1', 1),
(3, 'Categoria I', '1', 2),
(4, 'Categoria II', '1', 2),
(5, 'Categoria I', '1', 3),
(6, 'Categoria II', '1', 3),
(7, 'Categoria I', '1', 4),
(8, 'Categoria II', '1', 4);

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes`
--

CREATE TABLE `equipes` (
  `id_equipe` int(11) NOT NULL,
  `status_equipe` enum('1','0') NOT NULL,
  `modalidades_id_modalidade` int(11) NOT NULL,
  `turmas_id_turma` int(11) NOT NULL,
  `nome_equipe` varchar(90) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `equipes`
--

INSERT INTO `equipes` (`id_equipe`, `status_equipe`, `modalidades_id_modalidade`, `turmas_id_turma`, `nome_equipe`) VALUES
(1, '1', 11, 8, NULL),
(2, '1', 12, 8, NULL),
(3, '1', 13, 8, NULL),
(4, '1', 14, 8, NULL),
(5, '1', 15, 8, NULL),
(6, '1', 11, 9, NULL),
(7, '1', 12, 9, NULL),
(8, '1', 13, 9, NULL),
(9, '1', 14, 9, NULL),
(10, '1', 15, 9, NULL),
(11, '1', 11, 10, NULL),
(12, '1', 12, 10, NULL),
(13, '1', 13, 10, NULL),
(14, '1', 14, 10, NULL),
(15, '1', 15, 10, NULL),
(16, '1', 16, 11, NULL),
(17, '1', 17, 11, NULL),
(18, '1', 18, 11, NULL),
(19, '1', 19, 11, NULL),
(20, '1', 20, 11, NULL),
(21, '1', 16, 12, NULL),
(22, '1', 17, 12, NULL),
(23, '1', 18, 12, NULL),
(24, '1', 19, 12, NULL),
(25, '1', 20, 12, NULL),
(26, '1', 16, 13, NULL),
(27, '1', 17, 13, NULL),
(28, '1', 18, 13, NULL),
(29, '1', 19, 13, NULL),
(30, '1', 20, 13, NULL),
(31, '1', 16, 14, NULL),
(32, '1', 17, 14, NULL),
(33, '1', 18, 14, NULL),
(34, '1', 19, 14, NULL),
(35, '1', 20, 14, NULL),
(36, '1', 21, 15, NULL),
(37, '1', 22, 15, NULL),
(38, '1', 23, 15, NULL),
(39, '1', 24, 15, NULL),
(40, '1', 25, 15, NULL),
(41, '1', 21, 16, NULL),
(42, '1', 22, 16, NULL),
(43, '1', 23, 16, NULL),
(44, '1', 24, 16, NULL),
(45, '1', 25, 16, NULL),
(46, '1', 21, 17, NULL),
(47, '1', 22, 17, NULL),
(48, '1', 23, 17, NULL),
(49, '1', 24, 17, NULL),
(50, '1', 25, 17, NULL),
(51, '1', 26, 18, NULL),
(52, '1', 27, 18, NULL),
(53, '1', 28, 18, NULL),
(54, '1', 29, 18, NULL),
(55, '1', 30, 18, NULL),
(56, '1', 26, 19, NULL),
(57, '1', 27, 19, NULL),
(58, '1', 28, 19, NULL),
(59, '1', 29, 19, NULL),
(60, '1', 30, 19, NULL),
(61, '1', 26, 20, NULL),
(62, '1', 27, 20, NULL),
(63, '1', 28, 20, NULL),
(64, '1', 29, 20, NULL),
(65, '1', 30, 20, NULL),
(66, '1', 26, 21, NULL),
(67, '1', 27, 21, NULL),
(68, '1', 28, 21, NULL),
(69, '1', 29, 21, NULL),
(70, '1', 30, 21, NULL),
(71, '0', 42, 15, '6EF teste Equipe 1'),
(72, '0', 42, 16, '7EF teste Equipe 1'),
(73, '0', 42, 17, '8EF teste Equipe 1'),
(74, '1', 43, 18, '9EF teste Equipe 1'),
(75, '1', 43, 19, '1EMA teste Equipe 1'),
(76, '1', 43, 20, '2EMA teste Equipe 1'),
(77, '1', 43, 21, '3EMA teste Equipe 1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `equipes_has_usuarios`
--

CREATE TABLE `equipes_has_usuarios` (
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `equipes_has_usuarios`
--

INSERT INTO `equipes_has_usuarios` (`equipes_id_equipe`, `usuarios_id_usuario`) VALUES
(21, 138),
(21, 139),
(21, 140),
(26, 69),
(26, 73),
(26, 74),
(31, 103),
(31, 104),
(31, 107),
(33, 109),
(51, 265),
(51, 266),
(51, 267),
(51, 268),
(52, 265),
(52, 266),
(52, 267),
(52, 268),
(53, 265),
(53, 266),
(53, 267),
(53, 268),
(54, 261),
(55, 265),
(56, 203),
(56, 204),
(56, 205),
(57, 199),
(57, 201),
(57, 203),
(57, 204),
(57, 205),
(58, 203),
(58, 204),
(58, 205),
(58, 208),
(61, 165),
(61, 169),
(61, 170),
(62, 165),
(62, 169),
(62, 170),
(63, 165),
(63, 169),
(63, 170),
(63, 174),
(64, 165),
(64, 169),
(65, 165),
(66, 232),
(66, 233),
(66, 236),
(67, 232),
(67, 233),
(67, 236),
(67, 238),
(68, 232),
(68, 233),
(68, 236),
(68, 239),
(69, 232),
(69, 233),
(70, 233),
(76, 165),
(76, 167);

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_arrecadacoes`
--

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

--
-- Despejando dados para a tabela `historico_arrecadacoes`
--

INSERT INTO `historico_arrecadacoes` (`id_historico`, `id_turma`, `id_interclasse`, `quantidade`, `pontos_adicionados`, `data_registro`, `registrado_por`, `status_historico`) VALUES
(1, 19, 3, 0.10, 0, '2026-07-29 11:38:20', 1, '1'),
(2, 19, 3, 1.30, 3, '2026-07-29 12:04:42', 1, '1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `interclasses`
--

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

--
-- Despejando dados para a tabela `interclasses`
--

INSERT INTO `interclasses` (`id_interclasse`, `nome_interclasse`, `ano_interclasse`, `regulamento_interclasse`, `status_interclasse`, `ponto_1_lugar`, `ponto_2_lugar`, `ponto_3_lugar`, `valor_item_arrecadacao`) VALUES
(1, 'Interclasse new teste Supremo', '2026-07-22 00:00:00', '', '0', 10, 7, 5, 2),
(2, 'Interclasse new teste Supremo00', '2026-07-22 00:00:00', '', '0', 10, 7, 5, 2),
(3, 'Interclasse', '2026-07-22 00:00:00', '', '1', 10, 7, 5, 2),
(4, 'Interclasse', '2026-07-24 00:00:00', '', '0', 10, 7, 5, 2);

--
-- Acionadores `interclasses`
--
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
        WHERE interclasses_id_interclasse = NEW.id_interclasse;
    END IF;
END
$$
DELIMITER ;

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
  `tempo_restante_jogo` int(11) DEFAULT NULL COMMENT 'Segundos restantes salvos no ultimo snapshot (pausa/save)',
  `duracao_jogo` int(11) DEFAULT NULL COMMENT 'Duracao total programada em segundos',
  `tempo_extra_jogo` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de segundos extras (acrescimos/prorrogacao)',
  `data_inicio_real` datetime DEFAULT NULL COMMENT 'Data/hora do inicio real da partida (iniciar ou retomar)',
  `modalidades_id_modalidade` int(11) NOT NULL,
  `locais_id_local` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `jogos`
--

INSERT INTO `jogos` (`id_jogo`, `nome_jogo`, `data_jogo`, `inicio_jogo`, `termino_jogo`, `status_jogo`, `tempo_restante_jogo`, `duracao_jogo`, `tempo_extra_jogo`, `data_inicio_real`, `modalidades_id_modalidade`, `locais_id_local`) VALUES
(1, 'MM:4:0:N', '2026-07-22', '08:00:00', NULL, 'Concluido', 1200, 1200, 0, '2026-07-22 08:24:04', 16, 1),
(2, 'MM:4:1:B', '2026-07-22', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 16, 1),
(3, 'MM:2:0:N', '2026-07-22', '08:00:00', NULL, 'Agendado', NULL, NULL, 0, NULL, 16, 1),
(4, 'MM:4:0:N', '2026-07-22', '08:00:00', NULL, 'Concluido', 300, NULL, 300, '2026-07-22 10:40:42', 26, 1),
(5, 'MM:4:1:B', '2026-07-22', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 26, 1),
(6, 'MM:2:0:N', '2026-07-23', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 26, 12),
(7, 'MM:1:0:N', '2026-07-22', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 26, 1),
(8, 'MM:4:0:B', '2026-07-22', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 27, 1),
(9, 'MM:4:1:N', '2026-07-22', '08:00:00', NULL, 'Concluido', 60, 300, 60, '2026-07-24 07:24:57', 27, 1),
(10, 'MM:4:0:N', '2026-07-22', '08:00:00', NULL, 'Concluido', 60, NULL, 360, '2026-07-22 13:54:45', 28, 1),
(11, 'MM:4:1:N', '2026-07-22', '08:00:00', NULL, 'Concluido', 300, 300, 0, '2026-07-22 14:40:30', 28, 1),
(12, 'MM:2:0:N', '2026-07-22', '07:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 28, 1),
(13, 'MM:1:0:N', '2026-07-22', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, NULL, 28, 1),
(14, 'MM:2:0:N', '2026-07-24', '08:00:00', NULL, 'Iniciado', NULL, NULL, 0, '2026-07-31 09:12:38', 27, 1),
(16, 'IND:29', '2026-07-31', '08:00:00', NULL, 'Concluido', 60, NULL, 60, NULL, 29, 1),
(18, 'IND:30', '2026-07-31', '08:00:00', NULL, 'Concluido', NULL, NULL, 0, '2026-07-31 08:52:59', 30, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `locais`
--

CREATE TABLE `locais` (
  `id_local` int(11) NOT NULL,
  `nome_local` varchar(45) NOT NULL,
  `disponivel_local` enum('0','1') NOT NULL DEFAULT '1',
  `carga_local` int(11) DEFAULT NULL,
  `status_local` enum('1','0') NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `locais`
--

INSERT INTO `locais` (`id_local`, `nome_local`, `disponivel_local`, `carga_local`, `status_local`, `interclasses_id_interclasse`) VALUES
(1, 'Quadra', '1', NULL, '1', 1),
(2, 'Biblioteca', '1', NULL, '1', 1),
(3, 'Pátio 1', '1', NULL, '1', 1),
(4, 'Pátio 2', '1', NULL, '1', 1),
(5, 'Quadra', '1', NULL, '1', 2),
(6, 'Biblioteca', '1', NULL, '1', 2),
(7, 'Pátio 1', '1', NULL, '1', 2),
(8, 'Pátio 2', '1', NULL, '1', 2),
(9, 'Quadra', '1', NULL, '1', 3),
(10, 'Biblioteca', '1', NULL, '1', 3),
(11, 'Pátio 1', '1', NULL, '1', 3),
(12, 'Pátio 2', '1', NULL, '1', 3),
(13, 'Quadra', '1', NULL, '1', 4),
(14, 'Biblioteca', '1', NULL, '1', 4),
(15, 'Pátio 1', '1', NULL, '1', 4),
(16, 'Pátio 2', '1', NULL, '1', 4);

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

--
-- Despejando dados para a tabela `modalidades`
--

INSERT INTO `modalidades` (`id_modalidade`, `nome_modalidade`, `genero_modalidade`, `max_inscrito_modalidade`, `status_modalidade`, `tipos_modalidades_id_tipo_modalidade`, `categorias_id_categoria`, `interclasses_id_interclasse`) VALUES
(1, 'Futsal - MA', 'MASC', 12, '1', 1, 1, 1),
(2, 'Queimada - MI', 'MISTO', 15, '1', 1, 1, 1),
(3, 'Volei - MI', 'MISTO', 10, '1', 1, 1, 1),
(4, 'Corrida - FE', 'FEM', 2, '1', 2, 1, 1),
(5, 'Corrida - MA', 'MASC', 2, '1', 2, 1, 1),
(6, 'Futsal - MA', 'MASC', 12, '1', 1, 2, 1),
(7, 'Queimada - MI', 'MISTO', 15, '1', 1, 2, 1),
(8, 'Volei - MI', 'MISTO', 10, '1', 1, 2, 1),
(9, 'Corrida - FE', 'FEM', 2, '1', 2, 2, 1),
(10, 'Corrida - MA', 'MASC', 2, '1', 2, 2, 1),
(11, 'Futsal - MA', 'MASC', 12, '1', 1, 3, 2),
(12, 'Queimada - MI', 'MISTO', 15, '1', 1, 3, 2),
(13, 'Volei - MI', 'MISTO', 10, '1', 1, 3, 2),
(14, 'Corrida - FE', 'FEM', 2, '1', 2, 3, 2),
(15, 'Corrida - MA', 'MASC', 2, '1', 2, 3, 2),
(16, 'Futsal - MA', 'MASC', 12, '1', 1, 4, 2),
(17, 'Queimada - MI', 'MISTO', 15, '1', 1, 4, 2),
(18, 'Volei - MI', 'MISTO', 10, '1', 1, 4, 2),
(19, 'Corrida - FE', 'FEM', 2, '1', 2, 4, 2),
(20, 'Corrida - MA', 'MASC', 2, '1', 2, 4, 2),
(21, 'Futsal - MA', 'MASC', 12, '1', 1, 5, 3),
(22, 'Queimada - MI', 'MISTO', 15, '1', 1, 5, 3),
(23, 'Volei - MI', 'MISTO', 10, '1', 1, 5, 3),
(24, 'Corrida - FE', 'FEM', 2, '1', 2, 5, 3),
(25, 'Corrida - MA', 'MASC', 2, '1', 2, 5, 3),
(26, 'Futsal - MA', 'MASC', 12, '1', 1, 6, 3),
(27, 'Queimada - MI', 'MISTO', 15, '1', 1, 6, 3),
(28, 'Volei - MI', 'MISTO', 10, '1', 1, 6, 3),
(29, 'Corrida - FE', 'FEM', 2, '1', 2, 6, 3),
(30, 'Corrida - MA', 'MASC', 2, '1', 2, 6, 3),
(31, 'Futsal - MA', 'MASC', 12, '1', 1, 7, 4),
(32, 'Queimada - MI', 'MISTO', 15, '1', 1, 7, 4),
(33, 'Volei - MI', 'MISTO', 10, '1', 1, 7, 4),
(34, 'Corrida - FE', 'FEM', 2, '1', 2, 7, 4),
(35, 'Corrida - MA', 'MASC', 2, '1', 2, 7, 4),
(36, 'Futsal - MA', 'MASC', 12, '1', 1, 8, 4),
(37, 'Queimada - MI', 'MISTO', 15, '1', 1, 8, 4),
(38, 'Volei - MI', 'MISTO', 10, '1', 1, 8, 4),
(39, 'Corrida - FE', 'FEM', 2, '1', 2, 8, 4),
(40, 'Corrida - MA', 'MASC', 2, '1', 2, 8, 4),
(42, 'teste', 'MISTO', 12, '0', 2, 5, 3),
(43, 'teste', 'MISTO', 12, '1', 1, 6, 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias`
--

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

-- --------------------------------------------------------

--
-- Estrutura para tabela `ocorrencias_turmas`
--

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

--
-- Despejando dados para a tabela `ocorrencias_turmas`
--

INSERT INTO `ocorrencias_turmas` (`id_ocorrencia_turma`, `turmas_id_turma`, `interclasses_id_interclasse`, `titulo_ocorrencia`, `descricao_ocorrencia`, `pontos_descontados`, `data_ocorrencia`, `usuarios_id_usuario`, `data_registro`) VALUES
(2, 26, 4, 'teste', '', 1, '2026-07-29', NULL, '2026-07-29 10:54:48'),
(3, 12, 2, 'teste', '', 5, '2026-07-29', NULL, '2026-07-29 11:28:25'),
(4, 19, 3, 'teste', '', 2, '2026-07-29', NULL, '2026-07-29 13:04:09'),
(5, 19, 3, 'teste', '', 1, '2026-07-29', NULL, '2026-07-29 13:56:44'),
(6, 19, 3, 'sla', '', 1, '2026-07-31', NULL, '2026-07-31 10:48:47'),
(7, 19, 3, 'sla', '', 1, '2026-07-31', NULL, '2026-07-31 12:12:58'),
(8, 20, 3, 'sla', '', 1, '2026-07-31', NULL, '2026-07-31 12:13:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `usuarios_id_usuario` int(11) DEFAULT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0,
  `status_partida` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `partidas`
--

INSERT INTO `partidas` (`id_partida`, `jogos_id_jogo`, `equipes_id_equipe`, `usuarios_id_usuario`, `resultado_partida`, `status_partida`) VALUES
(1, 1, 21, NULL, 2, '1'),
(2, 1, 26, NULL, 1, '1'),
(3, 2, 31, NULL, 1, '1'),
(4, 3, 21, NULL, 0, '1'),
(5, 3, 31, NULL, 0, '1'),
(6, 4, 66, NULL, 2, '1'),
(7, 4, 56, NULL, 1, '1'),
(8, 5, 61, NULL, 1, '1'),
(9, 6, 66, NULL, 0, '1'),
(10, 6, 61, NULL, 0, '1'),
(11, 7, 61, NULL, 0, '1'),
(12, 8, 62, NULL, 1, '1'),
(13, 9, 67, NULL, 1, '1'),
(14, 9, 57, NULL, 0, '1'),
(15, 10, 53, NULL, 0, '1'),
(16, 10, 68, NULL, 1, '1'),
(17, 11, 63, NULL, 1, '1'),
(18, 11, 58, NULL, 0, '1'),
(19, 12, 63, NULL, 0, '1'),
(20, 12, 68, NULL, 0, '1'),
(21, 13, 63, NULL, 0, '1'),
(22, 14, 67, NULL, 0, '1'),
(23, 14, 62, NULL, 0, '1'),
(44, 18, 65, 165, 1, '1'),
(45, 18, 70, 233, 2, '1'),
(46, 18, 55, 265, 3, '1'),
(47, 16, 64, 165, 1, '1'),
(48, 16, 64, 169, 2, '1'),
(49, 16, 69, 232, 3, '1');

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

--
-- Despejando dados para a tabela `tipos_modalidades`
--

INSERT INTO `tipos_modalidades` (`id_tipo_modalidade`, `nome_tipo_modalidade`, `status_tipo_modalidade`) VALUES
(1, 'Mata-Mata', '1'),
(2, 'Individual', '1');

-- --------------------------------------------------------

--
-- Estrutura para tabela `turmas`
--

CREATE TABLE `turmas` (
  `id_turma` int(11) NOT NULL,
  `interclasses_id_interclasse` int(11) NOT NULL,
  `nome_turma` varchar(45) NOT NULL,
  `turno_turma` enum('manha','tarde','noite','integral') NOT NULL,
  `nome_fantasia_turma` varchar(45) NOT NULL,
  `status_turma` enum('1','0') NOT NULL,
  `categorias_id_categoria` int(11) NOT NULL,
  `pontuacao_turma` int(11) NOT NULL DEFAULT 0,
  `qtd_itens_arrecadados` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `turmas`
--

INSERT INTO `turmas` (`id_turma`, `interclasses_id_interclasse`, `nome_turma`, `turno_turma`, `nome_fantasia_turma`, `status_turma`, `categorias_id_categoria`, `pontuacao_turma`, `qtd_itens_arrecadados`) VALUES
(1, 1, '6EF', 'manha', 'Sexto Ano', '1', 1, 0, 0.00),
(2, 1, '7EF', 'manha', 'Sétimo Ano', '1', 1, 0, 0.00),
(3, 1, '8EF', 'manha', 'Oitavo Ano', '1', 1, 0, 0.00),
(4, 1, '9EF', 'manha', 'Nono Ano', '1', 2, 0, 0.00),
(5, 1, '1EMA', 'manha', '1º Ano Médio', '1', 2, 0, 0.00),
(6, 1, '2EMA', 'manha', '2º Ano Médio', '1', 2, 0, 0.00),
(7, 1, '3EMA', 'manha', '3º Ano Médio', '1', 2, 0, 0.00),
(8, 2, '6EF', 'manha', 'Sexto Ano', '1', 3, 0, 0.00),
(9, 2, '7EF', 'manha', 'Sétimo Ano', '1', 3, 0, 0.00),
(10, 2, '8EF', 'manha', 'Oitavo Ano', '1', 3, 0, 0.00),
(11, 2, '9EF', 'manha', 'Nono Ano', '1', 4, 0, 0.00),
(12, 2, '1EMA', 'manha', '1º Ano Médio', '1', 4, 10, 0.00),
(13, 2, '2EMA', 'manha', '2º Ano Médio', '1', 4, 7, 0.00),
(14, 2, '3EMA', 'manha', '3º Ano Médio', '1', 4, 0, 0.00),
(15, 3, '6EF', 'manha', 'Sexto Ano', '1', 5, 0, 0.00),
(16, 3, '7EF', 'manha', 'Sétimo Ano', '1', 5, 0, 0.00),
(17, 3, '8EF', 'manha', 'Oitavo Ano', '1', 5, 0, 0.00),
(18, 3, '9EF', 'manha', 'Nono Ano', '1', 6, 12, 0.00),
(19, 3, '1EMA', 'manha', '1º Ano Médio', '1', 6, 24, 1.40),
(20, 3, '2EMA', 'manha', '2º Ano Médio', '1', 6, 54, 0.00),
(21, 3, '3EMA', 'manha', '3º Ano Médio', '1', 6, 59, 0.00),
(22, 4, '6EF', 'manha', 'Sexto Ano', '1', 7, 0, 0.00),
(23, 4, '7EF', 'manha', 'Sétimo Ano', '1', 7, 0, 0.00),
(24, 4, '8EF', 'manha', 'Oitavo Ano', '1', 7, 0, 0.00),
(25, 4, '9EF', 'manha', 'Nono Ano', '1', 8, 0, 0.00),
(26, 4, '1EMA', 'manha', '1º Ano Médio', '1', 8, 0, 0.00),
(27, 4, '2EMA', 'manha', '2º Ano Médio', '1', 8, 0, 0.00),
(28, 4, '3EMA', 'manha', '3º Ano Médio', '1', 8, 0, 0.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

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
-- Despejando dados para a tabela `usuarios_has_interclasses`
--

INSERT INTO `usuarios_has_interclasses` (`usuarios_id_usuario`, `interclasses_id_interclasse`, `dt_hr_aceita`, `aceito_termo`, `status_termo`) VALUES
(238, 3, '2026-07-29 08:07:32', 'sim', 'Ativo'),
(109, 2, '2026-07-29 08:28:49', 'sim', 'Ativo');

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
  ADD PRIMARY KEY (`id_categoria`),
  ADD KEY `fk_categorias_interclasses1_idx` (`interclasses_id_interclasse`);

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
-- Índices de tabela `historico_arrecadacoes`
--
ALTER TABLE `historico_arrecadacoes`
  ADD PRIMARY KEY (`id_historico`),
  ADD KEY `fk_hist_arrec_turmas_idx` (`id_turma`),
  ADD KEY `fk_hist_arrec_interclasses_idx` (`id_interclasse`),
  ADD KEY `fk_hist_arrec_usuarios_idx` (`registrado_por`);

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
  ADD PRIMARY KEY (`id_local`),
  ADD KEY `fk_locais_interclasses_idx` (`interclasses_id_interclasse`);

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
  ADD PRIMARY KEY (`id_ocorrencia`),
  ADD KEY `fk_ocorrencias_usuarios1_idx` (`usuarios_id_usuario`);

--
-- Índices de tabela `ocorrencias_turmas`
--
ALTER TABLE `ocorrencias_turmas`
  ADD PRIMARY KEY (`id_ocorrencia_turma`),
  ADD KEY `idx_turma` (`turmas_id_turma`),
  ADD KEY `idx_interclasse` (`interclasses_id_interclasse`),
  ADD KEY `fk_ot_usuarios` (`usuarios_id_usuario`);

--
-- Índices de tabela `partidas`
--
ALTER TABLE `partidas`
  ADD PRIMARY KEY (`id_partida`),
  ADD KEY `fk_jogos_has_equipes_equipes1_idx` (`equipes_id_equipe`),
  ADD KEY `fk_jogos_has_equipes_jogos1_idx` (`jogos_id_jogo`),
  ADD KEY `idx_partidas_usuarios` (`usuarios_id_usuario`);

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
  ADD UNIQUE KEY `chave_usuario_edicao_UNIQUE` (`chave_usuario_edicao`),
  ADD KEY `fk_usuarios_turmas1_idx` (`turmas_id_turma`),
  ADD KEY `fk_usuarios_interclasses1_idx` (`interclasses_id_interclasse`);

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
  MODIFY `id_artilheiro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `equipes`
--
ALTER TABLE `equipes`
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `historico_arrecadacoes`
--
ALTER TABLE `historico_arrecadacoes`
  MODIFY `id_historico` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `jogos`
--
ALTER TABLE `jogos`
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ocorrencias_turmas`
--
ALTER TABLE `ocorrencias_turmas`
  MODIFY `id_ocorrencia_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de tabela `pontuacoes`
--
ALTER TABLE `pontuacoes`
  MODIFY `id_pontuacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_modalidades`
--
ALTER TABLE `tipos_modalidades`
  MODIFY `id_tipo_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `turmas`
--
ALTER TABLE `turmas`
  MODIFY `id_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

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
-- Restrições para tabelas `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categorias_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
-- Restrições para tabelas `historico_arrecadacoes`
--
ALTER TABLE `historico_arrecadacoes`
  ADD CONSTRAINT `fk_hist_arrec_interclasses` FOREIGN KEY (`id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_hist_arrec_turmas` FOREIGN KEY (`id_turma`) REFERENCES `turmas` (`id_turma`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_hist_arrec_usuarios` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id_usuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Restrições para tabelas `jogos`
--
ALTER TABLE `jogos`
  ADD CONSTRAINT `fk_jogos_locais1` FOREIGN KEY (`locais_id_local`) REFERENCES `locais` (`id_local`),
  ADD CONSTRAINT `fk_jogos_modalidades1` FOREIGN KEY (`modalidades_id_modalidade`) REFERENCES `modalidades` (`id_modalidade`);

--
-- Restrições para tabelas `locais`
--
ALTER TABLE `locais`
  ADD CONSTRAINT `fk_locais_interclasses` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
-- Restrições para tabelas `ocorrencias_turmas`
--
ALTER TABLE `ocorrencias_turmas`
  ADD CONSTRAINT `fk_ot_interclasses` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`),
  ADD CONSTRAINT `fk_ot_turmas` FOREIGN KEY (`turmas_id_turma`) REFERENCES `turmas` (`id_turma`),
  ADD CONSTRAINT `fk_ot_usuarios` FOREIGN KEY (`usuarios_id_usuario`) REFERENCES `usuarios` (`id_usuario`);

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
  ADD CONSTRAINT `fk_usuarios_interclasses1` FOREIGN KEY (`interclasses_id_interclasse`) REFERENCES `interclasses` (`id_interclasse`) ON DELETE NO ACTION ON UPDATE NO ACTION,
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
