# Plano executável de UI/UX

Leia primeiro [escopo e achados](README.md). Os caminhos de aplicação abaixo são relativos à raiz do repositório. O [inventário](01-inventario.md) relaciona todas as telas e scripts às etapas.

## Regras de implementação

1. Execute uma etapa por vez, sem misturar uma reconstrução geral do frontend. Registre arquivos, evidências, testes e pendências ao concluir cada etapa.
2. Releia `AGENTS.md` e os contratos atuais. Este plano descreve o checkout `914ff13c`; confirme se outro trabalho já corrigiu o problema antes de editar.
3. Não altere `public/assets` manualmente. Use Bootstrap existente para espaçamento, grade, botões, inputs, offcanvas e modais. CSS novo deve resolver geometria/estado específico, sem recriar o Bootstrap.
4. Não mudar o breakpoint de 1200px nesta primeira execução. Corrigir composição em retrato dentro do modo compacto. Preservar classes/IDs exigidos por testes e SPA; qualquer mudança necessária deve atualizar seus consumidores e testar ambos os modos.
5. Não adicionar endpoints, dependências, migrações ou regras de negócio por conveniência visual. Reutilizar `Assets`, `Url`, `SGI_BASE_PATH`, `SGIHtml` e `SGIPage`.
6. Mudança funcional precisa de regressão observável. Reproduzir o defeito antes, implementar, testar o caso e depois rodar `all -IncludeVisual`. Refatorações exigem `all` antes e depois. Os comandos estão em [validação](03-validacao.md).
7. Nomes longos, vazio, erro, carregamento, sem permissão e edição diferente são parte do trabalho, não acabamento opcional.
8. Preservar a troca obrigatória de senha já implementada e as decisões anteriores sobre credenciais iniciais. Não reimplementar o plano histórico de primeiro acesso.

## Ordem e dependências

| Etapa | Resultado | Depende de | Achados |
| --- | --- | --- | --- |
| E00 | Linha de base reproduzível | — | U23 |
| E01 | Navegação visível, contexto e área de conteúdo correta | E00 | U01, U02, U13 |
| E02 | Retrato e ações responsivas | E01 | U03, U19 |
| E03 | Semântica, formulários e modais comuns | E01 | U06, U08, U13, U14 |
| E04 | Acesso e perfil coerentes | E02, E03 | U04, U05, U08, U17 |
| E05 | Cadastros, inscrições e upload acessíveis | E02, E03 | U06–U09, U12, U21 |
| E06 | Agenda e chaveamento por teclado | E02, E03 | U07, U15 |
| E07 | Operação de partida e feedback offline | E03, E06 | U08, U16, U22 |
| E08 | Configuração de edição concluível e confiável | E03, E05 | U10, U11, U20 |
| E09 | Estados e recuperação consistentes | E04–E08 | U12, U18, U21 |
| E10 | CSS, assets e impressão consolidados | E01–E09 | U19, U22, U24 |
| E11 | Verificação final e evidências | Todas | U23 |

E03 já define o padrão de feedback que E04–E08 devem usar; E09 fecha os casos restantes. Não duplicar a mesma infraestrutura em cada etapa.

## E00 — Registrar a situação inicial

**Arquivos:** `AGENTS.md`, `docs/testing.md`, `tools/test-local.ps1`, `tools/test-docker.ps1`, `tests/browser/fixtures.cjs`, `tests/browser/global-setup.cjs`, suites citadas em E11.

1. Registrar `git status --short` e HEAD, preservando alterações do usuário. Não aplicar os patches sobre trabalho não revisado.
2. Conferir ferramentas, credenciais exclusivamente de teste e isolamento. O executor cria seu próprio banco e servidor; não usar a aplicação de trabalho em 8080.
3. Executar a linha de base completa com visual antes das refatorações compartilhadas. Guardar logs e screenshots de falhas anteriores.
4. Reproduzir primeiro os casos U01–U03 numa sessão de teste autenticada; usar 320/390/640/1024/1440px e verificar estilo computado, bounding boxes e controle alcançável. As capturas sintéticas desta auditoria servem como pista, não como fixture de negócio.
5. Criar cenários independentes com dados sintéticos conhecidos. Não inferir IDs da base de trabalho.

**Aceite:** ambiente isolado comprovado; resultados anteriores documentados; teste da correção específica falha pelo comportamento esperado, não por pré-requisito ausente. Não alegar que a linha de base completa está verde só porque `npm test` passou nesta auditoria.

## E01 — Corrigir navegação e estrutura de página

**Arquivos:** `resources/views/components/{admin-nav,aluno-nav,admin-header,admin-head,aluno-head,page-title}.php`; `resources/scss/shared.scss`; `resources/css/source/admin.css`; `resources/views/pages/aluno/{perfil,modalidade}.php`; `resources/js/offline/mesario-offline.js`.

