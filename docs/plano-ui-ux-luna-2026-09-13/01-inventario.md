# Inventário completo de UI/UX

Checkout de referência: `914ff13c`. Os 101 arquivos de `resources/` estão relacionados abaixo. A presença de uma etapa indica correção específica ou verificação de regressão compartilhada; não significa que todo arquivo precise mudar. Consulte os achados U01–U24 no [relatório](README.md) e os passos E00–E11 no [plano](02-plano-luna.md).

Templates e CSS/SCSS foram inspecionados; a leitura de JavaScript priorizou HTML gerado, eventos, navegação e estados. Imagens foram inventariadas por uso/tamanho, com inspeção visual nas telas de amostra. Não houve auditoria completa das regras de negócio de todos os scripts.

## Telas e seus scripts

34 templates de página e 33 scripts associados. `aluno/login.php` é um redirect. Todas as outras páginas participaram das cinco renderizações estáticas.

| Template | JavaScript | Foco da implementação/verificação |
| --- | --- | --- |
| [resources/views/pages/acesso/colaboradores.php](../../resources/views/pages/acesso/colaboradores.php) | [resources/js/pages/acesso/colaboradores.js](../../resources/js/pages/acesso/colaboradores.js) | E02/E03/E05/E09 — estatísticas em 320px; labels e modais; senha inicial e envio; busca/erro. |
| [resources/views/pages/acesso/login.php](../../resources/views/pages/acesso/login.php) | [resources/js/pages/acesso/login.js](../../resources/js/pages/acesso/login.js) | E04/E10 — tamanho do formulário em retrato, ajuda de senha acionável, bloqueio de reenvio, rótulos e assets. |
| [resources/views/pages/acesso/perfil.php](../../resources/views/pages/acesso/perfil.php) | [resources/js/pages/acesso/perfil.js](../../resources/js/pages/acesso/perfil.js) | E03/E04 — rótulo de matrícula, status estático, senha/teclado, modal e feedback. |
| [resources/views/pages/aluno/home.php](../../resources/views/pages/aluno/home.php) | [resources/js/pages/aluno/home.js](../../resources/js/pages/aluno/home.js) | E01/E02/E06 — navegação, hierarquia, agenda externa e nomes acessíveis; preservar resumo e contexto. |
| [resources/views/pages/aluno/jogos.php](../../resources/views/pages/aluno/jogos.php) | [resources/js/pages/aluno/jogos.js](../../resources/js/pages/aluno/jogos.js) | E03/E06/E09 — ação de detalhes por teclado, filtros, carregamento de membros e nova tentativa após falha. |
| [resources/views/pages/aluno/login.php](../../resources/views/pages/aluno/login.php) | — | Redirecionamento legado para login canônico; preservar 302/no-store. Não é página visual nem possui JS dedicado. |
| [resources/views/pages/aluno/modalidade.php](../../resources/views/pages/aluno/modalidade.php) | [resources/js/pages/aluno/modalidade.js](../../resources/js/pages/aluno/modalidade.js) | E01/E05/E09 — offset desktop, estado selecionado acessível, limites e progresso de inscrição, erros e reenvio. |
| [resources/views/pages/aluno/perfil.php](../../resources/views/pages/aluno/perfil.php) | [resources/js/pages/aluno/perfil.js](../../resources/js/pages/aluno/perfil.js) | E01/E03/E04 — offset desktop, matrícula, senha/teclado, modal e feedback. |
| [resources/views/pages/aluno/ranking.php](../../resources/views/pages/aluno/ranking.php) | [resources/js/pages/aluno/ranking.js](../../resources/js/pages/aluno/ranking.js) | E01/E03/E09/E10 — headings, filtro/nome, vazio versus erro, contraste e impressão se disponível. |
| [resources/views/pages/aluno/termos.php](../../resources/views/pages/aluno/termos.php) | [resources/js/pages/aluno/termos.js](../../resources/js/pages/aluno/termos.js) | E03/E09 — orientação, checkbox associado, confirmação/erro; preservar conteúdo e regras dos termos. |
| [resources/views/pages/aluno/trocar-senha.php](../../resources/views/pages/aluno/trocar-senha.php) | [resources/js/pages/aluno/trocar-senha.js](../../resources/js/pages/aluno/trocar-senha.js) | E03/E04/E09 — validação anunciada, foco e controles; preservar primeiro acesso e regras existentes. |
| [resources/views/pages/competicoes/chaveamento.php](../../resources/views/pages/competicoes/chaveamento.php) | [resources/js/pages/competicoes/chaveamento.js](../../resources/js/pages/competicoes/chaveamento.js) | E02/E06/E07 — seletor KVS, teclado/foco, área de chaveamento rolável, toque e reentrada offline. |
| [resources/views/pages/competicoes/elenco-equipe.php](../../resources/views/pages/competicoes/elenco-equipe.php) | [resources/js/pages/competicoes/elenco-equipe.js](../../resources/js/pages/competicoes/elenco-equipe.js) | E03/E09 — nomes/semântica da lista ou tabela, retorno contextual e estados de carregamento. |
| [resources/views/pages/competicoes/equipe-alunos.php](../../resources/views/pages/competicoes/equipe-alunos.php) | [resources/js/pages/competicoes/equipe-alunos.js](../../resources/js/pages/competicoes/equipe-alunos.js) | E03/E05/E09 — nome do botão salvar, seleção identificável e resultado do envio; preservar vínculos. |
| [resources/views/pages/competicoes/jogos.php](../../resources/views/pages/competicoes/jogos.php) | [resources/js/pages/competicoes/jogos.js](../../resources/js/pages/competicoes/jogos.js) | E02/E03/E06/E09 — filtros, datas/ações acessíveis, leitura em retrato; não mascarar falha como lista vazia. |
| [resources/views/pages/competicoes/modalidade-detalhes.php](../../resources/views/pages/competicoes/modalidade-detalhes.php) | [resources/js/pages/competicoes/modalidade-detalhes.js](../../resources/js/pages/competicoes/modalidade-detalhes.js) | E02/E03/E09 — cards, headings, retornos e contexto de edição; nomes longos e erros. |
| [resources/views/pages/competicoes/modalidades.php](../../resources/views/pages/competicoes/modalidades.php) | [resources/js/pages/competicoes/modalidades.js](../../resources/js/pages/competicoes/modalidades.js) | E02/E03/E09 — cards/ações, busca e estado vazio/erro; preservar navegação conforme perfil. |
| [resources/views/pages/competicoes/placar.php](../../resources/views/pages/competicoes/placar.php) | [resources/js/pages/competicoes/placar.js](../../resources/js/pages/competicoes/placar.js) | E03/E07 — +/- com equipe no nome, radios acessíveis, modais, foco, tempo e confirmação offline. |
| [resources/views/pages/disciplina/ocorrencias.php](../../resources/views/pages/disciplina/ocorrencias.php) | [resources/js/pages/disciplina/ocorrencias.js](../../resources/js/pages/disciplina/ocorrencias.js) | E03/E09 — campos/erros, modais, ordem de inclusão do footer e estados; preservar magnitude das penalidades. |
| [resources/views/pages/eventos/categorias.php](../../resources/views/pages/eventos/categorias.php) | [resources/js/pages/eventos/categorias.js](../../resources/js/pages/eventos/categorias.js) | E02/E03/E05/E09 — cartões/ações e formulário de turma/importação, teclado e erro confiável. |
| [resources/views/pages/eventos/configurar-agenda.php](../../resources/views/pages/eventos/configurar-agenda.php) | [resources/js/pages/eventos/configurar-agenda.js](../../resources/js/pages/eventos/configurar-agenda.js) | E02/E03/E06 — calendário por teclado, filtros com labels, data selecionada, modal e retrato. |
| [resources/views/pages/eventos/configurar-arrecadacao.php](../../resources/views/pages/eventos/configurar-arrecadacao.php) | [resources/js/pages/eventos/configurar-arrecadacao.js](../../resources/js/pages/eventos/configurar-arrecadacao.js) | E03/E09 — modal antes do footer, labels/unidades, erro em linguagem do usuário e valores mantidos. |
| [resources/views/pages/eventos/configurar-categorias.php](../../resources/views/pages/eventos/configurar-categorias.php) | [resources/js/pages/eventos/configurar-categorias.js](../../resources/js/pages/eventos/configurar-categorias.js) | E02/E03/E05 — seleção separada da navegação, ação fixa, upload e modal acessíveis. |
| [resources/views/pages/eventos/configurar-equipes.php](../../resources/views/pages/eventos/configurar-equipes.php) | [resources/js/pages/eventos/configurar-equipes.js](../../resources/js/pages/eventos/configurar-equipes.js) | E03/E05/E09 — nome do botão criar, seleção e labels; limite/capacidade e erros preservados. |
| [resources/views/pages/eventos/configurar-locais.php](../../resources/views/pages/eventos/configurar-locais.php) | [resources/js/pages/eventos/configurar-locais.js](../../resources/js/pages/eventos/configurar-locais.js) | E03/E05/E09 — label, modal, salvar/erro e estados da lista; preservar CRUD e autorização. |
| [resources/views/pages/eventos/configurar-modalidades.php](../../resources/views/pages/eventos/configurar-modalidades.php) | [resources/js/pages/eventos/configurar-modalidades.js](../../resources/js/pages/eventos/configurar-modalidades.js) | E02/E03/E05 — overflow da toolbar em 320/390px, formulário/modal e ações sem recorte. |
| [resources/views/pages/eventos/configurar-pontuacao.php](../../resources/views/pages/eventos/configurar-pontuacao.php) | [resources/js/pages/eventos/configurar-pontuacao.js](../../resources/js/pages/eventos/configurar-pontuacao.js) | E03/E08/E09 — labels/unidades, edição selecionada, mudanças não salvas e resultado do envio. |
| [resources/views/pages/eventos/configurar-resumo.php](../../resources/views/pages/eventos/configurar-resumo.php) | [resources/js/pages/eventos/configurar-resumo.js](../../resources/js/pages/eventos/configurar-resumo.js) | E02/E03/E08/E09 — ação mobile inexistente, etapa/edição, ativação por um POST confirmado; erros sem navegação. |
| [resources/views/pages/eventos/configurar-turmas.php](../../resources/views/pages/eventos/configurar-turmas.php) | [resources/js/pages/eventos/configurar-turmas.js](../../resources/js/pages/eventos/configurar-turmas.js) | E02/E03/E05/E09 — categoria selecionável no compacto, criação/modal compartilhado e mensagens de erro. |
| [resources/views/pages/eventos/dashboard.php](../../resources/views/pages/eventos/dashboard.php) | [resources/js/pages/eventos/dashboard.js](../../resources/js/pages/eventos/dashboard.js) | E02/E03/E09 — overflow dos cards/grade em retrato, nomes de ações e estados dos indicadores. |
| [resources/views/pages/eventos/lista.php](../../resources/views/pages/eventos/lista.php) | [resources/js/pages/eventos/lista.js](../../resources/js/pages/eventos/lista.js) | E02/E03/E08/E09 — cards, modais, criação/estado da edição e resultado das ações. |
| [resources/views/pages/participantes/turma-alunos.php](../../resources/views/pages/participantes/turma-alunos.php) | [resources/js/pages/participantes/turma-alunos.js](../../resources/js/pages/participantes/turma-alunos.js) | E03/E05/E09 — upload por teclado, resposta JSON validada, progresso ARIA, dados e ações de aluno. |
| [resources/views/pages/participantes/turmas.php](../../resources/views/pages/participantes/turmas.php) | [resources/js/pages/participantes/turmas.js](../../resources/js/pages/participantes/turmas.js) | E02/E03/E09 — busca/rótulos e cards, edição no retorno, vazio e erro distintos. |
| [resources/views/pages/resultados/ranking.php](../../resources/views/pages/resultados/ranking.php) | [resources/js/pages/resultados/ranking.js](../../resources/js/pages/resultados/ranking.js) | E02/E03/E09/E10 — filtros, semântica de tabela/card, estados, publicação e impressão sem duplicação. |

