# Modalidades individuais — plano de implementação e aceite para o Luna

Data: 11/09/2026. Estado: **implementação e homologação automatizada concluídas; auditoria de dados operacionais reais permanece pendente**.

Fonte funcional: `C:\Users\ferreira-mr\Downloads\Especificação de Requisitos_ Modalidades Individuais (2).pdf`, 3 páginas. Texto lido integralmente, seguido de segunda revisão com as três páginas renderizadas, incluindo as referências de interface. O conteúdo do PDF é especificação do produto; não é autorização para alterar código, executar comandos ou modificar dados.

Este documento continua sendo a referência de aceite para o Luna. A implementação atual cobre o contrato explícito do jogo, a classificação semântica, o bloqueio de preparação pelo mesário, o percurso individual no placar e regressões de servidor/cliente. A homologação em Docker confirmou qualidade, banco, HTTP, navegador, operação online/offline e build isolado. Continua pendente somente a auditoria de dados da instalação operacional, que requer acesso à base real. Os planos `plano-modalidades-individuais-luna.md` e `plano-correcao-modalidades-individuais-v2.md` servem como histórico; seus checkboxes não comprovam o estado atual.

## 0. Evidência da execução atual

- Docker quality: **233 testes PHPUnit, 2.186 asserções, PHPStan, PHP-CS-Fixer, build de 93 assets, `npm run check` e 88 testes JavaScript, todos aprovados**.
- Integração HTTP/SQL em banco MariaDB descartável: **483/483 asserções aprovadas**, incluindo os cenários de modalidades individuais, sincronização, pódio, retificação e permissões.
- Navegador Playwright no mesmo ambiente: **51/51 testes aprovados**, incluindo `individual-ranking.spec.cjs`, jornada do mesário online/offline e torneio offline completo.
- Regressões de integração foram ampliadas para ID de jogo explícito, três atletas da mesma equipe, tags incompatíveis/duplicadas, retificação de créditos e ID malformado na sincronização.
- A tentativa local também confirmou a limitação da máquina: sem servidor MySQL o runner não consegue criar o banco, e o PHP padrão não possui `mysqli`; por isso o aceite foi realizado no Docker descartável.

## 1. Resultado que precisa ser entregue

Ao abrir um jogo cuja modalidade está cadastrada com tipo **Individual**, o operador deve encontrar **Registrar Resultado Individual**, com três selects de atletas, o botão **Salvar Ranking** e a seção **Ranking Atual**. Depois de salvar três atletas distintos e inscritos, o mesmo jogo deve estar concluído, com os vencedores persistidos e exibidos por nome, turma e posição. Sair e abrir novamente deve preservar esse resultado.

**Não basta existir uma função que desenha o formulário. O acesso normal pela agenda, a classificação do tipo, o salvamento real e a leitura posterior precisam funcionar juntos.**

O exemplo negativo da página 3 contém “Final — Confronto 1”, relógio de 20 minutos, dois placares, VS e controles de pontuação. Esse conjunto não pode aparecer para Individual, nem enquanto a página carrega, nem por falta de dados, nem na navegação offline preparada.

## 2. Requisitos rastreados até o PDF

| ID | Fonte | Regra de produto | Aceite observável |
| --- | --- | --- | --- |
| RI-01 | p. 1, definição e estrutura | Competição individual sem confrontos intermediários/chaveamento. | Preparar uma prova não gera eliminatórias, final entre duas equipes ou disputa de terceiro. |
| RI-02 | p. 1, participantes | Somente atletas vinculados e inscritos no evento e na modalidade. | Lista correta; envio manual de não inscrito é recusado. |
| RI-03 | p. 1, formulário | Exatamente três seletores de resultado: 1º, 2º e 3º. | Os três estão visíveis na página da prova, com rótulos associados. |
| RI-04 | p. 1, universo | Cada seletor oferece todos os inscritos elegíveis. | Nenhum corte por equipe, representante, finalista ou primeiros N alunos. |
| RI-05 | p. 2, unicidade | Um atleta não ocupa duas posições. | Todos os pares de duplicidade falham na UI e no servidor. |
| RI-06 | p. 2, turma | Atletas diferentes da mesma turma/equipe podem ocupar o pódio juntos. | Os três de uma mesma equipe são aceitos e persistidos. |
| RI-07 | p. 2, obrigatoriedade | Três posições são obrigatórias para homologar e encerrar. | Resultado parcial não grava pódio, não conclui e não pontua. |
| RI-08 | p. 2, salvar | Salvar Ranking persiste no banco. | Sucesso HTTP, leitura posterior e registros SQL concordam. |
| RI-09 | p. 2, ranking | Ranking Atual mostra nome, turma e posição de cada vencedor. | Três cartões corretos após salvar e após reabrir. |
| RI-10 | p. 3, correção explícita | A escolha da tela depende do tipo cadastrado da modalidade. | Individual com qualquer ID de tipo recebe formulário individual; o coletivo continua coletivo. |

