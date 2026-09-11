# Plano de responsividade do SGI para implementação pelo Luna

Data: 11/09/2026. Estado: planejado; implementação ainda não iniciada.

## 1. Objetivo e limites

Entregar somente duas composições de interface: computador com monitor Full HD e celular Xiaomi intermediário também com tela física Full HD, sempre em posição horizontal (paisagem), conforme informado pelo usuário. Prioridade máxima: o mesário conseguir operar uma partida nos dois contextos, inclusive com a casca offline preparada. Celular vertical não é alvo de projeto nem de homologação.

Limitar a responsividade significa limitar os layouts projetados e homologados. Não bloquear acesso por marca, resolução, user agent ou orientação; não fixar o documento em 1920 ou 1080 pixels. Tamanhos fora da matriz recebem o layout correspondente à largura, sem uma terceira composição específica para tablets. Manter rolagem, zoom e acesso às funções.

Este documento resulta de revisão estática de templates, CSS, renderização JavaScript, casca offline e configuração de testes. Os riscos abaixo foram identificados no código; ainda não houve inspeção visual da aplicação nem homologação em Xiaomi físico nesta tarefa.

### Resolução física não é largura CSS

Adotar como referência física nominal o Full HD: 1920 × 1080 tanto no desktop quanto no Xiaomi horizontal. O modelo exato ainda não foi informado; confirmar as dimensões físicas do painel na homologação caso o aparelho utilize uma proporção mais alongada.

O navegador organiza o conteúdo em pixels CSS. Na simulação nominal do Xiaomi, 1920 × 1080 pixels físicos com relação de 3:1 correspondem a 640 × 360 pixels CSS antes de considerar o espaço ocupado pelas barras do navegador. A relação 3:1 é uma hipótese de teste, não uma configuração confirmada do aparelho. Modelo, configuração de exibição, navegador e zoom precisam ser considerados. Não configurar o viewport mobile com largura de 1920 pixels CSS: isso selecionaria indevidamente a composição desktop. A principal restrição do celular horizontal será a altura útil.

Referências técnicas: [MDN — viewport](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/meta/name/viewport) e [MDN — conceitos de viewport](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/CSSOM_view/Viewport_concepts).

### Matriz proposta

| Contexto | Referência física | Referência de projeto, em pixels CSS | Variações de validação | Compromisso |
| --- | --- | --- | --- | --- |
| Desktop Full HD | 1920 × 1080 | 1920 × 1080, com escala de referência 1:1 | 1920 × 900, representando menor altura útil do navegador | Layout desktop completo; altura fluida e rolagem vertical |
| Xiaomi Full HD horizontal | 1920 × 1080 nominal; confirmar dimensões no modelo real | 640 × 360, com DPR 3 simulado | 640 × 280 para altura útil reduzida; 800 × 360, 873 × 393 e 915 × 412 como tolerâncias sintéticas | Mesmo layout mobile horizontal, sem sublayouts por modelo |

O painel Full HD é um requisito informado pelo usuário; o DPR e as dimensões CSS mobile ainda são cenários sintéticos. A homologação final deve substituir ou complementar a referência com a área útil real do aparelho. A altura pode diminuir com barras do navegador e teclado; não é critério para cortar conteúdo.

Fazer também verificações curtas de robustez em 1199 e 1200 pixels e desktop a 125% de zoom/escala. Não criar novos layouts para esses casos nem afirmar que são alvos homologados. Se ocorrer rotação acidental para retrato, preservar estado e permitir rolagem; não implementar terceiro layout, bloqueio obrigatório de orientação ou dependência de tela cheia. Homologar o celular nas duas posições de paisagem para conferir recortes e áreas seguras laterais.

### Decisão sobre breakpoints

