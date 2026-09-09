# Correção das modalidades individuais — plano V2 para implementação pelo Luna

Data: 09/09/2026. Estado: implementação V2 parcial concluída; auditoria de dados e homologação HTTP/browser pendentes.

Esta revisão substitui o plano anterior como roteiro de execução. A implementação realizada segue as etapas marcadas abaixo; não declara o ambiente publicado aprovado. Fonte: `Especificação de Requisitos_ Modalidades Individuais (1).pdf`, três páginas, texto e imagens revisados. O documento é fonte de requisitos do produto; reparações de dados publicados continuam dependendo de auditoria e autorização explícitas.

## 1. Correção central e diferença em relação ao plano anterior

A página 3 apresenta a falha concreta: `/jogos/placar?id_jogo=20` mostra Corrida, status Agendado, título “Final — Confronto 1”, relógio de 20 minutos, duas turmas, VS e botões de gols. Para uma modalidade cujo tipo cadastrado é Individual, essa apresentação está errada. As páginas 1 e 2 exigem o formulário de três posições e o ranking dos atletas.

O primeiro marco será demonstrar que o mesmo percurso usado pelo usuário escolhe a interface correta pelo tipo efetivo da modalidade. Não basta melhorar um formulário que só aparece quando uma tag ou um ID esperado coincide. O ID 20 é uma referência da evidência, não deve ser fixado nos testes ou no código.

A palavra “categoria” no PDF descreve a natureza da competição. No SGI, a decisão deve vir de `modalidades -> tipos_modalidades`, não da categoria escolar (`categorias`) nem do nome do esporte. Corrida não deve ser classificada por busca textual do nome.

Não foi consultado o banco do servidor da imagem nem reproduzido o erro nessa instalação. Portanto, cadastro incorreto, IDs diferentes, resposta da API e assets/cache desatualizados são hipóteses a verificar, não causas já comprovadas.

## 2. Requisitos e decisões de integração

| ID | Origem | Aceite |
| --- | --- | --- |
| V2-01 | PDF p. 1 e 3 | Tipo Individual abre formulário próprio; nunca apresenta VS, gols, final, semifinal ou geração de mata-mata. |
| V2-02 | PDF p. 1 | Os três selects contêm todos os atletas inscritos na modalidade e na edição correspondente. |
| V2-03 | PDF p. 1–2 | Seletores de 1º, 2º e 3º lugar, botão Salvar Ranking e seção Ranking Atual. |
| V2-04 | PDF p. 2 | Três atletas distintos obrigatórios; repetição recusada também pelo servidor. |
| V2-05 | PDF p. 2 | Mesma turma e mesma equipe não impedem ocupação das três posições. |
| V2-06 | PDF p. 2 | Salvar persiste resultado e encerramento, exibe nome, turma e posição; reabrir mantém o pódio. |
| V2-07 | Integração SGI | Pontos, pódio e status são atômicos; reenvio não duplica créditos; retificação aplica diferenças. |
| V2-08 | Integração SGI | Offline preserva seleções, ordem da fila, pendências, isolamento e confirmação real. |

Decisões propostas para execução:

- Conservar um jogo de prova por modalidade e vínculos internos de inscrição via equipes. Não exigir que o usuário monte confrontos ou duas equipes; uma equipe com três atletas basta. Isso não transforma a disputa individual em competição por equipes.
- Mostrar a interface individual inclusive em Agendado. Preservar por padrão a programação exigida pelo SGI, com ação explícita **Iniciar prova** quando a programação estiver completa; após iniciar, liberar Salvar Ranking. Se faltar programação, mostrar orientação e acesso à agenda conforme permissão. Não deixar o usuário preso num formulário bloqueado.
- Exigência de início/programação é integração proposta, não regra textual do PDF. Não introduzir cronômetro, duração obrigatória artificial ou placar de gols só para permitir início individual. Se a política do produto permitir homologação direta de Agendado, ajustar backend, UI e testes juntos; jamais corrigir apenas o botão.
- Encerrar o jogo da prova; não usar `status_modalidade` de ativação cadastral como estado de competição. Manter retificação e valores históricos de crédito conforme o modelo atual.
- Manter a aparência atual do SGI, reproduzindo os elementos e a hierarquia funcional das referências. Não restaurar caminhos PHP antigos das imagens.

## 3. Diagnóstico estático confirmado no código atual

Referências são relativas à raiz do repositório; conferir novamente antes de editar porque há alterações locais em andamento.

