ALTER TABLE jogos
    ADD COLUMN exige_vinculo_ponto TINYINT(1) NOT NULL DEFAULT 1 AFTER status_jogo;

ALTER TABLE artilheiros
    ADD COLUMN partidas_id_partida INT(11) DEFAULT NULL AFTER jogos_id_jogo,
    ADD COLUMN equipes_id_equipe INT(11) DEFAULT NULL AFTER partidas_id_partida,
    ADD COLUMN conta_no_placar TINYINT(1) NOT NULL DEFAULT 1 AFTER num_gol,
    ADD COLUMN status_artilheiro ENUM('ativo','anulado') NOT NULL DEFAULT 'ativo' AFTER conta_no_placar,
    ADD COLUMN chave_jogada VARCHAR(180) DEFAULT NULL AFTER status_artilheiro,
    ADD COLUMN registrado_por INT(11) DEFAULT NULL AFTER chave_jogada,
    ADD COLUMN registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER registrado_por,
    ADD COLUMN anulado_por INT(11) DEFAULT NULL AFTER registrado_em,
    ADD COLUMN anulado_em DATETIME DEFAULT NULL AFTER anulado_por,
    ADD KEY idx_artilheiros_partida (partidas_id_partida),
    ADD KEY idx_artilheiros_equipe (equipes_id_equipe),
    ADD KEY idx_artilheiros_status (jogos_id_jogo, status_artilheiro, conta_no_placar),
    ADD UNIQUE KEY uk_artilheiros_chave_jogada (chave_jogada),
    ADD CONSTRAINT fk_artilheiros_partida FOREIGN KEY (partidas_id_partida) REFERENCES partidas (id_partida),
    ADD CONSTRAINT fk_artilheiros_equipe FOREIGN KEY (equipes_id_equipe) REFERENCES equipes (id_equipe),
    ADD CONSTRAINT fk_artilheiros_registrado_por FOREIGN KEY (registrado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    ADD CONSTRAINT fk_artilheiros_anulado_por FOREIGN KEY (anulado_por) REFERENCES usuarios (id_usuario) ON DELETE SET NULL;

-- Jogos que já existiam antes desta migração continuam consultáveis e
-- retificáveis como histórico legado. Todo jogo criado depois usa o default
-- estrito (1), no qual qualquer gol precisa de um evento individual vinculado.
UPDATE jogos SET exige_vinculo_ponto = 0;
