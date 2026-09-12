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
config/routes/web.php          URLs canônicas para templates privados
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
5. As rotas públicas de produção usam somente os namespaces canônicos de páginas e `/api/v1`. Não há aliases procedurais nem executores PHP fora do front controller.

Os controladores novos não executam SQL. Serviços recebem interfaces de domínio e são
testáveis sem banco. Repositórios concentram consultas, transações e detalhes
do MySQL/MariaDB.

## Dependências entre módulos

- `Competicoes` pode expor contratos de equipes e a API de pontuação para os
  demais módulos; sua implementação MySQLi permanece em `Infrastructure`.
- `Participantes` usa contratos de `Competicoes` para localizar/criar equipes
  padrão e mantém a decisão de limite de modalidades em regra pura, sem chamar
  a infraestrutura de equipes diretamente.
- `Eventos` coordena a criação da edição e de seus padrões por contratos de
  `Competicoes`; a geração de nomes e o SQL continuam nos adaptadores de
  infraestrutura.
- `Presentation` não importa implementações de outro módulo para executar
  regras ou consultas; a composição concreta fica nas rotas.

As dependências concretas de apresentação abaixo permanecem fora do escopo
crítico migrado e são intencionais, não uma exceção curinga: `ArtilheiroController`
e `OcorrenciaController` usam queries somente de leitura do próprio módulo;
`EquipeController`, `JogoController` e `PartidaController` mantêm gateways de
compatibilidade dos fluxos operacionais; `EdicaoController` e
`ImportacaoTurmaController` recebem storages de arquivo do próprio módulo;
`HistoricoTurmaController` usa o repositório de consulta do histórico; e
`ChaveamentoSyncController` usa o gateway de sincronização de sua própria
fronteira. `ResultadoController` e `UsuarioController`, que foram migrados,
ficam fora dessa lista e só recebem serviços, contratos e adaptadores de HTTP.

## Fronteiras de segurança e dados

- `AccessGuard` aplica sessão e RBAC antes das mutações versionadas.
- `ExceptionMiddleware` converte falhas inesperadas em JSON seguro e registra o
  detalhe no log, sem vazar SQL ou caminhos locais.
- `StoragePaths` resolve uploads fora do código (`storage/uploads/*` por
  padrão) e aceita diretórios configuráveis por `SGI_*_DIR`.
- O modo offline do mesário usa IDs temporários negativos e a fila IndexedDB. A casca atual precisa ser preparada antes da perda de conexão e não depende de Service Worker.

## Regras para mudanças

1. Uma alteração de caso de uso deve incluir testes unitários do serviço e um
   teste de contrato HTTP quando a resposta pública mudar.
2. SQL e transações ficam em `Infrastructure`; páginas e JavaScript não acessam
   o banco diretamente.
3. Entradas são normalizadas e validadas antes do repositório. Mensagens
   internas de banco ficam no log.
4. Rotas novas entram exclusivamente em `/api/v1` e não executam arquivos procedurais.
5. Não adicionar triggers que atualizem a própria tabela disparadora (erro
   1442 em MySQL/MariaDB). Matrículas permanecem únicas por edição e senhas
   usam `password_hash`/`password_verify`.

## Limites e status de modalidade

`max_inscrito_modalidade` e `max_equipes` usam inteiros `INT` assinados no
banco. Os dois limites aceitam inteiro ou texto de algarismos inteiros entre
1 e 2.147.483.647. Para manter a semântica existente de ilimitado, também
aceitam `NULL`, texto vazio e zero (numérico ou texto); a persistência pode
representar ilimitado como `0` ou `NULL` para compatibilidade com registros
anteriores. Se ambos os limites forem positivos, a capacidade por turma é o
produto de inscritos por equipe e quantidade de equipes.

Valores negativos, fracionários, booleanos, arrays, texto que não contenha
somente algarismos e valores acima do `INT` assinado são inválidos. O status da
modalidade aceita somente `1`/`"1"` (ativa) e `0`/`"0"` (inativa), conforme o
ENUM existente no banco. Atualizações parciais aplicam as mesmas regras aos
campos informados.

## Assets e ciclo de vida offline

O build copia fontes e dependências fixadas no lockfile para `public/assets`, inclui licenças e gera um manifesto de checksums. URLs emitidas por `Assets` têm versão derivada do conteúdo.

Cada programa de página roda em uma função própria. `page-runtime.js` registra inicialização e reativação, restaura as ações usadas pelo HTML e evita duplicar eventos. Ao sair de uma tela, os eventos globais são removidos; placar e chaveamento interrompem suas atualizações. O shell guarda HTML, JSON e fontes JavaScript juntos no registro de versão 2.

O modal de ocorrência cancela fechamentos atrasados quando uma nova edição começa, impedindo que uma confirmação anterior desmonte a edição seguinte durante uma reconexão offline. A fila continua sendo a mesma store IndexedDB, com os mesmos aliases e identificadores de mutação.

## Migrações e sincronização

O gerenciamento de chaveamento usa `ChaveamentoController` e `ChaveamentoService`, com persistência por `ChaveamentoManagement` no endpoint versionado `/api/v1/chaveamentos`. A criação coletiva continua restrita a administrador/colaborador; o mesário registra resultados individuais somente na edição ativa. A criação de jogos e os avanços automáticos são confirmados na mesma transação.

O agendamento manual, a edição de horário/local e a confirmação de blocos compartilham a trava da linha do local, adquirida em ordem crescente quando há mais de um local. Operações no mesmo local serializam durante a validação e gravação; depois da trava, o repositório reconsulta jogos e reservas conflitantes com leitura atual dentro da transação. Conflitos continuam usando o intervalo de troca de dez minutos, enquanto outros locais, datas e faixas não conflitantes permanecem válidos.

A importação por PDF usa `ImportacaoTurmaController`, `ImportacaoTurmaService`, um contrato de leitura e um repositório de persistência em `/api/v1/importacoes/turma-pdf`. A turma e a edição são validadas antes de salvar arquivos; o conteúdo precisa ter cabeçalho PDF. Um bloqueio de arquivo serializa importações da mesma turma para preservar o par PDF/CSV durante a extração. A deduplicação de matrículas por edição permanece no importador existente.

`MigrationRunner` registra checksum, estado de conclusão e trava de execução. Instalações novas começam com banco vazio e aplicam todas as migrações; uma base sem histórico é recusada para evitar mistura de esquemas.

`MysqliMutationStore` serializa uma mesma chave, rejeita sua reutilização com outro conteúdo/operador e confirma resposta e dados na mesma transação. Transações aninhadas usam savepoints. Os testes provocam falha antes da confirmação e reenvios concorrentes. A fila mantém o identificador e atualiza o token CSRF da sessão ao reenviar.

O ensaio de recuperação de `tests/Integration/RecoveryRehearsalTest.php` usa somente bases sintéticas: preserva schema, dados, hashes e triggers através do upgrade e restauração, registra hash/versão no manifesto e deixa explícita a fronteira pré-upgrade. Ele não altera banco de trabalho nem remove filas do navegador.

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

A matriz declarada no CI executa qualidade em PHP 8.2 e 8.4; integração e navegador em PHP 8.4 com MySQL 8.4 e MariaDB 10.11; e comparação visual usando referências Linux. Uma execução local não cobre automaticamente os demais alvos. Quando não estiverem disponíveis, devem permanecer como pendência explícita no registro, não como aprovação implícita.

O procedimento reproduzível está em [testes](testing.md). Não há exceção de CSRF baseada em `SGI_APP_ENV=test`.
