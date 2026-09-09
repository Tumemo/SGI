ALTER TABLE jogos
    MODIFY data_jogo DATE NULL,
    MODIFY inicio_jogo TIME NULL,
    MODIFY termino_jogo TIME NULL,
    MODIFY locais_id_local INT(11) NULL;

CREATE TABLE agenda_blocos (
    id_bloco INT(11) NOT NULL AUTO_INCREMENT,
    id_interclasse INT(11) NOT NULL,
    id_usuario INT(11) NULL,
    chave_idempotencia VARCHAR(80) NOT NULL,
    parametros_json LONGTEXT NOT NULL,
    revisao INT(11) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmado_em DATETIME NULL,
    PRIMARY KEY (id_bloco),
    UNIQUE KEY uk_agenda_bloco_idempotencia (id_interclasse, chave_idempotencia),
    KEY idx_agenda_bloco_edicao (id_interclasse, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE agenda_reservas (
    id_reserva INT(11) NOT NULL AUTO_INCREMENT,
    id_interclasse INT(11) NOT NULL,
    id_modalidade INT(11) NOT NULL,
    chave_versao VARCHAR(64) NOT NULL DEFAULT '1',
    chave_tag VARCHAR(80) NOT NULL,
    id_jogo INT(11) NULL,
    data_reserva DATE NULL,
    inicio_reserva TIME NULL,
    termino_reserva TIME NULL,
    id_local INT(11) NULL,
    intervalo_troca_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    descanso_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    id_bloco INT(11) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_reserva),
    UNIQUE KEY uk_agenda_reserva_posicao (id_interclasse, id_modalidade, chave_versao, chave_tag),
    UNIQUE KEY uk_agenda_reserva_jogo (id_jogo),
    KEY idx_agenda_reserva_periodo (id_interclasse, data_reserva, inicio_reserva, termino_reserva, id_local),
    KEY idx_agenda_reserva_modalidade (id_modalidade, chave_tag)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE agenda_reservas_historico (
    id_historico INT(11) NOT NULL AUTO_INCREMENT,
    id_reserva INT(11) NULL,
    id_bloco INT(11) NULL,
    id_usuario INT(11) NULL,
    operacao VARCHAR(24) NOT NULL,
    anterior_json LONGTEXT NULL,
    novo_json LONGTEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_historico),
    KEY idx_agenda_historico_reserva (id_reserva, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
