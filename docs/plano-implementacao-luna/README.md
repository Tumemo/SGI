# Roteiro de implementação do SGI — Luna / Extra alto

> Arquivo histórico: as tarefas T00–T29 foram concluídas. Para as correções
> posteriores e o estado atual, consulte o [plano de melhorias de 11/09/2026](../plano-melhorias-luna-2026-09-11/README.md) e seu [STATUS](../plano-melhorias-luna-2026-09-11/STATUS.md).

Este é o ponto de entrada do plano detalhado solicitado em 07/09/2026. Destina-se à execução pelo modelo Luna com o esforço Extra alto selecionado pelo usuário. Não altera a configuração do modelo nem inicia a implementação.

Objetivo: corrigir os achados A1–A10 da [auditoria](../auditoria-arquitetura-2026-09-07.md), concluir as fronteiras de aplicação e comprovar a compatibilidade HTTP, banco e offline. Referência original: commit `aedbc420ea34aa759db1b14a8307abe4eb478cfa`.

## Como executar sem depender de toda a conversa

1. Leia `AGENTS.md`, este arquivo e [STATUS.md](STATUS.md).
2. Leia somente o arquivo da etapa atual e os arquivos de código indicados na tarefa. Consulte a auditoria para a justificativa, não como substituto das instruções de implementação.
3. Execute as tarefas na ordem da tabela. Uma tarefa é a unidade de implementação e verificação. Não abra uma refatoração de outra etapa para resolver uma dificuldade local.
4. Antes da correção, escreva a regressão indicada e confirme que ela falha pelo defeito esperado. Depois faça a correção e confirme o resultado no banco/UI.
5. Atualize `STATUS.md` após cada tarefa, com arquivos alterados, comandos realmente executados, resultado e próxima ação. Não marque conclusão com base apenas em leitura ou na existência de um teste.
6. Continue para a próxima tarefa autorizada. Não peça confirmação para decisões já especificadas aqui, criação de testes, alterações locais reversíveis ou migrações novas no banco de teste.
7. Se a sessão for interrompida, retome pela primeira tarefa incompleta. Não reinicie o projeto nem repita testes já aprovados se não houve mudanças relevantes desde a execução registrada.

O roteiro define comportamento e desenho proposto, não código já existente. Nomes de novos arquivos são propostas concretas; procure antes se uma implementação equivalente já foi criada em tarefa anterior. Se houver, estenda-a em vez de duplicar. Linhas da auditoria podem mudar: localize pelos nomes dos métodos.

## Ordem, dependências e tamanho das entregas

| Ordem | Arquivo | Tarefas | Entrega verificável |
| --- | --- | --- | --- |
| 0 | [00-preparacao.md](00-preparacao.md) | T00–T01 | Ambiente isolado e base de comparação reproduzível |
| 1 | [01-autorizacao.md](01-autorizacao.md) | T02–T06 | Mesário limitado ao recurso da edição ativa; erros seguros |
| 2 | [02-placar-offline.md](02-placar-offline.md) | T07–T11 | Cronômetro, placar e ocorrências persistentes nos dois modos |
| 3 | [03-pontuacao.md](03-pontuacao.md) | T12–T17 | Arrecadação, pódio, correção e histórico consistentes |
| 4 | [04-invariantes.md](04-invariantes.md) | T18–T21 | Estorno, inscrições, equipes e edição ativa protegidos |
| 5 | [05-fronteiras.md](05-fronteiras.md) | T22–T25 | Casos de uso e contratos aplicados aos fluxos críticos |
| 6 | [06-validacao-final.md](06-validacao-final.md) | T26–T29 | Upgrade, caches antigos, matriz e entrega documentada |

São 30 tarefas. Execute sequencialmente. A correção de estorno concorrente já integra T13; T18 valida a infraestrutura de testes concorrentes e confirma esse cenário junto aos demais. Não implemente duas soluções diferentes para o mesmo problema.

## Regras obrigatórias durante toda a execução