A desabilitação de um atleta nos outros selects é sugestão de UX do PDF; este plano a adota. As imagens são referência de hierarquia e comportamento, não obrigação de restaurar o CSS antigo, os caminhos PHP antigos ou nomes reais de alunos.

## 3. Interpretações de integração fixadas para a execução

Estas decisões conciliam o PDF com o SGI existente. Não atribuir ao PDF regras que ele não especifica.

1. **Tipo de modalidade:** “categoria individual” no texto significa o formato registrado em `tipos_modalidades`, relacionado à modalidade. Não significa `categorias` escolares. Nome do esporte, número da FK e tag do jogo não são autoridades para escolher o formato.
2. **Uma prova por modalidade:** preservar o modelo atual de um jogo `IND:{id_modalidade}` por registro de modalidade. O PDF não define baterias, várias provas ou vários pódios por modalidade. Não criar esses recursos.
3. **Equipe como vínculo técnico:** preservar `equipes` e `equipes_has_usuarios`, usados na inscrição. O competidor não precisa montar uma equipe para disputar uma prova individual. Verificar o cadastro/inscrição normal; corrigir qualquer exigência de montar elenco ou escolher dois adversários que bloqueie esse percurso. Não remover tabelas nem confundir atleta com equipe na persistência do pódio.
4. **Estado:** encerramento significa concluir o jogo da prova. Não desativar o cadastro em `status_modalidade` e não encerrar a edição inteira.
5. **Preparação/início:** preservar a regra de integração vigente: preparar/programar, iniciar e então homologar; concluído admite retificação conforme as permissões atuais. O PDF não exige essa sequência, embora a primeira imagem mostre “Em andamento”. O formulário deve existir desde Agendado, com ação alcançável de início. Não exigir relógio, tempo esgotado ou placar de gols para iniciar/salvar.
6. **Menos de três inscritos:** permitir a preparação segundo as regras atuais de cadastro, apresentar os três campos e explicar a impossibilidade de homologação. Não inventar empate, atleta fictício ou pódio parcial.
7. **Turma exibida:** adotar a turma escolar vinculada à inscrição e usada no crédito. Se houver nome fantasia, ele pode ser informação complementar, sem esconder a turma pedida no PDF. Um nome fantasia isolado não comprova RI-09.
8. **Histórico:** preservar pódio salvo se depois alguém ficar inativo ou perder a inscrição. Isso não torna a pessoa elegível para uma nova homologação. Não apagar pódio ao receber lista atual vazia.
9. **Retificação/pontos/offline:** são obrigações de integração do SGI. Preservar créditos por posição, deltas, mutações e operação do mesário na edição ativa. Não ampliar permissões ou antecipar divulgação ao aluno.
10. **Limites:** não implementar classificações por tempo/distância, empates, posições além do terceiro, premiação por bateria, novo módulo ou redesign geral.

Se a execução encontrar uma regra de produto incompatível com estas decisões, registrar o conflito concreto antes de modificar o contrato. Não usar uma divergência de nomenclatura como motivo para interromper as demais etapas independentes.

## 4. Diagnóstico atual: o que já existe e o que falta

Análise estática do checkout, incluindo alterações locais já presentes. Não foi acessado o servidor da captura nem o banco operacional; não se afirma que estas falhas expliquem sozinhas aquela instalação. As suítes automatizadas estão registradas na seção 0; resta apenas confrontar os dados da instalação operacional com as regras de identidade descritas abaixo.

### 4.1 Peças existentes a aproveitar

- `resources/js/pages/competicoes/placar.js`: já contém `jogoEhIndividual`, `renderIndividual`, `atualizarSelectsIndividual`, `carregarIndDados` e `salvarIndRanking`; já envia `id_jogo`.
- `TipoCompeticaoRules.php`: já centraliza parte da classificação no servidor.
- `IndividualRankingService.php`: já valida três posições, IDs de atletas e unicidade.
- `MysqliChaveamentoManagement.php`: envolve gravação individual em transação.
- `MysqliIndividualRepository.php`: já grava atleta por posição em `partidas`, conclui o jogo e reconcilia créditos em `pontuacoes_podio`.
- `mesario-data.js`: já projeta ranking pendente e mantém registros por mutação.
- Existem testes unitários, `PodiumCreditTest.php`, `IndividualSyncCreditTest.php`, testes JS de tipo e um teste browser de apresentação.