- Usar um único limite de composição próprio do SGI em 1200px CSS: abaixo dele, layout compacto de celular horizontal; a partir dele, desktop. O limite anterior de 768px deixa de ser adequado, pois os cenários mobile horizontais de 800–915px ativariam indevidamente o desktop.
- Não alterar os breakpoints globais do Bootstrap nem substituir a biblioteca.
- Nas áreas migradas, usar composição compacta abaixo de 1200px e desktop a partir desse limite, sem estados intermediários em `sm`, `md` ou `lg`. As classes `xl` podem ser usadas quando expressarem exatamente essa alternância.
- Migrar os pares de visibilidade `d-md-none`/`d-none d-md-*`, grades e offsets do shell para o mesmo limite de 1200px, incluindo CSS, templates, HTML gerado em JavaScript e eventuais consultas `matchMedia`. Não sobrescrever globalmente as utilidades Bootstrap; substituir os usos envolvidos ou aplicar classes próprias escopadas. A casca SPA e a página direta devem selecionar a mesma composição.
- Substituir os ajustes próprios de largura em 400, 575, 768 e 991 por dimensões fluidas ou pelo limite de 1200, quando pertencentes aos componentes tratados. Não apagar media queries de impressão, movimento reduzido, altura ou capacidades de entrada: elas não representam novos layouts.
- Classes Bootstrap antigas fora de uma etapa devem ser inventariadas e migradas na etapa correspondente, sem substituição global por expressão regular.

## 2. Diagnóstico do código atual

| Arquivo ou ponto de busca | Evidência e consequência a verificar |
| --- | --- |
| `resources/scss/shared.scss`: `.main-desktop-layout`, `.sgi-main` | Sidebar de 5rem, navegação inferior de 4rem, padding desktop de 2,5rem e mobile de 1rem. São a base para padronizar offsets. |
| `resources/views/pages/competicoes/placar.php` | Soma shell, `container-xxl py-4 px-3 px-md-4`, bloco Voltar com `mb-4` e card `p-4`. Risco de perder área operacional em margens e cabeçalhos. |
| `resources/js/pages/competicoes/placar.js`: `renderTudo()` | Gera o cronômetro e os times; `col-12 col-md-5` empilha os times no celular, com VS em outra linha. Alterar só o PHP não corrige essa composição. |
| `resources/css/source/admin.css`: `.mc-score`, `.btn-score` | Três tratamentos de largura para o placar, incluindo redução dos botões a 44px abaixo de 400px. Consolidar em duas composições e piso de toque definido. |
| `resources/views/components/admin-header.php` | Banner mobile de 120px. O placar já apresenta título e Voltar próprios: há oportunidade de cabeçalho compacto contextual. |
| `resources/views/components/admin-nav.php` | Menu mobile só com ícones, `p-1`, cinco destinos do mesário mais Sair. Script usa diferença entre altura inicial e atual para mover o menu; precisa de verificação com teclado e ciclo SPA. |
| `resources/css/source/admin.css`: `.sgi-offline-banner`, `body.sgi-offline-active` | Banner fixo e compensação fixa de 42px. O banner tem texto e várias ações em `offline-core.js`; pode ocupar mais linhas. |
| `resources/css/source/admin.css`: `.bkt-match__actions` | Ações dependem de hover/foco; tornar descobríveis por toque. |
| `resources/css/source/admin.css`: `.bracket-tree` | Hoje muda geometria em 991 e 575px. Definir árvore desktop e fases empilhadas mobile sob o mesmo limite do shell. |
| `resources/css/source/admin.css`: `#secaoJogos`, `#secaoJogosMob` | Histórico exige tabela de pelo menos 980px inclusive no celular. Priorizar cards operacionais no mobile; tabela continua apropriada no desktop. |
| Agenda, chaveamento e ocorrências | Há raízes desktop/mobile separadas. Preservar seletores e dados durante a adaptação; evitar novos pares duplicados de formulários. |
| `tests/browser/playwright.config.cjs` | Viewport geral de 1440 × 900. Não corresponde à nova referência desktop. |
| `tests/browser/visual-contract.spec.cjs` | Contrato visual atual cobre login em 1440 × 900 e 390 × 844; falta cobertura visual específica do mesário. |

Há alterações locais preexistentes em diversos arquivos, inclusive placar, navegação, SCSS e runtime. O Luna deve reler o diff antes de editar e integrar o plano ao estado atual, sem restaurar ou sobrescrever trabalho existente. As referências acima usam seletores, porque os números de linha podem mudar.

## 3. Contrato visual comum

### Desktop

