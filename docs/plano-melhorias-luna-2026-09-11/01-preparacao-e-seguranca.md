# N00–N07 — Preparação e correções prioritárias

Os caminhos abaixo são relativos à raiz. Novos nomes de testes/exceções são propostas: procurar equivalentes antes de criar.

## N00 — Estabelecer base de comparação

1. Ler `AGENTS.md`, `README.md`, `docs/testing.md` e o STATUS deste plano; registrar commit e `git status --short`.
2. Verificar PHP/extensões, Composer, Node, dependências, disponibilidade de banco de teste e Docker sem exibir segredos. Não instalar novamente se as dependências existentes bastam.
3. Executar `tools/test-local.ps1 -Suite all -DatabaseBackend local` conforme guia e guardar log. Espera-se que o checkout auditado falhe em CS Fixer; isso é estado inicial conhecido, não aprovação.
4. Se banco indisponível, executar qualidade/JavaScript independentes. Não inventar credenciais nem extrair `.env` para resets. Registrar o requisito ausente e continuar as tarefas de teste unitário/documentação possíveis.
5. Conferir caminhos e APIs antes de escrever testes. Usar fixtures A/B de `tests/Support/AuditFixtures.php`; ampliar somente se necessário.

**Aceite:** commit, comandos, resultados e recursos de teste identificados no STATUS; diferenças do usuário preservadas. Nenhuma alegação de banco/navegador validado sem execução.

## N01 — Tornar os checks e testes confiáveis

Fontes: `.php-cs-fixer.dist.php`, `composer.json`, `phpunit.xml`, `tools/test-local.ps1`, `tests/Unit/Modules/Competicoes/ResultadoServiceTest.php`, `IndividualRankingServiceTest.php`, `tests/Unit/Presentation/Web/PageControllerTest.php`.

### N01a — Estilo

1. Inspecionar diff do CS Fixer e `git ls-files --eol`. Separar mudanças de EOL de mudanças reais de estilo.
2. Definir EOL explícito para fontes pertinentes, por exemplo com `.gitattributes`, e alinhar o formatter. Evitar regra global que altere SQL histórico, arquivos binários ou assets gerados.
3. Aplicar normalização apenas aos arquivos de código necessários e reexecutar CS Fixer. Não usar `git add --renormalize .` como atalho.
4. Conferir hashes/conteúdo de `database/migrations` antes/depois e garantir que permaneçam intocados. Qualquer problema de checksum multiplataforma exige decisão própria e ensaio de recuperação, não atualização silenciosa de hashes gravados.

### N01b — Testes

1. Reescrever testes que usam `expectException` seguido de asserções inalcançáveis. Usar `try/catch` com `fail()` quando não houver exceção, então verificar o estado do fake; ou expectativas `never()` verificadas pelo PHPUnit.
2. Demonstrar que a nova regressão detecta uma escrita antes da exceção usando um fake sentinela apropriado; não alterar produção para fabricar a falha.
3. Trocar os dois metadados depreciados por atributos PHPUnit equivalentes. Preservar todos os datasets.
4. Buscar padrão semelhante nos outros testes e corrigir somente os casos realmente inalcançáveis.

### N01c — Executor

1. Exercitar falha de qualidade seguida de erro do cliente SQL no cleanup em ambiente sintético/controlado.
2. Garantir que o erro original e o exit code sejam preservados; falha de limpeza deve ser reportada, sem impedir restauração de ambiente/liberação de lock/limpeza dos recursos próprios.
3. Evitar tentativa de limpeza sem pré-requisitos; verificar identidade do recurso antes de remover. Criar teste automatizado do executor com stubs ou no suporte existente, sem tocar banco normal.

**Validação:** qualidade, PHPUnit com `--display-phpunit-deprecations`, teste do executor e `all` no ambiente disponível. Revisar separadamente o diff mecânico. **Aceite:** zero depreciações identificadas, pós-condições verificadas e falhas de cleanup sem ocultar causa original.

## N02 — Aplicar escopo por edição aos pontos

Achado C01. Fontes: `src/Modules/Competicoes/Presentation/Http/PontoController.php`, `Application/PontoService.php`, `Domain/PontoRepository.php`, `Infrastructure/MysqliPontoRepository.php`; `CompetitionAccess`, `EdicaoAccessPolicy`; composição em `config/routes.php`.