| Ponto | Evidência | Correção necessária |
| --- | --- | --- |
| `resources/js/pages/competicoes/placar.js`, `carregarDados` | Individual é decidido por tag IND ou FK numérica igual a 2; a decisão vem depois do carregamento/tratamento de partidas. | Resolver tipo antes de qualquer tratamento coletivo; não usar tag como autoridade. |
| Mesmo arquivo, `renderTudo` | Título é formatado como MM antes da bifurcação; ramo individual retorna antes da ação de início coletivo. | Cabeçalho e ações próprios da prova desde Agendado. |
| Mesmo arquivo, `carregarJogoLocalTemporario` | Define `ehIndividual = false`. | Resolver tipo também no carregamento local; jogo sem tipo conhecido não deve ser assumido coletivo. |
| `chaveamento.js`, controller e serviços PHP | Vários caminhos usam ID 2; tipos são criáveis/editáveis em `MysqliTipoModalidadeRepository`. | Classificação semântica central, consistente entre geração, leitura, operação e sincronização. |
| `ChaveamentoService::gerar` | Converte posições para int antes do `IndividualRankingService`. | Remover coerção prematura e adequar contrato da interface; `12abc`, float e boolean não podem virar atleta válido. |
| `MysqliIndividualRepository::buscarJogoExistente` | Procura só tag IND com LIMIT 1; salvar recebe modalidade, não jogo aberto. | Conferir modalidade, identidade e duplicação; impedir que a tela de um jogo grave em outro. |
| `MysqliIndividualRepository` | Não há bloqueio inicial explícito de modalidade/jogo; consulta de inscritos usa MIN(e.id_equipe). | Serializar preparação/salvamento e revalidar inscrição; não esconder vínculos conflitantes. |
| `mesario-data.js`, `onSynced` | Marca o registro único pending por modalidade como confirmado ao confirmar qualquer mutação. | Confirmação de A não confirma B; reconstruir projeção das mutações restantes. |
| Mesmo arquivo, `capture` | Captura linhas por URL/índice sem substituir integralmente lista anterior. | Snapshot de participantes/ranking deve remover apenas linhas obsoletas daquele snapshot, sem apagar fila. |
| `tests` | Existem testes de serviço, pódio e fila; não foram encontrados os arquivos específicos de UI/browser individual previstos anteriormente. | Testar tela e endpoints reais, além dos testes unitários. |
| Plano anterior e HOM-012 | Há itens marcados concluídos sem execução do percurso HTTP/browser. | Reabrir a homologação e vincular cada aceite a uma evidência efetivamente executada. |

Os testes PHP anteriormente aprovados não demonstram que a instalação da imagem entrega o tipo ou o JavaScript correto. A fixture SQL de teste fixa Individual no ID 2, o que também pode ocultar dependência indevida desse valor.

## 4. Etapas de execução

### COR-00 — Reproduzir a falha e criar teste que falha antes da correção

Ler `AGENTS.md`, `docs/testing.md`, diffs locais, rotas, código do placar e composição atual de CSS/assets. Preservar trabalho de agenda e reorganização de estilos.

No ambiente isolado, montar uma edição com prova individual, quatro atletas (três na mesma equipe/turma), um não inscrito e um coletivo de controle. Criar também cenário de tipo Individual com ID diferente de 2. Usar dados sintéticos e reproduzir a página 3 com jogo marcado MM vinculado a modalidade Individual.

Registrar, na mesma navegação: URL de entrada, jogo e modalidade retornados por `/api/v1/jogos`, FK e nome do tipo em `tipos_modalidades`, status/tag, URL/hash do JavaScript carregado, mensagens do console e estado de cache. Fazer a mesma comparação online e pela SPA preparada.

Consulta de diagnóstico, parametrizada pelo jogo observado:

```sql
SELECT j.id_jogo, j.nome_jogo, j.status_jogo,
       m.id_modalidade, m.nome_modalidade, m.interclasses_id_interclasse,
       m.tipos_modalidades_id_tipo_modalidade,
       tm.nome_tipo_modalidade
FROM jogos j
JOIN modalidades m ON m.id_modalidade = j.modalidades_id_modalidade
JOIN tipos_modalidades tm ON tm.id_tipo_modalidade = m.tipos_modalidades_id_tipo_modalidade
WHERE j.id_jogo = ?;
```

Criar `tests/browser/individual-ranking.spec.cjs` com asserções de formulário presente e controles coletivos ausentes. O caso deve falhar antes da mudança. Se a base real mostrar cadastro coletivo, registrar a divergência de cadastro e preparar sua correção, sem inferir Individual pelo nome Corrida.

Saída: diagnóstico separando dado, contrato da API, lógica da tela e versão publicada; teste reproduzível da interface errada.