- Sidebar existente com 80px; preservar destinos e permissões.
- Área de trabalho fluida descontando a sidebar, com 32px de espaçamento externo.
- Conteúdo geral com largura máxima de 1760px, centralizado dentro da área de trabalho; formulários curtos podem ter limite menor.
- Placar com largura máxima de 1440px. Operação principal em aproximadamente 2/3 e histórico/ocorrências em 1/3, com gap de 24px; usar `minmax(0, ...)` para permitir contração.
- Manter tempo, placares e comandos principais visíveis sem rolagem na referência 1920 × 900 para uma partida normal com duas equipes. Histórico longo pode exigir rolagem.
- Não usar `height: 1080px`, transformar a página por `scale()` ou esticar fontes e botões proporcionalmente à resolução.

### Mobile horizontal

- Aproveitar a largura com disposição lateral no placar e grades de duas colunas para cards simples; listas detalhadas e formulários longos podem manter uma coluna. Padding lateral de 12px, vertical de 8px, gaps de 8–12px e cards com padding de 12px. Evitar containers com padding cumulativo.
- Corpo e campos operacionais de 16px; metadados de pelo menos 14px; nomes essenciais podem quebrar linha.
- Alvos operacionais de pelo menos 48 × 48px, com 8px entre ações adjacentes. Pode haver exceção para ícone decorativo, nunca para sua área clicável.
- Substituir a navegação inferior fixa por botão Menu de 48 × 48px no cabeçalho compacto e painel offcanvas Bootstrap rolável. Isso libera a altura de 64px antes ocupada pela barra. Manter todos os destinos autorizados e Sair no painel, com texto e alvos de 48px, para todos os perfis. No placar, Menu e Voltar compartilham uma única faixa de cabeçalho.
- Remover no modo compacto os offsets inferiores reservados à antiga barra. Respeitar `env(safe-area-inset-left, 0px)`, `right` e `bottom`, principalmente com o aparelho invertido em paisagem. Não depender de recorte específico nem exigir fullscreen.
- Usar `min-width: 0`, quebra de texto e dimensões fluidas; não esconder defeitos com `body { overflow-x: hidden }`.
- Rolagem vertical do documento como padrão. Rolagem horizontal apenas em regiões de dados explicitamente previstas, nunca nos comandos do placar.
- Preservar o viewport atual `width=device-width, initial-scale=1.0`; não restringir zoom com `user-scalable=no` ou `maximum-scale=1`.

### Cabeçalhos, aviso offline e camadas

- No placar do mesário, substituir o banner decorativo de 120px por cabeçalho compacto com Voltar, título e estado. Aplicar por opção explícita no template, não por alteração indiscriminada de todos os cabeçalhos.
- Apresentar rede/pendências em texto legível, separado do estado esportivo. “Em andamento” não significa “sincronizado”. Preservar as mensagens e estados verdadeiros fornecidos pelo runtime.
- Preferir aviso offline no fluxo normal. Se a casca exigir posição fixa, medir sua altura com `ResizeObserver` e atualizar uma variável CSS; remover a dependência dos 42px constantes. Observar apenas o banner, sem loop de escrita/leitura por frame.
- Aviso e navegação ficam abaixo do backdrop/modal do Bootstrap. O `z-index: 1080` atual do banner deve ser revisto para não encobrir diálogos. Toast pode ficar acima se não bloquear controles.
- Ações secundárias de sincronização podem ficar em seção expansível acessível por botão; preservar sincronizar, importar e exportar pendências e seus handlers.
- Nenhum botão fixo pode cobrir último card, rodapé de modal ou teclado. Priorizar ações no fluxo no placar.

### Modais e teclado

- Manter Bootstrap e seu gerenciamento de foco/backdrop. No mobile horizontal, usar tratamento de tela cheia abaixo de 1200px, com corpo rolável e cabeçalho/rodapé compactos; no desktop, diálogo centralizado e largura apropriada ao formulário. Usar duas colunas nos campos curtos que couberem e largura total para descrição e mensagens.
- Rodapé deve permanecer alcançável com teclado aberto, por rolagem se necessário. Não exigir altura fixa nem reduzir fonte para encaixar.
- Preferir CSS com `dvh` e fallback; só manter JavaScript de viewport quando houver falha reproduzida no dispositivo. Remover o deslocamento heurístico atual do menu apenas após validar a substituição.
- Cada listener/observer novo deve ser registrado e limpo no ciclo correto do runtime. Montar/desmontar via SPA não pode multiplicar eventos.
- Diálogos de ponto, ocorrência, confirmação e erros devem permitir cancelar e retornar o foco à ação de origem.

