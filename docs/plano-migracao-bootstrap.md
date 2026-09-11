# Plano detalhado de redução de CSS e adoção de Bootstrap

Revisão: 10/09/2026. Repositório: SGI. Este documento substitui o plano anterior e orienta os próximos incrementos. **Planejamento atualizado; migração ainda parcial.**

## 1. Objetivo e decisões

Remover o máximo de CSS próprio que possa ser substituído por componentes, helpers e utilitários nativos do Bootstrap 5.3.8, preservando funcionalidades, acessibilidade e a identidade essencial do site. Aceitar mudanças em espaçamentos, cantos, sombras, tamanhos, disposição dos cards e efeitos decorativos.

A ordem de preferência é:

1. HTML semântico e componente nativo adequado.
2. Grid, helpers e utilitários já disponíveis no Bootstrap.
3. Pequeno tema global via variáveis Sass.
4. Variáveis CSS documentadas do componente, somente quando necessárias.
5. CSS local justificado por um requisito que as alternativas anteriores não atendam.

Preservar marca, vermelho principal e tipografia consistente. Não reproduzir cada detalhe antigo. Não criar classes como `.sgi-card` que copiem Bootstrap, nem usar `@extend` para esconder utilitários. Não mover regras para estilos inline, JavaScript ou um mapa Sass gigante para aparentar redução.

“Sem quebrar” será um critério de validação, não uma garantia antecipada: nenhum lote é concluído sem evidências dos fluxos afetados. A publicação depende da suíte integrada completa.

## 2. Estado real e ponto de partida

Já existem Bootstrap tematizado, separação dos bundles e migrações pontuais de filtros, botões, login e ranking. Há contratos automatizados de assets e testes isolados de componentes. Isso não comprova cobertura de todas as telas.

A última auditoria identificou oportunidades concretas:

- `.perfil-toast` e `.toast-sgi`: adotar Toast e seu ciclo de vida.
- `.skeleton-*`: adotar placeholders.
- `.mc-action-btn`, `.mc-duration-select` e botões de pontuação: adotar botões e selects nativos.
- `.dash-card`, `.ocr-card` e cards do portal: adotar card e remover decoração redundante.
- `utilities.css`: eliminar aliases e utilitários arbitrários substituíveis.
- Reavaliar regras remanescentes de layout, navegação, estados e responsividade, mesmo quando ainda têm consumidores.

Na revisão anterior, build, verificações PHP/JavaScript e testes isolados passaram. A primeira execução integrada em Docker chegou a 478 de 479 asserções: o único erro era do próprio `TestClient`, que não expunha o header `Content-Type` para o teste do novo bundle Bootstrap. O suporte de teste foi corrigido para capturar o MIME real; a repetição concluiu 479/479 asserções HTTP e a suíte de navegador concluiu 50/50 cenários.

### Medição disponível

Valores atuais da auditoria do recorte de seis fontes. A série histórica abaixo mantém como base comparável o ponto de 75.954 bytes e 2.512 linhas.

| Fonte em resources/css/source | Linhas | Bytes |
|---|---:|---:|
| admin.css | 576 | 20.585 |
| aluno-pages.css | 178 | 1.170 |
| aluno-home.css | 35 | 912 |
| aluno-shared.css | 80 | 1.544 |
| utilities.css | 68 | 1.260 |
| login.css | 106 | 2.302 |
| Total dessas seis fontes | 1.043 | 27.721 |

Esse total não inclui o SCSS próprio. Não apresentá-lo como total de personalização do projeto. O histórico de seis fontes tinha 263.997 bytes, mas não é uma comparação completa quando regras foram transferidas para SCSS.

Registrar por lote:

- Bytes e declarações do CSS/SCSS próprio, incluindo estilos inline e estilos gerados por JavaScript identificados no inventário.
- Bytes dos bundles entregues e tamanho gzip/Brotli sob configuração idêntica.
- CSS efetivamente carregado por contexto: login, administração e aluno.
- Quantidade de exceções customizadas e sua justificativa.
- Redução absoluta e percentual sobre uma base fixa e reproduzível.

Manter framework e bibliotecas de ícones em métricas separadas. Não contar minificação, troca de finais de linha ou mudança de arquivo como eliminação de regras. Não fixar percentual de redução antes do inventário.

