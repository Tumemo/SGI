-- O trigger tr_unico_interclasse_ativo tenta dar UPDATE em interclasses
-- durante um UPDATE na mesma tabela, o que o MySQL/MariaDB proíbe.
-- Isso gera erro fatal em HTML e quebra o JSON da API (SyntaxError no front).
-- A exclusividade (só um interclasse ativo) fica no PHP: api/interclasse.php

DROP TRIGGER IF EXISTS `tr_unico_interclasse_ativo`;