## 4. Especificação das telas do mesário

### 4.1 Placar: prioridade P0

Mobile horizontal, com a seguinte disposição:

1. Uma faixa de cabeçalho com Menu, Voltar, título curto e estado; resumo de conexão/pendências quando aplicável. Metadados completos acessíveis em detalhes expansíveis.
2. Área principal em três colunas: equipe A à esquerda, cronômetro e controle de tempo ao centro, equipe B à direita. Usar grade equivalente a `minmax(0, 1fr) 144px minmax(0, 1fr)` com gap de 8px; as duas equipes recebem a mesma largura.
3. Na coluna central, Pausar/Retomar abaixo do tempo; duração editável somente quando a regra atual permite. Não empilhar o cronômetro acima das equipes no modo horizontal.
4. Cada equipe apresenta nome, placar e uma linha de botões menos/mais abaixo do placar. Preservar alvos de toque e associação visual dos comandos com a equipe.
5. Nova ocorrência e Finalizar ficam em uma faixa secundária abaixo da área principal, no fluxo, separadas dos controles de pontuação. Preservar a confirmação existente e suas regras.
6. Estatísticas, artilharia e histórico abaixo da operação principal.

Usar nomes com quebra de linha em vez de truncamento obrigatório; textos longos podem aumentar a altura. Meta: em 640 × 360, jogo iniciado, nomes usuais e sem aviso expandido, cronômetro, Pausar/Retomar e botões das duas equipes aparecem na área útil sem rolagem. Orçamento inicial: cabeçalho de 48px, área principal de aproximadamente 184px, faixa secundária de 48px e até 32px de espaços verticais, deixando margem para estado de rede compacto. São metas de composição, não alturas rígidas de texto. Em 640 × 280, com teclado ou mensagens longas, priorizar legibilidade e permitir rolagem sem sobreposição.

Ponto de partida: placares de 48–64px no mobile e 80–104px no desktop; cronômetro de 40–48px no mobile e 56px no desktop. São valores de projeto a validar, não motivo para criar outro breakpoint. Usar números tabulares e testar três dígitos sem alterar limites de negócio.

Desktop: manter as equipes lado a lado, cronômetro acima e histórico na coluna lateral. Os comandos de registrar/anular ponto continuam associados à mesma equipe. Nova ocorrência pode ficar junto do histórico, sem botão flutuante sobre o placar.

Implementar apresentação em `placar.php`, strings de `renderTudo()`/`renderIndividual()` em `placar.js` e CSS escopado. Preservar `#placar-grid`, `#timer-placar`, `#placar-acoes`, `data-partida-idx`, `data-idx`, `data-gols`, IDs de formulários e listeners existentes.

Não chamar `renderTudo()` em resize: essa função também interfere no ciclo do cronômetro. Reorganizar por CSS sem recriar estado ou registrar pontuação. O botão mais continua abrindo a seleção do atleta; menos continua anulando o ponto conforme o fluxo atual.

Estados obrigatórios: carregando, erro, sem equipes, agendado, iniciado, pausado, tempo esgotado, encerrado, offline com pendências, sincronizando e falha de sincronização. O bloqueio “Tempo esgotado” não pode cobrir a ação necessária para resolver o estado.

Prova individual: seletores de 1º/2º/3º em grade lateral de três colunas no mobile horizontal e no desktop, com rótulos acima e botão de salvar na faixa seguinte. Conferir nomes longos e seleção efetiva em 640px; detalhes podem expandir verticalmente. Preservar elegibilidade, impedimento de participantes repetidos, estados de bloqueio e retorno de sucesso/erro.

### 4.2 Dashboard e lista de jogos: P1

- `pages/eventos/dashboard.php`: cards principais em duas colunas no mobile horizontal e três desktop. Resumo da preparação offline legível; manter `#conteudo-principal` e a estrutura exigida pela casca.
- `pages/competicoes/jogos.php` e seu JS: cards com modalidade, equipes, horário/local, status e acesso ao placar. Ordem lógica de leitura; botão com área de toque adequada. Filtros em uma coluna mobile.
- Não acrescentar consultas periódicas ou pré-carregamentos para conseguir o novo layout.

### 4.3 Agenda: P1

