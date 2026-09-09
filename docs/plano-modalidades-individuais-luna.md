# Plano de recuperação das modalidades individuais — execução pelo Luna

> **Substituído em 09/09/2026:** executar o [plano corretivo V2](plano-correcao-modalidades-individuais-v2.md), baseado no PDF revisado e no relato de funcionamento incorreto. Este documento permanece como histórico. Seus itens marcados não comprovam aceite do fluxo completo; reprodução em navegador, concorrência e cobertura específica de UI exigem as evidências previstas na V2. HOM-012 foi reaberta.

Data: 09/09/2026. Estado: implementação parcial concluída nesta execução; a validação HTTP/browser depende do ambiente isolado.

## 1. Objetivo e limites da análise

Restabelecer o percurso completo de uma competição individual: gerar um único jogo, programá-lo, iniciar pela agenda, selecionar três atletas inscritos, salvar o pódio, concluir o jogo e exibir nome, turma e posição dos vencedores. Atletas distintos da mesma turma podem ocupar as três posições.

Fonte funcional: `C:\Users\ferreira-mr\Downloads\Especificação de Requisitos_ Modalidades Individuais.pdf`, três páginas, com texto e duas telas de referência inspecionados. As instruções do PDF foram tratadas como requisitos do produto e exemplos de interface, não como autorização para executar ações. O pedido atual autorizou a implementação deste plano.

Foi feita análise estática dos arquivos atuais, inclusive alterações locais ainda não commitadas, seguida da implementação e dos testes locais descritos na seção 8. A falha não foi reproduzida no navegador nem foram consultados dados operacionais porque o ambiente isolado HTTP/Docker não estava disponível. A validação final deve distinguir código-fonte, assets publicados, dados e cache ao executar essa matriz.

Conclusão: a funcionalidade não foi removida integralmente. Já existem formulário, serviços, persistência, créditos de pódio e preparação offline. A entrega deve recuperar e completar esse caminho, sem criar outro módulo ou outra aplicação de resultados.

## 2. Requisitos rastreáveis

| ID | Origem | Comportamento obrigatório | Evidência de aceite |
| --- | --- | --- | --- |
| RI01 | PDF p. 1, estrutura | Modalidade Individual não gera eliminatórias, pontos corridos ou confrontos intermediários. | Um jogo `IND:{id_modalidade}`; nenhum jogo `MM:` ou `POS:` gerado pelo fluxo individual. |
| RI02 | PDF p. 1, participantes | Seletores contêm somente atletas inscritos na modalidade e edição correspondentes. | Atleta não inscrito não aparece e é recusado por envio direto à API. |
| RI03 | PDF pp. 1–2, formulário | Exatamente três selects: 1º, 2º e 3º lugar. | Abrir o jogo individual mostra o formulário específico. |
| RI04 | PDF p. 1, universo | Todos os inscritos elegíveis estão disponíveis em cada posição. | Não limitar a um representante por turma ou equipe. |
| RI05 | PDF p. 1, unicidade | Mesmo atleta não pode ocupar duas posições. | Validação na interface e no servidor, inclusive sincronização. |
| RI06 | PDF p. 1, turma | Atletas diferentes da mesma turma/equipe podem compartilhar o pódio. | Três atletas da mesma equipe são gravados corretamente. |
| RI07 | PDF p. 2, obrigatoriedade | Homologação exige as três posições preenchidas. | Pedido incompleto falha sem criar jogo, modificar status ou pontuar. |
| RI08 | PDF p. 2, tela 1 | Iniciar o jogo individual leva à página do jogo com o formulário. | Teste de navegador percorre a agenda até o formulário. |
| RI09 | PDF p. 3, tela 2 | Salvar Ranking persiste e mostra Ranking Atual, com atleta, turma e posição. | Pódio correto após salvar, sair e abrir novamente. |
| RI10 | PDF p. 2, encerramento | Resultado válido encerra a competição individual. | Jogo muda para `Concluido` na mesma transação do pódio. |

A desabilitação de atletas escolhidos nos outros selects é uma sugestão de UX do PDF; este plano a adota. Pontuação por turma, retificação, proteção de edição e offline são obrigações de integração com o SGI existente, não requisitos novos atribuídos ao PDF.

