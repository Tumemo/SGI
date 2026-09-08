# Instalação, atualização e recuperação

## Artefato da aplicação

Instale as dependências com `composer install --no-dev --optimize-autoloader` e execute `npm ci --ignore-scripts` seguido de `npm run build` durante a preparação do pacote. O servidor precisa de `vendor/`, `public/assets/`, `bootstrap/`, `config/`, `src/` e `resources/views/`. Node.js não é necessário para atender requisições.

`public/assets/` é gerado e não é versionado. Edite as fontes em `resources/`. O build inclui versões fixadas das bibliotecas e suas licenças. `public/index.php` é a única entrada HTTP; não publique a raiz do repositório.

## Banco novo

Crie um banco vazio e configure `SGI_DB_*`. Execute `php bin/sgi.php migrate`. Para a primeira conta, informe `SGI_ADMIN_LOGIN`, `SGI_ADMIN_NAME` e `SGI_ADMIN_PASSWORD` somente no ambiente do comando `php bin/sgi.php admin:create`. A senha precisa ter pelo menos 12 caracteres e é persistida com `password_hash`. A rotina não substitui administradores existentes.

Os dados de demonstração em `database/seeders/test.sql` pertencem aos testes. Não os carregue em produção.

## Atualização de uma base existente

1. Faça backup consistente do banco, dos uploads, das importações e da configuração. Teste a restauração em outra base.
2. Prepare o novo pacote e execute as suítes em um ambiente separado. Configure os mesmos diretórios persistentes de upload da instalação atual.
3. Em uma janela sem operações de mesário em andamento, execute `php bin/sgi.php migrate --baseline` na primeira adoção das migrações. A rotina confere tabelas, colunas e a chave de matrícula por edição antes de registrar o esquema inicial. Ela não certifica equivalência de todos os tipos, triggers ou índices: compare divergências da instalação com `001_initial_schema.sql` antes dessa etapa.
4. Em atualizações posteriores, execute somente `php bin/sgi.php migrate`.
5. Publique o pacote, faça login com os perfis utilizados e confira agenda, ranking e armazenamento. Os mesários devem concluir a preparação offline antes de perder a conexão.

As migrações são numeradas, têm checksum e usam trava no banco para impedir execuções simultâneas. Uma migração aplicada não deve ser editada; crie outra. DDL do MySQL/MariaDB pode fazer commit implícito: o marcador `dirty` sinaliza aplicação incompleta e interrompe novas tentativas automáticas.

## Compatibilidade offline

As URLs anteriores continuam definidas em `config/routes/compatibility.php`, `config/routes/web.php` e `config/assets.php`. Não remova esses aliases enquanto existirem clientes ou filas que os utilizem. A versão nova armazena HTML, configuração e JavaScript da página juntos. Registros antigos, contendo scripts inline, ainda são lidos pelo adaptador do shell.

O identificador de mutação acompanha os reenvios. Novas confirmações registram também a identidade do operador e o conteúdo da operação; a mesma chave com conteúdo ou operador diferente é recusada. Registros anteriores à migração `002` não possuem esse vínculo e preservam a resposta histórica para compatibilidade. Gols, ocorrências e resultados protegidos são confirmados junto com o registro de repetição na mesma transação. Operações recusadas permanecem na fila e precisam de revisão.

## Recuperação

Se a atualização falhar, interrompa novas escritas e guarde os logs. Volte ao pacote anterior somente se ele for compatível com o esquema já aplicado. A migração `002` adiciona uma coluna opcional; não a remova durante um retorno de versão.

Se houver DDL parcialmente aplicado ou incompatibilidade, restaure o backup verificado em uma base separada, valide-o e redirecione a aplicação. Não apague o marcador de falha para forçar repetição de uma migração sem analisar seus efeitos. Não existe comando automático de rollback destrutivo.

Preserve as filas IndexedDB durante a recuperação. Não limpe dados do navegador de um mesário com alterações ainda não confirmadas.

### Ensaio sintético reproduzível

Antes de uma atualização real, `php tests/run_all.php` executa a Suite 16 de recuperação em bancos descartáveis. O teste gera `test-results/t28-recovery-*.sql` e um manifesto JSON com hash do dump, referência do código, origem pré-upgrade e versão das migrações; compara matrículas, hashes, histórico, triggers e a fronteira pré-upgrade após restaurar em outra base. Esses arquivos são evidência do ensaio, não backup de produção.

O dump restaurado representa a base anterior às migrações 002–004. A aplicação atualizada não deve ser revertida apontando para esse dump sem interromper escritas e sem confirmar a compatibilidade do pacote anterior com o esquema já aplicado. O procedimento conserva as filas/cache offline e exige validação operacional antes de redirecionar tráfego. MySQL 8.4, MariaDB 10.11 e o job visual Windows continuam sendo validados pelo CI quando não estiverem disponíveis no host local.
