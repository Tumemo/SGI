# Auditoria do SGI — 11/09/2026

Referência: `f4c3f4902effde9a5e2e862d5481dd13909673f6`. Checkout inicialmente sem alterações. Entrega documental: nenhuma correção funcional foi implementada nesta revisão.

**Há problemas restantes de autorização, integridade e validação.** O plano de 07/09 já foi encerrado; esta auditoria não reabre automaticamente T00–T29. O novo ponto de entrada é [plano de melhorias para Luna](plano-melhorias-luna-2026-09-11/README.md).

## Alcance e grau de certeza

Foi feito inventário transversal do código próprio, buscas de padrões e leitura dirigida das rotas, proteção HTTP/sessão, serviços e repositórios dos sete módulos, migrações, fontes frontend/offline, testes e executores. O inventário encontrou 223 arquivos em `src`, 99 em `resources`, 148 em `tests`, 12 em `database`, 10 em `tools`, dois em `config` e dois em `bootstrap`. Esses números representam arquivos encontrados, não leitura manual linha a linha de todos eles.

O trabalho é uma revisão ampla orientada por risco, não prova de ausência de outros defeitos. Não houve auditoria interna de dependências de terceiros, consulta de vulnerabilidades externas, medição de carga, execução de CI remoto ou inspeção visual de todas as telas. Testes HTTP/banco e navegador não foram concluídos nesta execução.

Classificações usadas:

- **Reproduzido em camada:** executado com objetos reais de aplicação/controlador e repositórios substitutos, sem banco. Confirma o comportamento daquela camada, não a execução HTTP completa.
- **Confirmado por inspeção:** o caminho de código permite demonstrar a ausência ou inconsistência indicada; falta regressão integrada para registrar o resultado persistido.
- **Risco a reproduzir:** depende de concorrência/ambiente; não deve ser anunciado como falha observada.

## Evidência executada agora

| Comando | Resultado real |
| --- | --- |
| `powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local` | Falhou. Composer válido; PHPUnit 233 testes/2.186 asserções com duas depreciações; lint PHP aprovado; PHPStan sem erros; CS Fixer encontrou 292 de 293 arquivos corrigíveis e retornou 8. Integração e navegador não chegaram a executar. |
| `C:\xampp\php\php.exe vendor/bin/phpunit --configuration phpunit.xml --display-phpunit-deprecations` | 233 testes/2.186 asserções, sem falhas; duas depreciações identificadas. |
| `npm run build` | Passou; 104 arquivos de assets preparados. |
| `npm run check` | Passou; 42 arquivos JavaScript válidos. |
| `npm test` | Passou; 95 testes, zero falhas/skips. |
| `C:\xampp\php\php.exe test-results/auditoria-20260911-probes.php` | Confirmou quatro comportamentos de camada descritos abaixo. Sonda local descartável, não teste de regressão incorporado à suíte. |
| `git ls-files --eol src/Modules/Competicoes/Application/PontoService.php` | Índice LF, working tree CRLF, sem atributo explícito. Não há `.gitattributes` na referência. |
| `docker info --format '{{.ServerVersion}}'` | Acesso negado à configuração/API Docker nesta sessão. Não demonstra ausência do Docker nem servidor desligado. |

Log local: `test-results/auditoria-20260911-all.log` (ignorado pelo Git). A limpeza do executor tentou acessar `127.0.0.1:3306` e recebeu `ERROR 2002 ... (10061)`. Esse erro de limpeza não substitui a falha primária de estilo e não comprova que migrações/integração tenham executado. Não foram lidas credenciais do `.env` nem preparados fixtures na base de trabalho.

## Achados prioritários

### C01 — P1: pontos não autorizam a edição do recurso