## 3. Interpretações adotadas para evitar decisões implícitas

1. **Individual é tipo de modalidade**, não um novo `status_jogo` nem uma categoria escolar. Preservar os estados atuais e não desativar `status_modalidade` ao salvar o pódio. O encerramento é representado pelo jogo individual concluído.
2. **Sem equipe competitiva obrigatória na experiência do usuário:** manter `equipes` e `equipes_has_usuarios` como vínculo técnico de inscrição já existente. Não exigir que o aluno crie uma nova equipe; não eliminar essas tabelas.
3. **Uma competição por registro de modalidade:** preservar `IND:{id_modalidade}`. Categorias/gêneros continuam sendo delimitados pelos registros e inscrições existentes. Baterias, provas múltiplas e vários pódios para a mesma modalidade ficam fora desta entrega.
4. **Menos de três elegíveis:** permitir preparar/programar o evento segundo as regras vigentes, mas impedir a homologação; mostrar a quantidade disponível e explicar que são necessários três atletas distintos. Não criar pódio parcial nem completar automaticamente.
5. **Retificação:** preservar a capacidade existente de corrigir as três posições, com validações completas e reconciliação dos créditos. Não zerar a turma nem reaplicar todos os pontos.
6. **Estado operacional:** primeira homologação exige jogo já preparado e iniciado/pausado; jogo concluído admite retificação. Esta é uma decisão de integração proposta para impedir que o envio do resultado contorne a programação. Ajustar fixtures antigos que salvam diretamente em modalidade sem jogo para percorrer a preparação válida; manter um teste que demonstre a recusa desse atalho.
7. **Agenda:** preservar as alterações locais de programação, inclusive campos inicialmente nulos e bloqueio de início sem data, início, término e local. Este plano não implementa o assistente de agendamento em blocos. Reconsultar `docs/plano-agendamento-em-blocos.md` e o código ao iniciar; parte desse trabalho está em andamento.
8. **Telas do PDF:** reproduzir os elementos e o comportamento, usando o visual atual do SGI. Não restaurar caminhos PHP antigos nem copiar os nomes reais dos alunos das imagens para fixtures.
9. **Offline:** homologação local mostra resultado pendente de sincronização, sem afirmar que já está no servidor. O servidor revalida elegibilidade ao receber a fila.
10. **Inscrito posteriormente inativado:** preservar a exibição histórica do pódio já salvo. Em nova retificação, os três selecionados precisam ser elegíveis na gravação. Não apagar o histórico quando a lista de elegíveis estiver vazia.

## 4. Diagnóstico do código atual

Os caminhos desta seção são relativos a `C:\Projetos\SGI`.

