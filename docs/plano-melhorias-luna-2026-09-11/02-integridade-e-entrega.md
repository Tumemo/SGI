# N08–N13 — Integridade, concorrência e entrega

## N08 — Validar resultados e eventos sem coerção silenciosa

Achado C07. Fontes: `Competicoes/Presentation/Http/ResultadoController.php`, `Application/ResultadoService.php`, `PlacarService.php`, `Infrastructure/MysqliPontoRepository.php`; testes de resultado/pontos e payloads gerados pelo placar/offline.

1. Adicionar regressão para gols `3.9`, `-0.5`, string decimal, booleano, array, valor fora da faixa do banco e elemento escalar no array de resultados. O contrato atual é de placar inteiro não negativo.
2. Validar o tipo de `pontos` antes de qualquer `array_filter`. Se um item enviado for inválido, recusar o lote; não descartá-lo e concluir apenas os itens restantes.
3. Normalizar strings inteiras válidas de formulários explicitamente. Não usar `(int)` como validação. Reutilizar regras de inteiro existentes se adequadas; não introduzir validador genérico grande para esta tarefa.
4. Manter limites do tipo persistido e validação de chaves/equipe/atleta, inclusive entradas offline. Não exigir novos campos de filas antigas sem adaptador compatível.
5. Rejeitar entrada inválida antes de escrever. Cobrir rollback quando o erro depende de dado persistido e só é detectado dentro da transação.
6. Confirmar que placar zero é válido no contexto correto, empate continua obedecendo ao tipo de competição e pontos individuais continuam obrigatórios nos jogos novos. Preservar tratamento existente de byes e histórico.

**Aceite:** entradas inválidas retornam 4xx sem pontos, conclusão, avanço ou créditos parciais; entradas válidas e reenvios legados aprovados. Unitários, `PlacarAndArtilhariaTest`, `AtomicMutationTest`, torneio online/offline e `all`.

## N09 — Validar limites/status de modalidade

Achado C07, segunda parte. Fontes: `ModalidadeService`, `ModalidadeRepository`, `MysqliModalidadeRepository`, `EquipeCapacityRules`, `InscricaoRules`, formulário de modalidades e testes correspondentes.

1. Reproduzir `max_inscrito_modalidade=-1` e `max_equipes='abc'` em create/update; incluir fração, booleano, array, overflow e valor negativo pequeno.
2. Manter o significado existente de ilimitado para valores explicitamente suportados, como zero/NULL/vazio. Texto arbitrário, fração e negativo não podem virar ilimitado. Documentar a tabela de entradas aceitas.
3. Validar `status_modalidade` contra os valores reais permitidos e não esperar erro de ENUM do banco. Aplicar a mesma regra no POST e PUT parcial.
4. Conferir limites de banco e multiplicação de capacidade; não permitir overflow transformar capacidade em ilimitada. Reutilizar as regras de capacidade existentes.
5. Cobrir fronteiras de 0, 1, limite máximo aceito, capacidade cheia e inscrição repetida. Não alterar a política funcional de equipe padrão por conveniência.

**Aceite:** payload inválido não é persistido e não remove limite; formulários existentes continuam enviando valores aceitos. Unitários, HTTP de modalidades/equipes/inscrição e navegador do cadastro.

## N10 — Preservar escopo de cadastros relacionados

Achado C08. Executar dois lotes pequenos.

### N10a — Modalidades

Fontes: `ModalidadeService`, `MysqliModalidadeRepository`, categorias/edições e schema completo.

1. Reproduzir modalidade com edição A e categoria de B, inclusive PUT que altera apenas categoria e PUT que altera apenas edição.
2. Carregar estado final da entidade para validar updates parciais. Categoria e edição precisam corresponder. Verificar existência/status conforme regras atuais, evitando proibir leitura de histórico.
3. Impedir troca de edição de modalidade com equipes/jogos/inscrições/créditos relacionados sem fluxo específico de transferência. Não migrar descendentes implicitamente.
4. Validar e gravar no mesmo trecho protegido quando a relação puder ser alterada em concorrência. Erro deve deixar dados anteriores íntegros.

### N10b — Turmas pelo ranking

Fontes: `RankingService::atualizar`, `MysqliRankingRepository::updateTeam`, `Participantes/Application/TurmaService.php`, repositório de turmas, usuários, equipes e créditos.

1. Reproduzir PUT do ranking que troca edição/categoria de turma vinculada. Confrontar as mesmas validações no endpoint de turmas para impedir um caminho alternativo mais permissivo.
2. Reutilizar um caso de uso/contrato de atualização consistente; não duplicar SQL/regra entre módulos. Preservar ajustes de pontuação legitimamente permitidos.
3. Como comportamento conservador, rejeitar transferência de turma vinculada entre edições. Não mover alunos/pontos/doações/pódios automaticamente. Campos descritivos e atualização válida de categoria continuam conforme contrato.
4. Antes de eventual nova constraint, gerar diagnóstico apenas de leitura em base sintética com inconsistências. Não reparar dados reais por suposição. Se migração se mostrar necessária, criar nova numerada após a última, validar upgrade e repetição nos dois motores.

**Testes:** combinações A/A, A/B, IDs ausentes, inativos quando aplicável, PUT parcial, entidade com/sem dependentes, rollback e caminhos alternativos. **Aceite:** nenhum endpoint pode criar vínculo entre edições incompatíveis; histórico e totais preservados. Unitários + integração + navegador + `all`; matriz SQL se alterar schema/SQL específico.