### Execução registrada nos lotes implementados

Os lotes implementados cobrem partes das etapas 2, 3, 4, 5, 6, 7 e 8, sempre migrando o consumidor e removendo a regra substituída no mesmo diff:

- feedback transitório de perfil, turma e equipe centralizado em `window.SGI.showToast`, com Toast nativo, live region e texto seguro;
- skeletons de perfil e turmas substituídos por `placeholder-glow`/`placeholder`;
- cards de ocorrências, arrecadação e dashboard convertidos para `card`, `badge`, `input-group`, botões e utilitários Bootstrap;
- controles de placar preservando hooks JavaScript e usando `btn`, `form-select` e `btn-outline-*`;
- grids de equipe convertidos para `row`, `row-cols-*` e `g-3`;
- aliases utilitários substituíveis e handlers inline de hover removidos;
- exceções restantes em `utilities.css` limitadas a geometria de domínio, offsets fixos e valores dinâmicos documentados no próprio arquivo;
- portal do aluno (home, jogos, modalidade, perfil e ranking) convertido para cards, filtros, modais, progressos, badges e grids nativos;
- equipes, elencos, turmas, colaboradores, arrecadação e ocorrências convertidos para cards, listas, tabelas, input groups e estados Bootstrap;
- detalhes de modalidade e configuração de pontuação convertidos para cards, grids, alertas e controles Bootstrap, preservando os hooks de comportamento;
- agenda convertida para controles, filtros, cards, estados vazios, colunas e modais Bootstrap; a grade do calendário e a faixa lateral de status permanecem como geometria/semântica de domínio;
- locais e regulamento convertidos para cards, ações nativas, bordas de cláusulas e estados Bootstrap, removendo os blocos CSS duplicados correspondentes;
- lista administrativa de jogos convertida para cards, links, estados e badges Bootstrap;
- chaveamento convertido para cards, filtros, tabelas, ações, estados, modal de edição e feedback Bootstrap; a geometria da árvore, conectores e semântica de partidas permanecem como exceções de domínio;
- shell do chaveamento (contêiner, cabeçalho e métricas) convertido para `row`, `card`, `bg-*-subtle` e utilitários Bootstrap, removendo a camada genérica `kv-*`;
- revisão não geométrica da árvore concluída: fases, cabeçalhos de rodada, campeão, pódio individual e metadados/status das partidas usam componentes Bootstrap, preservando os hooks do mesário e as colunas/conectores do domínio;
- pódio do resultado individual no placar convertido para grid/cards Bootstrap, e a validação de acréscimos passou a usar `is-invalid` em vez de estilo inline;
- tela administrativa de modalidades e modal de alunos destaques convertidos para cards, badges, grids e cabeçalho Bootstrap, mantendo apenas `modalidade-card-simples` como hook de seleção/âncora;
- gestão de alunos da turma convertida para cabeçalho, busca, cards, tabela, ações, modal de detalhes e importação PDF Bootstrap, removendo o bloco dedicado `ta-*`;
- controles de timer e confronto do placar convertidos para bordas, grids, colunas, tipografia e utilitários Bootstrap, preservando os hooks `mc-*` de comportamento/teste e a geometria específica dos números e botões;
- contêiner do placar e alinhamento dos botões de pontuação convertidos para `card`, `rounded`, `shadow`, flex e tipografia Bootstrap, preservando apenas o overlay de tempo esgotado e as dimensões responsivas das áreas de toque;
- tabelas compartilhadas de chaveamento, ocorrências e arrecadação convertidas para as classes nativas `table`, `table-hover` e `align-middle`, removendo o segundo componente visual `sgi-table`;
- avatares dos perfis administrativo e do aluno convertidos para moldura, posicionamento, ícone e ação de câmera Bootstrap, mantendo no CSS apenas os dois tamanhos específicos do componente;
- avatares da navegação administrativa e do portal do aluno convertidos para imagem/fallback, borda, raio, alinhamento e cores Bootstrap, mantendo no CSS apenas as dimensões desktop/mobile;
- timeline do placar convertida para `card`, `card-body`, tipografia, cores, flex e espaçamento Bootstrap, mantendo no CSS somente o trilho, ponto, linha e revelação contextual das ações;
- placar convertido para cabeçalho, status, ações, chips, estados, modais e cards de artilharia Bootstrap; permanecem apenas a geometria do placar, dimensões das áreas de toque, timeline e posicionamento do botão flutuante;
- ações e penalidades da timeline do placar convertidas para `btn`, `badge` e utilitários Bootstrap; permanece apenas a regra de revelar os comandos quando a ocorrência recebe foco/hover;
- revisão final dos efeitos não funcionais do placar concluída: expiração usa `text-danger`, o alerta usa borda Bootstrap e foram removidas pulsações, transformações de hover/active e animações decorativas, mantendo apenas dimensões, overlay, trilho e revelação contextual;
- animação visual legada `kv-animate` removida dos estados do chaveamento, sem substituir comportamento funcional por outro alias próprio;
- blocos CSS órfãos de perfil, turmas, colaboradores, OCR, ranking e detalhes de modalidade removidos na mesma alteração dos consumidores;
- seleção de categorias e ocultação de linhas do chaveamento convertidas para estados utilitários Bootstrap, removendo o estilo de status órfão e os overrides dos filtros nativos;
- banner offline dos contextos administrativo e aluno convertido para contêiner, badge, botões, cores e ocultação Bootstrap, mantendo os hooks de runtime/teste e apenas o posicionamento fixo e o deslocamento do conteúdo em CSS;
- overrides móveis redundantes do dashboard/locais e estilos genéricos de scrollbar/hover de tabelas removidos, preservando no CSS somente o overflow, a largura mínima e o suporte de toque do histórico de jogos;
- troca de cartões de equipes preservada com os estados funcionais de visibilidade, removendo a animação decorativa e seu keyframe próprio;
- tipos de ocorrência do placar usam apenas variants `btn-outline-*` nativos, removendo o variant roxo próprio da suspensão;
- estado ativo da navegação mantém seus hooks e ícones sem escala, sombra ou transições decorativas próprias;
- cards e times da árvore do chaveamento mantêm estados esportivos e revelação de ações, sem transformações, sombras ou hover decorativo;
- estado de posição da árvore mantém a borda de destaque e remove o gradiente decorativo;
- modal de termo do portal do aluno usa `border-0`, `shadow`, `bg-primary` e `text-white` nativos, removendo seus overrides específicos;
- shell compartilhado mantém somente tokens de geometria usados e remove a animação de entrada de página e tokens visuais sem consumidores;
- seletor pesquisável do chaveamento e calendário da agenda preservam abertura, seleção, foco e estados de data sem transições decorativas próprias;
- testes estáticos, de JavaScript e de navegador ampliados para impedir o retorno dos padrões removidos.