## Componentes comuns — 7 arquivos

| Arquivo | Foco da implementação/verificação |
| --- | --- |
| [resources/views/components/admin-head.php](../../resources/views/components/admin-head.php) | E01/E10 — ordem dos bundles, título e assets locais; preservar scripts compartilhados. |
| [resources/views/components/admin-header.php](../../resources/views/components/admin-header.php) | E01/E10 — título reescrito como SGI, hierarquia do banner e alt. |
| [resources/views/components/admin-nav.php](../../resources/views/components/admin-nav.php) | E01/E04 — nome/estado/destino dos links, logout, avatar e foco; preservar offcanvas. |
| [resources/views/components/aluno-head.php](../../resources/views/components/aluno-head.php) | E01/E10 — aluno não carrega admin.css; estilos comuns precisam estar no shared. |
| [resources/views/components/aluno-nav.php](../../resources/views/components/aluno-nav.php) | E01/E04 — fundo ausente na sidebar, avatar e indicação ativa; preservar offcanvas. |
| [resources/views/components/footer.php](../../resources/views/components/footer.php) | E03 — fecha body/html; mover conteúdo interativo anterior ao include nos consumidores. |
| [resources/views/components/page-title.php](../../resources/views/components/page-title.php) | E01 — aceitar título específico seguro com fallback. |