1. Aplicar fundo explícito à sidebar do aluno, preferindo `bg-primary` como a administrativa, e manter contraste dos links. Não depender de estilos de `admin.css` no portal do aluno.
2. Transferir as regras de dimensão `.nav-avatar-*` usadas pelos dois menus para o bundle compartilhado e remover somente as duplicatas transferidas. Manter fallback de inicial e tamanho da foto sem deformação.
3. Colocar os dois mains problemáticos no contrato de offset já existente. No perfil, adicionar `main-desktop-layout` ao main desktop; em inscrições, adicionar a classe compatível com layout compartilhado sem criar margens manuais. Conciliar os paddings Bootstrap atuais para não somar duas compensações.
4. Adicionar `aria-label="Navegação principal"` ao nav desktop, nomes explícitos aos links e `aria-current="page"` ao ativo. Manter rótulo visual disponível: texto curto abaixo/ao lado dos ícones dentro da largura existente ou legenda que abra por foco e hover sem cobrir a tarefa. O nome acessível não depende da legenda.
5. Trocar a construção cega de nome de ícone com `-fill` por pares válidos quando houver variante, ou manter o ícone base e usar `active`/fundo/indicador visível. Nem todo ícone possui variante fill. Evitar diferenciar o ativo apenas pela cor.
6. Corrigir a correspondência de páginas ativas: `agenda_mesario`/`chaveamentos_mesario` versus valores atuais; páginas de equipe, turmas e inscrições hoje usam chaves ausentes ou genéricas. As rotas e autorizações existentes continuam sendo a fonte de verdade.
7. Permitir que `page-title.php` receba título já definido pela página, com fallback seguro, no formato `Agenda | SGI`. Preparar título antes de incluir o head. Atualizar `updatePageTitle` e a remontagem da SPA para não redefinir tudo como “SGI”. Nome da edição pode complementar, nunca substituir a identificação da tela.
8. Inserir link “Ir para o conteúdo” visível ao foco e um destino único na raiz ativa. Na SPA, atualizar título/contexto e transferir foco para o heading da tela somente após navegação efetiva. Atualizações periódicas de dados não devem roubar foco. Restaurar comportamento de Voltar sem focar elemento removido.
9. Conferir retornos administrativos que apontam para `aluno/inicio` e links que perdem `id`. O caminho pode ser redirecionado pelo Kernel, mas deve representar diretamente o destino correto para o perfil e edição. Usar helper de URL consistente; o cliente HTTP atual prefixa `/api`, não corrige todos os links de página.

**Testes:** ampliar `frontend-regression.spec.cjs`, `auth-rbac.spec.cjs`, `aluno-portal.spec.cjs`, `mesario-responsive.spec.cjs`, `deployment-paths.spec.cjs`; adicionar casos de teclado no arquivo de acessibilidade de E11.

**Aceite:** em 1440px, main começa após os 80px atuais da sidebar; em compacto, offset zero. Cada link tem nome e destino correto, existe um ativo quando aplicável, menus continuam respeitando os quatro perfis. Reentrada SPA não duplica menu/listener; título muda por tela; foco continua operável.

## E02 — Corrigir retrato, grades e barras de ações

**Arquivos:** `resources/css/source/admin.css`, `utilities.css`, `aluno-pages.css`, `resources/scss/shared.scss`; templates `eventos/dashboard.php`, `eventos/configurar-modalidades.php`, `acesso/colaboradores.php`, `eventos/{categorias,configurar-categorias,configurar-resumo}.php`, `competicoes/modalidades.php`.

1. Dashboard: uma coluna abaixo de 576px; preservar duas colunas em paisagem de 640px quando couberem. Restringir a regra atual `.col-12.col-md-6 {50%}` a essa faixa, evitando que se aplique a qualquer largura compacta.
2. Nos cabeçalhos dos cards, garantir `min-width:0` no conteúdo flexível, ícone com tamanho estável e texto que quebre. Não resolver com `overflow-x:hidden` no body nem com redução extrema da fonte.
3. Modalidades: remover o impedimento de encolher do grupo de ações; permitir que o grupo ocupe a linha e que os botões quebrem/empilhem em 320/390px. Aplique a mesma regra aos grupos equivalentes, depois de verificar o inventário.
4. Estatísticas de colaboradores: empilhar ícone e texto ou usar uma coluna quando a largura interna não comportar ambos. Verificar “Colaboradores”, nome longo e quatro dígitos.
5. Substituir barras em `top:85%` por uma região no fluxo, ou sticky no fim com fundo opaco e espaçamento real. A região deve suportar múltiplas linhas e safe area inferior. Botão de voltar fica junto ao título/contexto; não precisa competir com a ação primária no rodapé.
6. Garantir espaço para o menu flutuante no cabeçalho compacto, inclusive em páginas do aluno sem banner. Medir colisões com botão, título e banner offline. Não deslocar todos os conteúdos arbitrariamente por padding global.
7. Preservar rolagem horizontal interna quando o conteúdo é realmente bidimensional, como chaveamento desktop/tabela extensa. Adicionar nome e instrução de rolagem e testar teclado; não truncar informações necessárias para “passar” no teste.

