# Plano — vínculo obrigatório entre ponto e atleta

Data: 09/09/2026. Estado: implementado e validado em 09/09/2026.

Este documento substitui o plano apresentado na conversa. O plano foi executado com migração, API, interface, operação offline, preservação de histórico e testes de regressão.

## 0. Resultado da implementação

- A migração `010_vinculo_obrigatorio_pontos.sql` adiciona a identidade da jogada, a partida/equipe de origem, o estado de anulação e a chave idempotente. Jogos anteriores permanecem explicitamente legados; jogos novos exigem vínculo.
- O novo fluxo `GET/POST/PUT /api/v1/pontos` lista somente competidores ativos e inscritos na equipe exata, rejeita atleta ausente ou inválido e atualiza evento e placar na mesma transação.
- O botão `+` abre o seletor sem alterar o placar. A anulação reduz o placar, mas mantém a ação individual e sua indicação no histórico.
- Rotas antigas de artilharia e alterações diretas de placar foram protegidas; finalização, retificação, byes e reconstrução de chaveamento preservam a consistência da origem do placar.
- A fila offline, o IndexedDB e os jogos temporários carregam elencos por equipe, preservam anulações e reidratam o placar sem duplicar jogadas.
- Foram adicionados testes de regressão para seleção obrigatória, escopo de elenco, idempotência, anulação histórica, rotas alternativas, concorrência, finalização offline e cache do mesmo atleta em equipes diferentes.

Validação executada: suíte integrada `473/473` asserções; `npm run check` com 40 arquivos; `npm test` com 23 testes; `composer verify` com 229 testes PHPUnit, 2.175 asserções, PHPStan sem erros e PHP CS Fixer sem alterações; e os 2 cenários do torneio offline no navegador aprovados.

## 1. Regras e limites

- Todo novo ponto lançado pelo placar coletivo deve corresponder a uma jogada de um competidor ativo, inscrito na equipe exata participante do jogo.
- Clicar em `+` abre a seleção sem alterar placar ou criar mutação. Confirmar sem atleta é bloqueado com: “Selecione o aluno responsável pela jogada para confirmar o ponto”. Fechar, cancelar ou navegar antes da confirmação não registra nada.
- Confirmar registra uma jogada e acrescenta um ponto como uma única operação. A unidade atual é 1; este trabalho não introduz sets ou cestas de valores diferentes.
- Anular um ponto confirmado reduz somente sua contribuição ao placar. A ação individual e sua autoria permanecem salvas, inclusive no histórico final. Exibir separadamente ações realizadas, pontos válidos e pontos anulados; não excluir ações anuladas da contagem de ações individuais.
- Cancelar a seleção antes da confirmação e anular uma jogada já confirmada são operações distintas.
- Aplicar a regra a todos os placares coletivos com lançamento de pontos, independentemente do nome da modalidade ou ID numérico do tipo. Não alterar pódios individuais, arrecadações ou penalidades do ranking geral.
- Passagem automática por bye representa avanço, não uma jogada. Eliminar sua dependência de um gol fictício, preservando o avanço e a premiação corretos.
- Manter as permissões existentes de operação e retificação. Administrador também não pode criar ponto sem atleta. Edições encerradas permanecem protegidas.

## 2. Evidências e lacunas atuais

| Local | Evidência e consequência |
| --- | --- |
| `resources/js/pages/competicoes/placar.js`, `ajustarGols` | Incrementa e agenda o salvamento antes de abrir o modal; fechar o modal deixa ponto sem responsável. |
| Mesmo arquivo, `ehFutsal` e carregamento local | Seleção depende de nome/ID fixo; jogos temporários não inicializam todos os dados auxiliares. |
| Mesmo arquivo, `carregarAlunosArtilheiro` | Consulta por turma, permitindo mistura entre equipes da mesma turma. |
| `ArtilheiroController` e `MysqliArtilheiroRepository` | Participação só é verificada quando o usuário informado tem nível 3; faltam validações completas da equipe e da elegibilidade. |
| `PartidaService`, `ResultadoService`, `MysqliChaveamentoSyncGateway` | Há entradas independentes que gravam placares absolutos sem jogadas. |
| `offline-core.js`, `idbFindUrl` | Pode reutilizar lista de atletas de outro jogo pela turma. |
| `offline-core.js`, ordenação da fila | Resultados de jogos temporários são enviados antes da artilharia; incompatível com exigir autoria antes da conclusão. |
| `mesario-data.js` | Placar e artilharia têm projeções separadas; a leitura local de artilharia não produz necessariamente a mesma agregação da API. |
| `MysqliChaveamentoRepository`, `inserirJogoBye` | Usa resultado 1 para representar passagem automática. |
| Testes de persistência e torneio do navegador | Fecham o modal sem atleta e esperam manter o gol; essas expectativas precisam ser substituídas. |

