# Plano para remover o trigger de revalorização da arrecadação

## Objetivo

Remover `tr_atualiza_pontos_arrecadacao` sem alterar o resultado do ranking,
sem perder pontos esportivos ou ajustes e sem criar uma janela em que a
pontuação fique parcialmente atualizada.

O comportamento atual deve continuar sendo:

```text
novo_bruto = bruto_atual
           - D(quantidade_atual, valor_anterior)
           + D(quantidade_atual, valor_novo)
```

`D(Q, V)` é a mesma regra de `PontuacaoRules::doacao`: quantidade em
centésimos, valor inteiro e arredondamento inteiro único. A operação deve
alterar somente a parcela de arrecadação do bruto da turma.

## Estado atual e risco principal

O trigger está no schema inicial e é criado pela migration
`003_fix_arrecadacao_revaluation.sql`. Ele dispara depois de qualquer
`UPDATE` em `interclasses`, mas só age quando
`valor_item_arrecadacao` muda.

Os caminhos atuais de escrita são diferentes:

- `MysqliEdicaoRepository` altera o valor da arrecadação na edição;
- `MysqliArrecadacaoRepository` já calcula e grava o delta de arrecadação ao
  adicionar ou estornar itens;
- `MysqliPodioRepository` aplica deltas de créditos esportivos;
- `MysqliRankingRepository` mantém o ajuste administrativo legado do bruto.

O código de arrecadação não deve ser duplicado. A mudança necessária é apenas
assumir, dentro da atualização da edição, a responsabilidade que hoje é do
trigger.

O risco mais grave é a dupla aplicação: se o código novo atualizar `turmas` e
o trigger ainda existir, a mesma revalorização será aplicada duas vezes. Por
isso, código e remoção do trigger devem ser coordenados como uma única troca.

## Decisão arquitetural

Mover a revalorização para `MysqliEdicaoRepository::update`, dentro da
transação que já envolve a atualização da edição.

O repositório deve:

1. Validar o novo valor no serviço, mantendo a regra atual de não aceitar
   valores negativos.
2. Travar a edição com `FOR UPDATE` e obter o valor anterior.
3. Se o valor não mudou, não tocar nas turmas.
4. Se mudou, carregar todas as turmas da edição com
   `qtd_itens_arrecadados` e `pontuacao_turma`, em ordem crescente de
   `id_turma`, usando `FOR UPDATE`.
5. Calcular cada novo bruto por `PontuacaoRules::revalorizar`.
6. Atualizar `interclasses` e os brutos das turmas na mesma transação.
7. Confirmar somente depois que todas as escritas forem concluídas; qualquer
   erro deve fazer rollback da edição e de todas as turmas.

A ordem de locks deve permanecer edição → turmas por ID crescente, igual ao
fluxo de arrecadação existente. Isso reduz a possibilidade de deadlock entre
uma revalorização e um registro/estorno simultâneo.

Não alterar nesta etapa:

- `MysqliArrecadacaoRepository` e sua lógica de histórico/delta;
- `MysqliPodioRepository` e suas fontes de crédito;
- `MysqliRankingRepository` e o contrato do bruto legado;
- tabelas, colunas, IDs, histórico de arrecadação ou contratos offline.

## Etapas de implementação

### E0 — Congelar o contrato e preparar evidências

- Registrar a definição atual do trigger, o resultado de `SHOW CREATE TABLE`
  relevante e o conjunto de testes existentes.
- Executar a suíte atual antes da mudança em MariaDB e MySQL, quando os
  ambientes estiverem disponíveis.
- Usar fixtures com pelo menos duas edições, duas turmas por edição, pontos
  esportivos, ajuste residual e arrecadação fracionária.
- Não usar IDs fixos nem alterar a base de trabalho.

### E1 — Criar os testes de caracterização

Antes de remover o trigger, ampliar os testes para fixar o comportamento que
precisa sobreviver:

- `V=2 → V=3`, `Q=10`, bruto `30`, sendo `10` esportivos: resultado `40`;
- o mesmo caso com ajuste residual: `35 → 45`;
- `V=3 → V=2` restaura o bruto anterior;
- `Q=0` não altera esporte nem ajuste;
- `Q=1,25` segue a regra de arredondamento único;
- duas edições: somente as turmas da edição alterada mudam;
- edição sem turmas: atualização concluída sem erro;
- atualização de nome, status e pontos de pódio, sem alterar o valor de
  arrecadação, não revaloriza turmas;
- valor igual ao atual não gera nova alteração;
- histórico de arrecadação não é criado, removido ou reescrito durante a
  revalorização.

Reutilizar `PontuacaoReconciliationTest` e os testes unitários de
`PontuacaoRules`, em vez de criar uma segunda fórmula em outro teste.

### E2 — Implementar a revalorização na aplicação

- Extrair, se necessário, um método privado ou componente de infraestrutura
  para carregar e atualizar as turmas com parâmetros preparados.
- Reutilizar `PontuacaoRules::revalorizar`; não copiar `ROUND()` para PHP nem
  converter a quantidade para `float`.
- Capturar o valor anterior sob lock antes de montar o `UPDATE` dinâmico da
  edição.