- `pages/eventos/configurar-agenda.php` e `pages/eventos/configurar-agenda.js`: no mobile, data/filtros seguidos dos jogos; seleção de data expansível se necessário, preservando filtros atuais.
- Desktop: lista em 2/3 e calendário em 1/3 a partir de `xl` (1200px); abaixo disso, manter agenda compacta com seleção de data expansível, sem calendário lateral ocupando a área dos jogos.
- Rever `.ag-mobile { padding-top: 5.5rem }` e `.ag-cal-sticky`; calcular a partir do cabeçalho real, sem acumular compensações.
- Acesso ao placar evidente no card. Mesário mantém apenas as ações autorizadas; reorganização visual não concede edição administrativa.

### 4.4 Chaveamento e histórico de jogos: P1

- `pages/competicoes/chaveamento.php`, JS correspondente e `.bracket-*`: fases empilhadas no mobile, árvore em colunas desktop.
- Mobile: cabeçalho de fase, confrontos legíveis e comando de abrir jogo visível sem hover. Desktop: rolagem horizontal contida na árvore quando houver muitas fases.
- No histórico mobile, trocar a tabela mínima de 980px por cards com equipes/resultado, data/local, modalidade/fase, status e ação existente. Informações secundárias podem ficar em detalhes expansíveis, sem eliminar dados.
- Reutilizar a mesma coleção de dados e os mesmos handlers; não criar outra fonte de verdade. Se mantiver raízes desktop/mobile existentes, filtros e estado selecionado devem continuar sincronizados.
- Conferir cálculos de conectores e alturas no JavaScript antes de trocar CSS: não manter espaçadores verticais da árvore desktop nas fases mobile.
- Validar também prova individual, BYE, nomes longos e jogos temporários negativos gerados offline.

### 4.5 Ocorrências e perfil: P1

- `pages/disciplina/ocorrencias.php` e JS correspondente: cards legíveis, histórico com ações visíveis por toque e alvos de 48px; preservar distinção aluno/turma.
- Nova/editar ocorrência: campos em uma coluna mobile; descrição longa deve quebrar linha. Validar salvar, cancelar, excluir e mensagens online/offline.
- `pages/acesso/perfil.php` e JS correspondente: formulário sem recorte pelo teclado, ações de foto/senha e retorno acessíveis; manter acesso pela casca preparada conforme suporte existente.

## 5. Restante do sistema: P2 obrigatório

O escopo é o sistema inteiro; P0/P1 apenas definem a ordem. Não encerrar a implementação após o placar.

| Família | Arquivos em `resources/views/` | Tratamento |
| --- | --- | --- |
| Acesso | `pages/acesso/login.php`, `pages/aluno/login.php`, componentes head | Duas composições, formulário legível com teclado, preservar imagens e autenticação existentes |
| Portal aluno | `pages/aluno/*.php`, `components/aluno-nav.php`, `components/aluno-head.php` | Reaproveitar espaçamentos e navegação; jogos/ranking/inscrições em cards mobile; formulários em uma coluna |
| Eventos/configurações | `pages/eventos/*.php` | Formulários em uma coluna mobile, grade desktop; botões do rodapé alcançáveis; agenda já tratada em P1 |
| Participantes/equipes | `pages/participantes/*.php`, `pages/competicoes/equipe-alunos.php`, `elenco-equipe.php` | Busca/filtros sem transbordamento, nomes legíveis, ações de inscrição e gestão acessíveis |
| Modalidades | `pages/competicoes/modalidades.php`, `modalidade-detalhes.php` | Cards e detalhes adaptados, limites/permissões preservados |
| Resultados | `pages/resultados/ranking.php` | Ranking mobile legível; tabelas detalhadas podem rolar dentro de região identificada |
| Colaboradores/perfil | `pages/acesso/colaboradores.php`, `perfil.php` | Formulários/modais e listas com alvos adequados; preservar permissões |

Revisar também o HTML produzido pelos arquivos correspondentes em `resources/js/pages/`. Uma alteração de classe no template não alcança necessariamente os cards gerados após fetch.

Tabelas administrativas largas podem manter scroll local com indicação “Deslize para ver as demais colunas”. Não exigir conversão de toda tabela em cards. A conversão é obrigatória onde este plano a define para a operação do mesário.

