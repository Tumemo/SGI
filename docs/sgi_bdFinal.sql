-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 29/07/2026 às 19:29
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
  `turmas_id_turma` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `equipes`
--

INSERT INTO `equipes` (`id_equipe`, `status_equipe`, `modalidades_id_modalidade`, `turmas_id_turma`) VALUES
(1, '1', 11, 8),
(2, '1', 12, 8),
(3, '1', 13, 8),
(4, '1', 14, 8),
(5, '1', 15, 8),
(6, '1', 11, 9),
(7, '1', 12, 9),
(8, '1', 13, 9),
(9, '1', 14, 9),
(10, '1', 15, 9),
(11, '1', 11, 10),
(12, '1', 12, 10),
(13, '1', 13, 10),
(14, '1', 14, 10),
(15, '1', 15, 10),
(16, '1', 16, 11),
(17, '1', 17, 11),
(18, '1', 18, 11),
(19, '1', 19, 11),
(20, '1', 20, 11),
(21, '1', 16, 12),
(22, '1', 17, 12),
(23, '1', 18, 12),
(24, '1', 19, 12),
(25, '1', 20, 12),
(26, '1', 16, 13),
(27, '1', 17, 13),
(28, '1', 18, 13),
(29, '1', 19, 13),
(30, '1', 20, 13),
(31, '1', 16, 14),
(32, '1', 17, 14),
(33, '1', 18, 14),
(34, '1', 19, 14),
(35, '1', 20, 14),
(36, '1', 21, 15),
(37, '1', 22, 15),
(38, '1', 23, 15),
(39, '1', 24, 15),
(40, '1', 25, 15),
(41, '1', 21, 16),
(42, '1', 22, 16),
(43, '1', 23, 16),
(44, '1', 24, 16),
(45, '1', 25, 16),
(46, '1', 21, 17),
(47, '1', 22, 17),
(48, '1', 23, 17),
(49, '1', 24, 17),
(50, '1', 25, 17),
(51, '1', 26, 18),
(52, '1', 27, 18),
(53, '1', 28, 18),
(54, '1', 29, 18),
(55, '1', 30, 18),
(56, '1', 26, 19),
(57, '1', 27, 19),
(58, '1', 28, 19),
(59, '1', 29, 19),
(60, '1', 30, 19),
(61, '1', 26, 20),
(62, '1', 27, 20),
(63, '1', 28, 20),
(64, '1', 29, 20),
(65, '1', 30, 20),
(66, '1', 26, 21),
(67, '1', 27, 21),
(68, '1', 28, 21),
(69, '1', 29, 21),
(70, '1', 30, 21);

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
(69, 233);

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
(14, 'MM:2:0:N', '2026-07-24', '08:00:00', NULL, 'Agendado', NULL, NULL, 0, NULL, 27, 1);

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
(40, 'Corrida - MA', 'MASC', 2, '1', 2, 8, 4);

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
(5, 19, 3, 'teste', '', 1, '2026-07-29', NULL, '2026-07-29 13:56:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `partidas`
--

CREATE TABLE `partidas` (
  `id_partida` int(11) NOT NULL,
  `jogos_id_jogo` int(11) NOT NULL,
  `equipes_id_equipe` int(11) NOT NULL,
  `resultado_partida` int(11) NOT NULL DEFAULT 0,
  `status_partida` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `partidas`
--

INSERT INTO `partidas` (`id_partida`, `jogos_id_jogo`, `equipes_id_equipe`, `resultado_partida`, `status_partida`) VALUES
(1, 1, 21, 2, '1'),
(2, 1, 26, 1, '1'),
(3, 2, 31, 1, '1'),
(4, 3, 21, 0, '1'),
(5, 3, 31, 0, '1'),
(6, 4, 66, 2, '1'),
(7, 4, 56, 1, '1'),
(8, 5, 61, 1, '1'),
(9, 6, 66, 0, '1'),
(10, 6, 61, 0, '1'),
(11, 7, 61, 0, '1'),
(12, 8, 62, 1, '1'),
(13, 9, 67, 1, '1'),
(14, 9, 57, 0, '1'),
(15, 10, 53, 0, '1'),
(16, 10, 68, 1, '1'),
(17, 11, 63, 1, '1'),
(18, 11, 58, 0, '1'),
(19, 12, 63, 0, '1'),
(20, 12, 68, 0, '1'),
(21, 13, 63, 0, '1'),
(22, 14, 67, 0, '1'),
(23, 14, 62, 0, '1');

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
(18, 3, '9EF', 'manha', 'Nono Ano', '1', 6, 7, 0.00),
(19, 3, '1EMA', 'manha', '1º Ano Médio', '1', 6, 24, 1.40),
(20, 3, '2EMA', 'manha', '2º Ano Médio', '1', 6, 27, 0.00),
(21, 3, '3EMA', 'manha', '3º Ano Médio', '1', 6, 47, 0.00),
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

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `nivel_usuario`, `genero_usuario`, `data_nasc_usuario`, `foto_usuario`, `status_usuario`, `turmas_id_turma`, `interclasses_id_interclasse`, `chave_usuario_edicao`) VALUES
(1, 'SN', 'sgi@sgi.com', 'Administrador SGI', '$2y$10$5G1J8iMBKFHsZTgCNL9EoeVVzhEyTpZVPe7mzroqp.8Z4BO5Mu57u', '0', 'MASC', '2026-01-01', '', '1', NULL, NULL, NULL),
(2, 'SN', 'colab@sgi.com', 'Colaborador SGI', '$2y$10$5G1J8iMBKFHsZTgCNL9EoeVVzhEyTpZVPe7mzroqp.8Z4BO5Mu57u', '1', 'MASC', '2026-01-01', 'default.png', '1', NULL, NULL, NULL),
(3, 'SN', 'mes@sgi.com', 'Mesa Diretora SGI', '$2y$10$5G1J8iMBKFHsZTgCNL9EoeVVzhEyTpZVPe7mzroqp.8Z4BO5Mu57u', '2', 'MASC', '2026-01-01', 'default.png', '1', NULL, NULL, NULL),
(4, 'RM', '1', 'Alice Fernanda dos Santos', '$2y$10$eZUWIJh6cUThDgaKQ7/OYOqyor3c1kPHUsWeGssQxj2ERT.Y4Knly', '3', 'FEM', '2009-01-06', 'default.jpg', '0', 7, 1, '1-1'),
(5, 'RM', '2', 'Ana Clara do Amaral Justi', '$2y$10$aJYMOgpmQ3hpPzVCPjPh/uBicJtphqoaUongw4pR1.g3VztMuOLJW', '3', 'FEM', '2009-04-03', 'default.jpg', '0', 7, 1, '2-1'),
(6, 'RM', '3', 'Braien Bernardino Delanhese Galvão', '$2y$10$/fl9XJ.PrBkF/hp0KHawAuZWF/bxeaxRMTFYeNhUbfnsUGPYeEUFW', '3', 'MASC', '2008-04-03', 'default.jpg', '0', 7, 1, '3-1'),
(7, 'RM', '4', 'Breno Moura Bianchini', '$2y$10$/rPwt.tWubyVl16F0x.gBOI333fD0Atv/jj.ansc2knwaBq3hanru', '3', 'MASC', '2008-12-09', 'default.jpg', '0', 7, 1, '4-1'),
(8, 'RM', '5', 'Clara Tauane Moraes Dourado', '$2y$10$o0.uidaEziEppCxSuUnbpu/LHn2g/1owxyazB4Z6ugK8efXwwqp9W', '3', 'FEM', '2007-01-26', 'default.jpg', '0', 7, 1, '5-1'),
(9, 'RM', '6', 'Eloisa Florence Lima', '$2y$10$kxwvGPkawoVNey3xDJpx0.q5Y7f9pDLhu4Q82RPg7FeF89Uoo4Szi', '3', 'FEM', '2009-06-15', 'default.jpg', '0', 7, 1, '6-1'),
(10, 'RM', '7', 'Enrico Antonio Rodrigues', '$2y$10$soyCEOpY.6TYiaKZKZSzCuIIN5mdn8QtW9NqlzPDa4WJZZftCuBbG', '3', 'MASC', '2008-09-02', 'default.jpg', '0', 7, 1, '7-1'),
(11, 'RM', '8', 'Gabriela Pissinin Menossi', '$2y$10$sxwM2I/dNOqwnHGa.e0Kme.KvnBvBehNH9nZsxcNZ5JolhfzKalqa', '3', 'FEM', '2009-01-20', 'default.jpg', '0', 7, 1, '8-1'),
(12, 'RM', '9', 'Geovana Eduarda Sousa de Oliveira', '$2y$10$/H7g8JAmPf9HB3HrTPlBV.uTfGRtbjrHFv38Lo6P.7Ptf80TKpyp.', '3', 'FEM', '2008-09-21', 'default.jpg', '0', 7, 1, '9-1'),
(13, 'RM', '10', 'Gustavo Santos Vieira', '$2y$10$F7CNRQmUHcfPKf3J.ip3cu2p...DQl3jhMJ5d/GpwjHbhw/vd7Uu.', '3', 'MASC', '2009-03-27', 'default.jpg', '0', 7, 1, '10-1'),
(14, 'RM', '11', 'Henry Salvador Trindade Montoya Neto', '$2y$10$ZD1bI9evn5d5rnkxcr36P.bZV7IbZpA2QZwIGfxkMuyO.eXgBP73.', '3', 'MASC', '2009-01-17', 'default.jpg', '0', 7, 1, '11-1'),
(15, 'RM', '12', 'Hugo Francisco da Silva', '$2y$10$LmJnzPTGicAquu.K9VKIpuaC4h5YgSqxtJeBbUFPaNdY70x140cFW', '3', 'MASC', '2008-06-23', 'default.jpg', '0', 7, 1, '12-1'),
(16, 'RM', '13', 'Hytallo Gabriel Ruffo Flores', '$2y$10$oBAZMlt1DUdaFhmp/kt4hOskgQVZTzFFy0WRkurEn8/YY6V/HgwOK', '3', 'MASC', '2009-03-17', 'default.jpg', '0', 7, 1, '13-1'),
(17, 'RM', '14', 'Kaiky Gonçalves da Silva', '$2y$10$XEz7YC8hipnGMCa8L1IBuuiKGNiHSH1CN2z0TW5bs34r64RqzCTlS', '3', 'MASC', '2008-08-06', 'default.jpg', '0', 7, 1, '14-1'),
(18, 'RM', '15', 'Kaio Gabriel Alves Figueredo', '$2y$10$hpCYccjlkuALbBhyfESahehckPJ9O/zaTZJmcaGarQBFBkwvbbHpy', '3', 'MASC', '2008-08-25', 'default.jpg', '0', 7, 1, '15-1'),
(19, 'RM', '16', 'Kauê Alves Rufino', '$2y$10$.Yt4Lf/9h3cIzKNY8qo4eeCTohozJEq8dT7fqcr.Gv8GhNco1VAam', '3', 'MASC', '2008-10-11', 'default.jpg', '0', 7, 1, '16-1'),
(20, 'RM', '17', 'Kayky Teófilo de Souza Castriani', '$2y$10$99Y5HLBfG.5VLdNfLX4MMeLAfEmmRY/NtRi4dJ9lBA9rKLyC0HhLW', '3', 'MASC', '2009-03-10', 'default.jpg', '0', 7, 1, '17-1'),
(21, 'RM', '18', 'Lanna Livia Fernandes Andrade', '$2y$10$UKTqAHwBbtJYB5Vhyq9g2.evII/pncZGZFfC53sLVbze/NoUGUkUO', '3', 'FEM', '2008-10-31', 'default.jpg', '0', 7, 1, '18-1'),
(22, 'RM', '19', 'Lara Wada Oguido', '$2y$10$IJNF1NCjSxyq/r.dgBfGlujx74W8mmT8QWDcCND3xfwXP0TKW5eGW', '3', 'FEM', '2009-03-26', 'default.jpg', '0', 7, 1, '19-1'),
(23, 'RM', '20', 'Luiz Gustavo Pereira de Souza', '$2y$10$JYlUhNvo25WCSpZ02QDdouiQE4q96vy15uHN/3hBcGSa6yx.vyeiG', '3', 'MASC', '2008-10-15', 'default.jpg', '0', 7, 1, '20-1'),
(24, 'RM', '21', 'Luma Christofole Massaranduba', '$2y$10$6hNJCbHgHAVh1vJjNK0RZ.8ZyqG/76wpuxhOwiqmxGrGvNmjnSw/2', '3', 'FEM', '2008-10-30', 'default.jpg', '0', 7, 1, '21-1'),
(25, 'RM', '22', 'Murilo Beraldo Corral Fernandes', '$2y$10$kGyn1/9y4znfnRg1B4yCBeYw9/VHk//WpZ91MVQPdm0RsfsNk6vk2', '3', 'MASC', '2009-03-31', 'default.jpg', '0', 7, 1, '22-1'),
(26, 'RM', '23', 'Natália Caroline Bosisio', '$2y$10$7FiYtGQeXMuULLHEePHokOJrsr5eRyqB.eNJH43el.IheVQ39Uw3y', '3', 'FEM', '2008-11-13', 'default.jpg', '0', 7, 1, '23-1'),
(27, 'RM', '24', 'Nicolas Henrique Mendes Oliveira', '$2y$10$F7JxQlc5VWDot//mw2VCu..7ElVl0thLOUdL06NugBMvJr3erBAQm', '3', 'MASC', '2008-11-21', 'default.jpg', '0', 7, 1, '24-1'),
(28, 'RM', '25', 'Pietro Eduardo Coelho Dalbem', '$2y$10$YwITCkZUDa09uXR49oA1nu3Lbq7Hby0zJJYR02fK2zfzWYvGxf1Xi', '3', 'MASC', '2009-03-04', 'default.jpg', '0', 7, 1, '25-1'),
(29, 'RM', '26', 'Rafael Antonio Mantovani', '$2y$10$2KpWCWoGdgFrGiPmQMx1Sup2EnyfGLYSQKNkAxS22V9ptSp0xG5OS', '3', 'MASC', '2008-06-13', 'default.jpg', '0', 7, 1, '26-1'),
(30, 'RM', '27', 'Ryan Rodrigo dos Santos Clemente da Silva', '$2y$10$1YZx9VY1.Hc0qVLsP/Ib9.snGaQYeehinztbDiXpGzsOIOaqrt12.', '3', 'MASC', '2009-03-25', 'default.jpg', '0', 7, 1, '27-1'),
(31, 'RM', '28', 'Ryan Rodrigues Rangel', '$2y$10$05D1kDJ9ObDxdooOp3/guetghkZjt35.39joJp9jO/VKrGw36g9aW', '3', 'MASC', '2008-12-29', 'default.jpg', '0', 7, 1, '28-1'),
(32, 'RM', '29', 'Thainá Vitória da Silva Pontes', '$2y$10$LFhRvyKDgO8e6GgC6ydbTutIK6gi1Py50f72eSFJvrMxpoBYtXySS', '3', 'FEM', '2009-01-07', 'default.jpg', '0', 7, 1, '29-1'),
(33, 'RM', '30', 'Thauan Mateus de Souza Ramos', '$2y$10$FT8ig6Q4kkkrhFRTR9HRXu5cmvgypuQBD5eG.kwio.ceVWJFmY7yW', '3', 'MASC', '2008-07-24', 'default.jpg', '0', 7, 1, '30-1'),
(34, 'RM', '31', 'Yasmin Gabriélli da Come Silva', '$2y$10$SPApRsAwX8271giaSysnfO8ftv8yXevK8CCPj.drAn5gSAWu.dSWy', '3', 'FEM', '2008-06-14', 'default.jpg', '0', 7, 1, '31-1'),
(35, 'RM', '32', 'Yasmim Vitória da Silva Brasi', '$2y$10$ZHvEJMs69FnOzxGtqozvJuOTFxLjSmOt98Wy0jlVDDjWne4pwYGmq', '3', 'FEM', '2009-09-01', 'default.jpg', '0', 6, 1, '32-1'),
(36, 'RM', '2916', 'Ana Ayumi Shiguematsu Lourenço', '$2y$10$fKKGwFQa6oeh8lJ2SoZltO2yJ34H/ZZthMBycjMFo5E4eHqIZymkm', '3', 'FEM', '2011-01-22', 'default.jpg', '0', 5, 1, '2916-1'),
(37, 'RM', '2910', 'Ana Elisa de Souza Furtunato', '$2y$10$XturuDt795UPWREAiOlTkeY2O83E3Po09NjG9XJNsFHXhTLLLh/u.', '3', 'FEM', '2011-03-18', 'default.jpg', '0', 5, 1, '2910-1'),
(38, 'RM', '2924', 'Ana Mel Dundes de Macedo', '$2y$10$S0bO0IJEWOWUoQ3jWlkC0O1kw5mKZ5dLSNLWcCM6IpZnCH5cUOHsy', '3', 'FEM', '2010-11-03', 'default.jpg', '0', 5, 1, '2924-1'),
(39, 'RM', '2998', 'Angelina Garrido Lapa', '$2y$10$ujIh75F3cRYvMdYXsFKiKeSuUUcI8v5hsQNgdjt2rEraMV587lumS', '3', 'FEM', '2010-09-27', 'default.jpg', '0', 5, 1, '2998-1'),
(40, 'RM', '3396', 'Anna Laura Inácio Medeiro', '$2y$10$XiTzngNfeP7C4C6PTMWMp.QKLvvCU56t32D9yoFdAEuWLb.g1BMke', '3', 'FEM', '2011-01-25', 'default.jpg', '0', 5, 1, '3396-1'),
(41, 'RM', '2925', 'Anny Gabrielly de Oliveira Silva', '$2y$10$4WSVf920BNV.aTPNn8stE.XuX8Q1qP9orl7NMD8.ZYn/WlhpT1Cqi', '3', 'FEM', '2010-11-27', 'default.jpg', '0', 5, 1, '2925-1'),
(42, 'RM', '2928', 'Antony Gabriel Pimenta David', '$2y$10$U9.1Aw6oyYDwlAqPshwyXeNgCiH847ZV1kHwtYV0p5l8.i0KGIZgq', '3', 'MASC', '2010-08-27', 'default.jpg', '0', 5, 1, '2928-1'),
(43, 'RM', '3358', 'Arthur Antonio da Silva', '$2y$10$JnUKGCz90yM2oHqP2m471./KKbEsBX3.FmPs3D4DoTg1pZsmagfku', '3', 'MASC', '2010-07-09', 'default.jpg', '0', 5, 1, '3358-1'),
(44, 'RM', '3258', 'Arthur Lasso Malacrida', '$2y$10$u.FZjE6xO6UvtkJNknMCEuPLqPADTgBSf5lohNsE8/iUPxX6KXlUW', '3', 'MASC', '2011-06-23', 'default.jpg', '0', 5, 1, '3258-1'),
(45, 'RM', '2907', 'Bianca Costa Guimaraes', '$2y$10$LCYGdyDYYyV1Skd.4AekaeioXEti9.nuKM96PNN2BVqnt0v0ER4MK', '3', 'FEM', '2011-04-11', 'default.jpg', '0', 5, 1, '2907-1'),
(46, 'RM', '2917', 'Bruna Fernanda Figueiredo Colnago', '$2y$10$.cC7b2DHzOacar8F3ueyzOMh921A.jJn00ADgCCVYLdr4/nUHRqve', '3', 'FEM', '2010-09-30', 'default.jpg', '0', 5, 1, '2917-1'),
(47, 'RM', '3150', 'Bruno Rodrigues Araujo', '$2y$10$OCMuO6B64YJEWbQYT3inDuBoGk//MR/QQHVuEWlvwuSSVfAgbISRq', '3', 'MASC', '2011-05-26', 'default.jpg', '0', 5, 1, '3150-1'),
(48, 'RM', '2949', 'Cauã Colnago Manganaro Cabelo', '$2y$10$uVNcOVy2r2mdCa.5GfaweuTMom/FeFmdDqe6Q9Vc1I.zysg8w.6aG', '3', 'MASC', '2010-07-01', 'default.jpg', '0', 5, 1, '2949-1'),
(49, 'RM', '2903', 'Daniel Mazzaro Pachega', '$2y$10$JXwSS1QV135hbXRLmBGjm.G1jcrTOUUBCno10Niei5UCQJVnbYB3K', '3', 'MASC', '2010-07-02', 'default.jpg', '0', 5, 1, '2903-1'),
(50, 'RM', '2904', 'Felipe Teixeira Rodrigues', '$2y$10$EcVjv0m5GY5dLAFRRRRlteOD/j7mB47IHzZX3OJ/yAzEO9GrO23Z.', '3', 'MASC', '2010-08-30', 'default.jpg', '0', 5, 1, '2904-1'),
(51, 'RM', '2876', 'Felipe Verli dos Santos', '$2y$10$qPHheZMAjqq2fiW/hmEWTuz4rVULOH1EL2MGPFI4HPQbKD4TsdrIi', '3', 'MASC', '2009-11-25', 'default.jpg', '0', 5, 1, '2876-1'),
(52, 'RM', '2914', 'Isabelle Alves Monteiro', '$2y$10$vzyUSNScu997oonnmKFc2.hyviajtk0EPPmcR1hEttJ7f1UIvIM16', '3', 'FEM', '2011-02-08', 'default.jpg', '0', 5, 1, '2914-1'),
(53, 'RM', '2944', 'Isadora Lima da Silva', '$2y$10$RGMfKXwKo33Ifm.CZDHax.QiC7qw0QhjXn6UpClFF/iCojSk3IdFe', '3', 'FEM', '2011-04-15', 'default.jpg', '0', 5, 1, '2944-1'),
(54, 'RM', '3264', 'João Henrique dos Santos Silva', '$2y$10$kekNPgoXEKQ8jXHWxwMa../nH6nBQylK/WJSeNjh6tgFtNODnHSzS', '3', 'MASC', '2011-03-11', 'default.jpg', '0', 5, 1, '3264-1'),
(55, 'RM', '2912', 'Kauã Franco Santos', '$2y$10$Bl40hpxcsBHlrErPyBN5KOYMMdxeEBtdhBAJbdEvB4xWVNMS.Nkve', '3', 'MASC', '2010-09-27', 'default.jpg', '0', 5, 1, '2912-1'),
(56, 'RM', '3141', 'Lara de Brito Milani das Dores', '$2y$10$.AhWCyAR9NzWjs1wI3EXie0bKmZzRW3.IMkQY0/yasISMqutPm.RC', '3', 'FEM', '2010-07-30', 'default.jpg', '0', 5, 1, '3141-1'),
(57, 'RM', '2915', 'Laura Rodrigues Viana', '$2y$10$RPy5Ssy9EgQ6hJG7dOWHGObm75yErw0lvPGeuXkuL3CVw1zZ3VI3K', '3', 'FEM', '2011-01-14', 'default.jpg', '0', 5, 1, '2915-1'),
(58, 'RM', '3152', 'Leonardo Artur Augusto Santos Felizardo', '$2y$10$kcQiFWcjHQSLOXOQQd1ms.Al8qSnlem4R3ARW3vnckIvd0B12elKC', '3', 'MASC', '2010-09-07', 'default.jpg', '0', 5, 1, '3152-1'),
(59, 'RM', '2929', 'Maiara Scarabelli Aguilera', '$2y$10$zmoJmIP/MPKXL.DSas02TeUSsevx9Jj5YYwWwd30AFyZaOWRevCeq', '3', 'FEM', '2011-06-27', 'default.jpg', '0', 5, 1, '2929-1'),
(60, 'RM', '3400', 'Marcos Felipe Mean dos Santos', '$2y$10$ijbNN6n.by9B2yJF9tUE/Own3GJRdL/.mbaUgnDdxbxBuNdBhyg2y', '3', 'MASC', '2011-05-02', 'default.jpg', '0', 5, 1, '3400-1'),
(61, 'RM', '3072', 'Pedro Henrique Silva Madia', '$2y$10$SNkl.ItO.3n1LaGeU9u39O1fHkQJmeGwEsXVhr9lHRuJGTN0OhVo6', '3', 'MASC', '2010-12-27', 'default.jpg', '0', 5, 1, '3072-1'),
(62, 'RM', '2873', 'Rian Henrique de Macedo', '$2y$10$zXQntKfvd/9d7p1BP8UuYuPfPkS9hjMCuqkU8KyPkJewT9wy336hy', '3', 'MASC', '2009-10-05', 'default.jpg', '0', 5, 1, '2873-1'),
(63, 'RM', '3070', 'Sofia Batista Vinha', '$2y$10$fqG07DMmK6RKE2q9wAPmPe2BGrjFJyHqiJ/HT8FgCT.G.K.uP3ffO', '3', 'FEM', '2010-09-08', 'default.jpg', '0', 5, 1, '3070-1'),
(64, 'RM', '3067', 'vitor Silva Tomasetti', '$2y$10$Av1Hq.i2v0t32SoSIyk2HO7D8RFNL7VgkXLX/tFuGPRZnKPa.tfDu', '3', 'MASC', '2011-03-01', 'default.jpg', '0', 5, 1, '3067-1'),
(65, 'RM', '2905', 'William Augusto Gomes Ruedel', '$2y$10$Qj1lY.abUqiUkUBCBPZ/b.OSaxSzvUVOvSq4mXZ/CORSFZ27fjbcW', '3', 'MASC', '2011-03-24', 'default.jpg', '0', 5, 1, '2905-1'),
(66, 'RM', '3473', 'Eder Mathias Bonhomme Lima', '$2y$10$p8jTO18W7vIVIow6ojyTLO1YyQC8L0iNBHi40JVWsJ9mjZlJE5bCC', '3', 'MASC', '2011-08-08', 'default.jpg', '0', 5, 1, '3473-1'),
(67, 'RM', '3475', 'Julia Carvalho Galindo Alecrim', '$2y$10$2d7.BGNgbGY/8rrul7U3UuLyyrZT2hqiIFsq8.gd4rXYx3Qssv7nS', '3', 'FEM', '2010-07-23', 'default.jpg', '0', 5, 1, '3475-1'),
(68, 'RM', '3481', 'Gabriel Ferreira Bastos', '$2y$10$IzeeCiCIE7ORED2lYFUAkuUi2U5qeXRNa.pWx1.w3tnIt3oudnavG', '3', 'MASC', '2010-07-07', 'default.jpg', '0', 5, 1, '3481-1'),
(69, 'RM', '2879', 'Alex Bressanin Junior', '$2y$10$DSD/5PmJy/5TUa.SdyfSLu12v97KFosn3cwr3U2THuJF2V0MwGn6e', '3', 'MASC', '2010-03-07', 'default.jpg', '0', 13, 2, '2879-2'),
(70, 'RM', '2861', 'Ana Júlia Martins Borges', '$2y$10$fTjoNHf3rlPQSUkP3.KfAOzmmZW2tiuObZkdzMfMctO8KCUFyxGsu', '3', 'FEM', '2009-08-16', 'default.jpg', '0', 13, 2, '2861-2'),
(71, 'RM', '2870', 'Ana Júlia Miller Benoni', '$2y$10$rPf.DyyW6Ldzki.JPZnoguQg9dZ4/wwSCNIuQVksL7.UXvcCaP6uq', '3', 'FEM', '2009-07-27', 'default.jpg', '0', 13, 2, '2870-2'),
(72, 'RM', '3395', 'Anna Luiza da Silva Ribeiro', '$2y$10$O55rFsdX3B5bc6ebXPTMX.k2/CcGnOLkZw4I58TQ025HWQ.sZxiz6', '3', 'FEM', '2009-10-05', 'default.jpg', '0', 13, 2, '3395-2'),
(73, 'RM', '2866', 'Antonio Franco Parangaba', '$2y$10$AMKaUuEJrga7HP0FV.cmJOlfK6XmIOd56qTiCZJ78sWF1Oj1zpvlm', '3', 'MASC', '2010-04-11', 'default.jpg', '0', 13, 2, '2866-2'),
(74, 'RM', '3360', 'Antony Alves Estevam de Souza', '$2y$10$9a05GFVtwy3O4s7MAtZJ8ey7kLn9CFcPk877zb0/bH3dmMmDDRvFG', '3', 'MASC', '2009-10-14', 'default.jpg', '0', 13, 2, '3360-2'),
(75, 'RM', '2867', 'Beatriz Palopoli Germano', '$2y$10$DvaL67mbOdgCYTxLqyjKE.tr4.7j8zsV4GaeN57N2SkY04/OwYyCC', '3', 'FEM', '2009-07-13', 'default.jpg', '0', 13, 2, '2867-2'),
(76, 'RM', '3000', 'Bianca Alves Vieira', '$2y$10$gNFB2OUG/oyf4KK0ZjcS4uG8xKTqpugyaDOAW8xvEkIUS66cJyyPy', '3', 'FEM', '2009-06-04', 'default.jpg', '0', 13, 2, '3000-2'),
(77, 'RM', '2852', 'Bianca Colnago Blazech', '$2y$10$1xoIvFZivP.C9afeil9CNeOIHVN4fQM9jy.5g9LmOEex26tr0tsOu', '3', 'FEM', '2009-07-07', 'default.jpg', '0', 13, 2, '2852-2'),
(78, 'RM', '3354', 'Cauã de Goes Malacrida', '$2y$10$hXYamEaNI1zSXhgXp1c5EO8hEJRywpJiOG2410CP5RTnCrp2EPR2W', '3', 'MASC', '2009-11-05', 'default.jpg', '0', 13, 2, '3354-2'),
(79, 'RM', '3077', 'Eduardo Yanai Otsuka', '$2y$10$KrcLOgaa2ihXD6PkOBczZOtMiFbQm6oBuOn17r0l5NAPwI8XpfWS2', '3', 'MASC', '2010-02-14', 'default.jpg', '0', 13, 2, '3077-2'),
(80, 'RM', '3066', 'Felipe Rodrigues Rampasso', '$2y$10$chsK0ZrQFV4hPeZ0qrodMO7b/PGSW5xKv5DD3c72RmsdSjQhqtbSu', '3', 'MASC', '2009-06-18', 'default.jpg', '0', 13, 2, '3066-2'),
(81, 'RM', '2881', 'Gabriel Daxter Califani Honorato e Silva', '$2y$10$mevx/zr0lrf4i52j48eU6O0OYX6ZvydkqPzi0evjJGS6jpqKTqxP6', '3', 'MASC', '2009-11-03', 'default.jpg', '0', 13, 2, '2881-2'),
(82, 'RM', '2865', 'Gabrielle Heloisa Correa Viana', '$2y$10$vyk3p0Y3dj1mRyc9jTrjce.zGsmpyGwVCE14YPytHtEKq5E7y6MEi', '3', 'FEM', '2009-10-02', 'default.jpg', '0', 13, 2, '2865-2'),
(83, 'RM', '3145', 'Gustavo da Costa Brito', '$2y$10$hfzNkXA.6/ZIsnsuvGHLxu7ZSi6MNGkR9N8qq5yyLY4xIKdapTKam', '3', 'MASC', '2009-05-12', 'default.jpg', '0', 13, 2, '3145-2'),
(84, 'RM', '3215', 'Isadora Mota Omura Felix', '$2y$10$ZDNAK8i8962PLBSHWWGKC.vAA.zsH9JjYP/rnRujt2/LEfbpriaPO', '3', 'FEM', '2010-05-12', 'default.jpg', '0', 13, 2, '3215-2'),
(85, 'RM', '2877', 'João Pedro Shinozuka Azevedo', '$2y$10$U4CsSw2Zp3SOCMWMCIvSEuvgCT4OOoZ8mgYslBGk/T45ti47zlXtG', '3', 'MASC', '2010-05-29', 'default.jpg', '0', 13, 2, '2877-2'),
(86, 'RM', '2892', 'João Pedro Silva Braz Paião', '$2y$10$Zw9/rHVirn0Rxr86ddhz9ONSEIYqrzfdlrDYfPdlAzTc7ZZbZAl4W', '3', 'MASC', '2009-12-01', 'default.jpg', '0', 13, 2, '2892-2'),
(87, 'RM', '2874', 'Júlia Fernandes Ventura', '$2y$10$n1pDzSGoVinmOFkkfQT0VO7IP/yRao3BPMUwXET6nAmosau5UOrje', '3', 'FEM', '2010-02-08', 'default.jpg', '0', 13, 2, '2874-2'),
(88, 'RM', '2864', 'Kauã Rodrigues de Sousa Santos', '$2y$10$oqpjlkqDz.vdVKURWuqWUeRzt9XzPs61daYQ6oMQLFiAc3qvhq0vC', '3', 'MASC', '2009-06-19', 'default.jpg', '0', 13, 2, '2864-2'),
(89, 'RM', '2863', 'Kauê Nascimento Broca', '$2y$10$bjmoV5gkrTT1iLsOI.KIj.gqpPy4sF1gki2fABs/MtLeFXegdATjS', '3', 'MASC', '2009-06-29', 'default.jpg', '0', 13, 2, '2863-2'),
(90, 'RM', '3005', 'Lara Aranda Cantalupe', '$2y$10$NKsjD04bMSHyo71ItkKt/OrytuX0l9uiY6kNU/6IkexGStUO12rum', '3', 'FEM', '2009-04-18', 'default.jpg', '0', 13, 2, '3005-2'),
(91, 'RM', '3252', 'Lara Beltrame Zara', '$2y$10$ddOrhB4aiFK34is.TGay8OTyqkwEceyiTbO8CYyWrXCfK4pXPQYaq', '3', 'FEM', '2009-08-19', 'default.jpg', '0', 13, 2, '3252-2'),
(92, 'RM', '2875', 'Lara de Souza Vieira', '$2y$10$efqFMmhmhIO35qIL/yb6Gevb3cWFjyROOt61ZoVBtVxTlvFYPV/IS', '3', 'FEM', '2009-10-08', 'default.jpg', '0', 13, 2, '2875-2'),
(93, 'RM', '2872', 'Maria Fernanda Pachega Garrido', '$2y$10$G4U4/3AMLj27iRMGRdC5oOc..8EONMD4gOvL4JNOzEWYmSzfvSsmy', '3', 'FEM', '2010-05-17', 'default.jpg', '0', 13, 2, '2872-2'),
(94, 'RM', '2854', 'Miguel Bertoncelo Guarnier da Silva', '$2y$10$Ldr6WMRaGOOsLfEGjzGW/uTU/n73j2lgvlnouH3loK5dXekINluz2', '3', 'MASC', '2010-05-23', 'default.jpg', '0', 13, 2, '2854-2'),
(95, 'RM', '3406', 'Paulo de Souza Morari', '$2y$10$cS9PlWggiTiqS0BsF5AGcu0nE3GanVOUXCNVcfD7kSZGPZLXSU70O', '3', 'MASC', '2009-07-22', 'default.jpg', '0', 13, 2, '3406-2'),
(96, 'RM', '3074', 'Raissa Helena Santos', '$2y$10$J12i.69pSaMoMVH/4ORzO.WRhlvrfDmFJUYNfNHSM31jJUajLDGJK', '3', 'FEM', '2010-01-25', 'default.jpg', '0', 13, 2, '3074-2'),
(97, 'RM', '2878', 'Samuel Victor Santos Caetano', '$2y$10$TvIPO8/CYXx9P9LC2vGVZeFFtvSIdSXJgMEcnv7I9HeVh6SIle5zC', '3', 'MASC', '2009-11-19', 'default.jpg', '0', 13, 2, '2878-2'),
(98, 'RM', '2869', 'Sthefany Lavini Chagas dos Santos', '$2y$10$dK7n0CW8FY01XL4Qm7j2xuBPp6.tgfwtWft5B8rtcmkpL/5MJ.KG.', '3', 'FEM', '2010-02-05', 'default.jpg', '0', 13, 2, '2869-2'),
(99, 'RM', '2858', 'Victor Hugo Ferreira Viani', '$2y$10$zqk6BXSbJC3fk8isBGCCceF7askLOm90N06tX4AeDdpXFDFSmX/oC', '3', 'MASC', '2009-08-31', 'default.jpg', '0', 13, 2, '2858-2'),
(100, 'RM', '3319', 'Yasmim Vitória da Silva Brasi', '$2y$10$Y.fZDlaoskthJWo5XlyoAe2SIOGOGMkTtyGy/E4mVVUsVVoAcCcWa', '3', 'FEM', '2009-09-01', 'default.jpg', '0', 13, 2, '3319-2'),
(101, 'RM', '3263', 'Alice Fernanda dos Santos', '$2y$10$l1B8EOC1c7cTlTIeIptrju0kX0mftH1el8971CdLLstkcvUAzqsDS', '3', 'FEM', '2009-01-06', 'default.jpg', '0', 14, 2, '3263-2'),
(102, 'RM', '2810', 'Ana Clara do Amaral Justi', '$2y$10$.FO4.vRAUNJqW6tCUKict.HA/qoqI9liZrtC.HzqIKw90WQrRfCDy', '3', 'FEM', '2009-04-03', 'default.jpg', '0', 14, 2, '2810-2'),
(103, 'RM', '3356', 'Braien Bernardino Delanhese Galvão', '$2y$10$WH07Y72QsiXad1iqG9LocuUSCh4lGpdZE0avggqh0/TKAJoDobqSq', '3', 'MASC', '2008-04-03', 'default.jpg', '0', 14, 2, '3356-2'),
(104, 'RM', '2833', 'Breno Moura Bianchini', '$2y$10$KDqipo7gKy2wy4nueiROleqdJGTzxMst/S2N7AgfneUosOWHHMun2', '3', 'MASC', '2008-12-09', 'default.jpg', '0', 14, 2, '2833-2'),
(105, 'RM', '2416', 'Clara Tauane Moraes Dourado', '$2y$10$vb3RlUvgmzwv2GlmFtBiOejwkraVlHfaFYNfAIGlD3ksPLMHAq.TC', '3', 'FEM', '2007-01-26', 'default.jpg', '0', 14, 2, '2416-2'),
(106, 'RM', '3206', 'Eloisa Florence Lima', '$2y$10$8w2EdCRl6V6tGV9PIx/9Ae4/27d7D7jprOJsJ1l8By5aztYioG8PO', '3', 'FEM', '2009-06-15', 'default.jpg', '0', 14, 2, '3206-2'),
(107, 'RM', '2834', 'Enrico Antonio Rodrigues', '$2y$10$m..sEWpREHIBx6hwjtYXeOaXwbgmtXoTiuURt4SJ6YrQXl95x6ASe', '3', 'MASC', '2008-09-02', 'default.jpg', '0', 14, 2, '2834-2'),
(108, 'RM', '2838', 'Gabriela Pissinin Menossi', '$2y$10$Tmjj979Q5gtWv2l6Cl4OV.oHheWLbugYNNPI7dSZnQcgrH7c/Lvz6', '3', 'FEM', '2009-01-20', 'default.jpg', '0', 14, 2, '2838-2'),
(109, 'RM', '2817', 'Geovana Eduarda Sousa de Oliveira', '$2y$10$cBIWCa/hEyFyiDccedrZf.cKXknvGrNq3EABTdtb3doYC41OwFhk.', '3', 'FEM', '2008-09-21', 'default.jpg', '0', 14, 2, '2817-2'),
(110, 'RM', '2814', 'Gustavo Santos Vieira', '$2y$10$xGSU3uRCMRtcVkdRgpzKbOwZnpW9pbySeE7lU.9NoBPfOuvKJL9Ky', '3', 'MASC', '2009-03-27', 'default.jpg', '0', 14, 2, '2814-2'),
(111, 'RM', '3401', 'Henry Salvador Trindade Montoya Neto', '$2y$10$dmi9IYggDD8xit.OHGuFfe/c5dDn2kXIXRe87.d5pGrj98jWJY.ka', '3', 'MASC', '2009-01-17', 'default.jpg', '0', 14, 2, '3401-2'),
(112, 'RM', '3002', 'Hugo Francisco da Silva', '$2y$10$h3s1S/qKczJ92cEIuJ4VGecskWeaqPz5TwbS9qWFsCq9Ld2ptk0uC', '3', 'MASC', '2008-06-23', 'default.jpg', '0', 14, 2, '3002-2'),
(113, 'RM', '2835', 'Hytallo Gabriel Ruffo Flores', '$2y$10$oUemBUOpwceJqYSs/043mO984JNgCCy2CzbEbXnZDl17b7DVFzd8u', '3', 'MASC', '2009-03-17', 'default.jpg', '0', 14, 2, '2835-2'),
(114, 'RM', '2826', 'Kaiky Gonçalves da Silva', '$2y$10$UEj77qgFb.72onPGJofVOeNogAO1ERcYVG1jOoDRHhNhI92MCrIEa', '3', 'MASC', '2008-08-06', 'default.jpg', '0', 14, 2, '2826-2'),
(115, 'RM', '2813', 'Kaio Gabriel Alves Figueredo', '$2y$10$u/1XxfCg7GNc8JQd9laf/uTdvSDpc4s1DxV9Lj3mMo3/D8fA1iLA2', '3', 'MASC', '2008-08-25', 'default.jpg', '0', 14, 2, '2813-2'),
(116, 'RM', '2829', 'Kauê Alves Rufino', '$2y$10$NYsgbUFANqRGCrWrXOqkz.8guMmxRZeUUeBeBZlQwwO2Abg6WyZSW', '3', 'MASC', '2008-10-11', 'default.jpg', '0', 14, 2, '2829-2'),
(117, 'RM', '2830', 'Kayky Teófilo de Souza Castriani', '$2y$10$VR.aCj35o6L70ldXJ9ps6.TS2tD1Xgw6wf1VJIsVF/008fnyNiS.a', '3', 'MASC', '2009-03-10', 'default.jpg', '0', 14, 2, '2830-2'),
(118, 'RM', '2844', 'Lanna Livia Fernandes Andrade', '$2y$10$sw3XPWnuJtJonDHxE6.3JOYj1EASSTKUrsqt9/9Gp9nSLfub0vOKy', '3', 'FEM', '2008-10-31', 'default.jpg', '0', 14, 2, '2844-2'),
(119, 'RM', '2851', 'Lara Wada Oguido', '$2y$10$hwxUf6Vay4aSTmU7J9B4HOB80jCnlivXWlVF1kZd6Gd.0EqeonhMG', '3', 'FEM', '2009-03-26', 'default.jpg', '0', 14, 2, '2851-2'),
(120, 'RM', '3052', 'Luiz Gustavo Pereira de Souza', '$2y$10$dOZCwZ0txMxEfEdXC/fWbeWc5iZECfvV2Dz94GImAO/034WtWJxqW', '3', 'MASC', '2008-10-15', 'default.jpg', '0', 14, 2, '3052-2'),
(121, 'RM', '2825', 'Luma Christofole Massaranduba', '$2y$10$QD6VqtQvSbi/wcgQmvHvkO7QJ1nmhPh5ONc1bjCl/t85I..nAqLim', '3', 'FEM', '2008-10-30', 'default.jpg', '0', 14, 2, '2825-2'),
(122, 'RM', '2827', 'Murilo Beraldo Corral Fernandes', '$2y$10$b46Am2ppAcm8O5AE.nEl.eYaO50JJhmeoCAi2XZ9bhDgitj2WtOB2', '3', 'MASC', '2009-03-31', 'default.jpg', '0', 14, 2, '2827-2'),
(123, 'RM', '2832', 'Natália Caroline Bosisio', '$2y$10$odiSbJHEiDCF..k502XlmuiqBJ2A.iId6obCPJIMWc28NMfWy59oe', '3', 'FEM', '2008-11-13', 'default.jpg', '0', 14, 2, '2832-2'),
(124, 'RM', '2815', 'Nicolas Henrique Mendes Oliveira', '$2y$10$/sWhlyV1y6Ib8Mix7SAaeekqqvw4vHg2FF3Qk/oZcy7qLMXuv0CPi', '3', 'MASC', '2008-11-21', 'default.jpg', '0', 14, 2, '2815-2'),
(125, 'RM', '2820', 'Pietro Eduardo Coelho Dalbem', '$2y$10$EC1gDawwpuUmwFysYcShxugDZf5KGt4hpvILzpT0FzS2ygdPUXMz.', '3', 'MASC', '2009-03-04', 'default.jpg', '0', 14, 2, '2820-2'),
(126, 'RM', '3148', 'Rafael Antonio Mantovani', '$2y$10$nXdUloG8TvQ/U5P5oS5nv.h4ggHfnsDz.7gMor9RYSvMZu25CZtTG', '3', 'MASC', '2008-06-13', 'default.jpg', '0', 14, 2, '3148-2'),
(127, 'RM', '2836', 'Ryan Rodrigo dos Santos Clemente da Silva', '$2y$10$SC.QOP4O.6OvodhIZmEE5eZHmI/n5fziw7LUOKbaAeoNwX5pBKbPi', '3', 'MASC', '2009-03-25', 'default.jpg', '0', 14, 2, '2836-2'),
(128, 'RM', '2818', 'Ryan Rodrigues Rangel', '$2y$10$HR7qCdIIBywtBrALe1RU/eQLK4MCl0JEbL8QMhu.QehNU/dfJ7E0y', '3', 'MASC', '2008-12-29', 'default.jpg', '0', 14, 2, '2818-2'),
(129, 'RM', '2823', 'Thainá Vitória da Silva Pontes', '$2y$10$IdpSEJDEiNSMxd8.v7mWvuNe48LWZR2/Pzo0v9pNwgVSUqS.mUVRW', '3', 'FEM', '2009-01-07', 'default.jpg', '0', 14, 2, '2823-2'),
(130, 'RM', '2824', 'Thauan Mateus de Souza Ramos', '$2y$10$tIedtDU/cvKk8Zvnch5FuelbdPAVYc9Pww0ZsgMTx8NEF.sqN4kuC', '3', 'MASC', '2008-07-24', 'default.jpg', '0', 14, 2, '2824-2'),
(131, 'RM', '3308', 'Yasmin Gabriélli da Come Silva', '$2y$10$hYaOZcJoRQKA/XOeQwz5pexU1vfEEVdin3VJXkgmBy7VSNNDbzTEi', '3', 'FEM', '2008-06-14', 'default.jpg', '0', 14, 2, '3308-2'),
(132, 'RM', '2916', 'Ana Ayumi Shiguematsu Lourenço', '$2y$10$1pxWqAz0hFzpumBdw9ydDuNjavU6NrznbE/r0SKyxfBFBDDm/ol/C', '3', 'FEM', '2011-01-22', 'default.jpg', '0', 12, 2, '2916-2'),
(133, 'RM', '2910', 'Ana Elisa de Souza Furtunato', '$2y$10$gHsI2J7WpbX10XPI7Fp6/O48u1gm/zgOg89fI/1fyBYruRH6602jW', '3', 'FEM', '2011-03-18', 'default.jpg', '0', 12, 2, '2910-2'),
(134, 'RM', '2924', 'Ana Mel Dundes de Macedo', '$2y$10$vXZtkT2OIJON6lVQjArQ..XSAK4CmbvmkG03L4l7pxUYBfKa4rgQ.', '3', 'FEM', '2010-11-03', 'default.jpg', '0', 12, 2, '2924-2'),
(135, 'RM', '2998', 'Angelina Garrido Lapa', '$2y$10$ZPbvWO9j0Ooz4wpV8i5ysOrmemKqHsrXoGYmfkiFvT3g.61ixM/42', '3', 'FEM', '2010-09-27', 'default.jpg', '0', 12, 2, '2998-2'),
(136, 'RM', '3396', 'Anna Laura Inácio Medeiro', '$2y$10$D08EDNlRkX1i.0216Ac09.j1scxalBYm20h8JxsmretUoQc.Jsm4S', '3', 'FEM', '2011-01-25', 'default.jpg', '0', 12, 2, '3396-2'),
(137, 'RM', '2925', 'Anny Gabrielly de Oliveira Silva', '$2y$10$GaPjuS0uzX0X2T3RgeP8ouEfbZbTg623hhI0C5JruW/7NoSigtr9O', '3', 'FEM', '2010-11-27', 'default.jpg', '0', 12, 2, '2925-2'),
(138, 'RM', '2928', 'Antony Gabriel Pimenta David', '$2y$10$dUwlywpUQqbLFmOmbACe3uFxfOg3//2VIKpxkiR9S6IqquSt3eKZG', '3', 'MASC', '2010-08-27', 'default.jpg', '0', 12, 2, '2928-2'),
(139, 'RM', '3358', 'Arthur Antonio da Silva', '$2y$10$jMwS0EuEXVkHXrHbVHMMcuWo5WCW/U0ZBqRemJflBI8cBA/NIcYc.', '3', 'MASC', '2010-07-09', 'default.jpg', '0', 12, 2, '3358-2'),
(140, 'RM', '3258', 'Arthur Lasso Malacrida', '$2y$10$S7ZuyZMYyyby6Bu/F0bAlOZB/lm34hN2TOBTQ18GGnnZsAk2MCXzm', '3', 'MASC', '2011-06-23', 'default.jpg', '0', 12, 2, '3258-2'),
(141, 'RM', '2907', 'Bianca Costa Guimaraes', '$2y$10$BpCMRX6/d2dkZmmiQj7HlukLyewhPp52YxY5unu.djyuy3dt9oFP.', '3', 'FEM', '2011-04-11', 'default.jpg', '0', 12, 2, '2907-2'),
(142, 'RM', '2917', 'Bruna Fernanda Figueiredo Colnago', '$2y$10$I24rXvEBYy.StVObTLkoOefblewDm0wpJywJABh6EhsKwI8DRRPFa', '3', 'FEM', '2010-09-30', 'default.jpg', '0', 12, 2, '2917-2'),
(143, 'RM', '3150', 'Bruno Rodrigues Araujo', '$2y$10$MBPG..8m.y8QMZNP44wr2efW2S/b.Z6snPhZljsaIoCU4DseKKXmy', '3', 'MASC', '2011-05-26', 'default.jpg', '0', 12, 2, '3150-2'),
(144, 'RM', '2949', 'Cauã Colnago Manganaro Cabelo', '$2y$10$Mk4pd8nFJ9dfJXVbQdYBTOg3YL3GPQ2tHZUYCIYD4A3Xk1mIfMtv.', '3', 'MASC', '2010-07-01', 'default.jpg', '0', 12, 2, '2949-2'),
(145, 'RM', '2903', 'Daniel Mazzaro Pachega', '$2y$10$FWQi1UkeZq7w736dktuihO3QmT/ixFz/PTn49qH1mjiVuxvx4ugH2', '3', 'MASC', '2010-07-02', 'default.jpg', '0', 12, 2, '2903-2'),
(146, 'RM', '2904', 'Felipe Teixeira Rodrigues', '$2y$10$cm2hBZLQWXe/ZundcI2oa.HmKEj.DjtnqPWIbaTP0T0nUMFGXrbx6', '3', 'MASC', '2010-08-30', 'default.jpg', '0', 12, 2, '2904-2'),
(147, 'RM', '2876', 'Felipe Verli dos Santos', '$2y$10$/CSwmWsgDkcmSJo9AUSBnOPJ9w8MrVWY3KeWnuvKuh09.Dv2BlJOe', '3', 'MASC', '2009-11-25', 'default.jpg', '0', 12, 2, '2876-2'),
(148, 'RM', '2914', 'Isabelle Alves Monteiro', '$2y$10$hc2eLT9gF69z.07KWIHt/ejo76XCBCrruZuOB0oBFR8qDpdFpi5iW', '3', 'FEM', '2011-02-08', 'default.jpg', '0', 12, 2, '2914-2'),
(149, 'RM', '2944', 'Isadora Lima da Silva', '$2y$10$QqQw9fDCF0ebHQYmnvahzeEiMTY9HN2I0vb526KuwDbt8nVO7mje6', '3', 'FEM', '2011-04-15', 'default.jpg', '0', 12, 2, '2944-2'),
(150, 'RM', '3264', 'João Henrique dos Santos Silva', '$2y$10$8V9wqNlkcqafea2ff5zD1eli0oGKSz4fTewyVrTIK2AvnYdXWSSre', '3', 'MASC', '2011-03-11', 'default.jpg', '0', 12, 2, '3264-2'),
(151, 'RM', '2912', 'Kauã Franco Santos', '$2y$10$6ss1ZqR0PjXYK0b5BtOL7.LrHkMju/l6BF.9fzgTx5N55pSTXx2oa', '3', 'MASC', '2010-09-27', 'default.jpg', '0', 12, 2, '2912-2'),
(152, 'RM', '3141', 'Lara de Brito Milani das Dores', '$2y$10$0T8fLIIdPm4.PexpAwWFMuGF8oF2gsr7V0TnfYzRpYyvRB7RVjB26', '3', 'FEM', '2010-07-30', 'default.jpg', '0', 12, 2, '3141-2'),
(153, 'RM', '2915', 'Laura Rodrigues Viana', '$2y$10$jsiKxSQu1g4v2F7BK8Otwe5VPzEP5v4QV93WUbfOur5PDklO2/yXy', '3', 'FEM', '2011-01-14', 'default.jpg', '0', 12, 2, '2915-2'),
(154, 'RM', '3152', 'Leonardo Artur Augusto Santos Felizardo', '$2y$10$rC00aPXR/J0iSNuBVCO.v.s/tRa0VY2g411wEiUt6LE8oCYxFmN3O', '3', 'MASC', '2010-09-07', 'default.jpg', '0', 12, 2, '3152-2'),
(155, 'RM', '2929', 'Maiara Scarabelli Aguilera', '$2y$10$jFv4KWojlGzw59aRTtu5fu7266Dp7p.IslnN4SqYZEX8yDqO9Przi', '3', 'FEM', '2011-06-27', 'default.jpg', '0', 12, 2, '2929-2'),
(156, 'RM', '3400', 'Marcos Felipe Mean dos Santos', '$2y$10$LhjxIR4Um/KMVg4vonslt.JiSU0ojdSKSBhEOh1ZG14TXRH2nK6ci', '3', 'MASC', '2011-05-02', 'default.jpg', '0', 12, 2, '3400-2'),
(157, 'RM', '3072', 'Pedro Henrique Silva Madia', '$2y$10$rLMtUMW2EE2Bp4U33XvrgeQGd/39ntRw1IT6WreeCqM1z5O52YSK6', '3', 'MASC', '2010-12-27', 'default.jpg', '0', 12, 2, '3072-2'),
(158, 'RM', '2873', 'Rian Henrique de Macedo', '$2y$10$2c0zIht/pmWCrpjMKoMuRO3RpUnT5bGYgkjCl07iCpCiL2YQFJc/C', '3', 'MASC', '2009-10-05', 'default.jpg', '0', 12, 2, '2873-2'),
(159, 'RM', '3070', 'Sofia Batista Vinha', '$2y$10$F.jtQgxEUqWA9Dh4y7o4t.N/z.WPYFGhItNYGkqRRvr6O8zOnSIa6', '3', 'FEM', '2010-09-08', 'default.jpg', '0', 12, 2, '3070-2'),
(160, 'RM', '3067', 'vitor Silva Tomasetti', '$2y$10$IQmGSyiEShT/ZuThaePilOgMFQ/VlQvvANE4mIbJFCB5f40PGJAJ2', '3', 'MASC', '2011-03-01', 'default.jpg', '0', 12, 2, '3067-2'),
(161, 'RM', '2905', 'William Augusto Gomes Ruedel', '$2y$10$jHMkTmSC21l8cCfi8cP75eIjvQdZiNrich2L9Zr2woSajixXQNU6i', '3', 'MASC', '2011-03-24', 'default.jpg', '0', 12, 2, '2905-2'),
(162, 'RM', '3473', 'Eder Mathias Bonhomme Lima', '$2y$10$d0NjPzw9I832EAeUjXZYOuTqWBVjkqSK6Vfq6aNj.dMTHESMHHRUe', '3', 'MASC', '2011-08-08', 'default.jpg', '0', 12, 2, '3473-2'),
(163, 'RM', '3475', 'Julia Carvalho Galindo Alecrim', '$2y$10$qClqiBSPRJYKnSBKokoX3.mgwaU7Qwiqzcr9QmjdbzwJbJfV5JQgO', '3', 'FEM', '2010-07-23', 'default.jpg', '0', 12, 2, '3475-2'),
(164, 'RM', '3481', 'Gabriel Ferreira Bastos', '$2y$10$mJ5BD5pj29oHwkEuHEYsTuvjZuAY8eY18FxUGwnToFcarefdWToUC', '3', 'MASC', '2010-07-07', 'default.jpg', '0', 12, 2, '3481-2'),
(165, 'RM', '2879', 'Alex Bressanin Junior', '$2y$10$ZzNI4TDLzQgsgHeRHy8FmuxwyL1GdNmpS4sO1fQqiSurOCLKDV4/O', '3', 'MASC', '2010-03-07', 'default.jpg', '1', 20, 3, '2879-3'),
(166, 'RM', '2861', 'Ana Júlia Martins Borges', '$2y$10$w9LAiwKKUCU6OCM5Al43fOhyVgyQtkSBPaREmYvBwU2yaxZ1BIAP.', '3', 'FEM', '2009-08-16', 'default.jpg', '1', 20, 3, '2861-3'),
(167, 'RM', '2870', 'Ana Júlia Miller Benoni', '$2y$10$w8.0/W7z127WhzwhkreVXugcm2fvPLBiD6E9iqf1HT9xpqXnANKH6', '3', 'FEM', '2009-07-27', 'default.jpg', '1', 20, 3, '2870-3'),
(168, 'RM', '3395', 'Anna Luiza da Silva Ribeiro', '$2y$10$8LhCpiRwJyh6M4m28HMX2uLToUElaaaiS9lhSo5yU2FKUSFZv6Uu.', '3', 'FEM', '2009-10-05', 'default.jpg', '1', 20, 3, '3395-3'),
(169, 'RM', '2866', 'Antonio Franco Parangaba', '$2y$10$uVji/GV3WNl0Bs4/Jtv9uegjf8CEiU/Lz3g2AKke6vj96ImI8tamG', '3', 'MASC', '2010-04-11', 'default.jpg', '1', 20, 3, '2866-3'),
(170, 'RM', '3360', 'Antony Alves Estevam de Souza', '$2y$10$3Kb/tcyGQq1p/vh46JnmUONpA8w7zCOedRdfAh3VPh0HOVBZdjY4O', '3', 'MASC', '2009-10-14', 'default.jpg', '1', 20, 3, '3360-3'),
(171, 'RM', '2867', 'Beatriz Palopoli Germano', '$2y$10$.1tWvaDnn9M7jXPAgXjof.DGXoGT5NZBmroHVVjI.gTuQwjwsyZQ2', '3', 'FEM', '2009-07-13', 'default.jpg', '1', 20, 3, '2867-3'),
(172, 'RM', '3000', 'Bianca Alves Vieira', '$2y$10$eq135laOgKJlMlS18f.xiuXUgJSsONcobF26CJxIr/a84jrE/4jlW', '3', 'FEM', '2009-06-04', 'default.jpg', '1', 20, 3, '3000-3'),
(173, 'RM', '2852', 'Bianca Colnago Blazech', '$2y$10$.poUDxfBG3ORHpbf9bCPeexVcaZHvrqVwtPy1flbLgGAfcaiJTrPW', '3', 'FEM', '2009-07-07', 'default.jpg', '1', 20, 3, '2852-3'),
(174, 'RM', '3354', 'Cauã de Goes Malacrida', '$2y$10$lj/.v0MAQkbgLDsTOdvcee1nOlAX2YA0eAihbjhQXEFNZ/Q9ev4qK', '3', 'MASC', '2009-11-05', 'default.jpg', '1', 20, 3, '3354-3'),
(175, 'RM', '3077', 'Eduardo Yanai Otsuka', '$2y$10$X3JgLZfibjQfqlO.1kaJDOhGs2LhGOhjnB7L..jKCcRg2ixeK7GPW', '3', 'MASC', '2010-02-14', 'default.jpg', '1', 20, 3, '3077-3'),
(176, 'RM', '3066', 'Felipe Rodrigues Rampasso', '$2y$10$gPFy0Kp2AwQqlJsv2tqj9.zGNfU0bARss/tUTf7dOYrjUytXGNCou', '3', 'MASC', '2009-06-18', 'default.jpg', '1', 20, 3, '3066-3'),
(177, 'RM', '2881', 'Gabriel Daxter Califani Honorato e Silva', '$2y$10$8Xa55t3eRnJhFJRVhqJG9ub1NxzBq839rieFGakE2Xq.XgvmtDdHC', '3', 'MASC', '2009-11-03', 'default.jpg', '1', 20, 3, '2881-3'),
(178, 'RM', '2865', 'Gabrielle Heloisa Correa Viana', '$2y$10$cIaRT7e/bWW2kDsHxFy7R.wFMCJ8.dmO9HasTkUDGXs0qj7WmttdK', '3', 'FEM', '2009-10-02', 'default.jpg', '1', 20, 3, '2865-3'),
(179, 'RM', '3145', 'Gustavo da Costa Brito', '$2y$10$tzMHyeyQhWz1sTTwK70EEuhMnT/YiQg9d5JGbkjK.4nLzWJ/WbJeW', '3', 'MASC', '2009-05-12', 'default.jpg', '1', 20, 3, '3145-3'),
(180, 'RM', '3215', 'Isadora Mota Omura Felix', '$2y$10$RTGTQZqoVvuwqlTw./7yzuw7dcIB7vHZGSmV1HuzIJi5qAnPynnU6', '3', 'FEM', '2010-05-12', 'default.jpg', '1', 20, 3, '3215-3'),
(181, 'RM', '2877', 'João Pedro Shinozuka Azevedo', '$2y$10$SlHKc38Q/8f8MpMJu1//4.8YW48iltKnFfUGpnvY6MSU3.f7qk1mO', '3', 'MASC', '2010-05-29', 'default.jpg', '1', 20, 3, '2877-3'),
(182, 'RM', '2892', 'João Pedro Silva Braz Paião', '$2y$10$BXbNsJVaxJePhS6f/15J.ue3sD.LypwjwNJJR1qBHK9zFx6Qic1gS', '3', 'MASC', '2009-12-01', 'default.jpg', '1', 20, 3, '2892-3'),
(183, 'RM', '2874', 'Júlia Fernandes Ventura', '$2y$10$rU2Al/LyZy/txw/QR7DYiOAyQxsrjXcRwBL8YpauVlXUYWwD0tJvG', '3', 'FEM', '2010-02-08', 'default.jpg', '1', 20, 3, '2874-3'),
(184, 'RM', '2864', 'Kauã Rodrigues de Sousa Santos', '$2y$10$OXh7vpw7n65EC8CSkTXYGOtIEMoa2sBM4.4kx/ngdeKQBhj29I0GW', '3', 'MASC', '2009-06-19', 'default.jpg', '1', 20, 3, '2864-3'),
(185, 'RM', '2863', 'Kauê Nascimento Broca', '$2y$10$GTNwOT5D2Ln3ZVeNNsn4uewL/sxxGl5xG1dTd8imcKxHiS3W3qd9m', '3', 'MASC', '2009-06-29', 'default.jpg', '1', 20, 3, '2863-3'),
(186, 'RM', '3005', 'Lara Aranda Cantalupe', '$2y$10$JVX3.SG7IAhikdt3PtgENOwDd0j.4G..i6J5nDa7tUdJa6Y9C.NkW', '3', 'FEM', '2009-04-18', 'default.jpg', '1', 20, 3, '3005-3'),
(187, 'RM', '3252', 'Lara Beltrame Zara', '$2y$10$G.VK8B0daTD0NiXHwKVMDePzvGAoxID5ejFygjf8MS0oPTIhvj7KO', '3', 'FEM', '2009-08-19', 'default.jpg', '1', 20, 3, '3252-3'),
(188, 'RM', '2875', 'Lara de Souza Vieira', '$2y$10$tqfuD3rZY5BCSs.k/KX9A.JPLAWrhNwr4HAQC3gjBFsdCzEYwXJUy', '3', 'FEM', '2009-10-08', 'default.jpg', '1', 20, 3, '2875-3'),
(189, 'RM', '2872', 'Maria Fernanda Pachega Garrido', '$2y$10$bNh1oS8xUPZQarNmtlfDoOTcsNG5BrkDKr5i.E0fg.GeCAAcfoLWu', '3', 'FEM', '2010-05-17', 'default.jpg', '1', 20, 3, '2872-3'),
(190, 'RM', '2854', 'Miguel Bertoncelo Guarnier da Silva', '$2y$10$OCM8X8.i9m.HX1.mxBJYL.UIH/C1W1U0CeqDe0vm8CgLvTccK/zW.', '3', 'MASC', '2010-05-23', 'default.jpg', '1', 20, 3, '2854-3'),
(191, 'RM', '3406', 'Paulo de Souza Morari', '$2y$10$R5xyHqTpZcugqXEGqKc8iuZFbpWgFHxCtgo.b6Hg5JxVvUgfYLjce', '3', 'MASC', '2009-07-22', 'default.jpg', '1', 20, 3, '3406-3'),
(192, 'RM', '3074', 'Raissa Helena Santos', '$2y$10$A.lsclJC56bxxulbPQN8jeHPmuAKwpJWNskVuZukPEHoW94B1JhtC', '3', 'FEM', '2010-01-25', 'default.jpg', '1', 20, 3, '3074-3'),
(193, 'RM', '2878', 'Samuel Victor Santos Caetano', '$2y$10$wdoF1fo6gnsHxxflekZQSu8je1LHI8J0r4Vel8Tr2LTitA.c.grFy', '3', 'MASC', '2009-11-19', 'default.jpg', '1', 20, 3, '2878-3'),
(194, 'RM', '2869', 'Sthefany Lavini Chagas dos Santos', '$2y$10$3K8c1NlqotIaDqIiWNoRU..JVzu2AcwOiuecqEXYxqodl6khFKwXS', '3', 'FEM', '2010-02-05', 'default.jpg', '1', 20, 3, '2869-3'),
(195, 'RM', '2858', 'Victor Hugo Ferreira Viani', '$2y$10$6ruzQ/Lk1Me9YAlZRblBsOwLvZ6YN3OgylzgKwjdy44S9l852Un/i', '3', 'MASC', '2009-08-31', 'default.jpg', '1', 20, 3, '2858-3'),
(196, 'RM', '3319', 'Yasmim Vitória da Silva Brasi', '$2y$10$4TUts.9zwf.fAml1G11MNOzXCD//sR2yZDMI3pZpusVpfuQjJGDxO', '3', 'FEM', '2009-09-01', 'default.jpg', '1', 20, 3, '3319-3'),
(197, 'RM', '2916', 'Ana Ayumi Shiguematsu Lourenço', '$2y$10$XKxTFeOvXnX/6phMlvz67.U42HLgYJw4f3qHOKc4urjBK0Mugi89m', '3', 'FEM', '2011-01-22', 'default.jpg', '1', 19, 3, '2916-3'),
(198, 'RM', '2910', 'Ana Elisa de Souza Furtunato', '$2y$10$Q6ZNiOKUB2/Ah/GNzh/ZQu8qoHueeQIRs4WXAKaY8h0YupGzoKhnu', '3', 'FEM', '2011-03-18', 'default.jpg', '1', 19, 3, '2910-3'),
(199, 'RM', '2924', 'Ana Mel Dundes de Macedo', '$2y$10$afsqs23PuwDPjg2fdMoxYeEXgoexbCQ.0h6mA4jyeOtltpmWV3UKm', '3', 'FEM', '2010-11-03', 'default.jpg', '1', 19, 3, '2924-3'),
(200, 'RM', '2998', 'Angelina Garrido Lapa', '$2y$10$xZXihIBaTv4gNnfiz3zbCOub8Dq03Fhc9/VLVEbqBBkMHTq6pEtPq', '3', 'FEM', '2010-09-27', 'default.jpg', '1', 19, 3, '2998-3'),
(201, 'RM', '3396', 'Anna Laura Inácio Medeiro', '$2y$10$YLGODjf6Uy/ONmXx6EQCZ..kmirVgKxzEx3QPqLHa9awNkKIebVG.', '3', 'FEM', '2011-01-25', 'default.jpg', '1', 19, 3, '3396-3'),
(202, 'RM', '2925', 'Anny Gabrielly de Oliveira Silva', '$2y$10$yvd8YGnJG/SN3yMzWAiJBOnY5aaecWKfrvzETHqVq706Bkl5bcO7K', '3', 'FEM', '2010-11-27', 'default.jpg', '1', 19, 3, '2925-3'),
(203, 'RM', '2928', 'Antony Gabriel Pimenta David', '$2y$10$mUXQWsKj7yrEBVrhRJ20ZuGDuRjlnSMLzbFZEbsKFpb8xAaswrUly', '3', 'MASC', '2010-08-27', 'default.jpg', '1', 19, 3, '2928-3'),
(204, 'RM', '3358', 'Arthur Antonio da Silva', '$2y$10$4aBbuHCxYG7Q1zbSeTsBJOaU1EOmmWJiEQ3bGAtgAgvF2If6pziLu', '3', 'MASC', '2010-07-09', 'default.jpg', '1', 19, 3, '3358-3'),
(205, 'RM', '3258', 'Arthur Lasso Malacrida', '$2y$10$3lsE/bE/AM05Z8aUlU.0zuJf/V4RFGRQbIq7cjQrkaYi0T6.Hu44y', '3', 'MASC', '2011-06-23', 'default.jpg', '1', 19, 3, '3258-3'),
(206, 'RM', '2907', 'Bianca Costa Guimaraes', '$2y$10$TyEdZPSeBuVitvaZt2jzu.oUDODX6LEf2XX2wYN8veL3nwT/Jm6X.', '3', 'FEM', '2011-04-11', 'default.jpg', '1', 19, 3, '2907-3'),
(207, 'RM', '2917', 'Bruna Fernanda Figueiredo Colnago', '$2y$10$gz9fB072FpXx0AgDDXyiFex3U43IDf2pv.8bblNnq/sjQlPJr11va', '3', 'FEM', '2010-09-30', 'default.jpg', '1', 19, 3, '2917-3'),
(208, 'RM', '3150', 'Bruno Rodrigues Araujo', '$2y$10$ZDqkIHt3Wci15qoTxBQo.uRwpuJswv4QGccNkJxb7UBhEh2vIBRpm', '3', 'MASC', '2011-05-26', 'default.jpg', '1', 19, 3, '3150-3'),
(209, 'RM', '2949', 'Cauã Colnago Manganaro Cabelo', '$2y$10$MLFNjHlWqT2DjT.IELcX0.g84Xawgr9IutGRajUK0ScCO.H0gt0Ya', '3', 'MASC', '2010-07-01', 'default.jpg', '1', 19, 3, '2949-3'),
(210, 'RM', '2903', 'Daniel Mazzaro Pachega', '$2y$10$DivNhLP.tAlieo1WCTJuNOpDy0PbnoqV/mehxjPd8H5bPebqtBGKG', '3', 'MASC', '2010-07-02', 'default.jpg', '1', 19, 3, '2903-3'),
(211, 'RM', '2904', 'Felipe Teixeira Rodrigues', '$2y$10$hKpza5v7EVyxJiuOy8o7FOmcWM3s3H7NX8sdPvEZOTN3oXx1S2QdG', '3', 'MASC', '2010-08-30', 'default.jpg', '1', 19, 3, '2904-3'),
(212, 'RM', '2876', 'Felipe Verli dos Santos', '$2y$10$SqMmIg3uYHJzRtkXYACfd.Jw.N.k3hFNBVb1CesrntQ3gqmc858ZK', '3', 'MASC', '2009-11-25', 'default.jpg', '1', 19, 3, '2876-3'),
(213, 'RM', '2914', 'Isabelle Alves Monteiro', '$2y$10$FQGBionHrZdLTioqSj1m1.OBd8DG9CZ12xB4iVRA/vNvuefLfRkqa', '3', 'FEM', '2011-02-08', 'default.jpg', '1', 19, 3, '2914-3'),
(214, 'RM', '2944', 'Isadora Lima da Silva', '$2y$10$DbpEOQ2HCqyBQ13r4DuVQek1/cLPt00Rwt60va74n1spoI7w/Zj/y', '3', 'FEM', '2011-04-15', 'default.jpg', '1', 19, 3, '2944-3'),
(215, 'RM', '3264', 'João Henrique dos Santos Silva', '$2y$10$wskVMlnddKLhkTDgiNvK5utZ9z4XJUBH3qsSLq.COley3oqbz8x92', '3', 'MASC', '2011-03-11', 'default.jpg', '1', 19, 3, '3264-3'),
(216, 'RM', '2912', 'Kauã Franco Santos', '$2y$10$DQBMRqnGhdFqpDU74XtkEuywrl329KGK6JGmGxEeRvXHkvnBX.GUO', '3', 'MASC', '2010-09-27', 'default.jpg', '1', 19, 3, '2912-3'),
(217, 'RM', '3141', 'Lara de Brito Milani das Dores', '$2y$10$nTHdp8GRuynX1SK3AbPk1ORKSiL/6kxhK/SDQPxR0J7jNb6wOMDOa', '3', 'FEM', '2010-07-30', 'default.jpg', '1', 19, 3, '3141-3'),
(218, 'RM', '2915', 'Laura Rodrigues Viana', '$2y$10$E30ZsPwM25gq4RIPqDvtR.kVbEktHDw4aw6cbu0osErn0C6HQDdki', '3', 'FEM', '2011-01-14', 'default.jpg', '1', 19, 3, '2915-3'),
(219, 'RM', '3152', 'Leonardo Artur Augusto Santos Felizardo', '$2y$10$v9aFAhC5CltYDczy/0Y4kuJvCfWd2ahTr4Op0u2Z5p2p5FzfWQQwq', '3', 'MASC', '2010-09-07', 'default.jpg', '1', 19, 3, '3152-3'),
(220, 'RM', '2929', 'Maiara Scarabelli Aguilera', '$2y$10$/Y5rmdvKn.kgfWS.0Vthnu2FJh5Ei5zqk3Emhtr89/1NH7NMcx1Q2', '3', 'FEM', '2011-06-27', 'default.jpg', '1', 19, 3, '2929-3'),
(221, 'RM', '3400', 'Marcos Felipe Mean dos Santos', '$2y$10$xy24ibwm/CcIegeAuzH6HOWnci3YqoAgwoW2Jlj32DTLBZpIeUrAe', '3', 'MASC', '2011-05-02', 'default.jpg', '1', 19, 3, '3400-3'),
(222, 'RM', '3072', 'Pedro Henrique Silva Madia', '$2y$10$V31hozaBPIVpY5xmn5Y9Du.e1zdvAeOpqfzcs5ICizUfDzccqIEEu', '3', 'MASC', '2010-12-27', 'default.jpg', '1', 19, 3, '3072-3'),
(223, 'RM', '2873', 'Rian Henrique de Macedo', '$2y$10$Q9VlCRnk29Izl23xEVJ6Oey5jIkTYHesg5ITZRb9a47bOHFszQNIa', '3', 'MASC', '2009-10-05', 'default.jpg', '1', 19, 3, '2873-3'),
(224, 'RM', '3070', 'Sofia Batista Vinha', '$2y$10$/g6qi9SC1tr/UuVK10HMNeT6vDA.hPCRBJb8MCvaTypNIxHZNckGW', '3', 'FEM', '2010-09-08', 'default.jpg', '1', 19, 3, '3070-3'),
(225, 'RM', '3067', 'vitor Silva Tomasetti', '$2y$10$DhPOjyu8jzTxpvSx0Lmq1elfUfg6lOjVZHLcD5QNUv.vm/ZZ0b/D.', '3', 'MASC', '2011-03-01', 'default.jpg', '1', 19, 3, '3067-3'),
(226, 'RM', '2905', 'William Augusto Gomes Ruedel', '$2y$10$XVXgWT1sunCUeSDmD3cSq.XOikaA1T46dbNMAuQyL7ud1Kai.oJ6W', '3', 'MASC', '2011-03-24', 'default.jpg', '1', 19, 3, '2905-3'),
(227, 'RM', '3473', 'Eder Mathias Bonhomme Lima', '$2y$10$uyjwHajWNUfrQGbTTjV.AecwMHe6ql2cIkh.S3.zdL.IjGC8uscni', '3', 'MASC', '2011-08-08', 'default.jpg', '1', 19, 3, '3473-3'),
(228, 'RM', '3475', 'Julia Carvalho Galindo Alecrim', '$2y$10$ejfSWE0/fG8LlJTgVGt6seIspLN/0y3Aqxz44QBQHs.jzQWYBEcWC', '3', 'FEM', '2010-07-23', 'default.jpg', '1', 19, 3, '3475-3'),
(229, 'RM', '3481', 'Gabriel Ferreira Bastos', '$2y$10$y7JzfoEeAx/A92Ah3YySluF0OQuyyYjUfqj6JCKgH3XSWlYOlDBay', '3', 'MASC', '2010-07-07', 'default.jpg', '1', 19, 3, '3481-3'),
(230, 'RM', '3263', 'Alice Fernanda dos Santos', '$2y$10$1bizR4Cnod/hGMSFzfCxnO/0pOfj1w5eb9PbeRDphigT84Yqnkfsi', '3', 'FEM', '2009-01-06', 'default.jpg', '1', 21, 3, '3263-3'),
(231, 'RM', '2810', 'Ana Clara do Amaral Justi', '$2y$10$nH27UQk.vGdqR2wxQOx34.HF2jpYDLhg966HHA7EYrdKxvupD7dtG', '3', 'FEM', '2009-04-03', 'default.jpg', '1', 21, 3, '2810-3'),
(232, 'RM', '3356', 'Braien Bernardino Delanhese Galvão', '$2y$10$L0c/3mdeL/8SH6O/u6HgX.5EGNkqW.z0bFuKCyvbjelXqJAmpOvh2', '3', 'MASC', '2008-04-03', 'default.jpg', '1', 21, 3, '3356-3'),
(233, 'RM', '2833', 'Breno Moura Bianchini', '$2y$10$Q5NmqLbpPec21ZDSrhTadeyAN/OTc2McWTJk/yTZLs0SK6oLzs.ni', '3', 'MASC', '2008-12-09', 'default.jpg', '1', 21, 3, '2833-3'),
(234, 'RM', '2416', 'Clara Tauane Moraes Dourado', '$2y$10$YgMPHmsqES9FOsud/cgjIeJP3XhpjiFLK/2qnDCfoQBu23svqH4eC', '3', 'FEM', '2007-01-26', 'default.jpg', '1', 21, 3, '2416-3'),
(235, 'RM', '3206', 'Eloisa Florence Lima', '$2y$10$TaQQAc4FQV8q7kPE1KzlPuqw66ajOexG8rlBkJJBn6AlRYO1FKNti', '3', 'FEM', '2009-06-15', 'default.jpg', '1', 21, 3, '3206-3'),
(236, 'RM', '2834', 'Enrico Antonio Rodrigues', '$2y$10$0QNGZ03EV9WM.9OfyIkq/OpsU0AldcHwvWYzsgTFkbo1lgSywN13i', '3', 'MASC', '2008-09-02', 'default.jpg', '1', 21, 3, '2834-3'),
(237, 'RM', '2838', 'Gabriela Pissinin Menossi', '$2y$10$MQBKrYbW1.XEXAfdyCUkyOlaO5OT/LkMNv.PDv405Aw0BuDjp9sPe', '3', 'FEM', '2009-01-20', 'default.jpg', '1', 21, 3, '2838-3'),
(238, 'RM', '2817', 'Geovana Eduarda Sousa de Oliveira', '$2y$10$4b1JU15noUN4pwWbeX4y0uF5AGMQG1ZeZTRbdz3xbBu3nRY9qpiGy', '3', 'FEM', '2008-09-21', 'user_238_1785324286.png', '1', 21, 3, '2817-3'),
(239, 'RM', '2814', 'Gustavo Santos Vieira', '$2y$10$O2hvcwBQyZVLjY1xmko6EOWjuWBL4SmE3rrLy2t66QtkjAy99S9tO', '3', 'MASC', '2009-03-27', 'default.jpg', '1', 21, 3, '2814-3'),
(240, 'RM', '3401', 'Henry Salvador Trindade Montoya Neto', '$2y$10$LSn3H0EoV6ku47.9olQ1kecVRFqBD3jDZilNyaxZilozleARlkj6a', '3', 'MASC', '2009-01-17', 'default.jpg', '1', 21, 3, '3401-3'),
(241, 'RM', '3002', 'Hugo Francisco da Silva', '$2y$10$8wa2SJi6xmUbcBA9/w2m2.ZfKtvUjS4jnu.UUVG2Hre2sm1.Kbb7.', '3', 'MASC', '2008-06-23', 'default.jpg', '1', 21, 3, '3002-3'),
(242, 'RM', '2835', 'Hytallo Gabriel Ruffo Flores', '$2y$10$3x3Dht/JcBd1T6omc6bUwuhKJQO2kdKurDFxhjnahn6avb8zPE1ri', '3', 'MASC', '2009-03-17', 'default.jpg', '1', 21, 3, '2835-3'),
(243, 'RM', '2826', 'Kaiky Gonçalves da Silva', '$2y$10$OXeGAovwetbONAVHjYw6dOL46BlY9USHX4xym0cXYihgHGVNSAAX6', '3', 'MASC', '2008-08-06', 'default.jpg', '1', 21, 3, '2826-3'),
(244, 'RM', '2813', 'Kaio Gabriel Alves Figueredo', '$2y$10$MLTksJmbZ6CxaLxWWeSZxuexMDCm2KrFZ2Cvm7Vpfdm17WuDx1gsS', '3', 'MASC', '2008-08-25', 'default.jpg', '1', 21, 3, '2813-3'),
(245, 'RM', '2829', 'Kauê Alves Rufino', '$2y$10$ucrX3mmMd3pF4BCZoOx3Su13/BEdMmFBcP7522KN.8.lIHywYtyAW', '3', 'MASC', '2008-10-11', 'default.jpg', '1', 21, 3, '2829-3'),
(246, 'RM', '2830', 'Kayky Teófilo de Souza Castriani', '$2y$10$IOBbakidZ3oqF8YfalCaAeFprXTSF.q1Efu12378lirLwJxH1dE.q', '3', 'MASC', '2009-03-10', 'default.jpg', '1', 21, 3, '2830-3'),
(247, 'RM', '2844', 'Lanna Livia Fernandes Andrade', '$2y$10$.9JalBDfbs.Jk1CBr/FRMuG243iVP9YtglvuiKnYAFuPPa4OLXAKu', '3', 'FEM', '2008-10-31', 'default.jpg', '1', 21, 3, '2844-3'),
(248, 'RM', '2851', 'Lara Wada Oguido', '$2y$10$u.MnRJITfsuhtTQxg5FzSuIKX7yN2rYZhfFFlwqE1pgz6ywwDHAVC', '3', 'FEM', '2009-03-26', 'default.jpg', '1', 21, 3, '2851-3'),
(249, 'RM', '3052', 'Luiz Gustavo Pereira de Souza', '$2y$10$PELJrprmcnnOr/s8RyaHK.aBVGgUwJUvbUaP7dyKO7hvvkxiN5Z0C', '3', 'MASC', '2008-10-15', 'default.jpg', '1', 21, 3, '3052-3'),
(250, 'RM', '2825', 'Luma Christofole Massaranduba', '$2y$10$blN45hZOkUC20utFMJPSOOn6jNlA6XesiS4j7DzlJn5gmQv3jh8yi', '3', 'FEM', '2008-10-30', 'default.jpg', '1', 21, 3, '2825-3'),
(251, 'RM', '2827', 'Murilo Beraldo Corral Fernandes', '$2y$10$PoCX4coEnO7a0/3avDvGXuj1DmEQ256w1e4P8y/5YMFotWpyYzKkK', '3', 'MASC', '2009-03-31', 'default.jpg', '1', 21, 3, '2827-3'),
(252, 'RM', '2832', 'Natália Caroline Bosisio', '$2y$10$iOkpEhMwkkr4h4EPy4oq.eJBUnkTXwRUKou5rJso4x1g8T8mAGyW6', '3', 'FEM', '2008-11-13', 'default.jpg', '1', 21, 3, '2832-3'),
(253, 'RM', '2815', 'Nicolas Henrique Mendes Oliveira', '$2y$10$84NOFG3Qmb..HEAcsvafKemAPvIDuJpmWMTxRGyXp9nSR8K5ZbXpW', '3', 'MASC', '2008-11-21', 'default.jpg', '1', 21, 3, '2815-3'),
(254, 'RM', '2820', 'Pietro Eduardo Coelho Dalbem', '$2y$10$dmQDXlDSN04sU8Y8Zyq2ouDfj04unZCY6Sm9QFr.rtDmmw5bhrmum', '3', 'MASC', '2009-03-04', 'default.jpg', '1', 21, 3, '2820-3'),
(255, 'RM', '3148', 'Rafael Antonio Mantovani', '$2y$10$2FuBB6ID1IjiBGpmpdtSYOf9yv/ARt1ZTspdk6DWnEFKF2RCgoXou', '3', 'MASC', '2008-06-13', 'default.jpg', '1', 21, 3, '3148-3'),
(256, 'RM', '2836', 'Ryan Rodrigo dos Santos Clemente da Silva', '$2y$10$j4sjF3tvTBdAC.tYS3Mm2O/3.P68oiAyXSuwWGQKzqu7qWJq2Hrm.', '3', 'MASC', '2009-03-25', 'default.jpg', '1', 21, 3, '2836-3'),
(257, 'RM', '2818', 'Ryan Rodrigues Rangel', '$2y$10$sPsvuiFZU1dRtIt2EAkEQuJU0PGdlvOAjuTC7Vr8GxCR7UirvT2km', '3', 'MASC', '2008-12-29', 'default.jpg', '1', 21, 3, '2818-3'),
(258, 'RM', '2823', 'Thainá Vitória da Silva Pontes', '$2y$10$dCkePZUEsSYYjPkHyzE7kOQo11mjealknVMpbb.tFP3Wn2zajxLY2', '3', 'FEM', '2009-01-07', 'default.jpg', '1', 21, 3, '2823-3'),
(259, 'RM', '2824', 'Thauan Mateus de Souza Ramos', '$2y$10$awbGhmzW5/qun2WdW.c02uIv/Z/Ag0rDWJcNK4CIdnP1RvgpHBEsi', '3', 'MASC', '2008-07-24', 'default.jpg', '1', 21, 3, '2824-3'),
(260, 'RM', '3308', 'Yasmin Gabriélli da Come Silva', '$2y$10$OqoTXnBKDkq4Srh7GzczU.mMzUh/f.kW4A.50nK02DrgxU71tmNnu', '3', 'FEM', '2008-06-14', 'default.jpg', '1', 21, 3, '3308-3'),
(261, 'RM', '3466', 'Alice dos Santos Zanelato', '$2y$10$tQHZOx/lXwUG/Y66xdpLlujm7V28Svfg1nO8QJZYHNs6dK.QQh9GC', '3', 'FEM', '2014-05-24', 'default.jpg', '1', 18, 3, '3466-3'),
(262, 'RM', '3447', 'Ana Beatriz dos Reis Oliveira', '$2y$10$hSxe.g78pylxI/VESsKWs.tllYvSNlxIbOvqu.5IJdBK95HPqRhr2', '3', 'FEM', '2014-10-05', 'default.jpg', '1', 18, 3, '3447-3'),
(263, 'RM', '3452', 'Ana Júlia Souza Silva', '$2y$10$feF..lGkXdJfLkq1gws7/eQIe8D.jCANj/m3tNyzvsp7VDGT9gcPO', '3', 'FEM', '2014-10-13', 'default.jpg', '1', 18, 3, '3452-3'),
(264, 'RM', '3468', 'Ana Laura Rodrigues', '$2y$10$HheavR0g8.WVP7ylH4zjReAc300mygb.U9RD7psElmBZVGT.Oh3D.', '3', 'FEM', '2014-09-29', 'default.jpg', '1', 18, 3, '3468-3'),
(265, 'RM', '3446', 'Apolo dos Santos Fonseca', '$2y$10$o7g9af6Su/vUQoIYPxwSD.LVMyVtYjRRwYTeFHsGKZy/rg/Ds53ue', '3', 'MASC', '2014-07-31', 'default.jpg', '1', 18, 3, '3446-3'),
(266, 'RM', '3470', 'Breno Gabriel Gonçalves Silva', '$2y$10$zbseGFG9yIcI6dHdHlYAX.Pjm9ARV1VtLlsfmMUDVUqdZtOj1n2kO', '3', 'MASC', '2014-06-01', 'default.jpg', '1', 18, 3, '3470-3'),
(267, 'RM', '3471', 'Bryan Gabriel da Silva Oliveira', '$2y$10$qfX/D8B0jJuj22HoNOTGZOJo0fO14IN.tyniZhLAoX3R.UGp4xbsG', '3', 'MASC', '2014-08-18', 'default.jpg', '1', 18, 3, '3471-3'),
(268, 'RM', '3462', 'Daniel Correia Carvalho', '$2y$10$nsBCxD/kqHEpwZR5HOXZE.aZDIXdE1bb2Gc65f0.Eoy2rJ3CnmNj2', '3', 'MASC', '2014-05-13', 'default.jpg', '1', 18, 3, '3462-3'),
(269, 'RM', '3459', 'Daniel Scatolon Machado', '$2y$10$QhdnTwW7yTDjweVvzSgE.eK9MwtiMyFCtZ.NK6tb2ZMRzBRAETS.6', '3', 'MASC', '2014-02-10', 'default.jpg', '1', 18, 3, '3459-3'),
(270, 'RM', '3439', 'Davi de Lima Rodrigues Monteiro', '$2y$10$/jt.scYgGurY5QH9hHadH.70wBwJAH1NwPO/Dzk4OEmsRJfsTWwJW', '3', 'MASC', '2014-05-19', 'default.jpg', '1', 18, 3, '3439-3'),
(271, 'RM', '3454', 'Francine Renata Crispim Moreira', '$2y$10$kX7fAjGb0CCJ4ZV/pB/DMeAIY.noHC0/dVR1jVkq29HzeIJLPd2dG', '3', 'FEM', '2014-10-08', 'default.jpg', '1', 18, 3, '3454-3'),
(272, 'RM', '3444', 'Gabrielli Donato Resende Batista', '$2y$10$JKJmilql1iBGCYlTUBynxeBPSiwFoio33Fo0u631Fy3ExRAGMBwz2', '3', 'FEM', '2014-11-26', 'default.jpg', '1', 18, 3, '3444-3'),
(273, 'RM', '3450', 'Isabella Cáceres Coronel', '$2y$10$TWmJsdivheq6AKjF1CKml.gR4BOQVRHx2HfurCMGkIiWhiQYjBfwC', '3', 'FEM', '2015-06-18', 'default.jpg', '1', 18, 3, '3450-3'),
(274, 'RM', '3465', 'Isadora Betine Pirão', '$2y$10$cjUUdcS6nM7t/6sathKfTOkYlKW5/a.Ly9qHw2g1uxYUkg6yJCQ4W', '3', 'FEM', '2014-04-14', 'default.jpg', '1', 18, 3, '3465-3'),
(275, 'RM', '3442', 'Livia Mariana Sobrinho Silva', '$2y$10$7.CAvoPqG3fd18HDlKxK1eXGd6dginRsTuvm9LCmg0l5DiqAcGA2K', '3', 'FEM', '2014-10-28', 'default.jpg', '1', 18, 3, '3442-3'),
(276, 'RM', '3464', 'Lorena Bento Seolin', '$2y$10$dlX0qt8mCu99BF7wRhliNu83u005DepGwpmMHkbFYzty/7tpKe7R2', '3', 'FEM', '2014-11-07', 'default.jpg', '1', 18, 3, '3464-3'),
(277, 'RM', '3458', 'Luiz Henrique Francisquini', '$2y$10$lhzKQsy9msUKHDQCzbgoLeaTpfVymEoL6w7QkQi6zmfzk55tWLtri', '3', 'MASC', '2014-12-26', 'default.jpg', '1', 18, 3, '3458-3'),
(278, 'RM', '3467', 'Maressa Carvalho Alecrim', '$2y$10$YDSgc6SeQYyHAEVqSRyHROza/W5VSzwcUaUWIYQXXPxIT8cFr7BBG', '3', 'FEM', '2014-11-26', 'default.jpg', '1', 18, 3, '3467-3'),
(279, 'RM', '3448', 'Maria Alice Pereira Lima', '$2y$10$p8AhMnCXsnJnKiT37uNmku85MVzN26sYEICqFkJNZYDdfnWZrBRyi', '3', 'FEM', '2014-08-08', 'default.jpg', '1', 18, 3, '3448-3'),
(280, 'RM', '3457', 'Maria Eduarda Pereira Macedo', '$2y$10$RPYCujn.liSJzWCullv.Qefl52T8sXTdo./gOcMuapY2bKW.k1ewu', '3', 'FEM', '2014-07-24', 'default.jpg', '1', 18, 3, '3457-3'),
(281, 'RM', '3460', 'Maria Gabriela Martins de Oliveira', '$2y$10$lEQ5cEPFI2C1nLb42WTo/.en1rlnU6ZpxGfdkyxJZMbasIutOuBiK', '3', 'FEM', '2014-10-12', 'default.jpg', '1', 18, 3, '3460-3'),
(282, 'RM', '3455', 'Mariana Flausino Vilalva', '$2y$10$iZ69BdjAmSjZS8lg09EO2.uwrg9adWSBCbvZK0jvxX7lf8ygH4sFm', '3', 'FEM', '2015-01-26', 'default.jpg', '1', 18, 3, '3455-3'),
(283, 'RM', '3438', 'Marinalva Gabriela Batista Hoedlich', '$2y$10$e2cLySBDyXVcvRLIINboVuKVy2K4DhI0yAWPTt2/r6AcSwp7q/CLK', '3', 'FEM', '2014-12-02', 'default.jpg', '1', 18, 3, '3438-3');
INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `nivel_usuario`, `genero_usuario`, `data_nasc_usuario`, `foto_usuario`, `status_usuario`, `turmas_id_turma`, `interclasses_id_interclasse`, `chave_usuario_edicao`) VALUES
(284, 'RM', '3469', 'Matheus Freitas Mariza', '$2y$10$O88T3VwUleQ3A9P4cvxpm.KCsJoByITQ7RQOO1aNEhrMpdAqZ2UBi', '3', 'MASC', '2015-03-23', 'default.jpg', '1', 18, 3, '3469-3'),
(285, 'RM', '3443', 'Pedro Augusto Martins Santos', '$2y$10$z68vg70GYNA/tgc3.szu/OdeMOdVEZY8vCfiW.mvHhhjEZ9OtVRfC', '3', 'MASC', '2015-01-31', 'default.jpg', '1', 18, 3, '3443-3'),
(286, 'RM', '3451', 'Pierre Nascimento Biazetto', '$2y$10$wxTELJXqAZ0Rtt4dceIwsOXrqimEuF.kHftKyoubwkIGwjaNpPNcW', '3', 'MASC', '2015-02-19', 'default.jpg', '1', 18, 3, '3451-3'),
(287, 'RM', '3453', 'Rafael Corbari Fraga de Souza', '$2y$10$bEX2F7FC/CMXH.EDFk6F.uEu5KMPkHnMZtyS5OiStASU895EUoECW', '3', 'MASC', '2015-01-17', 'default.jpg', '1', 18, 3, '3453-3'),
(288, 'RM', '3461', 'Rafaella Almeida Barcello', '$2y$10$PQw2Ftmot6NNBO2giLiPGeGxswAROW3b0h1yqTIe.iaJkYhwzHYtO', '3', 'FEM', '2014-06-07', 'default.jpg', '1', 18, 3, '3461-3'),
(289, 'RM', '3445', 'Samuel Acorse Ribeiro Costa', '$2y$10$u3LympH91udtymZNMPqfMuzTqJhlxaKO5YKjhbfmm.HpkMI7eUYGq', '3', 'MASC', '2014-11-06', 'default.jpg', '1', 18, 3, '3445-3'),
(290, 'RM', '3441', 'Samuel Barbosa Ferreira', '$2y$10$EIMbPJS79fJhAXP4WBBzcOOy3g0Ihh8vEvPKMqaic3ldwkVGwnwgC', '3', 'MASC', '2014-12-05', 'default.jpg', '1', 18, 3, '3441-3'),
(291, 'RM', '3449', 'Samuel Lucas Besson Souza', '$2y$10$1mQtLJNhKj2FlbV8BSdUK.OP2jQ4YSKNx1kLwBRufM7izkrYDMgZm', '3', 'MASC', '2014-12-15', 'default.jpg', '1', 18, 3, '3449-3'),
(292, 'RM', '3440', 'Sophia Gabrielly Oliveira', '$2y$10$naN3xQJ2zqRDvdSPra0.UODPnFzRPrRhq2YxDcrrySY.XYloMcqRe', '3', 'FEM', '2015-03-27', 'default.jpg', '1', 18, 3, '3440-3');

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
  MODIFY `id_equipe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

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
  MODIFY `id_jogo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de tabela `locais`
--
ALTER TABLE `locais`
  MODIFY `id_local` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `modalidades`
--
ALTER TABLE `modalidades`
  MODIFY `id_modalidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de tabela `ocorrencias`
--
ALTER TABLE `ocorrencias`
  MODIFY `id_ocorrencia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `ocorrencias_turmas`
--
ALTER TABLE `ocorrencias_turmas`
  MODIFY `id_ocorrencia_turma` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `partidas`
--
ALTER TABLE `partidas`
  MODIFY `id_partida` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

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