**Testes:** `mesario-responsive.spec.cjs` e `frontend-regression.spec.cjs` com cenários em retrato e dados longos; contrato visual de painel, colaboradores e modalidades.

**Aceite:** viewport 320px sem scroll horizontal da página; 390px com ações e texto completos; 640×360 continua funcional. Tab deve alcançar última ação e último registro sem cobertura fixa. Zoom/reflow e alturas curtas são verificados em E11.

## E03 — Padronizar HTML, rótulos, foco e modais

**Arquivos:** os 41 templates/componentes do inventário, renderizadores correspondentes em `resources/js/pages/`, `resources/js/shared/bootstrap-feedback.js` e `page-runtime.js` quando necessário.

1. Para cada input/select/textarea, conferir o nome no DOM renderizado, inclusive dentro de modal e conteúdo dinâmico. Usar `label for` e `id` único. Labels visíveis são o padrão; `visually-hidden` cabe em buscas claramente identificadas. Não usar placeholder como única instrução.
2. Rotular explicitamente os campos de pontuação (`pontos-1`, `pontos-2`, `pontos-3`, `pontos-arr`), filtros de agenda, edição de jogo, ocorrências, nova edição, nova categoria e colaboradores. Exemplos: “Pontos do 1º lugar”, “Multiplicador por kg”.
3. Em botões somente com ícone, preferir texto visível de ação: “Criar equipe”, “Adicionar alunos”, “Salvar alunos”, “Nova modalidade”. Quando o espaço exige ícone, usar nome acessível específico e contexto da entidade. Ícones decorativos recebem `aria-hidden=true`.
4. Associar cada `.modal` a um título único via `aria-labelledby`; adicionar descrição somente quando curta e pertinente. Normalizar “Fechar”. Não adicionar `aria-hidden` manualmente durante a abertura, pois isso é controlado pelo Bootstrap.
5. Manter modais fora dos mains escondidos por breakpoint e antes do footer. Mover modal/histórico de arrecadação e scripts de ocorrências para antes do fechamento de body/html. Deixar Bootstrap carregado uma vez e manter a ordem que os scripts precisam.
6. Usar `modal-dialog-scrollable` nos formulários longos; em paisagem curta, título e botões precisam ser alcançáveis por rolagem. Conferir fluxo com o teclado virtual em aparelho real; diminuir viewport não simula integralmente esse teclado.
7. H1 identifica a página; h2 seções; títulos de cards podem usar h3 com classe de tamanho apropriada. Substituir headings escolhidos apenas pela aparência e `h1.d-none` quando ele é a única identificação. Com dois mains legados, apenas o visível deve integrar a navegação assistiva; não é necessário unificá-los nesta etapa.
8. Estruturar grupos de radio com fieldset/legend. Substituir radios `d-none` do placar por padrão Bootstrap `btn-check` associado a labels ou controles nativos visíveis; garantir foco visível no label correspondente.
9. Definir padrão de erro reutilizável: mensagem junto do campo, `aria-invalid`, `aria-describedby` e foco no primeiro inválido após envio. Mensagem geral persistente com `role=status` para andamento/sucesso e `role=alert` para erro relevante. Evitar anunciar a mesma mensagem simultaneamente em vários lugares.
10. Não remover o foco nativo indiscriminadamente. Para componentes customizados, usar `:focus-visible` com contorno distinguível e espaço para ele não ser cortado. Não colocar tabindex positivo.

**Testes:** nomes via `getByRole`/`getByLabel`, clicar no label foca/seleciona controle, fechar por Escape, retorno de foco, Tab preso apenas enquanto modal está aberto. Repetir abrir/fechar e navegar fora/voltar com `admin-lifecycle.spec.cjs` e `bootstrap-components.spec.cjs`.

**Aceite:** nenhum controle operável sem nome, nenhum ID duplicado ativo, nenhum modal sem título anunciado; navegação e ordem de leitura fazem sentido. Bootstrap continua controlando o foco; não criar um segundo trap.

## E04 — Melhorar login e perfil

**Arquivos:** `acesso/login.php`, `js/pages/acesso/login.js`, `css/source/login.css`; os dois `perfil.php` e `perfil.js`; `aluno/trocar-senha.php/js`; componentes de navegação.

