# Auditoria de UI/UX e plano de implementação para Luna

Data: 13/09/2026. Checkout de referência: `914ff13c`. Entrega: análise e planejamento; nenhum arquivo da aplicação foi alterado.

A interface segue boas práticas parcialmente. Bootstrap, componentes compartilhados, controles nativos e proteções de fluxo oferecem uma base aproveitável. Entretanto, há defeitos concretos de navegação, responsividade, acessibilidade e comunicação de resultados. A recomendação é corrigir essa base incrementalmente, preservando a identidade SESI e os contratos existentes, antes de qualquer redesign.

## Como usar esta entrega

1. Leia os achados abaixo e o [inventário completo](01-inventario.md).
2. Implemente na ordem do [plano detalhado](02-plano-luna.md), com os critérios de aceite de cada etapa.
3. Siga a [matriz de validação e as evidências](03-validacao.md).
4. Use o [prompt de execução para Luna](PROMPT-LUNA.md) para iniciar a implementação posteriormente.

## Escopo e método

- Varredura de todos os **41 templates/componentes PHP**, **9 fontes CSS/SCSS**, **43 scripts JavaScript** e **8 imagens** em `resources/`: 101 arquivos. Templates e estilos foram inspecionados; nos scripts, a análise se concentrou em renderizadores, eventos, feedback e navegação. Não é uma auditoria integral das regras de negócio dos 43 scripts.
- Conferência de rotas, composição dos bundles, contratos relevantes de edição/upload e testes existentes.
- Renderização estática de **33 templates em 5 viewports**, totalizando **165 combinações**: 320×568, 390×844, 640×360, 1024×768 e 1440×900. A 34ª página, `aluno/login.php`, é um redirecionamento.
- Templates executados com sessão e dados sintéticos; scripts da aplicação removidos da renderização, sem acesso ao banco e sem requisições às APIs. CSS compilado das fontes atuais. Recursos externos bloqueados; a fonte usa o fallback local. Isso permite medir geometria do HTML inicial, não confirmar jornadas autenticadas ou o conteúdo dinâmico.
- Inspeção visual das capturas de login, perfil, inscrições e gestão de turmas, além de duas referências visuais existentes do login. As referências antigas não substituem as fontes atuais.
- `npm test`: **101 testes aprovados**, nenhum reprovado ou ignorado. Suíte `all`, integração e jornadas Playwright autenticadas não foram executadas nesta entrega documental.

As marcações abaixo distinguem **C** (comprovado nas fontes), **R** (também reproduzido na renderização estática) e **V** (validação de interação ainda necessária). Uma renderização sem overflow não significa que a tela carregada, um modal aberto ou o teclado virtual também estejam corretos.

## O que preservar

- Bootstrap 5.3.8 local, tema em SCSS e ordem explícita dos bundles; não adicionar framework de frontend.
- Menu móvel com offcanvas, nome acessível, alvos de 48px e `aria-current`.
- `SGIPage.mount`, `pageScope.listen`, limpeza de página e reativação offline.
- `SGI.confirm` com foco inicial em Cancelar, restauração de foco, fila de diálogos e texto escapado. Não afirmar que falta controle de foco em todos os modais: o Bootstrap já fornece parte desse comportamento.
- `prefers-reduced-motion` no estilo compartilhado; filtros que já atualizam `aria-pressed`; progresso de inscrições que já atualiza `aria-valuenow`.
- Regras de inscrição, troca obrigatória de senha, termos, permissões e edição; contratos de pontos, resultados e idempotência offline.
- Breakpoint funcional compacto/desktop em 1200px, já coberto por testes. A correção do retrato não exige abolir o layout em paisagem homologado.

## Achados priorizados

P1: compromete tarefa principal, acesso ou confiança no resultado. P2: dificulta compreensão, uso consistente ou manutenção. P3: otimização. Não foi atribuída uma nota de usabilidade nem declarada conformidade WCAG: isso exigiria avaliação das jornadas completas e tecnologia assistiva.