Antes dos lotes de telas finais, as seis fontes CSS auditadas totalizavam 75.954 bytes e 2.512 linhas. O lote do shell do chaveamento reduziu o total para 73.087 bytes e 2.422 linhas; o lote da timeline e da limpeza de seção órfã reduziu para 72.329 bytes e 2.412 linhas; o sublote visual da árvore reduziu o total para 66.089 bytes e 2.232 linhas; o lote do pódio individual e da remoção do alias de animação reduziu para 65.716 bytes e 2.221 linhas; o lote de modalidades e destaques reduziu para 56.898 bytes e 1.826 linhas; o lote do layout interno do placar reduziu para 55.853 bytes e 1.812 linhas; o lote da timeline reduziu para 54.085 bytes e 1.792 linhas; o lote de alunos da turma reduziu para 41.762 bytes e 1.569 linhas; o lote do contêiner e dos controles avançados do placar reduziu para 41.145 bytes e 1.566 linhas; o lote das tabelas compartilhadas reduziu para 40.277 bytes e 1.532 linhas; o lote dos avatares de perfil reduziu para 36.802 bytes e 1.366 linhas; o lote dos avatares da navegação reduziu para 35.851 bytes e 1.338 linhas; o lote de revisão dos efeitos do placar reduziu para 34.404 bytes e 1.311 linhas; o lote de seleção e filtros reduziu para 33.981 bytes e 1.288 linhas; o lote do banner offline reduziu o total para 31.523 bytes e 1.176 linhas; o lote de limpeza de overrides reduziu para 30.208 bytes e 1.126 linhas; o lote de estados visuais reduziu para 30.032 bytes e 1.122 linhas; o lote de variants de ocorrência reduziu para 29.049 bytes e 1.086 linhas; o lote de efeitos de navegação reduziu para 28.678 bytes e 1.067 linhas; o lote de hover do chaveamento reduziu para 28.302 bytes e 1.057 linhas; o lote do modal de termos reduziu para 28.099 bytes e 1.048 linhas; o lote do seletor pesquisável e do calendário reduziu para 27.773 bytes e 1.043 linhas; este lote do gradiente do estado de posição reduz o total para 27.721 bytes e 1.043 linhas, uma redução adicional de 52 bytes (0,19%) e nenhuma linha. Em relação à base fixa registrada acima, a redução acumulada é de 48.233 bytes (63,50%) e 1.469 linhas (58,48%). A medição continua separada dos bundles Bootstrap e do SCSS próprio.

