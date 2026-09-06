# Arquitetura do SGI

O SGI é um monólito modular em PHP. O deploy continua sendo uma única
aplicação, mas cada capacidade de negócio possui código organizado por módulo,
camadas e contratos explícitos. Essa organização permite evoluir cada área
sem transformar o monólito em um conjunto de dependências acidentais.

## Estrutura do projeto

```text
public/index.php               entrada HTTP
bootstrap/autoload.php         Composer, raiz do projeto e ambiente
bootstrap/app.php              composição do Kernel
config/routes.php              controladores e dependências das APIs versionadas
config/routes/web.php          URLs anteriores para templates privados
config/routes/compatibility.php aliases de API e endpoints ainda em migração
config/assets.php              compatibilidade com URLs antigas de assets
src/Modules/
  Acesso/                      autenticação, perfil, fotos e usuários
  Eventos/                     edições, categorias e locais
  Participantes/               turmas, matrículas e importação de alunos
  Competicoes/                 modalidades, equipes, jogos e chaveamento
  Resultados/                  ranking, classificação e arrecadação
  Disciplina/                  ocorrências individuais e de turma
  Sincronizacao/               identidade, repetição e confirmação de mutações
src/Shared/                    HTTP, configuração, banco e armazenamento
resources/views/               templates privados e componentes
resources/js/pages/            programas das telas, com estado isolado
resources/js/shared/           cliente HTTP e ciclo de vida das páginas
resources/js/offline/          IndexedDB, shell, fila e chaveamento local
resources/css/                 estilos da aplicação
resources/images/              imagens e ícones
public/assets/                 saída reproduzível de npm run build
api/                           endpoints procedurais ainda em migração
storage/                       arquivos de execução, fora do Git
bin/sgi.php                    migrações e configuração inicial por CLI
database/migrations/           esquema versionado e histórico de execução
tests/Unit/                    regras, contratos unitários e arquitetura
tests/Integration/             HTTP, persistência, migrações e concorrência
tests/javascript/              ciclo de vida de telas e modais
tests/browser/                 navegação, comparação visual e operação offline
```

Cada módulo organiza contratos em `Domain`, casos de uso em `Application`, persistência em `Infrastructure` e controladores em `Presentation/Http`. Nem todo módulo precisa de todas as camadas. O antigo módulo genérico `Interclasses` foi distribuído conforme a responsabilidade de cada classe.

Os serviços de domínio/aplicação não dependem de HTTP, sessão ou MySQLi; testes verificam essa fronteira. Os templates não contêm SQL nem programas JavaScript inline. Configurações de página são serializadas como JSON com escape de caracteres HTML.

## Fluxo HTTP

1. O servidor aponta exclusivamente para `public/`.
2. `public/index.php` carrega `bootstrap/app.php`; o Kernel rejeita
   arquivos internos, traversal e caminhos fora da lista pública.
3. URLs versionadas (`/api/v1/*`) passam por `MiddlewareStack`, tratamento
   uniforme de exceções e `Router`.
4. Cada rota compõe explicitamente seu controlador, serviço e repositório em
   `config/routes.php`; não existe contêiner global ou descoberta implícita.
5. Os aliases encaminham URLs anteriores aos mesmos controladores versionados. Os endpoints restantes em `api/` são executados somente pela lista explícita de compatibilidade e pelo `LegacyEndpoint`; ainda contêm trechos de persistência que precisam de extração. Eles não devem ser usados como modelo para código novo.

Os controladores novos não executam SQL. Serviços recebem interfaces de domínio e são
testáveis sem banco. Repositórios concentram consultas, transações e detalhes
do MySQL/MariaDB.

## Fronteiras de segurança e dados

- `AccessGuard` aplica sessão e RBAC antes das mutações versionadas.
- `ExceptionMiddleware` converte falhas inesperadas em JSON seguro e registra o
  detalhe no log, sem vazar SQL ou caminhos locais.
