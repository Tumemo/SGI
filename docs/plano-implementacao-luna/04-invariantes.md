# Etapa 4 — Concorrência, limites e edição ativa

Achados A7/A9. Depende de T17. Executar T18–T21. T13 deve usar antecipadamente a técnica de T18 para comprovar o estorno; o objetivo desta etapa é consolidar e reutilizar esse suporte.

## Ordem de travas

Escolher uma ordem única para os recursos que participam da mesma operação. Para o SGI, usar: edição → usuário, quando necessário → modalidades em ordem de ID → jogos → turmas → históricos/créditos. Se uma tabela não participa, pular essa tabela, nunca inverter as restantes.

Uma trava curta da linha da edição é aceitável como primeiro mecanismo simples de serialização das mutações dessa edição. Ela sacrifica paralelismo para preservar consistência neste monólito escolar; não manter essa trava durante parsing de PDF, chamadas de rede ou espera do usuário. Não trocar por uma solução mais complexa sem uma necessidade medida.

Toda transação deve finalizar em `finally`/tratamento equivalente; a conexão de outra tarefa não pode ficar esperando indefinidamente. Travas nomeadas precisam ser liberadas explicitamente; commit não libera `GET_LOCK`.

## T18 — Criar testes concorrentes que realmente concorram

**Ler:** `tests/Integration/AtomicMutationTest.php`, `tests/Support/MutationWorker.php`, `TestDatabase.php`, `Transaction.php`.

**Criar:** `tests/Support/ConcurrentScenarioWorker.php`, helper de coordenação e `tests/Integration/ConcurrentInvariantsTest.php`; registrar no runner.

### Implementação do teste

1. Executar cada worker como processo PHP distinto usando `PHP_BINARY` e `proc_open` com argumentos estruturados; cada um abre sua própria conexão ao banco de teste.
2. Não usar somente duas requisições ao mesmo servidor PHP embutido de um worker: esse servidor pode serializá-las e esconder a falha.
3. O processo pai cria as fixtures confirmadas. Cada worker anuncia “pronto” por pipe/arquivo exclusivo do teste; o pai libera o início dos dois.
4. Para reproduzir a janela de corrida, o pai pode manter uma trava de linha até ambos os workers alcançarem uma espera de banco. Implementar a observação de espera no helper de teste de acordo com MySQL/MariaDB, com prazo máximo e diagnóstico; não usar pausa fixa como única prova de concorrência.
5. Estorno: travar a turma no pai. O código antigo consegue ler histórico ativo e depois bloqueia ao descontar; o corrigido deve reler/validar sob a ordem de trava antes de descontar.
6. Inscrição: travar o aluno no pai. O código antigo pode ler as inscrições antes de bloquear em uma escrita/FK; o corrigido deve obter a trava do aluno antes de consultar o limite.
7. Equipes: travar modalidade/turma usada pela criação; testar duas criações quando resta somente uma vaga.
8. Liberar a trava do pai após comprovar o estado esperado ou abortar com erro claro se o teste não conseguiu montar a concorrência. Sempre liberar em `finally`.
9. Coletar stdout, stderr e exit code de ambos. Timeout/falha de worker falha o teste; não contar como operação legitimamente recusada.
10. Consultar valores finais em uma nova leitura/ conexão após os commits; evitar snapshot antigo da transação do coordenador.
11. Helpers de coordenação ficam em `tests/`; não criar hooks HTTP públicos, bypass de CSRF ou sleeps de produção.

### Aceitação

| Cenário | Resultado final |
| --- | --- |
| Dois estornos do mesmo histórico | Uma remoção efetiva; quantidade/pontos descontados uma vez |
| Duas novas modalidades para aluno já em duas | No máximo três modalidades; uma solicitação deve ser recusada ou não incluir nova modalidade |
| Duas equipes para última vaga | Contagem não ultrapassa o limite |
| Mesma chave de mutação repetida | Teste existente continua retornando um único registro |

O resultado funcional importa mais que qual worker venceu. Não exigir que o primeiro processo criado seja o vencedor.

## T19 — Proteger limite de inscrições por aluno e capacidade

**Ler/editar:** `Participantes/Infrastructure/MysqliInscricaoRepository.php`, `Application/InscricaoService.php`, `Domain/InscricaoRepository.php`, `Competicoes/Infrastructure/MysqliEquipePadraoRepository.php`.

### Implementação

1. Abrir transação antes de ler aluno, inscrições existentes e capacidade. Não calcular `$already` fora dela.
2. Travar a edição e o aluno real, validar que pertence à edição/turma permitida.
3. Normalizar e ordenar IDs de modalidade/equipe para aquisição determinística das travas.
4. Contar modalidades distintas já inscritas, não quantidade de equipes. Calcular união com as novas modalidades solicitadas sob a mesma trava do aluno.
5. Rejeitar se a união passar de três. Inscrição repetida na mesma modalidade não consome outra vaga.
6. A capacidade também precisa de uma linha estável a travar mesmo quando ainda não há inscritos. Não depender exclusivamente de `COUNT(...) FOR UPDATE` sobre um conjunto vazio. A trava de modalidade/turma deve existir antes da contagem.
7. Conferir coerência de turma, edição, status e regras de gênero/capacidade já existentes. Não reduzir as validações a `count($ids)<=3`.
8. A resolução/criação de equipe padrão usada pela inscrição participa da mesma transação e das mesmas regras de limite de T20. Procurar todas as chamadas de `buscarOuCriarEquipePadrao`.
9. Preservar o contrato atual de resposta, incluindo erros de itens e indicação de inscrições existentes. Se corrigir uma inconsistência de “nenhuma nova inscrição”, acrescentar teste que diferencie repetição válida de erro real.
10. Fechar a transação somente depois de confirmar os vínculos. Não consultar a contagem autoritativa depois do commit para decidir se era permitido.