| Local e símbolo | Constatação estática | Trabalho necessário |
| --- | --- | --- |
| `resources/js/pages/competicoes/placar.js`: `carregarDados`, `renderTudo`, `renderIndividual`, `salvarIndRanking` | Reconhece tag IND/tipo 2, já desenha três selects, botão e pódio. | Reproduzir acesso real e recuperar o ramo existente; não criar página paralela. |
| Mesmo arquivo: `carregarIndDados` | Exceções viram listas vazias. | Diferenciar ausência de participantes de falha HTTP/cache, mantendo os dados válidos anteriores. |
| Mesmo arquivo: `renderIndividual` | Retorna cedo se não houver participantes, ocultando também um ranking histórico. Não desabilita opções repetidas dinamicamente. | Renderizar histórico independentemente dos elegíveis e sincronizar as opções dos selects. |
| Mesmo arquivo: `salvarIndRanking` | Marca `_pendente: true` mesmo após resposta online; sucesso é escrito antes de redesenhar o elemento que contém a mensagem. | Usar confirmação real/enfileiramento e mensagem persistente após renderização. |
| `src/Modules/Competicoes/Application/ChaveamentoService.php`: `gerar` | Ranking incompleto vira `null` e dispara preparação da agenda. Ramo individual confia no tipo informado pelo cliente. | Distinguir operação de preparação de tentativa inválida de homologação; conferir modalidade real. |
| `tests/Unit/Modules/Competicoes/ChaveamentoServiceTest.php` | `testIncompletePodiumPreservesScheduleOperation` espera sucesso para ranking incompleto. | Substituir essa expectativa por erro sem persistência e manter teste separado de preparação sem campo ranking. |
| `IndividualRankingService.php`: `registrar` | Já exige campos e unicidade, mas converte com cast simples; `null` significa preparar. | Validar IDs sem coerção permissiva; preservar distinção entre operações. |
| `MysqliIndividualRepository.php`: `buscarParticipantes` | Filtra inscrição, equipe e usuário ativos. Não explicita nível 3 nem coerência da edição/turma do usuário; DISTINCT inclui equipe. | Tornar elegibilidade consistente e retornar um item por atleta. |
| Mesmo repositório: `salvarRanking`, `montarJsonRanking` | Já grava posição e usuário em `partidas`, aceita mesma equipe e lê nome/turma; conclui o jogo. | Reutilizar; reforçar validação, estado e bloqueio transacional. |
| `MysqliChaveamentoManagement.php`: `saveIndividual` | Envolve a gravação no TransactionRunner. | Preservar a transação; não inserir begin/commit aninhados nos auxiliares. |
| `MysqliChaveamentoSyncGateway.php`: `sync` | Caminho em lote também usa serviço individual dentro de transação. | Aplicar os mesmos invariantes e conferir tipo real, não apenas o parâmetro recebido. |
| `resources/js/offline/mesario-data.js`: projeção de POST individual e `localGet` | Projeção individual atualiza status, mas não o pódio; leituras com `acao` retornam ao cache por URL. | Sobrepor ranking pendente ao snapshot para evitar leitura antiga. Confirmar interação com offline-core no teste real. |
| `resources/js/offline/mesario-offline.js` | Já aquece URLs de participantes e ranking individual. | Preservar e testar a casca preparada; não prometer abertura offline sem preparação. |
| `resources/js/pages/competicoes/chaveamento.js`: `carregarArvore` | Já tem ramo de Ranking Atual em vez da árvore para tipo individual. | Preservar, validar tipo/IDs e oferecer acesso claro ao jogo. |
| `resources/js/pages/eventos/configurar-agenda.js`: `montarCardJogo` | Usa os dois primeiros nomes para renderizar VS quando há múltiplas equipes. | Individual precisa de apresentação de prova/participantes, sem sugerir duelo. |
| `PodiumCreditTest.php` e `IndividualSyncCreditTest.php` | Já existem cenários de créditos, retificação e repetição individual. | Ampliar para mesma turma, HTTP, percurso visual e offline real. |

Há modificações locais em vários desses arquivos e duas migrações com prefixo `005`. Não fazer reset, checkout de arquivos, substituição integral ou renumeração automática. Confirmar o trabalho já integrado antes de cada etapa. Não presumir necessidade de nova migração para este recurso.

## 5. Contrato funcional e técnico de destino

### 5.1 Leituras e gravação

Manter as rotas atuais, usando a base de URL do SGI:

```text
GET /api/v1/chaveamentos?tipo_modalidade=individual&acao=participantes&id_modalidade=42
GET /api/v1/chaveamentos?tipo_modalidade=individual&acao=ranking&id_modalidade=42
POST /api/v1/chaveamentos
```

Exemplo de homologação; IDs meramente ilustrativos, a substituir pelos retornados no fixture:

```json
{
  "tipo_modalidade": "individual",
  "id_modalidade": 42,
  "ranking": { "primeiro": 101, "segundo": 102, "terceiro": 103 }
}
```

Preparação do jogo usa o POST atual **sem a chave `ranking`**. Se a chave existir, `null`, string, lista, objeto vazio ou objeto incompleto devem causar erro; nunca converter para preparação. O controlador precisa inspecionar presença no corpo, pois `input()` com default nulo pode perder essa diferença. Usar a API real de `Request` para obter o corpo e `array_key_exists`; não inventar um método sem implementá-lo.

Rejeitar IDs zero/negativos, booleanos, arrays, decimais, notação exponencial e textos como `12abc`. Aceitar inteiro positivo e string decimal positiva proveniente do formulário, dentro da faixa de IDs do banco; normalizar uma vez. Ignorar campos adicionais somente conforme a convenção já existente, sem usá-los para determinar equipe ou turma.

