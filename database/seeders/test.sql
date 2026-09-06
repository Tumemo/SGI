INSERT INTO `tipos_modalidades` (`id_tipo_modalidade`, `nome_tipo_modalidade`, `status_tipo_modalidade`) VALUES
(1, 'Mata-Mata', '1'),
(2, 'Individual', '1');


INSERT INTO `usuarios` (`id_usuario`, `sigla_usuario`, `matricula_usuario`, `nome_usuario`, `senha_usuario`, `nivel_usuario`, `genero_usuario`, `data_nasc_usuario`, `foto_usuario`, `status_usuario`, `turmas_id_turma`, `interclasses_id_interclasse`, `chave_usuario_edicao`) VALUES
(1, 'ADM', 'admin', 'Administrador SGI', '$2y$10$nhy/mXtKiIPYIESlZjowk.m6Y.RNAO4qwmn7GMy42yxVVfWondStq', '0', 'MASC', '2000-01-01', '', '1', NULL, NULL, NULL),
(2, 'COL', 'colab', 'Colaborador SGI', '$2y$10$nhy/mXtKiIPYIESlZjowk.m6Y.RNAO4qwmn7GMy42yxVVfWondStq', '1', 'MASC', '2000-01-01', 'default.png', '1', NULL, NULL, NULL),
(3, 'MES', 'mesario', 'Mesário SGI', '$2y$10$nhy/mXtKiIPYIESlZjowk.m6Y.RNAO4qwmn7GMy42yxVVfWondStq', '2', 'MASC', '2000-01-01', 'default.png', '1', NULL, NULL, NULL),
(4, 'RM', '2879', 'Aluno Competidor Teste', '$2y$10$nhy/mXtKiIPYIESlZjowk.m6Y.RNAO4qwmn7GMy42yxVVfWondStq', '3', 'MASC', '2010-05-15', 'default.png', '1', NULL, NULL, NULL);