- A aplicação continua um monólito PHP/JavaScript com os sete módulos atuais. Não introduzir framework, microsserviço, ORM, contêiner global ou módulo genérico `Interclasses`.
- SQL, MySQLi e mecanismos concretos de trava ficam na infraestrutura. Domain/Application não recebem `Request`, `Response`, sessão ou conexão MySQLi.
- Uma transação que atravessa serviços/repositórios usa a mesma conexão e o mecanismo de savepoints de `App\Shared\Database\Transaction`. Não chamar `mysqli::begin_transaction()` dentro de uma transação já controlada por `MutationAction`.
- Um erro entre resultado, avanço, pontuação e resposta idempotente deve desfazer tudo. Não capturar a exceção e devolver sucesso para fazer a fila desaparecer.
- Preservar caminhos em `config/routes/compatibility.php`, nomes de campos consumidos pelo JavaScript, chaves de mutação e entradas de cache antigas. Novos campos devem ser opcionais e compatíveis.
- Não modificar o corpo, a chave ou o fingerprint de uma mutação já persistida apenas para adaptá-la à versão nova. Adaptar a interpretação no servidor; nunca reenviar silenciosamente como uma mutação diferente.
- Editar fontes em `resources/`; gerar `public/assets/` com `npm run build`. Não corrigir somente o arquivo gerado.
- Não editar migrações já existentes/aplicadas. Numerar novas migrações após a última encontrada no momento da tarefa. Os números sugeridos nos capítulos pressupõem apenas `001` e `002`; não reutilizar um número que outro trabalho ocupou.
- Não adicionar trigger que atualize a tabela que o disparou. Não relaxar chaves estrangeiras, unicidade de matrícula por edição, CSRF ou RBAC para aprovar testes.
- Testes destrutivos só podem usar banco isolado confirmado. Não trocar o banco de trabalho pelo de teste na configuração permanente do usuário.
- Não atualizar snapshots visuais automaticamente para esconder uma diferença. Não reduzir asserções, pular cenários ou transformar falha inesperada em aprovação.
- Não efetuar deploy, push, merge ou limpeza de dados de trabalho como parte da implementação local deste roteiro.

## Decisões de comportamento adotadas neste plano

| Tema | Decisão |
| --- | --- |
| Mesário | Modifica somente recursos da edição ativa no momento da execução; a edição deve vir do banco, nunca do corpo da requisição |
| Administrador/colaborador | Preservar as permissões atuais de cada ação; coerência de IDs é exigida mesmo para administrador |
| Aluno | Preservar permissões do portal; não permitir mutações operacionais do mesário |
| Referência de partida | `id_partida` determina jogo/equipe reais; corpo divergente é erro, não autorização para mover a partida |
| Arrecadação | Alterar o valor do item revaloriza a quantidade ativa, preservando esporte e ajustes |
| Arredondamento | Pontos continuam inteiros; arredondar o total da quantidade ativa da turma, conforme T12, sem aritmética monetária em float |
| Pódio | Cada modalidade tem posições 1/2/3 com origem identificável; corrigir posição substitui o crédito antigo, não soma outro |
| Configuração de pódio | Alterar valores de 1º/2º/3º não revaloriza automaticamente prêmios já registrados; manter valores concedidos na correção de participantes. Revalorização geral de pódios seria outro requisito |
| Dados históricos insuficientes | Não inventar o valor originalmente concedido nem apagar diferenças. Registrar a inconsistência e preservar o saldo até reconciliação explícita |
| Cronômetro | Tempo restante de referência + instante da última retomada; pausa congela; retomada não retorna à duração original |
| Placar | Persistir a intenção localmente antes de considerá-la salva; envio remoto pode ocorrer em seguida |
| Offline antigo | Continuar interpretando registros legados; não exigir campos que não existiam na fila antiga |

Essas decisões tornam a execução determinada e preservam os comportamentos documentados. Se um requisito já existente contradisser uma decisão, cite o arquivo e a regra no STATUS, adapte somente esse ponto e mantenha os demais trabalhos. Não atribua ao usuário uma decisão que o código não permite recuperar, como valores históricos de prêmios perdidos.

