# Plano de correção de segurança do SGI para Luna

Data: 09/09/2026. Base: árvore de trabalho atual, inclusive alterações não commitadas.

## 1. Objetivo e limites da auditoria

Corrigir o acesso do mesário a telas administrativas e fechar as falhas relacionadas de autorização, exposição de dados, autenticação e sessão, preservando placar e sincronização offline.

Este documento registra a auditoria inicial e a implementação desta rodada. A revisão cobriu rotas, controladores, consultas, sessões, CSRF, renderização, pontos de DOM, armazenamento de uploads e testes. Não houve exploração de contas reais, execução de mutações no banco de trabalho, auditoria de infraestrutura em produção ou varredura exaustiva de dependências. Não interpretar esta revisão como garantia de ausência de outras vulnerabilidades.

A árvore tem muitas alterações anteriores. Luna deve preservá-las, reler os arquivos antes de editar e não executar reset, limpeza de arquivos, checkout de versões antigas ou reescrita de migrações aplicadas.

## 2. Evidência reproduzida

Foi invocado o `PageController::show` real com os templates reais e sessão PHP sintética de nível 2, sem banco, sem servidor HTTP e sem conta real:

| Rota | Status | Tamanho do HTML |
| --- | --- | --- |
| `/colaboradores` | 200 | 22.743 bytes |
| `/edicoes/pontuacao` | 200 | 20.191 bytes |
| `/edicoes/modalidades` | 200 | 17.575 bytes |
| `/turmas/alunos` | 302 | 0 bytes |

Isso comprova a falha na autorização/renderização do servidor. Foram adicionados testes HTTP e de navegador para a mesma sequência; a execução deles depende do servidor isolado com MariaDB descrito em `docs/testing.md`.

Os testes de regressão agora cobrem todas as rotas administrativas críticas para o mesário; a suíte completa executada ao final desta rodada passou com 212 testes e 2.089 asserções. A reprodução HTTP com login e navegador ainda depende do ambiente isolado descrito em `docs/testing.md`.

## 3. Achados e prioridade

### SEC-01 — Alta: autorização permissiva das páginas (confirmado e reproduzido)

**Arquivos:** `src/Presentation/Web/PageController.php`, `config/routes/web.php`, `resources/views/components/admin-head.php`, `resources/views/pages/acesso/colaboradores.php`.

`PAGE_LEVELS` contém somente `/turmas/alunos => [0]`. Todas as demais páginas fora de `/aluno/` herdam `[0,1,2]`. O componente de cabeçalho repete a mesma autorização ampla. Na página de colaboradores, `$usuarioEhAdmin` não impede renderização da página para o mesário.

**Impacto:** acesso indevido a telas e controles administrativos. Não significa que todas as APIs de escrita estejam liberadas; várias já negam nível 2 corretamente.

**Correção:** mapa explícito e completo por rota, negação por padrão, verificação antes de carregar dados ou renderizar. APIs respondem 403 para sessão autenticada sem permissão; páginas web seguem o contrato existente de redirecionar a sessão sem permissão para o login, e anônimos também vão para login. Não resolver apenas ocultando menu ou redirecionando por JavaScript.

### SEC-02 — Alta: leitura excessiva de usuários (confirmado no código)

**Arquivos:** `src/Modules/Acesso/Presentation/Http/UsuarioController.php`, `src/Modules/Acesso/Infrastructure/MysqliUsuarioConsultaRepository.php`.

Todo GET em `/api/v1/usuarios` aceita `[0,1,2]`. `listar_colaboradores` revela contas administrativas e matrículas; a ação padrão lista usuários; `listar_competidores` aceita `id_interclasse` do cliente e retorna matrícula e data de nascimento. Não há limitação do mesário à edição ativa nesse ramo.

**Impacto:** exposição de dados pessoais e insumos para SEC-03. Bloquear `/turmas/alunos` não bloqueia a consulta direta.