1. Login em retrato: campos e botão principal com 48px e fonte de 1rem; rótulos persistentes. Limitar o banner por altura disponível (por exemplo, `clamp` com teto próximo de 35–40svh), validando 320×568 e 390×844. Manter foto/marca e composição de paisagem; rolagem vertical é preferível a cortar o formulário.
2. Dar título “Acesso ao sistema” também ao mobile. Conferir autofill, colagem, nome/matrícula alfanumérica e `autocomplete=username/current-password`. Não forçar input numérico se a matrícula pode conter letras.
3. Transformar o texto de recuperação em botão “Como recuperar o acesso?” que abre orientação local: procurar a organização para redefinição. Usar o processo de reset existente. Não inventar envio de e-mail ou endpoint de recuperação. Oferecer a mesma ajuda nos dois tamanhos.
4. Em `realizarLogin`, bloquear reenvio em andamento, apresentar “Entrando…” e `aria-busy`, restaurar botão no erro e manter matrícula digitada. Mensagem de credencial inválida permanece genérica; erro de rede sugere tentar novamente. Não alterar regras de senha nesta etapa.
5. Perfil: remover a linha “E-mail” que repete matrícula se não existir campo real no contrato; não adicionar coluna de e-mail. Usar “Aluno” para o papel 3 quando apropriado. Remover “Online” estático e “Senha criptografada”, que não ajudam a ação do usuário.
6. Retirar `tabindex=-1` dos botões de mostrar senha, atualizar nome entre “Mostrar senha”/“Ocultar senha” e estado quando necessário. Aplicar padrão também no primeiro acesso sem alterar o mínimo de seis caracteres ou a obrigação de troca.
7. Foto: manter upload/remover/salvar, mostrar formatos e limites efetivos do servidor, erro com recuperação e fallback. Corrigir verificação dos caminhos legados usando `StoragePaths` ou dado já preparado pela apresentação; não reconstruir caminho público de armazenamento no template.
8. Conferir foto no menu após salvar/remover e nova navegação. Preservar autorização da rota de foto e não converter uploads privados em arquivos públicos.

**Testes:** login inválido, rede indisponível, duplo envio e sucesso; teclado nos toggles; perfil com/sem foto e falha de upload; jornada de primeiro acesso e troca voluntária. Ampliar `auth-rbac`, `aluno-portal`, `frontend-regression`, `first-login-password.test.cjs` quando o comportamento coberto mudar.

**Aceite:** ajuda realiza uma ação útil; formulário legível nas duas orientações; uma requisição por envio em andamento; perfil não apresenta dado sob rótulo incorreto; regressões de senha/termos continuam verdes. Capturas intencionais do login precisam de revisão humana antes de atualizar referências.

## E05 — Tornar cadastros, seleção e importação operáveis

**Arquivos:** templates/scripts de `participantes`, `eventos/{categorias,configurar-categorias,configurar-turmas,configurar-equipes,configurar-modalidades,configurar-locais}`, `competicoes/{modalidades,modalidade-detalhes,equipe-alunos,elenco-equipe}`, `acesso/colaboradores`, `aluno/modalidade`.

1. Em `configurar-turmas`, disponibilizar seletor de categoria e ação “Adicionar turma” em compacto. O modal deve ser compartilhado fora dos mains. Selecionar a categoria da URL, se válida; sem ela, mostrar uma instrução e permitir escolha, sem spinner indefinido. Sincronizar seleção/filtro entre desktop/mobile.
2. Categoria desktop: separar semanticamente seleção de categoria e link “Ver detalhes”. Usar radio/checkbox apropriado ou botão de seleção com estado. Não transformar um card que contém link em um botão aninhado.
3. Nos cards de modalidades em modo de criação, expor seleção com controle/estado acessível; no modo de consulta, manter link. Em inscrições do aluno, preservar Enter/Espaço já existentes, expor seleção e explicar equipe escolhida, lotação, elegibilidade e limite. Não alterar capacidade nem permitir quarta modalidade.
4. Associar os checkboxes de `equipe-alunos.js` ao nome do aluno; hoje vários anunciam apenas “Adicionar aluno à equipe”. Exibir contagem de novos selecionados e botão “Adicionar N alunos”. Preservar a regra explícita: desmarcar não remove quem já está vinculado.
5. Colaboradores: campos de senha com `type=password`, `autocomplete=new-password` e opção acessível para mostrar; edição vazia mantém senha conforme contrato. Usar fieldset/legend para papéis e mostrar somente opções permitidas.
6. Uploads: manter input de arquivo nativo visível e rotulado, ou botão nativo “Selecionar PDF” que o aciona; drag-and-drop é complementar. Não depender de `div` clicável ou label sem controle focável. Mostrar arquivo selecionado, tipo/tamanho permitido e próxima ação.
7. Regulamento: substituir “Tamanho máximo suportado” pelo limite atual de **20 MB** conferido em `RegulamentoStorage`, sujeito também aos limites reais de PHP/servidor. Para importação e foto, conferir as classes atuais e a configuração antes de escrever números; não copiar 20 MB automaticamente.
8. Em `enviarPdf`, validar status HTTP, parse JSON e contrato de sucesso explícito real do endpoint antes de dizer que concluiu. HTML 200, JSON inválido e erro de aplicação mantêm o formulário e mostram falha. Não mudar CSRF para facilitar esse teste.
9. Progresso: enquanto houver bytes totais, atualizar `aria-valuemin/max/now`; quando não houver percentual confiável, usar estado indeterminado e texto “Processando”. Não dizer “Processando alunos” após resposta que já confirmou conclusão. Exibir resumo de importados/recusados somente se o endpoint o fornecer.
10. Preservar os detalhes de falha e permitir reenvio seguro no mesmo formulário. Não fazer reload imediato que apague resultado relevante, busca ou contexto. Conferir duplicidade e atomicidade nos testes de importação existentes.

