# Etapa 1 — Autorização por recurso e erros públicos

Achados: A1 e A10. Depende de T00–T01. Executar T02 a T06 em ordem.

## Matriz obrigatória

| Operador / entrada | Resultado esperado |
| --- | --- |
| Anônimo em mutação protegida | 401, nenhuma escrita |
| Aluno em mutação operacional | 403, nenhuma escrita |
| Mesário, recurso de A ativa | Operação válida conforme regra do endpoint |
| Mesário, recurso de B inativa | 403, nenhuma escrita |
| Mesário, não há edição ativa | 403, nenhuma escrita |
| ID de partida de B acompanhado de jogo de A | Rejeição; nunca mover a partida |
| Jogo de A com equipe/atleta de B | Rejeição para qualquer perfil |
| ID temporário resolvendo jogo de B | 403 para mesário; nenhuma criação auxiliar |
| Referência inexistente | Erro 404 ou código de validação já contratado; nunca sucesso de gravação |
| Admin/colaborador autorizado na ação | Manter acesso a edições históricas, exigindo relações consistentes |

Quando houver dois problemas, autorização vem antes de revelar detalhes do recurso. Na mesma edição autorizada, IDs discordantes retornam 422. Não mudar os envelopes legados (`status/mensagem` versus `success/message`) sem adaptar e testar os consumidores.

## T02 — Política única de acesso à edição

**Ler:** `AccessGuard.php`, `CompetitionAccess.php`, `MysqliInterclasseRepository.php`, `config/routes.php` e os testes de AccessGuard.

**Criar conforme README:** `ContextoOperador`, `EdicaoAccessPolicy`, `AcessoEdicaoNegadoException` e `tests/Unit/Modules/Acesso/EdicaoAccessPolicyTest.php`.

### Implementação

1. `ContextoOperador` recebe `int userId`, `int nivel`, `?int edicaoAtivaId`. Não acessa sessão.
2. A política recebe contexto e ID da edição real do recurso. Permite níveis 0/1; permite nível 2 somente com edição ativa não nula e igual; rejeita demais níveis no fluxo operacional.
3. Ausência de recurso é tratada pelo caso de uso antes de chamar a política; não permitir acesso apenas porque dois IDs são zero ou nulos.
4. A apresentação constrói o contexto a partir da sessão autenticada e da consulta de edição ativa já existente. Ignorar `id_usuario`, `nivel` e edição ativa enviados pelo cliente.
5. Manter a função atual de `CompetitionAccess` como adaptador de sessão/HTTP durante a migração. Acrescentar a criação do contexto sem espalhar leituras de sessão em Application.
6. Para operações transacionais, validar novamente o estado da edição dentro da unidade de trabalho usando a linha da edição do recurso. A leitura que autoriza deve permanecer válida até o commit; a ativação em T21 deve coordenar suas atualizações com essas travas.
7. Não adicionar um `if` permissivo no repositório para contornar a ausência de contexto. Atualizar explicitamente os consumidores do método alterado.

**Testar:** todos os perfis, IDs iguais/diferentes/nulos, sessão obsoleta de mesário e mudança da edição ativa entre login e operação. Os testes unitários da política não abrem banco.

**Concluída quando:** a política está testada e é possível montar o contexto sem dependência HTTP em Application.

## T03 — Aplicar a política à artilharia e às ocorrências

**Ler/editar:**

- `Competicoes/Presentation/Http/ArtilheiroController.php`, `Application/ArtilheiroService.php`, `Domain/ArtilheiroRepository.php`, `Infrastructure/MysqliArtilheiroRepository.php`, `MysqliArtilheiroQueries.php`.
- `Disciplina/Presentation/Http/OcorrenciaController.php`, `Application/OcorrenciaService.php`, `Domain/OcorrenciaRepository.php`, `Infrastructure/MysqliOcorrenciaRepository.php`, `MysqliOcorrenciaQueries.php`.
- `Sincronizacao/Presentation/Http/MutationAction.php`.