Consultar modalidade real antes de decidir geração, leitura ou homologação. Uma modalidade coletiva não pode receber resultado individual forçando `tipo_modalidade`. Uma modalidade individual não deve gerar mata-mata com parâmetro omitido/incorreto. Manter a preparação individual já reconhecida pelo tipo real; recusar combinações de resultado incompatíveis com mensagem clara.

Manter envelope de sucesso `success: true`, `message`, `id_jogo`; pode acrescentar `ranking` e `jogo` na resposta se isso simplificar a atualização confirmada. Leituras mantêm `participantes` ou `ranking`/`jogo`. Usar HTTP 400 para corpo/regra inválida, 403 para acesso negado e 500 com mensagem genérica para falha de persistência, seguindo o controlador atual. Não retornar sucesso com corpo parcial ou erro oculto.

### 5.2 Permissões e estado

| Operação | Administrador/colaborador | Mesário | Aluno |
| --- | --- | --- | --- |
| Preparar/programar jogo | Conforme política existente | Não criar ou programar pelo endpoint de ranking | Não |
| Salvar/retificar pódio | Sim, em edição permitida | Sim, exclusivamente na edição ativa | Não |
| Consultar resultados | Conforme política de leitura existente | Respeitar contexto da edição ativa | Conforme acesso de consulta existente |

Aplicar autorização antes de efeitos colaterais. Reutilizar `CompetitionAccess`, política de edição e proteção CSRF já instaladas; validar também a entrada em lote. Não alterar a política global de leitura dos alunos como parte desta entrega.

Verificar caminhos alternativos de conclusão: `PUT /api/v1/jogos`, snapshots de cronômetro e `/api/v1/resultados`. Um jogo individual não pode ficar homologado sem os três atletas por essas entradas. Preferir recusar a conclusão individual por endpoint genérico e orientar o uso do ranking, preservando início/pausa permitidos e o comportamento coletivo. Testar o bloqueio no servidor, não só ocultar botões.

### 5.3 Persistência, concorrência e pontos

Preservar `jogos`, `partidas.usuarios_id_usuario`, `partidas.resultado_partida` como posição 1/2/3 e `pontuacoes_podio`. Não criar unicidade por equipe: ela impediria RI06.

Sequência dentro da transação já existente:

1. Bloquear a linha da modalidade por ID (`SELECT ... FOR UPDATE`) e validar seu tipo. Essa linha existe mesmo antes do primeiro jogo; usá-la para serializar preparação e gravações da mesma modalidade em ambos os caminhos HTTP/lote.
2. Resolver o jogo por modalidade e tag IND, validar correspondência e estado. Preparação repetida deve devolver o mesmo jogo e nunca alterar pódio concluído. Se houver vários jogos IND para uma modalidade, falhar de forma diagnosticável; não escolher/apagar arbitrariamente.
3. Validar os três IDs distintos contra uma consulta única de elegibilidade. Obter equipe e turma no servidor e reutilizar esse mapeamento; evitar um segundo `LIMIT 1` que possa escolher outro vínculo.
4. Definir a proteção da inscrição durante a gravação: conferir os bloqueios usados pelas mutações de inscrição e bloquear/revalidar os vínculos selecionados antes da escrita. Testar inscrição removida antes da sincronização.
5. Carregar os créditos anteriores bloqueados e executar as validações de reconciliação já presentes antes de remover partidas.
6. Substituir apenas as partidas do jogo individual identificado e gravar três linhas com posição, atleta e equipe.
7. Reutilizar `PodioRules::deltas` e `MysqliPodioRepository`; aplicar a soma das diferenças por turma. Preservar valores de crédito anteriores na retificação, como faz o comportamento atual.
8. Concluir o jogo e confirmar a transação. Em qualquer falha, reverter partidas, status e pontos.

Exemplo de teste com pontuação configurada no fixture em 10/7/5: três atletas da mesma turma creditam 22. Reenvio deixa 22. Substituir somente o campeão por atleta de outra turma deixa 12 na primeira e acrescenta 10 na segunda, considerando apenas o delta esportivo desse evento. Arrecadações e ocorrências não mudam.