**Testes:** categoria mobile com e sem parâmetro; criar turma/erros; seleção por teclado; matrícula/nome longo; inscrição válida, lotada, duplicada e limite; upload válido, extensão inválida, conteúdo não PDF, erro de aplicação e HTTP 200 com HTML; perfis sem autorização.

**Aceite:** a mesma tarefa está disponível em compacto e desktop; nenhuma seleção depende só de mouse; upload falso não é confirmado; dados e permissões permanecem consistentes. Usar `configuration-name-xss`, `aluno-portal`, `frontend-regression`, `admin-lifecycle` e integração HTTP pertinente.

## E06 — Agenda e chaveamento acessíveis

**Arquivos:** `eventos/configurar-agenda.php/js`, `competicoes/chaveamento.php/js`, `aluno/jogos.php/js`, `css/source/admin.css`.

1. Calendário: caminho mais simples é um conjunto de botões nativos agrupados por mês. Cada dia tem nome completo (“15 de setembro de 2026, 2 jogos”), estado selecionado e indicação textual/acessível de hoje. Células de preenchimento sem data não são botões; datas válidas sem jogos continuam selecionáveis para preservar o filtro e o estado vazio. Não adicionar `role=grid` sem implementar navegação de grid.
2. Se for adotado grid, implementar roving tabindex, setas, Home/End e transição de mês conforme padrão escolhido; documentar. Na solução simples, Tab/Enter/Espaço funcionam nativamente. Rótulos de mês/ano e nomes dos dias não podem depender apenas das letras D/S/T/Q.
3. Ao selecionar dia/filtro, preservar foco no controle ou no equivalente após rerender, anunciar quantidade/data do resultado uma vez e manter opção de limpar o filtro.
4. No cartão de jogo do aluno, preferir botão explícito “Ver detalhes do jogo” dentro do article. Alternativa de card inteiro exige nome, Enter/Espaço e foco, sem interativos aninhados. Não basta `role=button`.
5. KVS: preservar busca e grupos. Recomendação de menor complexidade é busca rotulada + select nativo com optgroups, mantendo a seleção ao filtrar. Se a experiência exigir popup, implementar combobox/listbox completo, incluindo expansão, opção ativa, seleção, Escape, retorno de foco e comportamento quando não há resultados. Não adicionar apenas ARIA a um comportamento incompleto.
6. Remover o uso de `#9ca3af` para texto essencial; usar cor semântica de contraste suficiente. Buscar com acentos/nome longo e mudar de viewport não podem perder a opção selecionada.
7. No chaveamento, manter leitura por fases e scores; identificar vencedor também por texto/ícone. Ações aparecem com foco e em dispositivos sem hover, inclusive tablet grande. Não alterar o avanço do mata-mata.
8. No histórico em modo card, substituir dependência de textos de `td::before` e cabeçalho `display:none` por associação estrutural confiável. Pode manter tabela semântica em scroll ou criar lista com rótulos no DOM, evitando duplicar informação anunciada. Testar leitura com NVDA.
9. “Abrir no Google Calendar” hoje abre somente a home externa: renomear para “Visitar Google Calendar” com indicação de nova aba ou remover esse atalho da tarefa. Exportação/sincronização de agenda seria escopo novo, não deve ser fingida pelo rótulo.

**Testes:** calendário por teclado, troca de mês e filtro vazio; popup/lista e seleção; categoria/modalidade com nomes iguais; abrir detalhes do aluno por teclado; reentrada SPA e navegação offline preparada. Ampliar suites de agenda/chaveamento e acessibilidade sem substituir testes de resultado.