No SCSS próprio compartilhado, medido separadamente do recorte acima, este lote reduziu `shared.scss` de 3.159 para 1.806 bytes e de 113 para 73 linhas: menos 1.353 bytes (42,83%) e 40 linhas (35,40%).

A matriz Docker funcional foi executada após a correção final: 479/479 asserções HTTP e 50/50 cenários de navegador, incluindo fluxos online, offline e responsivos. O contrato visual separado permanece pendente de referências Linux: `tests/browser/visual-contract.spec.cjs-snapshots` contém somente referências Windows (`*-win32.png`), portanto o Docker não possui baseline `*-linux.png` versionado para comparação.

Permanecem para os próximos lotes a revisão final da pontuação avançada e da geometria restante do placar/árvore, a auditoria das exceções customizadas restantes e as referências Linux do contrato visual. Agenda, locais/regulamento, modalidades, controles de pontuação, o pódio individual, o layout base do confronto, a timeline, os efeitos decorativos da navegação e do chaveamento e o modal de termos já foram tratados. Na agenda e no chaveamento, permanecem somente geometria, conectores e acentos sem equivalente Bootstrap como exceções documentadas; dimensões de toque, números do placar e layout avançado continuam sob revisão. A migração global só será marcada como concluída quando as pendências forem tratadas, cada exceção estiver justificada e houver referências para o contrato visual.

## 3. Contratos que precisam ser preservados

- IDs, atributos data, nomes de campos, relações label/controle e seletores usados por eventos.
- Estados disabled, checked, selected, hidden, validação, carregamento e autorização.
- Submissões, CSRF, regras de inscrição, cálculos, ordenação, filtros e ações por perfil.
- Foco, navegação por teclado, nomes acessíveis, anúncios de erro e feedback.
- Cronômetro, placar, cartões, ocorrências, classificação e avanço de chaveamento.
- Identificadores e schema de mutações offline, persistência local e sincronização sem duplicação.
- Inicialização e descarte de componentes quando a casca SPA troca de tela.

Quando uma classe misturar estilo e comportamento, preservar o contrato inicialmente ou migrar todos os consumidores no mesmo lote. Não apagar um seletor apenas por não aparecer literalmente em PHP: classes podem ser construídas no JavaScript e estar em HTML carregado do cache.

A casca offline requer preparação online prévia; não declarar suporte a primeiro acesso totalmente offline. Atualizações de assets/cache não podem apagar mutações pendentes.

## 4. Inventário obrigatório

Criar uma matriz versionada com uma linha por tela/componente e estes campos:

| Campo | Conteúdo |
|---|---|
| Origem | Template PHP, renderer JavaScript e CSS/SCSS relacionados |
| Uso | Rota real, perfis autorizados e navegação direta/SPA |
| Estados | Normal, vazio, carregamento, erro, validação e estados específicos |
| Substituição | Recurso Bootstrap escolhido e diferença visual aceita |
| Contratos | IDs, data attributes, eventos, foco e persistência |
| Exclusão | Seletores e declarações a remover no mesmo lote |
| Exceção | Requisito concreto, arquivo e razão para manter CSS |
| Evidência | Testes existentes, testes faltantes e resultado antes/depois |

Usar busca de código e cobertura CSS do navegador como apoio. Cobertura de uma visita não autoriza exclusão: combinar telas, perfis, estados, breakpoints, impressão e renderização dinâmica.

