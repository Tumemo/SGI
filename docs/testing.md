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

Para o desenvolvimento diário, use o executor local. Ele escolhe um PHP único,
cria um nome exclusivo para a base, inicia o servidor HTTP, prepara sessões e
uploads em `test-results/` e remove os processos criados ao terminar. A base
principal é recriada pelo próprio runner antes dos cenários; não use uma base de
trabalho.

Depois de instalar as dependências uma vez (`composer install`, `npm ci` e
`npm ci --prefix tests/browser`), execute:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local
```

Os perfis disponíveis são `quality`, `integration`, `browser`, `visual` e
`all`. `quality` não inicia banco nem servidor. `browser` e `visual` preparam a
integração uma vez antes do Playwright. Para usar apenas o banco em Docker e
manter PHP/Node/Chromium no host:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend docker
```

O backend local usa `SGI_TEST_DB_HOST`, `SGI_TEST_DB_PORT`, `SGI_TEST_DB_USER` e
`SGI_TEST_DB_PASSWORD`; os parâmetros equivalentes do script têm precedência.
Essas variáveis deixam explícito que a credencial pertence ao ambiente de
teste, sem importar a configuração da base de trabalho. O
backend Docker cria um container temporário com `tmpfs` e uma porta local livre.
Ambos exigem os clientes `mysql`/`mysqldump` para o ensaio de recuperação; no
Windows, os executáveis do XAMPP são encontrados automaticamente quando
existem. Use `-Keep` apenas para investigar uma falha; o banco Docker e o
servidor local serão mantidos.

O executor usa um lock por checkout porque alguns cenários de recuperação
criam bases auxiliares. Ele também passa um identificador de execução para que
essas bases não colidam entre invocações diferentes.

Para diagnosticar uma instalação, rode o perfil desejado: a validação falha
antes de criar recursos quando PHP, `mysqli`, clientes SQL, Node ou dependências
do navegador estão ausentes. Informe outro PHP com `-PhpPath` ou
`SGI_PHP_PATH`. O PHP escolhido é colocado primeiro no `PATH`, portanto
Composer, servidor e runner usam a mesma versão.

Para medir o custo de um perfil em execuções repetidas, use o benchmark. Ele
grava os tempos e códigos de saída em `test-results/` e calcula a mediana das
execuções aprovadas:

```powershell
powershell -ExecutionPolicy Bypass -File tools/benchmark-tests.ps1 -Suite quality -Runs 3
```

Repita com `-DatabaseBackend docker` ou outro perfil somente em uma máquina que
já tenha as dependências correspondentes. Compare primeira execução e repetições
com cache separadamente; o arquivo JSON preserva cada medição.

## Execução manual fora do Docker

Para uma execução legada com ferramentas instaladas no host, use o servidor
isolado e configure **nos dois terminais** as mesmas variáveis:

```powershell
$env:SGI_DB_HOST = '127.0.0.1'
$env:SGI_DB_PORT = '3308'
$env:SGI_DB_USER = 'root'
$env:SGI_DB_PASSWORD = 'sgi-test-only'
$env:SGI_TEST_DB_NAME = 'sgi_test'
$env:SGI_TEST_BASE_URL = 'http://127.0.0.1:8099'
```

No primeiro terminal:

```powershell
powershell -File tools/start-test-server.ps1 -Database sgi_test -Port 8099
```

O parâmetro `-PhpPath` permite selecionar outro PHP. MySQLi precisa estar habilitado tanto no servidor quanto no PHP de linha de comando (`php --ri mysqli`). No segundo terminal:

```powershell
php tests/run_all.php
```

O runner reconstrói **apenas** a base de testes, executa migrações e carrega os fixtures. Antes do reset, confere o nome da base com a resposta de saúde do servidor em modo `test`. Os cenários HTTP usam os mesmos tokens CSRF do navegador; não há exceção de segurança para testes.

Não execute duas suítes que alteram o banco simultaneamente. Os cenários de integração montam uma edição compartilhada em sequência. Regressões concorrentes usam processos e conexões independentes: cobrem replay da mesma mutação, anulação contra conclusão do jogo e disputa de local/horário entre criação manual, edição e confirmação de bloco. As barreiras de teste sincronizam os participantes da corrida sem atrasos arbitrários.

## Navegador em execução manual

