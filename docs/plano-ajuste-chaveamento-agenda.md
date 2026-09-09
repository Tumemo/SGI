# Plano de Implementação — Chaveamento sem Agendamento Automático

> **Plano anterior, substituído em 09/09/2026:** utilizar [Plano de agendamento em blocos](plano-agendamento-em-blocos.md). A nova proposta restringe o mesário à programação completa, reserva horários de fases futuras e propõe impedir conflitos no mesmo espaço. As regras abaixo ficam apenas como histórico e não devem orientar a implementação.

## 1. Objetivo

Alterar o comportamento de criação dos jogos do chaveamento para que eles sejam gerados sem data, horário ou local definidos. Esses dados deverão aparecer como **A definir** e serão preenchidos posteriormente pelo professor ou colaborador responsável pela agenda.

O plano também preserva o comportamento observado na homologação: jogos diferentes podem ser marcados para o mesmo horário e local quando essa for a decisão do responsável pela organização.

## 2. Requisito funcional

Ao gerar um chaveamento mata-mata ou uma competição individual:

- o jogo deve ser criado normalmente;
- as equipes e a estrutura da chave devem continuar disponíveis;
- a data deve iniciar como **A definir**;
- o horário deve iniciar como **A definir**;
- o local deve iniciar como **A definir**;
- o professor deve conseguir editar e preencher esses dados pela agenda ou pela tela de chaveamento;
- o jogo não pode ser iniciado enquanto o agendamento estiver incompleto;
- jogos com o mesmo horário e local devem continuar podendo ser salvos;
- nenhuma regra de unicidade deve ser criada no banco para bloquear essa situação.

### Decisão de representação

“A definir” será um estado de apresentação, não um valor armazenado:

| Informação | Valor no banco enquanto não definida | Texto na interface |
| --- | --- | --- |
| Data | `NULL` | `A definir` |
| Horário inicial | `NULL` | `A definir` |
| Horário final | `NULL` | `A definir` |
| Local | `NULL` | `A definir` |

Não usar `CURDATE()`, `08:00:00`, string vazia, `00:00:00` ou ID `0` como substitutos. Esses valores representam dados reais ou inválidos e podem fazer o jogo parecer agendado.

## 3. Evidências do comportamento atual

### 3.1 Geração do chaveamento

O gerador atual escolhe o primeiro local cadastrado e grava a data atual e `08:00` ao criar partidas:

- [MysqliChaveamentoRepository.php](../src/Modules/Competicoes/Infrastructure/MysqliChaveamentoRepository.php) — `criarChaveamentoInicial`, `inserirJogoDupla` e `inserirJogoBye`.
- O avanço para fases seguintes repete o mesmo padrão em `chaveamentoProcessarAvanco`.
- A disputa de 3º lugar repete o padrão em `inserirJogoPosicao`.
- [MysqliIndividualRepository.php](../src/Modules/Competicoes/Infrastructure/MysqliIndividualRepository.php) cria o jogo de modalidade individual com a mesma data, horário e local padrão.

### 3.2 Restrições do banco

Na estrutura inicial, `jogos.data_jogo`, `jogos.inicio_jogo` e `jogos.locais_id_local` são `NOT NULL`. Será necessária uma nova migração; a migração inicial não deve ser reescrita.

### 3.3 Agenda

O fluxo atual da agenda descarta jogos sem data em `jogosDoMesVisivel()`. Com o novo comportamento, isso faria os jogos “A definir” desaparecerem da tela do professor. Será necessário criar uma área própria para pendências de agendamento.

### 3.4 Chaveamento

O JSON da árvore já retorna data e horário, mas não retorna o ID e o nome do local. A tela de edição da árvore não consegue, portanto, selecionar corretamente o local atual. O contrato da árvore deverá passar a devolver todos os campos de agenda.

### 3.5 Operação offline

O motor offline herda a data do primeiro jogo ou usa a data atual e, em alguns pontos, escolhe o primeiro local disponível:

- [chaveamento-engine.js](../resources/js/offline/chaveamento-engine.js) — `dataJogoPadrao`, `defaultLocalId` e `garantirJogoPorTag`.
- O carregamento do placar também tem um fallback que atribui o primeiro local quando o jogo não possui local.

Esses fallbacks precisam ser retirados para que a operação online e offline tenha o mesmo contrato.