### Primeiro reproduzir

Adicionar `tests/Integration/MesarioResourceScopeTest.php` ao runner. Login de mesário com A ativa; enviar POST de artilharia com aluno/jogo de B. Confirmar que o código anterior grava; a correção deve retornar 403 sem aumentar a tabela.

### Implementação

1. Estender contratos com consultas pequenas para identificar edição do atleta, do jogo e da ocorrência existente. Implementar os joins na infraestrutura.
2. Resolver jogo temporário sem gravar dados indevidos. Se ainda não materializado, preservar o 409 atual, mantendo a operação na fila.
3. Na artilharia, validar edição real do jogo e do atleta; validar que o atleta faz parte de uma equipe participante da modalidade/jogo quando esse vínculo for exigido pelo fluxo atual. Não aceitar combinação de IDs de edições diferentes.
4. Em ocorrência sem jogo, usar a edição do aluno como recurso obrigatório. Ocorrências gerais são permitidas; não inventar obrigatoriedade de jogo.
5. Em ocorrência com jogo, validar aluno, jogo e turma informada; validar IDs estruturados e não confiar só nos marcadores `[JOGO:...]`/`[TURMA:...]` da descrição.
6. Em PUT, carregar primeiro a ocorrência persistida por `id_ocorrencia`, autorizar o alvo real e depois validar eventuais novos vínculos. Alterar somente `status_ocorrencia` também exige autorização.
7. Executar autorização/validação antes do INSERT/UPDATE e dentro da transação que protege a mutação. Em cartão vermelho automático por segundo amarelo, os dois inserts devem continuar atômicos.
8. Manter o mesmo identificador lógico de rota passado a `MutationAction`, por exemplo `artilheiro.post` e `ocorrencias.post`. Não invalidar respostas de reenvios antigos.
9. Encaminhar o acesso negado como 403; não deixá-lo cair em `catch Throwable` que transforme tudo em 500.

### Testes de aceitação

- POST e PUT de artilharia e ocorrência em A/B; ocorrência sem jogo; inativação de ocorrência antiga.
- IDs de aluno de B com jogo de A; aluno inexistente; jogo negativo não materializado.
- Operação válida de mesário, admin e colaborador conforme suas permissões.
- Dois reenvios com a mesma chave não duplicam gol/cartão; 403 não persiste resposta de sucesso.
- Antes/depois das tabelas envolvidas iguais nos casos recusados.

## T04 — Autorizar a partida persistida, sem aceitar IDs discordantes

**Ler/editar:** `PartidaController.php`, `PartidaService.php`, `PartidaRepository.php`, `MysqliPartidaRepository.php`, `MysqliPartidaGateway.php` e `PlacarAndArtilhariaTest.php`.

### Implementação

1. Para ID positivo, carregar a partida real e seus `jogos_id_jogo`/`equipes_id_equipe` antes de validar a edição.
2. Se o corpo contiver jogo/equipe diferentes, rejeitar. Um update de placar não é um comando de transferência de partida.
3. Para PUT somente com `id_partida` e `resultado_partida`, manter compatibilidade: inferir jogo/equipe do registro persistido.
4. No POST legado de resultado por partida, carregar o placar completo do jogo e substituir somente o valor da partida alvo antes de encaminhar ao lançamento do resultado. Não finalizar um jogo com uma lista artificial de apenas um participante se a regra exige os dois placares.
5. A conclusão deve passar pelo mesmo fluxo de A3/T15, não manter uma segunda implementação de premiação.
6. Para IDs temporários, caracterizar a resposta legada atual e os consumidores antes de alterá-la. Não enviar `mm_local_*` como ID numérico real. A materialização oficial continua pelo resultado/tag; T05 trata seu escopo.
7. Preservar campos de resposta esperados pelos consumidores existentes.

**Testes:** partida de B + jogo de A; partida/equipe discordantes dentro de A; PUT sem jogo no corpo; partida inexistente; finalização legada; nenhuma reassociação depois de rejeição. Consultar os FKs e placar originais após cada caso.