**Aceite:** toda seleção funciona sem mouse; estado é anunciado; foco não fica em conteúdo escondido; calendário não prende o usuário; chaveamento e agenda em 640×360 e desktop continuam com todos os controles.

## E07 — Placar e estado offline claros

**Arquivos:** `competicoes/placar.php/js`, `resources/js/offline/{offline-core,mesario-offline}.js`, `shared.scss`, `admin.css`, `aluno-shared.css`. Ler `mesario-data.js` e `chaveamento-engine.js` para preservar os contratos, sem redesenhar sua persistência.

1. Rotular +/- com ação e equipe: “Registrar ponto para Equipe A” e “Anular ponto da Equipe A”. O texto deve corresponder à operação real, que pode abrir seleção do atleta; não dizer que salvou antes da confirmação.
2. Aplicar radios acessíveis de E03 a ocorrências; associar todos os selects. Esclarecer “Penalidade em pontos” e “Sem penalidade” sem contradizer 0/1–30. Manter o atleta obrigatório quando o contrato exige vínculo.
3. Não usar região viva para cronômetro a cada segundo. Anunciar pausa, retomada, fim do tempo e confirmação de ponto/ocorrência em região dedicada e moderada. O overlay “Tempo esgotado” em pseudo-elemento precisa equivalente textual real; bloqueio deve ser também funcional, como já exige o fluxo.
4. Tratar estado da partida e estado de sincronização como informações distintas: partida concluída localmente pode continuar pendente de envio. Exibir indicação de pendência próxima ao resultado usando o estado existente, sem inventar confirmação do servidor.
5. Banner: região estável `role=status`/`aria-live=polite`, com mensagem atualizada apenas quando o estado relevante muda. Erro de revisão precisa ser persistente e acionável, não sumir só por timeout.
6. Separar rótulos “Sem conexão”, “Alterações pendentes”, “Enviando alterações”, “Revisão necessária” e “Sessão expirada”, conforme estado real exposto. Só usar “Enviando” durante execução; online com fila não prova sincronização em curso.
7. Alterar variante dos botões conforme fundo: `outline-dark` no amarelo, contraste adequado no vermelho ou superfície neutra. Preservar importar/exportar/sincronizar. Não remover recuperação para simplificar a barra.
8. Em compacto, mostrar estado/conteúdo essencial com quebra de linha e opção “Ver pendências” para detalhes se necessário; evitar esconder ações fora de uma faixa horizontal sem indicação. Manter altura medida por ResizeObserver e testar foco sob banner/menu/modal. Consolidar z-index duplicado entre contextos.
9. Na preparação offline, comunicar que vale para a aba preparada; refresh/nova aba sem rede não são suportados. Reutilizar o status e as tentativas atuais. Não adicionar Service Worker nem limpar IndexedDB.
10. Todas as novas inscrições de eventos/timers/observers devem ter ciclo de vida. Reentrar na tela três vezes e registrar uma operação precisa resultar em uma única mutação e uma única alteração de score.

**Testes:** `score-persistence`, `clock-persistence`, `individual-ranking`, `occurrence-offline-edit`, `offline-queue-regression`, `mesario-offline`, `tournament-offline`, `offline-tournament-bracket` e `mesario-responsive`. Incluir HTML 200/JSON inválido, reconexão, mesma chave reenviada, IDs negativos, revisão, sessão expirada e troca de operador.

**Aceite:** score, atleta, anulação e avanço continuam coerentes; mensagens distinguem salvo localmente de confirmado; controles recuperáveis e legíveis com toque/teclado; nenhuma operação pendente perdida ou duplicada.

## E08 — Corrigir o fluxo de configuração da edição

**Arquivos:** templates/scripts `eventos/{lista,configurar-categorias,configurar-turmas,configurar-modalidades,configurar-pontuacao,configurar-resumo}`; leitura de `EdicaoController`, `EdicaoService`, `MysqliEdicaoRepository`, `config/routes.php` e testes de ativação.

