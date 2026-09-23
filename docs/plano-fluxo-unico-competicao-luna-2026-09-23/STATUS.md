# Progresso — fluxo único de competição

Atualizado em 23/09/2026, America/Sao_Paulo. **Estado: fluxo implementado; bateria MariaDB aprovada; validação ponta a ponta ainda parcial.** Referência inicial: `eaef642d37c4f0f182cc090793947a1aab986921`. O checkout contém alterações preexistentes de outras tarefas, mantidas fora dos commits deste trabalho.

## Etapas

| Etapa | Estado observado | Evidência / pendência |
| --- | --- | --- |
| U00 | Validada | Baseline focal PHP/JS e mapa inicial registrados abaixo. |
| U01 | Validada | Integração cobre calendário com equipes sem alunos, consulta da agenda antes da inscrição e conflito planejado. |
| U02 | Implementada; validação parcial | Migração e vínculo nó/jogo incluídos; repetição da migração exercitada no MariaDB. Falta cenário explícito de atualização com jogos legados ambíguos e colisões entre dados existentes. |
| U03 | Implementada; validação parcial | Liberação cria jogos em transação, testa mínimo, rollback tardio e repetição idempotente. Concorrência simultânea não foi exercitada. |
| U04 | Implementada; validação parcial | Resultado e BYE avançam pela árvore e preservam o compromisso da final; falta cobertura de seis entradas e correção depois de descendente operado. |
| U05 | Implementada; validação parcial | Tags planejadas são aceitas no engine/sync e o navegador opera jogo liberado offline; falta a jornada completa de final offline até reconciliação. |
| U06 | Implementada; validação parcial | Interface oferece liberação e mostra a árvore publicada; falta E2E real único, conduzido pela interface desde equipes vazias até a final. |
| U07 | Validada nos caminhos cobertos | Geração antiga recusa com código estável; sincronização legada e escritores administrativos incompatíveis são bloqueados por regressões de integração. |
| U08 | Parcial | Bateria passou, mas V01–V20 não está integralmente coberta. Ver a matriz rastreada abaixo; V20 permanece pendente. |
| U09 | Validada para MariaDB | Qualidade, integração, navegador e visual passaram na execução `20260923_165945_261e02`. MySQL não foi executado nesta validação, conforme a orientação atual do usuário. |

## Decisões implementadas

- Calendário é a fonte de confrontos; publicação acontece com equipes vazias.
- “Liberar competição” cria os jogos já resolvidos em lote atômico. Resultados disponibilizam fases seguintes na mesma árvore.
- Chaveamento é visualização e operação; geração inicial independente será retirada também do servidor.
- O vínculo persistente entre nó publicado e jogo físico usa uma migração nova; a liberação é idempotente e não troca a árvore após começar a operação.
- Resultados e BYEs avançam pela árvore `PL:` publicada. Filas existentes continuam compatíveis com identificadores `MM:` legados.
- Não há autorização de push, merge, deploy ou reset de base de trabalho.

## Baseline U00 — 23/09/2026, America/Sao_Paulo

HEAD conferido: `eaef642d37c4f0f182cc090793947a1aab986921`. `git status --short` mostrou alterações preexistentes em AGENTS, executores, Dockerfiles, README, testes de navegador e documentos, além de artefatos; foram preservadas. As suítes focais foram executadas antes de editar código:

| Comando | Resultado |
| --- | --- |
| `php vendor/bin/phpunit --configuration phpunit.xml --filter 'Cronograma|Chaveamento|Resultado|Inscricao'` | exit 0; 69 testes, 260 assertions, uma depreciação PHPUnit |
| `npm test` | exit 0; 130 testes JS aprovados |

Mapa observado: `/api/v1/cronograma` → `CronogramaController` → `CronogramaService` → `MysqliCronogramaRepository`; `releaseOperation` valida mínimos e só muda a flag; `materializeNode` cria um nó físico separadamente. `/api/v1/chaveamentos` → `ChaveamentoController` → `ChaveamentoService` → `MysqliChaveamentoManagement::createBracket` sorteia equipes com elenco e produz tags `MM:`; `MysqliPartidaGateway` chama avanço MM depois do resultado. O planner salva nós `PL:`; `plannedNodeParticipants` consulta origens/resultados, mas o avanço online e `chaveamento-engine.js` não estão ligados à identidade `PL:`. A tela chaveamento ainda chama o POST de geração; não foi localizado uso de UI para `materializar_no`. O portal do aluno consulta equipes planejadas antes da inscrição.