## 5. Mapa de substituições

| Padrão atual | Destino preferido | Cuidados |
|---|---|---|
| Containers e alinhamentos próprios | container/container-fluid, row/col, flex e gap | Manter ordem de leitura e evitar overflow |
| Espaçamentos e utilitários arbitrários | Escalas m/p/gap e utilitários responsivos | Aceitar aproximação; não criar um utilitário para cada pixel |
| Botões próprios | btn e variantes, btn-sm/btn-lg, grupos | Manter tipo, nome acessível, estados e eventos |
| Inputs/selects/switches | form-control, form-select, form-check/form-switch | Manter label, validação, valor e foco |
| Grupos de campos | input-group, grid e form-text | Não substituir semântica por decoração |
| Cards e painéis | card, card-body/header/footer | Remover sombras, bordas e hover redundantes |
| Tags de estado | badge e variantes semânticas | Comunicar estado também por texto |
| Mensagens persistentes | alert | Não esconder erros necessários à correção |
| Notificações transitórias | toast e Toast API | Live region, duração, fechamento e descarte |
| Skeletons/spinners próprios | placeholder-glow/wave e spinner-border/grow | Marcar carregamento; evitar conteúdo falso acessível |
| Diálogos e drawers | modal/offcanvas | Foco, Escape, backdrop e scroll |
| Abas/expansões | nav-tabs e Tab, accordion/collapse | Estado e navegação acessíveis |
| Tabelas/listagens | table, table-responsive, list-group | Cabeçalhos e ações acessíveis em telas estreitas |
| Navegação | navbar/nav e offcanvas/collapse | Compatibilidade com a casca e destaque da rota ativa |
| Progressos/avatares/imagens | progress, ratio, rounded e object-fit quando aplicáveis | Usar apenas recursos existentes na versão instalada |
| Ranking/pódio decorativo | Tabela/lista/cards e badges | Simplificar apresentação mantendo posição e pontuação |
| Dropdown de seleção próprio | form-select se seleção simples | Bootstrap não oferece select pesquisável nativo |
| Calendário/chaveamento/cronômetro | Controles Bootstrap; CSS mínimo para estrutura específica | Bootstrap não implementa esses widgets de domínio |

Usar primary para destaque da identidade e ação principal; danger para erro, risco ou ações destrutivas conforme contexto. Não recolorir todos os estados com o vermelho da marca.

## 6. Execução em dez etapas

Cada etapa pode ser dividida em lotes pequenos por componente ou tela. Concluir um lote significa migrar consumidores, apagar regras substituídas e validar, sem acumular uma folha de compatibilidade permanente.

### Etapa 0 — Congelar a base e habilitar os testes

1. Registrar o diff atual sem descartar alterações existentes.
2. Preparar servidor e banco descartáveis conforme docs/testing.md.
3. Executar a suíte completa exigida por AGENTS.md antes da refatoração.
4. Registrar falhas preexistentes separadamente; resolver bloqueios de ambiente antes de certificar equivalência.
5. Capturar métricas, telas e estados representativos nos ambientes de screenshot suportados.
6. Preencher o inventário de todas as telas listadas na seção 7.

Saída: base mensurável, testes executáveis e lacunas de cobertura conhecidas.

### Etapa 1 — Consolidar tema e entrega de assets

1. Manter um único bootstrap-theme, compartilhado pelos contextos.
2. Revisar _theme.scss e shared.scss: tema mínimo, sem recriar escalas e componentes.
3. Auditar tokens --sgi/--aluno e substituir equivalentes por --bs onde aplicável; apagar tokens sem consumidores.
4. Conferir ordem, duplicações e dependências nos heads e em tools/css-bundles.json.
5. Comparar build limpo e incremental; verificar manifesto e ausência de assets antigos ainda referenciados.
6. Auditar tratamento de arquivos bloqueados no build: não aceitar sucesso com asset desatualizado.
7. Manter Bootstrap completo nesta migração. Imports seletivos são uma otimização posterior, condicionada a inventário e testes próprios.

Saída: identidade centralizada e entrega reproduzível. Não prometer menor download apenas porque o CSS próprio diminuiu.

### Etapa 2 — Eliminar utilitários e decoração substituíveis

