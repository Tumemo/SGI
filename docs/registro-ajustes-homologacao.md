# Registro de Ajustes da Homologação

Documento consolidado dos problemas identificados durante a homologação do SGI, das causas encontradas, das soluções implementadas e das validações executadas.

## Escopo da rodada

- **Ambiente:** base local/de homologação, sem dados de produção.
- **Data da consolidação:** 09/09/2026.
- **Responsável pela análise técnica:** equipe de desenvolvimento.
- **Critério de validação:** reprodução do problema, inspeção do código, testes automatizados e inspeção visual em diferentes larguras de tela.

## Resumo das ocorrências

| ID | Área/Tela | Problema | Causa principal | Solução | Status |
| --- | --- | --- | --- | --- | --- |
| HOM-001 | Chaveamento / edição de jogo | A agenda não podia ser salva porque era interpretada como alteração do cronômetro. | O `status_jogo` era tratado isoladamente como mutação do cronômetro, mesmo quando o formulário apenas mantinha o status da agenda. | Separação entre payload de agenda e payload de cronômetro no front-end e no controlador. | `Validado` |
| HOM-002 | Locais / regulamento | O upload era concluído, mas o navegador informava que a resposta da API não era JSON. | Avisos HTML do PHP, iniciados por `<br />`/`<b>`, contaminavam o corpo JSON; também faltavam garantias para diretório temporário e armazenamento. | Leitura segura da resposta, validação do armazenamento e configuração de diretórios de upload. | `Validado` |
| HOM-003 | Login mobile | O banner e o destaque vermelho quebravam a composição em telas estreitas. | Imagem e borda eram elementos independentes no fluxo, com dimensionamento incompatível com a referência mobile. | Banner em camadas, borda posicionada abaixo da imagem e formulário/rodapé dimensionados para a viewport. | `Validado` |
| HOM-004 | Todas as telas | Algumas páginas apresentavam overflow horizontal, especialmente Jogos, Chaveamento, Colaboradores e Modalidades. | Offset fixo da barra lateral, grids com largura mínima de conteúdo, toolbar sem quebra adequada e regra global que anulava estilos com `all: unset`. | Dimensionamento responsivo global, ajustes por componente e remoção da regra destrutiva. | `Validado` |
| HOM-005 | Base de homologação / acessos | Era necessário preparar os administradores e usuários padrão para executar os testes. | A base local não estava com o conjunto padrão de acessos disponível para a homologação. | Cadastro dos perfis padrão na base não produtiva, com níveis de acesso compatíveis e senhas armazenadas com hash. | `Validado` |
| HOM-006 | Sessão / ambiente local | A sessão podia depender de um diretório PHP inexistente ou sem permissão de gravação. | O caminho padrão de sessões variava entre instalações e não era garantido pelo aplicativo. | Diretório de sessão configurável por `SGI_SESSION_DIR`, com criação e validação de permissão no bootstrap. | `Validado` |
| HOM-007 | Acesso / gerenciamento de alunos | O mesário conseguia abrir diretamente a página `/turmas/alunos` pela URL. | O controlador de páginas aplicava uma autorização genérica para todos os níveis administrativos, e a view apenas ocultava alguns controles. | Política específica no servidor para permitir a rota somente ao nível `0`, com teste regressivo e view alinhada à mesma regra. | `Validado` |
| HOM-008 | Chaveamento / agenda / mesário | Jogos recebem programação padrão repetida; a organização precisa agendar por blocos e o mesário deve receber apenas programação completa. | Geradores preenchem data/horário/local automaticamente; fases futuras ainda não têm uma reserva própria e criação/edição divergem na validação de conflitos. | Implementados agenda nula na geração, reservas futuras, simulador determinístico, assistente sequencial terça/quinta, limite padrão de 11h30, intervalo mínimo de 10 minutos, confirmação idempotente e filtro operacional do mesário. | `Validado tecnicamente — homologação publicada pendente` |
| HOM-009 | Tabelas administrativas | As tabelas apresentavam estilos inconsistentes, células comprimidas e rolagem ruim em telas estreitas. | Regras distribuídas entre Bootstrap e componentes específicos; regra duplicada removia o espaçamento da tabela de elenco; tabelas largas não mantinham largura mínima legível no celular. | Base visual compartilhada, espaçamento corrigido, rolagem horizontal interna e aplicação do padrão em jogos, ocorrências e arrecadações. | `Validado tecnicamente — reteste visual pendente` |
| HOM-010 | Categorias / edições | O sistema permitia criar duas categorias com o mesmo nome dentro da mesma edição. | Não havia validação de duplicidade no serviço nem restrição única no banco; o mesmo problema poderia ocorrer em requisições concorrentes. | Validação no serviço para criação/edição, restrição única por edição no banco, conversão de conflito para HTTP 409 e testes de regressão. | `Validado tecnicamente — migração condicionada à auditoria de dados` |
| HOM-011 | Todas as páginas | O título do navegador podia repetir `SGI` ou incluir o nome da tela, interclasse ou turma. | O título era definido em múltiplas views e reescrito por scripts da navegação administrativa, turma de alunos e operação offline. | Componente único com `<title>SGI</title>`, remoção dos títulos por página e bloqueio das substituições contextuais no JavaScript. | `Validado tecnicamente — reteste visual pendente` |
| HOM-012 | Modalidades individuais / placar | O PDF revisado mostra Corrida como “Final — Confronto 1”, com relógio e placar coletivo. | Há dependência de ID fixo/tag na seleção da interface; a causa exata no ambiente do relato ainda exige confronto de cadastro, API e assets/cache. | Plano corretivo V2: identificação pelo tipo, auditoria dos jogos existentes, início e formulário individual, integridade, fila offline e testes completos do percurso. | `Implementada tecnicamente — auditoria de dados e homologação HTTP/browser pendentes` |
| HOM-015 | Dashboard / edições inativas | Ao abrir uma edição inativa, o sistema informava que ela não havia sido finalizada e oferecia o botão `Concluir criação`. | O dashboard tratava qualquer edição com status diferente de `1` como não finalizada e montava um link para o fluxo de resumo/ativação. | Substituição pela mensagem exata `O interclasse está inativo no momento.`, sem botão e sem redirecionamento para `/edicoes/resumo`. | `Implementado — validação direcionada aprovada` |
| HOM-016 | Modalidades / edição de modalidade | O campo de gênero não aparecia no modal de edição, impedindo alterar o gênero de uma modalidade já criada. | O modal de edição não renderizava o select de gênero e o JavaScript não carregava nem enviava `genero_modalidade`, embora o backend já aceitasse o campo. | Inclusão do select de gênero no modal, preenchimento com o valor atual, envio no `PUT` e validação do formulário. | `Implementado — validação técnica aprovada; reteste visual/browser pendente` |
| HOM-017 | Chaveamento / geração | O botão **Gerar Chaveamento** retornava HTTP 500 depois do preenchimento dos dados obrigatórios. | O código passou a criar jogos com data, horário e local nulos, mas a base ainda estava somente até a migração 004 e mantinha essas colunas como `NOT NULL`. | Aplicação das migrações pendentes, reconciliação segura da migração 008 já parcialmente refletida no banco e validação da geração com equipes reais. | `Implementado — validação técnica aprovada` |
| HOM-018 | Autorização / sessão / escopo do mesário | O mesário conseguia acessar telas administrativas por URL direta e consultar recursos de outras edições por filtros manipulados. | O `PageController` herdava níveis permissivos, APIs confiavam em IDs enviados pelo cliente e sessões não eram revalidadas contra alterações de papel, senha ou status. | Política explícita por rota, escopo derivado do servidor, revalidação com `auth_version`, logout somente por POST/CSRF, projeções mínimas e testes de regressão. | `Validado tecnicamente — homologação publicada pendente` |
| HOM-019 | Modalidades / Alunos Destaques | O modal **Alunos Destaques** exibia “Erro ao carregar os destaques.” e a API retornava HTTP 500. | A consulta SQL correlacionava o alias externo `m` dentro de uma subconsulta derivada, onde esse alias não era visível no MySQL/MariaDB. | Apuração do maior total por modalidade com `NOT EXISTS`, agrupamento compatível com modo SQL estrito, suporte aos eventos de pontos da migration 010 e regressões HTTP/browser. | `Implementado — integração e navegador aprovados; publicação pendente` |
| HOM-021 | Infraestrutura de testes / CI | Os testes dependiam de ferramentas e serviços instalados no host, dificultando a repetição em ambiente limpo e a equivalência entre desenvolvimento e CI. | A execução não possuía um fluxo único para provisionar banco, servidor HTTP, navegador e dependências em ambientes isolados e temporários. | Criado executor Docker descartável com Compose, imagens de teste, bancos MariaDB/MySQL, suites de qualidade, integração, navegador e contrato visual, além de limpeza automática ao final. | `Implementado — infraestrutura validada; reteste da suíte completa afetado por alterações de domínio em paralelo` |
| HOM-022 | Disciplina / ocorrências de turma / ranking | Uma penalidade informada com sinal negativo era interpretada como bônus, fazendo o ranking aumentar em vez de diminuir; a remoção da ocorrência também precisava restaurar exatamente o valor anterior. | O serviço aceitava valores assinados, enquanto o cálculo do ranking já subtraía a penalidade. Faltavam normalização centralizada e uma restrição de banco para impedir valores negativos legados ou novos. | Normalização com `ABS()` no serviço e na interface, correção dos dados legados, `CHECK` constraints na migration 009 e regressões para persistência, ranking, remoção e recuperação. | `Implementado — regressão MariaDB aprovada; cenário de navegador da ocorrência aprovado; ressalva de falhas MySQL preexistentes fora deste fluxo` |
| HOM-023 | Portal do aluno / aceite de regras | Um aluno conseguia abrir Jogos, Modalidades/Inscrições e outras telas por URL direta antes de aceitar as regras; também era possível tentar acessar as APIs diretamente. | O bloqueio dependia da navegação da interface e não havia uma barreira única, server-side, aplicada a todas as rotas web e APIs do nível competidor. | Estado do aceite consultado no banco e revalidado a cada requisição; rotas e APIs do aluno bloqueadas antes do aceite, com redirecionamento web ou HTTP 403; liberados somente Termos, status dos termos e regulamento ativo. | `Implementado — testes de regressão do aceite aprovados` |
| HOM-024 | Mesário / placar / artilharia / operação offline | O placar podia ser incrementado antes da identificação do atleta; havia caminhos de API que permitiam pontuação isolada; ao anular, o histórico individual precisava permanecer; durante a regressão offline, uma jogada temporária chegou a ser contabilizada duas vezes. | O botão de ponto misturava a alteração visual do placar com o lançamento da artilharia; o elenco era consultado por turma em vez da equipe exata; rotas legadas aceitavam mutações sem vínculo; e o ramo de jogos temporários projetava o mesmo ponto antes e depois do tratamento comum de sucesso. | Migração 010 e serviço transacional de pontos, seletor por equipe, validações server-side, proteção das rotas alternativas, anulação lógica com preservação histórica, projeção offline idempotente e correção da duplicidade encontrada no teste `06–00` versus `03–00`. | `Implementado — integração 475/475 e navegador 47/47 aprovados; publicação pendente` |

> A validação técnica das ocorrências HOM-001 a HOM-011, HOM-013, HOM-014, HOM-015, HOM-016, HOM-019, HOM-020, HOM-021, HOM-022, HOM-023 e HOM-024 foi registrada abaixo. HOM-012 foi reaberta após o PDF revisado; seus testes anteriores são evidência histórica, sem aceite do percurso completo. O responsável pela homologação ainda pode repetir os fluxos já corrigidos no ambiente publicado para registrar a confirmação visual final.

## Ocorrências detalhadas

### HOM-001 — Edição de jogo confundia agenda com cronômetro

- **Data do relato:** durante a homologação; data original não informada.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** `/chaveamento`, modal **Editar Jogo**.
- **Prioridade:** Alta.
- **Status:** Validado.

#### Descrição do erro

Ao editar um jogo na página de chaveamento, o sistema informava que as atualizações de agenda e cronômetro deveriam ser enviadas separadamente. A mensagem aparecia mesmo quando o usuário alterava somente data, horário ou local.

#### Passos para reproduzir

1. Acessar o chaveamento de uma edição.
2. Abrir a edição de um jogo.
3. Alterar a data, o horário ou o local, mantendo o status atual.
4. Salvar o jogo.

#### Resultado esperado

Alterações de agenda devem ser salvas sem serem encaminhadas ao serviço do cronômetro. Alterações reais do cronômetro devem continuar protegidas contra combinação indevida com agenda.

#### Resultado encontrado

O servidor rejeitava a atualização como se agenda e cronômetro tivessem sido enviados simultaneamente.

#### Causa identificada

O front-end sempre enviava `status_jogo`, inclusive quando o status não havia sido alterado. No back-end, a presença de qualquer `status_jogo` era suficiente para classificar a requisição como mutação do cronômetro. Assim, estados de agenda como `Agendado` eram confundidos com operação do relógio.

#### Solução aplicada na tentativa anterior (histórico)

- O formulário passou a comparar o status atual com o status selecionado e só envia `status_jogo` quando houve alteração.
- O controlador passou a considerar mutação de cronômetro quando recebe campos do relógio (`cronometro`, `tempo_restante_jogo`, `tempo_extra_jogo` ou `duracao_jogo`) ou uma transição operacional explícita para `Iniciado`, `Pausado` ou `Concluido`.
- Estados de agenda, como `Agendado`, permanecem no fluxo de agenda.
- Foi incluído teste de regressão para garantir que a edição da agenda continue aceita e que a atualização mista permaneça rejeitada atomicamente.

#### Validação histórica da tentativa anterior

- **Teste de regressão:** `tests/Integration/ConsistencyGuardsTest.php`.
- **Resultado:** atualização de agenda aprovada; atualização mista continua bloqueada sem persistência parcial.
- **Suíte HTTP:** 348 de 348 asserções aprovadas.

---

### HOM-002 — Upload do regulamento retornava resposta inválida