## 4. Escopo da implementação

### Incluído

1. Alteração versionada do schema para aceitar agenda incompleta.
2. Geração online de jogos sem agenda padrão.
3. Geração e avanço offline sem agenda padrão.
4. Persistência e sincronização de valores nulos.
5. Exibição de **A definir** nas telas administrativas, do mesário e do aluno.
6. Edição posterior de data, horário e local.
7. Bloqueio de início de jogo sem agenda completa.
8. Aceitação explícita de dois ou mais jogos no mesmo horário e local.
9. Testes unitários, integração, navegador, migração e regressão.

### Fora do escopo

- Alterar a estrutura das equipes ou o algoritmo de sorteio da chave.
- Alterar regras de avanço, classificação ou pontuação.
- Apagar ou reescrever jogos já existentes.
- Criar uma agenda automática obrigatória.
- Criar reserva exclusiva de quadra ou controle de capacidade do local.

## 5. Modelo de dados e migração

### 5.1 Nova migração

Criar uma nova migração em `database/migrations/`, por exemplo `005_jogos_agendamento_opcional.sql`, contendo alterações equivalentes a:

```sql
ALTER TABLE jogos
    MODIFY data_jogo DATE NULL,
    MODIFY inicio_jogo TIME NULL,
    MODIFY termino_jogo TIME NULL,
    MODIFY locais_id_local INT(11) NULL;
```

Observações:

- `termino_jogo` já aceita `NULL`, mas deve ser mantido explicitamente como parte do contrato.
- A chave estrangeira para `locais` deve continuar existindo; uma chave estrangeira aceita `NULL` sem exigir um local fictício.
- Nenhum jogo existente deve ser alterado automaticamente nessa migração.
- A migração deve funcionar em MySQL e MariaDB.
- Testar instalação limpa, atualização de uma base existente, repetição sem delta e rollback transacional quando aplicável ao runner.

### 5.2 Dados históricos

Não converter automaticamente jogos antigos que possuem data/local. Um valor antigo pode ter sido definido intencionalmente pelo professor e não é possível distinguir isso com segurança apenas pelo conteúdo atual.

Se a homologação exigir limpar os jogos já gerados com valores padrão, fazer uma operação separada, revisável e limitada à edição escolhida, com consulta prévia dos registros afetados. Essa operação não deve fazer parte da migração estrutural.

## 6. Alterações no backend

### 6.1 Contrato de criação de jogos gerados

Centralizar a criação de jogos derivados em um caminho que receba agenda opcional. Para jogos criados pelo chaveamento, gravar:

```text
data_jogo       = NULL
inicio_jogo     = NULL
termino_jogo    = NULL
locais_id_local = NULL
status_jogo     = Agendado
```

Aplicar a regra a todos os produtores:

1. Partidas iniciais normais do mata-mata.
2. Partidas `bye`.
3. Partidas da fase seguinte criadas durante o avanço do chaveamento.
4. Disputa de 3º lugar e futuras disputas de posição.
5. Jogo agrupador de modalidades individuais.
6. Materialização no servidor de jogo temporário criado offline.
7. Sincronização de árvore que precise materializar um jogo ainda inexistente.

Retirar a dependência de `resolverIdLocal()` desses caminhos. O método poderá continuar existindo apenas onde houver uma necessidade real e explícita de local padrão, depois de uma busca de consumidores.

### 6.2 Atualização de jogos

A atualização deve diferenciar três situações:

| Payload | Comportamento |
| --- | --- |
| Campo omitido | Preservar o valor atual. |
| Campo enviado com valor | Gravar o novo valor. |
| Campo enviado como `null` | Limpar o valor e voltar a mostrar **A definir**. |

Revisar [MysqliJogoGateway.php](../src/Modules/Competicoes/Infrastructure/MysqliJogoGateway.php), porque o código atual converte valores nulos para string vazia ou inteiro `0` antes do `UPDATE`.

O endpoint `PUT /api/v1/jogos` deve aceitar, para professor/colaborador:

```json
{
  "id_jogo": 123,
  "data_jogo": null,
  "inicio_jogo": null,
  "termino_jogo": null,
  "locais_id_local": null
}
```

Também deve aceitar a gravação dos quatro valores reais quando o responsável concluir o agendamento.

### 6.3 Validação de início