Não recriar essas camadas. Corrigir seus contratos e completar a comprovação.

### 4.2 Falhas e lacunas confirmadas na leitura

| Prioridade | Local/símbolo | Constatação | Consequência e ação planejada |
| --- | --- | --- | --- |
| P0 | `MysqliIndividualRepository::buscarJogoExistente` | No ramo com `idJogo > 0`, prepara SQL e faz `bind_param`, mas não executa, não lê e não fecha a consulta; `$row` permanece null. | A UI envia justamente esse campo. O salvamento chega a “Prepare o jogo...” mesmo havendo jogo. Criar reprodução HTTP com ID explícito e corrigir primeiro. |
| P0 | Mesmo método, ramo sem ID | Retorna o primeiro IND com `LIMIT 1` antes de verificar outros jogos; sem IND aceita um único jogo legado de qualquer tag. | Duplicidades e misturas podem ficar ocultas. Definir identidade única e tratar registros antigos explicitamente. |
| P1 | `TipoCompeticaoRules::resolve` | Ainda usa FK 2/1 como fallback; nome desconhecido pode acabar classificado pela FK. | O objetivo de eliminar dependência numérica da V2 não está completo. Remover inferência numérica dos caminhos operacionais. |
| P1 | `placar.js::jogoEhIndividual` e `chaveamento.js::modalidadeEhIndividual` | Ainda usam fallback de ID 2; o retorno booleano mistura desconhecido com coletivo. | Tipo não resolvido pode cair em mata-mata. Adotar terceiro estado explícito. |
| P1 | `placar.js::carregarDados` | Busca partidas/pontos antes da bifurcação individual e ainda processa duração/saldo depois. | O fluxo individual depende de partes coletivas desnecessárias. Bifurcar assim que o jogo/tipo for conhecido. |
| P1 | `ChaveamentoController` | `id_jogo` usa `is_numeric` e cast; inválido pode virar null; preparação individual não tem bloqueio específico de mesário nesse ramo. | Não permitir identidade malformada virar resolução implícita, nem ampliar a permissão de preparar provas. Testar o endpoint com perfis reais. |
| P1 | `MysqliIndividualRepository::buscarParticipantes` | Filtra modalidade, usuário ativo, nível 3 e edição do usuário; não compara explicitamente turma do usuário com a turma da equipe. Retorna linhas distintas por equipe. | Completar validação de coerência com o schema real; não listar o mesmo aluno duas vezes nem escolher vínculo conflitante arbitrariamente. |
| P1 | `placar.js::salvarIndRanking` | Após POST, faz escrita em cache e recarga; `carregarIndDados` absorve erro. Ainda pode mostrar sucesso genérico depois de falhar a leitura; não há guarda de ciclo da página nesse salvamento. | Separar persistência confirmada de atualização visual e evitar que resposta antiga afete outra prova. |
| P1 | `tests/browser/individual-ranking.spec.cjs` | Faz `skip` sem `SGI_INDIVIDUAL_BROWSER=1`; usa respostas simuladas, abre URL diretamente e só confere elementos iniciais. | Não prova início, POST, persistência, reabertura ou navegação pela agenda. Tornar a cobertura necessária executável e acrescentar E2E real. |
| P2 | `placar.js::renderIndividual` | Ordena resultado, mas medalha/rótulo vêm do índice; usa nome fantasia com preferência sobre turma escolar. | Derivar posição do próprio valor validado e exibir turma escolar identificável. |
| P2 | `mesario-data.js::onSynced` | Seleciona a última pendência pela ordem de `all()`; confirmado é reconstruído a partir de IDs do payload. | Comprovar ordem da fila, preservação de nomes históricos e status do jogo após confirmação; não presumir ordem cronológica do IndexedDB. |

Os testes PHP de crédito examinados salvam/sincronizam sem `id_jogo`, portanto não exercitam o ramo quebrado usado pela tela. Os testes JS atuais verificam classificação, não o percurso de gravação. Essa diferença entre o contrato testado e o contrato da UI é um risco concreto de repetir uma entrega incompleta.

### 4.3 O que ainda precisa ser investigado durante a implementação

- Cadastro e tipo reais do jogo que apresentou a tela incorreta; a captura visual não contém o JOIN do banco.
- Assets servidos e cache da instalação afetada. Código correto em `resources` não garante bundle atualizado no navegador.
- Jogos antigos duplicados ou gerados como MM/POS em modalidades Individual.
- Concorrência efetiva com inscrição/desinscrição e duas homologações.
- Resultado de ponta a ponta após reconexão, incluindo confirmação do estado de `jogos` e nomes históricos.

