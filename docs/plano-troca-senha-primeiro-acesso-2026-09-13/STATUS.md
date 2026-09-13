# Status da implementação

**Estado:** implementada e validada localmente em 2026-09-13.

## Implementação concluída

- A migração `012_student_first_login_password.sql` cria o marcador persistente `senha_troca_pendente` e marca os alunos atuais para troca.
- Importação PDF, cadastro administrativo e reset de aluno usam a senha inicial compartilhada `sesi-senai`; somente o hash é persistido. A rotina `students:senha-inicial` inicializa contas existentes em desenvolvimento, revoga sessões por `auth_version` e é idempotente.
- Login e revalidação da sessão usam o estado persistido. O Kernel bloqueia páginas e APIs até a troca, mantém logout disponível, exige CSRF no POST de senha e só libera a nova tela enquanto a pendência existir.
- A tela dedicada troca a senha antes dos termos; após a troca, o aluno segue para termos ou portal conforme o aceite. Senha inválida ou CSRF inválido preservam hash, marcador e versão; sessões concorrentes antigas são revogadas.
- A senha não é enfileirada, exportada ou reimportada pelo fluxo offline. Fixtures HTTP e de navegador agora concluem o primeiro acesso antes de aceitar termos; atletas sintéticos têm identificadores próprios para evitar colisões nos retries.
- A regressão HTTP cobre cadastro, hash, redirecionamentos diretos, bloqueio de APIs, CSRF, limites e confirmação, troca, aceite de termos, reset, corrida de `auth_version` e revogação. A regressão de navegador cobre o onboarding na mesma aba e a proteção da fila offline.

## Verificações executadas

Todos os perfis usaram bancos e sessões descartáveis dos executores Docker; nenhum teste usou o banco de trabalho.

| Comando | Resultado |
|---|---|
| `powershell -NoProfile -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mariadb -PhpVersion 8.4 -IncludeVisual` | Aprovado: qualidade, build, 366 testes PHPUnit/2535 asserções, lint PHP, PHPStan, estilo, 43 verificações JavaScript, 101 testes JavaScript, integração 987/987, Playwright 64/64 e visual 2/2. |
| `powershell -NoProfile -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mysql -PhpVersion 8.4 -IncludeVisual` | Aprovado com os mesmos resultados: integração 987/987, Playwright 64/64 e visual 2/2, além da qualidade e do build. |
| `powershell -NoProfile -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mariadb -PhpVersion 8.2 -SkipBrowser` | Aprovado: qualidade em PHP 8.2, 366 testes PHPUnit/2535 asserções, build, verificações JavaScript e integração 987/987. Browser e contrato visual não foram executados neste alvo. |
| `npm run check` | Aprovado localmente: 43 arquivos JavaScript válidos. |
| `git diff --check` | Sem erros de whitespace; o Git mostrou apenas avisos de normalização LF/CRLF em arquivos modificados no Windows. |

Os cenários de migração verificaram instalação, upgrade até `012`, preservação de IDs/vínculos, repetição e recuperação sintética nos dois motores SQL. A suíte completa em MariaDB e MySQL passou após corrigir o redirecionamento da página de troca e atualizar as fixtures que ainda aceitavam termos sem concluir a nova etapa.

## Base local de desenvolvimento

A migração e o inicializador **não foram executados na base de trabalho**, para preservar as contas e os dados já existentes. Ao preparar a base de desenvolvimento, aplique as migrações e, se houver alunos existentes, confira o valor real de `SGI_DB_NAME`, defina `SGI_APP_ENV=development` e rode `php bin/sgi.php students:senha-inicial --confirm-database=<SGI_DB_NAME>`. Esse inicializador redefine a senha dos alunos para `sesi-senai`, marca a troca obrigatória e incrementa `auth_version`; ele recusa ambiente diferente de desenvolvimento ou nome de base sem confirmação.

Não foi executado CI remoto nem validado navegador/visual em PHP 8.2. Os perfis completos de navegador e visual foram executados em MariaDB 10.11 e MySQL 8.4 com PHP 8.4.
