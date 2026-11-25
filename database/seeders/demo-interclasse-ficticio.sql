-- Carga fictícia e não destrutiva para a edição "Interclasses SESI 2026".
-- Requer o schema atualizado por todas as migrações.
-- Pode ser executada novamente: usa chaves naturais e não apaga registros.
-- Os dados são para demonstração em banco isolado, nunca para produção.

SET @demo_nome_edicao := 'Interclasses SESI 2026';
START TRANSACTION;

INSERT INTO interclasses (
    nome_interclasse, ano_interclasse, regulamento_interclasse,
    status_interclasse, ponto_1_lugar, ponto_2_lugar, ponto_3_lugar,
    valor_item_arrecadacao
)
SELECT @demo_nome_edicao, CONCAT(YEAR(CURDATE()), '-01-01 00:00:00'), '', '1', 10, 7, 5, 2
WHERE NOT EXISTS (
    SELECT 1 FROM interclasses WHERE nome_interclasse = @demo_nome_edicao
);

SET @demo_edicao := (
    SELECT id_interclasse FROM interclasses
    WHERE nome_interclasse = @demo_nome_edicao
    ORDER BY id_interclasse LIMIT 1
);

INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade)
SELECT 'Mata-Mata', '1'
WHERE NOT EXISTS (
    SELECT 1 FROM tipos_modalidades WHERE LOWER(TRIM(nome_tipo_modalidade)) = 'mata-mata'
);
INSERT INTO tipos_modalidades (nome_tipo_modalidade, status_tipo_modalidade)
SELECT 'Individual', '1'
WHERE NOT EXISTS (
    SELECT 1 FROM tipos_modalidades WHERE LOWER(TRIM(nome_tipo_modalidade)) = 'individual'
);
UPDATE tipos_modalidades SET status_tipo_modalidade = '1'
WHERE LOWER(TRIM(nome_tipo_modalidade)) IN ('mata-mata', 'individual');

INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse)
SELECT 'Categoria I', '1', @demo_edicao
WHERE NOT EXISTS (
    SELECT 1 FROM categorias
    WHERE interclasses_id_interclasse = @demo_edicao AND nome_categoria = 'Categoria I'
);
INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse)
SELECT 'Categoria II', '1', @demo_edicao
WHERE NOT EXISTS (
    SELECT 1 FROM categorias
    WHERE interclasses_id_interclasse = @demo_edicao AND nome_categoria = 'Categoria II'
);
SET @demo_categoria_1 := (
    SELECT id_categoria FROM categorias
    WHERE interclasses_id_interclasse = @demo_edicao AND nome_categoria = 'Categoria I'
    ORDER BY id_categoria LIMIT 1
);
SET @demo_categoria_2 := (
    SELECT id_categoria FROM categorias
    WHERE interclasses_id_interclasse = @demo_edicao AND nome_categoria = 'Categoria II'
    ORDER BY id_categoria LIMIT 1
);
UPDATE categorias SET status_categoria = '1'
WHERE id_categoria IN (@demo_categoria_1, @demo_categoria_2);

CREATE TEMPORARY TABLE IF NOT EXISTS demo_interclasse_turmas (
    nome_turma VARCHAR(45) PRIMARY KEY,
    nome_fantasia VARCHAR(45) NOT NULL,
    categoria VARCHAR(45) NOT NULL
);
DELETE FROM demo_interclasse_turmas;
INSERT INTO demo_interclasse_turmas (nome_turma, nome_fantasia, categoria) VALUES
    ('6EF', '6º Ano A', 'Categoria I'),
    ('7EF', '7º Ano A', 'Categoria I'),
    ('8EF', '8º Ano A', 'Categoria I'),
    ('9EF', '9º Ano A', 'Categoria II'),
    ('1EMA', '1º Médio A', 'Categoria II'),
    ('2EMA', '2º Médio A', 'Categoria II'),
    ('3EMA', '3º Médio A', 'Categoria II');