## 6. Organização técnica e invariantes

- Tokens comuns em `resources/scss/shared.scss`: espaçamentos, alturas de navegação, limites do conteúdo e tamanho mínimo de alvo.
- Estilos de mesário em novo `resources/css/source/mesario.css`, incluído depois de `admin.css` no bundle `admin` de `tools/css-bundles.json`. Mover as regras de placar correspondentes, retirando duplicatas antigas em vez de acumular overrides.
- Adicionar uma classe de página, por exemplo `.sgi-placar`, ao main do placar; seletores devem funcionar tanto na página direta quanto dentro de `#conteudo-principal`. Evitar depender de classe de `body` que a casca pode não transportar.
- Ajustes comuns de modalidade/árvore permanecem no CSS compartilhado pelo componente, não globalizados para todos os cards Bootstrap.
- Publicar pelo pipeline existente: `npm run build`. Não editar `public/assets/` manualmente. Conferir manifesto e conteúdo publicado, inclusive se houver arquivo bloqueado no Windows.
- Preservar `SGI_ROOT`, `Assets` e `Url`; não introduzir novas rotas nem chamadas relativas legadas.
- Não alterar banco, migrações, autenticação, regras esportivas, esquema IndexedDB, IDs de mutação, ordenação/idempotência da fila, cálculo do cronômetro ou avanço de chaveamento para esta tarefa.
- Não limpar IndexedDB, filas, sessões ou caches de dados pendentes para testar aparência ou forçar atualização visual.
- Inspecionar `mesario-offline.js`, `offline-core.js`, `shared/page-runtime.js` e componentes head ao integrar assets/ciclo de página. Fazer alterações somente na apresentação ou ciclo de montagem que sejam necessárias e comprovadas.
- Manter HTML e CSS disponíveis no preload da casca. Não criar CSS remoto/CDN ou dependência que exija rede para abrir modal.
- Não ampliar a promessa offline: testar navegação em casca autenticada e preparada. Cold-open/refresh offline sem essa casca não passam a ser suportados por mudança visual.

## 7. Execução em etapas pequenas para o Luna

Executar sequencialmente. Ao concluir cada etapa, registrar arquivos alterados, checks, evidências e pendências em `docs/status-responsividade-luna.md` (arquivo a criar na implementação). Não marcar uma etapa como concluída se restar um critério obrigatório.

### R00 — Baseline e inventário

1. Ler `AGENTS.md`, este plano e `docs/testing.md`; examinar `git status` e diffs preexistentes.
2. Executar a suíte completa antes da refatoração em banco/servidor isolados. Usar o executor documentado, sem resetar banco de trabalho.
3. Capturar telas atuais de login, painel, agenda, placar iniciado/pausado, modal de ponto/ocorrência, chaveamento e ocorrência nas duas referências.
4. Registrar overflow, sobreposições e seletores de teste. Classificar defeitos confirmados separadamente dos riscos estáticos deste plano.
5. Registrar dados do Xiaomi real quando disponível: modelo, navegador, orientação, `innerWidth/innerHeight`, `devicePixelRatio` e `visualViewport.width/height/scale`. Sem aparelho disponível, seguir com os cenários sintéticos e deixar homologação física pendente.

Saída: baseline reproduzível e lista concreta de telas/estados; nenhum código funcional modificado nesta etapa.

### R01 — Shell, navegação e tokens

Arquivos principais: `shared.scss`, componentes admin/aluno de navegação/cabeçalho, `admin.css`; runtime somente se necessário.

Implementar espaçamentos, limite desktop, safe area lateral, alvos e cabeçalho compacto opt-in. Trocar a barra inferior pelo Menu offcanvas no celular horizontal e remover os offsets correspondentes. Migrar a alternância dos shells e suas grades de 768 para 1200px em todas as fontes envolvidas. Conferir acesso aos destinos para níveis 0, 1, 2 e 3.

Aceite: sem scroll horizontal do documento; último elemento alcançável; menu offcanvas abre/fecha com foco correto e não disputa camadas com modais; sidebar desktop apenas a partir de 1200px. As larguras mobile de 800–915px continuam usando composição compacta, sem barra inferior fixa.

### R02 — Placar coletivo e individual

Arquivos: `placar.php`, `placar.js`, novo `mesario.css`, `css-bundles.json`.