**Confirmado por inspeção.** `src/Modules/Competicoes/Presentation/Http/PontoController.php:60` chama `CompetitionAccess::authorize()` sem edição. Esse método só aplica `EdicaoAccessPolicy` quando recebe o ID do recurso. No GET, a verificação exige apenas que exista edição ativa para o mesário. `PontoService` e `MysqliPontoRepository` validam partida/equipe/atleta, mas não vinculam o recurso à edição autorizada do operador.

Consequência: tendo edição A ativa, um mesário pode alcançar consultas e mutações de pontos de B se os demais requisitos do recurso forem satisfeitos. GET de atletas sem jogo também precisa resolver a edição da equipe. PUT deve derivar o jogo do ponto persistido, nunca de um ID adicional confiado ao corpo.

Regressão necessária: A ativa/B diferente, GET pontos/atletas, POST e PUT; exigir 403 e ausência de alterações. Confirmar fluxo permitido em A e permissões de administrador/colaborador. Tarefa N02.

### C02 — P1: ranking de aluno ignora publicação

**Confirmado por inspeção.** `RankingController::list()` só chama `isActive()` e passa `somente_encerrados`; `MysqliRankingRepository::list()` só restringe `status_interclasse='0'`. Já `HistoricoTurmaController` exige `isRankingPublished()` e edição encerrada. Existe método e campo de publicação, introduzidos pela migração 007.

Consequência: uma edição encerrada ainda não publicada pode ter o ranking consultado diretamente pela API por aluno autenticado elegível para acessar o portal. Ocultar o link no frontend não corrige a API.

Regressão necessária: edição encerrada com `ranking_publicado_em=NULL`, aluno ativo de uma edição preparada, consulta direta negada; após publicação, permitida; edição ativa continua bloqueada. Preservar leitura administrativa. Tarefa N03.

### C03 — P1: autoria offline pode ser fornecida pelo cliente

**Reproduzido em camada.** Em `ResultadoService.php:81`, `registrado_por` só recebe o operador autenticado quando o campo está ausente. `MysqliPontoRepository::persistirPontosOffline()` usa o valor para gravação e anulação.

Sonda: operador 123, ponto com `registrado_por=999`; o repositório recebeu 999 e o caso de uso retornou sucesso. Um valor zero também pode terminar em autoria NULL na infraestrutura. Não é necessário falsificar a sessão para alcançar esse comportamento.

A identidade de quem sincroniza deve vir do contexto confiável. Preservar autor de um evento já gravado; registrar anulador atual separadamente. Filas legadas continuam com corpo/chave originais: adaptar a interpretação do servidor. Tarefa N04.

### C04 — P1: inscrição não aplica gênero e categoria no servidor

**Confirmado por inspeção.** `resources/js/pages/aluno/modalidade.js:170` filtra gênero e categoria. `MysqliInscricaoRepository::subscribe()` carrega turma/edição/status do usuário e modalidade/edição da equipe, mas não carrega nem compara gênero do usuário e modalidade, ou categoria da turma e modalidade. `InscricaoService` limita apenas formato, IDs e quantidade.

Um POST direto pode contornar os filtros da tela quando há equipe da turma para a modalidade incompatível. A correção precisa alcançar o caminho de persistência sob as travas existentes; não basta melhorar o JavaScript. Não foi executado um POST real nesta revisão. Tarefa N05.

### C05 — P2: repetição de inscrição é informada como falha

**Confirmado por inspeção.** `MysqliInscricaoRepository.php:107` remove modalidades existentes antes do loop; `$existing` começa em zero e só é incrementado dentro do loop das modalidades novas. Uma solicitação composta apenas de modalidades já inscritas retorna `success=false` e `ja_existentes=0`, apesar da inscrição existir. A mensagem afirma que nenhuma inscrição nova foi necessária.

Consequência: retry após perda da resposta pode parecer recusado, embora o estado esteja correto. Corrigir contagem e contrato idempotente sem criar outro vínculo nem consumir outra vaga. Tarefa N06.

### C06 — P2: detalhes internos escapam pelo fluxo de inscrição/importação

