# Execução dos testes

## Execução descartável no Docker

O fluxo recomendado não exige PHP, Composer, Node, MySQL/MariaDB ou Chromium
instalados no host. O script constrói as imagens, cria um banco em `tmpfs`,
inicia o servidor HTTP, executa as suítes e remove os containers ao terminar.

No Windows:

```powershell
powershell -File tools/test-docker.ps1 -Database mariadb
```

No Linux/macOS:

```bash
sh tools/test-docker.sh --database mariadb
```

Para executar contra MySQL 8.4:

```powershell
powershell -File tools/test-docker.ps1 -Database mysql
```

O banco padrão é `sgi_test`, com usuário `root` e senha `sgi-test-only`.
Esses dados são exclusivos do ambiente de teste. `-Keep`/`--keep` mantém os
containers para investigação; sem essa opção o Compose executa `down --volumes`.

Quando o serviço `app` publica a porta HTTP no host, o bind padrão é
`127.0.0.1:8099`, evitando exposição acidental na rede. A porta pode ser alterada
com `SGI_TEST_HOST_PORT`. Para uma homologação que precise ser acessível por
outros dispositivos da rede local, configure explicitamente
`SGI_TEST_BIND_ADDRESS=0.0.0.0`, restrinja o firewall à sub-rede autorizada e
confirme a URL antes de compartilhar. O servidor dentro do container continua
escutando em `0.0.0.0:8099` somente para permitir o acesso entre os serviços
Compose pelo alias `sgi-web`; esse bind interno não publica a aplicação na rede
do host por si só.

As imagens usam PHP 8.4 por padrão. Para validar PHP 8.2:

```bash
sh tools/test-docker.sh --database mariadb --php-version 8.2
```

O comando executa, em sequência, `composer verify`, validação do Composer,
build e checks JavaScript, a suíte HTTP/banco (`tests/run_all.php`) e os testes
Playwright online/offline. Os resultados permanecem em `test-results/` e
`tests/browser/{test-results,playwright-report}/`.

O contrato visual pode ser solicitado com `-IncludeVisual`/`--include-visual`.
O container usa referências Linux (`*-linux.png`) versionadas separadamente das
referências Windows (`*-win32.png`), evitando que a plataforma do executor
altere o resultado da comparação.

## Execução local em um comando

Para o desenvolvimento diário com runtimes instalados no host, use o executor
local. Ele escolhe um PHP único, cria um container SQL descartável com nome
exclusivo, inicia o servidor HTTP, prepara sessões e uploads em
`test-results/` e remove os processos/container criados ao terminar. Ele nunca
usa uma instância MySQL/MariaDB instalada no host.

Depois de instalar as dependências uma vez (`composer install`, `npm ci` e
`npm ci --prefix tests/browser`), execute:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all
```

Os perfis disponíveis são `quality`, `integration`, `browser`, `visual` e
`all`. `quality` não inicia banco nem servidor. `browser` e `visual` preparam a
integração uma vez antes do Playwright. A seleção de backend local foi removida;
os executores recusam qualquer tentativa de apontar para um SQL externo. O
executor Docker cria um container temporário com `tmpfs`. O executor local
mantém PHP/Node/Chromium no host e exige apenas os clientes `mysql`/`mysqldump`
para o ensaio de recuperação; isso não significa que exista um banco local.
No Windows, os executáveis do XAMPP são encontrados automaticamente quando
existem. Use `-Keep` apenas para investigar uma falha; o ambiente mantido deve
ser removido manualmente depois.

O executor usa um lock por checkout porque alguns cenários de recuperação
criam bases auxiliares. Ele também passa um identificador de execução para que
essas bases não colidam entre invocações diferentes.

Para diagnosticar uma instalação, rode o perfil desejado: a validação falha
antes de criar recursos quando PHP, `mysqli`, clientes SQL, Docker, Node ou
dependências do navegador estão ausentes. Informe outro PHP com `-PhpPath` ou
`SGI_PHP_PATH`. O PHP escolhido é colocado primeiro no `PATH`, portanto
Composer, servidor e runner usam a mesma versão.

Para medir o custo de um perfil em execuções repetidas, use o benchmark. Ele
grava os tempos e códigos de saída em `test-results/` e calcula a mediana das
execuções aprovadas:

```powershell
powershell -ExecutionPolicy Bypass -File tools/benchmark-tests.ps1 -Suite quality -Runs 3
```

Para perfis que acessam o banco, o benchmark usa somente o container Docker.
Compare primeira execução e repetições com cache separadamente; o arquivo JSON
preserva cada medição.

## Execução manual do runner de baixo nível

`tests/run_all.php` é um runner interno e não cria infraestrutura. Ele só pode
ser executado dentro de um serviço preparado pelos wrappers Docker. A chamada
direta sem esse ambiente falha antes de resetar o schema; não configure as
variáveis para fazê-la apontar a um banco local.

Para integração somente via Composer, use `composer test:integration`; esse
comando delega ao executor Docker e remove o ambiente ao terminar.

Não execute duas suítes que alteram o banco simultaneamente. Os cenários de integração montam uma edição compartilhada em sequência. Regressões concorrentes usam processos e conexões independentes: cobrem replay da mesma mutação, anulação contra conclusão do jogo e disputa de local/horário entre criação manual, edição e confirmação de bloco. As barreiras de teste sincronizam os participantes da corrida sem atrasos arbitrários.

## Navegador em execução manual

O fluxo recomendado é `tools/test-docker.ps1`/`.sh`, que já prepara o container
SQL, a aplicação e o navegador. Não execute Playwright diretamente contra uma
URL herdada ou um servidor conectado ao banco local.

No fluxo Docker, a imagem oficial do
Playwright já contém o Chromium e o serviço `browser` recebe
`SGI_BASE_URL=http://sgi-web:8099/`; não configure `SGI_E2E_RESET`, porque a etapa de
integração já prepara a mesma base descartável. Na execução manual,
`SGI_E2E_RESET=1` não substitui a preparação do container e o setup recusa
ambiente sem `SGI_TEST_DB_RUNTIME=container`. `SGI_CHROME_PATH` permite
outro executável, mas comparações visuais devem usar a mesma versão das
referências.