1. Registrar a sequência real a partir dos links atuais: a edição é criada antes do resumo. Identificar etapa atual e contexto `modo=create/view`; acrescentar um indicador simples de etapas com nomes e links válidos, sem inventar estados de banco como “rascunho”.
2. Mostrar nome/ano/status da edição selecionada. Em modo view, rótulos e ações não devem insinuar criação de uma nova edição.
3. Resumo: substituir o botão com alvo inexistente por link real “Editar categorias”, preservando id/modo, ou reutilizar um modal efetivamente presente. Disponibilizar a ação final equivalente em mobile e desktop.
4. Trocar “Criar interclasse” por texto que represente a operação. Se já ativa, “Concluir configuração” pode apenas ir ao painel; se a etapa ativa a edição, usar “Ativar edição e abrir painel” e explicar que a edição ativa anterior será encerrada/desativada conforme contrato. Não executar nova criação.
5. Remover o loop cliente que desativa edições individualmente. `MysqliEdicaoRepository::update` já desativa outras dentro da transação quando recebe `status_interclasse='1'`. Enviar apenas a atualização da edição desejada pelo endpoint existente.
6. Bloquear duplo envio e validar `response.ok`, JSON e indicador de sucesso previsto. Atualizar cache/navegar só após confirmação. No erro, permanecer no resumo, restaurar botão e permitir tentar de novo. Não colocar redirecionamento em `finally`.
7. Corrigir “Regulamento” que leva a pontuação: ambas as variantes devem chamar esse bloco “Pontuação”. Link do regulamento PDF, se mantido, aponta para a tela que o gerencia. Mostrar valores carregados, sem texto estático que afirme configuração concluída sem dados.
8. Links de turmas devem usar `idInterclasse` selecionado, não preferir a edição ativa. Botões de retorno e navegação devem preservar contexto; aplicar prefixo de subdiretório sem duplicá-lo.
9. Em pontuação, instrução deve mencionar a edição em edição e só habilitar continuar com estado coerente. Se há alterações não salvas, oferecer salvar/descartar/cancelar antes da navegação controlada. Não perder valores após tentativa malsucedida.

**Testes:** criação até resumo nos dois tamanhos; edição ativa e inativa; outra edição ativa; falha de ativação; resposta inválida; duplo clique; voltar/continuar com alterações; acesso negado e edição diferente. Teste HTTP verifica a atomicidade existente e a persistência; Playwright verifica que erro não navega e que mobile conclui.

**Aceite:** nenhum botão sem destino; mesmo resultado nas duas composições; UI só confirma operação confirmada; uma requisição de ativação; somente uma edição ativa conforme regra existente; contexto correto na raiz e em subdiretório.

## E09 — Uniformizar estados, erros e recuperação

**Arquivos:** scripts de páginas do inventário, especialmente `configurar-resumo`, `configurar-turmas`, `competicoes/jogos`, `aluno/jogos`, `configurar-arrecadacao`, rankings e perfis; helper de feedback existente.

1. Para cada lista/formulário registrar cinco estados: carregando, pronto, vazio válido, erro e ação em andamento. Não classificar erro HTTP/JSON como array vazio. Mostrar uma mensagem humana e ação “Tentar novamente” na região afetada.
2. Diferenciar filtro sem resultado (“Nenhum aluno corresponde à busca; limpar busca”) de turma/equipe sem cadastro (“Ainda não há alunos vinculados”). A sugestão de criar/adicionar só aparece para quem tem permissão.
3. Erros de consulta dos dados secundários não devem apagar o conteúdo principal já confirmado. Exibir aviso localizado de dado desatualizado quando adequado. Em `aluno/jogos::carregarMembrosEquipe`, liberar a marca de carregamento após falha, para nova tentativa funcionar.
4. Preferir toast para sucesso simples que não exige decisão; formulário/lista mantém resultado essencial. Usar `SGI.confirm` para exclusão, reset, ativação ou descarte, com entidade e consequência real. Não confirmar cada clique de cadastro.
5. Remover mensagens de infraestrutura como “verifique se o ficheiro api/v1/arrecadacao existe”. Texto proposto: “Não foi possível salvar a arrecadação. Seus valores foram mantidos. Tente novamente.” Só prometer preservação se a implementação a garantir.
6. Em inputs numéricos, informar unidade e regra efetiva: kg, pontos, quantidade de atletas/equipes. Não converter silenciosamente entrada inválida em zero sem orientação. Preservar aceitação de decimais/inteiros determinada pelo backend; não relaxar regra para acomodar a UI.
7. Preservar estado de busca/filtro e foco ao atualizar cards; reativar ambos os botões/inputs duplicados de uma operação. Bloqueio de envio deve incluir a função, não apenas a variante de botão clicada.
8. Guardar mudanças não salvas apenas durante a sessão de página onde necessário; não salvar senhas ou dados pessoais em localStorage para esse fim. Registrar handlers de navegação via runtime e removê-los ao desativar. `beforeunload` pode complementar, mas não é a única proteção nem um modal assíncrono confiável.
9. Adotar `role=status` para contagem/resultados e associar erros conforme E03; não tornar toda uma tabela ou histórico uma live region ruidosa.

**Aceite:** erro não parece vazio ou sucesso; tentativa novamente funciona; input e seleção sobrevivem à falha; mensagens explicam próxima ação; backend e fila continuam validando o resultado. Testar lento, offline, 401/403, 422/409 se retornados pelo contrato, 500, HTML 200 e JSON inválido nos fluxos afetados.

## E10 — Consolidar CSS, assets e impressão