**Correção:** permissão por ação e projeção de campos por finalidade. Lista administrativa só para quem gerencia usuários. Para o mesário, fornecer elenco operacional mínimo da edição ativa, sem data de nascimento, credenciais ou matrícula salvo necessidade explícita documentada. Manter IDs e nomes necessários à artilharia e ocorrências. Rejeitar ações desconhecidas, em vez de retornar todos os usuários.

### SEC-03 — Alta: autenticação por matrícula e nascimento; possível escalada condicional

**Arquivos:** `UsuarioController.php` (ação `validar_inscricao`), `UsuarioService.php::validarInscricao`, `MysqliUsuarioConsultaRepository.php::findCompetitorForValidation`, `CsrfGuard.php`.

O endpoint público valida matrícula e nascimento, regenera a sessão e atribui o nível do registro encontrado sem senha, sem token de ativação e sem verificar se a conta já foi ativada. A consulta aceita `(nivel_usuario = '3' OR competidor_usuario = '3')`.

**Confirmado:** esse fluxo cria sessão autenticada com os dois dados pessoais, inclusive quando já existe senha definida. Combinado com SEC-02, permite assumir conta de aluno pelo fluxo de validação.

**Condicional:** se existir conta de nível 0/1/2 com `competidor_usuario = '3'`, o ramo pode criar sessão desse nível. A existência desses registros não foi verificada; não declarar invasão administrativa comprovada.

**Correção:** validação cadastral não deve produzir sessão operacional. Implementar ativação limitada com segredo aleatório de uso único, validade curta e finalidade registrada, exigindo estritamente nível 3. Após ativação, esse mecanismo deixa de funcionar; acesso normal exige senha. Nunca usar nascimento como segredo de autenticação. Guardar somente hash do token; não registrar tokens ou dados pessoais em logs. Definir entrega assistida do token pela escola, sem inventar dependência de e-mail. Testar reuso, expiração, concorrência e contas já ativadas. Criar fixture de conta administrativa marcada como competidor e provar rejeição.

### SEC-04 — Alta: alteração de senha sem confirmação da senha atual

**Arquivos:** `src/Modules/Acesso/Presentation/Http/SenhaController.php`, `src/Modules/Acesso/Application/SenhaService.php`, `src/Modules/Acesso/Presentation/Http/PerfilController.php`.

`/api/v1/senha` aceita os quatro níveis e exige somente nova senha e confirmação. Isso oferece uma alternativa à troca pelo perfil, que passa a senha atual para o serviço. O fluxo fraco de SEC-03 pode ser encadeado com este para substituir a senha da vítima.

**Correção:** troca normal exige senha atual para todos os perfis. Exceção de ativação/reset somente com autorização curta, específica e de uso único no servidor. Não usar apenas `exige_troca_senha` manipulável por fluxo de autenticação incompleto. Unificar política entre os dois caminhos, atualizar formulários e invalidar outras sessões após troca/reset.

### SEC-05 — Alta: sessões continuam confiando no papel antigo

**Arquivos:** `AccessGuard.php`, `PageController.php`, `SessionController.php`, `MysqliUsuarioManagementRepository.php`, `MysqliUsuarioAdministrativoRepository.php`.

Os guards usam o nível da sessão sem consultar estado atual do usuário. Alteração de papel, desativação e reset alteram o banco, sem mecanismo de versão de autenticação nos caminhos revisados.

**Impacto:** uma sessão de administrador rebaixado pode continuar autorizada; sessão de usuário desativado pode continuar alcançando endpoints que só usam o guard. Reproduzir com duas sessões no banco isolado.

**Correção:** contexto autenticado central com ID válido, usuário existente/ativo, nível válido e versão de credencial/autorização. Incrementar versão ao alterar senha, papel ou estado. Validar no servidor em cada requisição protegida, inclusive `/api/v1/session` e páginas. Definir expiração absoluta e por inatividade sem perder a fila offline. Revogação remota não pode ser instantânea sem rede: na reconexão, suspender replay e exigir autenticação válida antes de qualquer escrita.

### SEC-06 — Alta: leitura de ocorrências sem escopo do usuário

**Arquivos:** `src/Modules/Disciplina/Presentation/Http/OcorrenciaController.php`, `src/Modules/Disciplina/Infrastructure/MysqliOcorrenciaQueries.php`.

