# Plano: troca obrigatória de senha no primeiro acesso

**Estado:** implementação concluída e validada localmente em 2026-09-13. Consulte [STATUS.md](STATUS.md) para comandos e resultados de cada alvo.

**Decisão de produto:** todos os alunos usam `sesi-senai` como senha inicial compartilhada. Toda senha temporária exige troca antes do acesso ao portal. A regra aplica-se à importação PDF, ao cadastro administrativo de aluno e ao reset administrativo.

## Objetivo e decisão de escopo

Depois de autenticar com uma credencial temporária, o aluno deve trocar a senha antes de aceitar termos, consultar o portal ou chamar qualquer outra API protegida. A regra precisa ser aplicada no servidor, inclusive em acesso direto a URL e chamadas HTTP; modal ou redirecionamento no navegador não são controles de autorização.

O projeto está em desenvolvimento e não precisa manter compatibilidade retroativa de contas, sessões ou dados. A implementação pode alterar o esquema e os dados atuais. Para dispensar detecção por hashes antigos, a migração inicial marcará os alunos existentes como pendentes de troca e a preparação do banco de desenvolvimento os inicializará com `sesi-senai`. Isso pode substituir senhas atuais dos alunos e exigir uma troca única; esse custo operacional foi aceito. Outros perfis não serão marcados.

A importação PDF, o cadastro administrativo de aluno e o reset usarão todos a senha inicial compartilhada `sesi-senai`. Essa escolha reduz o trabalho de distribuição individual; o produto aceita o risco porque o sistema não trata informações críticas e exige a troca antes de liberar o portal. A interface pode informar essa senha padrão, sem gerar ou exibir uma lista individual de credenciais. Persistir apenas o hash com `password_hash`; centralizar o valor em uma única constante/política para evitar divergência entre emissores. Não registrar senhas em logs.

## O que o checkout faz hoje

- `PdfAlunoImporter::inserirAlunosNaTurma()` cria todos os alunos importados com `password_hash('123', PASSWORD_DEFAULT)` e não grava um estado de troca.
- O cadastro administrativo de aluno e o reset geram senhas temporárias aleatórias, mas não guardam um estado que obrigue a troca.
- `LoginService` e `TermosService` deduzem `exige_troca_senha` verificando se a senha armazenada confere com `123`.
- `LoginController` grava a dedução na sessão e escolhe entre `/aluno/termos` e `/aluno/inicio`; não prioriza a troca.
- `Kernel` revalida a sessão e bloqueia aluno sem termos, mas não bloqueia um aluno com troca pendente. A troca aparece como modal dentro da página inicial, portanto depende de chegar à interface.
- `POST /api/v1/senha` já passa pelo CSRF central e atualiza `auth_version`. O controller substitui uma senha atual vazia por `123` quando a sessão indica troca, o que não cobre as senhas temporárias aleatórias.
- `SessionRevalidator` recarrega `auth_version` e o aceite de termos, mas ainda não recarrega o estado da troca.

Referências conferidas: `src/Modules/Participantes/Infrastructure/PdfAlunoImporter.php`, `src/Modules/Acesso/Application/LoginService.php`, `src/Modules/Acesso/Application/TermosService.php`, `src/Modules/Acesso/Presentation/Http/LoginController.php`, `src/Modules/Acesso/Presentation/Http/SenhaController.php`, `src/Modules/Acesso/Application/SenhaService.php`, `src/Modules/Acesso/Infrastructure/MysqliSenhaRepository.php`, `src/Shared/Http/Kernel.php`, `src/Shared/Http/SessionRevalidator.php` e `config/routes/web.php`.

## Contrato esperado

