# Validação da auditoria e da implementação futura

## O que foi executado nesta auditoria

| Verificação | Resultado | Limite |
| --- | --- | --- |
| Inventário de `resources/` | 101 arquivos relacionados em `01-inventario.md` | Código de negócio fora desse diretório consultado apenas quando necessário |
| `npm test` | 101 aprovados; 0 falhas; 0 ignorados | Testes JavaScript; não equivale à suíte completa |
| Renderização sintética PHP + Chromium | 33 páginas × 5 tamanhos = 165 combinações | Sem JS da aplicação, API, banco ou autenticação real |
| Inspeção de capturas | Login, perfil do aluno, inscrições e configuração de turmas; referências antigas de login consultadas | Não prova comportamento após interação |
| Revisão documental | Caminhos, links, comandos e `git diff --check` conferidos | Sem implementação funcional nesta entrega |

Não foram executadas jornadas autenticadas, integração/banco, suíte `all`, comparação visual completa ou avaliação com leitor de tela. Não declarar conformidade WCAG com base nesta auditoria.

## Evidências de geometria

As medidas foram feitas com estilos compilados das fontes do checkout `914ff13c`, Bootstrap local, fonte de fallback e dados sintéticos. Scripts da aplicação foram removidos antes da renderização. Imagens e ícones vieram dos assets locais; requisições externas foram bloqueadas. Conteúdo dinâmico, menu aberto e modal aberto não foram exercitados.

O [resumo de geometria](evidencias/geometria.json) preserva as 165 medições. Os arquivos de exploração estão em `test-results/ui-ux-audit-20260913/` neste checkout, diretório ignorado pelo Git; não são testes oficiais e não acompanham necessariamente um clone futuro. As capturas e o resumo nesta pasta são evidências duráveis. Reproduzir funcionalmente pelos testes oficiais durante E00, sem reutilizar a sessão sintética como autenticação de testes.

| Tela | Viewport | Largura do documento observada |
| --- | --- | --- |
| Colaboradores | 320×568 | 350px |
| Configurar modalidades | 320×568 / 390×844 | 416px / 416px |
| Dashboard | 320×568 / 390×844 | 414px / 449px |

Perfil e inscrições do aluno em 1440px têm main em x=0 e sidebar de 80px; a sidebar tem fundo transparente e links brancos. No login em 390px, os inputs medem 32px de altura com fonte de 11,2px. Esses resultados sustentam U01–U04, sem generalizar ausência de overflow nas demais capturas para todas as interações.

## Ambiente obrigatório para Luna

1. Ler `AGENTS.md`, `docs/testing.md` e os scripts atuais. Verificar mudanças posteriores ao checkout auditado.
2. Usar banco, servidor, sessões e uploads exclusivos de teste; preservar o ambiente de trabalho. Nunca executar reset/seed em banco real.
3. Configurar `SGI_TEST_DB_HOST`, `SGI_TEST_DB_PORT`, `SGI_TEST_DB_USER` e `SGI_TEST_DB_PASSWORD` no terminal conforme ambiente de teste. O executor não importa credenciais do `.env` de trabalho. Não registrar senhas em logs ou documentação.
4. Para backend local, conferir PHP/extensões, Composer, Node, banco e clientes `mysql`/`mysqldump`. O usuário SQL precisa criar/remover bases de teste. Selecionar PHP com `-PhpPath` ou `SGI_PHP_PATH` se necessário.
5. Instalar dependências apenas se ausentes ou exigidas por mudança deliberada; preservar lockfiles. Para setup, seguir README e AGENTS.
6. Não executar duas suítes que alteram banco simultaneamente no mesmo checkout. Não usar a aplicação de desenvolvimento em 8080 como alvo dos testes HTTP.

## Comandos conferidos

Na raiz do repositório, linha de base e entrega funcional com aparência alterada:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local -IncludeVisual
```

Qualidade sem banco/servidor, útil durante ajustes, mas insuficiente para entrega funcional:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite quality
```

Etapas de integração, navegador e visual também podem ser executadas pelos perfis `integration`, `browser` e `visual`. O perfil `all` inclui qualidade, integração e navegador; `-IncludeVisual` acrescenta o contrato visual. O perfil `quality` já inclui o build.

Alternativa com ferramentas e banco em Docker/Compose, quando disponível:

```powershell
powershell -File tools/test-docker.ps1 -Database mariadb -IncludeVisual
```

Para testes JavaScript específicos, sem banco:

```powershell
node --test tests/javascript/responsive-regression.test.cjs tests/javascript/css-bundles.test.cjs
node --test tests/javascript/page-runtime.test.cjs tests/javascript/modal-feedback.test.cjs
```

Não executar `php tests/run_all.php`, seeds ou a suíte de navegador isoladamente sem o preparo seguro documentado. Não inventar parâmetro de filtro para `test-local.ps1`: se for necessário filtrar Playwright, seguir o fluxo manual isolado de `docs/testing.md` e a configuração real em `tests/browser/`.

## Matriz de cobertura por etapa

Todos os caminhos da tabela são relativos a `tests/`. São pontos de extensão, não afirmações de que os testes atuais já cobrem os novos critérios.

