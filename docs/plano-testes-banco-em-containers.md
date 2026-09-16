# Plano: testes de banco exclusivamente em containers descartáveis

Data: 16/09/2026

## Objetivo

Garantir que toda suíte que realmente acessa MySQL/MariaDB execute contra um
servidor SQL criado pelo próprio executor, em um container descartável, sem
fallback para banco instalado no host, credenciais do `.env` de trabalho ou
datadir local.

O isolamento será por execução da suíte: a integração HTTP, a recuperação e os
cenários de navegador que dependem dela reutilizam o mesmo container durante a
execução para preservar a ordem e as fixtures compartilhadas. Não será criado
um container por método de teste, pois isso quebraria os cenários encadeados e
as corridas concorrentes que precisam de conexões independentes no mesmo
servidor.

## Diagnóstico atual

O repositório já tem o caminho Docker em `compose.test.yml` e nos wrappers
`tools/test-docker.ps1`/`tools/test-docker.sh`. O serviço `db` usa `tmpfs` e a
suíte completa já consegue executar aplicação, integração e navegador em
containers.

Ainda existem caminhos incompatíveis com a regra desejada:

- `tools/test-local.ps1` aceita `-DatabaseBackend local` e pode apontar para
  `SGI_TEST_DB_HOST`, `SGI_TEST_DB_PORT` e credenciais do host.
- `tools/benchmark-tests.ps1`, `README.md`, `AGENTS.md` e `docs/testing.md`
  recomendam ou documentam execução contra SQL local.
- `tools/start-test-server.ps1`, `tests/run_all.php`, o setup do Playwright e
  `tests/seed_interclasse_demo.php` podem ser usados fora de um executor que
  tenha criado o container.
- `tests/Integration/RecoveryRehearsalTest.php` cria bases auxiliares no mesmo
  servidor e precisa continuar usando clientes de dump/restauração disponíveis
  no ambiente de teste.
- `compose.test.yml` usa nome de projeto, banco e artefatos padrão; execuções
  simultâneas podem colidir mesmo com o banco em container.

## Decisões de implementação

1. **Docker será o único backend de banco para testes.** O backend `local` será
   removido dos executores, dos parâmetros e da documentação ativa. Não haverá
   variável `SGI_TEST_DB_HOST`/`SGI_TEST_DB_PORT` para escolher um servidor
   externo.
2. **A qualidade sem acesso ao banco continua leve.** `quality`, testes
   unitários, lint, análise estática e testes JavaScript puros não precisam
   iniciar SQL. Qualquer teste novo que abra `mysqli` será classificado como
   dependente de banco e só poderá ser executado pelos executores Docker.
3. **A suíte Docker completa será a referência oficial.** Ela deve criar um
   projeto Compose exclusivo, um nome de banco exclusivo e diretórios próprios
   de logs/sessões/uploads por execução. `tmpfs` continuará sendo usado para o
   datadir do SQL.
4. **Execuções host+Docker, se mantidas, terão apenas runtimes de aplicação no
   host.** Elas nunca iniciarão nem reutilizarão MySQL/MariaDB local. A opção
   preferida é delegar perfis dependentes de banco ao executor Docker completo,
   evitando também a dependência de clientes `mysql`/`mysqldump` no host.
5. **O runner de baixo nível terá uma barreira de segurança.** Antes de resetar
   ou conectar, `TestDatabase`/`tests/run_all.php` exigirão uma marca de
   ambiente emitida pelo executor (`SGI_TEST_DB_RUNTIME=container`) e
   confirmarão pelo health endpoint o nome do banco, o modo `test` e o runtime
   esperado. Execução manual sem preparação falhará antes de qualquer `DROP`.
6. **Recuperação permanecerá dentro do container.** O serviço de integração
   usará os clientes SQL presentes na imagem de teste; as bases de origem e
   restauração serão nomes exclusivos e ficarão sujeitas ao mesmo ciclo de
   remoção do container.
7. **`-Keep`/`--keep` será somente diagnóstico explícito.** A execução normal
   sempre fará `down --volumes --remove-orphans` ou `docker rm -f`, inclusive
   após falhas. Um ambiente mantido não será apresentado como descartado até
   que seja removido manualmente.

## Etapas

### 1. Formalizar o contrato no `AGENTS.md`

- Substituir os exemplos de `-DatabaseBackend local` pelo executor Docker.
- Declarar que integração, navegador, visual, recuperação, seeds de cenário e
  qualquer teste com conexão SQL exigem container novo por execução.
- Proibir explicitamente `php tests/run_all.php`, Playwright com reset SQL,
  seeds e `start-test-server.ps1` contra banco local.
- Documentar a exceção dos testes sem banco e o significado de `-Keep`.
- Exigir evidência de imagem, nome do projeto/container, banco, logs e limpeza
  ao reportar uma execução.

### 2. Endurecer os executores

Arquivos principais: `tools/test-local.ps1`, `tools/test-docker.ps1`,
`tools/test-docker.sh`, `tools/benchmark-tests.ps1` e
`tools/test-local-cleanup*.ps1`.

- Remover o ramo `local`, os parâmetros de host/porta/usuário/senha SQL e o
  `DROP DATABASE` executado contra servidor externo.
- Fazer os perfis dependentes de banco criarem o container Docker antes de
  iniciar servidor ou Playwright, aguardarem healthcheck e propagarem falha.
- Gerar `runId`, `COMPOSE_PROJECT_NAME`, banco, porta HTTP e diretório de
  artefatos exclusivos; não usar nome fixo `sgi-test` em execuções concorrentes.
