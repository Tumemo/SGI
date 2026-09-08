# Etapa 3 — Pontuação com origens e correção atômica

Achados A2, A3, A8 e parte de A7. Depende de T11. Executar T12–T17. Esta etapa exige leitura cuidadosa dos valores esperados; não corrigir o total com um `UPDATE` genérico que zere a parcela desconhecida.

## Modelo e regras que devem permanecer iguais em todos os fluxos

```text
Q = quantidade ativa de itens da turma, com duas casas decimais
V = valor atual de um item, inteiro não negativo
D(Q,V) = arredondamento de Q * V para pontos inteiros
S = soma dos créditos esportivos identificados
J = ajustes/saldo legado sem origem detalhada
P = penalidades ativas, conforme as consultas atuais

bruto = D(Q,V) + S + J
liquido = bruto - P
```

`turmas.pontuacao_turma` continua sendo **bruto**. O ranking continua expondo o líquido em `pontuacao_turma` e o bruto em `pontuacao_sem_penalidade`. Não subtrair penalidades na gravação e novamente na consulta.

Decisão de arredondamento: manter pontos inteiros e calcular sobre a quantidade total ativa da turma. Para Q não negativo em centésimos `qc`, calcular `intdiv(qc * V + 50, 100)` em PHP; no banco usar DECIMAL e `ROUND(Q * V, 0)`. Não acumular arredondamento independente de cada inserção.

Validar capacidade numérica antes da multiplicação e da persistência. A fórmula em inteiros pressupõe produto dentro de `PHP_INT_MAX`; pontos também precisam caber na coluna INT atual. Para valores fora do limite, retornar erro de validação sem escrita, em vez de converter implicitamente para float, truncar ou saturar. Testar os limites, além dos exemplos pequenos.

Exemplo obrigatório: duas arrecadações de 0,25 item, valor 2. Total ativo 0,50 → 1 ponto. Remover uma: 0,25 → 1 ponto; remover a outra: 0 → 0. O delta da operação pode ser zero, mesmo com quantidade alterada.

## T12 — Corrigir a revalorização sem apagar esporte ou ajustes

**Ler/editar:** `database/migrations/001_initial_schema.sql` somente leitura; `MigrationRunner.php`; `MysqliEdicaoRepository.php`; criar `PontuacaoRules.php` e teste unitário.

### Primeiro teste

Criar `tests/Integration/PontuacaoReconciliationTest.php` e registrá-lo no runner. Preparar V=2, Q=10, bruto=30, sendo 10 esportivos. Alterar V para 3 pela API: esperado bruto40. Confirmar falha no código anterior.

### Implementação

1. Criar nova migração, sugestão `003_fix_arrecadacao_revaluation.sql` se esse número estiver livre.
2. Remover e recriar somente o trigger `tr_atualiza_pontos_arrecadacao`. A nova fórmula deve ser:

```text
novo_bruto = bruto_atual
             - ROUND(qtd_itens_arrecadados * OLD.valor_item_arrecadacao, 0)
             + ROUND(qtd_itens_arrecadados * NEW.valor_item_arrecadacao, 0)
```

3. O trigger continua sendo disparado por `interclasses` e alterando `turmas`; não fazer UPDATE em `interclasses` dentro dele.
4. Não atualizar pontos de pódio nesse trigger. Mudar valor de arrecadação não deve ter outro efeito esportivo.
5. Criar a regra pura equivalente de D(Q,V) com entradas precisas, para uso pelos serviços/testes.
6. Usar a mesma convenção de arredondamento na revalorização, adição, remoção e histórico. Não misturar `ROUND(...,2)` com colunas inteiras.
7. Validar instalação nova, migração sobre 001/002 e repetição pelo MigrationRunner. Não executar o SQL da migração manualmente em banco de trabalho.

### Aceitação

- Q10, V2→3, bruto30 com S10 → bruto40.
- Mesmo caso com ajuste J5 → bruto45.
- Voltar V3→2 restaura o total inicial.
- Q0 não altera pontos esportivos/ajustes.
- Quantidades fracionárias seguem a fórmula única.
- Checksums de 001/002 permanecem idênticos.

## T13 — Adicionar e estornar por diferença, sob trava

**Ler/editar:** `ArrecadacaoService.php`, `ArrecadacaoRepository.php`, `MysqliArrecadacaoRepository.php`, `ArrecadacaoController.php` e testes de arrecadação.

### Implementação