1. Mapear cada regra de utilities.css e equivalentes nos demais arquivos.
2. Substituir display, flex, grid, gaps, margens, padding, texto, bordas, arredondamentos, sombras e visibilidade por utilitários nativos.
3. Aproximar medidas à escala Bootstrap sempre que não houver requisito funcional.
4. Remover gradientes, animações e hover puramente decorativos.
5. Migrar PHP e renderers JavaScript juntos; preservar classes de comportamento.
6. Apagar imediatamente os aliases substituídos e verificar cascata/media queries.

Saída: nenhum utilitário próprio equivalente mantido sem justificativa. Não criar novas classes utilitárias para preservar aparência.

### Etapa 3 — Padronizar controles e formulários

1. Migrar botões, grupos de ações, filtros, selects, inputs e switches.
2. Remover overrides de hover/focus/active/disabled e cores próprios dos controles.
3. Usar layout responsivo nativo para ações que precisam ocupar a largura no celular.
4. Simplificar seletores customizados para form-select quando não houver busca obrigatória.
5. Manter feedback de validação visível e associado ao campo; preservar validação do servidor.
6. Testar envio por teclado, estado ocupado, erro, edição, cancelamento e permissões.

Saída: controles genéricos usam o estilo e os estados nativos; exceções dimensionais ficam justificadas.

### Etapa 4 — Padronizar feedback e componentes interativos

1. Migrar perfil-toast/toast-sgi para markup e API Toast.
2. Migrar skeletons e indicadores para placeholders/spinners.
3. Padronizar alertas e estados vazios usando componentes e utilitários.
4. Revisar modais, offcanvas, collapse, tabs e dropdowns usados no projeto.
5. Remover temporizadores e animações visuais redundantes sem remover a lógica de negócio.
6. Usar instâncias de forma consistente, evitando listeners, backdrops ou notificações duplicadas na SPA.
7. Testar foco inicial/restaurado, Escape, fechamento e cliques repetidos.

Saída: componentes interativos com ciclo de vida correto no acesso direto e na navegação repetida.

### Etapa 5 — Migrar telas administrativas de menor risco

1. Migrar acesso/perfil/colaboradores e estrutura das listas.
2. Migrar eventos/lista, dashboard, categorias e configurações.
3. Migrar participantes, equipes e modalidades.
4. Trocar dash-card/ocr-card e painéis semelhantes por card, grid e listas/tabelas nativas.
5. Preservar fluxo de importação, validação de arquivo e apresentação de resultados.
6. Remover regras específicas de cada tela que ficaram redundantes.

Saída: telas administrativas comuns cobertas por testes de leitura, edição e erro, conforme sua função.

### Etapa 6 — Migrar portal do aluno e navegação compartilhada

1. Revisar login, home, perfil, termos, modalidade, jogos e ranking.
2. Padronizar cards, avisos, filtros, estatísticas e ações de inscrição.
3. Migrar admin-nav/admin-header/aluno-nav e footer para estruturas nativas onde adequado.
4. Manter CSS de offsets/posicionamento apenas se a arquitetura da navegação exigir.
5. Testar menus no celular, rota ativa, edição do perfil, termos e regras de inscrição.
6. Conferir que mudanças compartilhadas não alterem indevidamente o login ou o perfil mesário.

Saída: portal e navegação usam uma linguagem visual consistente, com acesso às mesmas funções.

### Etapa 7 — Migrar agenda, resultados e disciplina

1. Padronizar listas de jogos, filtros, ranking, arrecadações e ocorrências.
2. Simplificar pódios e estatísticas decorativos para estruturas nativas.
3. Padronizar ações/legendas da agenda; preservar geometria somente quando necessária.
4. Preservar impressão do ranking, hierarquia dos dados, totais e estados de jogo.
5. Testar ordenação, filtros combinados, paginação quando existir, nomes longos e edição de ocorrências.
6. Executar regressões offline dos fluxos disponíveis ao mesário já neste lote.

Saída: dados e operações equivalentes, com CSS restrito ao que não cabe nos componentes padrão.

### Etapa 8 — Migrar placar e chaveamento