## N11 — Investigar e proteger anulação concorrente com conclusão

Risco R01, ainda não reproduzido. Fontes: `PontoService::anular`, `MysqliPontoRepository::buscar/anular/contextoPartida`, `ResultadoService::lancar`, `MysqliPartidaGateway::lockGame`, `Transaction`, `MutationAction`, `tests/Support` e `ConcurrentInvariantsTest`.

1. Mapear a ordem real das travas de resultado e ponto. Fazer diagrama simples no registro da tarefa, com conexão A/B e eventos de barreira.
2. Preparar jogo em andamento com placar vinculado e pelo menos um ponto. A lê ponto/status para anulação; B conclui jogo; A retoma a anulação. Usar processos/conexões/sessões independentes, não uma sessão PHP que serialize tudo.
3. Se a intercalação for impossível pelas travas existentes, provar isso com teste de barreira e encerrar como hipótese refutada. Não adicionar locks redundantes só porque o plano os sugere.
4. Se reproduzido, adquirir locks em ordem consistente com a conclusão e revalidar status depois da trava. A regra de estados permitidos permanece no serviço/regra, SQL na infraestrutura.
5. Provar que ou a anulação entra antes da conclusão e esta valida o novo placar, ou a conclusão vence e a anulação posterior é recusada sem alterar evento/placar/créditos.
6. Cobrir duas anulações da mesma jogada, duas jogadas distintas e replay. Em erro de deadlock, garantir rollback integral e resposta recuperável sem confirmação falsa.

**Aceite:** teste concorrente determinístico descoberto pelo runner; nenhuma combinação de jogo concluído/pódio com placar alterado indevidamente. Executar nos motores disponíveis e registrar explicitamente a matriz restante.

## N12 — Investigar conflitos concorrentes no agendamento

Risco R02. Fontes: `JogoService::agendar`, `MysqliJogoRepository::localConflict/create`, `MysqliJogoGateway`, `MysqliAgendamentoBlocoRepository`, schedulers sequencial/em bloco, `JogosAndConflitosTest`, `AgendamentoBlocoTest`, `AgendamentoSequencialTest` e suporte de concorrência.

1. Reproduzir duas criações manuais no mesmo local/data/faixa que passam juntas pelo `localConflict`. Confirmar persistência final, não só códigos HTTP.
2. Repetir manual × lote, manual × edição de jogo e intervalos adjacentes que respeitam os dez minutos já considerados pelo código. Manter esse intervalo; não reinventar regra de calendário.
3. Se reproduzido, escolher protocolo de trava compartilhado pelos caminhos que disputam o mesmo local/data. Uma trava apenas na criação manual não cobre lote/update.
4. Relê-se o conflito dentro da seção protegida antes de gravar. Não confiar somente na transação: duas consultas sem trava comum podem ver ausência simultaneamente.
5. Reutilizar transação/savepoint existente e deixar SQL/travas na infraestrutura. Preservar regras de horários, equipes, modalidade, edição e rollback de partidas criadas.
6. Cobrir locais distintos sem bloqueio indevido, mesmo local em outro dia, intervalo inválido e falha durante a criação. Avaliar custo da trava antes de serializar o sistema inteiro.

**Aceite:** uma das criações conflitantes é recusada e não deixa jogo/partida órfãos; casos não conflitantes persistem. Se refutado, manter teste e explicação. Validar SQL nos dois motores quando disponível; não declarar os dois aprovados por uma execução local.

## N13 — Fechar validação e documentação

1. Executar `all` no ambiente isolado depois das correções; acrescentar visual se houve mudança de aparência e inspecionar diferenças antes de alterar snapshots.
2. Executar matriz pertinente MySQL/MariaDB e PHP declarada em CI, localmente quando possível. Registrar versão real. Não alterar workflow para ocultar falha nem apresentar CI configurado como CI executado.
3. Conferir raiz e subdiretório `/SGI` nos fluxos tocados, preservando `Url`/cliente HTTP. Não criar URLs de PHP físico nem endpoints procedurais.
4. Conferir navegador: inscrição válida/inválida/retry, ranking antes/depois da publicação, criação/anulação de ponto online/offline, reconexão, reentrada SPA, operador/edição trocados. Nenhuma limpeza de IndexedDB como preparo.
5. Atualizar `docs/architecture.md` e guias afetados com a política real. Corrigir menção à matriz visual Windows quando o workflow usa Linux e remover descrição obsoleta de URL antiga, confrontando rotas reais. Arquivar/referenciar o plano antigo como histórico, sem reabrir tarefas concluídas ou reconstruir `config/routes/compatibility.php` ausente.
6. Conferir builds, lockfiles, migrações e ausência de dados pessoais/segredos no diff. `public/assets` permanece gerado e não versionado. Não remover arquivos/logs que não pertençam à execução.
7. Atualizar STATUS: resultado por tarefa, arquivo/teste, falha reproduzida, comando e saída resumida, pendência real. Revisar `git diff --check` e diff final.

**Entrega:** lista de correções com evidências, riscos refutados ou corrigidos, comandos efetivamente executados, limitações e ponto exato de retomada. Não basta dizer “todos os testes passaram” sem distinguir unitário, JavaScript, HTTP/banco, navegador, visual e matriz externa.
