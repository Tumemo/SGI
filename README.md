# SGI — Sistema de Gestão de Interclasses 🏆⚽🏐

> Sistema web completo para administração, agendamento, chaveamento, pontuação, controle disciplinar e operação offline de eventos esportivos e culturais escolares (Interclasses) no SESI.

---

## 📌 Principais Recursos

- 🏫 **Gestão Multi-Edições:** Crie e gerencie edições do Interclasse com geração automática de turmas, modalidades esportivas e equipes padrão.
- 📄 **Importação Automática de Alunos via PDF:** Faça upload das listas de chamada em PDF e o sistema extrai e cadastra os alunos diretamente em suas respectivas turmas.
- 📱 **Portal do Aluno (Mobile-First):** Alunos podem aceitar termos de responsabilidade, consultar a agenda de jogos da sua turma, visualizar o ranking geral e se inscrever em até 3 modalidades esportivas.
- ⚡ **Operação 100% Offline do Mesário (Offline-First):** Mesários na quadra continuam operando o placar, cronômetro, faltas, gols, cartões e avanço do mata-mata mesmo sem conexão de internet (via IndexedDB). Na reconexão, todas as ações são sincronizadas atomicamente com o servidor MySQL.
- 🌳 **Árvore de Chaveamento Interativa:** Visualização em tempo real das chaves de mata-mata com avanço automático dos vencedores até a Grande Final.
- 📊 **Ranking Geral em Tempo Real:** Pontuações calculadas automaticamente com base nos pódios (1º, 2º e 3º lugares), itens arrecadados na campanha solidária e descontos por ocorrências disciplinares.

---

## 🛠️ Tecnologias Utilizadas

- **Backend:** PHP 8.2+ com MySQLi
- **Banco de Dados:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5, Bootstrap Icons
- **Armazenamento e Cache Offline:** IndexedDB, Cache Storage, Service Worker / SPA Shell
- **Bibliotecas:** `Smalot\PdfParser` (leitura de PDF), `Axios`

---

## 🚀 Instalação e Execução Local

### Pré-requisitos
- [XAMPP](https://www.apachefriends.org/) (com Apache e MySQL) ou ambiente equivalente com PHP 8.2+.

### Passo a Passo

1. **Clonar ou copiar o projeto para o diretório web:**
   ```bash
   # Exemplo no XAMPP para Windows:
   C:\xampp\htdocs\SGI
   ```

2. **Configurar o Banco de Dados:**
   - Inicie o módulo **MySQL** no XAMPP Control Panel.
   - Crie o banco de dados `sgi` no phpMyAdmin (`http://localhost/phpmyadmin`) ou via terminal:
     ```sql
     CREATE DATABASE sgi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
     ```
   - Importe o arquivo de schema localizado em `docs/sgi.sql`.

3. **Configurar variáveis de ambiente:**
   - Copie `.env.example` para `.env` (ou configure as mesmas variáveis no servidor).
   - Informe `SGI_DB_HOST`, `SGI_DB_PORT`, `SGI_DB_NAME`, `SGI_DB_USER` e `SGI_DB_PASSWORD`.
   - O arquivo `.env` não deve ser versionado; a aplicação não mantém credenciais no código.

4. **Acessar a Aplicação:**
   - Em Apache, configure `SGI/public` como **DocumentRoot**. O front controller
     mantém as URLs `/api/...` e `/views/...` sem publicar `config/`, `src/`,
     `tests/`, `vendor/` ou `storage/`.
   - Abra o navegador e acesse: [http://localhost/SGI/](http://localhost/SGI/)
   - Para acesso em outros computadores ou celulares na mesma rede Wi-Fi, utilize o IP da sua máquina (ex.: `http://10.141.117.2/SGI/`).

---

## 👥 Credenciais de Teste Padrão

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
6. Reconecte a rede (Network -> **Online**): todas as alterações locais serão enviadas e integradas no banco MySQL instantaneamente sem perda de dados.

---

## 📂 Estrutura de Diretórios

```
SGI/
├── public/                # Único DocumentRoot (front controller e regras HTTP)
├── api/                   # Endpoints REST e controladores PHP
│   ├── includes/          # Regras de negócio (chaveamento, PDF, equipes, validações)
│   ├── interclasse.php    # CRUD e gerenciamento de edições
│   ├── jogos.php          # Gerenciamento e agendamento de partidas
│   ├── lancar_resultado.php # Finalização de jogos e cálculo de pódios
│   ├── login.php          # Autenticação e controle de sessões
│   └── ...
├── config/                # Bootstrap e conexão com o banco
├── src/                   # Domínio, casos de uso e adaptadores PSR-4
│   ├── Autenticacao/      # Login e repositórios de usuários/edições
│   ├── Interclasse/       # Edições, turmas, equipes, jogos e resultados
│   ├── Usuarios/           # Casos administrativos de usuários
│   └── Shared/             # Configuração, sessão e armazenamento
├── docs/                  # Scripts SQL (sgi.sql) e documentações
├── storage/               # Arquivos de execução (uploads fora do Git)
├── tools/                 # Lint, análise e servidor de testes
└── views/                 # Interfaces do usuário
    ├── src/componentes/   # Motores JavaScript offline (IndexedDB, SPA, Chaveamento)
    ├── src/pages/         # Painéis administrativos, agenda, placar e ranking
    └── src/pages/alunos/  # Portal exclusivo dos competidores/alunos
```

---

## 🧪 Testes Automatizados e Auditoria

O projeto conta com uma suite completa de testes automatizados modulares:

```bash
# Testes unitários, lint, estilo e análise estática:
composer test
composer lint
composer analyse
composer cs:check

# O CI também executa a suíte HTTP em MySQL isolado a cada push/PR.

# Testes HTTP completos (118 asserções) em banco isolado:
# terminal 1
powershell -ExecutionPolicy Bypass -File tools/start-test-server.ps1
# terminal 2
$env:SGI_TEST_BASE_URL = 'http://127.0.0.1:8099'
$env:SGI_TEST_DB_NAME = 'sgi_test'
php tests/run_all.php

# Inicializar/resetar dados de demonstração (Edição ativa, turmas, equipes e alunos):
php tests/seed_interclasse_demo.php
```

---

## 📄 Licença e Direitos
Desenvolvido para uso educacional e institucional no **SESI**. Todos os direitos reservados.