### Testes

- Aluno com2, solicita C/D simultaneamente → no máximo3.
- Duas equipes da mesma modalidade não contam como duas modalidades.
- Repetir inscrição já existente não duplica vínculo.
- Última vaga disputada por dois alunos não excede capacidade.
- Equipe de outra turma/edição é rejeitada.
- Falha ao criar equipe/vínculo reverte toda a operação correspondente.

## T20 — Validar limite de equipes independentemente do nome

**Ler/editar:** `EquipeService.php`, `EquipeRepository.php`, `MysqliEquipeRepository::create/generateName/update`, `MysqliEquipePadraoRepository.php`, `MysqliEquipeGateway.php`.

### Implementação

1. Extrair o teste de limite de `generateName` para regra explícita. Gerar nome não deve decidir se é permitido criar equipe.
2. Toda criação, com nome automático ou informado, carrega modalidade/turma, valida vínculo e limite sob transação/trava.
3. Preservar o significado atual de `max_equipes`: limite por turma/modalidade; null/zero segue a convenção de ilimitado já utilizada.
4. Contar as equipes conforme o critério de status existente, documentando-o no teste. Não contar apenas equipes com nome automático.
5. Submeter também geração coletiva e criação de equipe padrão ao mesmo mecanismo. Não corrigir apenas a rota de cadastro manual.
6. Se UPDATE puder mover/reativar equipe em outra turma/modalidade, validar capacidade do destino na mesma transação. A equipe já pertencente ao destino não deve ser contada duas vezes.
7. Usar a regra de número/nome após garantir a capacidade. Concorrência deve produzir nomes coerentes sem permitir duas criações além do limite.
8. Não criar índice único só sobre nome para simular limite numérico; são invariantes distintas.

### Testes

Limite1, já há uma equipe: criar outra com `nome_equipe='Equipe manual'` é recusado, assim como com nome null. Testar limite2 com uma vaga concorrente, ilimitado, reativação, geração padrão e falha transacional.

## T21 — Um único caso de uso para ativar/criar edição

**Ler/editar:** `Eventos/Application/EdicaoService.php`, `Domain/EdicaoRepository.php`, `Infrastructure/MysqliEdicaoRepository.php`, `Acesso/Presentation/Http/UsuarioController.php`, `MysqliUsuarioGateway::setEditionStatus`.

### Implementação

1. Criar ou explicitar operação de ativar/desativar em `EdicaoService`. Reutilizar o serviço nas duas rotas existentes; não manter UPDATE independente no módulo Acesso.
2. Validar status permitido, ID positivo e existência do alvo **antes de desativar** qualquer outra edição.
3. Serializar criar/ativar/desativar com uma trava nomeada estável por banco, por exemplo hash de `DATABASE() + ':edicao-ativa'`, adquirida antes de abrir a transação. A mesma trava deve ser usada nos três caminhos.
4. Dentro da transação, travar linhas de edições envolvidas em ordem crescente; desativar anteriores e ativar alvo. A criação também passa pelo mecanismo para não haver duas edições ativas simultaneamente.
5. Liberar a trava nomeada em `finally`, tanto em sucesso como em erro. Timeout retorna falha transitória clara e nenhuma alteração parcial.
6. Preservar o trigger de sincronização de status dos alunos; verificar efeitos em alunos das edições desativada/ativada.
7. Admin/colaborador mantêm as permissões atuais por ação. A possibilidade de mesário operar continua restrita ao estado efetivamente ativo, conforme etapa1.
8. Consultas de sessão podem manter cache informativo, mas a autorização de escrita não deve confiar apenas nele.
9. Desativar a única edição pode resultar em nenhuma ativa se essa operação já for permitida. A invariante é **no máximo uma**, não criar outra edição automaticamente.

### Testes

- Ativar B pela rota de usuários com A ativa → só B ativa.
- Ativar A pela rota de edições → só A ativa.
- ID inexistente → erro e A continua ativa.
- Ativar A/B concorrentemente → exatamente uma ativa ao final, sem erro SQL exposto.
- Criar duas edições concorrentemente → no máximo uma ativa, fixtures de ambas íntegras.
- Falha intermediária restaura estados; trava é liberada para próxima operação.
- Alunos seguem status correto e mesário com sessão antiga usa edição atual.

### Fechamento

Executar os testes concorrentes em MySQL/MariaDB no ensaio final. Enquanto apenas um motor estiver disponível, registrar qual foi executado e manter a outra verificação pendente em STATUS.