Esses itens são hipóteses/lacunas de evidência, não defeitos de produção já reproduzidos.

## 5. Contrato de interface e de dados

### 5.1 Composição da página

Manter a rota atual do placar e os componentes de navegação. Para Individual, renderizar nesta ordem:

1. Voltar, nome da modalidade/prova, metadados de programação e badge de estado.
2. Ação Iniciar prova quando Agendado; orientação de programação se incompleta.
3. Bloco Registrar Resultado Individual.
4. Três selects: 1º Lugar, 2º Lugar, 3º Lugar. Desktop em três colunas; celular em coluna, na ordem 1–2–3.
5. Salvar Ranking e mensagem persistente de resultado/erro.
6. Ranking Atual: estado vazio antes de salvar; depois, três cartões com medalha, posição, nome completo e turma.

Manter ocorrências e elementos auxiliares já existentes quando aplicáveis — a própria referência mostra ocorrências/timeline. Não inicializar artilharia, VS, controles de gols, duração de partida coletiva ou botões de finalização coletiva.

| Estado | Comportamento obrigatório |
| --- | --- |
| Carregando | Indicador neutro, sem layout de mata-mata provisório. |
| Tipo desconhecido | Mensagem de configuração; bloquear operação; não adivinhar formato. |
| Agendado | Formulário visível, início acessível; homologação ainda bloqueada pela regra atual. |
| Iniciado/Pausado | Permitir preencher e salvar se houver três elegíveis. |
| Concluido/Finalizado | Pódio visível e retificação conforme permissão. |
| 0–2 inscritos | Explicação específica e salvamento bloqueado; não ocultar histórico. |
| Falha na leitura | Erro com Tentar novamente; preservar dados válidos e escolhas da mesma prova. |
| Salvando | Bloquear clique repetido e manter campos escolhidos. |
| POST confirmado, GET falhou | “Resultado salvo; não foi possível atualizar a visualização.” Oferecer nova leitura. |
| Gravação offline durável | Mostrar pódio local e “aguardando sincronização”. |
| Rejeição na sincronização | Mostrar pendência com erro recuperável, sem afirmar homologação no servidor. |

Desabilitar em cada select somente IDs selecionados nos outros dois. Trocar/limpar deve reabilitar opções. Homônimos devem ser distinguíveis pela turma; não usar nome como chave. Contar atletas únicos, não linhas SQL. Escapar nomes/turmas usando os utilitários atuais e preservar as melhorias locais de segurança de HTML.

### 5.2 API: preservar a rota e tornar as operações inequívocas

Continuar usando `/api/v1/chaveamentos` e as consultas `acao=participantes` / `acao=ranking`, evitando criar uma segunda API de pódio.

Payload ilustrativo de homologação; IDs são fictícios:

```json
{
  "tipo_modalidade": "individual",
  "id_modalidade": 10,
  "id_jogo": 22,
  "ranking": { "primeiro": 101, "segundo": 102, "terceiro": 103 }
}
```

- Tipo verdadeiro vem do cadastro no servidor; o campo do cliente expressa intenção e não pode sobrescrevê-lo.
- Novos envios da UI devem conter o ID positivo do jogo aberto. Validar jogo e modalidade sem coerção permissiva. ID presente inválido é erro, nunca equivalente a omitido.
- Ausência de `ranking` significa preparação, somente para organização autorizada. `ranking: null`, objeto vazio, parcial ou outro tipo é tentativa inválida de homologação, sem preparação como efeito colateral.
- Para payloads já pendentes sem ID, resolver somente se a modalidade tiver uma prova inequivocamente identificada. Ambiguidade deve permanecer recuperável; não escolher o primeiro resultado SQL.
- Retornar identidade confirmada e sucesso apenas depois do commit. Reconsultar ranking/jogo ou acrescentar esses campos à resposta usando o formato existente; não montar sucesso autoritativo a partir dos selects.
- Manter CSRF, identidade de mutação e deduplicação existentes. GET não cria, repara, renomeia ou conclui jogos.
- Erro de validação pode manter o padrão 400 existente; proposto 409 para ambiguidade/conflito, desde que a fila o trate como erro recuperável. 403 para acesso e 500 para falha inesperada. Não mudar códigos sem adequar consumidores e testes.

### 5.3 Persistência e invariantes

Preservar `jogos`, `partidas.usuarios_id_usuario`, `partidas.resultado_partida` e `pontuacoes_podio` como modelo principal. Confirmar os índices atuais antes de decidir por migração. Não adicionar unicidade por equipe que impeça três atletas da mesma equipe.

