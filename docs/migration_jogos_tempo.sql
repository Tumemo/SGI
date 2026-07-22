-- ============================================================
-- MIGRAÇÃO: Colunas de tempo e controle de partida na tabela jogos
-- Data: 2026-07-20
-- Descricao: Adiciona suporte a cronômetro sincronizado,
--            tempo extra (prorrogação) e rastreamento de
--            duração real da partida.
-- ============================================================

-- 1. tempo_restante_jogo: segundos restantes no momento do último snapshot
--    (usado para pausa/page refresh — o valor é recalculado pelo servidor
--     quando o status é 'Iniciado' com base em data_inicio_real).
ALTER TABLE `jogos`
  ADD COLUMN `tempo_restante_jogo` INT(11) NULL DEFAULT NULL
  AFTER `status_jogo`;

-- 2. duracao_jogo: duração total programada da partida em segundos
--    (ex.: 20 min → 1200). Definida ao iniciar o jogo.
ALTER TABLE `jogos`
  ADD COLUMN `duracao_jogo` INT(11) NULL DEFAULT NULL
  AFTER `tempo_restante_jogo`;

-- 3. tempo_extra_jogo: total de segundos extras adicionados (prorrogações).
--    Cada "Adicionar tempo extra" incrementa este campo.
ALTER TABLE `jogos`
  ADD COLUMN `tempo_extra_jogo` INT(11) NOT NULL DEFAULT 0
  AFTER `duracao_jogo`;

-- 4. data_inicio_real: timestamp do momento em que o jogo foi iniciado
--    (ou retomado de pausa). Usado para calcular tempo restante
--    no servidor e evitar manipulação do cronômetro pelo cliente.
ALTER TABLE `jogos`
  ADD COLUMN `data_inicio_real` DATETIME NULL DEFAULT NULL
  AFTER `tempo_extra_jogo`;

-- ============================================================
-- ATUALIZAR O SCHEMA OFICIAL (sgi_bdFinal.sql) também:
-- Copiar as colunas acima para o CREATE TABLE de `jogos`.
-- ============================================================