INSERT INTO turmas (
    interclasses_id_interclasse, nome_turma, turno_turma, nome_fantasia_turma,
    status_turma, categorias_id_categoria
)
SELECT @demo_edicao, s.nome_turma, 'manha', s.nome_fantasia, '1',
       CASE s.categoria WHEN 'Categoria I' THEN @demo_categoria_1 ELSE @demo_categoria_2 END
FROM demo_interclasse_turmas s
WHERE NOT EXISTS (
    SELECT 1 FROM turmas t
    WHERE t.interclasses_id_interclasse = @demo_edicao AND t.nome_turma = s.nome_turma
);
UPDATE turmas t
JOIN demo_interclasse_turmas s ON s.nome_turma = t.nome_turma
SET t.status_turma = '1',
    t.nome_fantasia_turma = s.nome_fantasia,
    t.categorias_id_categoria = CASE s.categoria
        WHEN 'Categoria I' THEN @demo_categoria_1 ELSE @demo_categoria_2 END
WHERE t.interclasses_id_interclasse = @demo_edicao;

CREATE TEMPORARY TABLE IF NOT EXISTS demo_interclasse_modalidades (
    categoria VARCHAR(45) NOT NULL,
    nome_modalidade VARCHAR(45) NOT NULL,
    genero ENUM('FEM','MASC','MISTO') NOT NULL,
    tipo VARCHAR(45) NOT NULL,
    max_inscritos INT NOT NULL,
    min_equipe INT NOT NULL,
    max_equipe INT NOT NULL,
    formato ENUM('equipe','dupla','individual') NOT NULL,
    duracao_min SMALLINT UNSIGNED NOT NULL,
    contabiliza_artilharia TINYINT(1) NOT NULL,
    PRIMARY KEY (categoria, nome_modalidade, genero)
);
DELETE FROM demo_interclasse_modalidades;
INSERT INTO demo_interclasse_modalidades VALUES
    ('Categoria I',  'Futsal - MA',   'MASC',  'Mata-Mata', 10, 5, 10, 'equipe',     25, 1),
    ('Categoria I',  'Queimada - MI', 'MISTO', 'Mata-Mata', 20, 6, 20, 'equipe',     15, 0),
    ('Categoria I',  'Volei - MI',    'MISTO', 'Mata-Mata', 12, 6, 12, 'equipe',     25, 0),
    ('Categoria I',  'Corrida - FE',  'FEM',   'Individual', 2, 1,  2, 'individual', 15, 0),
    ('Categoria I',  'Corrida - MA',  'MASC',  'Individual', 2, 1,  2, 'individual', 15, 0),
    ('Categoria II', 'Futsal - MA',   'MASC',  'Mata-Mata', 10, 5, 10, 'equipe',     25, 1),
    ('Categoria II', 'Queimada - MI', 'MISTO', 'Mata-Mata', 20, 6, 20, 'equipe',     15, 0),
    ('Categoria II', 'Volei - MI',    'MISTO', 'Mata-Mata', 12, 6, 12, 'equipe',     25, 0),
    ('Categoria II', 'Corrida - FE',  'FEM',   'Individual', 2, 1,  2, 'individual', 15, 0),
    ('Categoria II', 'Corrida - MA',  'MASC',  'Individual', 2, 1,  2, 'individual', 15, 0);

INSERT INTO modalidades (
    nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes,
    status_modalidade, tipos_modalidades_id_tipo_modalidade,
    categorias_id_categoria, interclasses_id_interclasse, contabiliza_artilharia
)
SELECT s.nome_modalidade, s.genero,
       s.max_inscritos, 4, '1', tm.id_tipo_modalidade,
       CASE s.categoria WHEN 'Categoria I' THEN @demo_categoria_1 ELSE @demo_categoria_2 END,
       @demo_edicao, s.contabiliza_artilharia