## 3. Persistência e invariantes

Adicionar nova migração, escolhendo o próximo número disponível na implementação. Não editar migrações aplicadas.

Evoluir `artilheiros` para representar cada nova jogada com `num_gol = 1`, chave estável de jogada, jogo, equipe, turma de origem, atleta, referência à partida, operador e instante de registro. Acrescentar `conta_no_placar` e dados de anulação (operador, instante e chave da operação). A chave da jogada deve ser única e persistir entre cliente e servidor. O operador vem da sessão, nunca do corpo enviado pelo cliente.

Os campos novos dos registros antigos podem ficar sem preenchimento, com origem legada explícita. Novas gravações exigem todos os vínculos válidos. Não inferir partida, equipe ou autoria de registros ambíguos. Evitar usar a turma atual do usuário como única fonte da turma histórica da jogada.

A referência física à partida não pode causar exclusão em cascata do histórico. Inspecionar reconstruções de chaveamento e exclusões antes de definir as FKs: conservar a identidade histórica e bloquear reconstrução destrutiva que alcance jogos com jogadas, até existir retificação explícita que preserve esses registros. Não retirar proteção de integridade para permitir perda silenciosa de autoria.

Para jogos reconciliados sob a nova regra:

- placar da equipe = soma das contribuições das jogadas que contam no placar;
- ações individuais = todas as jogadas registradas, inclusive anuladas;
- nenhuma anulação apaga ou zera a ação original;
- repetir registro ou anulação não muda novamente o estado;
- uma falha reverte jogada, placar e confirmação idempotente na mesma transação.

`resultado_partida` continua como projeção utilizada pelos consumidores atuais. Novos pontos passam a ter a jogada como origem verificável. Não comparar o placar com a soma de todas as ações, pois isso invalidaria anulações legítimas.

## 4. Casos de uso e contratos

Implementar casos de uso em `Competicoes/Application`, com repositórios em Infrastructure e controladores HTTP pequenos. Reutilizar as proteções de edição, CSRF, transação e `MutationAction`.

### 4.1 Selecionar e registrar

Proposta de contrato: `GET /api/v1/pontos?acao=atletas&id_jogo=...&id_equipe=...` e `POST /api/v1/pontos`.

O POST recebe chave estável da jogada, identidade do jogo, equipe e atleta. Referências temporárias carregam modalidade e tag, validadas no servidor. Não recebe placar final arbitrário. Retorna identidade da jogada, IDs resolvidos, placar e revisão do jogo.

Dentro da transação, resolver o jogo; bloquear jogo/partida e recursos necessários; verificar edição, estado operacional, cronômetro conforme as regras existentes, equipe e partida ativas, competidor nível 3 ativo e inscrição na equipe exata. Aplicar impedimentos disciplinares pertinentes. Leitura do seletor e escrita usam a mesma política. Coordenar as travas com alterações de inscrição/atividade para evitar validação que fique obsoleta antes do commit.

Registrar a jogada, atualizar o placar e salvar a confirmação idempotente. Mesmo identificador com conteúdo ou operador diferente gera conflito. Eventos distintos simultâneos não podem sobrescrever pontos uns dos outros.

### 4.2 Anular

Proposta: `PUT /api/v1/pontos`, com ação `anular` e chave da jogada. A identidade da anulação também acompanha os reenvios.

O botão `−` apresenta o último ponto válido da equipe para confirmação, mostrando o atleta. Uma lista de jogadas permite selecionar outro ponto quando necessário. O alvo é capturado pelo identificador; o servidor não escolhe novamente “o último” ao receber a requisição.

Bloquear o mesmo jogo e a jogada. Se já anulada, devolver seu estado sem novo desconto. Caso contrário, marcar sua contribuição como anulada, registrar autoria/data da anulação e atualizar o placar. Anulação não depende de o atleta continuar elegível hoje. Não permitir placar negativo.

