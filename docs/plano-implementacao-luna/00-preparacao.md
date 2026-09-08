# Etapa 0 — Preparação reproduzível

Leia primeiro [README.md](README.md). Não implementar correções nesta etapa.

## T00 — Confirmar ambiente, código e resultados anteriores

**Entrada:** nenhuma tarefa anterior. **Saída:** ambiente seguro e resultado inicial registrado.

### Arquivos a ler

- `AGENTS.md`, `composer.json`, `package.json`.
- `docs/testing.md`, `compose.test.yml`, `tools/start-test-server.ps1`.
- `tests/run_all.php`, `tests/Support/TestDatabase.php`, `tests/browser/playwright.config.cjs`.

### Passos

1. Executar `git status --short`, `git rev-parse HEAD` e `git branch --show-current`.
2. Preservar alterações preexistentes. Os documentos deste plano podem ainda estar não versionados. Não removê-los nem atribuí-los à implementação.
3. Conferir executáveis PHP, Composer e Node. Nesta máquina, o PHP do PATH estava sem MySQLi e `C:\xampp\php\php.exe` tinha a extensão. Verificar novamente; não assumir que continuam iguais.
4. Se necessário, escolher o PHP existente com as extensões exigidas e acrescentar seu diretório ao PATH somente do processo/sessão, para que Composer e os subprocessos usem a mesma versão.
5. Não executar instalação se `vendor/` e `node_modules/` estiverem corretos e disponíveis. Se faltarem dependências, usar os lockfiles: `composer install`, `npm ci --ignore-scripts` e instalação do pacote de navegador conforme o guia.
6. Preparar banco dedicado. Preferir o Docker documentado quando disponível; reutilizar outra instância apenas após confirmar que é a instância de testes. Não presumir senha vazia, porta 3306 ou dados descartáveis pelo nome do serviço.
7. Usar banco `sgi_test_luna`, servidor `127.0.0.1:8110` e caminhos de execução em `test-results/`. Se a porta estiver ocupada, selecionar outra e atualizar todas as variáveis desta execução.
8. Conferir a resposta de `/api/v1/health`: precisa declarar modo de teste e exatamente `sgi_test_luna` antes do runner que reconstrói o banco.
9. Executar os checks abaixo, sequencialmente. Registrar versões e resultados reais em STATUS.

### Comandos de referência — PowerShell

Execute da raiz do projeto. As credenciais abaixo são exclusivamente do serviço descartável documentado no repositório.

```powershell
Set-Location C:\Projetos\SGI
git status --short
git rev-parse HEAD
php -v
php --ri mysqli
node --version
```

Se o PHP selecionado for o do XAMPP:

```powershell
$sgiPhp = 'C:\xampp\php\php.exe'
$env:PATH = (Split-Path -Parent $sgiPhp) + ';' + $env:PATH
php --ri mysqli
```

Para o serviço Docker documentado:

```powershell
docker compose -f compose.test.yml --profile mariadb up -d --wait
$env:SGI_DB_HOST = '127.0.0.1'
$env:SGI_DB_PORT = '3308'
$env:SGI_DB_USER = 'root'
$env:SGI_DB_PASSWORD = 'sgi-test-only'
$env:SGI_DB_NAME = 'sgi_test_luna'
$env:SGI_TEST_DB_NAME = 'sgi_test_luna'
$env:SGI_APP_ENV = 'test'
$env:SGI_BASE_PATH = ''
$env:SGI_TEST_BASE_URL = 'http://127.0.0.1:8110'
$env:SGI_BASE_URL = 'http://127.0.0.1:8110/'
$env:SGI_E2E_RESET = '0'
```

Servidor, em processo separado com o mesmo ambiente:

```powershell
powershell -File tools/start-test-server.ps1 -Database sgi_test_luna -Port 8110 -PhpPath $sgiPhp
```

Se executar como agente, manter esse processo em uma sessão de ferramenta ou iniciá-lo com `Start-Process -WindowStyle Hidden`; registrar o PID e redirecionar logs para `test-results/`. Não abrir uma janela de terminal visível sem necessidade. O processo dos testes precisa herdar as mesmas variáveis do servidor.

