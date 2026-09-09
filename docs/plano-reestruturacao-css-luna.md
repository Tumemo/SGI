# Plano de reestruturação dos estilos do SGI para implementação pelo Luna

Data: 09/09/2026. Estado: implementado; detalhes e pendências registrados em HOM-013.

## 1. Decisão e objetivo

**Manter o Bootstrap 5.3.8 já instalado e reorganizar o CSS próprio em arquivos por responsabilidade, gerando poucos arquivos finais locais.** Priorizar previsibilidade da cascata, nomes compreensíveis e preservação visual. Não migrar para outro framework nesta entrega.

O SGI já utiliza Bootstrap no HTML e chamadas `bootstrap.Modal` e `bootstrap.Tooltip` no JavaScript. O problema observado não é falta de framework: é a sobreposição de estilos próprios e a falta de fronteiras entre temas, componentes e telas.

Escopo deste documento: registrar a decisão técnica, o mapa de migração e os critérios usados na implementação. A refatoração preserva funcionalidades, aparência atual, permissões, rotas, identificadores DOM usados pelo JS, atributos `data-bs-*`, contratos das APIs e dados offline pendentes. A entrega não inclui redesign, migração de frontend ou atualização de dependências em massa.

## 2. Diagnóstico baseado no código atual

As contagens abaixo são a referência textual anterior à implementação; incluem comentários/linhas em branco e consideram a cópia de trabalho que já continha alterações não commitadas. Os números pós-refatoração estão registrados em `docs/frontend-styles.md`.

| Arquivo em resources/css | Linhas aproximadas | Bytes | Ocorrências de !important | Ocorrências de seletores sgi-inline |
| --- | ---: | ---: | ---: | ---: |
| style.css | 6.036 | 196.865 | 165 | 0 |
| style-utilities.css | 831 | 12.578 | 0 | 160 |
| style-migrated.css | 2.012 | 47.123 | 3 | 160 |
| aluno.css | 483 | 9.978 | 1 | 0 |
| aluno-page.css | 901 | 22.091 | 22 | 0 |

São aproximadamente 10,3 mil linhas e 289 KB de fontes próprias, sem contar Bootstrap, ícones, compressão HTTP ou CSS produzido pelo JS. Esses números não medem o tráfego efetivo por página.

Evidências relevantes:

- `resources/views/components/admin-head.php` carrega `style.css` e `style-utilities.css` em todas as telas administrativas e do mesário. O login carrega o mesmo conjunto, incluindo estilos de competições que não utiliza.
- `resources/views/components/aluno-head.php` carrega `aluno.css`, `style-migrated.css` e `aluno-page.css`, nessa ordem. Todos os estilos de jogos, modalidades, perfil e ranking do portal são globais nesse contexto.
- `aluno.css` e o início de `aluno-page.css` repetem tokens e regras como `.aluno-page` e `.aluno-hero`. Há redefinições de `--aluno-primary` e `--aluno-text-muted` entre arquivos. A ordem atual determina valores efetivos; não basta conservar a primeira declaração encontrada.
- Os dois arquivos de migração/utilitários contêm classes opacas `sgi-inline-*`. Auditar equivalência declaração por declaração antes de unificar; contagens iguais não provam equivalência integral.
- `style.css` mistura reset, variáveis, Bootstrap, navegação, login, ranking, turmas, agenda, chaveamento e placar. Seu sumário menciona nomes antigos de templates.
- Há sobrescritas globais de `body, main`, peso de títulos e até `.fw-bold`, `.fw-bolder` e `.fw-semibold`. Isso dificulta prever o resultado de utilitários Bootstrap.
- `style.css` importa Inter do Google Fonts. A família de fonte é diferente no portal do aluno. Não uniformizar tipografia silenciosamente.
- `tools/build-assets.cjs` atualmente copia fontes e dependências e calcula o manifesto; não compõe CSS. `src/Shared/Http/Assets.php` calcula o hash do arquivo servido para versionar a URL.
- `resources/js/offline/mesario-offline.js` captura blocos `<style>` em `extrairScreen`, mas não captura folhas `<link rel="stylesheet">` das páginas baixadas. A montagem mantém o head da casca e substitui conteúdo. Dividir em CSS carregado somente por tela exigiria mudar esse contrato.
- O mesmo JS injeta `CSS_UI` e um estado de tela indisponível com estilos inline. Existem propriedades dinâmicas, como largura de progresso e visibilidade, que não devem ser removidas mecanicamente.
- Há quatro referências visuais do login em `visual-contract.spec.cjs`. A cobertura funcional de outras páginas não equivale a uma comparação visual de todas elas.