FROM demo_interclasse_modalidades s
JOIN tipos_modalidades tm ON LOWER(TRIM(tm.nome_tipo_modalidade)) = LOWER(s.tipo)
WHERE NOT EXISTS (
    SELECT 1 FROM modalidades m
    WHERE m.interclasses_id_interclasse = @demo_edicao
      AND m.categorias_id_categoria = CASE s.categoria
          WHEN 'Categoria I' THEN @demo_categoria_1 ELSE @demo_categoria_2 END
      AND m.nome_modalidade = s.nome_modalidade
      AND m.genero_modalidade = s.genero
);
UPDATE modalidades m
JOIN demo_interclasse_modalidades s
  ON s.nome_modalidade = m.nome_modalidade AND s.genero = m.genero_modalidade
JOIN tipos_modalidades tm ON LOWER(TRIM(tm.nome_tipo_modalidade)) = LOWER(s.tipo)
SET m.status_modalidade = '1',
    m.max_inscrito_modalidade = s.max_inscritos,
    m.max_equipes = 4,
    m.tipos_modalidades_id_tipo_modalidade = tm.id_tipo_modalidade,
    m.contabiliza_artilharia = s.contabiliza_artilharia
WHERE m.interclasses_id_interclasse = @demo_edicao
  AND m.categorias_id_categoria = CASE s.categoria
      WHEN 'Categoria I' THEN @demo_categoria_1 ELSE @demo_categoria_2 END;

-- Desativa uma modalidade de teste deixada por cargas antigas, sem apagar histórico.
UPDATE modalidades SET status_modalidade = '0'
WHERE interclasses_id_interclasse = @demo_edicao
  AND LOWER(TRIM(nome_modalidade)) = 'teste';

INSERT INTO locais (nome_local, disponivel_local, carga_local, status_local, interclasses_id_interclasse)
SELECT 'Ginásio Poliesportivo Principal', '1', 8, '1', @demo_edicao
WHERE NOT EXISTS (
    SELECT 1 FROM locais WHERE interclasses_id_interclasse = @demo_edicao
      AND nome_local = 'Ginásio Poliesportivo Principal'
);
INSERT INTO locais (nome_local, disponivel_local, carga_local, status_local, interclasses_id_interclasse)
SELECT 'Quadra Externa A', '1', 6, '1', @demo_edicao
WHERE NOT EXISTS (
    SELECT 1 FROM locais WHERE interclasses_id_interclasse = @demo_edicao
      AND nome_local = 'Quadra Externa A'
);
UPDATE locais SET disponivel_local = '1', status_local = '1'
WHERE interclasses_id_interclasse = @demo_edicao
  AND nome_local IN ('Ginásio Poliesportivo Principal', 'Quadra Externa A');

INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe)
SELECT '1', m.id_modalidade, t.id_turma,
       CONCAT(t.nome_turma, ' ', m.nome_modalidade, ' - 1')
FROM modalidades m
JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
JOIN turmas t ON t.interclasses_id_interclasse = @demo_edicao
             AND t.categorias_id_categoria = c.id_categoria
JOIN demo_interclasse_modalidades s
  ON s.nome_modalidade = m.nome_modalidade
 AND s.genero = m.genero_modalidade
 AND s.categoria = c.nome_categoria
WHERE m.interclasses_id_interclasse = @demo_edicao
  AND NOT EXISTS (
      SELECT 1 FROM equipes e
      WHERE e.modalidades_id_modalidade = m.id_modalidade
        AND e.turmas_id_turma = t.id_turma
  );

CREATE TEMPORARY TABLE IF NOT EXISTS demo_interclasse_numeros (
    n TINYINT UNSIGNED PRIMARY KEY
);
DELETE FROM demo_interclasse_numeros;
INSERT INTO demo_interclasse_numeros (n) VALUES
    (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),
    (11),(12),(13),(14),(15),(16),(17),(18),(19);