- **Data do relato:** durante a homologação; data original não informada.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** `/edicoes/locais`, upload do regulamento em PDF.
- **Prioridade:** Alta.
- **Status:** Validado.

#### Descrição do erro

Ao enviar um regulamento em PDF, o navegador apresentava:

> `Unexpected token '<', "<br /> <b>"... is not valid JSON`

Apesar da mensagem, o arquivo podia ser gravado no armazenamento.

#### Passos para reproduzir

1. Acessar a tela de locais.
2. Abrir o modal de regulamento.
3. Selecionar um PDF válido.
4. Enviar o arquivo.

#### Resultado esperado

A API deve responder sempre com um envelope JSON válido, e a tela deve informar claramente sucesso ou falha do upload.

#### Resultado encontrado

O front-end tentava executar `response.json()` sobre uma resposta que começava com HTML de aviso do PHP. O upload podia ter sido concluído antes de o cliente interpretar a resposta.

#### Causa identificada

Avisos de PHP relacionados ao upload ou ao diretório temporário eram incluídos no corpo da resposta. Além disso, o armazenamento não verificava de forma explícita se o diretório existia e era gravável antes de mover o arquivo.

#### Solução aplicada

- O cliente passou a ler o corpo como texto e recuperar o envelope JSON quando houver ruído HTML antes dele, sem transformar uma gravação concluída em erro falso.
- Respostas realmente inválidas continuam gerando mensagem amigável de falha.
- O armazenamento passou a validar tamanho, assinatura `%PDF-`, existência do arquivo, criação do diretório e permissão de gravação.
- O caminho de armazenamento permanece configurável por `SGI_REGULAMENTOS_DIR`.
- A configuração de desenvolvimento controla `display_errors`; fora do desenvolvimento, os avisos não devem ser enviados para a resposta HTTP.
- A documentação de implantação passou a exigir `upload_tmp_dir` existente e gravável.

#### Validação da solução

- Upload de PDF válido aprovado.
- Arquivos inválidos continuam sendo rejeitados.
- Testes de PHP, integração e fronteira pública aprovados.
- O arquivo permanece disponível após a resposta da API.

---

### HOM-003 — Quebra visual no login mobile

- **Data do relato:** durante a homologação; imagem de referência anexada pelo solicitante.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** `/login` em viewport mobile.
- **Prioridade:** Média.
- **Status:** Validado.

#### Descrição do erro

Na versão mobile, a composição do login não correspondia à referência: o destaque vermelho não ficava corretamente abaixo da imagem, e o formulário/rodapé variavam de posição conforme a largura da tela.

#### Resultado esperado

O destaque vermelho deve aparecer abaixo da imagem, mostrando somente a borda; o formulário deve ficar centralizado abaixo do banner, seguido pela marca SESI.

#### Causa identificada

Imagem e borda eram renderizadas em sequência no fluxo normal do documento. As alturas e margens dependiam do comportamento do Bootstrap e não mantinham a composição da referência em telas estreitas.

#### Solução aplicada

- Criação de um contêiner de banner mobile com altura proporcional à viewport.
- Posicionamento da imagem e da borda em camadas, com a borda atrás/abaixo da imagem.
- Ajuste dos campos, botão, espaçamentos e marca SESI para o layout de referência.
- Preservação da versão desktop separada.
- Atualização dos snapshots visuais de login desktop/mobile e de erro de login.

#### Validação da solução

- Contrato visual desktop aprovado.
- Contrato visual mobile aprovado.
- Tela verificada em 360, 390 e 768 px sem overflow horizontal.

---

### HOM-004 — Falhas de responsividade em telas administrativas

- **Data do relato:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** conjunto de telas administrativas.
- **Prioridade:** Alta.
- **Status:** Validado.

#### Descrição do erro

Algumas telas ultrapassavam a largura disponível ou comprimiam seus componentes em mobile e tablet. Os casos mais evidentes foram Jogos, Chaveamento, Colaboradores e Modalidades.

#### Causa identificada

- O layout compartilhado mantinha o deslocamento de 80 px da barra lateral e, em algumas páginas, somava esse deslocamento à largura total.
- A tabela do chaveamento precisava de rolagem interna, mas seu conteúdo também aumentava a largura calculada da página.
- Os quatro cartões de estatística de Colaboradores não tinham espaço suficiente em 768 px e alguns filhos mantinham largura mínima de conteúdo.
- A toolbar de Modalidades não quebrava adequadamente em celulares estreitos.
- Cards legados de Modalidades combinavam um bloco `width: 100%` com os botões de ação, fazendo o botão sair do card.
- Uma regra global com `all: unset` anulava estilos de componentes do placar e de cabeçalhos.

#### Solução aplicada

- O layout desktop passou a usar `width: calc(100% - 80px)`.
- Em mobile, o conteúdo recupera 100% da largura e reserva espaço para a barra de navegação inferior.
- `html` e `body` não permitem rolagem horizontal acidental.
- A tabela do chaveamento permanece rolável dentro de seu próprio contêiner.
- Os cartões de Colaboradores usam duas colunas até 991 px, com `min-width: 0` e quebra segura de rótulos.
- A toolbar de Modalidades empilha ações em telas estreitas.
- O título e os botões dos cards legados de Modalidades passaram a dividir a largura corretamente.
- A regra global destrutiva `all: unset` foi removida.

#### Validação da solução

- 24 rotas administrativas testadas em 360, 390, 768, 1024 e 1440 px.
- Nenhuma das 120 combinações apresentou overflow horizontal de página.
- O teste Playwright de layout responsivo das telas críticas foi aprovado.
- As tabelas continuam com rolagem interna quando necessário.

---

### HOM-005 — Preparação dos usuários padrão da homologação

- **Data do ajuste:** 09/09/2026.
- **Área:** base local de homologação e autenticação.
- **Prioridade:** Média.
- **Status:** Validado.

#### Solicitação

Disponibilizar administradores e usuários padrão para executar os fluxos de homologação sem utilizar dados de produção.

#### Solução aplicada

Foram preparados na base local os perfis padrão utilizados nos testes:

| Login de homologação | Nível | Papel |
| --- | ---: | --- |
| `admin` | 0 | Administrador |
| `colab` | 1 | Colaborador |
| `mesario` | 2 | Mesário |
| `2879` | 3 | Competidor/aluno |

As senhas foram armazenadas como hash conforme a regra do sistema. As credenciais provisórias da base local não devem ser reutilizadas em produção nem registradas neste documento.

#### Validação

Os logins foram utilizados nos testes de autenticação e RBAC. A base principal permaneceu fora do escopo de gravação da homologação.

---

### HOM-006 — Persistência de sessão no ambiente local

- **Data do ajuste:** 09/09/2026.
- **Área:** bootstrap, sessões PHP e configuração de armazenamento.
- **Prioridade:** Média.
- **Status:** Validado.

#### Descrição

Ambientes locais e de teste podiam iniciar com um caminho de sessão PHP inexistente, não gravável ou compartilhado indevidamente entre execuções.

#### Solução aplicada

- Inclusão de `SGI_SESSION_DIR` na configuração de ambiente.
- Criação centralizada do caminho em `StoragePaths`.
- Validação e criação do diretório pelo `SessionManager` antes de `session_start()`.
- Preservação de caminhos de sessão explicitamente configurados pelos testes.
- Manutenção de `display_errors` desativado fora do modo de depuração e registro de erros em log.

#### Validação

Os testes de autenticação, sessão, contratos HTTP e suíte completa de integração foram aprovados.

---

### HOM-007 — Mesário acessava o gerenciamento de alunos pela URL

- **Data do relato:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** `/turmas/alunos`, gerenciamento de alunos da turma.
- **Prioridade:** Alta.
- **Status:** Validado.

#### Descrição do erro

Com uma sessão de mesário autenticada, era possível acessar diretamente a URL `/turmas/alunos`, embora o gerenciamento de alunos devesse ser exclusivo do administrador.

#### Passos para reproduzir

1. Autenticar com um usuário de nível `2` (mesário).
2. Informar diretamente a URL `/turmas/alunos` no navegador.
3. Observar que a página de alunos da turma era carregada.

#### Resultado esperado

A rota deve ser aberta somente por usuários de nível `0` (administrador). Mesários, colaboradores, competidores e usuários não autenticados não devem receber o HTML da tela.

#### Causa identificada

O `PageController` usava a regra genérica `[0, 1, 2]` para as páginas administrativas, sem uma política específica para `/turmas/alunos`. A view ocultava ações conforme o nível, mas isso não impedia a abertura da página por navegação direta.

#### Solução aplicada

- Inclusão de uma política de níveis por rota no `PageController`, com `/turmas/alunos` autorizado somente para o nível `0`.
- Bloqueio no servidor antes da renderização da view, preservando o redirecionamento padrão para o login quando o nível não é autorizado.
- Ajuste de `$podeGerenciar` na view para considerar exclusivamente o administrador, mantendo a camada visual coerente com a autorização do servidor.
- Inclusão de teste unitário para os níveis colaborador, mesário e competidor.
- Inclusão de regressão no teste de navegador para a tentativa de acesso direto do mesário.

#### Validação da solução

- `tests/Unit/Presentation/Web/PageControllerTest.php`: 3 testes e 9 asserções aprovados.
- `composer test`: 167 testes e 1.849 asserções aprovados.
- `composer lint`, `composer analyse` e `composer cs:check`: aprovados na rodada final; a análise inicial havia apontado problemas no módulo de agendamento, corrigidos antes da consolidação.
- `npm test` e `npm run check`: aprovados.
- O teste de navegador foi adicionado em `tests/browser/auth-rbac.spec.cjs`; sua execução depende de servidor e banco de teste configurados por `SGI_TEST_BASE_URL`.