1. O estado da obrigação vive no banco, com nome explícito, por exemplo `senha_troca_pendente TINYINT(1) NOT NULL DEFAULT 0`. A sessão pode espelhá-lo para navegação, mas não é a fonte de autorização.
2. Importação PDF, cadastro de aluno que gera senha temporária e reset de senha marcam o estado como pendente na mesma operação que grava o hash. Uma troca confirmada limpa o estado na mesma gravação que substitui o hash e incrementa `auth_version`.
3. O login de aluno pendente redireciona primeiro para `/aluno/trocar-senha`, independentemente do aceite de termos. Depois da troca, o destino será `/aluno/termos` se faltar aceite ou `/aluno/inicio` se já aceitou.
4. Após `SessionRevalidator::valid()`, o `Kernel` usa o estado revalidado no servidor e, antes do bloqueio de termos, permite a um aluno pendente somente a página de troca, `POST /api/v1/senha` e logout. Só permita `/api/v1/session` ou outra rota de leitura se a nova página demonstrar que precisa dela; não libere dados de negócio, `/api/v1/termos`, a página de termos ou rotas administrativas. Assets públicos seguem o fluxo atual.
5. Páginas bloqueadas redirecionam para a troca. APIs bloqueadas retornam JSON de acesso negado com o redirecionamento, sem executar controlador de negócio. Respostas da tela e do endpoint de troca usam `Cache-Control: no-store`.
6. A rota de troca aceita ausência da senha atual somente quando a sessão revalidada corresponde ao próprio aluno com pendência no banco. A troca voluntária de conta não pendente continua exigindo senha atual. A senha nova mantém o mínimo atual de seis caracteres e não pode ser igual à senha que está sendo substituída; verificar contra o hash atual, sem guardar a senha temporária em sessão.
7. Uma troca inválida, CSRF inválido, falha de persistência ou tentativa de repetir uma pendência já consumida não limpa o marcador nem revoga a proteção. A gravação deve confirmar que a conta continua pendente e que o `auth_version` observado ainda é atual, para um reset administrativo concorrente prevalecer.
8. No sucesso, banco e sessão ficam com o mesmo novo `auth_version`; incremente-o uma vez no banco e atualize a versão local uma vez. A sessão que realizou a troca continua válida; sessões concorrentes antigas são revogadas. A pendência só termina depois da gravação confirmada.
9. Login e mudança de senha continuam online e sob `CsrfGuard`. Não colocar senhas em URLs, logs ou fila offline/IndexedDB. `sesi-senai` é a senha inicial pública definida pelo produto, não uma senha individual ou dado sigiloso.
10. Administradores, colaboradores e mesários permanecem fora da regra enquanto não tiverem senha temporária explícita. Não alterar o protocolo offline dos mesários nem introduzir Service Worker.

## Sequência de implementação para Luna

### T00 — Baseline e regressões primeiro

Antes de alterar o código, leia este plano e `AGENTS.md`, confira o estado atual das migrações/rotas e execute os testes existentes relevantes pelo executor isolado oficial. Acrescente testes permanentes que mostrem que hoje um aluno temporário consegue acessar páginas e APIs antes de trocar a senha. Registre resultados vermelhos sem mascará-los.

Não use `php tests/run_all.php` ou Playwright diretamente sem preparar a infraestrutura por `tools/test-local.ps1` ou `tools/test-docker.ps1`. Nunca rode seeds/reset na base normal de trabalho.

### T01 — Estado de troca e fontes de credenciais

- Adicione uma nova migração numerada após a última existente (neste checkout a próxima prevista é `012`; confirme antes de nomeá-la). Migrações aplicadas são imutáveis.
- Crie `senha_troca_pendente` com default falso para novos registros e marque como pendentes os registros atuais com `nivel_usuario = '3'`. Não tente comparar hashes bcrypt no SQL.
- Atualize o contrato/esquema dos repositórios de usuário e senha para selecionar e gravar esse estado.
- Marque pendência junto ao hash nos três emissores de senha temporária: `PdfAlunoImporter`, `MysqliUsuarioManagementRepository::createStudent()` e `MysqliUsuarioAdministrativoRepository::resetStudentPassword()` (com os serviços/controladores correspondentes).
- Centralize o valor público `sesi-senai` em uma política/constante única. Os três emissores devem gravar `password_hash('sesi-senai', PASSWORD_DEFAULT)` e marcar pendente; mantenha coerente o campo `senha_temporaria` já devolvido pelo cadastro/reset, sem criar uma senha diferente por aluno.
- Atualize a interface de importação e os avisos de cadastro/reset para informar que a senha inicial é `sesi-senai` e que a troca é obrigatória. Não gere lista de senhas por aluno nem persista o valor em claro.
- Como o esquema atual não pode calcular `password_hash()` em SQL e não há obrigação de preservar contas/dados de desenvolvimento, faça a inicialização atual por uma das vias explícitas: recriar/resemear o banco sintético com as fixtures atualizadas, ou criar uma rotina CLI idempotente e restrita ao ambiente de desenvolvimento que redefine hashes de alunos para essa senha, grava a pendência e incrementa `auth_version`. A atualização planejada da base de desenvolvimento está autorizada; confirme ambiente/nome da base antes de executá-la e nunca aplique a rotina em produção.
- Atualize fixtures/seeds sintéticos de alunos e testes de migração para declarar o estado intencionalmente; credenciais de teste continuam restritas aos fixtures.