-- 19 alunos fictícios por turma: as equipes recebem elencos completos e sem
-- exceder três modalidades por aluno. A senha de demonstração é "123".
INSERT INTO usuarios (
    sigla_usuario, matricula_usuario, nome_usuario, senha_usuario,
    nivel_usuario, genero_usuario, data_nasc_usuario, foto_usuario,
    status_usuario, turmas_id_turma, interclasses_id_interclasse
)
SELECT 'RM',
       CONCAT('FIC', @demo_edicao, '-', t.id_turma, '-', LPAD(n.n, 2, '0')),
       CONCAT('Aluno Fictício ', t.nome_turma, ' ', LPAD(n.n, 2, '0')),
       '$2y$12$B3a1zpJmPeVJ0lF9XlD77umnIzLrysr2WYzmtJDgjvtYgGbXYDjNy',
       '3',
       CASE
           WHEN n.n BETWEEN 1 AND 5 THEN 'MASC'
           WHEN n.n BETWEEN 6 AND 8 THEN 'FEM'
           WHEN n.n BETWEEN 9 AND 11 THEN 'MASC'
           WHEN n.n BETWEEN 12 AND 14 THEN 'FEM'
           WHEN n.n BETWEEN 15 AND 17 THEN 'MASC'
           WHEN n.n = 18 THEN 'FEM'
           ELSE 'MASC'
       END,
       DATE_SUB(CURDATE(), INTERVAL CASE t.nome_turma
           WHEN '6EF' THEN 11 WHEN '7EF' THEN 12 WHEN '8EF' THEN 13
           WHEN '9EF' THEN 14 WHEN '1EMA' THEN 15 WHEN '2EMA' THEN 16
           ELSE 17 END YEAR),
       '', '1', t.id_turma, @demo_edicao
FROM turmas t
CROSS JOIN demo_interclasse_numeros n
WHERE t.interclasses_id_interclasse = @demo_edicao
  AND t.status_turma = '1'
ON DUPLICATE KEY UPDATE
    nome_usuario = VALUES(nome_usuario),
    senha_usuario = VALUES(senha_usuario),
    nivel_usuario = VALUES(nivel_usuario),
    genero_usuario = VALUES(genero_usuario),
    data_nasc_usuario = VALUES(data_nasc_usuario),
    status_usuario = VALUES(status_usuario),
    turmas_id_turma = VALUES(turmas_id_turma);

-- A preparação original do ambiente pode ter importado uma lista de alunos.
-- Preserva IDs e vínculos técnicos, mas troca os dados pessoais por identidades
-- fictícias e invalida sessões anteriores nessa edição de demonstração.
UPDATE usuarios u
JOIN turmas t ON t.id_turma = u.turmas_id_turma
SET u.matricula_usuario = CONCAT('FICOLD', @demo_edicao, '-', u.id_usuario),
    u.nome_usuario = CONCAT('Aluno Fictício Extra ', LPAD(u.id_usuario, 4, '0')),
    u.senha_usuario = '$2y$12$B3a1zpJmPeVJ0lF9XlD77umnIzLrysr2WYzmtJDgjvtYgGbXYDjNy',
    u.senha_troca_pendente = 0,
    u.genero_usuario = IF(MOD(u.id_usuario, 2) = 0, 'FEM', 'MASC'),
    u.data_nasc_usuario = DATE_SUB(CURDATE(), INTERVAL CASE t.nome_turma
        WHEN '6EF' THEN 11 WHEN '7EF' THEN 12 WHEN '8EF' THEN 13
        WHEN '9EF' THEN 14 WHEN '1EMA' THEN 15 WHEN '2EMA' THEN 16
        ELSE 17 END YEAR),
    u.foto_usuario = '',
    u.chave_usuario_edicao = NULL,
    u.auth_version = u.auth_version + 1
WHERE u.interclasses_id_interclasse = @demo_edicao
  AND u.nivel_usuario = '3'
  AND u.matricula_usuario NOT LIKE CONCAT('FIC', @demo_edicao, '-%')
  AND u.matricula_usuario NOT LIKE CONCAT('FICOLD', @demo_edicao, '-%');

