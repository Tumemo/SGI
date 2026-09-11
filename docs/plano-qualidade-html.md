# Plano de melhoria da qualidade do HTML

## Escopo

Status: lote 1 da Fase 1 executado e validado. As demais fases e os lotes
seguintes permanecem pendentes de execução.

Esta auditoria cobre somente PHP, JavaScript, templates HTML, contratos de
renderização e testes. CSS, SCSS, imagens e decisões de estilização ficam fora
do escopo deste plano.

O objetivo é tornar o HTML previsível, seguro, acessível e fácil de testar sem
alterar as regras de negócio do SGI nem os contratos do modo offline.

## Diagnóstico inicial

O inventário conferido nesta revisão contém 40 templates PHP e 42 arquivos
JavaScript (`rg --files resources/views -g '*.php'` e equivalente em
`resources/js`). A busca `rg -l 'innerHTML|outerHTML|insertAdjacentHTML'
resources/js` encontra 31 arquivos, incluindo leituras de HTML e infraestrutura
offline; esse número não equivale a 31 vulnerabilidades. Existem pontos legados
com handlers inline (`onclick`/similares). A maior parte dos dados exibidos já
usa escape, mas o padrão não era uniforme.

Este levantamento de renderização não representa uma auditoria exaustiva de
todo o backend nem comprova ausência de XSS. Cada achado deverá registrar
arquivo, função, origem do dado, contexto de saída e reprodução antes de ser
classificado como vulnerabilidade.

Os riscos mais importantes encontrados foram:

1. Mensagens retornadas por APIs sendo interpoladas diretamente em
   `innerHTML`, permitindo markup inesperado no navegador.
2. Nomes de turma, equipe e local sendo serializados em `onclick`; escape de
   HTML não é escape de string JavaScript e pode quebrar a ação ou abrir XSS.
3. `<option>` criado por concatenação de resposta da API.
4. Formulários de login sem rótulos associados e mensagens sem região viva para
   leitores de tela.
5. Saídas PHP dinâmicas sem uma regra única e explícita por contexto.
6. Duplicação entre versões mobile/desktop, que aumenta a chance de um ajuste
   estrutural ser aplicado em apenas uma das telas.

## Trabalho já realizado nesta rodada

- Criado `resources/js/shared/html-utils.js` com escape de texto e escrita
  explícita em `textContent`.
- O utilitário passou a ser carregado pelas cascas administrativa, do aluno e
  do login.
- Corrigidas mensagens dinâmicas de erro/sucesso em páginas de perfil,
  colaboradores, home do aluno, categorias, chaveamento, placar,
  modalidade e turmas.
- Removida a serialização inline de ações que recebiam nomes de local, turma ou
  equipe nas telas de locais, arrecadação, ocorrências e equipes. Essas ações
  usam `data-*` e listeners registrados pelo escopo da página.
- Rankings administrativo e do aluno passaram a escapar nomes de turma,
  acumular a renderização antes de escrever no DOM e delegar filtros e histórico.
- A listagem de modalidades, o detalhe da modalidade e a seleção de equipes do
  aluno passaram a usar ações `data-*`, delegação e `Option` para dados de API.
- A lista de edições, o elenco e o gerenciamento de alunos passaram a usar
  controles sem handlers inline, com ativação por teclado onde há cartões
  acionáveis.
- As ações de edição/exclusão de ocorrências do placar também passaram a usar
  delegação, preservando o ciclo de vida do modo Mesário.
- Selects de tipo e categoria passaram a usar `Option`/API DOM, sem concatenar
  conteúdo vindo da API.
- Login recebeu `label` associado, `name`, `autocomplete`, `aria-live` e
  imagens decorativas com `alt` explícito; a linguagem dos shells foi
  normalizada para `pt-BR`.
- Saídas da navegação e URLs de foto receberam escape explícito.
- Criados testes de regressão em
  `tests/javascript/html-safety.test.cjs` para proteger esses contratos. O
  arquivo agora cobre escape, cascas, mensagens, rankings, selects e ações
  renderizadas.

## Plano por fases

### Fase 1 — Segurança de renderização (prioridade alta)

- Lote 1 concluído: mensagens, rankings, modalidades, ações de equipes,
  ocorrências do placar, lista de edições, elenco e alunos foram corrigidos e
  testados.