Toda homologação precisa garantir, na mesma transação:

1. Modalidade real e edição autorizada; bloquear modalidade e depois jogo numa ordem consistente.
2. Jogo aberto pertence à modalidade e é a prova canônica, sem conflitos de identidade.
3. Estado operável, três IDs positivos distintos e vínculos elegíveis/coerentes revalidados.
4. Leitura dos créditos anteriores; substituição de exatamente três posições vinculadas a atletas.
5. Reconciliação dos créditos por delta, preservando valores históricos conforme o modelo atual.
6. Estado Concluido do mesmo jogo; commit antes de responder sucesso.

Falha em qualquer ponto reverte pódio, status e pontos. A revalidação precisa ser coordenada com os caminhos de inscrição/desinscrição; bloquear apenas a modalidade não comprova que outra transação respeite o bloqueio.

Oráculo de pontos com configuração sintética 10/7/5: atletas A, B e C da turma X somam 22. Reenviar o mesmo resultado acrescenta zero. Trocar apenas A por D da turma Y deixa o crédito da prova em X=12 e Y=10. Demais fontes de pontuação, arrecadação e ocorrências permanecem intactas.

## 6. Execução em etapas pequenas e verificáveis

O Luna deve executar nesta ordem. Cada etapa exige evidência de saída; encontrar código parecido não equivale a concluir a etapa. Corrigir apenas os arquivos necessários, aproveitando o que já atende ao contrato.

### MI-00 — Preparar reprodução e linha de base

**Ler:** `AGENTS.md`, `docs/testing.md`, este plano, `git status` e diffs dos arquivos tocados. Há trabalho local em placar, chaveamento, CSS, HTML e várias outras telas; preservá-lo.

**Fazer:** montar fixture descartável com modalidade Individual cujo tipo tenha ID diferente de 2; três atletas A/B/C da mesma turma/equipe, D de outra turma, não inscrito E e atleta de outra edição F. Criar coletivo de controle. Separar variantes inválidas: tipo desconhecido, tag MM em Individual, duplicidade IND, mistura IND+MM.

**Testar antes da correção:** POST real com `id_jogo` válido, três atletas e jogo iniciado. Deve reproduzir a falha estática descrita. Criar regressão que verifique gravação e leitura, não só mensagem. Executar a linha de base das suítes conforme seção 8 e registrar falhas preexistentes separadamente.

**Saída:** reprodução mínima, estado esperado, teste inicialmente falhando e relatório do ambiente. Não acessar a instalação da captura sem contexto de acesso; se disponível, diagnosticar tipo/asset em leitura, sem alterar dados.

### MI-01 — Corrigir o contrato do jogo e o salvamento básico

**Arquivos principais:** `MysqliIndividualRepository.php`, `ChaveamentoController.php`, `ChaveamentoService.php`, `IndividualRankingService.php` e testes de integração correspondentes.

- Corrigir execução, fetch e fechamento no ramo de ID explícito.
- Validar IDs presentes estritamente e diferenciar omitido/inválido. Jogo de outra modalidade, inexistente, zero, negativo ou malformado deve falhar sem efeitos.
- Fixar uma política única de seleção da prova. Não permitir que ID explícito válido contorne a verificação de múltiplos jogos incoerentes.
- Separar leitura de resolução com bloqueio, se necessário: consultas de apresentação não devem executar lógica de reparação ou depender de uma transação de gravação.
- Preservar transação já existente e assegurar que autorização para preparação não seja concedida ao mesário pelo endpoint de resultados.

**Saída:** salvar com o payload que a UI realmente envia funciona; três posições, jogo concluído e créditos corretos após nova leitura. Testes com e sem ID não podem usar apenas o mesmo mock de repositório.

### MI-02 — Completar classificação semântica em todos os caminhos

**Arquivos principais:** `TipoCompeticaoRules.php`, consultas de jogos/modalidades, `placar.js`, `chaveamento.js` e consumidores offline/agenda encontrados por busca.

- Inventariar comparações de FK/tag relativas ao formato; não substituir valores 1/2 de permissões ou outras regras.
- Servidor resolve tipo a partir do JOIN real e publica `tipo_competicao`. Aceitar somente nomes reconhecidos pela regra central; desconhecido permanece desconhecido.
- Remover fallbacks numéricos operacionais. Corrigir fixtures/mocks que omitem o tipo, em vez de manter inferência só para fazê-los passar.
- Cliente usa `individual`, `mata_mata` ou desconhecido. Se necessário um resolvedor JS comum pequeno, seguir o carregamento atual de assets e incluí-lo na SPA; não criar framework novo.
- Cache atual sem tipo pode consultar o catálogo local confiável pelo ID. Sem evidência suficiente, bloquear operação e orientar atualização online. Não apagar fila nem stores para resolver classificação.
- Resolver tipo logo após carregar o jogo; seguir ramo individual antes de partidas/pontos/cronômetro coletivos.