1. Separar CSS decorativo de geometria e estados funcionais.
2. Migrar mc-action-btn, mc-duration-select, pontuação e demais controles para btn/form-select.
3. Manter legibilidade do placar e tamanho adequado das áreas de toque.
4. Preservar somente regras necessárias ao alinhamento de rodadas/conectores e estrutura específica.
5. Testar cronômetro, persistência após navegação/recarregamento, pontuação, cartões, encerramento e avanço de vencedor.
6. Testar aquecimento online, operação offline, fila pendente, reconexão e sincronização sem duplicação.
7. Testar atualização de assets com dados pendentes sem limpar armazenamento funcional.

Saída: máximo uso de Bootstrap nos controles, sem tentar substituir regras esportivas por componentes inexistentes.

### Etapa 9 — Remoção final, certificação e prevenção

1. Repetir inventário e busca por overrides, aliases, !important, estilos inline e CSS gerado por JavaScript.
2. Revisar cada regra restante e registrar requisito, consumidores e testes.
3. Apagar seletores órfãos, declarações sobrepostas, media queries vazias, tokens e arquivos sem uso.
4. Atualizar bundles e manifesto; conferir referências e build limpo.
5. Avaliar bibliotecas de ícones duplicadas separadamente: remover apenas após migrar todos os consumidores.
6. Executar a matriz completa, comparar métricas e revisar mudanças visuais intencionais.
7. Adicionar controles de CI para impedir retorno de aliases e cópias de componentes; manter exceções explícitas.
8. Registrar relatório final com redução real, CSS mantido e evidências de validação.

Saída: nenhum CSS próprio genérico sem justificativa, nenhum consumidor quebrado conhecido e todas as condições de conclusão atendidas.

## 7. Cobertura de telas

Os nomes abaixo são templates em resources/views/pages. Resolver suas URLs pelo roteamento real durante a etapa 0; não assumir que o nome físico seja a URL.

| Grupo | Telas que precisam constar da matriz | Cenários específicos |
|---|---|---|
| Acesso | acesso/login, perfil, colaboradores; aluno/login | Login válido/inválido, validação, edição e restrições |
| Evento | eventos/lista, dashboard, categorias | Alternar edição, vazios, cards e filtros |
| Configuração | eventos/configurar-agenda, arrecadacao, categorias, equipes, locais, modalidades, pontuacao, resumo, turmas | Edição, cancelamento, salvar/erro e feedback |
| Participantes | participantes/turmas, turma-alunos | Listagem, importação quando disponível, nomes longos |
| Equipes/modalidades | competicoes/modalidades, modalidade-detalhes, equipe-alunos, elenco-equipe | Filtros, inscrições/elenco e ações permitidas |
| Operação | competicoes/jogos, placar, chaveamento | Estados da partida, relógio e avanço online/offline |
| Resultados/disciplina | resultados/ranking; disciplina/ocorrencias | Pontuação, ordenação, impressão, edição e sincronização |
| Portal | aluno/home, jogos, modalidade, perfil, ranking, termos | Regras de inscrição, termos, vazios e edição |
| Compartilhados | heads, admin-header, admin-nav, aluno-nav, page-title, footer | Todos os contextos, menu responsivo e navegação repetida |

Cobrir administrador, colaborador, mesário e aluno somente nas rotas autorizadas, incluindo testes que confirmem restrições. Descobrir também páginas/estados gerados por JavaScript sem template próprio.

Para cada tela: normal e vazio; carregamento, erro e validação quando aplicáveis; conteúdo extenso; permissão e ações relevantes. Para cada componente compartilhado: estados interativos e ambos os contextos consumidores.

## 8. Estratégia de testes e aprovação de lotes

### Ambiente e comandos

Seguir AGENTS.md e docs/testing.md. Nunca executar fixtures/resets no banco de uso real. Não executar duas suítes que alteram a mesma base simultaneamente.

Fluxo Docker documentado:

```powershell
powershell -File tools/test-docker.ps1 -Database mariadb -IncludeVisual
```

Validar compatibilidade MySQL conforme o procedimento de testes do projeto. Se usar execução manual, preparar servidor/base isolados e as variáveis SGI_TEST_BASE_URL e SGI_BASE_URL conforme o guia.

Verificações antes/depois da refatoração:

```text
npm run build
npm run check
npm test
composer verify
php tests/run_all.php
npm --prefix tests/browser test
git diff --check
```

