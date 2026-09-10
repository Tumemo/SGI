# Plano do schema inicial consolidado

## Objetivo

Criar um baseline SQL único para uma instalação vazia do SGI, incorporando o
estado estrutural produzido pelas migrations `001` a `010`. A base atual não é
produção, portanto o baseline pode nascer já com a estrutura final sem exigir
um caminho de upgrade de dados.

O artefato executável é `database/schema-inicial.sql`. As migrations existentes
permanecem intactas para preservar o histórico, os checksums e a capacidade de
atualizar bases que ainda usam o `MigrationRunner`.

## Inventário e decisões de consolidação

| Migration | Conteúdo absorvido no baseline |
| --- | --- |
| `001_initial_schema.sql` | Tabelas, chaves, índices, auto incrementos, foreign keys e triggers originais. |
| `002_mutation_fingerprint.sql` | Coluna `sincronizacoes_idempotentes.request_hash`. |
| `003_fix_arrecadacao_revaluation.sql` | Versão corrigida de `tr_atualiza_pontos_arrecadacao`, que troca somente o crédito antigo pelo novo e preserva os demais pontos. |
| `004_podio_credit_sources.sql` | Tabela `pontuacoes_podio`, seus índices, unicidade e foreign keys. |
| `005_agendamento_blocos.sql` | Campos de agenda opcionais em `jogos` e as tabelas `agenda_blocos`, `agenda_reservas` e `agenda_reservas_historico`. |
| `006_unique_category_name_per_edition.sql` | Chave única `uk_categorias_edicao_nome`. |
| `007_publicacao_ranking.sql` | `ranking_publicado_em`, `ranking_publicado_por` e remoção do trigger `tr_sincroniza_status_usuarios`. |
| `008_auth_version.sql` | `usuarios.auth_version`. |
| `009_occurrence_penalty_invariant.sql` | Checks `chk_ocorrencias_turmas_pontos_nonnegative` e `chk_ocorrencias_penalidade_nonnegative`. |
| `010_vinculo_obrigatorio_pontos.sql` | Vínculo obrigatório de ponto em jogos e os campos, índices e foreign keys de artilharia. |

As instruções de normalização de dados das migrations `007`, `009` e `010` não
foram copiadas como operações de carga: em uma base vazia não há dados legados
para publicar, normalizar ou reclassificar. O default final de
`jogos.exige_vinculo_ponto` permanece `1`, que é a política para jogos novos.

## Estrutura final esperada

O baseline contém 22 tabelas de aplicação:

- núcleo de edições: `interclasses`, `categorias`, `turmas`, `locais`;
- modalidades e participantes: `tipos_modalidades`, `modalidades`, `equipes`,
  `usuarios`, `equipes_has_usuarios`, `usuarios_has_interclasses`;
- competição e pontuação: `jogos`, `partidas`, `artilheiros`, `pontuacoes`,
  `pontuacoes_podio`;
- resultados e disciplina: `historico_arrecadacoes`, `ocorrencias`,
  `ocorrencias_turmas`;
- agenda: `agenda_blocos`, `agenda_reservas`, `agenda_reservas_historico`;
- sincronização: `sincronizacoes_idempotentes`.

O único trigger ativo é `tr_atualiza_pontos_arrecadacao`. O trigger
`tr_sincroniza_status_usuarios` não deve ser recriado, porque foi removido na
migration `007`.

## Plano de execução

1. Criar uma base vazia com `utf8mb4` e `utf8mb4_general_ci`.
2. Selecionar essa base e executar `database/schema-inicial.sql` uma única vez.
3. Confirmar em `information_schema` a existência das 22 tabelas, dos índices
   únicos, das foreign keys, dos dois checks de penalidade e do trigger de
   arrecadação.
4. Executar `database/seeders/test.sql` somente em uma base de testes, nunca
   como parte do baseline de produção.
5. Rodar a suíte de integração contra a base criada e confirmar os fluxos de
   autenticação, agenda, ranking, arrecadação, disciplina, artilharia,
   chaveamento e sincronização offline.
6. Para a instalação nova que adotar o baseline como caminho oficial, decidir
   explicitamente como o `MigrationRunner` registrará esse estado: a tabela
   técnica `sgi_migrations` não faz parte do schema de domínio e não é criada
   por este arquivo. Não se deve executar o runner sobre uma base criada pelo
   baseline sem primeiro registrar um baseline compatível ou ajustar o fluxo de
   instalação.

## Validação de paridade

A validação deve comparar uma base criada pelo conjunto das migrations com uma
base criada pelo `schema-inicial.sql`, desconsiderando apenas:

- dados de teste e dados legados;
- a tabela técnica `sgi_migrations` e seus registros;
- diferenças de ordem textual que não alterem a definição SQL.

Devem ser iguais, no mínimo, nomes e tipos de colunas, nulabilidade, defaults,
chaves primárias, índices, foreign keys, checks e triggers. Também devem ser
executados os testes de repetição do runner na base de migrations, pois o
baseline não substitui o mecanismo de upgrade de bases existentes nesta etapa.

## Critérios de aceite

- Uma base vazia aceita o SQL sem `ALTER TABLE` posterior.
- Não existe trigger legado de sincronização de status.
- Categorias não aceitam nomes duplicados dentro da mesma edição.
- Penalidades persistidas não aceitam valores negativos.
- Jogos novos exigem vínculo de ponto por default.
- Artilharia suporta partida, equipe, autoria, anulação e chave idempotente.
- Agenda, ranking de pódio, publicação do ranking, autenticação versionada e
  idempotência de sincronização estão disponíveis desde a primeira criação.
- As migrations `001` a `010` continuam sem alteração.