Arquivos da área de código não estavam modificados no início. `vendor/bin/phpunit` existe. Docker respondeu acesso negado ao pipe `npipe:////./pipe/docker_engine` durante a inspeção anterior. Não há baseline SQL/HTTP/browser. Os logs das duas suítes focais são a saída de terminal desta execução; elas não produziram artefatos dedicados.

## Evidências deste pacote

As aprovações históricas dos outros planos não aprovam U00–U09. Os estados acima se referem somente a esta implementação.

Validação documental em 23/09/2026 às 10:26 BRT: os links relativos e os caminhos completos citados nos sete arquivos foram conferidos por leitura e `Test-Path`; todos existem. Flags dos runners, caminho `vendor/bin/phpunit` e atalho `test:offline-queue` foram conferidos no projeto. `git diff --check` terminou com exit code 0, com avisos de conversão CRLF/LF em arquivos preexistentes. Como o pacote é novo e ainda não rastreado, seus sete arquivos também passaram pela checagem `git diff --no-index --check -- NUL <arquivo>` sem diagnóstico de whitespace; exit code 1 nesse modo indica diferença em relação ao arquivo vazio, não falha funcional. Nenhum arquivo preexistente foi editado para produzir o plano.

## Implementação e validação final

O fluxo principal foi implementado: o cronograma publicado é preparado com equipes vazias; estudantes consultam compromissos previstos; após encerrar inscrições, a ação **Liberar competição** valida os elencos e cria os jogos iniciais em transação; o chaveamento passa a exibir a mesma árvore e os resultados avançam os nós publicados. O endpoint de geração independente foi desativado com resposta estável. A implementação também associa jogos à identidade dos nós publicados e mantém os formatos legados necessários à sincronização já persistida.

Validação final em container descartável, somente MariaDB, conforme pedido:

| Campo | Resultado |
| --- | --- |
| Comando | `powershell -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mariadb -IncludeVisual` |
| Execução aprovada | `20260923_165945_261e02` |
| Data/hora | 23/09/2026 16:59:45–17:10:34 UTC (13:59:45–14:10:34 America/Sao_Paulo) |
| Ambiente | Docker Compose descartável; PHP 8.4.25, MariaDB 10.11; URL interna da suíte `http://localhost/SGI/`; containers removidos ao final |
| Qualidade | Exit 0; PHPUnit 389 testes / 2.684 assertions (1 depreciação PHPUnit), lint PHP e JavaScript 132/132 |
| Integração HTTP/banco | Exit 0; 1.030/1.030 assertions |
| Navegador | Exit 0; 169/169 testes, sem retry na execução aprovada |
| Contrato visual | Exit 0; 2/2 snapshots aprovados |
| Resultado do executor | Exit 0, `status: passed` no manifesto |
| Manifesto e logs | `test-results/docker-20260923_165945_261e02/run-manifest.json`, `integration-timings.json`, `docker-compose.log`, `browser-playwright.json` e `visual-playwright.json` |
| Evidência de navegador | `tests/browser/test-results/docker-20260923_165945_261e02/browser/` e `tests/browser/test-results/docker-20260923_165945_261e02/visual/` |

A tentativa completa imediatamente anterior (`20260923_164812_067017`) teve um teste de modal de turma instável no primeiro intento e aprovado no retry; por isso o runner marcou aquela execução como falha. A execução aprovada acima repetiu a bateria completa e passou sem retries. Uma tentativa MySQL anterior à orientação atual identificou ordenação incompatível com `DISTINCT`; a consulta foi ajustada para ordenar pelos aliases selecionados e a bateria completa foi repetida no MariaDB. Nenhum teste MySQL foi executado após a orientação de testar apenas MariaDB. CI remoto, deploy, push e merge não foram executados.

## Rastreamento da matriz V01–V20

“Coberto” indica que a regressão descrita está exercitada de forma direta. “Parcial” indica que há cobertura relacionada, mas falta ao menos uma condição da matriz. A bateria verde não transforma cenários parciais em aprovados.