**Reproduzido em controlador para inscrição; confirmado por inspeção para importação.** `InscricaoController.php:29` captura `RuntimeException` e expõe `getMessage()`. `mysqli_sql_exception` é capturada por esse ramo. A sonda lançou `mysqli_sql_exception('AUDIT_SQL_DETAIL')` pelo repositório: a resposta foi HTTP 400 com a mesma mensagem.

`PdfAlunoImporter.php:138` captura `Throwable` e devolve o detalhe em `mensagem`; `ImportacaoTurmaService::importar()` repassa esse campo. Uma correção apenas no catch do controlador não cobre esse retorno em array. Investigar também `MysqliEquipePadraoRepository::redistribuir` e os consumidores de sua mensagem.

Usar exceções específicas de negócio para mensagens públicas; erros de persistência/parser vão ao log e produzem mensagem genérica e status adequado. Tarefa N07.

### C07 — P2: entradas inválidas são truncadas ou descartadas

**Reproduzido em camada.** `ResultadoService::lancar()` aceitou gols `3.9` e `-0.5`; o fake de persistência terminou em 3 e 0. O teste prova que a validação permite esses valores; a persistência real ainda precisa de teste. `ResultadoController` e serviço filtram elementos que não sejam arrays, permitindo descartar entradas inválidas silenciosamente. `pontos` com tipo escalar pode causar erro interno em `array_filter`.

Também reproduzido: `ModalidadeService::criar()` encaminhou `max_inscrito_modalidade=-1` e converteu `max_equipes='abc'` para NULL. Os consumidores interpretam limite não positivo como ilimitado. Não ampliar essa normalização para dados inválidos.

Separar dois lotes de correção: contrato de resultados/pontos (N08) e limites/status de modalidades (N09). Preservar strings inteiras válidas usadas por formulários e aliases efetivamente testados.

### C08 — P2: cadastros podem romper vínculos entre edições

**Confirmado por inspeção.** `ModalidadeService` aceita categoria/edição positivas sem verificar relação; `MysqliModalidadeRepository::create/update` grava diretamente. FKs individuais de categoria e edição não garantem que pertençam ao mesmo escopo. PUT também permite mudar edição de modalidade que já tem equipes/jogos.

Outro caminho é `RankingService::atualizar()` → `MysqliRankingRepository::updateTeam()`, que aceita alterar edição/categoria da turma sem reconciliar seus vínculos. É problema de integridade mesmo para um administrador autorizado.

Validar relações finais em updates parciais e impedir transferências de entidades vinculadas sem um caso de uso explícito. Não mover dados históricos automaticamente. Tarefa N10.

### C09 — P2: verificação de estilo não é reproduzível no checkout atual

**Observado na execução.** CS Fixer falha em 292/293 arquivos. Há diferença LF/CRLF comprovada e ausência de `.gitattributes`. Não atribuir todos os 292 casos apenas a EOL sem analisar o diff. Isso bloqueia `all` antes das verificações comportamentais.

Definir convenção de EOL para código, separar mudanças mecânicas das funcionais e repetir o check. **Não renormalizar migrações aplicadas:** `MigrationRunner` verifica hash dos bytes; alteração de EOL de SQL pode invalidar checksums históricos. Tarefa N01.

### C10 — P2/P3: parte dos testes não verifica as pós-condições declaradas

**Confirmado por inspeção.** Em `ResultadoServiceTest::testResultadoSemEquipesNaoAbreTransacaoNemEscreve` e `testPlacarInvalidoNaoAbreTransacaoNemAlteraPontuacao`, asserções de ausência de escrita ficam depois da chamada que deve lançar exceção; essas linhas não executam no cenário esperado.