O endurecimento posterior das demais rotas administrativas, APIs, sessões e escopos do mesário está consolidado em [HOM-018](#hom-018--mesário-acessava-administração-e-recursos-fora-da-edição-ativa).

---

### HOM-009 — Padronização visual e responsividade das tabelas

- **Data do relato:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** tabelas administrativas, com foco em `/chaveamento`, elenco de equipe, histórico de ocorrências e histórico de arrecadações.
- **Prioridade:** Média.
- **Status:** Validado tecnicamente — reteste visual pendente.

#### Descrição do erro

As tabelas não apresentavam um padrão visual único. Em algumas telas o cabeçalho, espaçamento, bordas e efeito de hover eram diferentes; em telas estreitas, tabelas com muitas colunas ficavam comprimidas ou provocavam uma experiência ruim de rolagem.

#### Causa identificada

- Havia uma combinação de estilos Bootstrap genéricos com regras específicas de cada componente.
- A tabela de elenco possuía uma regra posterior duplicada para `.aluno-table td`, removendo o espaçamento horizontal das células.
- A tabela de jogos do chaveamento não preservava uma largura mínima para manter a leitura das colunas no celular.
- Históricos gerados dinamicamente não tinham uma classe visual comum para receber o mesmo acabamento.

#### Solução aplicada

- Criada a classe `sgi-table` para padronizar cabeçalho, tipografia, bordas, espaçamento, hover e remoção da borda da última linha.
- Ajustada a barra de rolagem dos contêineres `.table-responsive`.
- Definida largura mínima de `980px` para as tabelas do histórico de jogos, mantendo o overflow dentro do próprio contêiner horizontal.
- Corrigida a tabela `.aluno-table` com `table-layout`, padding uniforme e cabeçalho mais legível.
- Aplicado o padrão às tabelas do chaveamento, ocorrências disciplinares e arrecadações.
- Assets recompilados com `npm run build`.

#### Arquivos afetados

- `resources/css/source/admin.css`
- `resources/views/pages/competicoes/chaveamento.php`
- `resources/js/pages/disciplina/ocorrencias.js`
- `resources/js/pages/eventos/configurar-arrecadacao.js`

#### Validação da solução

- `composer verify`: 167 testes e 1.849 asserções aprovados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 17 testes JavaScript aprovados.
- `npm run build`: 134 assets preparados com sucesso.
- PHP lint, `node --check` e `git diff --check`: aprovados.
- `php tests/run_all.php`: não executado por ausência de `SGI_TEST_BASE_URL` e de servidor HTTP de teste configurado.
- `npm --prefix tests/browser test`: execução iniciada, mas bloqueada no primeiro cenário pela ausência do servidor HTTP de teste; o reteste visual final permanece pendente.

### HOM-010 — Categorias duplicadas na mesma edição

- **Data do relato:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** cadastro de categorias por edição.
- **Prioridade:** Alta.
- **Status:** Validado tecnicamente — migração condicionada à auditoria de dados.

#### Descrição do erro

O sistema permitia criar duas categorias com o mesmo nome na mesma edição. A regra esperada é permitir a repetição do nome em edições diferentes, mas nunca dentro da mesma edição.

#### Resultado esperado

Ao criar ou renomear uma categoria, a operação deve ser recusada quando já existir outra categoria com o mesmo nome na edição selecionada. A comparação segue a collation do banco, portanto nomes que diferem apenas por maiúsculas/minúsculas também são tratados como iguais. Categorias com o mesmo nome em edições diferentes continuam permitidas.

#### Causa identificada

O serviço não consultava a existência de outra categoria antes de criar ou atualizar o registro, e a tabela `categorias` não possuía uma chave única composta por edição e nome. Assim, a regra não era protegida nem no fluxo normal nem contra duas requisições concorrentes.

#### Solução aplicada

- Criada a exceção de domínio `CategoriaDuplicadaException`.
- A criação e a alteração passaram a verificar a duplicidade no `CategoriaService`, ignorando o próprio registro durante uma renomeação.
- Criada a migration `database/migrations/006_unique_category_name_per_edition.sql`, com a chave única `uk_categorias_edicao_nome` sobre `(interclasses_id_interclasse, nome_categoria)`. Essa é a proteção definitiva contra concorrência.
- Violações da chave única retornam a mesma exceção de domínio, evitando erro 500 não tratado.
- A API de categorias responde HTTP `409 Conflict` com mensagem orientando que o nome já está em uso na edição.
- A mesma regra é aplicada a categorias ativas e inativas, para impedir reutilização ambígua do nome dentro da edição.

#### Validação da solução

- `tests/Unit/Modules/Eventos/CategoriaServiceTest.php`: 8 testes e 9 asserções aprovados, cobrindo criação duplicada, diferença entre edições, renomeação e manutenção do próprio nome.
- `tests/Unit/Modules/Eventos/CategoriaControllerTest.php`: incluído teste do contrato HTTP `409 Conflict` e da mensagem de duplicidade.
- `tests/Integration/ModalidadesAndEquipesTest.php`: incluídas regressões para criação e renomeação duplicadas, com confirmação de HTTP 409 e preservação do cadastro original.
- `tests/Integration/MigrationsTest.php`: incluída verificação da chave única e da idempotência da migration.
- `tests/Integration/RecoveryRehearsalTest.php`: incluída a migration 006 no ensaio de recuperação e no manifesto de schema, preservando a migration 005 já existente.
- `composer lint`, `composer analyse` e `composer cs:check`: aprovados na rodada final; nenhum erro foi apontado nos arquivos da correção de categorias.
- `composer test`: 172 testes e 1.880 asserções executados; a única falha está no teste preexistente de conflito de agenda (`JogoServiceTest`), causada por alteração não relacionada em `JogoService.php`. Nenhum teste de categoria falhou.

#### Cuidados antes de aplicar a migration

A migration não exclui categorias automaticamente. Antes de aplicá-la em uma base que já possua dados, deve ser feita a auditoria abaixo:

```sql
SELECT interclasses_id_interclasse, nome_categoria, COUNT(*) AS total
FROM categorias
GROUP BY interclasses_id_interclasse, nome_categoria
HAVING COUNT(*) > 1;
```

Se a consulta retornar registros, é necessário reconciliar manualmente as categorias duplicadas e seus vínculos antes de executar a migration. Isso evita apagar referências de turmas, modalidades ou outros registros por decisão automática. Em uma base sem duplicidades, a migration pode ser aplicada normalmente e deve permanecer registrada pelo controle de checksum das migrations.

### HOM-011 — Título das páginas duplicado ou contextual

- **Data do relato:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/tela:** todas as páginas web, incluindo login, portal do aluno e operação offline do mesário.
- **Prioridade:** Média.
- **Status:** Validado tecnicamente — reteste visual pendente.

#### Descrição do erro

O título exibido na aba do navegador podia aparecer duplicado ou variar conforme a tela. Havia combinações como `SGI - SGI - <interclasse>` e títulos contextuais de turma, modalidade ou operação do mesário.

#### Resultado esperado

Todas as páginas devem manter exatamente o título `SGI`, independentemente do perfil autenticado, dos parâmetros da URL, da edição selecionada ou da navegação online/offline.

#### Causa identificada

O título era definido em mais de um ponto: cada página informava `$tituloPagina`, os cabeçalhos compartilhados renderizavam esse valor, o login possuía um `<title>` próprio e scripts JavaScript reescreviam `document.title` durante a resolução da edição, carregamento da turma e navegação offline. No cabeçalho administrativo, a ausência de um título padrão no `body` fazia o fallback `SGI` ser concatenado novamente.

#### Solução aplicada

- Criado o componente compartilhado `resources/views/components/page-title.php` com o único título permitido: `<title>SGI</title>`.
- Os cabeçalhos administrativo, do aluno e a tela de login passaram a incluir o componente compartilhado.
- Removidas as declarações `$tituloPagina` das páginas, evitando fontes paralelas de título.
- A atualização de título do cabeçalho administrativo e da casca offline passou a manter `document.title = 'SGI'`.
- Removida a substituição contextual do título na tela de alunos da turma.
- Recompilados os assets públicos após a alteração.

#### Validação da solução

- `tests/Unit/Presentation/Web/PageTitleTest.php`: 4 testes e 114 asserções aprovados, verificando componente único, inclusão nos três pontos de entrada, ausência de títulos por página e navegação JavaScript.
- `composer verify`: 194 testes e 2.035 asserções aprovados; foi reportada uma depreciação do PHPUnit sem falha de teste.
- `npm test`: 18 testes JavaScript aprovados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm run build`: 134 assets preparados.
- Auditoria textual: apenas `<title>SGI</title>` permanece como título HTML nos arquivos de origem; os títulos dinâmicos foram eliminados.
- `php tests/run_all.php` e o reteste completo do Playwright permanecem pendentes nesta rodada por ausência de `SGI_TEST_BASE_URL`, servidor HTTP e banco isolados no ambiente local.

---

## Observação de infraestrutura encontrada durante a validação

O Apache local estava publicando a aplicação em uma subpasta, enquanto alguns redirecionamentos utilizavam a raiz (`/edicoes`). Isso produzia 404 no ambiente local e não era uma falha de responsividade. A inspeção visual foi repetida em um servidor local publicado na raiz da aplicação, eliminando a interferência do prefixo incorreto.

## Registro de alterações da homologação

| Data | Ajuste realizado | Área afetada | Validação |
| --- | --- | --- | --- |
| 09/09/2026 | Separação entre alterações de agenda e cronômetro. | Chaveamento / jogos | Teste de consistência e suíte HTTP aprovados |
| 09/09/2026 | Tratamento seguro de resposta de upload e armazenamento de regulamentos. | Locais / regulamento | Testes de upload e PHP aprovados |
| 09/09/2026 | Reestruturação do banner e formulário do login mobile. | Acesso | Snapshots visuais aprovados |
| 09/09/2026 | Ajustes globais e específicos de responsividade. | Telas administrativas | 24 rotas x 5 larguras sem overflow |
| 09/09/2026 | Preparação de usuários padrão e diretório de sessão. | Ambiente de homologação | Testes de autenticação e integração aprovados |
| 09/09/2026 | Restrição da página de gerenciamento de alunos ao nível administrador. | Acesso / participantes | Testes unitários, análise estática e regressão de RBAC adicionada |
| 09/09/2026 | Padronização visual, espaçamento e rolagem responsiva das tabelas. | Chaveamento, elenco, ocorrências e arrecadações | Composer, JavaScript, lint e build aprovados; reteste visual pendente |
| 09/09/2026 | Bloqueio de nomes de categorias repetidos dentro da mesma edição. | Categorias / edições | Testes unitários, regressão HTTP, análise estática e chave única de banco adicionadas |
| 09/09/2026 | Padronização do título do navegador para `SGI` em todas as páginas. | Todas as páginas / navegação online e offline | Testes de regressão, suíte PHP, JavaScript, auditoria textual e build aprovados; reteste visual pendente |
| 09/09/2026 | Recuperação do fluxo de modalidades individuais, com pódio, créditos e sincronização offline. | Chaveamento / placar / mesário | `composer verify`, testes JavaScript, análise estática e build aprovados; HTTP/browser pendentes por falta do ambiente isolado |
| 09/09/2026 | Reestruturação dos estilos em fontes por responsabilidade e bundles por contexto; remoção de duplicações e utilitários hash. | Acesso, administração, mesário e portal do aluno | `composer verify`, `npm run check`, `npm test`, `npm run build` e `git diff --check` aprovados; reteste visual e suíte HTTP/browser pendentes |
| 09/09/2026 | Substituição do aviso de finalização por `O interclasse está inativo no momento.` e remoção do botão de conclusão. | Dashboard / edições inativas | Teste Playwright direcionado e regressão do dashboard ativo aprovados; reteste visual/publicado pendente |
| 09/09/2026 | Implementação da programação em blocos, reservas futuras, filtro operacional do mesário e agenda sequencial terça/quinta. | Chaveamento / agenda / mesário | 473 asserções de integração aprovadas, testes unitários, análise estática, build e materialização de reservas aprovados; homologação publicada pendente |
| 09/09/2026 | Fechamento de autorização por rota, escopo de recursos, revalidação de sessão, logout com POST/CSRF e regressões do mesário. | Acesso / APIs / mesário | PHPUnit 212/212, 2.089 asserções, JavaScript 21/21, análise estática e build aprovados; HTTP/browser isolados pendentes |
| 09/09/2026 | Vínculo obrigatório de cada ponto a atleta, preservação do histórico após anulação e correção da duplicidade no placar temporário offline. | Mesário / placar / artilharia / sincronização | Integração 475/475, JavaScript 23/23, navegador 47/47, PHPStan, CS Fixer e build aprovados |

## Validações históricas das rodadas anteriores

Os itens desta seção preservam evidências de homologações anteriores. A validação atual da correção de autorização do mesário está registrada em **HOM-018**, com os números mais recentes e as limitações do ambiente desta rodada.

- `npm run build` — assets recompilados com sucesso.
- `npm run check` — 40 arquivos JavaScript válidos.
- `npm test` — 18 testes JavaScript aprovados.
- `composer verify` — 196 testes PHPUnit e 2.038 asserções aprovados; 1 depreciação do PHPUnit reportada sem falha.
- `php tests/run_all.php` — 373 de 373 asserções aprovadas no servidor HTTP e banco isolados de teste.
- `php vendor/bin/phpunit --filter PageControllerTest` — 3 testes e 9 asserções aprovados.
- `php vendor/bin/phpunit --filter Categoria` — 9 testes e 11 asserções aprovados, incluindo o serviço e o controlador de categorias.
- `php vendor/bin/phpunit tests/Unit/Presentation/Web/PageTitleTest.php` — 4 testes e 114 asserções aprovados.
- `php vendor/bin/phpunit tests/Unit/Modules/Competicoes` — 73 testes e 198 asserções aprovados, incluindo contratos de modalidade individual, cronômetro e placar.
- `node --test tests/javascript/mesario-data.test.cjs` — 4 testes offline aprovados.
- `composer lint`, `composer analyse` e `composer cs:check` — aprovados.
- Regressões de categoria adicionadas para HTTP 409, renomeação, isolamento por edição, idempotência da migration e recuperação do schema.
- Regressão de acesso direto do mesário adicionada ao Playwright; a execução requer o servidor HTTP e banco isolados da homologação.
- Auditoria visual de 24 rotas em 360, 390, 768, 1024 e 1440 px — sem overflow horizontal.
- Playwright — testes visuais de login desktop/mobile e layout responsivo das rodadas anteriores aprovados; o reteste visual específico das tabelas permanece pendente.
- Playwright direcionado da HOM-015 — dashboard de edição inativa exibiu somente `O interclasse está inativo no momento.`, sem botão ou link de conclusão.
- Playwright de regressão do dashboard ativo — alerta de edição inativa permaneceu oculto.
- `AgendamentoBlocoSchedulerTest` — 8 cenários unitários aprovados, incluindo dependências, descanso, conflito de local/participante e datas inválidas.
- `AgendamentoBlocoTest` — integração HTTP aprovada para autorização, prévia, confirmação transacional, idempotência, reservas futuras e materialização offline.
- A suíte browser completa deve ser repetida após atualizar os cenários legados que criavam jogos sem agenda diretamente; esses cenários agora precisam agendar o jogo pela organização antes de abrir o placar.

### HOM-012 — Recuperação do fluxo de modalidades individuais

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** solicitação de recuperação após a refatoração.
- **Área/tela:** `/chaveamento`, `/jogos/placar` e operação offline do mesário.
- **Prioridade:** Alta.
- **Status:** Implementada tecnicamente — auditoria de dados e homologação HTTP/browser real pendentes.

#### Reabertura após nova especificação — 09/09/2026

O usuário informou que a implementação não atende ao solicitado. A página 3 de `Especificação de Requisitos_ Modalidades Individuais (1).pdf` mostra Corrida com título de confronto, cronômetro e placar coletivo. A inspeção estática confirmou decisões por ID fixo/tag, ausência de ação de início no formulário individual, conversão de IDs antes da validação estrita e risco de confirmar outra mutação de pódio ainda pendente. A imagem não comprova isoladamente a origem do erro no ambiente publicado.

O [plano corretivo V2 para o Luna](plano-correcao-modalidades-individuais-v2.md) substitui o roteiro anterior. A implementação desta rodada cobre o tipo semântico nas respostas e telas, formulário desde Agendado com início da prova, vínculo do ranking ao jogo aberto, validação estrita de IDs e confirmação parcial da fila offline. A auditoria e eventual reparação dos jogos já publicados continuam separadas, com prévia e autorização.

As seções seguintes preservam o registro da tentativa anterior. Os resultados abaixo distinguem os testes executados nesta rodada das evidências históricas; ainda não comprovam o aceite do percurso publicado até a execução HTTP/browser com dados reais.

#### Implementação e testes da correção V2 — 09/09/2026

- `TipoCompeticaoRules` resolve `individual`/`mata_mata` pelo nome cadastrado e expõe `tipo_competicao`; o fallback numérico existe somente para snapshots antigos sem o campo.
- O placar e o chaveamento resolvem o tipo antes de limitar partidas ou montar o título. Uma prova Individual com tag legada de mata-mata exibe o formulário individual e a ação `Iniciar prova`, sem VS, gols ou cronômetro coletivo.
- O salvamento envia `id_jogo`, rejeita IDs não inteiros/fora de faixa e valida o pódio no servidor. Participantes retornam equipe/turma sem `MIN`, permitindo três atletas da mesma turma/equipe e rejeitando vínculos conflitantes.
- A busca sem `id_jogo` só reaproveita a tag oficial ou um único legado; múltiplos jogos sem identidade explícita retornam conflito para auditoria, evitando gravar o pódio no jogo errado.
- A projeção offline mantém mutações individuais por identificador: confirmar A mantém B pendente e só encerra o indicador quando não restam retificações.
- `composer test`: 211 testes e 2.083 asserções aprovados; duas depreciações do PHPUnit sem falha.
- `composer analyse`, `composer cs:check`, `npm run check`, `npm test` (21 testes) e `npm run build` (132 assets) aprovados.
- O teste browser `individual-ranking.spec.cjs` foi adicionado e é executado com `SGI_INDIVIDUAL_BROWSER=1` contra servidor/banco de homologação. Nesta máquina ele permaneceu opt-in; `php tests/run_all.php` continua bloqueado sem `SGI_TEST_BASE_URL`.
- Uma tentativa da suíte browser ampla contra o servidor local apresentou falhas em cenários já existentes e foi interrompida por espera prolongada; isso não é aceite da funcionalidade individual.

#### Comportamento esperado

Uma modalidade individual deve gerar somente o jogo especial da prova, ser programada pela agenda, permitir a seleção de três atletas inscritos e distintos, registrar 1º, 2º e 3º lugar e apresentar o ranking com nome, turma e posição. Atletas da mesma turma ou equipe podem ocupar posições diferentes. A conclusão deve atualizar o jogo e os créditos do ranking geral de forma transacional.

#### Causa identificada

O fluxo existente estava distribuído entre o chaveamento, o placar, o repositório individual e a sincronização offline. A decisão dependia em alguns pontos do parâmetro enviado pelo cliente, a preparação do jogo podia ser confundida com homologação inválida e os caminhos genéricos de placar/cronômetro poderiam concluir uma prova individual sem pódio.

#### Solução aplicada

- O controlador consulta o tipo real da modalidade antes de gerar, consultar ou salvar; modalidade coletiva não aceita resultado individual e modalidade individual não gera mata-mata.
- A ausência de `ranking` prepara o jogo para a agenda; um `ranking` ausente, nulo ou incompleto durante a homologação é recusado.
- Participantes são filtrados por usuário competidor ativo, equipe ativa e edição coerente. A gravação exige três atletas distintos e preserva a equipe validada de cada atleta.
- A homologação exige jogo preparado e iniciado ou pausado; retificações de jogo concluído reconciliam os créditos sem duplicação. Preparar novamente um jogo concluído não cria placeholders nem altera o resultado.
- O placar exibe os três seletores, desabilita escolhas repetidas, informa falta de participantes ou jogo ainda não iniciado e mostra o ranking atual.
- A camada offline projeta jogo e pódio pendente no IndexedDB, mantém a leitura local durante a fila e marca a confirmação após a sincronização.
- Cronômetro, atualização direta de jogos, lançamento genérico de placar e sincronização validam o tipo real e bloqueiam conclusão individual fora do fluxo do pódio.

#### Arquivos principais

- `src/Modules/Competicoes/Application/ChaveamentoService.php`
- `src/Modules/Competicoes/Domain/TipoCompeticaoRules.php`
- `src/Modules/Competicoes/Presentation/Http/ChaveamentoController.php`
- `src/Modules/Competicoes/Application/IndividualRankingService.php`
- `src/Modules/Competicoes/Infrastructure/MysqliIndividualRepository.php`
- `src/Modules/Competicoes/Application/CronometroService.php`
- `src/Modules/Competicoes/Application/ResultadoService.php`
- `src/Modules/Sincronizacao/Infrastructure/MysqliChaveamentoSyncGateway.php`
- `resources/js/pages/competicoes/placar.js`
- `resources/js/pages/competicoes/chaveamento.js`
- `resources/js/pages/eventos/configurar-agenda.js`
- `resources/js/offline/mesario-data.js`
- `tests/Unit/Modules/Competicoes/TipoCompeticaoRulesTest.php`
- `tests/javascript/individual-ranking.test.cjs`
- `tests/browser/individual-ranking.spec.cjs`

#### Validação da solução

- `composer test`: 211 testes e 2.083 asserções aprovados; duas depreciações do PHPUnit foram reportadas sem falha.
- `composer analyse` e `composer cs:check`: aprovados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 21 testes JavaScript aprovados.
- Teste offline direcionado: confirmação independente de retificações A/B aprovada, mantendo a segunda mutação pendente até sua própria sincronização.
- `npm run build`: 132 assets preparados.
- Foram adicionados testes para tipo semântico, tag legada de mata-mata, `id_jogo` explícito, entradas inválidas, atletas da mesma turma, conflito de vínculos e confirmação parcial da fila offline.
- O teste browser `individual-ranking.spec.cjs` foi incluído como opt-in (`SGI_INDIVIDUAL_BROWSER=1`) para validar o fluxo visual em ambiente de homologação real; sem essa variável, o cenário permanece ignorado pela suíte padrão.
- `php tests/run_all.php` permanece bloqueado sem `SGI_TEST_BASE_URL`; a suíte browser HTTP completa deve ser repetida contra o servidor/banco isolados antes do aceite publicado.
- Nenhum dado da base publicada foi alterado automaticamente. Jogos legados ambíguos devem ser auditados e corrigidos com prévia antes de qualquer reparação.

### HOM-013 — Reestruturação dos estilos e remoção de CSS duplicado

- **Data da implementação:** 09/09/2026.
- **Origem:** solicitação de reorganização dos estilos após a análise da base.
- **Área/tela:** acesso, telas administrativas, operação do mesário e portal do aluno.
- **Status:** Implementado tecnicamente — homologação visual e HTTP/browser pendentes.

#### Solução aplicada

- Os fontes foram reorganizados em `resources/css/source/`, separados por contexto e responsabilidade.
- O build passou a gerar somente `admin.css`, `aluno.css` e `login.css` em `public/assets/css`; os fragments-fonte não são publicados individualmente.
- O login deixou de carregar o pacote administrativo completo e recebeu uma folha própria com os estilos necessários aos banners, formulários e feedback de autenticação.
- Os utilitários que só existiam no login foram substituídos por classes locais `login-*`, evitando duplicação no pacote compartilhado.
- As declarações dos utilitários `sgi-inline-*` foram mantidas uma vez, renomeadas para `sgi-u-*` e distribuídas somente aos bundles que têm consumidores.
- Os tokens e regras repetidos do portal do aluno foram consolidados na folha compartilhada, preservando os valores efetivos da cascata anterior.
- A classe órfã `sgi-inline-886e54cd`, sem declaração CSS, foi removida do HTML gerado pelo chaveamento.
- Bootstrap 5.3.8 foi preservado como framework de componentes, sem troca de dependência ou alteração do schema offline.

#### Validação da implementação

- `composer verify`: 196 testes PHPUnit e 2.038 asserções aprovados; uma depreciação do PHPUnit foi reportada sem falha.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 18 testes aprovados.
- `npm run build`: 132 assets preparados e manifesto atualizado.
- `git diff --check`: aprovado.
- `php tests/run_all.php` e `npm --prefix tests/browser test`: pendentes por ausência do servidor/banco isolados de homologação nesta sessão.
- Reteste visual do login, portal, administrativo e navegação offline: pendente no ambiente publicado.

## Pendências para encerramento

- [ ] Responsável da homologação repetir os fluxos no ambiente publicado.
- [ ] Homologar no ambiente publicado o assistente de agenda sequencial: terça inicial, quinta seguinte, alternância posterior, limite padrão de 11h30, intervalo de 10 minutos e materialização das reservas futuras.
- [ ] Retestar visualmente os bundles CSS e a navegação offline após a reestruturação (HOM-013).
- [ ] Retestar visualmente o campo de gênero no modal de edição de modalidade e confirmar a persistência da alteração (HOM-016).
- [ ] Retestar visualmente no ambiente publicado o comportamento do dashboard para uma edição inativa (HOM-015).
- [ ] Anexar evidências finais do reteste do usuário, se exigido pelo processo.
- [x] Ocorrências técnicas registradas com causa e solução.
- [x] Correções críticas validadas por testes automatizados.
- [x] Regra de vínculo obrigatório entre ponto e atleta validada na API, interface, operação offline e sincronização (HOM-024).
- [x] Servidores temporários de teste encerrados.

## Encerramento da rodada

- **Total de ocorrências registradas:** 24.
- **Correções técnicas registradas:** 24, com ressalvas de auditoria de dados ou confirmação visual/publicada descritas em cada ocorrência.
- **Validação automatizada consolidada:** suíte integrada 475/475, qualidade PHP/JavaScript aprovada e navegador 47/47.
- **Pendências operacionais:** retestes visuais/publicados e auditoria da HOM-012, conforme a lista de pendências e os status detalhados acima.
- **Reabertas:** 1 (HOM-012, incluída nas pendências de auditoria/publicação).
- **Observação final:** a confirmação visual do responsável pela homologação deve ser registrada após o reteste no ambiente publicado.

## HOM-008 — Programação em blocos e agenda sequencial pela organização

- **Data da solicitação e do plano:** 09/09/2026.
- **Origem:** teste de homologação e orientação posterior do responsável.
- **Status:** Implementado — validado tecnicamente.
- **Problema:** data, horário e local são repetidos automaticamente na geração; o fluxo atual também permite inconsistência na validação de sobreposição entre criação e edição.
- **Comportamento solicitado:** a organização define todos os horários; o mesário recebe apenas programação completa. Para o fluxo automático, a primeira sessão começa na terça-feira, a próxima ocorre na quinta-feira e as sessões seguintes alternam terça e quinta.
- **Solução aplicada:** gerar confrontos sem agenda automática, reservar previamente fases futuras e oferecer assistente de agendamento em blocos com seleção, janelas por dia/local, duração, troca, descanso, prévia e confirmação integral. O assistente também oferece a agenda sequencial com solicitação progressiva das próximas sessões.
- **Regra de conflito proposta:** impedir sobreposição no mesmo espaço e verificar participantes e dependências. Substitui a interpretação anterior de permitir sobreposição deliberada.
- **Offline:** carregar reservas das fases futuras para que o mesário avance o torneio sem precisar agendar; preservar resultados e tratar divergências de revisão na reconexão.
- **Plano detalhado vigente:** [Agendamento em blocos e programação do mesário](plano-agendamento-em-blocos.md).
- **Plano anterior:** `plano-ajuste-chaveamento-agenda.md`, identificado como substituído.
- **Validação técnica:** suíte unitária dos algoritmos, suíte HTTP completa, análise estática, build e regressões de materialização offline aprovados. A execução integrada mais recente terminou com 473 asserções e nenhuma falha.
- **Pendência:** confirmação dos responsáveis pela homologação no ambiente publicado. Os cenários de agenda e do mesário foram validados no ambiente isolado; a regressão de pontuação offline que surgiu durante a validação foi registrada e corrigida em HOM-024.

#### Implementação realizada

- A geração de chaveamentos, jogos individuais e materializações passa a iniciar com data, horário e local nulos quando não existe uma reserva confirmada.
- O endpoint `POST /api/v1/agenda-blocos` oferece simulação e confirmação para níveis administrador/colaborador, com seleção de jogos ou posições futuras, janelas, locais, duração, intervalo de troca, descanso, dependências e revisão.
- O algoritmo evita sobreposição no local, conflito conhecido de participantes e programação de uma fase dependente antes do término da fase anterior; a confirmação rejeita propostas incompletas.
- As reservas futuras são gravadas sem jogo fictício e aplicadas quando o confronto real é materializado, preservando data, local e duração.
- O mesário consulta somente jogos operacionais com agenda completa; não agenda nem reprograma jogos.
- A confirmação é transacional, idempotente e mantém histórico da alteração.

#### Adequação do plano — agenda sequencial terça/quinta

O plano original de agendamento em blocos foi ampliado para atender à regra operacional definida durante a revisão da homologação. A agenda passa a ser construída de forma progressiva: o responsável informa a primeira sessão, recebe uma prévia e só precisa informar a sessão seguinte quando o limite diário não comportar os jogos restantes.

Regras consolidadas:

- A primeira data válida é obrigatoriamente uma terça-feira. A interface já sugere a próxima terça-feira disponível, inclusive quando o modal é aberto em outro dia da semana.
- A segunda sessão deve ser a quinta-feira imediatamente posterior à terça-feira. Depois disso, a cadência é terça-feira, quinta-feira, terça-feira, e assim sucessivamente.
- O horário inicial do primeiro jogo é informado pelo responsável, com valor inicial de `08:00`.
- O limite inicial para término dos jogos é `11:30`. O limite pode ser ajustado na sessão, mas nenhum jogo da sessão pode ultrapassar o término informado.
- Cada jogo recebe intervalo fixo de 10 minutos. O backend rejeita intervalos menores, mesmo que não haja sobreposição direta entre os jogos.
- Quando a prévia não consegue acomodar todos os jogos até o limite da última sessão informada, a resposta devolve a próxima data da cadência, o horário inicial sugerido e o limite `11:30`.
- A interface exibe esses dados em um painel **Ainda há jogos. Informe a próxima sessão**, permitindo alterar o horário, adicionar a sessão e recalcular. O processo se repete até que não existam pendências.
- O local selecionado permanece associado às sessões seguintes no assistente. Conflitos com jogos ou reservas fora da seleção continuam sendo considerados.
- A confirmação só é habilitada quando a prévia não possui pendências; a confirmação permanece protegida por revisão e chave de idempotência.

#### Fluxo de homologação atualizado

1. Acessar a configuração de agenda e selecionar uma modalidade Mata-Mata.
2. Clicar em **Agendar automaticamente**.
3. Conferir a terça-feira sugerida, o horário inicial, o limite `11:30`, o local e a duração média do jogo.
4. Clicar em **Calcular prévia** e conferir a ordem dos confrontos, os intervalos de 10 minutos e os horários de cada sessão.
5. Se houver pendências, conferir que o sistema sugere a quinta-feira seguinte, informar/confirmar o horário da próxima sessão e clicar em **Adicionar dia e recalcular**.
6. Repetir a etapa anterior enquanto houver jogos pendentes, validando a alternância terça/quinta.
7. Confirmar a agenda somente quando a prévia indicar que todos os jogos possuem horário.
8. Conferir no calendário e na tela do mesário que apenas jogos com data, horário e local completos são operacionais.
9. Materializar/avançar uma posição futura da chave e conferir que a reserva é aplicada ao jogo real sem novo agendamento manual.

#### Contrato técnico do fluxo sequencial

- `POST /api/v1/agenda-blocos` com `acao: simular_sequencial` simula a agenda e retorna `dias`, `resumo`, `proposta`, `pendencias`, `intervalo_troca_min`, `limite_termino_padrao`, `proximo_dia_sugerido`, `proximo_inicio_sugerido` e `proximo_termino_sugerido`.
- `POST /api/v1/agenda-blocos` com `acao: confirmar_sequencial` confirma a mesma proposta após validar a revisão.
- O payload sequencial usa `id_modalidade`, `dias` e `opcoes.duracao_min`; cada item de `dias` contém `data`, `inicio`, `fim` e `local`.
- O intervalo operacional é normalizado para 10 minutos no assistente e validado também no servidor.
- A seleção automática descobre as posições existentes da modalidade, inclui posições futuras da chave e a disputa de terceiro lugar quando aplicável, sem criar jogos fictícios.
- Posições futuras são persistidas em `agenda_reservas` com `id_jogo` nulo. Quando o chaveamento cria o jogo real, `aplicarReservaAgenda()` copia data, início, término e local e vincula a reserva ao novo jogo.
- A seleção respeita as dependências entre fases: uma posição só pode ser programada depois que seus confrontos filhos tiverem horário ou estiverem resolvidos por bye.
- O fluxo antigo `simular`/`confirmar` continua disponível para preservar compatibilidade com agendamentos em bloco já existentes.

#### Testes de regressão da adequação

- `tests/Unit/Modules/Competicoes/AgendamentoSequencialSchedulerTest.php`: seis cenários para limite diário, sugestão da quinta-feira, alternância de datas, dependências, intervalo mínimo e rejeição de primeira data fora da terça-feira.
- `tests/Integration/AgendamentoSequencialTest.php`: prévia e confirmação HTTP, inclusão das posições futuras, intervalo de 10 minutos, reserva futura não materializada e reenvio idempotente.
- `tests/Integration/JogosAndConflitosTest.php`: rejeição de jogo com intervalo operacional inferior a 10 minutos no mesmo local.
- `tests/Integration/AgendamentoBlocoTest.php`: preservação do fluxo legado, reservas futuras e materialização.
- `tests/run_all.php`: a nova suíte sequencial é executada junto com a suíte HTTP completa.

#### Validação final atualizada — 09/09/2026

- PHPUnit em Docker: 229 testes aprovados e 2.175 asserções; PHPStan sem erros e PHP CS Fixer sem correções.
- JavaScript em Docker: 40 arquivos válidos, 23 testes aprovados e 132 assets preparados.
- Integração HTTP, banco e recuperação em MariaDB isolado: 475/475 asserções aprovadas, incluindo a nova Suite 6.2 de agenda automática terça/quinta e a regressão do relatório de destaques.
- Navegador: a suíte completa terminou com **47/47 testes aprovados**, incluindo inspeção offline do chaveamento, persistência do placar, operação do mesário e torneio online/offline. Durante a execução foi encontrada e corrigida a duplicidade de projeção `03–00`/`06–00`, detalhada em HOM-024.
- `git diff --check`: sem erros de whitespace.
- O ambiente Docker temporário foi encerrado ao final da validação.

#### Arquivos principais

- `database/migrations/005_agendamento_blocos.sql`
- `src/Modules/Competicoes/Application/AgendamentoBlocoScheduler.php`
- `src/Modules/Competicoes/Infrastructure/MysqliAgendamentoBlocoRepository.php`
- `src/Modules/Competicoes/Presentation/Http/AgendamentoBlocoController.php`
- `resources/js/pages/eventos/configurar-agenda.js`
- `resources/js/pages/competicoes/chaveamento.js`
- `resources/js/offline/chaveamento-engine.js`
- `tests/Unit/Modules/Competicoes/AgendamentoBlocoSchedulerTest.php`
- `tests/Integration/AgendamentoBlocoTest.php`

#### Validação final da implementação — 09/09/2026

- `php tests/run_all.php`: 475/475 asserções aprovadas.
- `composer verify`: 229 testes PHPUnit e 2.175 asserções aprovados; duas depreciações do PHPUnit, sem falha.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 23 testes aprovados.
- `npm run build`: 132 assets preparados.
- `npm --prefix tests/browser test`: **47/47 testes aprovados**, incluindo os cenários de inspeção do chaveamento offline e torneio online/offline.
- Contratos visuais de login desktop/mobile: 2 testes aprovados.
- A validação confirmou: geração sem data/horário/local fictícios, prévia sem escrita, confirmação transacional, idempotência, revisão concorrente, conflitos de local/participantes, intervalo mínimo de 10 minutos, limite diário de 11h30, cadência terça/quinta, reservas futuras e operação offline do mesário sem agendamento local.

## HOM-014 — Ranking da edição em andamento visível aos alunos

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Origem:** solicitação de ajuste durante a homologação.
- **Área/tela:** portal do aluno, ranking, classificação, chaveamento e artilharia.
- **Prioridade:** Alta.
- **Status:** Validado tecnicamente — homologação HTTP/browser pendente.

#### Descrição do erro

O aluno conseguia consultar informações da edição em andamento. O ranking atual e dados relacionados à competição podiam ser acessados antes da entrega das premiações, contrariando a regra de divulgação definida para o evento.

#### Comportamento esperado

Durante a competição, o aluno deve continuar acessando apenas os recursos operacionais necessários, como inscrição e agenda. O ranking, a classificação, o chaveamento público, a artilharia e os dados de arrecadação da edição em curso não devem ser exibidos nem retornados pelas APIs. Rankings de edições encerradas só devem aparecer depois de publicados pela organização, no momento da premiação.

#### Causa identificada

- O ranking do aluno selecionava automaticamente a edição ativa.
- A API de ranking aceitava a consulta do aluno sem exigir uma edição encerrada e publicada.
- A autorização de algumas APIs de resultados permitia o nível de aluno, sem verificar o estado de publicação da edição.
- A edição possuía apenas o status ativo/inativo, sem um registro explícito do momento ou responsável pela publicação do ranking.
- O encerramento da edição estava associado a uma rotina que poderia alterar o status de acesso dos usuários, o que não é adequado para liberar o ranking.

#### Solução aplicada

- Criada a migração `database/migrations/007_publicacao_ranking.sql`, adicionando `ranking_publicado_em` e `ranking_publicado_por` à tabela `interclasses`.
- Edições já encerradas foram consideradas publicadas para preservar o acesso ao histórico existente.
- Criado o serviço de publicação do ranking, disponível somente para administradores.
- A publicação exige que não existam jogos pendentes e grava a data e o usuário responsável em uma operação atômica.
- O endpoint de ranking exige que o aluno informe uma edição encerrada e publicada; consultas da edição ativa retornam HTTP 403 com mensagem de bloqueio.
- O ranking do portal do aluno passou a selecionar somente edições históricas publicadas.
- A classificação, o chaveamento, a artilharia e a arrecadação foram protegidos contra consultas de alunos durante a competição.
- O menu e os cards do portal foram ajustados para usar o conceito de “rankings publicados” e informar quando a premiação ainda não ocorreu.
- A rotina de sincronização que vinculava automaticamente o status da edição ao status de acesso dos alunos foi removida, evitando bloquear contas durante a publicação.

#### Fluxo de publicação

1. O administrador acessa o ranking da edição.
2. Enquanto houver jogos pendentes, a publicação permanece indisponível.
3. Após a conclusão dos jogos, o administrador seleciona **Publicar na premiação**.
4. O sistema registra a publicação e libera o ranking histórico aos alunos.

#### Arquivos principais

- `database/migrations/007_publicacao_ranking.sql`
- `src/Modules/Eventos/Application/EdicaoService.php`
- `src/Modules/Eventos/Infrastructure/MysqliEdicaoRepository.php`
- `src/Modules/Eventos/Infrastructure/MysqliEdicaoConsultaRepository.php`
- `src/Modules/Eventos/Presentation/Http/EdicaoController.php`
- `src/Modules/Resultados/Presentation/Http/RankingController.php`
- `src/Modules/Resultados/Presentation/Http/HistoricoTurmaController.php`
- `src/Modules/Resultados/Presentation/Http/ClassificacaoController.php`
- `src/Modules/Resultados/Presentation/Http/ArrecadacaoController.php`
- `src/Modules/Competicoes/Presentation/Http/ChaveamentoController.php`
- `src/Modules/Competicoes/Presentation/Http/ArtilheiroController.php`
- `resources/js/pages/aluno/home.js`
- `resources/js/pages/aluno/ranking.js`
- `resources/js/pages/resultados/ranking.js`
- `resources/views/pages/resultados/ranking.php`

#### Validação da solução

- PHPUnit: 196 testes e 2.038 asserções aprovados.
- `composer analyse`: aprovado.
- `composer cs:check`: aprovado.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm run build`: 132 assets preparados.
- `git diff --check`: aprovado.
- `php tests/run_all.php` e `npm --prefix tests/browser test`: pendentes nesta sessão por ausência de `SGI_TEST_BASE_URL` e do servidor/banco isolados de homologação.
- Reteste HTTP/browser com usuário aluno e administrador deve confirmar: bloqueio da edição ativa, liberação após publicação e preservação dos rankings históricos.

### HOM-015 — Edição inativa exibia fluxo indevido de ativação

- **Data do relato:** 09/09/2026.
- **Data da análise:** 09/09/2026.
- **Data da correção:** 09/09/2026.
- **Reportado por:** homologação, com evidência visual anexada.
- **Área/tela:** `/painel?id=<id_inativo>`, dashboard administrativo de uma edição inativa.
- **Prioridade:** Média.
- **Status:** Implementado — validação direcionada aprovada; reteste visual/publicado pendente.

#### Descrição do erro

Ao acessar uma edição de interclasse que não está ativa, o dashboard exibe o aviso:

> Esta edição ainda não foi finalizada. Conclua as etapas para ativá-la.

O aviso também apresenta o botão **Concluir criação**, que direciona o usuário para o fluxo `/edicoes/resumo`. Esse fluxo não corresponde ao requisito para uma edição simplesmente inativa e pode induzir o usuário a tentar reconfigurar ou ativar indevidamente o evento.

#### Passos para reproduzir

1. Autenticar com um usuário administrador.
2. Selecionar uma edição com `status_interclasse = '0'` ou acessar diretamente `/painel?id=<id_inativo>`.
3. Observar o alerta exibido no dashboard.
4. Verificar a presença do botão **Concluir criação** e seu destino para `/edicoes/resumo`.

#### Resultado esperado

O dashboard deve exibir somente a mensagem abaixo quando a edição não estiver ativa:

> O interclasse está inativo no momento.

O texto antigo — **“Esta edição ainda não foi finalizada. Conclua as etapas para ativá-la.”** — deve ser removido e não pode aparecer em nenhuma parte desse aviso. A mensagem não deve mencionar finalização, etapas, conclusão ou ativação.

Também não deve existir botão, link ou redirecionamento para concluir criação, configurar etapas ou ativar a edição a partir desse aviso. Os cards e permissões já existentes devem permanecer inalterados, salvo orientação funcional específica posterior.

#### Resultado encontrado

O sistema interpreta a edição inativa como uma edição ainda não finalizada, exibe uma mensagem de orientação para conclusão e oferece uma ação que leva ao resumo da edição.

#### Causa identificada

- Em `resources/views/pages/eventos/dashboard.php`, o administrador recebe um alerta oculto inicialmente contendo a mensagem de finalização e o link `linkConcluirInterclasse`.
- Em `resources/js/pages/eventos/dashboard.js`, qualquer edição cujo `status_interclasse` seja diferente de `1` remove a classe de ocultação do alerta.
- O mesmo trecho JavaScript atribui ao botão o endereço `/edicoes/resumo?id=<id>`, misturando o conceito de edição inativa com o fluxo de conclusão/ativação.
- O problema não está na persistência do status nem exige mudança na API de ativação; trata-se de uma mensagem e ação inadequadas na camada de apresentação.

#### Correção aplicada

- Substituir, no alerta do dashboard, o texto atual pelo texto exato: **“O interclasse está inativo no momento.”**
- Não deixar no template nenhuma orientação para finalizar etapas, concluir criação ou ativar o evento.
- Remover o botão **Concluir criação** do template, preservando o identificador do alerta enquanto ele for utilizado pelos testes ou pela navegação.
- Remover do JavaScript a atribuição do link para `/edicoes/resumo`; o alerta deve apenas ser exibido ou ocultado conforme o status da edição.
- Manter a verificação do status normalizada como texto, para continuar tratando corretamente respostas que tragam `0` ou `'0'`.
- Garantir que uma edição ativa não exiba o alerta.
- Não alterar rotas, banco de dados, serviço de ativação, permissões, cards do dashboard ou contratos da operação offline.

#### Arquivos principais

- `resources/views/pages/eventos/dashboard.php`
- `resources/js/pages/eventos/dashboard.js`
- `tests/browser/admin-lifecycle.spec.cjs`
- `tests/browser/frontend-regression.spec.cjs`

#### Validação executada

- Cenário direcionado do Playwright aprovado: `npm --prefix tests/browser test -- admin-lifecycle.spec.cjs -g "dashboard informa somente"` — 1 teste aprovado.
- O cenário confirmou a visibilidade exclusiva da mensagem `O interclasse está inativo no momento.`.
- O texto antigo sobre finalizar etapas não aparece no HTML nem na tela.
- Não existe `linkConcluirInterclasse`, link de conclusão ou destino para `/edicoes/resumo` dentro do alerta.
- Regressão do dashboard de edição ativa aprovada em `frontend-regression.spec.cjs`; o alerta permaneceu oculto.
- `composer verify`, `npm run check`, `npm test`, `npm run build` e `git diff --check` aprovados.
- A execução completa de `admin-lifecycle.spec.cjs` não foi concluída porque o primeiro cenário encontrou `Not Found` do Apache local na rota `/edicoes`, antes de executar os demais cenários; a falha é de roteamento do ambiente, não do HOM-015.

#### Validação da solução

Implementação concluída e validação automatizada direcionada aprovada. Permanece pendente o reteste visual no ambiente publicado e a execução da suíte HTTP/browser completa em ambiente isolado.

### HOM-016 — Gênero ausente no modal de edição de modalidade

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação, com evidências visuais anexadas.
- **Área/tela:** `/modalidades/detalhes`, modal **Editar Modalidade**.
- **Prioridade:** Média.
- **Status:** Implementado — validação técnica aprovada; reteste visual/browser pendente.

#### Descrição do erro

Uma modalidade podia ser criada com gênero normalmente, mas o campo **Gênero** não aparecia ao abrir o modal de edição. Consequentemente, não era possível revisar ou alterar o gênero de uma modalidade existente pela interface.

#### Passos para reproduzir

1. Criar uma modalidade informando nome, gênero, tipo e categoria.
2. Abrir os detalhes da modalidade.
3. Clicar em **Editar**.
4. Observar que o modal não apresenta o campo **Gênero**.

#### Resultado esperado

O modal de edição deve exibir o campo **Gênero**, selecionar o valor atualmente salvo e permitir alterá-lo para **Masculino**, **Feminino** ou **Misto**. Ao salvar, o novo valor deve permanecer após recarregar os detalhes.

#### Resultado encontrado

O modal apresentava nome, limites, tipo e categoria, mas não renderizava o select de gênero. O formulário também não enviava `genero_modalidade` na requisição de atualização.

#### Causa identificada

- O template `resources/views/pages/competicoes/modalidade-detalhes.php` não possuía o campo de gênero no formulário de edição.
- A função `abrirModalEdicao()` não preenchia o gênero atual.
- O payload `PUT` montado em `resources/js/pages/competicoes/modalidade-detalhes.js` não incluía `genero_modalidade`.
- O backend já aceitava, normalizava e validava o campo; portanto, não havia falha de banco ou necessidade de migration.

#### Correção aplicada

- Adicionado o select `editGeneroModalidade` ao modal de edição, com as opções `MASC`, `FEM` e `MISTO`.
- O valor retornado pela API passou a ser selecionado automaticamente ao abrir o modal.
- O gênero passou a ser enviado no `PUT /api/v1/modalidades`.
- Adicionada validação no formulário para impedir o salvamento sem gênero.
- Mantida a validação de domínio existente no `ModalidadeService`.

#### Arquivos principais

- `resources/views/pages/competicoes/modalidade-detalhes.php`
- `resources/js/pages/competicoes/modalidade-detalhes.js`
- `tests/Unit/Modules/Competicoes/ModalidadeServiceTest.php`

#### Validação executada

- `npm run build`: 132 assets preparados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 18 testes JavaScript aprovados.
- `php vendor/bin/phpunit --configuration phpunit.xml tests/Unit/Modules/Competicoes/ModalidadeServiceTest.php`: 4 testes e 7 asserções aprovados.
- `composer verify`: 197 testes e 2.039 asserções aprovados; uma depreciação do PHPUnit foi reportada sem falha.
- A suíte browser foi iniciada com 48 cenários, mas encontrou falhas anteriores em fluxos de autenticação/admin e não chegou a validar especificamente a edição do gênero; o reteste direcionado permanece pendente.

#### Validação da solução

Implementação concluída e validação técnica aprovada. Permanece pendente o reteste visual/browser no ambiente de homologação, confirmando a abertura do modal, a seleção do gênero atual e a persistência da alteração.

### HOM-017 — Erro 500 ao gerar chaveamento

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação, com evidência visual anexada.
- **Área/tela:** `/chaveamento`, botão **Gerar Chaveamento**.
- **Prioridade:** Alta.
- **Status:** Implementado — validação técnica aprovada.

#### Descrição do erro

Após selecionar uma modalidade com equipes e competidores vinculados, o botão **Gerar Chaveamento** retornava HTTP 500 e a tela exibia “Não foi possível processar o chaveamento.”.

#### Causa identificada

O fluxo atual cria jogos inicialmente sem programação, usando `NULL` para `data_jogo`, `inicio_jogo` e `locais_id_local`. Porém, o banco local ainda possuía o esquema anterior, no qual essas colunas eram `NOT NULL`. O log do servidor registrava:

> `Column 'data_jogo' cannot be null`

O histórico `sgi_migrations` continha apenas as migrações `001` a `004`, embora as migrações `005` a `008` já estivessem disponíveis no projeto.

#### Correção aplicada

- Aplicada a migração `database/migrations/005_agendamento_blocos.sql`, tornando opcionais os campos de programação dos jogos e criando a estrutura de reservas da agenda.
- Aplicadas também as migrações `006` e `007`.
- A migração `008_auth_version.sql` encontrou a coluna `auth_version` já existente com a definição correta (`INT UNSIGNED NOT NULL DEFAULT 1`); o marcador `dirty` foi reconciliado após a verificação da estrutura e da remoção do trigger antigo.
- O histórico de migrações foi confirmado com as versões `001` a `008` concluídas (`dirty = 0`).

#### Validação executada

- A tabela `jogos` passou a aceitar `NULL` em `data_jogo`, `inicio_jogo` e `locais_id_local`.
- Geração real com a modalidade de teste e três equipes: dois jogos criados sem erro; a transação foi revertida após a validação.
- Teste unitário do serviço de chaveamento: 8 testes e 29 asserções aprovados.
- A suíte HTTP isolada iniciou com 41 asserções aprovadas, mas foi interrompida posteriormente por uma falha independente na configuração do armazenamento de sessões de teste.

#### Arquivos e comandos relacionados

- `database/migrations/005_agendamento_blocos.sql`
- `database/migrations/006_unique_category_name_per_edition.sql`
- `database/migrations/007_publicacao_ranking.sql`
- `database/migrations/008_auth_version.sql`
- `src/Modules/Competicoes/Infrastructure/MysqliChaveamentoRepository.php`
- `php bin/sgi.php migrate`

#### Validação da solução

Implementação concluída e validação técnica aprovada. O erro 500 relacionado à criação de jogos sem data, horário e local foi eliminado no ambiente local de homologação.

---

### HOM-018 — Mesário acessava administração e recursos fora da edição ativa

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação e auditoria de segurança.
- **Área/tela:** páginas administrativas, APIs de recursos, login e sessão do mesário.
- **Prioridade:** Crítica.
- **Status:** Implementado tecnicamente — homologação HTTP/browser isolada pendente.

#### Descrição do erro

Depois de autenticar como mesário, era possível digitar URLs administrativas diretamente, como `/colaboradores`, `/edicoes/modalidades`, `/edicoes/pontuacao` e `/turmas/alunos`. Também havia risco de consultar ou alterar dados de outra edição informando IDs nos parâmetros das APIs.

#### Resultado esperado

O mesário deve acessar apenas as telas operacionais autorizadas e somente a edição ativa. Rotas administrativas devem ser bloqueadas antes da renderização; APIs devem retornar 403 para ações fora do papel ou do escopo. Uma sessão rebaixada, desativada ou invalidada por troca de credencial não pode continuar operando.

#### Causa identificada

- O `PageController` aplicava `[0, 1, 2]` como padrão para páginas da equipe.
- Vários controladores aceitavam `id_interclasse`, `id_turma`, `id_jogo` ou `id_equipe` fornecidos pelo cliente sem derivar o relacionamento da sessão e do banco.
- A sessão mantinha o nível e a edição capturados no login mesmo depois de alteração administrativa.
- O endpoint de logout aceitava GET, permitindo destruição de sessão por navegação involuntária ou requisição cross-site.
- Listagens de usuários e ocorrências podiam retornar campos além do necessário para a operação do mesário.

#### Correção aplicada

- Criado mapa explícito de níveis por rota web, com negação por padrão; páginas não autorizadas redirecionam para o destino operacional apropriado antes de carregar dados.
- Fechadas ações de usuários por finalidade; o mesário recebe somente a projeção operacional da edição ativa, sem data de nascimento ou dados administrativos.
- Adicionada a `SessionRevalidator` ao Kernel para consultar status, nível e `auth_version` em cada requisição protegida; a edição ativa do mesário é atualizada nesse momento.
- Criada a migration `database/migrations/008_auth_version.sql`; troca de senha, reset, desativação e alteração de papel incrementam a versão e invalidam sessões antigas.
- Aplicado escopo de edição em jogos, partidas, equipes, modalidades, categorias, locais, chaveamentos, ocorrências, artilharia, histórico, fotos e filtros SQL.
- Mesário não pode criar ou agendar jogos manualmente; continua autorizado a operar placar, cronômetro, ocorrências e avanço do chaveamento dentro da edição ativa.
- Logout passou a exigir POST com token CSRF; GET retorna HTTP 405 e as páginas autenticadas usam `Cache-Control: private, no-store`.
- Senhas temporárias de criação/reset de aluno deixaram de usar o valor universal `123`.

#### Testes de regressão adicionados

- `tests/Unit/Presentation/Web/PageControllerTest.php`: todas as rotas administrativas críticas são testadas com sessão de nível 2.
- `tests/Unit/Modules/Acesso/SessionControllerTest.php`: GET de logout não encerra a sessão.
- `tests/Unit/Shared/Database/SqlFiltersTest.php`: filtros de equipe e artilharia preservam a edição solicitada.
- `tests/Integration/AuthAndRbacTest.php`: acesso direto a páginas, consulta administrativa de usuários, validação cadastral legada, CSRF e logout.
- `tests/Integration/MesarioResourceScopeTest.php`: IDs de outra edição não permitem gravar artilharia, ocorrências ou partidas.
- `tests/browser/auth-rbac.spec.cjs`: navegação direta do mesário para URLs administrativas e logout pela interface.

#### Validação executada

- `composer verify`: 212 testes PHPUnit e 2.089 asserções aprovados; duas depreciações do PHPUnit foram reportadas sem falha.
- PHPStan: nenhum erro.
- PHP CS Fixer: 0 de 285 arquivos com correções.
- `npm test`: 21 testes JavaScript aprovados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm run build`: 132 assets preparados.
- `git diff --check`: concluído sem erros de whitespace.
- `php tests/run_all.php`: 388/388 asserções aprovadas no servidor e banco isolados.
- `npm --prefix tests/browser test`: 48 testes aprovados e 1 cenário opt-in ignorado; os testes de autorização do mesário passaram.
- `php tests/run_all.php` e a suíte Playwright completa não foram executados nesta rodada porque o daemon Docker/MariaDB não estava disponível e `SGI_TEST_BASE_URL` exige um servidor isolado.

#### Pendências registradas

O rate limiting persistente de login e a ativação formal por token individual de uso único ainda precisam ser implementados. A senha universal de criação/reset foi removida, mas o fluxo de ativação e a auditoria final da casca offline devem ser homologados em ambiente HTTP/MariaDB isolado antes da publicação.

---

### HOM-019 — Erro 500 ao carregar Alunos Destaques nas modalidades

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação, com evidências visuais e console anexados.
- **Área/tela:** `/edicoes/modalidades`, botão **Alunos Destaques**.
- **Prioridade:** Alta.
- **Status:** Implementado — integração e navegador aprovados; publicação pendente.

#### Descrição do erro

Ao clicar em **Alunos Destaques** na tela de modalidades, o modal era aberto, mas exibia a mensagem:

> `Erro ao carregar os destaques.`

No console do navegador, a chamada abaixo retornava HTTP 500:

> `GET /api/v1/artilheiros?acao=destaques_modalidades&id_interclasse=1`

O log do servidor registrava:

> `Unknown column 'm.id_modalidade' in 'where clause'`

#### Resultado esperado

O modal deve consultar a artilharia da edição selecionada e exibir os alunos com maior total de gols agrupados por modalidade. Quando não houver registros, deve informar que ainda não existem alunos destaque, sem erro de servidor.

#### Causa identificada

Em `MysqliArtilheiroQueries::revelarDestaquesPorModalidade()`, a consulta comparava o total de gols com o maior total encontrado em uma subconsulta derivada. Essa subconsulta tentava usar `m.id_modalidade`, alias pertencente à consulta externa, dentro do `FROM (...) AS sub`:

```sql
WHERE m2.id_modalidade = m.id_modalidade
```

MySQL/MariaDB não permite essa referência correlacionada nesse nível da subconsulta derivada. A exceção do banco não tratada pelo fluxo de leitura resultava em HTTP 500 e acionava a mensagem genérica do front-end. A consulta de destaque geral possuía o mesmo padrão, usando `c.id_categoria` dentro de uma subconsulta derivada.

#### Correção aplicada

- Substituída a subconsulta derivada inválida por uma condição `HAVING NOT EXISTS`, que elimina um aluno quando existe outro com total de gols maior na mesma modalidade.
- Mantido o suporte a empates: todos os alunos com o maior total continuam sendo retornados.
- Mantido o filtro por edição informado na tela e o filtro da edição ativa quando nenhum ID é fornecido.
- Incluídos no `GROUP BY` os campos não agregados selecionados, garantindo compatibilidade com `ONLY_FULL_GROUP_BY`.
- Aplicada a mesma correção estrutural à consulta de destaque geral por categoria.
- A migration `database/migrations/010_vinculo_obrigatorio_pontos.sql` fornece os campos de status e contribuição efetiva usados pela consulta para separar pontos ativos de ações anuladas; sua aplicação deve preceder a publicação desta versão.
- Não houve mudança de rota ou do contrato JSON consumido pelo front-end.

#### Arquivos principais

- `src/Modules/Competicoes/Infrastructure/MysqliArtilheiroQueries.php`
- `database/migrations/010_vinculo_obrigatorio_pontos.sql`
- `resources/js/pages/eventos/configurar-modalidades.js` — consumidor existente da API, sem alteração necessária.
- `tests/Integration/PlacarAndArtilhariaTest.php`
- `tests/browser/frontend-regression.spec.cjs`

#### Validação executada

- Regressão HTTP adicionada para `acao=destaques_modalidades` com e sem `id_interclasse`; a resposta foi HTTP 200, `success: true` e `data` em formato de lista.
- Migration 010 aplicada e repetida em MariaDB descartável; as colunas, índices e vínculos foram validados sem reaplicação indevida.
- `php tests/run_all.php`: 475/475 asserções aprovadas em servidor e banco isolados MariaDB.
- `composer verify`: 229 testes PHPUnit e 2.175 asserções aprovados.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 23 testes JavaScript aprovados.
- `npm run build`: 132 assets preparados.
- `npm --prefix tests/browser test`: 47/47 testes aprovados, incluindo a abertura do modal e a ausência da mensagem de erro.
- `git diff --check`: concluído sem erros de whitespace.

#### Validação da solução

O erro SQL que provocava o HTTP 500 foi eliminado. O endpoint utilizado pelo modal agora retorna o envelope JSON esperado, preservando a listagem de destaques por modalidade e os demais fluxos de artilharia.

---

### HOM-020 — Ranking do aluno dependia de publicação manual e aparecia no menu lateral

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação, com especificação visual e de regras anexada.
- **Área/tela:** portal do aluno — `/aluno/inicio`, `/aluno/ranking` e menu lateral.
- **Prioridade:** Alta.
- **Status:** Implementado — validação técnica concluída; homologação HTTP/browser isolada pendente por indisponibilidade do ambiente.

#### Descrição da falha

O portal do aluno ainda tratava o ranking como uma funcionalidade dependente de publicação manual. Em uma edição encerrada sem `ranking_publicado_em`, o card da home exibia **Ranking aguardando premiação**, não oferecia um link funcional e o aluno não conseguia consultar a classificação final. Ao mesmo tempo, o menu lateral sempre exibia o item **Rankings publicados**, embora esse não fosse mais o fluxo desejado.

Também era possível acessar diretamente `/aluno/ranking?id={ID}`. Para uma edição ativa, a tela não usava uma regra própria de estado: a API misturava a validação de edição ativa com a existência do campo de publicação e a mensagem apresentada dizia que o ranking era restrito a administradores.

#### Resultado esperado

- Edição ativa (`status_interclasse = '1'`): o card deve exibir **Ver Detalhes** e o ranking deve permanecer oculto.
- Edição encerrada (`status_interclasse = '0'`): o card deve exibir **Ver Ranking**, sem depender de publicação manual.
- Acesso direto a uma edição ativa deve retornar o estado **Ranking Oculto**, com cadeado e explicação.
- O menu lateral do aluno não deve conter o link de ranking.

#### Causa identificada

- `resources/views/components/aluno-nav.php` mantinha o item de ranking fixo no array do menu.
- `resources/js/pages/aluno/home.js` exigia `ranking_publicado_em` para montar o link de edições encerradas.
- `resources/js/pages/aluno/ranking.js` procurava uma edição encerrada que também tivesse publicação registrada.
- `src/Modules/Resultados/Presentation/Http/RankingController.php` bloqueava o aluno quando a edição não estava publicada ou ainda estava ativa.
- `src/Modules/Resultados/Infrastructure/MysqliRankingRepository.php` filtrava o ranking do aluno por status encerrado e publicação manual.

#### Correção aplicada

- Removido o item **Rankings publicados** do menu desktop e mobile do aluno.
- Alterada a home para decidir o destino apenas pelo `status_interclasse`: modalidades para edição ativa e ranking para edição encerrada.
- Alterada a seleção automática da rota de ranking para usar qualquer edição encerrada, sem consultar `ranking_publicado_em`.
- Atualizada a tela de ranking para mostrar **Ranking Oculto**, cadeado e mensagem orientativa quando a edição informada estiver ativa; filtros e contadores são limpos nesse estado.
- Alterada a proteção da API para bloquear somente edições ativas e renomeado o filtro interno para `somente_encerrados`.
- Alterada a consulta SQL do ranking para retornar dados de qualquer edição encerrada, mesmo que a coluna legada de publicação esteja nula.
- Removido do ranking administrativo o botão e o JavaScript de publicação manual, que não fazem mais parte do fluxo de negócio.
- A migration `007_publicacao_ranking.sql` não foi reescrita e as colunas legadas foram preservadas para compatibilidade; elas deixaram de controlar a visualização do aluno.

#### Testes de regressão adicionados

- `tests/Integration/AlunosPortalTest.php`: aluno bloqueado em edição ativa, ranking liberado após encerramento sem publicação manual e restauração da edição ativa.
- `tests/browser/aluno-portal.spec.cjs`: ausência do link no menu, botão **Ver Detalhes** na home ativa e estado **Ranking Oculto** no acesso direto.

#### Arquivos principais

- `resources/views/components/aluno-nav.php`
- `resources/js/pages/aluno/home.js`
- `resources/js/pages/aluno/ranking.js`
- `resources/views/pages/aluno/ranking.php`
- `resources/views/pages/resultados/ranking.php`
- `resources/js/pages/resultados/ranking.js`
- `src/Modules/Resultados/Presentation/Http/RankingController.php`
- `src/Modules/Resultados/Infrastructure/MysqliRankingRepository.php`

#### Validação executada

- `composer verify`: aprovado — 212 testes PHPUnit, 2.089 asserções, PHPStan sem erros e PHP CS Fixer sem correções.
- `npm run check`: aprovado — 40 arquivos JavaScript válidos.
- `npm test`: aprovado — 21 testes JavaScript.
- `npm run build`: aprovado — 132 assets preparados.
- `php -l` nos PHP alterados e `node --check` nos JavaScript alterados: aprovados.
- `git diff --check`: sem erros de whitespace.
- `php tests/run_all.php`: não executado porque `SGI_TEST_BASE_URL` e o servidor HTTP de teste não estavam configurados.
- `npm --prefix tests/browser test -- aluno-portal.spec.cjs`: não validado funcionalmente; a execução padrão apontou para o Apache local e recebeu `Not Found`, e a tentativa contra `127.0.0.1:8099` encontrou `ECONNREFUSED`.

#### Validação da solução

A regra do portal foi alinhada ao status da edição: rankings encerrados não dependem mais de publicação manual, enquanto rankings de edições ativas continuam protegidos no front-end e na API. A validação final em servidor, banco isolado e navegador permanece pendente até a disponibilidade desse ambiente.

---

### HOM-021 — Testes executados em Docker descartável

- **Data da implementação:** 09/09/2026.
- **Área:** automação de testes, homologação e CI.
- **Prioridade:** Alta.
- **Status:** Implementado — infraestrutura validada tecnicamente.

#### Solicitação

Garantir que os testes rodem em um ambiente isolado e descartável, sem depender de PHP, Composer, Node.js, MySQL/MariaDB ou Chromium instalados no host.

#### Solução aplicada

- Criadas imagens separadas para o executor PHP/Node/Composer e para Playwright/Chromium.
- O `compose.test.yml` passou a orquestrar banco, servidor HTTP, qualidade, integração, navegador e contrato visual.
- O banco usa `tmpfs`; as sessões usam um volume Docker temporário, removido ao final da execução.
- O fluxo aceita MariaDB 10.11 e MySQL 8.4, além de PHP 8.2 e 8.4.
- Os scripts `tools/test-docker.ps1` e `tools/test-docker.sh` executam as suites e limpam containers, rede e volumes automaticamente; `-Keep`/`--keep` permanece disponível para investigação.
- O contrato visual passou a usar snapshots Linux próprios (`*-linux.png`), mantendo as referências Windows existentes.
- O CI foi migrado para Docker para as etapas de qualidade, integração, navegador e contrato visual.
- Ajustes SQL tornaram a suíte compatível com `ONLY_FULL_GROUP_BY` e a consulta de bloqueios compatível com MySQL e MariaDB.

#### Comandos de homologação

```text
powershell -File tools/test-docker.ps1 -Database mariadb
powershell -File tools/test-docker.ps1 -Database mysql
sh tools/test-docker.sh --database mariadb --include-visual
```

#### Validação executada

- `docker compose -f compose.test.yml config --quiet`: aprovado.
- Qualidade em Docker: 214 testes PHPUnit, 2.091 asserções, PHPStan sem erros, PHP CS Fixer sem correções e 21 testes JavaScript aprovados.
- Integração MariaDB: 406/406 asserções aprovadas.
- Navegador Playwright: 46 testes aprovados e 1 cenário opt-in existente; contrato visual Linux: 2 testes aprovados.
- MySQL 8.4: execução da integração e do navegador concluída com sucesso em rodada anterior.
- `git diff --check`: aprovado.
- Ao finalizar, containers, rede e volume de sessões são removidos automaticamente, sem deixar dados persistentes da rodada.

#### Observação da rodada

A execução mais recente no workspace encontrou falhas no fluxo antigo de artilharia/placar porque alterações de domínio adicionadas em paralelo passaram a exigir pontos vinculados a atletas e retornam HTTP 422 para os testes antigos. Essa falha não é causada pelo executor Docker; os arquivos dessas alterações não foram modificados neste ajuste.

---

### HOM-022 — Penalidade negativa em ocorrência de turma invertia o ranking

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Evidência analisada:** `C:\Users\ferreira-mr\Downloads\Pontuação.pdf`.
- **Reportado por:** homologação.
- **Área/telas:** `/ocorrencias`, `/api/v1/ocorrencias-turmas` e ranking da turma.
- **Prioridade:** Alta.
- **Status:** Implementado — regressão MariaDB aprovada; cenário de ocorrência no navegador aprovado; ressalvas de compatibilidade MySQL registradas abaixo.

#### Distinção entre evidência e instrução

O PDF foi utilizado como **evidência do comportamento observado** e não como instrução de alteração do sistema. A correção foi definida a partir da regra de negócio existente: pontos de ocorrência representam uma penalidade e devem ser armazenados como magnitude positiva, sendo descontados uma única vez no cálculo do ranking.

#### Descrição da falha

Ao informar uma ocorrência de turma com pontuação negativa, o valor era salvo com o sinal negativo. Como o ranking já aplicava a fórmula `pontuação acumulada - penalidades`, uma penalidade de `-100` era convertida em `+100` no ranking. Na prática, a turma recebia bônus em vez de perder pontos. A remoção da ocorrência precisava, ainda, devolver o ranking exatamente ao valor anterior, sem deixar efeito residual.

#### Resultado esperado

- Qualquer pontuação de ocorrência deve ser tratada como magnitude não negativa.
- Uma ocorrência de `4` ou `-4` deve reduzir o ranking em exatamente `4` pontos.
- A penalidade não pode ser aplicada duas vezes nem transformar-se em bônus.
- A remoção de uma ocorrência ativa deve restaurar exatamente o ranking calculado antes dela.
- Registros antigos negativos devem ser corrigidos sem reescrever migrações já aplicadas.
- Ocorrências individuais continuam usando inativação lógica; o ajuste não altera esse contrato nem cria exclusão física indevida.

#### Causa identificada

- `OcorrenciaTurmaService` encaminhava o valor recebido sem normalizar o sinal.
- A validação HTML com `min="0"` não era uma proteção suficiente para requisições manuais ou clientes antigos.
- A tabela não possuía uma invariável de banco que impedisse valores negativos.
- O cálculo do ranking já subtraía a soma das penalidades; por isso, aceitar um valor negativo invertia a regra.

#### Correção aplicada

- Normalização centralizada com `abs()` em `src/Modules/Disciplina/Application/OcorrenciaTurmaService.php`, protegendo a regra também para chamadas HTTP diretas.
- Normalização no formulário de `resources/js/pages/disciplina/ocorrencias.js`, incluindo correção visual do campo quando o usuário digita um valor negativo manualmente.
- Nova migration `database/migrations/009_occurrence_penalty_invariant.sql` para:
  - converter registros legados negativos para seus valores absolutos;
  - impedir novos valores negativos em `ocorrencias_turmas` e `ocorrencias` com `CHECK constraints`;
  - manter as migrações anteriores intactas e permitir atualização/reexecução segura.
- Preservado o endpoint existente de remoção de ocorrência de turma.
- Mantida a inativação lógica das ocorrências individuais, com `status_ocorrencia = 0`.
- Ajustado o ensaio de recuperação para descobrir dinamicamente todas as migrations, evitando que novas migrations quebrem o teste por contagem fixa.

#### Testes de regressão adicionados ou ajustados

- `tests/Unit/Modules/Disciplina/OcorrenciaTurmaServiceTest.php`: valor negativo normalizado para magnitude positiva antes da persistência.
- `tests/Unit/Modules/Disciplina/OcorrenciaServiceTest.php`: penalidade individual negativa rejeitada quando o usuário é válido.
- `tests/Integration/OcorrenciasAndRankingTest.php`: valor negativo persistido como positivo, desconto aplicado uma única vez e restauração exata após remoção.
- `tests/Integration/MigrationsTest.php`: presença das duas restrições `CHECK` da migration 009.
- `tests/Integration/RecoveryRehearsalTest.php`: aplicação e repetição das migrations sem depender de quantidade fixa.
- `tests/browser/occurrence-offline-edit.spec.cjs`: fluxo de ocorrência validado no navegador em execução isolada; o teste passou com `1/1` cenário.

#### Validação executada

- Suíte de qualidade em Docker: **214 testes PHPUnit, 2.091 asserções**, PHPStan sem erros, PHP CS Fixer sem correções, `npm run check` com 40 arquivos JavaScript válidos e `npm test` com 21 testes aprovados.
- Suíte HTTP, banco e recuperação em MariaDB isolado: **406/406 asserções aprovadas**.
- Regressões específicas de ocorrência e ranking aprovadas em MariaDB e nos cenários correspondentes do MySQL 8.4.
- `php -l` nos arquivos PHP alterados, `node --check` nos JavaScript alterados e `git diff --check`: aprovados.
- Cenário browser diretamente relacionado à ocorrência offline: **1/1 aprovado**.

#### Ressalvas da homologação

- A primeira execução concorrente do executor Docker sofreu encerramento do container da aplicação por pressão de memória do ambiente. A execução foi repetida de forma isolada, com a suíte de qualidade antes da inicialização do servidor, e foi aprovada.
- A suíte browser agregada foi interrompida por `SIGTERM` do ambiente antes do resumo final; o cenário diretamente relacionado à ocorrência foi executado separadamente e aprovado.
- A rodada completa em MySQL 8.4 ainda apresenta falhas preexistentes em cenários de placar/artilharia que passaram a exigir pontos vinculados a atletas e retornam HTTP 422. Os testes de ocorrência passaram, e nenhum ajuste fora desse fluxo foi incluído para mascarar essa falha de domínio.

#### Critério de aceite

Considera-se corrigido o erro de pontuação negativa quando o valor persistido é não negativo, a redução do ranking ocorre uma única vez, a remoção restaura o total anterior e a migration impede regressão por novas requisições ou dados inválidos. A confirmação visual no ambiente publicado permanece como etapa operacional da homologação.

---

### HOM-023 — Aluno acessava o sistema antes de aceitar as regras

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação.
- **Área/telas:** `/aluno/termos`, `/aluno/inicio`, `/aluno/modalidades`, `/aluno/jogos`, `/aluno/perfil`, `/aluno/ranking` e APIs do portal do aluno.
- **Prioridade:** Crítica.
- **Status:** Implementado — bloqueio server-side e regressões automatizadas aprovados; confirmação no ambiente publicado permanece como etapa operacional.

#### Descrição da falha

Foi reproduzido que um aluno autenticado, sem aceitar as regras de participação, conseguia acessar por URL direta telas de jogos e modalidades/inscrições. A mesma brecha permitia tentar consultar ou alterar recursos pelas APIs, contornando o bloqueio visual apresentado na navegação normal.

#### Resultado esperado

- Nenhum aluno pode acessar qualquer funcionalidade do sistema antes de aceitar as regras.
- O único fluxo disponível antes do aceite é a leitura/aceite dos termos, além da consulta necessária ao regulamento ativo.
- O bloqueio deve ocorrer no servidor, independentemente de o acesso ser feito pelo menu, por URL digitada, por recarregamento da página ou por chamada direta à API.
- Depois do aceite, o aluno deve seguir normalmente para o portal, jogos, modalidades/inscrições e demais recursos autorizados.
- A retirada do aceite no banco deve invalidar o acesso de uma sessão já aberta no próximo request, sem depender de novo login.

#### Causa identificada

- O controle anterior dependia principalmente do fluxo de navegação e do estado carregado no cliente.
- O `PageController` e as APIs não aplicavam uma política única para impedir o acesso de aluno sem aceite.
- A sessão precisava refletir o estado persistido em `usuarios_has_interclasses.aceito_termo`, inclusive quando esse estado fosse alterado depois do login.

#### Correção aplicada

- O repositório de usuários passou a carregar o estado de aceite a partir do vínculo do usuário com a edição.
- O login e o revalidador de sessão passaram a atualizar `$_SESSION['termo_aceito']` com o valor atual do banco.
- O `Kernel` passou a aplicar a barreira antes do processamento das rotas protegidas:
  - páginas web redirecionam para `/aluno/termos`;
  - APIs retornam HTTP 403 com a mensagem `Aceite os termos de responsabilidade para continuar.` e indicação do redirecionamento;
  - ficam liberados somente `/aluno/termos`, `GET /api/v1/termos` e a consulta sem identificador ao regulamento da edição ativa.
- A proteção específica do portal foi mantida no `PageController`, evitando acesso direto às cinco páginas do aluno mesmo fora da navegação normal.
- A navegação do aluno sem aceite exibe somente Termos e Sair; após a confirmação, o botão conclui o aceite e encaminha o aluno para `/aluno/inicio`.
- O aceite é idempotente: repetir a confirmação não duplica o registro nem altera o resultado já aceito.

#### Testes de regressão adicionados ou ajustados

- `tests/Integration/AlunosPortalTest.php`:
  - login inicial de aluno sem aceite direciona para Termos;
  - todas as páginas protegidas do portal são bloqueadas por URL;
  - matriz das APIs protegidas retorna HTTP 403;
  - tentativas de contornar a regra usando filtros no endpoint de edições também são bloqueadas;
  - Termos, status dos termos e regulamento ativo permanecem acessíveis;
  - aceite repetido é idempotente;
  - revogação direta no banco é percebida pela sessão já existente e volta a bloquear jogos/páginas.
- `tests/Unit/Presentation/Web/PageControllerTest.php`: data provider cobrindo início, modalidades, jogos, perfil e ranking, com redirecionamento e `Cache-Control: no-store`.
- `tests/browser/aluno-portal.spec.cjs`: percurso real no navegador, URLs diretas, menu restrito, consulta de jogos e tentativa de inscrição antes do aceite; depois do aceite, acesso normal ao portal.
- Fixtures de `tests/browser/auth-rbac.spec.cjs`, `tests/browser/mesario-offline.spec.cjs` e `tests/browser/occurrence-offline-edit.spec.cjs` ajustadas para aceitar os termos antes dos fluxos que dependem do portal do aluno.
- `tests/Integration/AuthAndRbacTest.php` e `tests/Integration/InscricaoModalidadesTest.php` atualizados para preservar o comportamento esperado de login e inscrição após o aceite.

#### Validação executada

- Qualidade em Docker: **229 testes PHPUnit e 2.175 asserções aprovados**, PHPStan sem erros, PHP CS Fixer aprovado, `npm run check` com 40 arquivos JavaScript válidos, `npm test` com 23 testes aprovados e `npm run build` com 132 assets preparados.
- Suíte HTTP, banco e recuperação em ambiente isolado: os cenários do portal do aluno e do aceite foram aprovados. A execução agregada mais recente terminou com **473 de 473 asserções aprovadas**.
- A validação da agenda automática terça/quinta também foi concluída separadamente e não apresenta pendências de integração; os cenários browser legados de placar permanecem acompanhados na seção HOM-008.
- Teste browser direcionado do Portal do Aluno: execução final aprovada; um cenário precisou de nova tentativa após erro transitório de conexão (`ERR_CONNECTION_REFUSED`) e passou no retry.
- `git diff --check`: aprovado.

#### Critério de aceite

Considera-se corrigida a falha quando um aluno sem aceite não consegue acessar páginas, jogos, inscrições ou APIs protegidas por menu, URL direta ou chamada manual; somente o fluxo de termos permanece disponível; o aceite libera o portal; e a revogação persistida volta a bloquear a sessão sem novo login. A validação da agenda possui registro próprio em HOM-008 e não altera este critério de aceite.

---

### HOM-024 — Ponto lançado sem vínculo obrigatório ao atleta

- **Data do relato:** 09/09/2026.
- **Data da implementação:** 09/09/2026.
- **Reportado por:** homologação e revisão da regra de lançamento de ponto.
- **Área/telas:** `/jogos/placar`, `/api/v1/pontos`, `/api/v1/artilheiros`, `/api/v1/partidas` e operação offline do mesário.
- **Prioridade:** Crítica.
- **Status:** Implementado — integração completa e suíte browser aprovadas; publicação pendente.

#### Descrição dos erros encontrados

O botão `+` alterava o placar antes de o mesário informar o atleta responsável pela jogada. O modal de artilharia era aberto depois do incremento e podia ser fechado sem uma identificação válida. Também existiam caminhos alternativos que permitiam alterar `resultado_partida` ou gravar artilharia sem a relação simultânea entre jogo, partida, equipe e atleta.

Na consulta do elenco, a seleção podia ser montada por turma, misturando alunos inscritos em equipes diferentes da mesma turma. Isso permitia atribuir a jogada à equipe incorreta. O fluxo de anulação ainda precisava retirar somente a contribuição do placar, mantendo a ação individual para o relatório final.

Durante a regressão do torneio 100% offline foi encontrado um segundo defeito: uma jogada de jogo temporário era projetada no placar duas vezes. O teste esperava `03–00`, mas a tela remontada apresentava `06–00`.

#### Resultado esperado

- Clicar em `+` deve abrir a seleção sem alterar placar, histórico ou fila.
- O seletor deve listar exclusivamente atletas competidores, ativos e inscritos na equipe exata da partida.
- A confirmação sem atleta deve ser bloqueada com a mensagem `Selecione o aluno responsável pela jogada para confirmar o ponto.`.
- A confirmação deve criar uma única jogada vinculada e incrementar o placar na mesma operação transacional.
- Reenviar a mesma jogada não pode criar outro ponto nem outro incremento.
- Anular uma jogada deve diminuir o placar uma única vez, sem excluir ou apagar a ação do atleta.
- O comportamento deve permanecer consistente após recarga, navegação SPA, operação offline e sincronização.

#### Causas identificadas

- A interface misturava a ação visual de incrementar o placar com a confirmação da artilharia.
- A validação do atleta não era uma regra centralizada do servidor; o endpoint legado de artilharia e alterações diretas de partida podiam contornar o vínculo.
- A consulta do modal não restringia todos os critérios à equipe exata e à inscrição ativa no jogo.
- O modelo anterior não diferenciava adequadamente ação válida, ação anulada e contribuição efetiva ao placar.
- A projeção de jogo temporário executava o incremento no ramo offline e novamente no tratamento comum de sucesso.
- A reconstrução do chaveamento podia excluir a partida referenciada pela ação, ameaçando a preservação do histórico.

#### Correção aplicada

- Criada a migration `database/migrations/010_vinculo_obrigatorio_pontos.sql`, com:
  - referência à partida e à equipe da jogada;
  - atleta e operador responsáveis;
  - chave idempotente `chave_jogada`;
  - estado `ativo`/`anulado` e `conta_no_placar`;
  - dados de autoria e data da anulação;
  - índices e chaves estrangeiras para preservar a integridade;
  - marcação explícita de jogos anteriores como legados, sem reescrever seus placares históricos.
- Criados `PontoService`, `PontoRepository`, `MysqliPontoRepository` e `PontoController` para concentrar o contrato de pontos.
- Implementado `GET /api/v1/pontos?acao=atletas&id_jogo=...&id_equipe=...`, que devolve somente atletas de nível competidor, ativos, inscritos na equipe exata e elegíveis disciplinarmente para o jogo.
- Implementado `POST /api/v1/pontos`, que valida jogo, partida, equipe, edição, modalidade coletiva, atleta, inscrição, elegibilidade e chave idempotente dentro da transação; o placar é incrementado junto com a jogada.
- Implementado `PUT /api/v1/pontos`, que marca a jogada como anulada, registra a autoria da operação e reduz a contribuição do placar sem remover o registro histórico.
- O front-end de `resources/js/pages/competicoes/placar.js` passou a abrir o modal antes de qualquer alteração, bloquear seleção vazia, enviar o atleta e exibir ações anuladas separadamente das válidas.
- As rotas antigas de artilharia e mutações diretas de partida foram bloqueadas para novas pontuações isoladas. A finalização e a sincronização conferem que o placar de jogos novos corresponde às jogadas ativas vinculadas.
- O IndexedDB passou a armazenar pontos, anulações e elencos por equipe; o pré-carregamento não mistura atletas de equipes distintas e a fila mantém a mesma intenção até a confirmação remota.
- A projeção de jogos temporários foi corrigida para incrementar cada ponto exatamente uma vez. O caso `06–00` foi eliminado sem alterar a expectativa do teste.
- Antes de remover partidas durante a reconstrução de chaveamento, o histórico é desvinculado da linha física da partida, preservando jogo, equipe, atleta, estado e autoria da ação.

#### Testes de regressão adicionados ou ajustados

- `tests/Unit/Modules/Competicoes/PontoServiceTest.php`: contrato de validação, ausência de atleta, escopo da equipe, idempotência e anulação.
- `tests/Integration/PlacarAndArtilhariaTest.php`: ponto sem atleta, elenco exato, repetição da chave, equipe adversária, anulação com histórico preservado, relatório e finalização.
- `tests/Integration/ConsistencyGuardsTest.php`: rejeição de alteração direta de placar e exigência de jogadas vinculadas para finalizar o jogo.
- `tests/Integration/MesarioResourceScopeTest.php`: bloqueio de recursos de outra edição e proteção das mutações do mesário.
- `tests/Integration/TemporaryResolutionScopeTest.php` e `tests/E2E/FullOfflineTournamentTest.php`: pontos vinculados em jogos temporários, resolução de IDs e sincronização do torneio offline.
- `tests/Integration/PodiumCreditTest.php`: retificação por eventos, preservação do histórico e não duplicação de créditos.
- `tests/javascript/mesario-data.test.cjs`: projeção e anulação offline, além da regressão que mantém o mesmo atleta disponível quando ele participa de equipes diferentes.
- `tests/browser/score-persistence.spec.cjs`, `tests/browser/mesario-offline.spec.cjs` e `tests/browser/tournament-offline.spec.cjs`: modal obrigatório, fila, remontagem da tela, anulação e torneio online/offline completo.
- `tests/browser/frontend-regression.spec.cjs`: abertura do modal **Alunos Destaques**, confirmação de HTTP 200 e ausência da mensagem de erro na página de modalidades.

#### Validação executada

- `php tests/run_all.php`: **475/475 asserções aprovadas**, incluindo a nova suíte de placar, artilharia, escopo, relatório de destaques e torneio offline.
- `composer verify`: **229 testes PHPUnit e 2.175 asserções aprovados**, PHPStan sem erros e PHP CS Fixer sem correções; duas depreciações do PHPUnit foram reportadas sem falha.
- `npm run check`: **40 arquivos JavaScript válidos**.
- `npm test`: **23/23 testes JavaScript aprovados**.
- `npm run build`: **132 assets preparados**.
- `npm --prefix tests/browser test`: **47/47 testes browser aprovados**, incluindo os cenários de persistência do placar, mesário offline, chaveamento e torneio completo online/offline.
- `git diff --check`: aprovado, sem erros de whitespace.

#### Critério de aceite

Considera-se corrigida a ocorrência quando nenhum novo ponto pode existir sem atleta e partida/equipe válidos; o seletor não mistura elencos; a confirmação é atômica e idempotente; a anulação reduz o placar sem apagar a ação; e o mesmo comportamento é mantido após recarga, operação offline, sincronização e reconstrução do chaveamento.