### 4.3 Fechar caminhos alternativos

- `PUT /partidas`: impedir alteração direta do placar que contorne os eventos.
- `POST /partidas`, `/resultados` e `/sincronizacao/chaveamento`: validar a origem dos pontos; nenhuma dessas rotas pode introduzir ou apagar contribuições arbitrariamente.
- `/artilheiros`: retirar escrita operacional independente após a transição. O PUT atual sobrescreve registros por usuário/jogo e não deve modificar as novas jogadas.
- Finalização: conferir a revisão e o placar derivado, aguardando as operações anteriores online e offline; então concluir e avançar. Snapshot divergente é conflito, não autorização para sobrescrever o servidor.
- Retificação de jogo concluído: usar operação explícita com os mesmos eventos e preservação histórica; reconciliar vencedor/pódio na mesma transação. Bloquear alterações que invalidem jogos posteriores com histórico até tratamento explícito desses jogos.
- Auditar todos os produtores de `resultado_partida`, incluindo bye, fixtures e sincronização. Não aplicar a regra de gols às posições de pódio individual.

## 5. Interface e relatórios

Reutilizar o modal existente, fixando a equipe acionada. Durante carregamento, lista vazia ou erro, impedir confirmação. Não selecionar automaticamente o primeiro atleta. Proteger respostas assíncronas contra troca de equipe, fechamento e navegação SPA.

Depois da confirmação durável, atualizar placar e ação individual juntos. Bloquear duplo clique e mostrar se o registro está confirmado no servidor ou aguardando sincronização. Se a fila foi salva mas sua projeção falhou, recuperar a mesma intenção; não orientar nova jogada com outra chave.

Mostrar a jogada anulada no histórico com indicação clara de que não conta no placar. Ajustar consultas de artilharia/destaques e seus consumidores para contar ações realizadas conforme o requisito, com total anulado separado. Preservar restrições de acesso já existentes para alunos e edições.

## 6. Offline e jogos temporários

Preservar schema, stores, namespaces e identificadores atuais enquanto houver dados pendentes. Usar registros discriminados nas stores existentes para jogadas/elencos e adaptar leitores para não misturar eventos, cadastros e agregados.

Uma intenção durável na fila contém atleta e ponto juntos. No banco estruturado, atualizar jogada e projeção do placar em uma transação IndexedDB. Como fila e projeção podem estar em bancos diferentes, não presumir transação única entre eles: usar a fila como origem recuperável, manter `projectionPending` e reexecutar projeções de forma idempotente. A UI confirma somente após a projeção concluir. Atualização remota não deve apagar intenções locais ainda pendentes.

Pré-carregar elencos completos por equipe/modalidade/edição, com os dados mínimos de elegibilidade, além dos jogos e impedimentos necessários. Aplicar localmente suspensões/expulsões pendentes. Proibir fallback apenas por turma. Jogos derivados usam suas equipes reais e esses elencos; ausência de dados impede lançamento com mensagem clara.

Sequência obrigatória para cada jogo temporário:

1. Sincronizar e confirmar os resultados dos predecessores necessários à formação das equipes.
2. Resolver/materializar o jogo por modalidade, tag e participantes validados, sem encerrá-lo e sem gravar gols fictícios. Reutilizar o jogo já criado pelo avanço quando existir; materialização deve ser idempotente.
3. Aplicar início e demais transições operacionais na ordem correta.
4. Sincronizar jogadas, anulações e ocorrências respeitando dependências e ordem por jogo.
5. Finalizar usando a revisão/placar conferidos e avançar o chaveamento.

Implementar materialização explícita no módulo Sincronizacao, por rota `/api/v1/sincronizacao/jogos`. Ela não aceita placar ou status concluído arbitrários e não permite inventar participantes. Mapear IDs locais para reais sem alterar a identidade ou conteúdo lógico das mutações já enviadas. A anulação depende da jogada por chave estável, inclusive antes da confirmação remota.

Se a inscrição/atividade mudou no servidor desde o lançamento offline, preservar a intenção para revisão e impedir finalização dependente. Não aceitar automaticamente um relógio ou snapshot do cliente como prova de elegibilidade passada. Ocorrências locais anteriores devem afetar a seleção; ocorrências posteriores não apagam jogadas já aceitas.