Executar testes direcionados enquanto desenvolve e a suíte completa nos pontos de aprovação exigidos pelo repositório. Uma falha de infraestrutura permanece “não validado”; não equivale a teste aprovado.

### Automatização

Reaproveitar frontend-regression, admin-lifecycle, auth-rbac, aluno-portal, individual-ranking, score-persistence, clock-persistence, visual-contract e as suítes offline existentes. Ampliar cenários faltantes; não presumir que os nomes dessas suítes cobrem toda a matriz.

- Testes de componente: estados normal/hover/foco/disabled/checked, modal/toast e carregamento.
- Testes funcionais: interação real com resultado persistido, e não apenas presença de classe CSS.
- Testes visuais: páginas e estados representativos de cada família; inspecionar diferenças antes de atualizar referências.
- Testes estáticos: detectar violações arquiteturais conhecidas; não usá-los como prova de equivalência visual.
- Testes offline: fila preservada, mutações sincronizadas uma vez e navegação repetida sem listeners duplicados.

### Responsividade e acessibilidade

Cobrir todas as telas em mobile e desktop; ampliar componentes compartilhados nos breakpoints 360, 390, 768, 1024 e 1440 px. Inspecionar transições relevantes imediatamente antes/depois dos breakpoints usados.

Verificar reflow em 320 px e zoom de 200%, teclado, foco visível, leitura de erros, reduced motion e contraste. Não exigir ausência de rolagem interna em chaveamento/tabela que realmente necessite dela; impedir que ela torne a página inteira ou os controles inacessíveis.

Aprovar mudanças visuais pela legibilidade, consistência e operação. Não restaurar CSS antigo apenas para reproduzir screenshots. Também não regenerar todas as referências para ocultar regressões.

## 9. CSS permitido ao final

Manter apenas exceções documentadas, por exemplo:

- Tema mínimo de identidade.
- Geometria indispensável de chaveamento e agenda.
- Legibilidade/dimensões específicas do placar quando utilitários existentes não atendam.
- Posicionamento exigido pela casca offline e navegação, após avaliar componentes nativos.
- Regras de impressão ou acessibilidade sem equivalente adequado.

Cada exceção deve responder: qual requisito atende, por que Bootstrap não resolve, onde é usada e como foi validada. Ser “diferente do desenho antigo” não justifica CSS próprio.

Evitar PurgeCSS automático como mecanismo inicial de remoção. Se adotado posteriormente, exigir inventário de classes dinâmicas, proteção dos estados relevantes e regressões completas.

## 10. Critérios de conclusão e reversão

A migração só pode ser declarada concluída quando:

- Todas as telas e estados aplicáveis da matriz tiverem evidência de revisão.
- Componentes genéricos usarem Bootstrap e não conservarem uma segunda implementação visual.
- Toda regra própria remanescente tiver justificativa verificável.
- Não houver aliases, tokens, imports ou seletores órfãos identificados.
- Build e suíte completa passarem no ambiente isolado, incluindo operação offline.
- O relatório comparar as mesmas métricas antes/depois, incluindo o SCSS próprio.
- Mudanças visuais intencionais estiverem registradas e as referências revisadas.

Entregar por lote mudanças coerentes de templates, renderers, CSS, testes e manifesto. Registrar um ponto de retorno testado para cada lote. Em caso de regressão, reverter somente o lote responsável, preservando as alterações preexistentes e os dados do usuário.

Não fazer migração de banco nem limpar IndexedDB como parte da redução de CSS. Publicar HTML, JS e CSS da mesma versão; verificar o ciclo de cache da casca com mutações pendentes.

## Referências

- Bootstrap 5.3: https://getbootstrap.com/docs/5.3/getting-started/introduction/
- Personalização Sass: https://getbootstrap.com/docs/5.3/customize/sass/
- Utilitários: https://getbootstrap.com/docs/5.3/utilities/api/
- Formulários: https://getbootstrap.com/docs/5.3/forms/overview/
- Toasts: https://getbootstrap.com/docs/5.3/components/toasts/
- Modais: https://getbootstrap.com/docs/5.3/components/modal/
- Placeholders: https://getbootstrap.com/docs/5.3/components/placeholders/
- Regras locais: AGENTS.md, docs/testing.md e tests/browser/README.md.