Não aplicar pontos com UPDATE manual separado nem reintroduzir triggers na mesma tabela. Não reconstruir dados operacionais para corrigir fixtures. Resultados antigos sem fontes de crédito reconciliadas devem manter a proteção existente e mensagem de recuperação, sem recálculo especulativo.

## 6. Etapas pequenas para implementação pelo Luna

Executar na ordem. Para cada etapa, registrar arquivos alterados, testes rodados e resultado. Marcar concluída somente com a evidência indicada. Novos nomes de arquivos de teste abaixo são propostas.

### IND-00 — Reproduzir e estabelecer a referência

**Ler:** `AGENTS.md`, `docs/testing.md`, este plano, `git status`, diffs dos arquivos a editar, `config/routes.php`, `PageController.php` e os planos de agenda vigentes.

**Fazer:** preparar ambiente isolado; executar verificações de referência da seção 8. Criar fixture de modalidade Individual com pelo menos quatro inscritos, três deles na mesma turma/equipe, e um atleta não inscrito. Conferir tipo da modalidade, vínculo de inscrição, jogo IND, status e programação. Percorrer geração → agenda → iniciar → placar. Registrar a URL, resposta de jogos, respostas de participantes/ranking, erros do console e asset efetivamente carregado.

**Critério de saída:** causa observada ou delimitação precisa do ponto onde o fluxo quebra. Se fonte e assets divergirem, executar build e repetir antes de atribuir o problema ao PHP. Não considerar cache antigo uma causa confirmada sem evidência. Falhas prévias da suíte ficam registradas separadamente.

### IND-01 — Corrigir contrato e validação central

**Editar:** `ChaveamentoController.php`, `ChaveamentoService.php`, `IndividualRankingService.php`, interfaces/adaptadores estritamente necessários e `MysqliChaveamentoSyncGateway.php`.

**Fazer:** implementar presença de ranking versus preparação; validar corpo/IDs; conferir tipo real; usar as mesmas validações nas duas entradas. Conferir autorização por operação, sobretudo mesário enviando POST sem ranking. Preservar respostas e rotas existentes.

**Testes:** ampliar `IndividualRankingServiceTest.php`, substituir o teste de ranking incompleto em `ChaveamentoServiceTest.php` e adicionar contrato de controlador. Cobrir todos os campos faltantes, null, tipos inválidos, repetição e modalidade coletiva forçada. Repositório não deve ser chamado para gravar em pedidos inválidos.

**Saída:** RI05/RI07 verificados no serviço e na borda HTTP; geração sem ranking continua funcionando para organização autorizada.

### IND-02 — Garantir elegibilidade e persistência indivisível

**Editar:** `MysqliIndividualRepository.php`, `MysqliIndividualRankingRepository.php`, `MysqliChaveamentoManagement.php` e o gateway de sincronização no limite necessário.

**Fazer:** consulta de inscritos com nível de competidor, usuário/equipe ativos e coerência entre modalidade, edição e turma. Deduplicar por `id_usuario`; se houver vínculos contraditórios de um atleta, rejeitar com diagnóstico em vez de escolher turma arbitrária. Reutilizar o vínculo validado na gravação. Implementar bloqueio compartilhado, preservação de jogo concluído na preparação e sequência transacional da seção 5.3.

**Testes:** ampliar `PodiumCreditTest.php` e `IndividualSyncCreditTest.php`. Adicionar mesmo atleta repetido, mesma turma nas três posições, outro evento/modalidade, usuário inativo/não competidor, rollback induzido após início das escritas e preparação repetida após conclusão. Preparar/iniciar jogos dos fixtures pela regra da seção 3.

**Saída:** três posições persistidas, pontuação correta e nenhuma escrita parcial. Se surgir necessidade de schema, documentar por que o modelo atual não basta, escolher próximo nome livre e adicionar migração nova; não modificar as já aplicadas.

### IND-03 — Recuperar navegação e proteger o encerramento

**Editar:** `resources/js/pages/eventos/configurar-agenda.js`, `resources/js/pages/competicoes/jogos.js`, `chaveamento.js`, `placar.js`; controllers/services de jogos, cronômetro e resultados somente para os guardas necessários.