```powershell
(Invoke-WebRequest "$env:SGI_TEST_BASE_URL/api/v1/health").Content
composer verify
npm run build
npm run check
npm test
php tests/run_all.php
npm --prefix tests/browser test
git diff --check
```

**Regra de execução:** executar um comando por vez e conferir seu código de saída. Não continuar a sequência como se tudo tivesse passado após uma falha. Não executar as suítes HTTP e de navegador simultaneamente: ambas escrevem no banco.

### Resultado inicial conhecido, apenas para comparação

116 testes/1.514 asserções PHPUnit; 224 asserções HTTP; 5 testes JavaScript; 26 testes de navegador; 230 assets. Esses números podem aumentar. Não usá-los como quantidade fixa em testes e não exigir que permaneçam iguais.

### Aceitação

- [ ] Banco de teste confirmado pelo servidor.
- [ ] Versões e caminho do PHP registrados.
- [ ] Cada check inicial tem resultado ou impedimento de ambiente explícito.
- [ ] Nenhum arquivo de aplicação ou dado de trabalho alterado.

## T01 — Preparar fixtures e registro das novas regressões

**Depende de:** T00. **Saída:** mecanismo para acrescentar testes que realmente serão executados.

### Arquivos a ler

- `tests/Support/TestClient.php`, `tests/Support/Assertions.php`.
- `tests/Integration/AuthAndRbacTest.php`, `AtomicMutationTest.php`, `InterclasseLifecycleTest.php`.
- `tests/bootstrap.php`, `phpunit.xml`, `tests/browser/fixtures.cjs`.

### Passos

1. Entender a separação: PHPUnit descobre `tests/Unit`; a integração HTTP usa o runner manual `tests/run_all.php`. Criar um arquivo de integração sem registrá-lo no runner não executa o teste.
2. Preparar um helper `tests/Support/AuditFixtures.php` se os novos cenários repetirem preparação. Usar nomes sintéticos e IDs retornados na criação, nunca IDs presumidos do banco.
3. Fixture de autorização: edição A ativa, edição B inativa, duas turmas/equipes/atletas em cada, jogos e partidas de ambas, usuários admin/colaborador/mesário/aluno. Guardar os IDs em uma estrutura nomeada.
4. Fixture de pontuação: valores de pódio 10/7/5, item 2, turmas inicialmente sem pontos/arrecadação, final identificável pela regra real de tags.
5. Registrar os novos testes no runner em ponto em que as dependências necessárias existam. O teste deve poder criar seus próprios dados e restaurar a edição ativa anterior em `finally`.
6. Não envolver HTTP em uma transação aberta no cliente esperando que o servidor enxergue inserts não confirmados: são conexões diferentes. Confirmar o preparo e executar limpeza/restauração por IDs explicitamente criados no teste.
7. Criar logs em `test-results/luna/`; mantê-los fora de arquivos de produção. Não adicionar atalhos HTTP de teste à aplicação.
8. Login no `TestClient` retorna `status: 'sucesso'`; não presumir `success: true` nesse endpoint. Usar o helper existente de autenticação e CSRF.
9. Testes de falha HTTP devem comparar o banco antes/depois. Uma resposta 403 sem verificar ausência de escrita não é prova suficiente de rollback.
10. Adicionar um cenário pequeno para validar que a fixture cria A/B corretamente e restaura a edição ativa; executar pela mesma entrada do runner que será usada nas próximas tarefas.

### Regra para testes posteriores

Não criar cópias das funções de produção nos testes. Para JavaScript, preferir módulos puros exportáveis ou a execução do arquivo real com dependências simuladas; usar navegador para integração. A extração via AST utilizada na auditoria foi reprodução pontual, não precisa virar o mecanismo permanente de testes.

### Aceitação

- [ ] Fixture cria recursos relacionados e valida os IDs.
- [ ] Novo teste aparece na saída do runner.
- [ ] Fixture não muda o significado dos cenários existentes.
- [ ] STATUS identifica os helpers disponíveis para T02.