- Próximo lote: revisar `pages/competicoes/chaveamento.js` (JSON em
  `data-jogo`, opções e handlers), `pages/aluno/jogos.js`,
  `pages/participantes/turmas.js` e `pages/eventos/configurar-agenda.js`. São
  pontos pendentes identificados por busca estática; cada um precisa de
  confirmação no contexto antes da alteração.

- Migrar todos os handlers inline restantes para `data-sgi-action` e listeners
  delegados ou registrados no `SGIPage`.
- Revisar os 31 arquivos com mutação de HTML e classificar cada interpolação
  como texto, atributo, URL, identificador ou markup estático.
- Substituir `innerHTML +=` por `append`, `replaceChildren`, `textContent` ou
  fragmentos construídos com DOM quando a origem for API/usuário.
- Garantir que mensagens de servidor sejam exibidas como texto, nunca como
  markup confiável por padrão.
- Distinguir contextos: escape HTML atende texto e atributos entre aspas;
  não valida esquemas de URL nem substitui serialização JSON/JavaScript.
  Preferir `textContent` para texto, validar URLs conforme seu uso e usar
  `Url`/`Assets` para rotas e recursos, preservando implantação em subdiretório.
  Serializar JSON e escapar o atributo completo quando embutido em HTML;
  dentro de scripts PHP, usar as opções `JSON_HEX_*` apropriadas.
- Adicionar regressões com payloads como `&lt;img src=x onerror=...&gt;`, aspas,
  apóstrofos e quebras de linha em nomes e mensagens.

### Fase 2 — Contrato estrutural dos templates PHP

- Verificar os utilitários PHP existentes antes de introduzir um helper.
  Padronizar escape HTML com `ENT_QUOTES | ENT_SUBSTITUTE` e UTF-8,
  evitando escape duplo; manter validação de URL e serialização JSON separadas.
- Revisar todas as saídas `<?= ... ?>` dos 40 templates, começando por dados de
  sessão, nomes, descrições, caminhos de arquivo e atributos `data-*`.
- Garantir nome acessível para controles. Usar `for`/`id` nos labels externos;
  preservar associação implícita válida quando o controle está dentro do label.
  Conferir IDs únicos nas versões mobile/desktop e relações entre controles.
- Adicionar `type="button"` a botões que não submetem formulários e manter
  `type="submit"` apenas nos envios.
- Completar `alt`, `aria-label`, `aria-labelledby`, `aria-describedby` e
  `aria-live` nos componentes que dependem de ícones ou feedback assíncrono.
- Adicionar `scope="col"`/`scope="row"` às tabelas e conferir a hierarquia de
  headings.
- Validar o HTML renderizado: aninhamento, formulários, landmarks, relações
  de cabeçalhos de tabela e conteúdo permitido em elementos como `picture`.
  Aplicar `scope` aos cabeçalhos pertinentes, não indiscriminadamente a células.
  Conferir foco ao abrir/fechar modais e ativação por Enter/Espaço; preferir
  controles nativos. Não adicionar ARIA redundante ou conflitante.
- Consolidar contratos comuns de modal, formulário, tabela e estado vazio sem
  entrar em alterações de CSS.

### Fase 3 — Renderização JavaScript sustentável

- Separar dados, estado e renderização nas páginas maiores (`placar`,
  `chaveamento`, rankings e portal do aluno).
- Preferir funções pequenas de renderização que recebam dados normalizados e
  não dependam de globais implícitos.
- Manter o `SGIPage` como proprietário dos listeners para evitar duplicação em
  reidratação SPA/offline.
- Preferir delegação em contêiner estável para listas renderizadas repetidamente,
  evitando retenção de elementos removidos pelos callbacks de limpeza. Testar
  rerenderização e desmontagem, além da primeira abertura.
- Definir uma convenção para estados de carregamento, vazio, erro e sucesso,
  incluindo texto acessível e possibilidade de nova tentativa.
- Não alterar IDs de mutação, stores IndexedDB ou envelopes das rotas v1.

### Fase 4 — Verificação automatizada e navegador

- Manter o teste unitário JavaScript de escape e os contratos estáticos já
  criados.
- Tratar os testes por regex atuais como proteção parcial de padrões: eles
  não exercitam cliques, interpretação de atributos ou execução de payloads.
  Priorizar regressões comportamentais antes de ampliar testes estáticos.