## 7. Dados existentes e entrega

Antes da ativação, inventariar jogos com placar sem autoria, registros agregados de artilharia, byes, jogos concluídos e formatos de filas pendentes. A migração estrutural não deve alterar placares históricos nem atribuir responsáveis por dedução.

Preferir concluir a sincronização do cliente antigo antes da atualização. Quando isso não for possível, conservar corpo, proprietário e chave da mutação, identificando operações incompatíveis como pendentes de reconciliação. Não descartá-las nem responder sucesso sem persistir. Não manter uma rota aberta que permita novos pontos sem atleta sob pretexto de compatibilidade.

Jogos históricos ficam identificados como legado; jogos em andamento inconsistentes exigem reconciliação explícita antes de adoção integral da nova regra. Produzir diagnóstico para a organização, preservando o original. Atribuição sem evidência permanece pendente. Não adicionar crédito duplicado ao importar uma ação já registrada.

Não executar reparos em dados de trabalho ou deploy como parte da implementação sem solicitação correspondente.

## 8. Etapas e critérios de saída

| Etapa | Trabalho | Critério de saída |
| --- | --- | --- |
| P0 | Referência de testes, reprodução isolada e inventário de escritas/legado | Defeito demonstrado; falhas preexistentes e todas as rotas documentadas. |
| P1 | Migração, eventos, elegibilidade e transações | Ponto e atleta atômicos; replay e concorrência seguros; anulação preserva ação. |
| P2 | Proteger rotas, conclusão, retificação e bye | Nenhuma API cria ponto sem evento; bye avança sem atleta/gol fictício. |
| P3 | Fila, elencos, projeções, materialização e dependências | Torneio offline sincroniza jogadas antes da conclusão, sem duplicar/perder registros. |
| P4 | Modal, anulação e relatórios | Cancelar não pontua; confirmação pontua; anulação mantém autoria e ação individual. |
| P5 | Transição, suíte completa e documentação | Migração e matriz aprovadas, limitações reais registradas e build conferido. |

## 9. Matriz mínima de aceite

- Abrir `+`, cancelar, fechar com Escape ou navegar: placar, histórico e fila inalterados.
- Confirmar sem atleta ou com dados inválidos: recusa sem escrita parcial.
- Atleta de outra equipe da mesma turma, outra modalidade/edição, não inscrito, inativo, suspenso, expulso ou usuário administrativo: recusa.
- Equipe/partida inativa ou jogo não operável: recusa coerente na UI e API.
- Confirmar uma vez: um ponto e uma ação; repetir a mesma mutação: nenhum acréscimo.
- Dois pontos distintos simultâneos: ambos preservados. Duas anulações da mesma jogada: desconto único.
- Anular ponto: placar diminui; atleta conserva a ação; histórico indica anulação após reload e sincronização.
- Falha entre INSERT e atualização/confirmacão: rollback integral. Falha local/reinício da SPA: recuperação da mesma intenção.
- API direta e sincronização em lote não contornam autoria; testar os três perfis operadores.
- Elenco em cache de outro jogo/turma não é aceito; impedimento local atualiza a seleção.
- Jogo temporário, reconexão antes de finalizar, torneio inteiro offline e resposta perdida: IDs resolvidos e histórico preservado.
- Finalização concorrente ou com jogadas pendentes: nenhum placar parcial ou sobrescrito; conflito claro quando necessário.
- Mudança de elegibilidade durante desconexão: pendência preservada; dependentes não finalizados silenciosamente.
- Bye, pódio individual e retificação com jogos posteriores: sem gol fictício, sem regressão e sem exclusão de histórico.
- Instalação limpa, upgrade, repetição de migração e fila antiga: nenhum reset/perda de dados.

Atualizar fixtures para utilizar competidores realmente inscritos; substituir os testes que fecham o modal e esperam manter o ponto. Manter cobertura de persistência imediata, agora depois da confirmação.

Executar antes da refatoração e ao final, conforme `docs/testing.md`, em servidor/banco isolados: `php tests/run_all.php`, `composer verify`, `npm run check`, `npm test`, `npm run build` e `npm --prefix tests/browser test`. Suítes que alteram a mesma base devem ser sequenciais. Testar migração nos motores suportados disponíveis e registrar os não ensaiados. Não declarar o percurso aprovado apenas com inspeção ou mocks.