Além disso, PHPUnit reportou metadados depreciados nos métodos `IndividualRankingServiceTest::testRejeitaIdsQueParecemNumericosMasNaoSaoInteirosPositivos` e `PageControllerTest::testMesarioCannotOpenAdministrativePages`. Corrigir os testes sem mudar a versão da dependência apenas para ocultar avisos. Tarefa N01.

## Riscos que ainda exigem reprodução

### R01 — P2: anulação concorrente com finalização

`PontoService::anular()` lê e valida status do jogo antes da trava; `MysqliPontoRepository::anular()` trava ponto/partida, mas não relê o status do jogo. Uma finalização entre essas fases pode permitir anulação após conclusão e divergir placar/pódio. Não foi reproduzido com duas conexões. N11 deve primeiro reproduzir uma intercalação determinística, incluindo as travas do caminho de resultado.

### R02 — P2: agendamento manual concorrente

`JogoService::agendar()` consulta `localConflict()` antes de `MysqliJogoRepository::create()` abrir transação. A criação não repete conflito sob uma trava comum. O agendamento em bloco usa suas próprias travas de edição/reservas; o manual precisa disputar o mesmo recurso para evitar dupla ocupação. Não houve execução concorrente. N12 deve reproduzir duas criações sobrepostas e criação manual contra lote antes de escolher a trava.

## Melhorias de manutenção e verificações adicionais

- **N13:** atualizar documentação operacional. `docs/architecture.md` menciona comparação visual Windows na matriz CI, mas o workflow atual executa visual em Ubuntu; também contém referência a URL antiga incompatível com a descrição das rotas canônicas. O plano antigo cita `config/routes/compatibility.php`, ausente na referência atual. Manter esse plano como histórico, sem recriar arquivo/rota removidos.
- O cleanup de `tools/test-local.ps1` pode levantar erro nativo de MySQL dentro do `finally`, prejudicando a apresentação da falha original e os passos seguintes. Validar falha de limpeza e restauração de ambiente/lock separadamente (N01).
- Não há razão demonstrada nesta revisão para reescrever o offline, introduzir framework/ORM ou dividir o monólito. Os guards, transações, savepoints, idempotência, projeções e runtime existentes são a base da correção.
- Otimizações de consultas, cache, tamanho de arquivos e extração adicional de gateways ficam fora dos lotes obrigatórios sem medição ou defeito associado. A lista de exceções arquiteturais não deve ser ampliada para facilitar as correções.

## Mapa do que foi confrontado

| Área | Caminhos/contratos revistos | Resultado/limite |
| --- | --- | --- |
| HTTP e Acesso | Kernel, sessão, CSRF, login, senha, perfil, foto, políticas | Proteções existentes preservadas; prioridade em aplicar escopo nos pontos. Não houve teste de invasão externo. |
| Eventos | criação/ativação, publicação, consultas, migrações | Regra de publicação não chegou ao ranking; coerência de vínculos merece cobertura. |
| Participantes | inscrição, portal, PDF e persistência | C04–C06; testes integrados pendentes. |
| Competições | pontos, resultado, modalidade, partida, agendamento e pódio | C01/C03/C07/C08; duas hipóteses concorrentes. |
| Resultados | ranking, histórico, créditos e arrecadação | C02/C08; não se propõe reimplementar a reconciliação anterior. |
| Disciplina | serviços, escopo, persistência e filtros frontend | Preservar magnitude não negativa, referências e edição; sem nova falha independente demonstrada. |
| Sincronização/frontend | identidade, store, confirmação, dependências, cliente HTTP, projeções e runtime | Reutilizar mecanismos existentes; confirmar novos cenários em IndexedDB real na implementação. |
| Entrega | runner, checks, CI, documentação e testes | C09/C10; sem aprovação da matriz remota/visual. |

O [plano](plano-melhorias-luna-2026-09-11/README.md) transforma esses achados em tarefas sequenciais com dependências e critérios de aceite. A implementação deve incorporar regressões aos executores existentes; as sondas descartáveis desta auditoria não substituem essa cobertura.