Limite da análise: inspeção de fontes e documentação; não houve execução da aplicação, comparação de screenshots, medição de cobertura CSS nem execução de testes nesta etapa de planejamento.

## 3. Avaliação de frameworks

| Alternativa | Benefício possível | Custo para o SGI | Decisão |
| --- | --- | --- | --- |
| Bootstrap atual + CSS organizado | Reutiliza grid, formulários, modais e utilitários existentes; permite personalização com variáveis | Exige corrigir as sobrescritas próprias | Recomendado |
| Bootstrap customizado com Sass | Permite alterar mapas, gerar utilitários e selecionar partes da distribuição | Adiciona compilador e responsabilidade de manter o tema compilado | Adiar; só adotar se houver limitação concreta das variáveis CSS |
| Tailwind CSS | Pode gerar apenas utilitários usados e oferecer convenções consistentes | Reescrita extensa de classes em PHP e HTML gerado por JS; nova compilação; componentes interativos precisam continuar atendidos | Não migrar nesta reorganização |
| Bulma | Oferece componentes e layout em um framework CSS | Troca de marcação; não substitui diretamente os contratos JS Bootstrap existentes | Sem vantagem suficiente para esta base |
| CSS próprio sem Bootstrap | Controle integral do estilo | Reimplementação e validação de componentes já funcionais | Não recomendado |