## Estilos — 9 arquivos

| Arquivo | Foco da implementação/verificação |
| --- | --- |
| [resources/css/source/admin.css](../../resources/css/source/admin.css) | E01/E02/E06/E07/E10 — avatar comum indevidamente restrito, grade compacta, KVS, placar e banner. |
| [resources/css/source/aluno-home.css](../../resources/css/source/aluno-home.css) | E02/E10 — cards da home, quebras de texto e composição em retrato. |
| [resources/css/source/aluno-pages.css](../../resources/css/source/aluno-pages.css) | E02/E05/E10 — inscrições e páginas do portal, offsets legados e ações. |
| [resources/css/source/aluno-shared.css](../../resources/css/source/aluno-shared.css) | E01/E02/E07/E10 — navegação, espaçamento e convivência de barras fixas. |
| [resources/css/source/login.css](../../resources/css/source/login.css) | E04/E10 — retrato com input 32px/11,2px, banner grande e consistência entre orientações. |
| [resources/css/source/utilities.css](../../resources/css/source/utilities.css) | E02/E10 — top:85%, valores de posição fixos; substituir no contexto e retirar após conferir consumidores. |
| [resources/scss/_theme.scss](../../resources/scss/_theme.scss) | E10 — tokens institucionais; preservar e medir contraste dos usos, sem redesenhar a marca. |
| [resources/scss/bootstrap-theme.scss](../../resources/scss/bootstrap-theme.scss) | E10 — importação remota de fonte; manter Bootstrap compilado local e tema. |
| [resources/scss/shared.scss](../../resources/scss/shared.scss) | E01/E02/E07/E10 — sidebar/offset, utilitários de breakpoint, foco, banner; preservar reduced-motion. |