**Fazer:** garantir navegação para `/jogos/placar?id_jogo=...` com base de instalação correta. Individual deve aparecer como prova, sem VS, sem placar de gols e sem avanço de chave. Manter configuração da programação com organização. Abrir o jogo já iniciado deve selecionar o ramo individual pelo tipo real e tag coerente. Acrescentar acesso ao resultado a partir da visualização individual quando existir jogo e a permissão permitir.

**Fazer também:** bloquear conclusão individual sem pódio pelos endpoints genéricos descritos na seção 5.2; não permitir contorno por snapshot. Não reestruturar todo o cronômetro.

**Testes:** agenda com uma única equipe contendo três atletas; modalidade coletiva com mais de duas equipes no conjunto; URL em raiz e subdiretório; início com programação incompleta recusado; conclusão genérica sem ranking recusada. Conferir que nenhuma regra exige duas equipes para a prova individual.

**Saída:** RI01/RI08/RI10; um usuário chega ao formulário pelo fluxo normal, e não apenas digitando a URL.

### IND-04 — Completar formulário e leitura do pódio

**Editar:** funções individuais de `placar.js`; template `resources/views/pages/competicoes/placar.php` e `resources/css/source/admin.css` apenas quando necessário.

**Fazer:** preservar os IDs `indSelectPrimeiro`, `indSelectSegundo`, `indSelectTerceiro` e `btnSalvarIndRanking`. Associar labels aos selects. Usar nomes/turmas escapados e agrupar por turma sem filtrar atletas da mesma turma. Desabilitar nos outros selects apenas o ID já escolhido; reabilitar ao limpar/trocar, aplicando a regra também após carregar o ranking existente. Não perder seleções ao mostrar erro.

Separar estados de carregamento, erro com repetição, zero inscritos, menos de três inscritos, pronto para salvar, salvando, salvo e pendente offline. Não limpar ranking confirmado por falha de busca de participantes. Exibir pódio por `posicao`, com ordenação numérica explícita, independentemente da ordem de chegada. Desabilitar envio duplicado enquanto houver uma operação em andamento. Manter feedback após redesenhar a tela; usar região de mensagem acessível.

Após sucesso confirmado, obter estado autoritativo pela resposta ou nova leitura, preservando distinção entre falha ao salvar e falha ao atualizar a visualização. Um erro de leitura depois de gravação confirmada deve informar que o resultado foi salvo e oferecer recarregar.

**Testes:** novo `tests/javascript/individual-ranking.test.cjs` conforme a estrutura existente; teste de navegador com tela estreita e desktop. Cobrir opções, preservação da seleção, nomes com caracteres HTML, histórico sem elegíveis e erros HTTP/JSON.

**Saída:** RI02–RI06/RI09 e elementos das duas telas do PDF presentes no estilo atual.

### IND-05 — Projetar o pódio offline e sincronizar

**Ler/editar:** `mesario-data.js`, `offline-core.js`, `mesario-offline.js`, `chaveamento-engine.js` e a integração de `placar.js`, apenas nos pontos necessários. Reutilizar fila e APIs públicas existentes.

**Fazer:** conservar aquecimento das duas URLs. Para leitura do ranking individual, compor snapshot confirmado com mutações individuais pendentes da mesma sessão/edição/modalidade, na ordem da fila. Resolver nome/turma pelos participantes preparados; não depender de rede após enfileirar. A última retificação pendente deve aparecer ao sair e voltar pela SPA.

Não criar outra fila, não renomear stores, não limpar IndexedDB, não remover identificadores de mutação. Preferir projeção derivada da fila existente para não exigir mudança de schema. Se for necessário alterar registros locais, garantir atualização coerente e cobertura para fila já pendente.

Só mostrar sucesso local após confirmação de enfileiramento durável. Se IndexedDB falhar, preservar formulário e não mostrar concluído. Usar os sinais reais de resposta offline do `offline-core.js`, identificados na leitura do código; não inferir confirmação apenas de `navigator.onLine` nem inventar um campo de resposta. Diferenciar "Salvo neste dispositivo; aguardando sincronização" de "Ranking salvo".