### COR-01 — Resolver tipo de competição de forma única

Criar regra de domínio, por exemplo `TipoCompeticaoRules`, que receba o tipo cadastrado e produza `individual`, `mata_mata` ou desconhecido. No schema atual, consultar `nome_tipo_modalidade` por JOIN e normalizar apenas nomes reconhecidos (espaços, caixa e grafias aceitas de Mata-Mata). Não usar ID 2, nome de esporte ou tag para decidir. Tipo ausente/desconhecido deve bloquear operação com mensagem de configuração, sem cair silenciosamente no coletivo.

Adicionar às respostas relevantes um campo semântico `tipo_competicao`; conservar IDs e campos usados pelos contratos atuais. Aplicar o mesmo resolvedor no servidor em jogos, modalidades, chaveamentos, cronômetro, resultado, atualização direta e gateway de sincronização. Inventariar consumidores offline e geradores de edição/agenda antes de substituir comparações numéricas; não trocar todo `== 2` indiscriminadamente, pois há níveis de acesso e outras regras.

No cliente, consumir o campo semântico. Cache preparado antigo que só possui FK pode resolver pelo catálogo de tipos armazenado; se insuficiente, solicitar atualização quando online ou informar indisponibilidade offline. Não limpar fila ou IndexedDB. Divergência tipo/tag é erro de integridade a resolver na etapa seguinte, nunca autorização para exibir gols.

Não é obrigatória nova migração para esta correção. Um código estável persistido para os tipos poderá ser usado se a auditoria exigir, com nova migração e tratamento explícito de nomes desconhecidos; nunca reescrever migrações aplicadas.

Saída: Individual funciona com ID diferente de 2; tipo desconhecido não vira mata-mata; coletivo de controle permanece funcional.

### COR-02 — Tratar jogos existentes gerados no formato errado

Auditar jogos de modalidades Individual: nenhum IND, IND único, múltiplos IND, tags MM/POS e vínculos/resultados/créditos já existentes. Incluir reservas de agenda e referências de ocorrências, artilharia e fila ao avaliar impacto.

Preparar uma rotina de diagnóstico com prévia e aplicação explícita, separada dos GETs. Não renomear, apagar ou converter dados ao abrir o placar.

- Caso simples: um único jogo indevido, Agendado, sem resultados/créditos/ocorrências/artilharia nem reservas derivadas conflitantes. Propor reaproveitar o mesmo ID e programação, converter para tag IND e ajustar somente placeholders de participantes. Preservar vínculos por ID e conferir colisão de tag.
- Caso com múltiplos jogos, fases futuras, pontuação, histórico ou mutações pendentes: produzir relatório por modalidade e plano de reconciliação; não excluir jogos nem recalcular pontos automaticamente. Suspender novas homologações daquela prova até identidade coerente; manter consulta do histórico.
- Caso de cadastro com tipo errado: corrigir primeiro a associação ao tipo com autorização aplicável, e só então tratar os jogos. Não alterar tipo global que afetaria outras modalidades.

A futura aplicação aos dados deve ter backup, prévia do que muda, transação, repetição idempotente e evidência antes/depois. O planejamento atual não autoriza modificar a base da imagem. Operações destrutivas exigem confirmação concreta conforme as regras do projeto.

Passar e validar `id_jogo` no salvamento individual para garantir que a prova aberta é a gravada. Payloads já enfileirados sem esse campo só podem resolver para IND único e coerente; caso ambíguo retorna conflito recuperável. Nunca gravar em um IND diferente e marcar o MM aberto como concluído apenas na tela.

Saída: URL existente aponta para a prova correta após reparação segura; casos ambíguos ficam diagnosticados, sem perda de dados.

### COR-03 — Renderizar a prova desde a entrada da página

Editar `placar.js`, template e CSS de origem atuais. Resolver tipo antes de enriquecer/truncar partidas, inicializar relógio, artilharia ou renderizar cabeçalho.

Estados obrigatórios:

| Estado | Tela/ação |
| --- | --- |
| Carregando tipo/dados | Indicador de carregamento; não mostrar placar coletivo provisório. |
| Tipo individual e Agendado | Título da prova; formulário individual; Iniciar prova se programação válida; orientação de agenda se incompleta. |
| Iniciado/Pausado | Três selects e Salvar Ranking habilitados quando há ao menos três elegíveis. |
| Concluido/Finalizado | Ranking Atual persistido; retificação conforme permissão existente. |
| Zero/um/dois inscritos | Mensagem específica; nenhum salvamento parcial; histórico anterior permanece visível. |
| Erro HTTP/JSON | Erro e Tentar novamente; preservar dados e escolhas anteriores. |
| Tipo desconhecido/jogo inconsistente | Diagnóstico claro; sem gols e sem gravação em outra prova. |