**Saída:** ID 37 com nome Individual funciona; FK 2 com tipo desconhecido não é prova individual por adivinhação; tag IND não transforma coletivo; tag MM não transforma Individual em coletivo. Divergências de tag seguem MI-03.

### MI-03 — Diagnosticar dados das tentativas anteriores

**Fazer:** criar diagnóstico explícito por modalidade, com todos os jogos, tags, status, programação, partidas, atletas, créditos e referências de histórico relevantes. Listar reservas de agenda e possíveis dependências de sincronização; não presumir que ausência de pendência no servidor significa dispositivos sem fila.

| Situação | Tratamento planejado |
| --- | --- |
| Nenhum jogo | Preparação cria uma prova única conforme regra vigente. |
| Um IND coerente | Reutilizar ID e programação; preparação repetida é idempotente. |
| Um jogo de tag errada, Agendado, sem histórico/resultados/conflitos | Produzir prévia de conversão preservando ID/programação; aplicar por operação explícita após verificar referências. |
| Duplicados IND, IND+MM, várias fases ou histórico existente | Bloquear novas homologações ambíguas e gerar proposta de reconciliação específica; manter consulta de histórico. |
| Cadastro associado a tipo errado | Diagnosticar a associação; não alterar o catálogo global nem inferir pelo nome Corrida. |

O reparador, se necessário, deve ter modo de prévia, transação e repetição idempotente. Testar em fixture. A aplicação em dados reais é etapa operacional distinta, com backup e revisão concreta; não incluir DELETE/conversão automática em GET ou abertura da tela. Não reescrever migrações aplicadas.

**Saída:** ambiguidades não são escondidas por `LIMIT 1`, e nenhum salvamento termina em outro jogo. Em dados reais não disponíveis, registrar “auditoria operacional pendente”, com o diagnóstico pronto.

### MI-04 — Fechar o percurso visual e de navegação

**Arquivos principais:** `resources/js/pages/competicoes/{placar,jogos,chaveamento,modalidade-detalhes}.js`, template do placar e CSS de origem quando necessário.

- Recuperar a composição da seção 5 sem refazer o visual global.
- Garantir entrada por clique na agenda e acesso ao resultado na visão da modalidade/chaveamento; rótulos de preparação individual não devem sugerir gerar confrontos.
- Conferir início de prova desde Agendado, programação e mensagem de erro. Não chamar a finalização coletiva para homologar.
- Desacoplar carregamento de participantes e ranking o suficiente para mostrar histórico mesmo quando a lista de elegíveis falha. Criar Tentar novamente real.
- Preservar seleção enquanto a mesma prova está aberta; invalidar respostas de prova anterior ao navegar. Usar o ciclo/escopo da página existente também em salvamento e recargas.
- Distinguir erro do POST, falha posterior de cache e falha do GET. Não sugerir reenviar um resultado já confirmado. Só exibir sucesso offline depois de persistência durável.
- Derivar medalha/rótulo de `posicao`, validar valores 1–3 e não apresentar histórico incompleto como pódio homologado completo.
- Verificar base de URL na raiz e em subdiretório; usar convenções `Url`/`Assets`/base de API existentes.

**Saída:** screenshots antes e depois equivalentes funcionalmente às páginas 1–2; zero elementos do exemplo negativo; percurso com clique, início, salvar, voltar e reabrir concluído em desktop e mobile.

### MI-05 — Completar elegibilidade, créditos e concorrência

**Arquivos principais:** repositório individual, regras/infra de pódio, gateway de sincronização e testes existentes de crédito.

- Consultar o schema real para validar modalidade, edição, turma da equipe, turma do aluno e vínculo de inscrição. Não listar usuários operacionais como atletas.
- Deduplicar o mesmo vínculo por atleta; detectar conflitos de equipe/turma sem MIN ou escolha arbitrária. Mensagem deve distinguir inconsistência cadastral de ausência de inscritos.
- Confirmar suporte a três linhas com a mesma equipe e usuários diferentes.
- Executar os casos de crédito 10/7/5, reenvio, retificação e falha induzida após remoção das posições anteriores.
- Testar duas conexões reais preparando simultaneamente e duas homologando; no final deve existir uma prova e um pódio íntegro, com pontos de um resultado completo. Preservar política atual de retificação; se não houver controle de versão, documentar que a última gravação serializada válida prevalece.
- Testar desinscrição entre carregar e salvar, e antes de sincronizar. Nunca homologar inscrição já removida por confiar na UI/cache.
- Confirmar que endpoints genéricos de resultados/status não encerram Individual contornando três atletas válidos.

