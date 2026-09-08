# SGI — Sistema de Gestão de Interclasses 🏆⚽🏐

> Sistema web completo para administração, agendamento, chaveamento, pontuação, controle disciplinar e operação offline de eventos esportivos e culturais escolares (Interclasses) no SESI.

---

## 📌 Principais Recursos

- 🏫 **Gestão Multi-Edições:** Crie e gerencie edições do Interclasse com geração automática de turmas, modalidades esportivas e equipes padrão.
- 📄 **Importação Automática de Alunos via PDF:** Faça upload das listas de chamada em PDF e o sistema extrai e cadastra os alunos diretamente em suas respectivas turmas.
- 📱 **Portal do Aluno (Mobile-First):** Alunos podem aceitar termos de responsabilidade, consultar a agenda de jogos da sua turma, visualizar o ranking geral e se inscrever em até 3 modalidades esportivas.
- ⚡ **Operação 100% Offline do Mesário (Offline-First):** Mesários na quadra continuam operando o placar, cronômetro, faltas, gols, cartões e avanço do mata-mata mesmo sem conexão de internet (via IndexedDB). Na reconexão, a fila reenvia as operações e confirma os resultados do servidor.
- 🌳 **Árvore de Chaveamento Interativa:** Visualização em tempo real das chaves de mata-mata com avanço automático dos vencedores até a Grande Final.
- 📊 **Ranking Geral em Tempo Real:** Pontuações calculadas automaticamente com base nos pódios (1º, 2º e 3º lugares), itens arrecadados na campanha solidária e descontos por ocorrências disciplinares.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2+ com MySQLi
- **Banco de Dados:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Bootstrap Icons
- **Armazenamento e Cache Offline:** IndexedDB e SPA Shell
- **Bibliotecas:** `Smalot\PdfParser` (leitura de PDF), `Axios`

---

## 🚀 Instalação e Execução Local

### Pré-requisitos

PHP 8.2 ou 8.4 com MySQLi, mbstring, fileinfo, DOM e cURL; Composer; Node.js 22 para preparar os arquivos do navegador; MySQL/MariaDB.

```bash
composer install
npm ci --ignore-scripts
npm run build
```

Copie `.env.example` para `.env` e configure a conexão com um banco previamente criado. Para uma base nova:

```bash
php bin/sgi.php migrate
```

As migrações não criam contas com senhas de demonstração. Para cadastrar o primeiro administrador, defina temporariamente `SGI_ADMIN_LOGIN`, `SGI_ADMIN_NAME` e `SGI_ADMIN_PASSWORD` no ambiente (senha de pelo menos 12 caracteres) e execute `php bin/sgi.php admin:create`. Remova essas variáveis depois. Esse comando recusa alterar uma instalação que já possui administrador.

Para uma instalação existente, siga [atualização e recuperação](docs/deployment.md). O pacote atual usa exclusivamente as migrações versionadas; a evolução do banco não depende de dumps históricos dentro do repositório.

Configure o servidor web com **DocumentRoot em `public/`**. Para desenvolvimento local:

```bash
php -S 127.0.0.1:8080 -t public public/index.php
```

Acesse `http://127.0.0.1:8080/`. Em publicação sob `/SGI`, configure `SGI_BASE_PATH=/SGI`. Arquivos de sessão, uploads e importações permanecem fora de `public/`; mantenha `.env` fora do Git.

---

## 👥 Credenciais somente do banco de testes

Estas contas são carregadas exclusivamente por `tests/run_all.php` em uma base isolada.

| Perfil | Matrícula / Login | Senha | Nível | Finalidade |
| :--- | :--- | :--- | :---: | :--- |
| **Administrador** | `admin` | `123` | `0` | Acesso a todas as configurações, edições e relatórios. |
| **Colaborador** | `colab` | `123` | `1` | Gestão de jogos, pontuações, turmas e ocorrências. |
| **Mesário** | `mesario` | `123` | `2` | Operação de partidas e placar em tempo real (online/offline). |
| **Aluno** | *RM do Aluno* (ex: `2879`) | `123` | `3` | Portal do competidor, inscrições e termos. |

---

## 📶 Como Testar o Modo Offline do Mesário

1. Acesse [http://localhost/SGI/](http://localhost/SGI/) e faça login com a conta de **Mesário** (`mesario` / `123`).
2. Aguarde a confirmação **"Pronto para uso offline! 🟢"** no topo da tela (o pré-carregamento dos dados e telas é concluído em poucos segundos).
3. Desconecte a rede ou abra o DevTools (**F12**) -> aba **Network** -> selecione **Offline**.
4. Navegue entre as partidas, inicie o jogo, pontue gols, aplique penalidades e finalize as partidas.
5. Note que as chaves avançam automaticamente mesmo sem internet.
6. Reconecte a rede (Network -> **Online**): todas as alterações locais serão enviadas e integradas no banco MySQL. Operações recusadas permanecem na fila para revisão.

---

## 📂 Estrutura de Diretórios

```text
SGI/
├── public/                 # DocumentRoot; index.php e assets gerados
├── bootstrap/              # Autoload e composição da aplicação
├── config/                 # Ambiente e rotas
├── src/Modules/            # Acesso, Eventos, Participantes, Competições,
│                           # Resultados, Disciplina e Sincronização
├── src/Shared/             # HTTP, conexão, transações, migrações e storage
├── resources/views/        # Templates privados e componentes
├── resources/js/           # Páginas, código compartilhado e motores offline
├── resources/css/          # Fontes das folhas de estilo
├── resources/images/       # Imagens e ícones da aplicação
├── database/migrations/    # Alterações versionadas do banco
├── database/seeders/       # Dados exclusivos de testes
├── storage/                # Dados gerados em execução, fora do Git
├── tests/                  # Unitários, integração, JavaScript e navegador
├── tools/                  # Preparação de assets e verificações
└── docs/                   # Arquitetura, implantação e requisitos
```

---

## 🧪 Testes Automatizados e Auditoria

Os testes devem usar banco e servidor isolados. O reset exige um nome contendo `test`/`testing` e confere se o servidor HTTP aponta para a mesma base antes de alterar dados.

```bash
composer verify
npm run check
npm test
```

Para os testes HTTP e de navegador, siga [o guia de testes](docs/testing.md). O fluxo completo executa:

```bash
php tests/run_all.php
npm --prefix tests/browser test
```

Há testes de autenticação, CSRF, permissões, importação PDF, inscrições, agendamento, ranking, migrações, concorrência, rollback e torneios online/offline. As comparações de imagem usam referências aprovadas no Windows. O CI foi configurado para PHP 8.2/8.4, MySQL/MariaDB e Chromium; a execução remota depende do envio destas alterações ao repositório.

Detalhes das camadas e da compatibilidade estão em [arquitetura](docs/architecture.md).

---

## 📄 Licença e Direitos
Desenvolvido para uso educacional e institucional no **SESI**. Todos os direitos reservados.
