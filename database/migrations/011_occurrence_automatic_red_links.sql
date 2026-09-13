-- Vermelhos automáticos novos têm vínculo explícito com o atleta, jogo e
-- amarelo que os originou. Ocorrências antigas não são inferidas a partir do
-- texto livre: permanecem históricas e sem classificação automática.
CREATE TABLE ocorrencias_vermelhos_automaticos (
    ocorrencia_vermelha_id INT(11) NOT NULL,
    usuarios_id_usuario INT(11) NOT NULL,
    jogos_id_jogo INT(11) NOT NULL,
    ocorrencia_amarela_origem_id INT(11) NOT NULL,
    PRIMARY KEY (ocorrencia_vermelha_id),
    UNIQUE KEY uk_vermelho_automatico_usuario_jogo (usuarios_id_usuario, jogos_id_jogo),
    KEY idx_vermelho_automatico_amarelo (ocorrencia_amarela_origem_id),
    CONSTRAINT fk_vermelho_automatico_ocorrencia
        FOREIGN KEY (ocorrencia_vermelha_id) REFERENCES ocorrencias (id_ocorrencia)
        ON DELETE CASCADE,
    CONSTRAINT fk_vermelho_automatico_amarelo
        FOREIGN KEY (ocorrencia_amarela_origem_id) REFERENCES ocorrencias (id_ocorrencia)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
