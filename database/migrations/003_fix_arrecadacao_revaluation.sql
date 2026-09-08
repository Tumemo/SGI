DROP TRIGGER IF EXISTS `tr_atualiza_pontos_arrecadacao`;
DELIMITER $$
CREATE TRIGGER `tr_atualiza_pontos_arrecadacao` AFTER UPDATE ON `interclasses` FOR EACH ROW BEGIN
    IF OLD.valor_item_arrecadacao <> NEW.valor_item_arrecadacao THEN
        UPDATE turmas
        SET pontuacao_turma = pontuacao_turma
            - ROUND(qtd_itens_arrecadados * OLD.valor_item_arrecadacao, 0)
            + ROUND(qtd_itens_arrecadados * NEW.valor_item_arrecadacao, 0)
        WHERE interclasses_id_interclasse = NEW.id_interclasse;
    END IF;
END
$$
DELIMITER ;