Os testes cobrem administração, portal do aluno, permissões, todas as telas principais e torneios com sete partidas. Os cenários offline desabilitam a rede do navegador, verificam IndexedDB e conferem no servidor o resultado após a reconexão.

`offline-queue-regression.spec.cjs` usa o IndexedDB real do Chromium e respostas HTTP controladas, sem alterar o banco SQL. Cobre a ordem entre alterações novas e pendentes, respostas sem confirmação de sucesso (vazias, HTML, JSON truncado ou `status: erro`), compatibilidade com `status: sucesso`, dependências de ocorrências nas rotas v1, jogos temporários intercalados, aborto de transação local, aplicação de resultados v1 no chaveamento, sondagem do servidor local, coordenação entre abas e exportação/importação idempotente sem credencial CSRF. Para rodar somente essa regressão puramente local, sem a aplicação/SQL, defina explicitamente `SGI_BROWSER_REQUIRES_DATABASE=0`; a suíte de navegador completa exige o container. Os testes JavaScript também verificam a captura dos cadastros e a projeção de placares pelas rotas v1.

O cenário de chaveamento ímpar prepara três equipes com elenco e exige um avanço automático inicial. Se a preparação falhar, a suíte falha. A retificação de placar usa os identificadores criados pelo próprio teste e consulta os valores persistidos depois da alteração.

`deployment-paths.spec.cjs` verifica redirecionamento, login, carregamento de arquivos e paridade das APIs no endereço configurado. Para testar uma instalação em subdiretório, inicie um servidor separado com `SGI_BASE_PATH=SGI` e execute esse teste com `SGI_BASE_URL=http://127.0.0.1:PORTA/SGI/`. O teste também roda normalmente na raiz. Os testes unitários usam sessões próprias em `test-results/unit-sessions/`, sem depender da pasta de sessões do servidor.

`legacy-offline-compat.spec.cjs` cobre fila antiga sem `session`, alias antigo de mutação, isolamento entre operadores, casca sem `pageSources` e retry após falha de rede. A compatibilidade exige uma casca autenticada/preparada; refresh, nova aba e cold-open sem essa casca não são declarados como suporte porque o pacote não usa Service Worker.

`visual-contract.spec.cjs` compara quatro imagens do login em desktop/mobile. A resposta de credenciais inválidas é fixa nesse teste visual; a autenticação real é validada separadamente. O fluxo Docker compara as referências Linux; a execução host+Docker do `test-local.ps1` usa as referências Windows.

Imagens, traces e relatório ficam em `tests/browser/test-results/` e `tests/browser/playwright-report/`. Só atualize snapshots após inspecionar uma mudança visual intencional. Testes aprovados cobrem os cenários descritos; não representam garantia de ausência de qualquer defeito.

## Matriz e recuperação

O CI executa qualidade apenas em PHP 8.4. Integração HTTP/banco,
navegador e contrato visual usam PHP 8.4 e MariaDB 10.11; o visual usa as
referências Linux. A configuração está em `.github/workflows/ci.yml`. A matriz
do CI usa uma única versão de PHP e MariaDB; MySQL e PHP 8.2 continuam disponíveis
para execuções locais pelo executor Docker.

`composer test:integration` executa `MigrationsTest`, que confirma a instalação
repetida e os elementos estruturais esperados no baseline atual, além de
`MigrationSupportTest` e `RecoveryRehearsalTest` para verificar migrations
futuras e a reinstalação do baseline. As bases de teste são descartáveis e os
ensaios não apontam para uma base de trabalho nem limpam filas IndexedDB. O
container, projeto Compose e volumes são removidos ao final da execução normal.