**Saída:** integridade, atomicidade e permissões comprovadas no banco, não apenas nos serviços com mocks.

### MI-06 — Verificar e completar offline

**Arquivos principais:** `resources/js/offline/{mesario-data,offline-core,mesario-offline}.js`, gateway de sincronização e testes JS/browser.

- Preparar casca, jogo, tipo, participantes e ranking online antes de desconectar; manter esquema e identidade das mutações pendentes.
- Projeção de resultado exige jogo local coerente; não inventar uma nova prova para mascarar falta de cache.
- Manter base confirmada e aplicar mutações pendentes em ordem explícita da fila. Não usar ordem das chaves/retorno de `all()` como relógio.
- Cenário obrigatório: gravar A, retificar B, confirmar apenas A. A tela continua mostrando B pendente. Confirmar B remove pendência e mantém B. Repetir com identificadores cuja ordem lexical difira da ordem de criação.
- Reconciliar também o estado de `jogos`; uma resposta antiga não pode restaurar Agendado sobre resultado local pendente.
- Snapshots de participantes substituem somente o escopo correto, inclusive lista vazia, sem apagar mutações. Preservar nomes/turmas do pódio confirmado mesmo após remoção da inscrição.
- Falha de IndexedDB não produz mensagem de sucesso nem conclusão falsa. Falha parcial entre stores deve permitir recuperação consistente com a fila durável.
- Rejeição no servidor mantém diagnóstico recuperável. Reconexão confirma pelo identificador correto e não duplica pontos. Troca de operador preserva isolamento.

**Saída:** browser com IndexedDB real, desconexão/reconexão e conferência posterior no banco. Não declarar suporte a abertura fria sem casca preparada.

### MI-07 — Homologar e registrar evidências

- Ampliar o browser existente ou separá-lo em teste de UI controlada e E2E real. O E2E obrigatório não pode ficar silenciosamente em `skip`; o runner suportado deve preparar suas dependências.
- Adicionar `tests/Integration/IndividualRankingHttpTest.php` se a cobertura de endpoint não couber nas suítes existentes e registrá-lo em `tests/run_all.php`.
- Ampliar `tests/javascript/individual-ranking.test.cjs`, testes de `TipoCompeticaoRules`, `IndividualRankingService`, `PodiumCreditTest`, `IndividualSyncCreditTest` e testes offline já existentes.
- Executar build antes de testar os assets servidos. Comparar a versão entregue no navegador com o build atual, inclusive na SPA preparada.
- Registrar comandos, resultado, testes ignorados, motor do banco, screenshots/traces e consultas de verificação. Atualizar HOM-012 somente com evidência efetiva.

**Saída:** checklist da seção 9 concluído ou pendências precisas, sem declarar atendimento integral enquanto falte prova do fluxo real.

## 7. Matriz mínima de testes que impede repetir a falha

| Grupo | Cenários mínimos | Nível de evidência |
| --- | --- | --- |
| Identidade | ID explícito válido; inexistente; outra modalidade; malformado; omitido em fila antiga; múltiplos jogos | HTTP + banco, incluindo o payload da UI |
| Tipo | Individual com ID diferente de 2; coletivo; desconhecido com FK 1/2; tags conflitantes | Unidade + API real + browser |
| Participantes | Todos os elegíveis; mesmo nome; mesma equipe; não inscrito; outra edição; inativo; vínculo conflitante | SQL/HTTP + UI |
| Validação | Cada par de duplicidade; 0/1/2 posições; null/vazio/tipo errado; boolean/float/array/overflow | Serviço + endpoint; banco intacto |
| Permissões | Admin/colaborador; mesário ativo/inativo; mesário tentando preparar; aluno tentando escrever | HTTP autenticado com CSRF real |
| Percurso | Preparar → programar → agenda → abrir → iniciar → selecionar → salvar → sair → reabrir | E2E com banco real, sem interceptar APIs principais |
| Apresentação | Três selects, troca/limpeza, turma escolar, nomes longos/HTML, posição fora de ordem, mobile | Browser/inspeção visual |
| Erros | Falha ao listar, GET após POST falha, cache após POST falha, navegação durante requisição, clique duplo | Browser controlado |
| Créditos | 22 pontos na mesma turma; reenvio zero; retificação 12/10; rollback; outras fontes preservadas | Banco real |
| Concorrência | Preparação simultânea, resultados simultâneos, desinscrição antes de salvar/sincronizar | Duas conexões/processos |
| Legado | IND duplicado, IND+MM, único MM vazio, MM com histórico, reparação repetida | Fixtures + diagnóstico/reparador |
| Offline | A/B com confirmação parcial, ordem não lexical, duas provas, snapshot vazio, erro local, rejeição remota, troca de operador | JS + Chromium/IndexedDB + reconexão SQL |
| Regressão | Mata-mata, avanço offline, terceiro lugar, agenda, inscrição, ocorrências e ranking geral | Suítes existentes |
| Entrega | Assets atuais, raiz/subdiretório, entrada normal e SPA | HTTP + browser |