- Avaliar um checker em `tools/` após definir regras e exceções. Usar DOM
  renderizado para estrutura e nomes acessíveis; buscas estáticas servem como
  triagem para interpolação PHP e handlers. Não bloquear automaticamente
  saídas de helpers seguros ou labels implícitos com base apenas em regex.
- Criar cenários Playwright para login, modais, filtros, cadastro/edição,
  mensagens de erro e navegação por teclado em mobile e desktop.
- Exercitar payloads maliciosos apenas como dados de teste e confirmar que são
  tratados como texto.
- Cobrir nomes com aspas, apóstrofos, `&`, Unicode e markup: o nome deve aparecer
  integralmente, nenhuma execução deve ocorrer e a ação deve enviar o ID correto
  uma única vez. Verificar preservação da seleção ao carregar opções, mensagens
  de erro e sucesso e reabertura de telas na casca offline preparada.
- Executar, antes de cada entrega, `composer verify`, `npm run check`, `npm
  test`, `npm run build`, `php tests/run_all.php` e
  `npm --prefix tests/browser test`.
- Executar antes/depois de refatorações no ambiente isolado descrito em
  `docs/testing.md`; não executar suítes que alteram a mesma base em paralelo.
  Reaproveitar os cenários existentes, acrescentando os comportamentos faltantes.
  Validar raiz e subdiretório nas alterações de links; registrar explicitamente
  quando contrato visual ou alvos da matriz de CI não tiverem sido executados.

### Fase 5 — Manutenção contínua

- Atualizar este plano quando uma nova tela ou componente HTML for criado.
- Exigir que cada alteração de template tenha teste estrutural ou de navegador
  correspondente.
- Fazer revisão periódica dos resultados do checker e dos testes de acessibilidade.
- Manter CSS/estilização como uma trilha independente, para que mudanças de
  aparência não escondam regressões de semântica ou comportamento.

## Critérios de aceite

Aplicar estes critérios às telas e comportamentos declarados em cada lote;
segurança e regressões devem acompanhar todas as fases, não ficar para o final.
Uma fase estará concluída quando:

- os payloads de regressão de nomes, descrições e mensagens não gerarem markup
  ativo nem execução e todos os achados confirmados no lote forem resolvidos;
- as ações funcionarem sem `onclick` inline com dados dinâmicos;
- os formulários puderem ser usados por teclado e leitor de tela;
- os estados assíncronos forem anunciados sem depender apenas de cor;
- os testes de regressão cobrirem o comportamento alterado;
- a suíte aplicável passar e instabilidades forem investigadas. Bloqueio de
  ambiente significa validação pendente, não aceite concluído.

Para cada lote, registrar arquivos, risco tratado, testes antes/depois e pendências.
Preservar o trabalho paralelo de CSS e conferir conflitos de classes com seu
responsável. A manutenção contínua da fase 5 é recorrente, não uma entrega finita.

## Evidência da última validação

Na execução anterior, `composer verify` no host não iniciou porque `vendor` não
contém `PHPUnit\\TextUI\\Application` e o PHP CLI também não possui a extensão
`mysqli`. Essa condição não foi reavaliada nesta revisão documental.
O fluxo isolado Docker descrito em `docs/testing.md` foi executado com sucesso
em um projeto Compose exclusivo após as alterações:

- PHPUnit: 231 testes e 2.177 asserções; aprovado, com apenas 2 deprecações já
  reportadas pela ferramenta.
- Lint PHP: aprovado.
- Build e verificação JavaScript: 42 arquivos válidos.
- Testes JavaScript: 78 aprovados.
- Suíte HTTP/integração: 481 asserções aprovadas.
- Playwright: 50 testes aprovados; o processo terminou com código 0.

As duas deprecações PHPUnit permanecem para classificação.

A execução excluiu o contrato visual do acesso (`--grep-invert`); não comprova
aprovação visual nem execução de toda a matriz PHP/MySQL/MariaDB do CI.
Os nove testes em `html-safety.test.cjs` incluem um teste funcional do escape
e cinco verificações estáticas; não substituem regressões de DOM/navegador.

O bloqueio do host local deve ser resolvido no ambiente de desenvolvimento,
mas não impediu a execução das suítes acima no ambiente isolado. Os resultados
são históricos e deverão ser renovados na implementação dos próximos lotes.