INSERT IGNORE INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario)
SELECT e.id_equipe, u.id_usuario
FROM equipes e
JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade
JOIN turmas t ON t.id_turma = e.turmas_id_turma
JOIN usuarios u ON u.turmas_id_turma = t.id_turma
               AND u.interclasses_id_interclasse = @demo_edicao
               AND u.matricula_usuario LIKE CONCAT('FIC', @demo_edicao, '-', t.id_turma, '-%')
JOIN demo_interclasse_modalidades s ON s.nome_modalidade = m.nome_modalidade
                                   AND s.genero = m.genero_modalidade
JOIN demo_interclasse_numeros n
  ON n.n = CAST(SUBSTRING_INDEX(u.matricula_usuario, '-', -1) AS UNSIGNED)
WHERE m.interclasses_id_interclasse = @demo_edicao
  AND m.status_modalidade = '1'
  AND e.status_equipe = '1'
  AND (
      (m.nome_modalidade LIKE 'Futsal%' AND n.n BETWEEN 1 AND 5)
      OR (m.nome_modalidade LIKE 'Queimada%' AND n.n BETWEEN 6 AND 11)
      OR (m.nome_modalidade LIKE 'Volei%' AND n.n BETWEEN 12 AND 17)
      OR (m.nome_modalidade = 'Corrida - FE' AND n.n = 18)
      OR (m.nome_modalidade = 'Corrida - MA' AND n.n = 19)
  );

INSERT INTO modalidade_planejamentos (
    id_modalidade, equipes_planejadas, min_inscritos_equipe,
    max_inscritos_equipe, formato_participacao, duracao_prevista_min, descanso_min
)
SELECT m.id_modalidade, 1, s.min_equipe, s.max_equipe,
       s.formato, s.duracao_min, 5
FROM modalidades m
JOIN categorias c ON c.id_categoria = m.categorias_id_categoria
JOIN demo_interclasse_modalidades s
  ON s.nome_modalidade = m.nome_modalidade
 AND s.genero = m.genero_modalidade
 AND s.categoria = c.nome_categoria
WHERE m.interclasses_id_interclasse = @demo_edicao
ON DUPLICATE KEY UPDATE
    equipes_planejadas = VALUES(equipes_planejadas),
    min_inscritos_equipe = VALUES(min_inscritos_equipe),
    max_inscritos_equipe = VALUES(max_inscritos_equipe),
    formato_participacao = VALUES(formato_participacao),
    duracao_prevista_min = VALUES(duracao_prevista_min),
    descanso_min = VALUES(descanso_min);

INSERT IGNORE INTO interclasse_planejamentos (id_interclasse, cronograma_status, inscricoes_status)
VALUES (@demo_edicao, 'rascunho', 'fechadas');
UPDATE interclasses SET status_interclasse = '1'
WHERE id_interclasse = @demo_edicao;

COMMIT;

SELECT @demo_edicao AS id_edicao_demo,
       (SELECT COUNT(*) FROM turmas WHERE interclasses_id_interclasse = @demo_edicao AND status_turma = '1') AS turmas,
       (SELECT COUNT(*) FROM modalidades WHERE interclasses_id_interclasse = @demo_edicao AND status_modalidade = '1') AS modalidades_ativas,
       (SELECT COUNT(*) FROM equipes e JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = @demo_edicao AND m.status_modalidade = '1' AND e.status_equipe = '1') AS equipes_ativas,
       (SELECT COUNT(*) FROM usuarios WHERE interclasses_id_interclasse = @demo_edicao AND nivel_usuario = '3' AND status_usuario = '1') AS alunos_ficticios,
       (SELECT COUNT(*) FROM locais WHERE interclasses_id_interclasse = @demo_edicao AND status_local = '1') AS locais_ativos,
       (SELECT COUNT(*) FROM equipes_has_usuarios ehu JOIN equipes e ON e.id_equipe = ehu.equipes_id_equipe JOIN modalidades m ON m.id_modalidade = e.modalidades_id_modalidade WHERE m.interclasses_id_interclasse = @demo_edicao) AS vinculos_aluno_equipe;