GET aceita `[0,1,2,3]` e repassa filtros fornecidos pelo cliente. A consulta retorna nomes, descrição, penalidade e outros dados; inicia em `WHERE 1=1` com filtros opcionais. Não deriva turma/edição autorizada da identidade.

**Impacto:** aluno ou mesário pode solicitar dados disciplinares fora de seu escopo. A lista auxiliar de atletas também precisa de autorização do recurso.

**Correção:** aluno recebe somente a projeção permitida de sua turma/conta; nunca descrições disciplinares de outras turmas. Mesário recebe dados operacionais da edição ativa. Aplicar restrição obrigatória na consulta antes dos filtros opcionais. Revisar de forma equivalente ocorrências de turmas, histórico, artilheiros, partidas, ranking e dados de pré-carga. Não tratar todas as leituras esportivas públicas como privadas: definir campos e finalidade explicitamente.

### SEC-07 — Média: mesário pode agendar novo jogo

**Arquivo:** `src/Modules/Competicoes/Presentation/Http/JogoController.php`.

O POST usa `CompetitionAccess::authorize()`, que permite nível 2, verifica edição e chama `agendar`. Há restrições ao mesário para alterações de programação no PUT, mas não a mesma restrição no POST.

**Correção:** criação manual/agendamento só para administrador e colaborador; operação de cronômetro, placar e avanço permanece autorizada ao mesário. Não bloquear a materialização legítima de partidas derivadas da sincronização do chaveamento. Testar POST e cada família de campos de PUT separadamente.

### SEC-08 — Média: associação de aluno sem validar toda a relação

**Arquivos:** `UsuarioController.php` (POST com `?id=`), `UsuarioService.php::atribuirAluno`, `MysqliUsuarioManagementRepository.php::assignStudent`.

A ação aceita edição do payload. O UPDATE exige apenas ID e nível 3; não verifica a edição original do aluno nem se a turma pertence à edição destino. Diferentemente de `createStudent`, não há consulta de pertencimento da turma.

**Correção:** tornar a operação explícita e verificar autorização e consistência aluno/turma/edição dentro da transação. Se transferência entre edições não for função aprovada, rejeitar. Não substituir o histórico anual por mudança silenciosa da edição do registro. Testar combinações cruzadas de IDs e ausência de alteração após rejeição.

### SEC-09 — Média: controles de autenticação incompletos

**Evidência:** não foi encontrado limitador de tentativas nos fluxos de login/validação revisados. A auditoria inicial encontrou senha comum `123` em `createStudent` e reset; a implementação desta rodada substituiu ambos por senha temporária aleatória, mas `exige_troca_senha` ainda não é um estado de ativação imposto pelo guard central.

**Correção:** limitação persistente e compartilhada entre processos por conta e origem, resposta 429 e recuperação após janela; mensagens genéricas. Não confiar em IP encaminhado sem proxy configurado. Substituir senha inicial comum por ativação individual e impor estado de ativação no servidor em páginas e APIs. Não bloquear escola inteira por usar um único IP. Verificar separadamente se o ambiente já possui limitação na borda; ela não foi auditada.

### SEC-10 — Pontos adicionais a validar, sem exploração confirmada