Antes de passar o jogo para `Iniciado`, validar no servidor que existem:

- data;
- horário inicial;
- horário final, ou uma duração operacional válida conforme o contrato atual;
- local.

Se faltar qualquer informação obrigatória, responder com `422` e uma mensagem orientando o professor a completar o agendamento. A mesma regra deve existir no cliente apenas para melhorar a experiência; a proteção efetiva deve permanecer no backend.

Revisar o caminho do [CronometroService.php](../src/Modules/Competicoes/Application/CronometroService.php) e seu repositório para que a validação ocorra antes do início real do cronômetro.

### 6.4 Consulta de jogos

Em [MysqliJogoGateway.php](../src/Modules/Competicoes/Infrastructure/MysqliJogoGateway.php):

- trocar o `INNER JOIN locais` por `LEFT JOIN locais`;
- manter `locais.nome_local` como `NULL` quando não houver local;
- retornar jogos sem data na listagem da agenda e nas consultas por edição/modalidade;
- revisar ordenação para que jogos sem data possam ser exibidos em uma seção de pendências, sem misturá-los silenciosamente a um mês específico.

O `LEFT JOIN` é obrigatório: sem ele, um jogo com `locais_id_local = NULL` desaparece da API.

### 6.5 Sobreposição de horário e local

O requisito da homologação é que jogos diferentes possam compartilhar data, horário e local. Portanto:

- não criar índice único envolvendo data, horário e local;
- não criar trigger para bloquear a sobreposição;
- não inserir uma nova validação de conflito no fluxo de edição ou agendamento em lote;
- atualizar o teste atual que espera bloqueio, caso a regra de aceite seja confirmar a permissão da sobreposição também na criação via `POST`.

Há uma diferença no código atual: `JogoService` consulta `localConflict()` no `POST`, enquanto a edição via `PUT` não faz essa validação. Durante a implementação, decidir e registrar a política única. Para atender ao exemplo da homologação, a recomendação deste plano é tornar a sobreposição permitida nos dois caminhos e transformar o teste de conflito em teste de aceitação de sobreposição.

## 7. Alterações no JSON do chaveamento

Atualizar `montarJsonArvore()` para incluir, por jogo:

```json
{
  "data_jogo": null,
  "inicio_jogo": null,
  "termino_jogo": null,
  "locais_id_local": null,
  "nome_local": null
}
```

Quando o jogo estiver agendado, os campos devem conter os valores reais. O mesmo contrato deve ser utilizado pela tabela de jogos, pela agenda e pelo motor offline.

## 8. Alterações na interface administrativa

### 8.1 Agenda de jogos

Revisar [configurar-agenda.js](../resources/js/pages/eventos/configurar-agenda.js) e seu template:

1. Criar uma seção “Jogos aguardando agendamento” acima ou ao lado do calendário.
2. Listar nessa seção qualquer jogo com pelo menos um campo obrigatório ausente.
3. Exibir, de forma explícita:
   - `Data: A definir`;
   - `Horário: A definir`;
   - `Local: A definir`.
4. Manter o botão de editar disponível para administrador e colaborador.
5. Não depender do mês atual para localizar pendências.
6. Adicionar filtro opcional `A definir` para separar pendências de jogos já agendados.
7. Não exibir o botão de iniciar para jogos incompletos.
8. Depois de salvar a agenda, remover o jogo da seção de pendências e exibi-lo no mês correto.
9. Se o professor limpar novamente algum campo, devolver o jogo à seção de pendências.

### 8.2 Modal de edição da agenda

Revisar o modal `modalEditarJogoAgenda`:

- remover a obrigatoriedade do campo de data para permitir o estado inicial;
- não preencher automaticamente data atual, `08:00` ou `09:00` ao abrir um jogo sem agenda;
- adicionar a opção `A definir` no seletor de local;
- permitir limpar data, horário inicial, horário final e local;
- enviar `null` explicitamente quando o campo for limpo;
- manter a validação de data passada somente quando uma data real for informada;
- informar ao professor que o jogo ficará pendente até os dados necessários serem definidos.

### 8.3 Datas automáticas

Revisar o recurso “Datas Automáticas” para garantir que:

