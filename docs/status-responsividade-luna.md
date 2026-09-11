# Status da responsividade do SGI

Data da revisão: 11/09/2026

## Entregue nesta rodada

- Duas composições de referência: desktop a partir de 1200px CSS e celular Xiaomi Full HD em paisagem abaixo desse limite.
- Shell compartilhado com sidebar desktop e raízes compactas coerentes entre 640, 800 e 915px CSS.
- Entrada pública corrigida para não voltar ao desktop entre 768 e 1199px.
- Login compacto em paisagem com banner e formulário lado a lado, mantendo os campos dentro da altura útil de 640 × 360 CSS.
- Placar coletivo com equipe A, cronômetro e equipe B na mesma grade; botões operacionais com pelo menos 48px no celular.
- Agenda compacta com filtros e cartões de jogos; calendário sem o espaçamento legado da barra inferior.
- Chaveamento compacto com fases empilhadas, ações visíveis por toque e histórico convertido em cartões sem tabela mínima de 980px.
- Ocorrências compactas com cartões de turma, ações de histórico/registro e modais roláveis em altura reduzida.
- Dashboard do Mesário, lista de jogos e perfil com a mesma grade compacta de duas colunas, quebra de nomes e comandos de toque.
- Cabeçalho decorativo do placar desativado no compacto e ocorrência mantida no fluxo da página.
- Aviso offline com altura medida por `ResizeObserver`, sem depender de 42px fixos.
- Navegação compacta migrada da barra inferior para Menu offcanvas com foco, backdrop, destinos autorizados e Sair.
- Casca offline protegida contra duplicação do Menu ao trocar/remontar telas na SPA.
- Conectores do chaveamento recalculados em cada redimensionamento desktop com `requestAnimationFrame` coalescido e cancelamento na desmontagem da SPA.
- Testes de regressão Node e Playwright específicos para breakpoint, overflow, toque, redimensionamento e remontagem SPA.

## Validação executada

- `npm run build`: passou; 104 assets preparados.
- `npm run check`: passou; 42 arquivos JavaScript válidos.
- `npm test`: passou; 95 testes.
- Revisão final: `node --check` dos arquivos alterados, `git diff --check` e teste responsivo estático passaram; o ajuste de redimensionamento dos conectores foi incluído sem alterar regras de negócio.
- Smoke geométrico em Chromium com os bundles publicados: login compacto em 640 × 360, 800 × 360 e 915 × 412 ficou dentro da altura útil e sem overflow horizontal.
- `composer verify`: PHPUnit passou (233 testes, 2186 asserções) e análise estática passou; o gate de estilo permanece bloqueado por diferenças de fim de linha preexistentes em 292 arquivos.
- Suíte Playwright `mesario-responsive.spec.cjs`: 4 testes passaram em servidor PHP temporário e banco descartável, incluindo 640/800/915px e 1920px; a nova cobertura valida Dashboard, lista de jogos, Agenda, Chaveamento, Perfil e Placar no modo compacto.
- Após o ajuste final de posicionamento do aviso, `mesario-offline.spec.cjs` passou (1/1), cobrindo preparação, operação sem rede, fila e sincronização.
- Suíte Playwright completa (sem contrato visual) da rodada anterior: 52 testes passaram e 1 foi pulado por fixture opcional, incluindo os fluxos online/offline existentes; a mudança desta rodada foi validada pela suíte focada de 3 testes.
- `php tests/run_all.php` em base isolada: a preparação inicial passou 41/41 asserções, mas o processo parou ao configurar a sessão do teste de envelopes; a causa é o fluxo preexistente de `session_set_save_path()` depois da saída em CLI. Uma rodada Docker anterior percorreu 376/376 asserções, mas o executor marcou falha por aviso de `mysqli` carregado duas vezes no processo de ambiente.

## Pendências para o Luna

- Revisar as páginas administrativas P2 e executar a homologação visual/física final em um Xiaomi Full HD real.
- Rodar o contrato visual e revisar capturas após preparar fixtures determinísticos para todos os fluxos.
- Homologar em aparelho Xiaomi físico, sempre em paisagem, com teclado, barras do navegador, rotação acidental e reconexão offline.