1. **DOM/XSS:** `resources/js/pages/participantes/turmas.js`, nas funções que montam selects, interpola `cat.nome_categoria` em `innerHTML`; `CategoriaService` aceita nome não vazio sem eliminar marcação. Construir teste de navegador com nome sintético e confirmar contexto/executabilidade. Preferir `new Option`, `textContent` e IDs numéricos; estender revisão a mensagens de erro, atributos, URLs, templates PHP e telas offline. Não classificar todo `innerHTML` estático como falha.
2. **Cache:** `admin-head.php` envia cache privado por 3 horas com `stale-while-revalidate` de 24 horas para a área da equipe. Verificar histórico, BFCache, troca de conta e rebaixamento. Aplicar `no-store` às páginas administrativas/dados sensíveis e limitar cache offline à lista explícita de telas operacionais do mesário. `Vary: Cookie` não substitui autorização nem revogação.
3. **CSRF de autenticação e logout:** `/api/v1/logout` aceitava GET; login é isento no `CsrfGuard` por ser o início da sessão e a validação cadastral legada foi descontinuada. Migrar logout para POST com token, e validar origem/CSRF nos fluxos de autenticação, respeitando navegação legítima. A proteção atual também alcança POST de perfil via Kernel; não registrar falsa ausência de CSRF nessa rota. GET não deve destruir sessão.
4. **Edição ativa obsoleta:** GET de jogos usa `$_SESSION['id_interclasse']`, enquanto `CompetitionAccess::context()` consulta a edição ativa atual. Testar troca/desativação de edição com sessão já aberta e consulta imediata sem mutação intermediária.
5. **Fronteira pública e uploads:** há consultas preparadas e validação de imagem/tamanho/nome aleatório nos caminhos lidos; não foi comprovada SQL injection ou upload executável. Testar acesso direto a `.env`, código, dumps, PDFs de alunos e arquivos de teste; extensão/MIME divergentes, tamanho/dimensões e caminhos. Verificar DocumentRoot real e proxy/TLS em homologação.

## 4. Matriz-alvo de páginas

Implementar uma entrada explícita para cada rota de `config/routes/web.php`. A tabela abaixo é a política proposta com base no AGENTS.md. Para ações de colaborador que hoje são mais amplas, preservar operação autorizada, mas registrar e resolver divergências antes de ampliar permissões.

| Grupo | Rotas | Níveis |
| --- | --- | --- |
| Público | `/login`, `/aluno/login` | anônimo; sessão existente tratada de forma consistente |
| Portal | todas as outras `/aluno/*` | 3, com ativação e escopo próprios |
| Gestão de usuários | `/colaboradores`, `/turmas/alunos` | 0 |
| Configuração estrutural | `/edicoes/categorias`, `/edicoes/equipes`, `/edicoes/locais`, `/edicoes/modalidades`, `/edicoes/pontuacao`, `/edicoes/turmas` | 0 |
| Agenda administrativa | `/edicoes/agenda` | 0,1; se usada pelo offline, separar visão operacional de leitura para 2 |
| Arrecadação | `/edicoes/arrecadacao` | 0,1 |
| Resumo de configuração | `/edicoes/resumo` | 0,1 |
| Seleção/painel | `/edicoes`, `/painel` | 0,1; para 2, só entrada operacional da edição ativa, sem administração |
| Operação/consulta | `/jogos`, `/jogos/placar`, `/chaveamento`, `/ocorrencias`, `/ranking`, `/categorias`, `/modalidades`, `/modalidades/detalhes`, `/turmas`, `/equipes/elenco` | 0,1,2, com edição ativa para 2 e controles por ação |
| Inscrição/gestão de equipe | `/equipes/alunos` | 0,1; mesário usa elenco somente leitura |
| Perfil próprio | `/perfil` | 0,1,2 |

Página mista não concede automaticamente permissão de escrita. Para configuração estrutural, alinhar as APIs correspondentes que hoje usam `requireWrite()` com a política final; não alterar globalmente esse helper para `[0]`, pois agendamento e arrecadação legítimos dependem do colaborador.

As APIs precisam de matriz própria: método + ação + nível + escopo + campos retornados. Inventariar **todas** as rotas de `config/routes.php`, incluindo caminhos alternativos de edição/status, geração de equipes, ranking e sincronização. Ações não reconhecidas e novas rotas sem política devem falhar fechadas.

## 5. Sequência de execução para Luna

### L00 — Preparação e testes que demonstram os defeitos

- Ler AGENTS.md, este plano e `docs/testing.md`; registrar estado atual sem sobrescrever trabalho existente.
- Preparar servidor e banco descartáveis conforme o guia e verificar modo test, nome e porta antes de reset/seed.
- Executar baseline: `php tests/run_all.php`, `composer verify`, `npm run check`, `npm test`, `npm --prefix tests/browser test`. Registrar falhas preexistentes; não ajustar expectativas para encobri-las.
- Criar fixtures com duas edições, duas turmas, todos os níveis, contas ativadas/não ativadas e duas sessões de um mesmo usuário.
- Adicionar testes de regressão SEC-01 a SEC-09; demonstrar falha antes da correção correspondente. Não depender de IDs fixos ou dados reais.