## Ciclo de cada tarefa

1. Confirmar pré-requisitos e estado do Git.
2. Ler as fontes indicadas; anotar chamadas que atravessam a fronteira da tarefa.
3. Preparar fixtures sintéticas com IDs criados no próprio teste.
4. Escrever e executar a regressão; guardar a falha esperada.
5. Implementar a menor mudança que respeite o contrato descrito.
6. Executar o teste novo e as verificações relevantes. Em refatoração, cumprir também a suíte completa antes/depois exigida pelo `AGENTS.md`.
7. Inspecionar o diff e procurar usos antigos dos métodos alterados.
8. Atualizar STATUS com evidência e prosseguir.

Se o teste falhar por ambiente, distinguir isso de falha da aplicação. Corrigir o ambiente dentro do escopo autorizado; não declarar o código correto por falta de execução. Se realmente faltar informação indispensável, registrar exatamente a informação ausente e avançar apenas nas tarefas sem dependência dela.

## Contratos comuns e nomes propostos

Criar apenas quando a tarefa correspondente pedir:

| Novo arquivo proposto, relativo à raiz | Responsabilidade |
| --- | --- |
| `src/Modules/Acesso/Domain/ContextoOperador.php` | Valores imutáveis: ID do usuário, nível e edição ativa opcional |
| `src/Modules/Acesso/Application/EdicaoAccessPolicy.php` | Regra pura de permissão sobre uma edição real |
| `src/Modules/Acesso/Application/AcessoEdicaoNegadoException.php` | Falha de autorização de domínio/aplicação, sem HTTP |
| `src/Shared/Application/TransactionRunner.php` | Interface `run(callable): mixed` |
| `src/Shared/Database/MysqliTransactionRunner.php` | Adaptador da interface usando `Transaction::begin/commit/rollback` |
| `src/Modules/Competicoes/Domain/CronometroRules.php` | Cálculo puro de tempo e transições |
| `src/Modules/Competicoes/Application/CronometroService.php` | Carregar estado, validar operador, aplicar regra e persistir |
| `src/Modules/Competicoes/Application/ResultadoService.php` | Lançar/corrigir resultado, validar relações e coordenar avanço/pontos |
| `src/Modules/Resultados/Domain/PontuacaoRules.php` | Cálculo puro da arrecadação e diferenças de crédito |
| `src/Modules/Resultados/Application/PontuacaoService.php` | Substituir créditos por origem e preservar ajustes |

Contratos de persistência devem expor operações de negócio pequenas, como carregar jogo, carregar pódio e substituir créditos. Não criar uma interface genérica que apenas reproduza um método `execute($sql)`.

## Prompt para iniciar a execução

Copie o texto abaixo na tarefa em que o Luna estiver selecionado:

> Implemente o plano de `docs/plano-implementacao-luna/README.md` no projeto `C:\Projetos\SGI`. Comece lendo `AGENTS.md`, o README do plano e `STATUS.md`. Execute a primeira tarefa incompleta e continue na ordem indicada. Leia somente a etapa atual e as fontes necessárias. Antes de corrigir cada defeito, reproduza-o com o teste descrito; após corrigir, valide o comportamento real e atualize STATUS com arquivos, comandos, resultados e próximo passo. Preserve aliases, filas/cache antigos, IDs de mutação, MySQL/MariaDB e migrações aplicadas. Não enfraqueça testes ou permissões para obter aprovação. Faça as alterações locais e verificações necessárias sem pedir confirmações já resolvidas no plano. Não publique nem altere banco de trabalho. Ao interromper, deixe um ponto exato de retomada. O objetivo é implementar, não apenas reescrever o plano.

## Critério final

Todas as tarefas concluídas com evidências; todos os achados ligados a regressões; valores reconciliados; operações offline antigas preservadas; verificação final e limitações documentadas. Um ambiente externo não executado deve aparecer como pendência explícita, nunca como check aprovado.
