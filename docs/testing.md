# Execução dos testes

## Verificações locais sem banco

Na raiz: `composer install`, `npm ci --ignore-scripts` e `npm run build`.

```bash
composer verify
npm run check
npm test
```

O PHPUnit testa regras de negócio, contratos HTTP unitários, organização dos módulos, limites das camadas, parsing SQL e validação do banco de teste. A análise estática usa PHPStan e o estilo usa PHP CS Fixer. A verificação de sintaxe inclui os templates privados.

## Banco isolado

Se usar Docker, `docker compose -f compose.test.yml --profile mariadb up -d --wait` disponibiliza uma base descartável em `127.0.0.1:3308`. Para MySQL, use o perfil `mysql` e a porta `3307`. Ambos usam banco `sgi_test`, usuário `root` e senha `sgi-test-only`; os dados são temporários e os serviços só escutam na interface local.

Também é possível usar outra instância dedicada de MySQL/MariaDB. Não aponte testes para dados de trabalho. Configure **nos dois terminais** as mesmas variáveis:

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

Não execute duas suítes que alteram o banco simultaneamente. Os cenários de integração montam uma edição compartilhada em sequência. O teste específico de concorrência usa dois processos independentes para reenviar a mesma mutação e conferir a ausência de duplicação.

## Navegador

```powershell
npm ci --prefix tests/browser
npx --prefix tests/browser playwright install chromium
$env:SGI_BASE_URL = 'http://127.0.0.1:8099/'
npm --prefix tests/browser test
```

Use a base preparada pelo runner HTTP. `SGI_E2E_RESET=1` pode executar esse preparo no início; nesse caso configure também `SGI_PHP_PATH` e as variáveis do teste HTTP. O Chromium instalado pelo Playwright é o padrão. `SGI_CHROME_PATH` permite outro executável, mas comparações visuais devem usar a mesma versão das referências.

Os testes cobrem administração, portal do aluno, permissões, todas as telas principais e torneios com sete partidas. Os cenários offline desabilitam a rede do navegador, verificam IndexedDB e conferem no servidor o resultado após a reconexão.

`deployment-paths.spec.cjs` verifica redirecionamento, login, carregamento de arquivos e paridade das APIs no endereço configurado. Para testar uma instalação em subdiretório, inicie um servidor separado com `SGI_BASE_PATH=SGI` e execute esse teste com `SGI_BASE_URL=http://127.0.0.1:PORTA/SGI/`. O teste também roda normalmente na raiz. Os testes unitários usam sessões próprias em `test-results/unit-sessions/`, sem depender da pasta de sessões do servidor.

`visual-contract.spec.cjs` compara quatro imagens do login em desktop/mobile. A resposta de credenciais inválidas é fixa nesse teste visual; a autenticação real é validada separadamente. As referências versionadas são do Windows. No Linux, execute os demais testes com `--grep-invert "contrato visual do acesso"`; o CI executa a comparação visual em um job Windows.

Imagens, traces e relatório ficam em `tests/browser/test-results/` e `tests/browser/playwright-report/`. Só atualize snapshots após inspecionar uma mudança visual intencional. Testes aprovados cobrem os cenários descritos; não representam garantia de ausência de qualquer defeito.
