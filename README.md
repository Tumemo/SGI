# SGI — Sistema de Gestão de Interclasses

Aplicação web para organizar os Interclasses do SESI: edições anuais, turmas, alunos, modalidades, equipes, jogos, chaveamento, ranking, arrecadações e ocorrências disciplinares.

Este guia é o ponto de partida para quem vai estudar e continuar o projeto. Primeiro coloque a aplicação para funcionar localmente; depois use a seção de testes para validar suas alterações.

## Tecnologias e perfis de acesso

O SGI é um monólito modular em **PHP 8.2+ e MySQL/MariaDB**, com HTML, CSS e JavaScript sem framework de frontend. Usa Bootstrap, Axios e Smalot/PdfParser. O mesário usa IndexedDB para armazenar dados e operações offline no navegador.

| Nível | Perfil | Uso principal |
| --- | --- | --- |
| 0 | Administrador | Configurar edições, usuários e regras; acesso completo. |
| 1 | Colaborador | Gerenciar jogos, pontuações e ocorrências. |
| 2 | Mesário | Operar partidas da edição ativa, inclusive offline após o preparo. |
| 3 | Aluno | Aceitar termos, consultar jogos e se inscrever em até três modalidades. |

## Instalação local, passo a passo

Os exemplos abaixo usam **Windows e PowerShell**, executados na pasta do projeto. Nos comandos comuns (`php`, `composer` e `npm`), o fluxo também serve para Linux/macOS; as diferenças de configuração do ambiente estão indicadas.

### 1. Preparar as ferramentas e abrir a pasta

Instale ou disponibilize:

- **PHP 8.2 ou superior** (a matriz do projeto valida 8.2 e 8.4), com `mysqli`, `mbstring` e `fileinfo`. Para as ferramentas e os testes, habilite também DOM/XML, XMLWriter e cURL.
- **Composer 2**, para as dependências PHP.
- **Node.js 22 e npm**, para gerar os arquivos usados pelo navegador.
- **MySQL ou MariaDB** em execução. A matriz de testes usa MySQL 8.4 e MariaDB 10.11.
- **Git**, se for obter o projeto por clone. Também é possível extrair o pacote recebido.

Abra a pasta que contém `composer.json`, `package.json` e `README.md` no editor e abra um terminal nessa pasta. Confira:

```powershell
php -v
php --ini
php -m
composer --version
node --version
npm --version
```

Se usar XAMPP, inicie o serviço **MySQL** pelo painel. Para este guia, o próprio PHP servirá a aplicação; não é necessário iniciar o Apache nem colocar o projeto em `htdocs`. Se o PHP do XAMPP não estiver no PATH, ajuste nesta sessão do PowerShell:

```powershell
$env:Path = 'C:\xampp\php;C:\xampp\mysql\bin;' + $env:Path
```

Adapte os caminhos à sua instalação. Use `php --ini` para descobrir qual `php.ini` o terminal realmente utiliza.

### 2. Instalar dependências e gerar os assets

```powershell
composer install
npm ci --ignore-scripts
npm run build
```

Isso prepara `vendor/`, `node_modules/` e `public/assets/`. A instalação das dependências precisa de acesso à internet. Aguarde cada comando terminar com sucesso antes de continuar.

### 3. Criar o banco e configurar o ambiente

Crie uma **base vazia de desenvolvimento**, por exemplo `sgi`, usando o phpMyAdmin (aba SQL) ou o cliente MySQL/MariaDB:

```sql
CREATE DATABASE sgi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Copie o arquivo de configuração apenas na primeira instalação; preserve seu `.env` se ele já existir:

```powershell
Copy-Item .env.example .env
```

No Linux/macOS, use `cp .env.example .env`. Edite estas linhas no `.env` para combinar com o servidor e o banco locais:

```dotenv
SGI_APP_ENV=development
SGI_APP_DEBUG=0
SGI_APP_URL=http://127.0.0.1:8080/
SGI_BASE_PATH=