Asserções de banco da homologação: mesmo `id_jogo` enviado/retornado/lido; exatamente três usuários distintos nas posições 1/2/3; nenhum jogo intermediário criado; status Concluido; créditos e total por turma coerentes. A UI deve mostrar esses mesmos usuários e turmas.

## 8. Comandos e ambiente de validação para o implementador

Executar antes e depois das alterações, conforme `AGENTS.md` e a versão atual de `docs/testing.md`. Usar banco descartável e servidor isolado; não executar seed ou reset em base de trabalho.

Fluxo local documentado:

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local
```

Alternativa documentada em Docker:

```powershell
powershell -File tools/test-docker.ps1 -Database mariadb
```

Verificar no runner a execução efetiva de `composer verify`, `npm run check`, `npm test`, `npm run build`, `php tests/run_all.php` e `npm --prefix tests/browser test`. Rodar separadamente o que o runner não cobrir. Até corrigir a integração do cenário individual no runner, `SGI_INDIVIDUAL_BROWSER=1` é necessário para o teste atual; a entrega final não deve depender de lembrar uma flag oculta para validar o requisito central.

Não executar duas suítes que recriem a mesma base ao mesmo tempo. Usar os testes dedicados para concorrência. Validar MySQL/MariaDB conforme matriz do projeto; registrar alvos não executados. Se houver migração nova, testar atualização de schema existente e repetição idempotente, além de instalação limpa.

Ambiente indisponível significa homologação pendente. Teste simulado de interface não substitui a evidência de persistência. Não marcar “passou” para teste ignorado ou não executado.

## 9. Checklist final de aceite

- [ ] RI-01 a RI-10 vinculados a evidências executadas.
- [ ] POST com `id_jogo` vindo da UI salva no mesmo jogo; o defeito encontrado tem regressão.
- [ ] Tipo desconhecido não abre placar coletivo e Individual funciona com ID não convencional.
- [ ] A agenda chega ao formulário, sem URL digitada manualmente.
- [ ] Três atletas da mesma equipe são aceitos, mostrados e persistidos.
- [ ] Resultado parcial/duplicado/externo não altera pódio, estado ou pontos.
- [ ] Salvar, sair e reabrir confirma nomes, turma escolar e posições no banco e na tela.
- [ ] Preparação repetida não duplica prova nem reabre concluída.
- [ ] Retificação, crédito por delta e rollback comprovados.
- [ ] Sincronização A/B confirma apenas a mutação correta e preserva o último resultado.
- [ ] Fluxo coletivo permanece aprovado.
- [ ] Browser real obrigatório executado, sem skip oculto.
- [ ] Capturas desktop/mobile e versão dos assets registradas.
- [ ] Situação dos dados antigos documentada; nenhuma reparação implícita em GET.
- [ ] Suítes e motores executados identificados; HOM-012 atualizado sem apagar evidências históricas.

## 10. Instrução pronta para entregar ao Luna

> Implemente `docs/plano-modalidades-individuais-v3-luna.md` por MI-00 a MI-07, preservando as alterações locais. Use o PDF “Especificação de Requisitos_ Modalidades Individuais (2).pdf” como fonte funcional. Comece reproduzindo o POST real com `id_jogo`: `MysqliIndividualRepository::buscarJogoExistente` atualmente não executa/lê a consulta nesse ramo. Aproveite os serviços e o formulário existentes, complete a classificação pelo tipo cadastrado e elimine a queda silenciosa no mata-mata. Valide cada etapa antes de avançar. A entrega exige percurso pela agenda, salvar três atletas da mesma equipe, reabrir o pódio persistido e verificar offline, créditos e regressão coletiva. Não considere testes simulados ou checkboxes antigos como homologação. Não altere banco operacional nem execute reparações implícitas; prepare diagnóstico e prévia quando houver dados ambíguos. Ao terminar, relate mudanças, testes realmente executados, evidências de aceite e qualquer pendência específica.