- Passar `SGI_TEST_DB_RUNTIME=container` e `SGI_TEST_RUN_ID` a todos os
  processos, inclusive workers de concorrência.
- Garantir limpeza idempotente no caminho normal, em falha de build, falha de
  health, interrupção e falha de teste; preservar a falha original quando a
  limpeza também falhar.
- Fazer o benchmark medir apenas o fluxo Docker para suítes com banco.
- Atualizar o teste do cleanup para verificar remoção do container/projeto e
  continuidade das demais ações, em vez de simular `DROP DATABASE` local.

### 3. Proteger o runner e o contrato HTTP

Arquivos principais: `tests/Support/TestDatabase.php`, `tests/run_all.php`,
`src/Shared/Http/HealthController.php`, `tests/browser/global-setup.cjs`,
`tests/seed_interclasse_demo.php` e os testes correspondentes.

- Criar uma verificação única de ambiente de teste descartável e usá-la antes
  de `resetFromSchema`, `connect`, criação de bases auxiliares e seeds.
- Fazer o health endpoint, somente em `SGI_APP_ENV=test`, informar também o
  runtime do banco; o runner deve comparar esse valor com o esperado.
- Fazer a execução direta sem marca de container terminar com erro claro e sem
  alterar banco.
- Fazer o setup do navegador exigir ambiente preparado pelo executor para os
  specs que usam a aplicação/SQL; regressões puramente de IndexedDB devem
  continuar podendo usar mocks sem banco quando forem executadas isoladamente.
- Adicionar testes unitários para nomes seguros, runtime ausente/incorreto e
  falha antes de qualquer operação destrutiva; adicionar teste HTTP para o
  contrato de health em modo de teste.

### 4. Revisar comandos e documentação ativa

Arquivos: `README.md`, `docs/testing.md`, `.env.example`,
`docs/plano-testes-locais.md`, `composer.json` e, se necessário,
`tools/start-test-server.ps1`.

- Tornar o Docker o único caminho documentado para integração, navegador e
  visual, com comandos equivalentes para Windows e Linux/macOS.
- Retirar o fluxo de dois terminais que configura banco local. O servidor
  manual deve ser classificado como servidor sem suíte SQL ou passar a iniciar
  o ambiente descartável completo; não pode ser uma porta de entrada ambígua.
- Remover do `.env.example` as variáveis que sugerem teste contra SQL local.
- Alterar `composer test:integration` para não executar diretamente o runner
  contra o ambiente herdado; ele deverá delegar a um wrapper Docker ou ser
  documentado como comando interno que exige o ambiente emitido pelo executor.
- Marcar `docs/plano-testes-locais.md` como substituído por este plano ou
  atualizar seu conteúdo para não recomendar SQL local.
- Atualizar troubleshooting, matriz, exemplos de logs e pré-requisitos para
  indicar Docker Engine/Compose como requisito das suítes com banco.

### 5. Validar e prevenir regressões

Adicionar ou atualizar verificações que falhem se o backend local voltar:

- teste de configuração que confirme o runtime `container`, o `tmpfs`, o
  healthcheck e a ausência de fallback local nos wrappers;
- teste do runner que rejeite execução sem a marca de container;
- teste de cleanup em sucesso, falha de suíte e falha de remoção;
- teste de duas execuções sequenciais com bancos/projetos diferentes;
- execução concorrente controlada para confirmar que nomes, portas, artefatos e
  bases de recuperação não colidem;
- busca automatizada na CI para impedir `DatabaseBackend local` e comandos de
  integração manual nos arquivos ativos.

A validação final deve ser sequencial e registrada:

```text
composer verify
npm run build
npm run check
npm test
test-docker MariaDB 10.11 / PHP 8.4 / suíte completa
test-docker MySQL 8.4 / PHP 8.4 / suíte completa
test-docker MariaDB 10.11 / PHP 8.2 / integração
test-docker com contrato visual quando houver alteração visual
git diff --check
```

Se o Docker Engine não estiver disponível, registrar o bloqueio e não declarar
aprovação da integração, recuperação ou navegador. Uma execução apenas com
PHP/Composer do host não comprova o objetivo deste plano.

## Critérios de aceite

- Nenhum comando suportado de teste com banco aceita ou descobre um MySQL/MariaDB
  local.
- Cada execução dependente de banco cria um container e um projeto/nome
  identificáveis, com datadir temporário.
- O reset do schema ocorre somente depois da confirmação de ambiente `test` e
  containerizado; a execução sem preparação não faz `DROP` nem `CREATE`.
- Recuperação, migrações, concorrência, HTTP, navegador e offline usam o mesmo
  ambiente descartável da execução e são removidos ao final.
- Duas execuções sequenciais e duas execuções simultâneas não compartilham
  banco, projeto, porta ou artefatos.
- Unitários e qualidade sem banco continuam executáveis sem Docker.
- A documentação, o `AGENTS.md`, os scripts e o CI descrevem o mesmo contrato.
- A suíte completa exigida pelo projeto passa nos motores aplicáveis; qualquer
  alvo não executado permanece explicitamente registrado como limitação.

## Resultado esperado

Depois da implementação, o comando de referência será o executor Docker completo
(`tools/test-docker.ps1` no Windows ou `tools/test-docker.sh` em Linux/macOS).
O executor local poderá continuar existindo apenas como atalho compatível para
qualidade ou para runtimes de aplicação, mas nunca como caminho para conectar
em banco instalado no host.
