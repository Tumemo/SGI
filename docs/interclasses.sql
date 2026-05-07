-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/05/2026 às 20:39
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
-- Estrutura para tabela `interclasses`
--

CREATE TABLE `interclasses` (
  `id_interclasse` int(11) NOT NULL,
  `nome_interclasse` varchar(45) DEFAULT NULL,
  `ano_interclasse` datetime DEFAULT NULL,
  `regulamento_interclasse` varchar(255) DEFAULT NULL,
  `status_interclasse` enum('1','0') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `interclasses`
--

INSERT INTO `interclasses` (`id_interclasse`, `nome_interclasse`, `ano_interclasse`, `regulamento_interclasse`, `status_interclasse`) VALUES
(4, 'Interclasse teste', NULL, 'regulamento.pjg', '1');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `interclasses`
--
ALTER TABLE `interclasses`
  ADD PRIMARY KEY (`id_interclasse`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `interclasses`
--
ALTER TABLE `interclasses`
  MODIFY `id_interclasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
