# Schema inicial atual do SGI

## Objetivo

`database/schema-inicial.sql` é a fonte única do estado estrutural esperado
para instalações novas. Ele contém a consolidação das migrations 001–010 e
também as alterações mais recentes das migrations 011 e 012:

- vínculo estruturado de vermelhos automáticos em
  `ocorrencias_vermelhos_automaticos`;
- coluna `usuarios.senha_troca_pendente`, com default `0` para novas contas.

As migrations históricas foram removidas do diretório porque não existem
bases legadas neste pacote. O suporte a migrations foi mantido para alterações
que forem necessárias depois da entrada em produção. O comando
`php bin/sgi.php schema:install` instala o baseline; `php bin/sgi.php migrate`
instala o baseline quando necessário e aplica migrations futuras.

## Estado incluído

O baseline cria 23 tabelas de aplicação:

- edições, categorias, turmas e locais;
- modalidades, equipes, usuários, vínculos e termos;
- jogos, partidas, artilheiros, pontuações e créditos de pódio;
- arrecadação e ocorrências, incluindo vínculos de vermelhos automáticos;
- agenda de blocos, reservas e histórico;
- idempotência de sincronização.

Também cria os índices, chaves estrangeiras, restrições `CHECK` e o trigger
atual de arrecadação exigidos pelo código atual. O trigger legado de
sincronização de status dos alunos não faz parte do baseline.

O `UPDATE` histórico que marcava alunos existentes como pendentes não é
necessário em uma base vazia. Alunos criados por cadastro, importação ou reset
recebem o estado pendente pela própria operação de aplicação.

## Instalação

1. Crie um banco vazio com `utf8mb4`.
2. Configure `SGI_DB_*`.
3. Execute `php bin/sgi.php schema:install` ou `php bin/sgi.php migrate`.
4. Execute `php bin/sgi.php admin:create` para criar o primeiro administrador.

O `SchemaInstaller` aceita uma base vazia e executa todos os statements,
incluindo o trigger. Se a base já contiver exatamente as 23 tabelas do
baseline, uma nova execução é um no-op; depois que o marcador foi registrado,
tabelas acrescentadas por migrations futuras também são aceitas. Bases parciais
ou sem o marcador e com tabelas inesperadas são recusadas para evitar mistura
de esquemas.

Após a instalação, o instalador registra o baseline em `sgi_migrations`. Isso
permite que o `MigrationRunner` reconheça a base atual e aplique, com checksum,
trava e marcador de conclusão, qualquer migration futura colocada em
`database/migrations/`.

## Validação

`tests/Integration/MigrationsTest.php` verifica a repetição da instalação,
a tabela de vínculos automáticos, a coluna de primeiro acesso, índices,
foreign keys, checks, trigger e fingerprint de sincronização. A preparação da
suíte usa apenas uma base de teste descartável e os fixtures de
`database/seeders/test.sql`.

`MigrationSupportTest` confirma que uma migration futura pode ser aplicada e
reaplicada sem duplicidade sobre uma base criada pelo baseline.