Aplicar a seção 4.1 sem alterar mutações. Cobrir todos os estados previstos, textos longos e três dígitos. Não duplicar placar ativo ou seus IDs para criar uma versão mobile.

Aceite: dois times operáveis simultaneamente no mobile; registro/anulação de ponto, pausa/retomada, encerramento e ranking individual preservados; controles críticos cabem na referência normal definida.

### R03 — Modais, aviso offline e teclado

Arquivos: modais nas páginas, `shared.scss`, CSS do mesário, apresentação do banner em `offline-core.js`, navegação e feedback compartilhado quando necessário.

Eliminar sobreposições, altura fixa do aviso e ações inacessíveis. Validar texto/contador de pendências com aviso expandido e recolhido. Exercitar teclado físico/virtual e retorno de foco.

Aceite: salvar/cancelar acessíveis; foco não escapa do modal; mesmo feedback online e offline; montagem repetida não duplica listeners/observers.

### R04 — Demais telas do mesário

Arquivos da seção 4.2–4.5, seus JS e CSS. Finalizar cards de histórico, fases mobile, agenda e ações de ocorrências.

Aceite: fluxo painel → agenda → placar → ocorrência → chaveamento → próxima partida funciona nos dois layouts e na casca preparada. Perfil permanece acessível.

### R05 — Páginas dos outros perfis

Aplicar matriz P2 por família; conferir CSS `login.css`, `aluno-shared.css`, `aluno-pages.css`, `aluno-home.css`, templates e HTML gerado.

Aceite: todas as famílias registradas no status, sem perda de formulário/ação/dado essencial. Impressão e permissões preservadas. Não redesenhar marca ou funcionalidades.

### R06 — Testes visuais e funcionais

Adicionar `tests/browser/mesario-responsive.spec.cjs`, reutilizando fixtures existentes. Atualizar ou complementar o contrato de login com as novas referências após inspeção das imagens. Manter baselines Linux/Windows separados.

Preferir contextos desktop/mobile dedicados aos testes de responsividade, com mobile habilitando `isMobile`, `hasTouch`, viewport horizontal e `deviceScaleFactor: 3` no cenário nominal. DPR escolhido para simulação não deve alterar regra de layout. Não executar automaticamente toda suíte de mutações duas vezes usando projetos globais: os testes compartilham preparação de banco. Manter execução sequencial e usar jogos/fixtures isolados por cenário.

Aceite: critérios da seção 8 atendidos e imagens inspecionadas, sem atualização automática de snapshots para esconder diferenças.

### R07 — Build, regressão completa e entrega

1. Executar build e todos os checks exigidos pelo projeto após a refatoração.
2. Conferir que a casca preparada usa os assets novos e navega offline; não usar limpeza de dados pendentes como solução.
3. Revisar diff final e retirar CSS duplicado/temporário introduzido durante a implementação.
4. Atualizar documentação com matriz realmente validada, evidências e limitações, incluindo pendência de aparelho físico se houver.

Aceite: resultados verificáveis e nenhuma mudança funcional fora de escopo. Falhas preexistentes devem ser identificadas, não declaradas como aprovadas.

## 8. Validação e definição de pronto

### Automatizar verificações relevantes

- Documento sem overflow horizontal: `scrollWidth <= clientWidth + 1`, após carregamento e abertura dos componentes relevantes. Exceções de tabela/árvore são internas ao contêiner, nunca à página.
- Botões críticos com bounding box de pelo menos 48 × 48px no mobile e clique efetivo. `toBeVisible()` sozinho não prova ausência de sobreposição: verificar geometria/interseção com navegação/banner e a ação real.
- Na referência 640 × 360 (simulação do painel Full HD horizontal com DPR 3), verificar cronômetro, pausa e comandos das duas equipes dentro da área útil no estado normal descrito em 4.1. Repetir em 640 × 280 aceitando rolagem vertical, sem recorte ou sobreposição dos comandos. Repetir em 800 × 360 e 915 × 412 para comprovar que o celular não ativa o shell desktop ao ultrapassar 768px.
- Nomes longos, placar de três dígitos, descrição longa, listas vazias e mensagem de erro sem recortes.
- Modal aberto: fechamento, confirmação, foco e rolagem. Simulação de menor altura não substitui teste do teclado Android real.
- Resize e ida/volta pela SPA: não reiniciar relógio, duplicar ponto/listener, perder filtro ou trocar equipe associada ao comando.
- Offline real via contexto do navegador: preparar casca, desativar rede, navegar, registrar ponto/ocorrência, pausar/retomar e reconectar; conferir a persistência no servidor, não apenas feedback na tela.
- Reexecutar cenários de jogos temporários, torneio e ranking individual já existentes; não refazer os motores para facilitar testes.