1. Abrir a transação antes de ler os valores necessários. Integrar `Transaction` se esse repositório puder participar de uma transação externa.
2. Usar uma ordem de travas determinística: edição → turmas por ID crescente → históricos por ID crescente. Na remoção, uma leitura inicial pode descobrir a turma, mas o status/quantidade precisam ser relidos sob trava antes de qualquer desconto.
3. Na adição, validar que turma pertence à edição, carregar Q/V sob trava e calcular `delta = D(Q + quantidade, V) - D(Q,V)`.
4. Persistir Q novo, incrementar bruto por delta e inserir o histórico na mesma transação. Uma operação de quantidade válida pode ter delta zero; não interpretar automaticamente `affected_rows=0` como turma inexistente.
5. Na remoção, carregar o histórico correto com `FOR UPDATE`, confirmar edição e estado ativo. Se já removido, preservar a resposta contratada de “já removido”; não descontar.
6. Calcular `delta = D(Q - quantidade, V) - D(Q,V)` usando o **valor atual**. Aplicar delta ao bruto; não subtrair `pontos_adicionados` histórico como se ainda fosse o valor vigente.
7. Marcar o histórico como removido com predicado `status_historico='1'` e conferir a transição. Se falhar, rollback, nunca desconto parcial.
8. Se Q for menor que a quantidade do histórico, registrar inconsistência e abortar com erro de negócio. Não mascarar com `GREATEST(0, ...)` deixando pontos divergentes.
9. Preservar `pontos_adicionados` como informação histórica da gravação. Se precisar registrar valor unitário original com precisão, adicionar coluna nullable em migração nova; não inventar valores para registros antigos.
10. Não criar código de arrecadação dentro do controlador. Manter validação/cálculo em Application/Domain e consultas/travas no repositório.

### Testes

- Dez itens a 2; mudar V para3; estornar → Q0 e bruto esportivo/ajustes preservados.
- Estornar duas vezes sequencialmente → segundo pedido não muda o banco.
- Dois processos removendo o mesmo histórico → apenas um desconto. Nesta tarefa, criar a parte mínima do helper determinístico descrito em T18 e executar o cenário; não esperar T18 para testar. T18 depois amplia/reutiliza o helper, sem refazê-lo nem criar dependência circular.
- Lote com uma turma inválida reverte todo o lote, conforme o contrato atual.
- Fracionários e delta zero; diferentes valores unitários ao longo do tempo.

## T14 — Registrar o crédito do pódio e tratar o legado explicitamente

**Ler:** `MysqliPartidaGateway::applyPodiumPoints`, `MysqliIndividualRepository::aplicarPontos`, `ChaveamentoRules`, `MysqliChaveamentoRepository::chaveamentoRebuildFromRound`, `bin/sgi.php` e o esquema atual.

### Desenho escolhido

Criar tabela `pontuacoes_podio` em nova migração, sugestão `004_podio_credit_sources.sql`. Ela armazena o crédito vigente por posição, não um log completo de eventos. Usar tipos de IDs compatíveis com o esquema atual, sem introduzir `UNSIGNED` em FK cujo alvo é signed.

| Campo proposto | Uso |
| --- | --- |
| `id_pontuacao` BIGINT, PK | Identidade interna |
| `id_interclasse` INT | Edição |
| `id_modalidade` INT | Modalidade |
| `posicao` TINYINT | 1, 2 ou 3, validado pela aplicação |
| `id_turma` INT | Beneficiária atual |
| `id_equipe` INT nullable | Equipe que originou a posição |
| `id_usuario` INT nullable | Atleta, no individual |
| `id_jogo` INT nullable | Jogo que originou o crédito |
| `pontos` INT | Valor efetivamente concedido à posição, inclusive zero |
| `ativo` TINYINT | Se o crédito participa do total |
| `origem_registro` VARCHAR(24) | `novo` ou `legado_conferido` |
| `atualizado_em` DATETIME | Auditoria operacional |

Unicidade: `(id_interclasse, id_modalidade, posicao)`. Não usar o ID negativo local como origem definitiva. A posição na modalidade é estável mesmo que o chaveamento recrie o jogo.

Não usar cascade que apague créditos antes de estornar o total da turma. Invalidação de chaveamento precisa ser uma operação explícita e transacional. Para referência de jogo removido, permitir NULL depois de tratar o crédito; definir e testar a política das demais FKs conforme a exclusão de edição/modalidade já existente.

### Implementação