Em individual, não renderizar cronômetro de gols, duração de 20 minutos, VS, botões +/−, Finalizar jogo coletivo ou fases MM. Título usa nome da prova, mesmo se um registro antigo tiver tag MM. A integridade do registro será tratada separadamente.

Todos os selects contêm todos os elegíveis, com nome/turma escapados. Desabilitar somente atleta já escolhido em outra posição; reabilitar ao trocar/limpar. Não comparar turma/equipe para impedir repetição. Ranking ordenado por posição numérica; label/medalha devem vir da posição, não do índice de chegada.

Após gravação confirmada, renderizar resposta autoritativa ou recarregar. Se a recarga falhar, informar “Resultado salvo; não foi possível atualizar a visualização” e oferecer nova leitura. Evitar reenvio duplo e respostas atrasadas de uma tela anterior na SPA.

Editar também navegação de jogos, agenda e chaveamento: acesso visível ao resultado individual, URLs com base de instalação, rótulo Preparar prova e nenhuma exigência de duas equipes. Não exigir URL digitada manualmente para chegar ao formulário.

Saída: comparação visual das páginas 1–2 em desktop/mobile e reprodução negativa da página 3.

### COR-04 — Fechar contrato, autorização e persistência

Remover cast das posições de `ChaveamentoService::gerar`; ajustar `ChaveamentoManagement::saveIndividual` para receber entrada bruta até a validação central. Validar inteiros positivos representáveis, rejeitando float, bool, array, null, campos faltantes, valores fora de faixa e texto como `12abc`. Preservar ausência de ranking como preparação; ranking presente inválido jamais prepara jogo.

Organização prepara/programa; mesário opera prova da edição ativa e não pode criar jogo com POST sem ranking. Aluno não escreve. Reutilizar CSRF, `CompetitionAccess` e protocolo de mutação existente.

Dentro da transação: bloquear modalidade e validar tipo; resolver e bloquear jogo único/ID; validar estado; revalidar os três vínculos e sua coerência de turma/edição; carregar créditos bloqueados; substituir três posições; aplicar deltas; concluir jogo. Usar a mesma ordem de bloqueios nos caminhos online/lote e conferir compatibilidade com inscrição/desinscrição. Testar concorrência real.

Não usar MIN(e.id_equipe) para mascarar vínculos contraditórios. Deduplicar o mesmo vínculo por atleta; se um atleta tiver equipes/turmas conflitantes, relatar inconsistência sem escolher arbitrariamente. Obter equipe/turma do servidor. Preservar três posições da mesma equipe.

Assegurar que preparação repetida não altera prova concluída. Reenvio idêntico aplica delta zero. Com pontos sintéticos 10/7/5, três atletas da mesma turma somam 22; substituir campeão por outra turma retira 10 da primeira e credita 10 na segunda. Não tocar arrecadação/ocorrências.

Saída: endpoints normais e de sincronização obedecem às mesmas regras e não permitem conclusão genérica individual.

### COR-05 — Corrigir projeção e confirmação offline

Preservar stores, namespace por operador, identificadores e fila já pendente. Aquecer tipo, jogo, participantes e ranking antes da desconexão.

Manter snapshot confirmado e projetar sobre ele todas as mutações pendentes da mesma prova na ordem da fila. A confirmação da primeira gravação não pode confirmar a última retificação. Só retirar o indicador quando não restar mutação relevante. Associar confirmação ao identificador da mutação, não apenas à modalidade.

Captura de lista online substitui o snapshot daquele recurso/escopo, incluindo lista vazia; não acumula atletas removidos por índices antigos. Resposta antiga não substitui projeção nova. Leitura de jogos deve considerar também pendência de ranking da prova, para não restaurar Agendado sobre uma conclusão local.

Salvar local só é sucesso após gravação durável. Falha de IndexedDB mantém formulário e status anterior. Rejeição no servidor mantém erro recuperável; não apagar fila nem converter rejeição em confirmação. Reentrada pela SPA e atualização de assets devem preservar mutações pendentes. Não prometer abertura fria sem casca preparada.

Saída: duas retificações offline, confirmação parcial, navegação e reconexão mantêm último pódio e pontos corretos.

### COR-06 — Testes obrigatórios e entrega