### Evidências visuais mínimas

Desktop e mobile principal: painel, agenda, placar iniciado, placar pausado com ocorrência, modal de ponto, modal de ocorrência, aviso offline com pendências, chaveamento e prova individual. Para imagens estáveis, usar dados determinísticos e cronômetro pausado ou relógio controlado apenas no teste; testar relógio em andamento em cenário funcional separado.

Nas larguras mobile auxiliares: placar com nomes longos, modal, aviso expandido e navegação. Capturar também o viewport visível; screenshot de página inteira pode ocultar o problema de comandos abaixo da área útil.

No Xiaomi físico, sempre em paisagem: Chrome Android utilizado na escola, teclado aberto, barras do navegador expandidas/recolhidas, ida/volta do app, paisagem nos dois sentidos e offline/reconexão. Conferir preservação de estado se houver rotação acidental, sem homologar retrato. Se o navegador real for outro, registrá-lo e validar nele antes de declarar homologação.

### Comandos e segurança do ambiente

Seguir a preparação de `docs/testing.md`. Antes e depois da refatoração, a suíte completa deve incluir:

```text
composer verify
npm run build
npm run check
npm test
php tests/run_all.php
npm --prefix tests/browser test
```

Não executar `tests/run_all.php` contra base de trabalho. O executor local documentado é uma alternativa que prepara e coordena as etapas:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local
```

Para contrato visual e configurações opcionais, seguir os perfis de `docs/testing.md`. Não rodar suítes que modificam banco em paralelo. Durante etapas intermediárias, fazer build e checks direcionados aos componentes alterados; a suíte completa continua obrigatória no início e no fechamento.

### Checklist final

- [ ] Há somente duas composições projetadas: celular horizontal abaixo de 1200px CSS e desktop a partir de 1200px; não há layout de retrato a homologar.
- [ ] Desktop Full HD e mobile sintético principal atendem todos os fluxos do mesário.
- [ ] Larguras auxiliares não cortam ações nem exigem zoom para operar.
- [ ] Modais, navegação e aviso offline não se sobrepõem indevidamente.
- [ ] Ações essenciais são descobríveis e utilizáveis por toque e teclado.
- [ ] Cronômetro, pontuação, ocorrências, ranking individual e avanço offline preservados.
- [ ] Famílias P2 revisadas; nenhuma função autorizada removida no mobile.
- [ ] Build, checks e suítes executados com resultados registrados.
- [ ] Capturas inspecionadas e diffs revisados.
- [ ] Xiaomi físico homologado, ou limitação explicitamente registrada sem alegar validação concluída.

## 9. Prompt pronto para o Luna

> Implemente o plano de `docs/plano-responsividade-luna.md`, seguindo R00 a R07 em ordem. O SGI terá duas composições: desktop Full HD e celular Xiaomi Full HD sempre horizontal, com prioridade para todas as telas do mesário online/offline. Use 640 × 360 pixels CSS com DPR 3 como referência mobile sintética e 1200px CSS como limite único entre composição compacta e desktop. No celular, disponha equipe A, cronômetro e equipe B lado a lado e use Menu offcanvas em vez de barra inferior fixa. Não projetar modo retrato. Leia `AGENTS.md`, preserve alterações locais existentes e registre o progresso em `docs/status-responsividade-luna.md`. Comece pela baseline em ambiente isolado, depois shell, placar, modais/aviso offline, demais telas do mesário e páginas dos outros perfis. Edite fontes em `resources/` e gere assets pelo build. Não altere regras de negócio, permissões, contratos da fila ou esquema IndexedDB. Valide CSS pixels, toque, teclado, sobreposições, cronômetro e reconexão conforme o documento. Não encerre após o placar nem declare homologação de aparelho físico sem evidência. Entregue arquivos alterados, testes executados, capturas e limitações reais.