### T02 — Login e sessão revalidada

- Faça `LoginService` retornar o marcador persistido. Elimine a inferência de negócio por `password_verify('123', ...)` de `LoginService` e `TermosService`.
- Em `LoginController`, guarde o marcador autoritativo na sessão e priorize `/aluno/trocar-senha` antes de termos e início. Preserve `session_regenerate_id(true)`, o token CSRF e a captura de `auth_version`.
- Estenda `SessionRevalidator` para recarregar a pendência junto com nível, estado e `auth_version` em toda requisição protegida. Se a conta tiver sido marcada por um reset concorrente, atualize a sessão e bloqueie a requisição corrente. Se a versão de autorização divergir, continue invalidando a sessão pelo comportamento existente.
- Não implemente fallback para usuários sem coluna/estado nem compatibilidade com sessões serializadas antigas: a migração de desenvolvimento marca as contas atuais e os testes criam sessões no contrato novo.

### T03 — Bloqueio central e fluxo de troca

- Acrescente a rota de página `/aluno/trocar-senha` em `config/routes/web.php` e uma tela dedicada em `resources/views/pages/aluno/` com JavaScript em `resources/js/pages/aluno/`. Somente aluno autenticado com pendência pode usar a página; demais perfis e alunos sem pendência seguem seus destinos atuais.
- Em `Kernel`, aplique o bloqueio depois da revalidação da sessão e antes da checagem de termos. Garanta que os métodos HTTP também sejam considerados: apenas POST válido em `/api/v1/senha`; não libere GET/PUT ou rotas de negócio por coincidirem com o mesmo prefixo.
- Mantenha logout disponível mesmo com pendência. A própria página deve carregar apenas assets e configuração necessários ao formulário. Não use o modal de `/aluno/inicio` como mecanismo de segurança; remova a detecção duplicada no home ou mantenha apenas a troca voluntária existente no perfil.
- Ajuste `SenhaController`, `SenhaService`, `SenhaRepository` e `MysqliSenhaRepository` para representar explicitamente a operação de primeira troca sem exigir a senha temporária no formulário. A autorização vem do estado persistido revalidado e o alvo continua sendo o ID da sessão; nunca aceite ID de usuário no payload.
- No sucesso, retorne o destino pós-troca conforme o estado de termos já revalidado. Em falha, mantenha a página de troca e o marcador pendente.
- Respeite `Url`/`Assets`, base path, escape HTML, `page-runtime.js`, ciclo de vida e layout desktop/mobile.

### T04 — Cobertura e atualização do onboarding de testes

Crie ou amplie testes na camada adequada; não dependa apenas de teste de componente visual.

- Unitários: `LoginServiceTest`, `TermosServiceTest` e `SenhaServiceTest` cobrem estado persistido, aluno pendente/não pendente, validação, senha temporária igual à nova e falha sem limpeza do marcador.
- Migração/integração: `MigrationsTest` comprova instalação/upgrade/repetição, coluna, marcação de alunos atuais e preservação de IDs e vínculos. Se houver rotina CLI para a base existente, teste que os hashes novos verificam `sesi-senai`, que `auth_version` é incrementado, que ela é idempotente e recusa ambiente fora de desenvolvimento. Testes HTTP validam produtores (importação, cadastro, reset), sessão, CSRF, bloqueio do Kernel e estado salvo.
- Para aluno pendente, teste login e redirecionamento; tentativa direta a início, termos, página de termos e APIs de negócio deve ser negada antes de atingir os controladores. Teste carregar assets, trocar com CSRF e obter o destino correto, tanto com termos pendentes quanto já aceitos.
- Teste senha errada/curta, confirmação diferente, CSRF inválido, exceção no repositório, troca concorrente com reset, logout/login, `auth_version` e revogação de outra sessão. As falhas devem preservar hash/estado/versão.
- Conta não pendente ainda usa o portal e precisa informar a senha atual para troca voluntária. Perfis 0/1/2 preservam seus fluxos.
- Atualize os cenários que usam senha inicial para autenticar com `sesi-senai` e concluir a troca antes de aceitar termos ou executar o restante. Procure especialmente `tests/Integration/AuthAndRbacTest.php`, `AlunosPortalTest.php`, `InscricaoModalidadesTest.php`, `FotoPerfilAndUsuariosTest.php`, `TurmasAndPdfImportTest.php` e specs em `tests/browser/` como `auth-rbac.spec.cjs`, `aluno-portal.spec.cjs` e `frontend-regression.spec.cjs`. Faça busca global por `senha_temporaria` e logins de alunos; não deixe specs falharem por onboarding incompleto.
- No navegador, valide o primeiro acesso na mesma aba, restrição de navegação direta, mensagens acessíveis e redirecionamento após sucesso. A autenticação e a troca ocorrem online; não armazene a senha inicial em IndexedDB ou na fila offline.