- Garantir que a revalorização ocorra apenas quando o campo foi enviado e o
  valor efetivamente mudou.
- Manter a transação e a trava nomeada já usadas na atualização de edição.
- Cobrir erros de preparação, execução e overflow com rollback verificável.

O teste de rollback deve preparar uma turma com bruto no limite de `INT`,
forçar uma revalorização que não caiba e confirmar que o valor da edição e os
brutos de todas as turmas voltaram ao estado anterior.

### E3 — Validar a aplicação com o trigger ainda presente

Esta etapa serve apenas para detectar se a implementação nova causaria dupla
aplicação. O código não pode ser considerado pronto enquanto os testes
mostrarem revalorização duplicada com o trigger ativo.

Há duas formas aceitáveis de concluir essa etapa:

1. implementar a troca sob modo de compatibilidade que detecte o trigger e
   não grave a parcela duas vezes; ou
2. executar a mudança em janela controlada, com os escritores parados, de modo
   que o código novo e a remoção do trigger entrem juntos.

Para este projeto, a segunda opção é a mais simples e segura, já que o banco
atual não está em produção.

### E4 — Remover o trigger do schema e das bases existentes

Para instalações novas:

- remover a definição de `tr_atualiza_pontos_arrecadacao` de
  `database/schema-inicial.sql`;
- o schema inicial deve terminar sem triggers de pontuação.

Para bases criadas pelo `MigrationRunner`:

- criar `database/migrations/011_remover_trigger_arrecadacao.sql` contendo
  somente `DROP TRIGGER IF EXISTS tr_atualiza_pontos_arrecadacao`;
- não reescrever `001` nem `003`;
- aplicar primeiro o código da E2 e, em seguida, a migration `011`, dentro da
  janela em que não existam escritas concorrentes;
- conferir `sgi_migrations` e o checksum depois da aplicação.

Não executar a migration `011` antes do código novo estar disponível: isso
deixaria atualizações do valor de arrecadação sem revalorização.

### E5 — Testes após a remoção

Executar os mesmos testes em uma base sem trigger. A aprovação deve demonstrar
que o comportamento agora vem da aplicação, não de um efeito residual do
banco.

Adicionar ou adaptar as seguintes verificações:

- `MigrationsTest`: a migration `011` é idempotente e o trigger não existe;
- teste do baseline: instalação de `schema-inicial.sql` termina com zero
  triggers de pontuação;
- `PontuacaoReconciliationTest`: todos os casos de E1 passam sem trigger;
- `ArrecadacaoConsistencyTest`: adição, estorno, lote atômico, delta zero e
  estorno concorrente continuam iguais;
- `PodiumCreditTest` e `IndividualSyncCreditTest`: créditos e retificações de
  pódio não são alterados por uma revalorização;
- `HistoryRankingReconciliationTest`: bruto, arrecadação atual, esporte,
  ajuste e líquido continuam semanticamente separados;
- `InterclasseLifecycleTest`: atualização normal da edição continua
  funcionando.

Criar também um cenário concorrente determinístico, reutilizando o helper já
existente em `ConcurrentInvariantsTest`:

1. worker A altera o valor da arrecadação;
2. worker B registra ou estorna arrecadação na mesma edição;
3. ambos devem respeitar a ordem edição → turma;
4. o resultado final deve ser igual ao cálculo serializado, sem perda de
   pontos, sem duplicação e sem deadlock.

Se o pódio puder atualizar a mesma turma simultaneamente, acrescentar o caso
de revalorização concorrente com aplicação/retificação de pódio e ajustar a
ordem de locks antes da remoção definitiva.

## Rollout e rollback

### Rollout recomendado

1. Criar backup da base e registrar contagens/somas de `turmas` por edição.
2. Parar temporariamente as escritas da aplicação.
3. Publicar o código da E2.
4. Aplicar a migration `011` nas bases existentes, ou usar o baseline sem
   trigger nas bases novas.
5. Confirmar ausência do trigger e executar a verificação de reconciliação.
6. Reabrir as escritas.
7. Executar os testes HTTP, integração, concorrência e navegador.

### Rollback

Se houver falha antes da remoção, reverter somente o código novo.

Se a migration `011` já tiver sido aplicada, não voltar para o código antigo
sem antes recriar o trigger original, porque o código antigo depende dele. O
rollback deve ser feito com os escritores parados, recriando exatamente a
versão corrigida da migration `003` ou restaurando o backup, e então validando
novamente as pontuações.

Nunca operar com estas combinações:

- código antigo sem trigger;
- código novo que grava turmas com trigger ativo;
- migration parcialmente aplicada;
- aplicação liberada durante a troca estrutural.

## Critérios de aceite

- O trigger não existe no schema inicial nem após a migration `011`.
- Alterar o valor da arrecadação preserva esporte, pódio e ajustes.
- A fórmula PHP e o resultado persistido são iguais para valores inteiros e
  fracionários.
- Adição, estorno, histórico e concorrência permanecem sem regressão.
- Falhas intermediárias revertem edição e turmas integralmente.
- Bases de duas edições permanecem isoladas.
- `001` a `010` continuam inalteradas.
- A suíte de qualidade, integração e navegador passa em MariaDB e MySQL.