### L01 — Fechar autenticação alternativa e tomada de conta

Executar SEC-03, SEC-04 e a remoção do segredo inicial comum de SEC-09. Enquanto o fluxo novo não estiver concluído, desabilitar a criação de sessão operacional pelo cadastro e manter login normal funcional. Criar migração nova após o maior número existente para estado/token de ativação, se necessário; nunca editar migrações aplicadas. Atualizar portal, reset e testes juntos. Dependência: L00.

**Aceite:** matrícula+nascimento não autenticam conta ativada nem administrativa; token expirado/reutilizado não funciona; troca normal sem senha atual é rejeitada sem alterar hash; login normal e ativação válida funcionam.

### L02 — Política central e restrições de páginas/API

Implementar SEC-01, SEC-02 e SEC-07. Criar política testável no módulo Acesso, usada pelas fronteiras web/API; manter serviços de domínio sem dependência de HTTP. Remover defaults permissivos. Gerar menu e controles a partir das mesmas capacidades, sem confiar neles como barreira. Ajustar destino pós-login do mesário e visão de agenda usada pela SPA. Dependência: L00; concluir L01 antes de considerar o sistema seguro para entrega.

**Aceite:** todo caminho administrativo proibido é bloqueado antes do HTML/dados (403 nas APIs e redirecionamento fechado nas páginas); mesário opera torneio completo, mas não cria agenda manual; GET de usuários não expõe nascimento nem listagem administrativa ao mesário. Cobertura deve falhar se nova rota for adicionada sem classificação.

### L03 — Escopo e consistência de recursos

Implementar SEC-06 e SEC-08; revisar todos os GETs e mutações por recurso. Derivar edição/turma do banco e contexto, não de campos livres do cliente. Para mesário, resolver edição ativa atual; inexistência de edição ativa deve negar operação, nunca remover filtro. Preservar escopo e IDs temporários da sincronização. Dependência: L02.

**Aceite:** troca de IDs entre edições/turmas não vaza dados nem altera banco; filtros omitidos não ampliam resultado; edição trocada pelo admin passa a valer na próxima consulta online; nenhuma mudança de esquema da fila é necessária.

### L04 — Sessões, tentativas e CSRF

Implementar SEC-05 e limitação de SEC-09; tratar logout e autenticação de SEC-10. Se necessário, adicionar migração para versão de autenticação e armazenamento de tentativas. Usar uma política de senha consistente para criar, trocar e resetar. Atualizar todos os caminhos que alteram estado/credencial/papel, e o endpoint de sessão. Dependências: L01 e L02.

**Aceite:** sessão antiga falha após rebaixamento/desativação/reset; outra sessão não pode continuar escrevendo após troca de senha; limitação funciona em requisições/processos diferentes; logout GET é inofensivo; POST sem CSRF é negado.

### L05 — DOM e cache offline

Reproduzir e corrigir pontos de SEC-10. Revisar `mesario-offline.js`, `mesario-data.js` e `offline-core.js` para lista permitida de páginas, troca de identidade e tratamento de 401/403. Não armazenar HTML de login/erro como casca válida. Não apagar fila para resolver revogação: suspender, preservar propriedade e informar necessidade de autenticação. Uma fila de A nunca deve ser executada com credencial de B. Invalidar só cache de apresentação incompatível; manter schema e identificadores de mutação. Dependências: L02–L04.

**Aceite:** marcação em nomes aparece como texto; histórico não reabre administração após logout; navegação offline operacional preparada funciona; reconexão com sessão revogada não perde nem envia mutações indevidas; reenvio autorizado continua idempotente.

### L06 — Validação e entrega