### T05 — Verificação final e STATUS

- Rode primeiro os testes focados vermelhos/verdes; depois `composer verify`, `npm run build`, `npm run check`, `npm test`, integração e browser pelo perfil oficial `all`. Execute MySQL 8.4 e MariaDB 10.11 disponíveis; use `-IncludeVisual` se a página/layout alterar contrato visual. Não declare alvo não executado como aprovado.
- Faça as validações de migração em instalação vazia, atualização sintética com os dados atuais e segunda execução sem duplicidade nos dois motores aplicáveis. Toda base deve ser exclusiva do runner.
- Confirme que logs e respostas de erro não expõem credenciais e que a informação da senha inicial aparece apenas nas telas de orientação apropriadas; as páginas administrativas continuam protegidas.
- Revise `git diff --check`, o diff completo, caminhos e referências. Atualize este STATUS e a documentação de fluxo se o contrato do usuário mudou. Não publique, faça push/merge nem execute migração/reset fora da base de desenvolvimento identificada; mantenha a base usada pelas suítes isolada.

## Arquivos principais a inspecionar durante a implementação

| Área | Arquivos atuais prováveis |
|---|---|
| Login e sessão | `src/Modules/Acesso/Application/LoginService.php`, `src/Modules/Acesso/Presentation/Http/LoginController.php`, `src/Shared/Http/SessionRevalidator.php`, `src/Shared/Http/Kernel.php` |
| Troca de senha | `src/Modules/Acesso/Application/SenhaService.php`, `src/Modules/Acesso/Domain/SenhaRepository.php`, `src/Modules/Acesso/Presentation/Http/SenhaController.php`, `src/Modules/Acesso/Infrastructure/MysqliSenhaRepository.php` |
| Credenciais emitidas | `src/Modules/Participantes/Infrastructure/PdfAlunoImporter.php`, `src/Modules/Participantes/Infrastructure/MysqliImportacaoTurmaRepository.php`, `src/Modules/Acesso/Application/UsuarioService.php`, `src/Modules/Acesso/Infrastructure/MysqliUsuarioManagementRepository.php`, `src/Modules/Acesso/Application/UsuarioAdministrativoService.php`, `src/Modules/Acesso/Infrastructure/MysqliUsuarioAdministrativoRepository.php` |
| Composição e páginas | `config/routes.php`, `config/routes/web.php`, `resources/views/pages/aluno/`, `resources/js/pages/aluno/`, `resources/views/pages/aluno/home.php`, `resources/js/pages/aluno/home.js` |
| Migração e testes | `database/migrations/`, `tests/Integration/MigrationsTest.php`, `tests/Integration/`, `tests/Unit/Modules/Acesso/`, `tests/browser/` |

## Critério de conclusão

Nenhum aluno marcado como pendente consegue usar termos, páginas do portal ou APIs de negócio antes de definir a própria senha; tentativa por URL ou chamada direta recebe bloqueio do servidor. Troca válida atualiza senha, limpa a pendência e mantém a sessão corrente sincronizada com `auth_version`; tentativas inválidas preservam todos os dados. Todos os emissores usam e marcam a senha inicial compartilhada `sesi-senai`; as contas não pendentes e demais perfis mantêm seus contratos, e as suítes executadas ficam registradas com resultados e limitações exatos.
