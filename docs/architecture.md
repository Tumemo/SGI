# Arquitetura do SGI

O SGI é um monólito modular em PHP. O deploy continua sendo uma única
aplicação, mas cada capacidade de negócio possui código organizado por módulo,
camadas e contratos explícitos. Essa organização permite evoluir cada área
sem transformar o monólito em um conjunto de dependências acidentais.

## Estrutura do projeto

```text
public/index.php               front controller e fronteira HTTP
config/bootstrap.php           inicialização única (autoload, ambiente e CSRF)
config/routes.php              composição das rotas /api/v1
api/                           adaptadores HTTP legados, sem regras de negócio
src/Modules/
  Acesso/                      autenticação, sessão e usuários administrativos
    Domain/                    contratos de acesso e usuários
    Application/               casos de uso de login, senha e permissões
    Infrastructure/            repositórios MySQLi
  Interclasses/                núcleo do domínio do evento
    Domain/                    contratos dos agregados e repositórios
    Application/               casos de uso, validações e exceções
    Infrastructure/            persistência e integração com motores legados
  Competicoes/Presentation/    controladores HTTP de modalidades
  Eventos/Presentation/        controladores HTTP de categorias e locais
  Resultados/Presentation/     controladores HTTP de ranking e resultados
src/Shared/                    HTTP, configuração, sessão e armazenamento
views/                         páginas PHP e componentes JavaScript
storage/                       arquivos gerados em execução (não versionados)
tests/Unit/                    testes de contratos, casos de uso e arquitetura
tests/Integration/             fluxos HTTP e persistência
tests/browser/                 regressão visual e operação offline
```

`Interclasses` é o bounded context central do evento. Os módulos de
apresentação expõem capacidades específicas sem duplicar regras ou SQL. O
módulo `Acesso` permanece isolado do contexto esportivo e concentra tudo que
envolve identidade, sessão e autorização.

## Fluxo HTTP

1. O servidor aponta exclusivamente para `public/`.
2. `public/index.php` carrega `config/bootstrap.php` uma única vez e rejeita
   arquivos internos, traversal e caminhos fora da lista pública.
3. URLs versionadas (`/api/v1/*`) passam por `MiddlewareStack`, tratamento
   uniforme de exceções e `Router`.
4. Cada rota compõe explicitamente seu controlador, serviço e repositório em
   `config/routes.php`; não existe contêiner global ou descoberta implícita.
5. As URLs legadas de `api/` continuam como adaptadores finos para as telas
   existentes. Elas usam os mesmos serviços e repositórios dos módulos novos,
   portanto a migração de uma tela pode ocorrer endpoint a endpoint.

Controladores não executam SQL. Serviços recebem interfaces de domínio e são
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