SGI_DB_HOST=127.0.0.1
SGI_DB_PORT=3306
SGI_DB_NAME=sgi
SGI_DB_USER=root
SGI_DB_PASSWORD=
```

O exemplo de `root` sem senha só funciona se o seu banco local estiver configurado assim. Informe seu usuário e senha reais quando necessário. O usuário precisa poder criar e alterar tabelas para executar as migrações. Mantenha as demais opções de diretórios do arquivo de exemplo.

Não envie `.env` para o Git. Variáveis `SGI_*` definidas no terminal têm prioridade sobre o arquivo; ao trocar de ambiente, confira se não ficaram valores de outra execução.

Crie as tabelas pelas migrações versionadas:

```powershell
php bin/sgi.php migrate
```

O comando deve terminar com **Banco atualizado.** Não é necessário importar um dump SQL. Para atualizar uma instalação com dados existentes, consulte [implantação e recuperação](docs/deployment.md).

Ao aplicar a migração `012_student_first_login_password.sql`, os alunos existentes ficam obrigados a trocar a senha. Em um banco local de desenvolvimento, para definir a senha inicial compartilhada `sesi-senai` para esses alunos e revogar sessões anteriores, execute depois da migração:

```powershell
php bin/sgi.php students:senha-inicial --confirm-database=sgi
```

Troque `sgi` pelo valor exato de `SGI_DB_NAME`. O comando exige `SGI_APP_ENV=development`, substitui as senhas dos alunos pela senha inicial compartilhada e pode ser repetido sem alterar alunos já inicializados. Instalações novas sem alunos existentes não precisam executá-lo. Alunos criados por cadastro ou importação e senhas redefinidas pela administração já usam essa senha inicial e precisam trocá-la no primeiro acesso.

### 4. Criar o primeiro administrador

As migrações não criam usuários de demonstração. No PowerShell, escolha seu login, nome e uma senha de pelo menos 12 caracteres:

```powershell
$env:SGI_ADMIN_LOGIN = Read-Host 'Login do administrador'
$env:SGI_ADMIN_NAME = Read-Host 'Nome do administrador'
$senhaAdmin = Read-Host 'Senha (mínimo de 12 caracteres)' -AsSecureString
$env:SGI_ADMIN_PASSWORD = [System.Net.NetworkCredential]::new('', $senhaAdmin).Password
php bin/sgi.php admin:create
Remove-Item Env:SGI_ADMIN_LOGIN, Env:SGI_ADMIN_NAME, Env:SGI_ADMIN_PASSWORD
Remove-Variable senhaAdmin
```

No Linux/macOS, uma alternativa é adicionar temporariamente `SGI_ADMIN_LOGIN`, `SGI_ADMIN_NAME` e `SGI_ADMIN_PASSWORD` ao `.env`, executar `php bin/sgi.php admin:create` e remover essas três linhas em seguida.

O resultado esperado é **Administrador inicial criado: ...**. O comando recusa criar outro administrador se já existir um; ele não redefine senhas. Guarde as credenciais escolhidas para entrar no sistema.

### 5. Iniciar o servidor e acessar

Na raiz do projeto:

```powershell
php -S 127.0.0.1:8080 -t public public/index.php
```

Mantenha esse terminal aberto e acesse [http://127.0.0.1:8080/](http://127.0.0.1:8080/). Entre com o administrador criado no passo anterior. Use outro terminal para comandos adicionais; para encerrar o servidor, pressione **Ctrl+C**.

O diretório público do servidor deve ser sempre **`public/`**. Não abra os arquivos PHP diretamente no navegador. O servidor embutido é destinado ao desenvolvimento local; publicação com Apache ou outro servidor está descrita em [implantação](docs/deployment.md).

### 6. Preparar dados para explorar o sistema

Uma instalação nova não contém o evento de demonstração da suíte de testes. Pelo painel administrativo:

1. Crie uma edição do Interclasses e deixe a edição desejada ativa.
2. Confira as categorias, turmas, modalidades e equipes geradas e ajuste os cadastros.
3. Cadastre ou importe alunos e prepare as inscrições/equipes que participarão dos jogos.
4. Cadastre um usuário mesário e configure os locais e jogos para experimentar o placar.
5. Faça login com cada perfil para conferir suas telas e permissões.

Use dados fictícios nas atividades de desenvolvimento. Para cenários automatizados já preparados, siga a próxima seção; os fixtures de testes não devem ser carregados sobre sua base de trabalho.

## Testes automatizados

**Explorar a aplicação em `8080` e executar a suíte automatizada são fluxos diferentes.** Os testes de integração recriam dados e precisam de banco e servidor isolados. Os executores abaixo preparam esse ambiente; não aponte testes para uma base que deseja preservar.

### Windows com ferramentas locais

Depois da instalação acima, prepare também o navegador de testes:

```powershell
npm ci --prefix tests/browser
npx --prefix tests/browser playwright install chromium
```

Para executar a suíte completa, incluindo qualidade PHP, build, verificações JavaScript, integração HTTP/banco e navegador:

```powershell
$env:SGI_TEST_DB_HOST = '127.0.0.1'
$env:SGI_TEST_DB_PORT = '3306'
$env:SGI_TEST_DB_USER = 'root'
$env:SGI_TEST_DB_PASSWORD = ''
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local
```

Ajuste as credenciais do banco de testes. O usuário precisa poder criar e remover as bases temporárias. Os clientes `mysql` e `mysqldump` também são necessários para o ensaio de recuperação; o executor procura os executáveis do XAMPP no Windows. Ele cria um nome exclusivo para a base de teste e inicia seu próprio servidor HTTP. As credenciais acima devem ser definidas **no terminal**, pois o script PowerShell não importa o `.env` de desenvolvimento.

Para uma verificação de qualidade sem banco nem servidor:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite quality
```