Criar testes pequenos em `tests/javascript/individual-ranking.test.cjs`, contratos HTTP em `tests/Integration/IndividualRankingHttpTest.php` (registrar no runner) e percurso real em `tests/browser/individual-ranking.spec.cjs`. Ampliar testes de serviços, `PodiumCreditTest`, `IndividualSyncCreditTest`, fila offline e concorrência existentes.

| Grupo | Casos mínimos |
| --- | --- |
| Tipo/tela | Individual ID 2 e ID diferente; coletivo; tipo ausente; tag MM com tipo Individual; tag IND com tipo coletivo; API divergente do cadastro. |
| Fluxo | Agendado com/sem programação; iniciar prova; salvar três posições; reabrir; retificar; entrada pela agenda e chaveamento; raiz e subdiretório. |
| UI | Formulário presente e controles coletivos ausentes; mesmo atleta em cada par; todos da mesma equipe; limpar/trocar; ordem de ranking embaralhada; nomes longos/HTML; mobile. |
| Contrato | Ranking omitido versus null/vazio/parcial; IDs inválidos/overflow; atleta externo/inativo; edição errada; mesário preparando; aluno escrevendo. |
| Dados | Um IND; duplicados; jogo MM incorreto sem resultados; conflito com histórico; repetição da reparação; mesmo ID/programação preservados. |
| Atomicidade | Falha induzida depois de remover partidas; rollback de status/pódio/pontos; dois processos salvando; preparação concorrente; inscrição removida antes de salvar/sincronizar. |
| Offline | Duas retificações A/B; confirmar A deixa B pendente; duas modalidades; trocar sessão; snapshot vazio remove elegíveis antigos; falha local; recarga SPA; reconexão real. |
| Regressão | Mata-mata online/offline, avanço, terceiro lugar, agenda, inscrição, ocorrências e ranking geral. |

Executar antes e depois das alterações, conforme `docs/testing.md`: `composer verify`, `npm run check`, `npm test`, `npm run build`, `php tests/run_all.php` e `npm --prefix tests/browser test`. Testes de banco somente em base descartável; nunca compartilhar a mesma base entre suítes concorrentes. Validar MySQL/MariaDB conforme matriz do projeto e registrar motor não executado.

Docker é uma opção, não o único meio: se indisponível, verificar instância de teste dedicada e servidor PHP isolado. Sem ambiente disponível, entregar código com homologação pendente, nunca declarar sucesso do percurso. Testes de UI com respostas controladas ajudam a testar renderização, mas não substituem o percurso com banco real.

Evidência final: imagem do formulário antes de salvar e pódio depois; respostas de tipo/participantes/ranking; estado e créditos do banco; trace de navegação; fila antes/depois de A e B; versão do asset servido; resultado de cada suíte. Só atualizar snapshots após revisar a mudança esperada.

## 5. Homologação e acompanhamento

Reabrir HOM-012 com vínculo a esta revisão. Preservar os resultados históricos das suítes anteriores, mas remover a conclusão de que eles comprovam atendimento visual/funcional do PDF. Os itens anteriormente marcados como percurso reproduzido, concorrência verificada ou UI testada precisam de novas evidências.

- [ ] COR-00: falha reproduzida e teste inicialmente vermelho.
- [x] COR-01: tipo semântico central no servidor/cliente/cache para as telas e gateways cobertos nesta rodada.
- [ ] COR-02: diagnóstico e tratamento seguro de jogos existentes (depende de auditoria do banco publicado).
- [x] COR-03: formulário alcançável desde Agendado, sem elementos coletivos, e início da prova.
- [x] COR-04: contratos, permissões, integridade e créditos verificados nas suítes PHP disponíveis.
- [x] COR-05: fila e confirmação parcial verificadas nos testes JavaScript offline.
- [ ] COR-06: HTTP, banco publicado e browser real ainda pendentes; teste browser foi adicionado e fica opt-in.
- [ ] HOM-012: aceite final registrado após comparação com o PDF revisado.

## 6. Instrução pronta para o implementador

Implementar este plano por COR-00 a COR-06, uma etapa verificável por vez. Começar pelo teste da tela errada, não pelo refinamento visual do formulário existente. Relacionar cada mudança ao requisito e executar seus testes antes de avançar. Preservar alterações locais, arquitetura modular, esquema offline e migrações aplicadas. Não classificar pelo nome do esporte, ID fixo ou tag; não reparar dados em GET; não marcar aprovação com base apenas em testes unitários. Ao terminar, atualizar homologação com resultados realmente executados e pendências explícitas. A aplicação de reparações a dados existentes deve respeitar prévia, autorização e segurança descritas em COR-02.