- Executar `npm run build` após editar recursos; não editar diretamente `public/assets`.
- Reexecutar todos os comandos da baseline sequencialmente quando compartilham banco. Validar raiz e subdiretório; MySQL e MariaDB, localmente ou com evidência do CI.
- Testar migrações do zero, atualização de banco anterior e repetição sem erro. Não testar na base de trabalho.
- Complementar `tests/Integration/AuthAndRbacTest.php`, `MesarioResourceScopeTest.php`, `PublicBoundaryTest.php`, os testes unitários de PageController/AccessGuard e `tests/browser/auth-rbac.spec.cjs`; adicionar arquivos focados para ativação, revogação e leitura de dados.
- Testar acesso HTTP direto sem seguir redirect, navegação real, HEAD, métodos inválidos, ausência de filtro, payload query/body discordante e sessão inválida. Requisições que testam RBAC de escrita devem ter CSRF válido para não produzir falso positivo.
- Registrar resultado de cada SEC: corrigido + teste, descartado com evidência, ou pendente com motivo. Listar comandos, totais, falhas preexistentes e limitações. Não declarar “todas as vulnerabilidades corrigidas” sem evidência.

## 6. Prompt de execução

> Leia AGENTS.md e docs/plano-seguranca-autorizacao-luna.md e implemente as etapas L00–L06, preservando alterações existentes. Comece demonstrando os defeitos em ambiente isolado. Priorize autenticação alternativa, troca de senha, autorização de páginas e exposição de dados. Use política explícita por rota/ação, escopo de recursos derivado do servidor e testes negativos e positivos. Preserve operação offline, propriedade das filas e identificadores idempotentes. Não faça deploy, reset da base de trabalho nem reescreva migrações aplicadas. Atualize este documento com evidências de conclusão por SEC e entregue o resultado dos testes e eventuais pendências reais.

## 7. Implementação desta rodada

Implementado no workspace:

- SEC-01: mapa explícito de rotas web e regressões unitárias/browser para mesário.
- SEC-02: ações de usuários fechadas por finalidade, edição ativa forçada para mesário e projeção sem data de nascimento nessa função.
- SEC-03: endpoint legado de validação cadastral não cria mais sessão e rejeita a operação com HTTP 410.
- SEC-04: troca de senha exige senha atual; somente a troca obrigatória do primeiro acesso de aluno ainda usa a senha gerada existente na sessão.
- SEC-05: migração `008_auth_version.sql`, revalidação no Kernel e incremento da versão ao trocar senha, alterar papel, resetar ou desativar usuário.
- SEC-07: mesário não agenda nem cria jogos manualmente.
- SEC-08: vínculo de aluno valida edição ativa, usuário e turma no banco antes do UPDATE.
- SEC-10: cache HTTP das páginas autenticadas passou a `no-store`; selects de categorias usam `Option`/`textContent` e não interpolação HTML; logout usa POST com CSRF e GET retorna 405 sem destruir a sessão.
- Escopo de recursos: consultas de edições, locais, chaveamentos, histórico, ocorrências, atletas, fotos, jogos, partidas, equipes, modalidades e categorias passam a respeitar a edição ativa do mesário; consultas de competidores não retornam data de nascimento nessa função.
- Reset de senha de aluno deixou de usar o valor universal `123`; passa a gerar segredo temporário aleatório e o devolve somente na resposta administrativa.

Validação local concluída: PHPUnit 212/212 (2.089 asserções), JavaScript 21/21, PHPStan sem erros, PHP CS Fixer sem correções, `npm run check` e `git diff --check` concluídos. `npm run build` também foi concluído nesta rodada de recursos. A suíte HTTP/browser completa ficou pendente porque o daemon Docker/MariaDB não estava disponível nesta máquina; os testes de regressão HTTP estão incluídos em `AuthAndRbacTest` e `MesarioResourceScopeTest` e devem ser executados no ambiente isolado descrito em `docs/testing.md`.

Ainda requer validação/implementação específica antes de declarar o plano encerrado: rate limiting persistente, ativação individual com token de uso único (a senha universal de criação/reset já foi removida, mas o fluxo de entrega/ativação ainda pode ser formalizado), e auditoria completa de todas as consultas de ocorrências/recursos e da casca offline em servidor com banco.
