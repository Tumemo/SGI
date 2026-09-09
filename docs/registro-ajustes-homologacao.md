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
| HOM-008 | Chaveamento / agenda / mesário | Jogos recebem programação padrão repetida; a organização precisa agendar por blocos e o mesário deve receber apenas programação completa. | Geradores preenchem data/horário/local automaticamente; fases futuras ainda não têm uma reserva própria e criação/edição divergem na validação de conflitos. | Implementados agenda nula na geração, reservas futuras, simulador determinístico, confirmação idempotente em bloco, validação de conflitos e filtro operacional do mesário. | `Validado tecnicamente — homologação publicada pendente` |
| HOM-009 | Tabelas administrativas | As tabelas apresentavam estilos inconsistentes, células comprimidas e rolagem ruim em telas estreitas. | Regras distribuídas entre Bootstrap e componentes específicos; regra duplicada removia o espaçamento da tabela de elenco; tabelas largas não mantinham largura mínima legível no celular. | Base visual compartilhada, espaçamento corrigido, rolagem horizontal interna e aplicação do padrão em jogos, ocorrências e arrecadações. | `Validado tecnicamente — reteste visual pendente` |
| HOM-010 | Categorias / edições | O sistema permitia criar duas categorias com o mesmo nome dentro da mesma edição. | Não havia validação de duplicidade no serviço nem restrição única no banco; o mesmo problema poderia ocorrer em requisições concorrentes. | Validação no serviço para criação/edição, restrição única por edição no banco, conversão de conflito para HTTP 409 e testes de regressão. | `Validado tecnicamente — migração condicionada à auditoria de dados` |
| HOM-011 | Todas as páginas | O título do navegador podia repetir `SGI` ou incluir o nome da tela, interclasse ou turma. | O título era definido em múltiplas views e reescrito por scripts da navegação administrativa, turma de alunos e operação offline. | Componente único com `<title>SGI</title>`, remoção dos títulos por página e bloqueio das substituições contextuais no JavaScript. | `Validado tecnicamente — reteste visual pendente` |
| HOM-012 | Modalidades individuais / placar | O PDF revisado mostra Corrida como “Final — Confronto 1”, com relógio e placar coletivo. | Há dependência de ID fixo/tag na seleção da interface; a causa exata no ambiente do relato ainda exige confronto de cadastro, API e assets/cache. | Plano corretivo V2: identificação pelo tipo, auditoria dos jogos existentes, início e formulário individual, integridade, fila offline e testes completos do percurso. | `Implementada tecnicamente — auditoria de dados e homologação HTTP/browser pendentes` |
| HOM-015 | Dashboard / edições inativas | Ao abrir uma edição inativa, o sistema informava que ela não havia sido finalizada e oferecia o botão `Concluir criação`. | O dashboard tratava qualquer edição com status diferente de `1` como não finalizada e montava um link para o fluxo de resumo/ativação. | Substituição pela mensagem exata `O interclasse está inativo no momento.`, sem botão e sem redirecionamento para `/edicoes/resumo`. | `Implementado — validação direcionada aprovada` |
| HOM-016 | Modalidades / edição de modalidade | O campo de gênero não aparecia no modal de edição, impedindo alterar o gênero de uma modalidade já criada. | O modal de edição não renderizava o select de gênero e o JavaScript não carregava nem enviava `genero_modalidade`, embora o backend já aceitasse o campo. | Inclusão do select de gênero no modal, preenchimento com o valor atual, envio no `PUT` e validação do formulário. | `Implementado — validação técnica aprovada; reteste visual/browser pendente` |
| HOM-017 | Chaveamento / geração | O botão **Gerar Chaveamento** retornava HTTP 500 depois do preenchimento dos dados obrigatórios. | O código passou a criar jogos com data, horário e local nulos, mas a base ainda estava somente até a migração 004 e mantinha essas colunas como `NOT NULL`. | Aplicação das migrações pendentes, reconciliação segura da migração 008 já parcialmente refletida no banco e validação da geração com equipes reais. | `Implementado — validação técnica aprovada` |
| HOM-018 | Autorização / sessão / escopo do mesário | O mesário conseguia acessar telas administrativas por URL direta e consultar recursos de outras edições por filtros manipulados. | O `PageController` herdava níveis permissivos, APIs confiavam em IDs enviados pelo cliente e sessões não eram revalidadas contra alterações de papel, senha ou status. | Política explícita por rota, escopo derivado do servidor, revalidação com `auth_version`, logout somente por POST/CSRF, projeções mínimas e testes de regressão. | `Validado tecnicamente — homologação publicada pendente` |