1. Acrescentar cenário em `tests/Integration/MesarioResourceScopeTest.php` ou teste novo registrado no runner: A ativa, B diferente, jogos/partidas/pontos/equipes sintéticos. Para testar POST em B, preparar atleta ativo elegível em B sem depender de efeitos automáticos de ativação.
2. Cobrir GET de pontos, GET `acao=atletas` com jogo e sem jogo, POST em B e PUT do ponto de B; confirmar negativa por escopo, não por atleta ausente/status errado. Nenhuma gravação e nenhuma confirmação idempotente de sucesso.
3. Expor consulta de contexto via contrato, retornando edição real a partir de jogo, equipe ou ponto. PUT usa ponto persistido; GET sem jogo usa equipe. Rejeitar combinações de IDs divergentes.
4. Aplicar a política existente; manter SQL fora do controlador. Para mutações, garantir validação do contexto dentro do trecho transacional adequado, sem criar janela de autorização baseada só no corpo.
5. Confirmar permissões existentes de 0/1; nível 3 negado; mesário sem edição ativa negado. Manter recurso inexistente distinto de recurso proibido conforme envelope adotado.
6. Testar replay de sucesso autorizado sem duplicar, tentativa com outro operador e mudança da edição ativa. Não apagar mutação pendente que passe a ser recusada.

**Aceite:** HTTP 403 em B, fluxo equivalente em A aprovado, placar/eventos intocados nos casos proibidos. Unitários da regra/contexto, integração real e navegador `point-athlete-required` ou cenário equivalente encontrado no inventário, incluindo offline/reentrada. Não assumir o nome de spec: localizar pelo comportamento.

## N03 — Respeitar publicação do ranking

Achado C02. Fontes: `Resultados/Presentation/Http/RankingController.php`, `Application/RankingService.php`, `Infrastructure/MysqliRankingRepository.php`; `Eventos/Domain/EdicaoConsulta.php`, `Infrastructure/MysqliEdicaoConsultaRepository.php`; `HistoricoTurmaController.php`; `resources/js/pages/aluno/ranking.js`.

1. Criar edição B encerrada e não publicada e aluno ativo com termos aceitos. Fazer GET direto do ranking de B, sem depender do dropdown da tela.
2. Cobrir B publicada, A ativa, ID inexistente, ausência de ID e administrador/colaborador. Asserções verificam também ausência dos dados no corpo recusado.
3. Exigir encerramento E publicação para aluno, reutilizando o contrato `EdicaoConsulta`. Não usar apenas status nem confiar no filtro opcional enviado pelo cliente.
4. Preservar leitura administrativa e autorização mais restrita do histórico da turma. Não liberar histórico alheio para uniformizar telas.
5. Alinhar a mensagem do frontend ao comportamento quando necessário; mudança de texto não exige redesign.

**Aceite:** não publicado → 403; publicado e encerrado → 200; ativo → 403, mesmo que campo de publicação antigo permaneça preenchido. Testar reativação/publicação sem inferir visibilidade do status sozinho. Sem migração nova necessária.

## N04 — Corrigir autoria dos eventos offline

Achado C03. Fontes: `ResultadoService`, `ResultadoController`, `PontoRepository`, `MysqliPontoRepository::persistirPontosOffline/inserirHistoricoAnulado/anular`, `PontoService`, `MutationIdentity`, `resources/js/pages/competicoes/placar.js` e projeções offline.

1. Unitário: operador A sincroniza ponto contendo `registrado_por=B`, zero, NULL e campo ausente. Inspecionar comando destinado ao repositório.
2. Para eventos novos, usar exclusivamente o operador autenticado. Recomenda-se interpretar o campo cliente como não autoritativo, sem reescrever body/chave da fila antiga. Alternativa de rejeição só se não quebrar compatibilidade comprovada; registrar decisão.
3. Para ponto persistido, preservar `registrado_por` original. Ao anular, gravar `anulado_por` do operador autenticado atual. Não confundir autor original com autor da anulação.
4. Tornar o operador um argumento explícito/confiável do caminho offline, se necessário. Não aceitar autor zero no fluxo autenticado para contornar FK.
5. Integração: consultar colunas de auditoria após criação, anulação por outro operador permitido, replay e falha transacional. Provar que ponto/placar/autor/resposta idempotente são atômicos.
6. Navegador: criar e anular ponto offline, reconectar; fila esvaziada só com confirmação, sem duplicação. Preservar eventos temporários e bodies legados.

**Aceite:** nenhuma autoria falsa persistida; ator de anulação correto; replay preserva histórico. Não tentar corrigir autores históricos por suposição.