Após reconexão: revalidar no servidor, atualizar snapshot confirmado e retirar indicador pendente somente quando não restarem operações dessa modalidade. Uma leitura antiga não pode substituir retificação ainda na fila. Erros de inscrição/edição devem permanecer visíveis e recuperáveis conforme o tratamento da fila; não descartá-los como sincronizados.

Verificar que `ind_ranking` no motor híbrido não promove vencedor nem cria partidas MM/POS. Não ampliar promessa de cold-open/refresh sem casca preparada.

**Testes:** ampliar `tests/javascript/mesario-data.test.cjs` e `tests/browser/offline-queue-regression.spec.cjs`; criar cenário individual integrado real na etapa seguinte. Cobrir duas retificações pendentes, duas modalidades, navegação SPA, reconexão e falha de gravação local.

**Saída:** pódio e status consistentes online/offline, com pontos aplicados uma única vez no servidor.

### IND-06 — Testar percurso completo e regressões

**Criar:** `tests/Integration/IndividualRankingHttpTest.php` e `tests/browser/individual-ranking.spec.cjs`, se não houver equivalentes ao iniciar. Registrar integração em `tests/run_all.php` respeitando a sequência das suítes e sem assumir IDs fixos. Reutilizar autenticação/CSRF e fixtures existentes.

**Fazer:** executar matriz da seção 7 com base isolada. Para concorrência, usar o padrão do teste de mutações concorrentes existente: dois processos, mesma modalidade e rankings válidos; o estado final deve corresponder integralmente a uma gravação serializada, nunca mistura de posições ou créditos duplicados. Repetição idêntica deve produzir delta zero. Não introduzir política nova de resolução entre dispositivos: conservar ordenação/revisões existentes e documentar a política observada.

**Saída:** evidências de banco e navegador, incluindo três atletas da mesma turma e offline com rede realmente desligada no navegador.

### IND-07 — Build, validação e entrega

**Fazer:** executar os comandos da seção 8, inspecionar o diff final e registrar resultado de cada comando. Conferir URLs/assets em subdiretório e nenhuma alteração indevida de migrações, fila ou alterações de terceiros. Atualizar documentação de teste com os cenários adicionados.

**Saída:** relatório curto com comportamento recuperado, arquivos centrais, evidências, limitações do ambiente e pendências reais. Não anunciar suporte completo se o percurso HTTP/browser/offline não tiver sido executado. Publicação/deploy não integra esta etapa.

## 7. Matriz mínima de aceite

| Caso | Ação | Resultado obrigatório |
| --- | --- | --- |
| A01 | Preparar individual duas vezes | Mesmo jogo IND; nenhuma chave e nenhuma duplicação. |
| A02 | Iniciar pela agenda | Abre formulário de três posições; sem interface de gols/VS. |
| A03 | Selecionar três atletas de turmas diferentes | Salva, conclui e exibe atleta/turma/posição. |
| A04 | Selecionar três atletas da mesma equipe/turma | Mesmo sucesso; turma recebe soma dos três créditos. |
| A05 | Repetir atleta em cada par de posições | Interface impede; API recusa sem escrita. |
| A06 | Omitir uma posição ou enviar ranking inválido | HTTP de erro; nenhuma preparação disfarçada de sucesso. |
| A07 | Enviar atleta externo à modalidade/edição | Recusa; participantes e pontos anteriores intactos. |
| A08 | Ter zero, um ou dois elegíveis | Mensagem correta; não homologa parcialmente. |
| A09 | Repetir ranking salvo | Três posições; delta zero; mesmo jogo. |
| A10 | Retificar campeão para outra turma | Posições e créditos mudam juntos; demais pontuações preservadas. |
| A11 | Falhar durante persistência | Rollback de partidas, pontos e status. |
| A12 | Salvar simultaneamente em dois processos | Pódio íntegro de uma gravação; sem duplicação de jogo/crédito. |
| A13 | Aluno escreve / mesário escreve em edição inativa | HTTP 403; nenhuma alteração. |
| A14 | Mesário tenta preparar evento sem ranking | Recusa; não cria jogo ou programação. |
| A15 | Forçar tipo individual em modalidade coletiva | Recusa; nenhum IND indevido. |
| A16 | Concluir por jogos/resultados/snapshot sem pódio | Recusa no servidor; não contorna RI07. |
| A17 | Salvar offline e navegar fora/voltar pela SPA | Pódio local correto com indicação pendente. |
| A18 | Retificar duas vezes offline e reconectar | Último resultado persistido; fila confirmada; pontos sem duplicação. |
| A19 | Remover inscrição antes da sincronização | Servidor rejeita; pendência/erro visível, sem falsa confirmação. |
| A20 | Falha HTTP/JSON ao carregar | Exibe erro recuperável, não afirma que não há participantes. |
| A21 | Histórico salvo, elegíveis vazios | Pódio histórico continua visível. |
| A22 | Reabrir após salvar online | Ranking persistido e sem marcador pendente indevido. |
| A23 | Mata-mata completo online/offline | Avanço, terceiro lugar e créditos preservados. |
| A24 | Instalação em subdiretório, mobile e desktop | Links, selects, mensagens e pódio funcionam sem cortes. |
| A25 | Preparar novamente evento já concluído | Nenhum placeholder adicional, nenhuma alteração no resultado. |