1. Criar contrato `Resultados/Domain/PodioRepository.php` com operações para carregar créditos bloqueados, substituir posições e consultar total por turma. SQL fica em `MysqliPodioRepository.php`.
2. A regra pura recebe créditos antigos e novos e calcula delta por turma, somando posições da mesma turma. Nunca pressupor que primeiro/segundo/terceiro pertencem a turmas diferentes.
3. Na primeira conclusão de jogo novo, capturar valores atuais 10/7/5 da edição. Guardar esses valores com as posições.
4. Na correção de participantes, reutilizar os valores já concedidos às posições; mudar beneficiário, não reavaliar configuração posterior silenciosamente.
5. Um jogo concluído antes da existência da tabela não pode ser tratado como “nunca premiado”. Caso contrário, a primeira correção duplicaria os pontos antigos.
6. Criar comando de diagnóstico, por exemplo `php bin/sgi.php pontuacao:diagnosticar`, que liste pódios concluídos sem origem, créditos órfãos, quantidades/históricos incompatíveis e diferenças, sem alterar dados.
7. Criar adoção explícita de pódios legados a partir de um arquivo de conciliação com edição, modalidade, posição, beneficiário, jogo e **pontos efetivamente já concedidos**. O comando valida os relacionamentos e grava `legado_conferido` sem somar novamente ao bruto. Repetição idêntica é inofensiva; conflito de valores é erro.
8. A adoção só pode ser automática quando o valor original tiver evidência confiável. O valor atual da configuração, isoladamente, não prova o valor concedido no passado. Não gerar valores “conferidos” por dedução sem evidência.
9. Em fixture de upgrade sintética, o teste conhece os valores históricos e produz o arquivo de adoção correspondente. Na instalação nova não existem pódios legados para adotar.
10. Até a adoção, preservar consultas e saldo legado. Uma correção de pódio histórico sem origem comprovada deve retornar conflito explicativo, sem gravar placar parcialmente. A fila permanece para revisão; não perder a operação.
11. Registrar essa exigência no procedimento de upgrade. A implantação não está pronta se os pódios que precisam ser operados continuarem sem conciliação.

### Aceitação

- Origem única por modalidade/posição e valores zero válidos.
- Adoção não altera bruto; segunda adoção não duplica.
- Origem divergente/malformada é rejeitada sem escrita parcial.
- Base nova funciona sem arquivo de conciliação.
- Legado desconhecido continua íntegro e aparece no diagnóstico, sem “conserto” inventado.

## T15 — Corrigir pódio mata-mata por substituição de crédito

**Ler/editar:** `ResultadoController.php`, `MysqliPartidaGateway::launch`, `MysqliChaveamentoRepository`, contratos de T14 e serviços de resultado/pontuação.

### Sequência da unidade transacional

```text
autorizar e validar referências
bloquear edição, modalidade/jogo, turmas e créditos na ordem comum
carregar resultado e créditos anteriores
validar placares e novo vencedor
persistir resultado
avançar/reconstruir fases, preservando a identificação do que foi invalidado
determinar pódio válido depois da alteração
calcular delta por turma entre créditos anteriores e novos
aplicar delta e substituir créditos
confirmar resultado, avanço, créditos e resposta idempotente juntos
```

### Implementação

1. Remover a condição que só soma pontos se o jogo ainda não estava concluído. Substituí-la pelo serviço que compara origens antigas e novas.
2. Não simplesmente chamar `applyPodiumPoints()` também na correção: isso somaria prêmios duplicados.
3. Final normal concede 1º/2º; disputa de terceiro concede 3º; semifinais/oitavas não geram esses créditos. Usar `ChaveamentoRules`, não procurar substrings aproximadas.
4. Repetir a mesma classificação produz delta zero, mesmo com outra requisição idempotente válida.
5. Trocar campeão de A para B com valores10/7 produz delta A−3/B+3. Valores totais finais `[7,10]`.
6. Ao corrigir fase anterior e invalidar final/terceiro lugar, retirar créditos que deixaram de ter origem válida. Guardar o estado anterior antes de DELETE/rebuild; não tentar recuperá-lo depois da exclusão.
7. Na recriação de fases, preservar os valores já associados às posições durante a correção desse torneio. Não recalcular retroativamente todos os pódios da edição.
8. Consolidar deltas por turma e aplicar travas por ID crescente, antes das travas dos créditos, conforme a ordem da etapa4. Uma leitura preliminar pode identificar beneficiários antigos/novos; reler os créditos sob trava antes do cálculo final. Não atualizar a mesma turma várias vezes sem necessidade.
9. Falha na confirmação de `MutationAction` deve reverter o crédito, mesmo que métodos internos tenham terminado. Usar savepoints, nunca commit independente.
10. O endpoint legado de resultado por partida e o resultado versionado devem compartilhar essa operação.