| ID | Estado | Evidência e limite conhecido |
| --- | --- | --- |
| V01 | Parcial | `CronogramaPlanejadoTest`: equipe sem alunos, publicação e preview de árvore de três/quatro equipes; falta gerar/publicar quatro equipes no navegador de ponta a ponta. |
| V02 | Parcial | `CronogramaPlanejadoTest`: agenda publicada consultada antes do vínculo ao elenco; falta validar a mesma jornada no portal do aluno e todo isolamento de escopo pela interface. |
| V03 | Parcial | Integração recusa conflito planejado entre modalidades; regras gerais de inscrição têm suíte própria, mas não toda a combinação capacidade/janela/final condicional desta matriz. |
| V04 | Parcial | Publicação incompleta e conflito tardio de liberação são recusados; corrida simultânea de reserva não foi testada. |
| V05 | Parcial | Elenco abaixo do mínimo e materialização antes da liberação são recusados; revisão antiga e concorrência não cobertas integralmente. |
| V06 | Parcial | Liberação cria jogos iniciais ligados aos nós e mantém fases futuras previstas; o browser helper opera jogo canônico, mas não percorre a liberação pela interface administrativa. |
| V07 | Coberto | `CronogramaPlanejadoTest`: falha após criação anterior reverte jogos, vínculos e flag de liberação. |
| V08 | Parcial | Repetir liberação é idempotente; timeout e requisições concorrentes não foram exercitados neste caminho. |
| V09 | Parcial | Resultado e BYE avançam para a final com o compromisso publicado; falta finalizar o torneio com duas semifinais reais e conferir toda a pontuação nesse fluxo. |
| V10 | Parcial | Topologia de três equipes com BYE e preview de quatro equipes; seis equipes e BYE intermediário não cobertos. |
| V11 | Parcial | Retry de liberação não duplica jogos; correção de resultado após descendente já operado não foi exercitada. |
| V12 | Parcial | Suítes existentes de modalidade individual/ranking continuam verdes; falta regressão direta de liberação e ranking individual criada pelo novo planejamento. |
| V13 | Coberto nos caminhos alterados | `MataMataEdgeCasesTest` verifica código estável da geração antiga; integração também verifica bloqueio da sincronização legada após publicação. |
| V14 | Parcial | Controller testa a recusa do endpoint antigo e as suítes RBAC/CSRF gerais passaram; matriz completa de papéis e liberação em outra edição não foi criada para o novo caso. |
| V15 | Parcial | Engine aceita identidade `PL:` e navegador opera jogo liberado sem rede; final offline não foi reconciliada e comparada ponta a ponta. |
| V16 | Parcial | Suíte IndexedDB existente passou; a cobertura de confirmação inválida, ordem e isolamento não foi repetida com final `PL:` do novo fluxo. |
| V17 | Parcial | Revisão depois da liberação é bloqueada e preserva a árvore; filas pendentes ao mudar revisão não foram percorridas. |
| V18 | Parcial | Migração e repetição foram exercitadas no MariaDB; cenário de atualização com dados legados ambíguos não foi validado, e MySQL ficou fora desta execução a pedido do usuário. |
| V19 | Parcial | Bateria browser/visual aprovada e telas responsivas existentes cobertas; falta auditoria focada de foco/teclado/raiz/subdiretório para a nova jornada completa. |
| V20 | Não coberto | Falta E2E real, sem chamadas manuais, desde zero vínculos até resultado final. |

Próxima ação recomendada: completar primeiro V18 (migração com dados legados), V09–V11 (avanço/correção e seis entradas), V15–V17 (reconciliação offline/revisão) e V20 (jornada real pela interface). A validação MariaDB acima é integral para a bateria automatizada existente, mas não substitui esses cenários de aceite ainda ausentes.

## Registro obrigatório por etapa

Copiar e preencher, sem apagar falhas anteriores:

```text
Etapa / estado:
Data/hora/fuso e responsável:
HEAD e alterações preexistentes preservadas:
Contratos / decisões / arquivos alterados:
Teste que reproduz a falha e resultado antes:
Comandos exatos e exit codes depois:
Ambiente / URL / run ID:
Esperado / observado:
Logs / manifest / trace / screenshots existentes:
IDs V01–V20 cobertos e nomes dos testes:
Limitações / falhas preexistentes / pendências:
Próxima ação e dependências:
```

Uma etapa só muda para validada com evidência do aceite. Se já estiver implementada no checkout encontrado, demonstrar o comportamento e registrar em vez de refazer. Resultado parcial fica parcial; suíte bloqueada fica bloqueada. Retomar a primeira etapa incompleta após reler este arquivo.