## JavaScript compartilhado e offline — 10 arquivos

| Arquivo | Foco da implementação/verificação |
| --- | --- |
| [resources/js/offline/chaveamento-engine.js](../../resources/js/offline/chaveamento-engine.js) | E06/E07 — preservar avanço e resolução de IDs temporários; sem mudança visual direta proposta. |
| [resources/js/offline/mesario-data.js](../../resources/js/offline/mesario-data.js) | E07 — preservar projeções e estado local; sem limpar stores para corrigir UI. |
| [resources/js/offline/mesario-offline.js](../../resources/js/offline/mesario-offline.js) | E01/E07 — título específico e foco na navegação real; reativação sem duplicação. |
| [resources/js/offline/offline-core.js](../../resources/js/offline/offline-core.js) | E07 — banner acessível, contraste e estado real; preservar idempotência/fila/recuperação. |
| [resources/js/offline/offline-form.js](../../resources/js/offline/offline-form.js) | E07/E09 — preservar integração de formulários e envio offline; feedback coerente com confirmação. |
| [resources/js/shared/bootstrap-feedback.js](../../resources/js/shared/bootstrap-feedback.js) | E03/E09 — reutilizar confirmação/foco/fila/escape; ampliar feedback sem duplicar modais. |
| [resources/js/shared/cronometro.js](../../resources/js/shared/cronometro.js) | E07 — preservar tempo e persistência; não anunciar tick em live region. |
| [resources/js/shared/html-utils.js](../../resources/js/shared/html-utils.js) | E03/E09 — reutilizar escape; impedir HTML não confiável em mensagens e atributos. |
| [resources/js/shared/http-client.js](../../resources/js/shared/http-client.js) | E08/E09 — seguir CSRF/base/API e contratos; não assumir prefixo automático para links de páginas. |
| [resources/js/shared/page-runtime.js](../../resources/js/shared/page-runtime.js) | E01/E07/E09 — usar mount/listen/cleanup, sem listeners ou timers duplicados. |