### Testes obrigatórios

- `[10,7]` vira `[7,10]` ao inverter a final, com placares persistidos coerentes.
- Terceiro lugar muda de turma; não interfere no campeão/vice.
- Duas posições da mesma turma somam corretamente.
- Configuração muda após premiação; correção de beneficiário preserva valores concedidos.
- Correção de semifinal invalida crédito da final antiga; nova final não duplica.
- Mesmo resultado repetido, chave repetida, falha intermediária e rollback da resposta.
- Pódio legado conciliado corrige; não conciliado falha sem escrita.

## T16 — Aplicar a mesma pontuação ao individual e ao lote offline

**Ler/editar:** `MysqliIndividualRepository::salvarRanking`, `MysqliChaveamentoManagement::saveIndividual`, `MysqliChaveamentoSyncGateway::sync`, `ChaveamentoService.php`.

### Implementação

1. Antes de apagar partidas antigas do individual, carregar e validar créditos anteriores. Preservar o ID do jogo/posição necessário à reconciliação.
2. Validar três atletas distintos, inscritos na modalidade e coerentes com edição/equipe. Manter o comportamento de prova sem ranking ainda definido.
3. Persistir posições e invocar o mesmo serviço de substituição de créditos de T14/T15. Não manter uma segunda função que só incrementa turmas.
4. No lote de sincronização, processar estado recebido, avanço e pódio dentro da mesma unidade de trabalho. O caminho `mata_mata` também precisa reconciliar prêmios, não somente avançar jogos.
5. Evitar dupla aplicação quando um resultado já sincronizado individualmente reaparece no lote. A origem estável por modalidade/posição deve produzir delta zero.
6. Não mudar envelopes, nomes de ação ou chaves antigas de idempotência.

**Testes:** trocar 1º/2º individual; dois atletas da mesma turma; terceiro repetido inválido; POST normal versus sincronização com mesmos dados; lote após resultados individuais; falha no último item com rollback total; torneio offline completo e consulta do ranking no servidor.

## T17 — Reconciliar histórico, ranking e ajustes

**Ler/editar:** `MysqliHistoricoTurmaRepository.php`, `MysqliRankingRepository.php`, `RankingService.php`, `resources/js/pages/resultados/ranking.js`, `resources/js/pages/aluno/ranking.js`.

### Implementação

1. Usar a mesma função D(Q,V) para a parcela atual de arrecadação. O campo histórico `pontos_adicionados` continua mostrando o que foi registrado, não substitui o valor atual da parcela.
2. Ler esporte das origens de pódio conhecidas. Preencher `esportes.modalidades` com modalidade, posição e crédito correspondente; não retornar lista sempre vazia.
3. Calcular saldo residual `J = bruto - D - S` e identificá-lo como ajuste/saldo legado sem origem detalhada. Não somar penalidades a esporte e não forçar J a zero.
4. Acrescentar campos opcionais de ajuste e total líquido no histórico. Manter `turma.pontuacao_turma` bruto se esse é o contrato antigo; atualizar os consumidores para usar explicitamente líquido no total comparável ao ranking.
5. Interface administrativa e do aluno devem exibir o mesmo significado. Não apresentar total bruto como se já tivesse descontado penalidades.
6. Se o endpoint de ranking permitir informar diretamente um novo total bruto, preservar essa função e tratar a diferença como ajuste; não substituir as origens esportivas/arrecadação. Nomear a diferença honestamente se não há registro detalhado da sua origem.
7. Para pódios legados ainda não conciliados, não atribuir automaticamente todo residual a esporte. Mostrar pendência de origem detalhada sem alterar o total.
8. Não tentar reparar dados de trabalho nesta tarefa; diagnóstico e adoção são executados no ensaio isolado.

### Tabela de aceitação

| D | S | J | P | Bruto | Líquido |
| --- | --- | --- | --- | --- | --- |
| 80 | 0 | 0 | 10 | 80 | 70 |
| 30 | 10 | 0 | 3 | 40 | 37 |
| 30 | 10 | 5 | 3 | 45 | 42 |
| 0 | 17 | 0 | 0 | 17 | 17 |

Testar essas linhas nas regras, no JSON do histórico/ranking e ao menos uma pela UI dos dois perfis. A soma das parcelas deve fechar com o total apresentado. Após T12–T17, executar as suítes completas e registrar também diagnóstico/adoção do legado sintético.