| Etapas | Cobertura existente a ampliar | Comportamento a comprovar |
| --- | --- | --- |
| E01–E03 | `javascript/responsive-regression.test.cjs`, `javascript/css-bundles.test.cjs`, `browser/frontend-regression.spec.cjs`, `browser/bootstrap-components.spec.cjs`, `browser/deployment-paths.spec.cjs` | Sidebar visível; offset; foco; labels; modais; raiz/subdiretório; ausência de overflow indevido |
| E04 | `javascript/first-login-password.test.cjs`, `browser/auth-rbac.spec.cjs`, `browser/aluno-portal.spec.cjs` | Login único durante envio; erro anunciado; ajuda acionável; troca obrigatória preservada; perfil correto |
| E05 | `javascript/equipe-alunos.test.cjs`, `browser/admin-lifecycle.spec.cjs`, `browser/configuration-name-xss.spec.cjs`, `browser/aluno-portal.spec.cjs` | Cadastros em retrato e desktop; categoria selecionável; inscrição; upload válido/inválido; preservação após erro |
| E06 | `browser/mesario-responsive.spec.cjs`, `browser/frontend-regression.spec.cjs`, `browser/admin-lifecycle.spec.cjs` | Agenda por teclado; seleção de chaveamento; detalhes de jogo acionáveis; manter busca e filtros |
| E07 | `javascript/cronometro.test.cjs`, `javascript/mesario-data.test.cjs`, `browser/score-persistence.spec.cjs`, `browser/clock-persistence.spec.cjs`, `browser/occurrence-offline-edit.spec.cjs` | Placar/tempo/ocorrências persistem; controles identificáveis; nenhuma duplicação de ação |
| E07 | `browser/mesario-offline.spec.cjs`, `browser/offline-queue-regression.spec.cjs`, `browser/offline-tournament-bracket.spec.cjs`, `browser/tournament-offline.spec.cjs` | Fila, reenvio, reconexão, IDs temporários, confirmação válida, isolamento e acesso à recuperação |
| E08 | `browser/admin-lifecycle.spec.cjs`, `browser/auth-rbac.spec.cjs`, integração HTTP correspondente em `tests/` | Ativação com uma requisição; backend mantém atomicidade; erro não navega; edição selecionada preservada |
| E09 | `javascript/page-runtime.test.cjs`, `javascript/modal-feedback.test.cjs`, testes de navegador do fluxo alterado | Falha distinta de vazio; tentar novamente; entradas mantidas; anúncio sem roubar foco; reentrada sem listeners duplicados |
| E10–E11 | `browser/visual-contract.spec.cjs`, `browser/individual-ranking.spec.cjs`, `javascript/individual-ranking.test.cjs`, suíte completa | Aparência comparada; impressão; contraste; manutenção de regras e dados |

Criar `tests/browser/accessibility.spec.cjs` conforme E11. Deve ser descoberto pela configuração existente, usar fixtures reais de teste e validar teclado/foco/nomes observáveis. Não transformar o script estático desta auditoria em substituto da suíte.

## Matriz manual e visual

| Dimensão | Casos mínimos | Aceite |
| --- | --- | --- |
| Tamanhos | 320×568, 390×844, 640×360, 1024×768, 1440×900; também 1199/1200px | Sem perda de ação na transição; nomes quebram linha; scroll horizontal apenas em região que realmente precise, como chaveamento |
| Zoom | 200%; verificar reflow equivalente a 320 CSS px | Texto e controles não se sobrepõem nem desaparecem; exceções espaciais justificadas |
| Dados | Vazio, um item, muitos itens, nomes longos, seleção existente | Listas legíveis; estados distintos; filtro e ações coerentes |
| Formulários | Válido, inválido, envio lento, clique duplo, erro e nova tentativa | Uma mutação por ação; mensagem junto do campo; valores preservados quando prometido |
| Teclado | Tab/Shift+Tab, Enter, Espaço, Escape, setas quando exigidas pelo widget | Todo controle operável; foco visível; nenhum ponto sem saída; retorno de foco após modal |
| Tecnologia assistiva | Navegação por headings/landmarks, leitura de inputs, status e modal | Título e contexto corretos, nome/estado/erro anunciados; sem anúncios a cada tick do relógio |
| Toque | Navegador móvel, teclado virtual aberto, orientação alternada | Ação e campo focado alcançáveis; alvo principal 44–48px; sem depender de hover |
| Menu/modal/barra | Menu e modal abertos; banner offline pendente; página longa | Foco não encoberto; scroll útil; fechar/cancelar acessível; z-index correto |
| Permissões | Admin, colaborador, mesário e aluno; edição ativa/inativa conforme contrato | UI coerente com autorização do servidor; nenhuma permissão ampliada |
| Rede | Lenta, rejeitada, HTML 200, JSON inválido, offline e reconexão | Erro não vira sucesso; pendências não somem; estado de envio representa atividade real |
| Implantação | Raiz e subdiretório preparados conforme guias | URLs de página, API, foto e asset corretas; testar só na raiz não prova subdiretório |
| Offline | Mesma aba preparada, sair/reentrar em páginas suportadas, reconectar | Sem duplicar listeners/timers; IDs e fila preservados; nova aba/refresh offline não são requisitos atuais |
| Impressão | Ranking com filtros e nomes longos | Uma variante impressa; título e contexto corretos; sem navegação ou ações |

Quando um tamanho não apresentar defeito, manter essa referência para impedir regressões. Preferir asserções de comportamento e geometria a igualdade textual de regras CSS. Snapshots só devem mudar após inspeção, com referências corretas por plataforma.

## Registro de conclusão por etapa

Luna deve registrar: IDs dos achados atendidos; arquivos alterados; reprodução anterior; resultado após correção; testes específicos e completos com comandos/resultados; capturas antes/depois; limitações ou falhas preexistentes. Estados úteis: **pendente**, **implementado**, **validado**, **impedido por requisito identificado**. Não marcar validado com apenas build/lint.

Antes da entrega, executar `git diff --check` e revisar o diff. A execução local não comprova o CI remoto. A matriz CI atual descrita em AGENTS usa PHP 8.4/MariaDB 10.11 para integração, navegador e visual; alterações específicas de SQL exigem também os motores pertinentes disponíveis nos executores. Este plano não prevê migração de banco.