Fundamentação externa: Bootstrap oferece [variáveis CSS globais e de componentes](https://getbootstrap.com/docs/5.3/customize/css-variables/) e [customização por Sass](https://getbootstrap.com/docs/5.3/customize/sass/). A [detecção de classes do Tailwind](https://tailwindcss.com/docs/detecting-classes-in-source-files) depende de nomes completos detectáveis nas fontes; classes interpoladas exigem cuidado. A [visão geral do Bulma](https://bulma.io/documentation/start/overview/) descreve sua proposta. A avaliação do custo de migração é uma inferência aplicada ao código do SGI, não uma afirmação de superioridade universal do Bootstrap.

## 4. Arquitetura desejada

Manter CSS comum, sem Sass/PostCSS/Vite nesta entrega. Criar somente arquivos que recebam regras reais; a árvore é um mapa de responsabilidade, não um pedido para produzir arquivos vazios.

```text
resources/css/
  foundation/
    tokens.css
    base.css
  themes/
    admin.css
    aluno.css
    bootstrap.css
  layouts/
    admin-shell.css
    aluno-shell.css
    acesso.css
  components/
    page-header.css
    cards.css
    forms.css
    tables.css
    feedback.css
    ranking.css
  pages/
    acesso/                 # login, perfil, colaboradores
    eventos/                # dashboard/home e configurações: conferir consumidores
    participantes/          # turmas, turma-alunos, equipes quando aplicável
    competicoes/            # modalidades, jogos, agenda, chaveamento, placar
    resultados/             # especificidades de ranking e pontuações
    disciplina/             # ocorrências
    aluno/                  # home, jogos, modalidade, ranking, perfil, termos
  offline/
    status.css
  utilities.css
tools/
  css-bundles.json
```

Os diretórios de telas acompanham os templates existentes; a localização exata de agenda e configurações deve seguir `resources/views/pages`, sem mover módulos PHP para encaixar a árvore ilustrativa.

### Saídas e carregamento

- `public/assets/css/admin.css`: fundamentos usados no administrativo, tema, layout, componentes, telas administrativas/mesário, UI offline e utilitários pertinentes.
- `public/assets/css/aluno.css`: fundamentos pertinentes, tema/layout do aluno, componentes utilizados e telas do portal.
- `public/assets/css/login.css`: somente fundamentos e componentes utilizados no acesso, além do layout e regras do login.
- Bootstrap e bibliotecas de ícones continuam locais e anteriores ao CSS SGI. Não juntar fornecedores ao CSS próprio nem eliminar bibliotecas de ícones nesta etapa.
- `admin-head.php`, `aluno-head.php` e `pages/acesso/login.php` referenciam suas respectivas saídas usando `Assets::url`.
- O mesário recebe o pacote administrativo completo no carregamento inicial, preservando a disponibilidade de estilos na navegação da casca preparada. Não introduzir carregamento sob demanda, Service Worker ou um novo cache de CSS.

Organização dos fontes e quantidade de downloads são decisões diferentes. Fontes separados com um pacote por contexto resolvem manutenção sem introduzir dependências de rede durante a navegação offline. Um pacote exclusivo de mesário é uma otimização futura, condicionada a inventário completo de todas as telas acessíveis.

### Composição e ordem

`tools/css-bundles.json` deve conter listas explícitas, ordenadas e relativas a `resources/css`, uma por saída. Estender o build para ler essas listas e concatenar os arquivos com separador de nova linha e comentário identificando a origem. Validar existência, extensão e permanência dos caminhos dentro da pasta de fontes. Não usar descoberta alfabética como ordem da cascata.

Gerar saídas antes de calcular o manifesto. Preservar cópia de JS/imagens/fornecedores, licenças e proteção atual do diretório de saída. Não copiar fontes fragmentados como se fossem folhas públicas consumidas por telas. Não adicionar minificação nesta etapa: ela não resolve organização e complica a comparação inicial.

Revisar `url(...)` ao mover arquivos: caminhos relativos são resolvidos a partir do arquivo final em `public/assets/css/`, não da pasta do fragmento. Não introduzir `@import` de fragmentos no navegador. Imports externos existentes precisam permanecer no início da saída até seu tratamento explícito; um `@import` no meio de uma concatenação pode deixar de funcionar.

Na primeira extração, preservar rigorosamente a ordem efetiva antiga, inclusive repetições e media queries. A ordem final desejada é fundamentos → tema/adaptação Bootstrap → layout → componentes → telas → utilitários. Chegar a essa ordem por etapas verificadas, sem pressupor que a mudança seja visualmente neutra.

## 5. Convenções de manutenção

1. Tokens compartilhados usam `--sgi-*`, com nomes semânticos para marca, superfície, texto, borda, espaçamento, raio e sombra. Preservar diferenças intencionais entre contextos com temas. Inicialmente manter aliases das variáveis antigas; substituir e remover somente após verificar todos os consumidores, incluindo JS.
2. Componentes novos usam `.sgi-*`, por exemplo `.sgi-empty-state` e `.sgi-ranking-card`. Extrair componente compartilhado somente quando usos reais tenham estrutura e comportamento compatíveis. Regras com mesmo nome não são necessariamente o mesmo componente.
3. Regras de tela ficam restritas a uma raiz estável no próprio template, por exemplo `.sgi-page-placar`. Como a casca extrai `<main>`, colocar o escopo nesse elemento quando adequado. Modais/FABs fora dele precisam de classe própria de componente ou escopo explícito; não envolver arbitrariamente a página inteira e quebrar a extração offline.
4. Não depender de classe de página no `body` para telas SPA: o corpo da casca não é substituído. Para regras globais do contexto, classes fixas como `.sgi-admin` e `.sgi-aluno` são aceitáveis.
5. Preservar IDs e classes utilizados como seletores JS; adicionar classe visual separada quando necessário. Inventariar templates literais, `classList`, seletores e HTML produzido no JS antes de renomear.
6. Preferir utilitários Bootstrap já existentes apenas quando equivalentes no valor, breakpoint e prioridade. `max-width: 360px` não equivale a `w-100`; converter esse caso em classe semântica do componente.
7. Não remover `!important` em lote. Configurar variáveis `--bs-btn-*` para botões e variáveis de cor/RGB aplicáveis; alterar apenas `--bs-danger` não atualiza necessariamente todos os estados compilados. Testar hover, foco, ativo e desabilitado.
8. Não adicionar novos resets globais ou neutralizar `.fw-bold` globalmente. Antes de retirar as regras atuais, atribuir às telas afetadas a tipografia que hoje é visível, para preservar a aparência.
9. Manter breakpoints atuais durante extração. Padronizar posteriormente nos pontos Bootstrap apenas onde a comparação demonstrar equivalência; limites especializados do placar podem continuar específicos.
10. Remover CSS estático de strings JS para `offline/status.css`; manter valores calculados em runtime, como largura de progresso e coordenadas, quando necessários. Não trocar `style.display` por `d-none` sem revisar os fluxos: o `!important` do utilitário pode impedir a reexibição.
11. Evitar introduzir `@layer` nesta entrega: misturar Bootstrap não encapsulado com regras próprias em camadas altera precedência. Evitar remoção automática por cobertura/PurgeCSS: estados dinâmicos e offline podem não aparecer na amostra.
12. Manter inicialmente Inter remoto e o fallback existente; registrar seu impacto offline. Hospedar a fonte localmente é melhoria separada, exigindo licença, arquivos, caminho de build e comparação tipográfica. Não mudar fontes durante a extração.

## 6. Mapa de migração

| Origem | Destino/responsabilidade | Cuidados |
| --- | --- | --- |
| style.css: reset e variáveis | foundation + themes | Capturar valores efetivos e diferenças entre login/admin |
| style.css: sobrescritas Bootstrap | themes/bootstrap.css | Estados completos e prioridade dos utilitários |
| style.css: menu e estrutura | layouts/admin-shell.css | Responsividade, áreas fixas e conteúdo sob menu |
| style.css: blocos de telas | pages por módulo | Não confiar somente em comentários antigos; procurar usos |
| aluno.css + aluno-page.css: tokens/hero repetidos | tema aluno + componentes usados no portal | Conservar resultado da cascata atual, não a primeira cópia |
| style-migrated.css: menu, jogos e modalidades | layout aluno + pages/aluno | Isolar seletores genéricos como .page-header |
| aluno-page.css: ranking/perfil/termos | pages/aluno e componentes realmente comuns | Perfis e rankings semelhantes podem ter diferenças |
| style-utilities.css + hashes em style-migrated.css | Bootstrap, componentes ou utilities.css | Tabela hash → consumidor → substituição → validação |
| CSS_UI e HTML estilizado em mesario-offline.js | offline/status.css | Preservar IDs, eventos, visibilidade e progresso |

## 7. Execução em etapas pequenas para Luna

Não executar várias etapas de refatoração antes de validar a primeira. Ao fim de cada etapa, registrar arquivos, verificações, problemas preexistentes e próxima etapa neste documento ou em um registro associado. Não fazer commits automaticamente sem instrução para isso.

### E0 — Registrar a referência atual

- Ler `AGENTS.md`, `docs/testing.md` e este plano. Inspecionar `git status` e o diff atual; preservar integralmente alterações já existentes, inclusive correções recentes de CSS/login/chaveamento.
- Executar os checks da seção 8 antes de refatorar, utilizando banco isolado. Registrar falhas preexistentes; não atribuí-las automaticamente à reorganização.
- Criar inventário de regras/consumidores e da ordem de carregamento por contexto. Incluir estilos estáticos e dinâmicos produzidos no JS.
- Capturar screenshots com dados determinísticos, mesmos viewports e navegador da validação final. Abrir modais e estados relevantes, não somente páginas vazias.
- Saída: referência visual e tabela de migração; nenhum estilo removido por suposição.

### E1 — Preparar composição sem alterar a cascata

- Implementar as listas e composição no build. Temporariamente gerar os cinco nomes antigos a partir de fragmentos de transição com conteúdo equivalente; não carregar o CSS antigo junto de sua cópia nova.
- Comparar conteúdo útil/ordem dos pacotes com as fontes originais e executar o build duas vezes: mesmos arquivos devem produzir os mesmos hashes.
- Confirmar URLs e fontes/imagens/ícones sem erros. Fragmentos transitórios devem ter destino e etapa de remoção identificados, sem virar um novo arquivo genérico permanente.
- Saída: mecanismo de build validado, nenhuma diferença visual esperada.

### E2 — Extrair áreas preservando valores e ordem

- Dividir blocos por responsabilidade, mantendo a ordem anterior nas listas. Quando uma regra tardia corrige bloco anterior, preservar a posição até a consolidação explícita.
- Começar pelo acesso, depois portal do aluno, administrativo de cadastros e, por último, agenda/chaveamento/placar.
- Separar CSS do login do restante somente após conferir todos os seletores presentes nos dois formulários e estados de erro. Migrar seu link para `login.css`.
- Saída: fontes navegáveis por área, login leve, sem redesign.

### E3 — Consolidar portal do aluno

- Resolver tokens duplicados pelos valores computados atuais, extrair componentes confirmados e isolar regras específicas das seis telas.
- Gerar o pacote único `aluno.css`; atualizar `aluno-head.php` e eliminar o carregamento dos dois arquivos auxiliares quando todo o conteúdo necessário estiver incorporado.
- Não aplicar indiscriminadamente a base administrativa ao aluno. Testar menus mobile/desktop, perfil, ranking e modais de inscrição.
- Saída: uma folha própria do aluno, com proprietário claro para cada regra.

### E4 — Consolidar administrativo e mesário

- Migrar cabeçalho para `admin.css`; manter todos os estilos de telas do mesário no pacote inicial.
- Organizar Bootstrap/layout/componentes/telas e resolver cada conflito com comparação visual. Incluir FABs e modais extraídos fora do main.
- Extrair o CSS estático da UI offline e da tela indisponível sem mudar o schema, chaves da fila, identificadores de mutação ou lógica de sincronização.
- Saída: navegação online e SPA offline com estilos presentes na ida e na volta entre telas.

### E5 — Eliminar nomes opacos e sobrescritas redundantes

- Preencher e executar a tabela de substituição de cada `sgi-inline-*`, procurando usos no PHP, JS e CSS. Migrar em lotes por componente/tela e verificar consumidores antes de apagar a definição.
- Substituir pelo Bootstrap apenas equivalências comprovadas; usar classes semânticas nos demais casos.
- Reduzir `!important` e variáveis repetidas de forma contextual. Não impor percentual de redução que incentive apagar regras necessárias. Cada exceção restante deve ter motivo curto registrado.
- Saída: zero classes `sgi-inline-*` em fontes executáveis, sem aliases de migração desnecessários.

### E6 — Fechar a estrutura e documentar

- Excluir arquivos de transição e os antigos `style.css`, `style-utilities.css`, `style-migrated.css` e `aluno-page.css` somente depois da migração dos consumidores. `aluno.css` torna-se saída gerada, com fontes em subpastas.
- Atualizar `tests/Integration/PublicBoundaryTest.php`, que referencia `assets/css/style.css`, para uma saída nova real, preservando a intenção de verificar a fronteira pública. Procurar demais referências em todo o repositório.
- Criar `docs/frontend-styles.md` com localização de regras, ordem dos pacotes, tokens, Bootstrap, escopo SPA, procedimento de build e exceções dinâmicas.
- Executar a validação final; comparar tamanho dos pacotes próprios por contexto com a referência. Medir separado do CSS de terceiros e não usar redução de linhas como prova de melhoria.
- Saída: três pacotes próprios, nenhum arquivo genérico de migração e relatório honesto das verificações.

## 8. Validação obrigatória e critérios de aceite

Seguir o ambiente isolado de `docs/testing.md`. Executar antes da refatoração e após o conjunto final:

```text
php tests/run_all.php
composer verify
npm run check
npm test
npm run build
npm --prefix tests/browser test
```

Preparar assets antes dos testes HTTP/navegador quando necessário. Não executar suítes que reconstruam a base simultaneamente. Nas etapas intermediárias, executar build e cenários pertinentes; falhas novas exigem correção antes da próxima etapa. Se a infraestrutura não estiver disponível, registrar o bloqueio de validação e não declarar aprovação.

Matriz visual mínima: login normal/erro; dashboard e navegação; turmas/alunos com menus e modais; configuração de agenda; modalidades; jogos; chaveamento com várias fases; placar em execução/pausado/finalizado e modais; ranking; ocorrências; todas as seis telas do aluno. Verificar admin, colaborador, mesário e aluno onde autorizado pelo sistema.

Viewports de referência: 390×844 e 1440×900, preservando os existentes do login; inspeção adicional a 768 px e em telas estreitas de 360 px para menus, tabelas e placar. Verificar foco por teclado, rolagem de modal, backdrop, conteúdo longo, loading, vazio, erro e desabilitado. Fixar dados e estabilizar animações/cronômetro nas comparações.

Reaproveitar `frontend-regression.spec.cjs`, `aluno-portal.spec.cjs`, `visual-contract.spec.cjs` e testes de mesário/torneio/offline. Acrescentar comparações visuais de telas críticas onde ausentes; não multiplicar testes que apenas verificam nomes de classes. Nunca atualizar todas as imagens automaticamente para fazer a suíte passar.

Offline: preparar sessão do mesário online, cortar rede, navegar dashboard → jogos → placar → chaveamento → voltar, abrir modais e verificar estilos. Abrir também uma tela disponível no preload ainda não visitada individualmente. Conferir mutações pendentes, reconexão e ausência de duplicações com os cenários existentes. O contrato é a casca preparada; não prometer cold-open offline, nova aba ou refresh sem ela.

Build: testar erro para fragmento inexistente e caminho inválido, composição determinística, manifesto atualizado e carregamento na raiz/subdiretório com `Assets::url`. Confirmar que nenhuma referência nova resolve para `?v=missing`.

Aceite final:

- Fontes separados por responsabilidade, sem arquivos vazios ou depósitos de regras sem proprietário.
- Três saídas próprias carregadas pelo contexto correspondente; mesário sem depender de novo CSS durante navegação offline.
- Sem classes opacas de migração nos arquivos executáveis e sem referências aos pacotes removidos.
- Sem mudança visual não explicada, perda de foco visível, elementos encobertos ou regressão de modais/rolagem.
- Sem alterações de regra de negócio, banco ou schema offline necessárias para esta tarefa.
- Checks executados e resultado registrado; pendências de ambiente explicitadas.
- Alterações preexistentes preservadas e documentação de manutenção concluída.

## 9. Instrução pronta para repassar ao Luna

> Implemente o plano de `docs/plano-reestruturacao-css-luna.md` na ordem E0–E6. Mantenha Bootstrap 5.3.8 e CSS nativo, preservando a aparência atual e o funcionamento online/offline. Antes de editar, leia AGENTS.md, registre as alterações existentes e execute a referência de validação em ambiente isolado. Faça uma etapa por vez e valide antes de avançar. Não remova CSS com base apenas em busca textual ou cobertura de uma página. Preserve ordem da cascata na extração, IDs e seletores funcionais do JavaScript, modais fora do main e estilos necessários à casca do mesário. Não introduza outro framework, redesign, Service Worker ou alteração de schema offline. Ao concluir, informe fontes reorganizados, pacotes gerados, duplicações eliminadas, verificações realizadas e eventuais limitações.