## N05 — Validar elegibilidade de inscrição no servidor

Achado C04. Fontes: `Participantes/Application/InscricaoService.php`, `Domain/InscricaoRules.php`, `Domain/InscricaoRepository.php`, `Infrastructure/MysqliInscricaoRepository.php`; `Competicoes/Domain/EquipePadraoRepository.php`; `resources/js/pages/aluno/modalidade.js`.

1. Reproduzir POST direto com aluno MASC para modalidade FEM da mesma turma/edição e modalidade de categoria diferente. A fixture precisa ter a equipe correspondente para que o defeito não seja mascarado por ID inexistente.
2. Acrescentar regra pura de compatibilidade, sem HTTP/SQL: MASC/MASC, FEM/FEM e qualquer gênero permitido/MISTO; categoria deve corresponder à turma. Fonte da verdade é banco.
3. Carregar contexto necessário sob as travas existentes. Revalidar status/edição/turma/modalidade e elegibilidade antes de criar equipe padrão/vínculo. Preservar ordem de locks e máximo de três modalidades/capacidade.
4. Definir explicitamente o tratamento de lote misto: preservar a semântica parcial existente, contabilizando erros por modalidade, a menos que teste/contrato atual exija atomicidade do lote. Uma modalidade recusada nunca pode gerar vínculo. Erro interno sempre desfaz a transação.
5. Cobrir gênero MISTO, categoria correta, usuário/equipe/modalidade inativos, outra turma/edição, falta de termos, CSRF e capacidade. Não confiar nos dados do contexto renderizado no navegador.

**Validação:** unitários da regra, `InscricaoModalidadesTest`, `ConcurrentInvariantsTest`, portal no navegador e `all`. **Aceite:** POST direto não contorna filtro visual; casos permitidos continuam funcionando, inclusive último lugar disponível em concorrência.

## N06 — Fazer inscrição repetida responder corretamente

Achado C05. Usar fontes/testes de N05.

1. Registrar inscrição e repetir os mesmos IDs; confirmar que atualmente o vínculo permanece, mas a resposta não reconhece o existente.
2. Computar interseção entre modalidades solicitadas válidas e já inscritas antes de remover as existentes. Contar uma modalidade uma vez mesmo se vierem IDs repetidos/equipes equivalentes.
3. Retornar sucesso quando ao menos uma inscrição solicitada já estiver satisfeita ou uma nova for criada; informar `insercoes` e `ja_existentes` corretamente, com erros das recusadas. Não introduzir campo novo sem necessidade.
4. Cobrir só existentes, só novas, mistura, lista repetida e tentativa de quarta modalidade. Repetição não deve disputar outra vaga nem criar equipe extra.

**Aceite:** após perda simulada da primeira resposta, retry confirma o estado sem duplicar linha e sem erro enganoso. Unitários adequados e integração obrigatória, pois a contagem depende do SQL.

## N07 — Separar erros de negócio de erros internos

Achado C06. Fontes: `InscricaoController`, `MysqliInscricaoRepository`, `ImportacaoTurmaService`, `MysqliImportacaoTurmaRepository`, `PdfAlunoImporter`, `ImportacaoTurmaController`, `MysqliEquipePadraoRepository` e consumidores das mensagens de redistribuição.

1. Reproduzir em controlador uma exceção SQL com marcador sintético; verificar status 400 e vazamento antes da correção. Para importação, simular retorno de erro interno no repositório/reader sem usar documento real.
2. Criar/reutilizar exceção específica para recusa de inscrição. Converter regras conhecidas em mensagem pública e 4xx. `RuntimeException` genérica/SQL/parser deve ser 5xx seguro, com detalhe apenas no log.
3. Remover a propagação por arrays de erro interno no importador. Preservar avisos de dados inválidos esperados e a transação/rollback da importação.
4. Revisar o retorno da redistribuição, distinguindo regra de capacidade de falha SQL. Não anunciar todo `getMessage()` como inseguro: mensagens de exceções de negócio são permitidas.
5. Regressões garantem ausência de marcador SQL, caminho local e stack no corpo; log contém detalhe sintético e operação não gera sucesso/commit parcial. Testar cada envelope consumido pela UI.

**Aceite:** erro técnico não vira erro de entrada nem resposta HTTP 200 de sucesso. Ampliar `tests/Integration/ExceptionEnvelopeTest.php` e unitários das fronteiras; testar upload válido/inválido e inscrição válida após a mudança.