- `StoragePaths` resolve uploads fora do código (`storage/uploads/*` por
  padrão) e aceita diretórios configuráveis por `SGI_*_DIR`.
- O modo offline do mesário continua usando IDs temporários negativos e a fila
  IndexedDB; os adaptadores legados permanecem na fronteira até que o contrato
  de sincronização seja totalmente coberto por controladores versionados.

## Regras para mudanças

1. Uma alteração de caso de uso deve incluir testes unitários do serviço e um
   teste de contrato HTTP quando a resposta pública mudar.
2. SQL e transações ficam em `Infrastructure`; páginas e JavaScript não acessam
   o banco diretamente.
3. Entradas são normalizadas e validadas antes do repositório. Mensagens
   internas de banco ficam no log.
4. Rotas novas entram primeiro em `/api/v1`; o adaptador legado só é removido
   depois que a regressão PHP e a suíte de navegador confirmarem paridade.
5. Não adicionar triggers que atualizem a própria tabela disparadora (erro
   1442 em MySQL/MariaDB). Matrículas permanecem únicas por edição e senhas
   usam `password_hash`/`password_verify`.

## Assets e ciclo de vida offline

O build copia fontes e dependências fixadas no lockfile para `public/assets`, inclui licenças e gera um manifesto de checksums. URLs emitidas por `Assets` têm versão derivada do conteúdo. Os aliases de arquivos antigos continuam disponíveis.

Cada programa de página roda em uma função própria. `page-runtime.js` registra inicialização e reativação, restaura as ações usadas pelo HTML e evita duplicar eventos. Ao sair de uma tela, os eventos globais são removidos; placar e chaveamento interrompem suas atualizações. O shell guarda HTML, JSON e fontes JavaScript juntos no registro de versão 2. O adaptador léxico é mantido apenas para os scripts legados e os caches de versões anteriores.

## Migrações e sincronização

O gerenciamento de chaveamento usa `ChaveamentoController` e `ChaveamentoService`, com persistência por `ChaveamentoManagement`. A URL antiga encaminha para `/api/v1/chaveamentos`. A criação coletiva continua restrita a administrador/colaborador; o mesário registra resultados individuais somente na edição ativa. A criação de jogos e os avanços automáticos são confirmados na mesma transação.

A importação por PDF usa `ImportacaoTurmaController`, `ImportacaoTurmaService`, um contrato de leitura e um repositório de persistência. As URLs antigas de upload encaminham para `/api/v1/importacoes/turma-pdf`, incluindo o campo legado `pdf`. A turma e a edição são validadas antes de salvar arquivos; o conteúdo precisa ter cabeçalho PDF. Um bloqueio de arquivo serializa importações da mesma turma para preservar o par PDF/CSV durante a extração. A deduplicação de matrículas por edição permanece no importador existente.

`MigrationRunner` registra checksum, estado de conclusão e trava de execução. A adoção de uma base existente exige `--baseline`; a rotina valida parte da estrutura e não apaga seus dados. Veja [implantação](deployment.md).

`MysqliMutationStore` serializa uma mesma chave, rejeita sua reutilização com outro conteúdo/operador e confirma resposta e dados na mesma transação. Transações aninhadas usam savepoints. Os testes provocam falha antes da confirmação e reenvios concorrentes. A fila mantém o identificador e atualiza o token CSRF da sessão ao reenviar.

## Rede de segurança

```text
composer test       PHPUnit unitário (inclui teste de layout modular)
composer lint       validação de sintaxe PHP
composer analyse    análise estática PHPStan
composer cs:check   estilo PHP CS Fixer
php tests/run_all.php
npm --prefix tests/browser test
```

As duas últimas suítes precisam de um servidor de teste e banco `sgi_test`.
Elas cobrem autenticação, ciclo de edição, importação de PDF, modalidades,
agendamento, placar, ranking, portal do aluno, fronteira pública, operação
offline e chaveamento completo.

O procedimento reproduzível está em [testes](testing.md). Não há exceção de CSRF baseada em `SGI_APP_ENV=test`.