**Arquivos:** os 9 estilos, `tools/css-bundles.json`, `tools/build-assets.cjs`, imagens e seus consumidores.

1. Após as correções funcionais, mapear regra→consumidor e retirar somente estilos comprovadamente obsoletos. Mover navegação compartilhada para shared, particularidades do aluno para aluno e geometria operacional para admin. Não fazer limpeza por busca de texto apenas: há classes geradas por JS.
2. A sobrescrita global de `.d-md-*` muda o significado de utilitários Bootstrap. Restringir gradualmente a mudança de shell a raízes/seletores SGI explícitos, mantendo 1200px e os testes existentes. Não redefinir `md` globalmente nem alterar toda a grade Bootstrap.
3. Consolidar regras duplicadas de banner offline e offsets antigos de barra inferior. Remover CSS legado somente após confirmar todos os consumidores, inclusive `#acoesInscricao` e impressão.
4. Tipografia: fonte de corpo legível e inputs em 1rem; rótulos secundários próximos de .875rem como orientação, sem forçar todo elemento a igual tamanho. Espaçamentos Bootstrap 2/3/4 e uma hierarquia consistente de radius/sombra. Não introduzir tokens para cada valor isolado.
5. Para independência de rede, preferir a pilha de fontes de sistema já existente e remover a importação externa. Se Inter for requisito visual confirmado, incorporar arquivos licenciados locais com `font-display:swap`, atualizar build/cache e guardar licença. Não buscar fontes em runtime para o offline funcionar.
6. Verificar contraste computado nos estados normal, foco, ativo, erro e hover. Branco sobre vermelho institucional não precisa ser substituído sem medição. Cinza `#9ca3af` sobre branco é aproximadamente 2,54:1; branco sobre amarelo `#ffc107`, aproximadamente 1,63:1: não usar para texto funcional.
7. Otimizar imagem de login e logo mantendo original/fidelidade; dimensionar para tamanhos usados e considerar `picture/srcset`. Evitar baixar as duas versões do banner quando só uma aparece, medindo requisições. Não lazy-load da imagem principal acima da dobra. Definir dimensões para estabilizar layout.
8. Corrigir alt: decorativas com `alt=""`; imagens funcionais nomeiam a função; foto/logo com descrição útil e concisa. Remover ícones duplicados do nome acessível quando o texto já explica a ação.
9. Impressão: garantir que apenas a variante apropriada do ranking seja impressa, com título, edição/data/contexto necessários, sem menu flutuante, botões ou modal. Verificar quebra de página dos cards e nomes completos; comparar preview com a lista filtrada escolhida.
10. Executar `npm run build` e inspecionar os assets realmente servidos. O build possui tratamento de arquivo ocupado; sucesso de comando não substitui conferir se o browser recebeu a versão nova.

**Aceite:** zero dependência remota necessária à UI/offline; nenhum componente duplicado do Bootstrap; grade e estados preservados; assets legíveis e menores quando otimizados; impressão sem duplicidade. Rodar `css-bundles.test.cjs`, checks, build e regressão completa antes/depois dessa refatoração.

## E11 — Fechar com testes e evidências

1. Implementar os casos de teclado/nome/foco em `tests/browser/accessibility.spec.cjs` (arquivo novo proposto, não existente hoje), usando as fixtures e segurança atuais. Não criar login auxiliar que burle CSRF ou o primeiro acesso.
2. Ampliar testes existentes de responsividade e adicionar screenshots comparativas de navegação do aluno, painel, agenda, placar, cadastro/modal e inscrições. Captura avulsa de `page.screenshot` documenta, mas não detecta regressão sozinha.
3. Testes devem afirmar comportamento: foco e ação por Enter/Espaço, `getByRole`/`getByLabel`, resultado de mutação, preservação de input, menu sem cobrir alvo, ausência de scroll indevido e estado de sincronização. Evitar testes que apenas exigem string CSS específica.
4. Rodar a [matriz de validação](03-validacao.md). Acrescentar análise automatizada de acessibilidade somente se deliberadamente adotada como dependência local; ela complementa o teste manual e não prova conformidade sozinha.
5. Inspecionar imagens antes de atualizar snapshots e preservar referências por plataforma. Falha Linux não é resolvida copiando PNG Windows. Se faltarem ferramentas/dados, registrar exatamente o impedimento.
6. Conferir diff final, links, `git diff --check` e docs alteradas. Relatar o que mudou, comandos, resultados e limitações; só marcar etapas validadas quando as verificações exigidas passarem.

**Aceite final:** todos os P1 confirmados resolvidos; P2 implementados ou explicitamente justificados com evidência de que não se aplicam mais; suites obrigatórias aprovadas no ambiente executado; jornadas dos quatro perfis e offline preservadas. Não declarar CI remoto executado a partir de teste local.