Esse perfil executa `composer verify`, `npm run build`, `npm run check` e `npm test`. Ele não substitui integração e navegador. Antes e depois de refatorações, execute a suíte completa. Acrescente `-IncludeVisual` para as comparações de imagem.

### Alternativa: ambiente descartável com Docker

Com Docker e Docker Compose disponíveis e o Docker em execução, não é necessário instalar PHP, Node ou banco no computador para este fluxo de testes:

```powershell
powershell -File tools/test-docker.ps1 -Database mariadb
```

No Linux/macOS:

```bash
sh tools/test-docker.sh --database mariadb
```

Use `-Database mysql` (ou `--database mysql`) para MySQL 8.4. `-IncludeVisual`/`--include-visual` inclui o contrato visual. Sem `-Keep`/`--keep`, os containers são removidos ao final. Esse fluxo valida o projeto; não é o servidor de desenvolvimento do passo 5.

Os relatórios ficam em `test-results/` e, conforme o executor, em `tests/browser/test-results/` e `tests/browser/playwright-report/`. Consulte [o guia de testes](docs/testing.md) para perfis, execução manual de `php tests/run_all.php`, configuração do Playwright e diagnóstico.

### Contas exclusivas dos fixtures de teste

Estas contas são preparadas pela suíte na **base isolada**, não pelo comando `migrate` da instalação local:

| Perfil | Login | Senha |
| --- | --- | --- |
| Administrador | `admin` | `123` |
| Colaborador | `colab` | `123` |
| Mesário | `mesario` | `123` |
| Aluno de teste | `2879` | `123` |

## Testar o modo offline manualmente