| ID | Prioridade / evidência | Problema e consequência | Onde verificar | Etapa |
| --- | --- | --- | --- | --- |
| U01 | P1 · C/R | Sidebar do aluno transparente com links brancos. Ícones ficam quase invisíveis sobre o fundo claro. Dimensões dos avatares existem apenas em `admin.css`, que o aluno não carrega. | `components/aluno-nav.php`, `.sidebar-nav`; `shared.scss`; `admin.css` em `.nav-avatar-*`; `tools/css-bundles.json` | E01 |
| U02 | P1 · C/R | Perfil e inscrições do aluno começam em x=0 enquanto a sidebar ocupa x=0–80 em desktop. Conteúdo e botão de retorno ficam sob a navegação. | `aluno/perfil.php` no main desktop; `aluno/modalidade.php` em `.modalidade-layout`; seletores de offset em `shared.scss` | E01 |
| U03 | P1 · C/R | Grade compacta força duas colunas até em 320px; títulos e grupos de ações ultrapassam a viewport. Overflow medido: painel 414px em viewport 320; modalidades 416px; colaboradores 350px. | `admin.css` no bloco `<1200`; `eventos/dashboard.php`; `eventos/configurar-modalidades.php` no grupo `flex-shrink-0`; `acesso/colaboradores.php` em `#statsMobile` | E02 |
| U04 | P1 · C/R | Login em retrato usa inputs de 32px e texto de 11,2px; banner ocupa cerca de 438px em 390px de largura. Paisagem tem campos de 48px/16px. A tarefa fica desproporcionalmente pequena em retrato. | `css/source/login.css`, `.login-mobile-banner`, `.login-mobile-form .form-control`, `.login-mobile-button` | E04 |
| U05 | P1 · C | “Esqueci minha senha” é um `span` sem ação, presente somente no mobile. Login não bloqueia reenvio nem informa “Entrando…” durante a requisição. | `acesso/login.php`, `.login-mobile-forgot`; `js/pages/acesso/login.js`, `realizarLogin` | E04 |
| U06 | P1 · C | Campos sem associação de rótulo; ações com apenas ícone e sem nome. Exemplos: salvar alunos, criar equipe, campos de pontuação, filtros da agenda e formulários de ocorrências. | `competicoes/equipe-alunos.php`, `eventos/configurar-equipes.php`, `eventos/configurar-pontuacao.php`, `competicoes/placar.php`; inventário | E03 |
| U07 | P1 · C | Calendário usa `div[data-date]` clicável sem teclado; cartão de jogo do aluno tem `role=button` e `tabindex=0`, mas só `onclick`; categoria desktop depende de click para selecionar. Uploads com input `d-none` e área clicável também precisam alternativa de teclado. | `configurar-agenda.js`, `gerarCalendarioVisual/Mobile`; `aluno/jogos.js`, `renderizarJogos`; `configurar-categorias.js`; `participantes/turma-alunos.js`, `configurarDropzone` | E05/E06 |
| U08 | P1 · C | Radios de tipo de ocorrência usam `display:none`; botões de mostrar senha têm `tabindex=-1`. Funções importantes saem da sequência de Tab. Botões +/- do placar não identificam a equipe. | `competicoes/placar.php` e `placar.js`, `renderTudo`/`.btn-score`; os dois templates de perfil | E03/E04/E07 |
| U09 | P1 · C/R/V | Gestão de turmas por categoria tem seletor e criação somente no main desktop, inclusive o modal. Em compacto não é possível trocar a categoria; sem `id_categoria`, a lista depende de uma seleção invisível. | `eventos/configurar-turmas.php`, `#listaCategorias`, `#modalCriarTurma`; JS `carregarCategorias`/`carregarTurmas` | E05 |
| U10 | P1 · C | Resumo da edição tem “Adicionar categoria” apontando para modal inexistente e não oferece a ação final no mobile. No desktop, “Criar interclasse” altera ativação de uma edição já criada. | `eventos/configurar-resumo.php`, alvo `#adicionarCategoria`, `#btnCriarInterclasseFinal` | E08 |
| U11 | P1 · C | Finalização do resumo faz vários POSTs sem validar respostas e navega em `finally`, inclusive após falha. A UI pode aparentar conclusão sem ativação confirmada. Backend já desativa outras edições atomicamente ao ativar uma. | `configurar-resumo.js`, listener de `btnCriarInterclasseFinal`; `MysqliEdicaoRepository::update` | E08 |
| U12 | P1 · C | Importação trata JSON inválido como `{}` e aceita HTTP 2xx se `success !== false`; pode mostrar “Importação concluída” para HTML. Progresso visual não atualiza valores ARIA. | `participantes/turma-alunos.js`, `enviarPdf`/`progressoHelper` | E05/E09 |
| U13 | P2 · C | Título de todas as páginas é “SGI”; scripts de header e SPA também o sobrescrevem. Diversas telas não têm h1 identificável; não há atalho para conteúdo. Estado atual desktop depende de uma classe sem regra CSS e de nomes de ícones construídos acrescentando `-fill`. | `components/page-title.php`, `admin-header.php`, `admin-nav.php`, `aluno-nav.php`, `mesario-offline.js` | E01/E03 |
| U14 | P2 · C | Vários modais têm título visível sem `aria-labelledby`; alguns botões de fechar não têm nome ou usam “Close”. Há modais/conteúdo após `footer.php`, que já fecha body/html. | Perfis, colaboradores, placar, categorias, turmas, ocorrências; `eventos/configurar-arrecadacao.php`; `disciplina/ocorrencias.php` | E03 |
| U15 | P2 · C/V | KVS substitui select nativo oculto por popup com semântica incompleta. Tem botões focáveis e Escape no campo de busca, mas não expõe expansão/seleção e não implementa todo o comportamento de combobox. | `competicoes/chaveamento.js`, `kvs_montar`/`abrir`/`fechar`; `admin.css`, `.kvs__*` | E06 |
| U16 | P1/P2 · C/V | Banner offline preserva estados úteis, mas não os anuncia por live region. Botões `btn-outline-light` permanecem claros no fundo amarelo; “SINCRONIZANDO” pode significar apenas fila pendente. Barra e menu fixos exigem teste de foco e sobreposição. | `offline-core.js`, `createBanner`/`updateBanner`; `shared.scss`, `admin.css`, `aluno-shared.css` | E07 |
| U17 | P2 · C | Perfil mostra matrícula sob “E-mail”, “Online” estático e “Senha criptografada”. Avatares da navegação verificam caminhos legados de `uploads` em diretórios diferentes, contrariando o armazenamento atual. | Ambos `perfil.php`; `components/admin-nav.php` e `aluno-nav.php`; `StoragePaths` e rota de foto | E04 |
| U18 | P2 · C | Falhas viram ausência de dados ou apenas console; mensagens mandam verificar arquivo/API/banco. Não há recuperação consistente junto da lista. Isso confunde “nenhum cadastro” com “não consegui carregar”. | `configurar-resumo.js::carregarResumos`, `competicoes/jogos.js::carregarDados`, `configurar-turmas.js`, `configurar-arrecadacao.js::salvarTurma` | E09 |
| U19 | P2 · C/V | Ações fixas em `top:85%`, offsets antigos de navegação e barras sem reserva de espaço podem cobrir conteúdo/foco. `main` e controles duplicados aumentam a chance de divergência entre tamanhos. | `utilities.css`; categorias/resumo/modalidades; `aluno-pages.css`; `shared.scss` | E02/E10 |
| U20 | P2 · C | Informação de etapa e estado é inconsistente: “Pontuação” no resumo mobile vira “Regulamento” desktop; link de turmas prefere edição ativa à selecionada; “valores aplicados ao Interclasse ativo” pode contrariar o id aberto. | `configurar-resumo.php/js`; `configurar-pontuacao.php`; links de navegação sem contexto de edição | E08 |
| U21 | P2 · C/V | Seleção de inscrição possui teclado e contador, mas não expõe estado selecionado no próprio card. Alertas de formulário frequentemente não têm anúncio/foco associado. Aviso de alterações não salvas em pontuação não protege saída. | `aluno/modalidade.js`, `atualizarEstadoCard`; `configurar-pontuacao.js`, `marcarMudancas`; mensagens `msg*` dos formulários | E05/E09 |
| U22 | P2 · C | Fontes em Google Fonts ainda são importadas pelo tema, apesar de fonte local de fallback. Branco/amarelo e cinzas claros do KVS pedem correção de contraste; não é necessário trocar o vermelho institucional. | `bootstrap-theme.scss`; `admin.css`, `#9ca3af` em `.kvs__trigger`/`.kvs__vazio`; banner offline | E07/E10 |
| U23 | P2 · C/V | Testes de frontend tiram capturas e verificam erros, mas o contrato comparativo de imagens cobre somente login. Testes atuais de mesário privilegiam paisagem. Faltam verificações transversais de nome, teclado, recorte e foco. | `frontend-regression.spec.cjs`, `visual-contract.spec.cjs`, `mesario-responsive.spec.cjs`, `css-bundles.test.cjs` | E11 |
| U24 | P3 · C | Imagem desktop do login tem 1.907.801 bytes e logo 273.728 bytes. Há dois conjuntos de imagens/forms no HTML e textos alternativos pouco úteis em imagens decorativas. | `resources/images/`, `acesso/login.php`, `components/admin-header.php` | E10 |