## Imagens — 8 arquivos

E10 deve conferir consumo real e pedidos de rede antes de substituir/remover arquivos. Não excluir imagem só por ausência de uma referência textual estática.

| Arquivo | Bytes na auditoria | Orientação |
| --- | ---: | --- |
| [resources/images/arrow-right.svg](../../resources/images/arrow-right.svg) | 1050 | Preservar identidade/uso; conferir dimensões e alt do consumidor. |
| [resources/images/banner-global.png](../../resources/images/banner-global.png) | 54279 | Preservar identidade/uso; conferir dimensões e alt do consumidor. |
| [resources/images/banner-login-desktop.png](../../resources/images/banner-login-desktop.png) | 1907801 | Conferir dimensões, otimização, carregamento da variante visível e alt. |
| [resources/images/banner-login.png](../../resources/images/banner-login.png) | 135832 | Conferir dimensões, otimização, carregamento da variante visível e alt. |
| [resources/images/borda-banner-login-desktop.png](../../resources/images/borda-banner-login-desktop.png) | 4016 | Conferir dimensões, otimização, carregamento da variante visível e alt. |
| [resources/images/borda-banner-login.png](../../resources/images/borda-banner-login.png) | 11521 | Conferir dimensões, otimização, carregamento da variante visível e alt. |
| [resources/images/icone-equipes.png](../../resources/images/icone-equipes.png) | 1024 | Preservar identidade/uso; conferir dimensões e alt do consumidor. |
| [resources/images/logo-sgi-sesi.png](../../resources/images/logo-sgi-sesi.png) | 273728 | Conferir dimensões, otimização, carregamento da variante visível e alt. |

## Fronteiras e arquivos de apoio

Além dos 101 recursos, foram consultados os pontos relevantes de rotas, helpers, backend e testes. Não são fontes CSS/HTML adicionais.

- `config/routes/web.php`, `config/routes.php`: URLs, papéis e endpoints reais.
- `src/Shared/Http/Assets.php`, `src/Shared/Http/Url.php` e infraestrutura de armazenamento: geração de URLs e fotos; conferir símbolos atuais antes da alteração.
- Repositório de edição e controladores de upload: confirmação de ativação atômica e contratos de resposta; E05/E08 não exigem nova API.
- `tools/css-bundles.json`, `tools/build-assets.cjs`, `package.json` e lockfiles: ordem, dependências e assets gerados.
- `tests/javascript/`, `tests/browser/`, `tools/test-local.ps1`, `tools/test-docker.ps1`: cobertura e execução segura; mapa em [validação](03-validacao.md).

`public/assets/` é saída gerada e não deve ser editada. Bibliotecas vendorizadas, `node_modules`, logs, uploads e documentação histórica não fazem parte do inventário autoral de UI. A revisão não autoriza alterar dados, regras de acesso ou schema offline.