1. Com a aplicação local funcionando e uma edição ativa com jogos, entre com o **mesário que você cadastrou**, em [http://127.0.0.1:8080/](http://127.0.0.1:8080/).
2. Aguarde a mensagem **Pronto para uso offline! 🟢**. O login e o preparo inicial precisam de conexão com o servidor.
3. Na mesma aba, abra as ferramentas do navegador (**F12**), vá a **Network/Rede** e selecione **Offline**. Isso simula a perda de acesso ao servidor local; desligar apenas o Wi-Fi não interrompe o endereço `127.0.0.1`.
4. Opere o placar e navegue pela aplicação na aba já preparada. Experimente gols, ocorrências e conclusão de partidas de mata-mata.
5. Desative **Offline** (selecione **No throttling/Sem limitação**) e aguarde a sincronização. Confira os resultados persistidos após a reconexão. Operações recusadas permanecem na fila para revisão.

O modo offline usa uma casca de navegação previamente carregada e não utiliza Service Worker. Recarregar a página, fechar a aba ou abrir outra aba sem conexão não faz parte do fluxo suportado. Não limpe os dados do site/IndexedDB enquanto houver operações pendentes. Use sempre o mesmo endereço: `localhost` e `127.0.0.1`, assim como portas diferentes, têm armazenamentos separados no navegador.

## Entender o código e continuar o desenvolvimento

```text
SGI/
├── public/                 # Entrada HTTP e assets gerados; raiz do servidor web
├── bootstrap/              # Inicialização, autoload e composição da aplicação
├── config/                 # Configuração e rotas
├── src/Modules/            # Regras e funcionalidades por módulo
├── src/Shared/             # HTTP, banco, transações e armazenamento compartilhados
├── resources/views/        # Templates PHP das páginas e componentes
├── resources/js/           # Scripts das páginas e subsistema offline
├── resources/css/          # Fontes dos estilos
├── resources/images/       # Imagens e ícones
├── bin/sgi.php             # Comandos de migração e administrador inicial
├── database/migrations/    # Evolução versionada do banco
├── database/seeders/       # Fixtures exclusivos de testes
├── storage/                # Sessões, uploads e arquivos de execução
├── tests/                  # Testes PHP, HTTP, JavaScript e navegador
├── tools/                  # Build, verificações e executores de testes
└── docs/                   # Guias detalhados
```

Uma requisição entra por `public/index.php`, carrega `bootstrap/app.php` e segue para a rota e o controlador responsáveis. Os módulos são `Acesso`, `Eventos`, `Participantes`, `Competicoes`, `Resultados`, `Disciplina` e `Sincronizacao`. Dentro deles, `Domain` concentra contratos/regras, `Application` os casos de uso, `Infrastructure` a persistência e `Presentation/Http` os controladores.

Ao alterar o projeto:

- Edite JavaScript, CSS e imagens em `resources/` e execute **`npm run build`** para atualizar `public/assets/`. Não há servidor Node necessário durante a navegação nem build automático nesse comando.
- Crie APIs em `/api/v1` e templates em `resources/views/`. Use `SGI_ROOT` para includes e os utilitários `Assets`/`Url` para links.
- Para evoluir o banco, adicione uma migração em `database/migrations/`; não reescreva migrações já aplicadas. Execute `php bin/sgi.php migrate` e valide atualização e repetição nos testes.
- Preserve os identificadores das mutações e o schema offline enquanto houver operações pendentes no cliente.
- Execute as verificações e revise `git diff` antes de entregar alterações. Não versione senhas, uploads ou dados pessoais.

Leia [AGENTS.md](AGENTS.md) para as convenções, [arquitetura](docs/architecture.md) para o fluxo entre camadas, [testes](docs/testing.md) para validar alterações e [implantação e recuperação](docs/deployment.md) para atualizar instalações existentes.

## Problemas comuns na primeira execução

| Sintoma | O que conferir |
| --- | --- |
| `php`, `composer`, `node` ou `npm` não é reconhecido | Instalação e PATH; reabra o terminal após instalar as ferramentas. |
| PowerShell bloqueia `npm.ps1` | Use `npm.cmd` no lugar de `npm` e `npx.cmd` no lugar de `npx`. |
| Composer informa extensão PHP ausente | Veja `php --ini` e `php -m`, habilite a extensão no PHP usado pelo terminal e repita `composer install`. |
| `vendor/autoload.php` não encontrado | Execute `composer install` na raiz do projeto. |
| Conexão recusada ou `Access denied` no banco | Serviço MySQL/MariaDB iniciado, host/porta, credenciais do `.env` e eventuais variáveis do terminal. |
| Banco desconhecido ou tabela inexistente | Crie a base configurada e execute `php bin/sgi.php migrate`. |
| Login `admin` / `123` não funciona | Essas credenciais são dos testes. Na base local, use o administrador criado por `admin:create`. |
| Página sem estilos ou scripts | Execute `npm ci --ignore-scripts` e `npm run build`; confirme que o servidor aponta para `public/`. |
| Endereço errado ou erro 404 | Use o comando completo do servidor e o endereço `http://127.0.0.1:8080/`, com `SGI_BASE_PATH` vazio. |
| Porta 8080 ocupada | Escolha outra porta no comando `php -S`, no `SGI_APP_URL` e no endereço do navegador. |
| Erro ao gravar sessão ou upload | Confira permissão de escrita nos diretórios `storage/` configurados no `.env`. |
| Teste de navegador não encontra Chromium | Execute `npx --prefix tests/browser playwright install chromium`. |

Depois de mudar `.env` ou `php.ini`, reinicie o servidor local. Para investigar erros durante o desenvolvimento, consulte a saída do terminal do servidor e habilite temporariamente `SGI_APP_DEBUG=1` no ambiente local.

## Licença e direitos

Desenvolvido para uso educacional e institucional no **SESI**. Todos os direitos reservados.