## 8. Verificações e disciplina de execução

Antes de refatorar, estabelecer a referência. Depois, executar testes focados por etapa e a suíte completa ao concluir. Seguir as variáveis, servidor isolado e banco de `docs/testing.md`.

```text
npm run build
composer verify
npm run check
npm test
php tests/run_all.php
npm --prefix tests/browser test
```

Não executar simultaneamente suítes que alteram a mesma base. O runner HTTP reconstrói a base de testes: nunca apontar para banco de trabalho. `tests/seed_interclasse_demo.php` é opcional, somente no mesmo ambiente isolado; não substitui fixtures/testes. Se houver migração nova, testar instalação limpa, atualização e repetição, em MySQL e MariaDB conforme a matriz do projeto. Registrar motores não disponíveis em vez de presumir aprovação.

Nesta implementação foram executados `npm run build`, `composer verify`, `npm run check` e `npm test`. A suíte PHP terminou com 193 testes e 2.029 asserções aprovadas (uma depreciação já existente); os testes JavaScript terminaram com 18 casos aprovados. `git diff --check` também passou. O runner HTTP (`php tests/run_all.php`) e os testes de navegador não puderam ser executados porque `SGI_TEST_BASE_URL` não está configurada e o Docker Desktop não disponibilizou o daemon `docker_engine` neste ambiente; isso deve ser repetido no ambiente isolado antes do aceite final.

## 9. Instrução de execução pronta para o Luna

> Implemente o plano `docs/plano-modalidades-individuais-luna.md` na ordem IND-00 a IND-07. Primeiro releia AGENTS.md, o código atual e os diffs locais: há trabalho de agenda e outros ajustes que devem ser preservados. Recupere o fluxo individual existente em vez de criar outro módulo. Diferencie geração sem ranking de homologação inválida; aceite três atletas distintos da mesma turma; mantenha pódio, status e créditos transacionais e a fila offline atual. Em cada etapa, execute os testes indicados e registre a evidência. Não restaure PHP/URLs antigos, não edite assets gerados como fonte e não reescreva migrações aplicadas. Ao final rode o build e as verificações de docs/testing.md, informe qualquer limitação de execução e entregue o resultado sem deploy.

## 10. Checklist de entrega

- [x] IND-00: percurso real reproduzido e referência de testes registrada.
- [x] IND-01: contrato, tipos e validações corrigidos.
- [x] IND-02: elegibilidade, mesma turma e transação verificados.
- [x] IND-03: agenda até formulário e guardas de conclusão implementados.
- [x] IND-04: formulário, feedback e pódio revisados em código e testes de interface.
- [x] IND-05: projeção offline, retificação e sincronização verificadas nos testes JavaScript.
- [ ] IND-06: matriz HTTP/navegador/concorrência executada; aguarda ambiente isolado com base e servidor.
- [x] IND-07: build, suíte local e documentação concluídos; validação HTTP/browser permanece pendente.