As prioridades combinadas de U16 dependem do controle: contraste e acesso à recuperação da fila são P1; refinamento de texto e anúncio é P2. “V” não autoriza tratar uma hipótese de sobreposição como defeito já observado em sessão real.

## Exemplos visuais

Capturas com dados sintéticos e CSS atual, sem execução do JavaScript de negócio:

- [Perfil do aluno em desktop](evidencias/aluno-perfil-1440.png): sidebar sem fundo e conteúdo iniciando sob seus 80px.
- [Inscrições em desktop](evidencias/aluno-modalidade-1440.png): mesma sobreposição.
- [Login em retrato](evidencias/acesso-login-390.png): formulário muito pequeno em relação ao banner.

As imagens mostram o estado inicial do template. Spinners, skeletons e valores iniciais nessas capturas não são evidência de falha da API.

## Referências para os critérios do plano

- Rotular controles e associar explicitamente label/id; placeholder não substitui orientação persistente. [W3C — Labeling Controls](https://www.w3.org/WAI/tutorials/forms/labels/).
- Associar erros ao campo, explicar correção e anunciar resultados relevantes. [W3C — User Notifications](https://www.w3.org/WAI/tutorials/forms/notifications/).
- Meta de contraste: 4,5:1 para texto normal e 3:1 para texto grande, observadas as definições e exceções do critério. [WCAG — Contrast Minimum](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html).
- Meta de projeto: controles principais de toque com 44–48px. Isso é mais exigente que o mínimo AA de 24×24px, que admite exceções de espaçamento e contexto; um input de 32px não viola automaticamente esse critério. [WCAG — Target Size Minimum](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html).
- Foco visível e não totalmente encoberto por conteúdo fixo. [WCAG — Focus Not Obscured](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum).
- Para manter um combobox customizado, implementar o padrão completo de semântica/teclado adequado ao widget. [WAI-ARIA APG — Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/).

Não há recomendação para mudar regras de autenticação, textos jurídicos dos termos ou armazenamento de dados nesta auditoria visual. Alterações de interação devem respeitar os contratos já implementados.