> A validação técnica das ocorrências HOM-001 a HOM-011, HOM-013, HOM-014, HOM-015 e HOM-016 foi registrada abaixo. HOM-012 foi reaberta após o PDF revisado; seus testes anteriores são evidência histórica, sem aceite do percurso completo. O responsável pela homologação ainda pode repetir os fluxos já corrigidos no ambiente publicado para registrar a confirmação visual final.

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
| 09/09/2026 | Implementação da programação em blocos, reservas futuras e filtro operacional do mesário. | Chaveamento / agenda / mesário | 373 asserções HTTP aprovadas, testes unitários, análise estática, build e regressão de materialização offline aprovados; browser/publicado pendentes |
| 09/09/2026 | Fechamento de autorização por rota, escopo de recursos, revalidação de sessão, logout com POST/CSRF e regressões do mesário. | Acesso / APIs / mesário | PHPUnit 212/212, 2.089 asserções, JavaScript 21/21, análise estática e build aprovados; HTTP/browser isolados pendentes |

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
- [ ] Retestar visualmente os bundles CSS e a navegação offline após a reestruturação (HOM-013).
- [ ] Retestar visualmente o campo de gênero no modal de edição de modalidade e confirmar a persistência da alteração (HOM-016).
- [ ] Retestar visualmente no ambiente publicado o comportamento do dashboard para uma edição inativa (HOM-015).
- [ ] Anexar evidências finais do reteste do usuário, se exigido pelo processo.
- [x] Ocorrências técnicas registradas com causa e solução.
- [x] Correções críticas validadas por testes automatizados.
- [x] Servidores temporários de teste encerrados.

## Encerramento da rodada

- **Total de ocorrências registradas:** 16.
- **Validadas tecnicamente:** 14 (HOM-012 tem implementação e testes locais, mas permanece fora do aceite publicado).
- **Em reteste após implementação:** 4 (HOM-013, HOM-014, HOM-015 e HOM-016).
- **Abertas:** 1 (HOM-012).
- **Reabertas:** 1 (HOM-012, incluída nas abertas).
- **Observação final:** a confirmação visual do responsável pela homologação deve ser registrada após o reteste no ambiente publicado.

## HOM-008 — Programação em blocos pela organização

- **Data da solicitação e do plano:** 09/09/2026.
- **Origem:** teste de homologação e orientação posterior do responsável.
- **Status:** Implementado — validado tecnicamente.
- **Problema:** data, horário e local são repetidos automaticamente na geração; o fluxo atual também permite inconsistência na validação de sobreposição entre criação e edição.
- **Comportamento solicitado:** a organização define todos os horários; o mesário recebe apenas programação completa.
- **Solução aplicada:** gerar confrontos sem agenda automática, reservar previamente fases futuras e oferecer assistente de agendamento em blocos com seleção, janelas por dia/local, duração, troca, descanso, prévia e confirmação integral.
- **Regra de conflito proposta:** impedir sobreposição no mesmo espaço e verificar participantes e dependências. Substitui a interpretação anterior de permitir sobreposição deliberada.
- **Offline:** carregar reservas das fases futuras para que o mesário avance o torneio sem precisar agendar; preservar resultados e tratar divergências de revisão na reconexão.
- **Plano detalhado vigente:** [Agendamento em blocos e programação do mesário](plano-agendamento-em-blocos.md).
- **Plano anterior:** `plano-ajuste-chaveamento-agenda.md`, identificado como substituído.
- **Validação técnica:** suíte unitária do algoritmo, suíte HTTP completa, análise estática, build e regressões de materialização offline aprovados. A suíte HTTP terminou com 388 asserções e nenhuma falha.
- **Pendência:** confirmação dos responsáveis pela homologação no ambiente publicado.

#### Implementação realizada

- A geração de chaveamentos, jogos individuais e materializações passa a iniciar com data, horário e local nulos quando não existe uma reserva confirmada.
- O endpoint `POST /api/v1/agenda-blocos` oferece simulação e confirmação para níveis administrador/colaborador, com seleção de jogos ou posições futuras, janelas, locais, duração, intervalo de troca, descanso, dependências e revisão.
- O algoritmo evita sobreposição no local, conflito conhecido de participantes e programação de uma fase dependente antes do término da fase anterior; a confirmação rejeita propostas incompletas.
- As reservas futuras são gravadas sem jogo fictício e aplicadas quando o confronto real é materializado, preservando data, local e duração.
- O mesário consulta somente jogos operacionais com agenda completa; não agenda nem reprograma jogos.
- A confirmação é transacional, idempotente e mantém histórico da alteração.

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

- `php tests/run_all.php`: 388/388 asserções aprovadas.
- `composer verify`: 212 testes PHPUnit e 2.089 asserções aprovados; duas depreciações do PHPUnit, sem falha.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 21 testes aprovados.
- `npm run build`: 132 assets preparados.
- `npm --prefix tests/browser test`: 48 testes aprovados e 1 cenário opt-in ignorado (`individual-ranking.spec.cjs`).
- Contratos visuais de login desktop/mobile: 2 testes aprovados.
- A validação confirmou: geração sem data/horário/local fictícios, prévia sem escrita, confirmação transacional, idempotência, revisão concorrente, conflitos de local/participantes, reservas futuras e operação offline do mesário sem agendamento local.

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