- jogos recém-gerados sem data sejam incluídos na seleção;
- a operação preencha data, horário inicial, horário final e local em lote;
- jogos já agendados só sejam alterados quando o professor confirmar a operação;
- a mensagem final informe quantos jogos foram atualizados e quantos permaneceram pendentes;
- a operação não introduza bloqueio por sobreposição de local/horário.

## 9. Alterações na tela de chaveamento

Revisar [chaveamento.js](../resources/js/pages/competicoes/chaveamento.js) e [chaveamento.php](../resources/views/pages/competicoes/chaveamento.php):

1. Renderizar `A definir` quando data, horário ou local forem `NULL`.
2. Mostrar a pendência no card da chave e na tabela de jogos.
3. Incluir local e término no objeto usado pelo botão de edição.
4. Tornar o campo de data opcional no formulário.
5. Preencher o local sem selecionar automaticamente o primeiro local disponível.
6. Enviar valores nulos de forma explícita ao limpar o formulário.
7. Manter a edição disponível para partidas derivadas online.
8. Para partidas temporárias offline, informar que a edição será concluída após a sincronização, preservando a agenda nula.

## 10. Alterações nas telas do aluno e do mesário

### Aluno

Revisar a apresentação de [aluno/jogos.js](../resources/js/pages/aluno/jogos.js) para mostrar:

- `A definir` no lugar de `—` ou `Quadra` quando a data/horário/local ainda não existir;
- o jogo normalmente na lista, mesmo sem data;
- o status como aguardando programação, sem sugerir que o jogo já esteja marcado.

### Mesário e placar

Revisar [placar.js](../resources/js/pages/competicoes/placar.js):

- remover o fallback que atribui automaticamente o primeiro local ao carregar um jogo sem local;
- impedir início de jogo sem agenda completa;
- permitir leitura de uma partida pendente sem inventar local ou horário;
- preservar `NULL` no contexto enviado para a projeção offline.

## 11. Alterações no modo offline

### 11.1 Motor de avanço

Em [chaveamento-engine.js](../resources/js/offline/chaveamento-engine.js):

- remover `dataJogoPadrao` baseado no primeiro jogo ou na data atual;
- criar jogos derivados com `data_jogo`, `inicio_jogo`, `termino_jogo` e `locais_id_local` nulos;
- não herdar o local do primeiro jogo;
- não substituir o local nulo pelo primeiro item de `locaisStore`;
- manter modalidade, edição, equipes e duração operacional quando necessário, sem inventar agenda;
- garantir que o jogo derivado permaneça visível na agenda offline como **A definir**.

### 11.2 Camada de dados local

Em [mesario-data.js](../resources/js/offline/mesario-data.js):

- distinguir propriedade ausente de propriedade presente com valor `null` durante o merge;
- não restaurar um local antigo quando a mutação solicitou limpeza explícita;
- preservar nulos em exportação, importação, fila e projeção;
- não alterar a versão do IndexedDB apenas por aceitar campos nulos, pois o schema local já armazena objetos flexíveis;
- testar mutações pendentes antes e depois da reconexão.

### 11.3 Sincronização

Em [MysqliChaveamentoSyncGateway.php](../src/Modules/Sincronizacao/Infrastructure/MysqliChaveamentoSyncGateway.php) e no materializador de partidas:

- criar a partida definitiva com agenda nula;
- não chamar `resolverIdLocal()` para uma partida que ainda não foi agendada;
- manter a tag do chaveamento e as equipes para posterior edição;
- não apagar uma agenda que o professor já tenha definido no servidor ao sincronizar apenas um resultado.

## 12. Plano de testes

### 12.1 Unidade

- Serviço de jogos aceita criação manual conforme a política definida para sobreposição.
- Atualização preserva campos omitidos.
- Atualização limpa campos enviados como `null`.
- Valores nulos não são convertidos para `0`, string vazia ou data atual.
- Regra de início rejeita jogo sem data, horário ou local.
- Motor de chaveamento cria partida derivada sem agenda.
- Motor offline cria partida derivada sem agenda e sem herdar o primeiro local.

### 12.2 Integração HTTP e banco