```powershell
npm ci --prefix tests/browser
npx --prefix tests/browser playwright install chromium
$env:SGI_BASE_URL = 'http://127.0.0.1:8099/'
npm --prefix tests/browser test
```

Use a base preparada pelo runner HTTP. No fluxo Docker, a imagem oficial do
Playwright já contém o Chromium e o serviço `browser` recebe
`SGI_BASE_URL=http://sgi-web:8099/`; não configure `SGI_E2E_RESET`, porque a etapa de
integração já prepara a mesma base descartável. Na execução manual,
`SGI_E2E_RESET=1` pode executar esse preparo no início; nesse caso configure
também `SGI_PHP_PATH` e as variáveis do teste HTTP. `SGI_CHROME_PATH` permite
outro executável, mas comparações visuais devem usar a mesma versão das
referências.

Os testes cobrem administração, portal do aluno, permissões, todas as telas principais e torneios com sete partidas. Os cenários offline desabilitam a rede do navegador, verificam IndexedDB e conferem no servidor o resultado após a reconexão.

`offline-queue-regression.spec.cjs` usa o IndexedDB real do Chromium e respostas HTTP controladas, sem alterar o banco SQL. Cobre a ordem entre alterações novas e pendentes, respostas sem confirmação de sucesso (vazias, HTML, JSON truncado ou `status: erro`), compatibilidade com `status: sucesso`, dependências de ocorrências nas rotas v1, jogos temporários intercalados, aborto de transação local, aplicação de resultados v1 no chaveamento, sondagem do servidor local, coordenação entre abas e exportação/importação idempotente sem credencial CSRF. Para rodar apenas essas regressões: `npm --prefix tests/browser test -- tests/browser/offline-queue-regression.spec.cjs`. Os testes JavaScript também verificam a captura dos cadastros e a projeção de placares pelas rotas v1.

O cenário de chaveamento ímpar prepara três equipes com elenco e exige um avanço automático inicial. Se a preparação falhar, a suíte falha. A retificação de placar usa os identificadores criados pelo próprio teste e consulta os valores persistidos depois da alteração.

`deployment-paths.spec.cjs` verifica redirecionamento, login, carregamento de arquivos e paridade das APIs no endereço configurado. Para testar uma instalação em subdiretório, inicie um servidor separado com `SGI_BASE_PATH=SGI` e execute esse teste com `SGI_BASE_URL=http://127.0.0.1:PORTA/SGI/`. O teste também roda normalmente na raiz. Os testes unitários usam sessões próprias em `test-results/unit-sessions/`, sem depender da pasta de sessões do servidor.

`legacy-offline-compat.spec.cjs` cobre fila antiga sem `session`, alias antigo de mutação, isolamento entre operadores, casca sem `pageSources` e retry após falha de rede. A compatibilidade exige uma casca autenticada/preparada; refresh, nova aba e cold-open sem essa casca não são declarados como suporte porque o pacote não usa Service Worker.

`visual-contract.spec.cjs` compara quatro imagens do login em desktop/mobile. A resposta de credenciais inválidas é fixa nesse teste visual; a autenticação real é validada separadamente. O fluxo Docker compara as referências Linux; a execução manual no Windows usa as referências Windows.

Imagens, traces e relatório ficam em `tests/browser/test-results/` e `tests/browser/playwright-report/`. Só atualize snapshots após inspecionar uma mudança visual intencional. Testes aprovados cobrem os cenários descritos; não representam garantia de ausência de qualquer defeito.

## Matriz e recuperação

O CI executa qualidade apenas em PHP 8.4. Integração HTTP/banco/recuperação,
navegador e contrato visual usam PHP 8.4 e MariaDB 10.11; o visual usa as
referências Linux. A configuração está em `.github/workflows/ci.yml`. A matriz
do CI usa uma única versão de PHP e MariaDB; MySQL e PHP 8.2 continuam disponíveis
para execuções locais pelo executor Docker.

`php tests/run_all.php` também executa o ensaio sintético de recuperação. Ele cria bases temporárias com nomes próprios, gera um `mysqldump` contendo schema/dados/triggers, grava hash e versão em `test-results/t28-recovery-*.json`, atualiza uma cópia com as migrações e restaura o dump em outra base. As bases são removidas ao final; os dumps e manifestos permanecem como evidência ignorada pelo Git. O ensaio não aponta para base de trabalho e não limpa filas IndexedDB.