## T05 — Validar resolução de jogos temporários e sincronização

**Ler/editar:** `ResultadoController.php`, `MysqliPartidaGateway::resolveGame/launch`, `MysqliArtilheiroQueries::resolveGame`, `MysqliOcorrenciaQueries::resolveGame`, `ChaveamentoSyncController.php`, `MysqliChaveamentoSyncGateway.php`, `ChaveamentoRules.php`.

### Implementação

1. Separar localizar candidato de criar jogo. A procura não deve realizar INSERT antes de validar modalidade/edição/equipes.
2. ID positivo: carregar o jogo, autorizar sua edição real e conferir os participantes.
3. ID negativo: validar modalidade existente, edição autorizada e tag válida com o parser existente; localizar por modalidade + tag.
4. Se usar o fallback por equipes, restringir à modalidade/edição coerentes. Não escolher o jogo global mais recente de duas equipes sem conferir o contexto. Se houver mais de um candidato plausível e os dados antigos não desambiguarem, responder 409; manter na fila para revisão.
5. Antes de materializar, validar que todas as equipes informadas pertencem à modalidade e à edição; não permitir que `garantirPartidaEquipe` introduza participantes arbitrários.
6. Somente depois criar o jogo quando essa criação for parte do protocolo atual. Autorizar novamente o recurso resolvido antes de gravar o resultado.
7. Na sincronização em lote, validar todos os jogos/equipes antes da primeira escrita. Falha de um item deve reverter o lote inteiro, conforme a transação atual.
8. Não mudar tags, IDs negativos, nomes de rota idempotente nem corpos persistidos. As adaptações aceitam o formato antigo.
9. Os casos de uso extraídos em T22 devem receber esta política e resolução; não reconstruí-las posteriormente de outra maneira.

### Testes obrigatórios

| Caso | Esperado |
| --- | --- |
| Resultado `id_jogo=-1`, tag/modalidade/equipes de B | 403, zero INSERT/UPDATE |
| Resultado temporário válido em A | Jogo resolvido/criado e resultado confirmado |
| Modalidade A e equipes B | Erro, nenhuma criação auxiliar |
| Fallback ambíguo | 409 e fila preservada |
| Reenvio da mesma chave | Mesma resposta, mesmos IDs, nenhuma duplicação |
| Mesmo ID de mutação com outro corpo | 409 como atualmente |
| Lote com último item inválido | Nenhuma escrita do primeiro item confirmada |
| Torneio completo de sete jogos | Continua funcionando online e offline |

## T06 — Padronizar falhas internas sem quebrar envelopes

**Ler/editar:** `UsuarioController.php`, `ResultadoController.php`, `HistoricoTurmaController.php`, `ExceptionMiddleware.php`; procurar outros `getMessage()` em capturas amplas de `Presentation`.

### Implementação

1. Listar quais exceções representam validação, não encontrado, autorização e conflito. Reutilizar as classes específicas existentes.
2. Não interpretar todo `RuntimeException` como 404: `mysqli_sql_exception` também deriva dessa classe.
3. Capturar as exceções específicas conhecidas e mapear para 400/403/404/409/422 conforme o contrato. Falhas inesperadas de persistência devem retornar 500 com mensagem genérica.
4. Registrar detalhes somente no log. Não devolver SQL, constraint, nome de coluna, stack trace ou caminho local.
5. Preservar o envelope de cada endpoint; evitar renomeação global de `mensagem` para `message` nesta correção.
6. Não capturar erro e prosseguir até o commit. Deixar o dono da transação efetuar rollback.

**Testar:** erro de validação esperado; FK inválida; exceção de persistência simulada por contrato; autorização negada; retorno seguro e ausência de escrita parcial. Não desligar constraints para simular sucesso.

### Fechamento da etapa

Executar os checks exigidos no README, os novos testes HTTP e os fluxos de navegador de autenticação e torneio. Registrar T02–T06 individualmente no STATUS, incluindo os cenários positivos preservados.