1. Aplicar a migração em uma base com dados existentes.
2. Reaplicar o runner sem gerar nova alteração.
3. Gerar um mata-mata e confirmar que todos os jogos criados têm agenda nula.
4. Confirmar o mesmo para `bye`, final, avanço de fase e disputa de 3º lugar.
5. Gerar modalidade individual e confirmar agenda nula.
6. Consultar `/api/v1/jogos` e `/api/v1/chaveamentos`; nenhum jogo pendente pode desaparecer.
7. Editar um jogo com data, horário e local reais.
8. Limpar os campos por `PUT` e confirmar o retorno a `A definir`.
9. Tentar iniciar jogo pendente e confirmar resposta `422`.
10. Agendar dois jogos com a mesma data, horário e local e confirmar sucesso nos dois.
11. Confirmar que jogos cancelados ou concluídos continuam sendo consultáveis sem local.

### 12.3 Navegador

- Agenda desktop mostra a seção de pendências.
- Agenda mobile mostra os mesmos jogos pendentes.
- Edição de jogo sem agenda abre campos vazios e local `A definir`.
- Salvar agenda preenchida coloca o jogo no mês correto.
- Limpar a agenda retorna o jogo para pendências.
- Dois jogos no mesmo horário/local aparecem como dois cards independentes.
- Chaveamento mostra `A definir` nos cards e permite editar.
- Portal do aluno apresenta os jogos pendentes sem quebrar a lista.
- Mesário não consegue iniciar uma partida sem agenda.

### 12.4 Offline e reconexão

- Gerar ou formar uma fase seguinte sem rede.
- Confirmar que o jogo temporário negativo mantém todos os campos de agenda nulos.
- Reabrir a agenda e o chaveamento offline sem preenchimento automático.
- Sincronizar a fila.
- Confirmar que o jogo definitivo continua sem data/local até o professor editar.
- Definir a agenda após a sincronização e confirmar que a edição persiste.
- Verificar que resultados, avanço e ranking não foram alterados por essa mudança.

## 13. Ordem de execução

### Fase 1 — Baseline e contrato

- Registrar o comportamento atual e os testes existentes.
- Confirmar a política de sobreposição.
- Definir os campos obrigatórios para iniciar uma partida.
- Criar casos de aceite antes da alteração.

### Fase 2 — Banco e backend

- Criar e testar a nova migração.
- Ajustar inserções de jogos gerados.
- Ajustar atualização com `NULL` explícito.
- Corrigir consultas com `LEFT JOIN`.
- Incluir campos de local no JSON do chaveamento.
- Implementar a proteção de início.

### Fase 3 — Interface online

- Ajustar agenda e pendências.
- Ajustar modal de edição e limpeza.
- Ajustar tela de chaveamento.
- Ajustar aluno, mesário e placar.

### Fase 4 — Offline e sincronização

- Ajustar motor offline.
- Ajustar merge da camada local.
- Ajustar materialização e sincronização.
- Reexecutar os cenários existentes de placar e avanço.

### Fase 5 — Validação final

- Executar testes unitários e de integração.
- Executar `composer verify`.
- Executar `npm run check`, `npm test` e `npm run build`.
- Executar `php tests/run_all.php`.
- Executar `npm --prefix tests/browser test`.
- Realizar teste manual com a mesma data, horário e local em dois jogos.
- Registrar evidências e atualizar o status de homologação.

## 14. Critérios de aceite

- [ ] Um chaveamento novo não recebe data atual, horário `08:00` nem primeiro local automaticamente.
- [ ] Todos os jogos gerados aparecem, mesmo com agenda nula.
- [ ] A interface exibe **A definir** para cada informação ausente.
- [ ] Professor/colaborador consegue preencher e limpar data, horário e local.
- [ ] Jogos sem agenda não podem ser iniciados.
- [ ] Dois jogos podem compartilhar o mesmo horário e local.
- [ ] A agenda retorna corretamente jogos sem local por meio de `LEFT JOIN`.
- [ ] A operação offline não inventa data, horário ou local.
- [ ] A sincronização mantém a agenda nula até uma edição explícita.
- [ ] Jogos já agendados não são sobrescritos por uma migração automática.
- [ ] Todas as suítes de qualidade e navegador passam.

## 15. Evidências a anexar

Para encerrar a ocorrência, anexar ou referenciar:

- resultado da migração em base de teste;
- resposta JSON de jogo pendente;
- captura da agenda com a seção **A definir**;
- captura de dois jogos no mesmo horário/local;
- captura do modal preenchendo a agenda;
- teste de bloqueio de início sem agenda;
- teste offline antes e depois da sincronização;
- resumo das suítes automatizadas executadas.
