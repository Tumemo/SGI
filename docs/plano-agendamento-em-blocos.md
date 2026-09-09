# Plano de implementação — Agendamento em blocos e programação do mesário

Data: 09/09/2026. Status: implementado; plano mantido como especificação técnica e checklist de regressão.

Este documento substitui o plano anterior `plano-ajuste-chaveamento-agenda.md`. Atende à orientação de deixar o agendamento sob responsabilidade da organização e apresentar ao mesário somente jogos com programação completa.

## 1. Comportamento esperado e decisões

1. Gerar o chaveamento define confrontos e dependências, sem preencher data, horário ou local automaticamente.
2. Administrador e colaborador (níveis 0 e 1) organizam a programação manualmente ou por blocos. Mesário (nível 2) opera partidas; aluno (nível 3) consulta informações.
3. A organização pode reservar horários de todas as fases antes de conhecer os classificados: por exemplo, “Final — vencedor da semifinal 1 × vencedor da semifinal 2”.
4. Um assistente solicita as condições do bloco, calcula uma proposta, mostra pendências e somente grava após o responsável confirmar a prévia.
5. O mesário recebe jogos e reservas com data, início, término e local definidos. Uma reserva com participantes ainda indefinidos aparece como “Aguardando classificados”, sem botão de iniciar.
6. Jogos sem programação ficam na área da organização, fora da lista operacional do mesário. O chaveamento pode mostrar rótulos estruturais das próximas fases, sem transformar pendências em jogos operáveis.
7. Avanços online e offline aproveitam a reserva específica do confronto. Não copiam data/local de outro jogo.
8. Jogos iniciados, pausados ou concluídos preservam agenda e histórico; não participam de reprogramação em bloco.

**Regra proposta para conflitos:** impedir sobreposição no mesmo espaço, tanto na edição manual quanto no bloco. O relato original é tratado como um problema de conflito, não como autorização para permitir sobreposição. Esta decisão substitui a interpretação do plano anterior. Espaços independentes devem ser cadastrados separadamente, por exemplo, “Quadra A” e “Quadra B”. Compartilhamento deliberado do mesmo espaço não entra na primeira versão.

“A definir”, “Programado” e “Aguardando classificados” são situações calculadas para apresentação. Não adicionar esses valores ao enum `status_jogo`. Manter os estados operacionais existentes. Byes continuam concluídos automaticamente e não consomem horário ou local.

## 2. Evidências e implicações do código atual

| Ponto observado | Consequência para a implementação |
| --- | --- |
| `MysqliChaveamentoRepository.php` gera partidas com data atual, 08:00 e local padrão. | Remover os padrões de todos os produtores, incluindo avanço e disputas de posição. |
| Fases futuras são materializadas durante o avanço, e a árvore consulta participantes existentes. | Não basta agendar somente IDs de jogos atuais; criar reservas por posição na chave. |
| Byes são gravados como `Concluido`. | Preservar o avanço automático; não convertê-los em jogos pendentes. |
| A disputa de terceiro lugar é materializada após a final no fluxo atual. | Reservar seu horário depois da final e preservar o gatilho atual nesta entrega. |
| Reconstrução da chave pode excluir jogos de posição. | Preservar a reserva por identidade da chave e reconciliar vínculos sem reutilizar agenda de outra chave. |
| A agenda em lote usa atualizações individuais. | Substituir por prévia e confirmação transacional; não admitir sucesso parcial silencioso. |
| POST valida conflito de local, mas PUT não aplica a mesma regra. | Centralizar as validações para todas as entradas. |
| O controlador usa `isset` para alguns campos de agenda e aceita IDs negativos antecipadamente. | Autorizar antes desses desvios; detectar campos com `array_key_exists`, inclusive valores nulos e alterações só de horário. |
| O motor offline herda agenda e cria IDs negativos. | Resolver a programação pela identidade da reserva; preservar identificadores de mutação. |

Os nomes de novas classes, tabelas e rotas abaixo são propostas. Antes de implementá-los, conferir o registro de rotas, a injeção de dependências e a próxima numeração disponível das migrações.

## 3. Informações solicitadas ao responsável

O botão **Agendar em blocos** substitui a experiência atual de “Datas Automáticas”. O formulário divide a configuração em quatro etapas e mantém um resumo visível.

### Etapa 1 — Escolher os confrontos

- Edição permitida ao usuário; validar o vínculo no servidor.
- Modalidades, categorias e fases; permitir selecionar uma ou várias.
- Seleção padrão: somente confrontos ainda não programados, incluindo posições futuras da chave.
- Opção explícita de reprogramar uma seleção de jogos ainda não iniciados, mostrando a agenda atual.
- Prioridade entre modalidades e confrontos da mesma fase; nunca permitir uma prioridade que viole dependências.
- Incluir disputa de terceiro lugar somente quando a modalidade já utilizar essa regra.
- Mostrar quantos confrontos reais precisam de horário, excluindo byes e posições que não produzirão uma partida.

### Etapa 2 — Informar dias e espaços

- Uma ou mais datas, com janela inicial/final por dia e por local.
- Pausas e indisponibilidades, como almoço, recreio ou uso externo da quadra.
- Locais disponíveis e compatíveis com cada modalidade; solicitar a associação no assistente, pois não se deve presumir uma compatibilidade existente no banco.
- Permitir copiar a configuração de um dia e revisá-la antes da simulação.
- Primeira versão: janelas dentro do mesmo dia, sem atravessar meia-noite. Havendo necessidade, o responsável informa outra janela na data seguinte.
- Cada local representa um único espaço simultâneo. Não interpretar `carga_local` como capacidade de partidas sem confirmar seu significado no domínio.

### Etapa 3 — Informar duração e intervalos

- Tempo reservado por partida, em minutos, por modalidade; obrigatório e maior que zero.
- Intervalo para troca/preparação do espaço entre partidas; obrigatório, permitindo zero explícito.
- Descanso mínimo entre jogos da mesma equipe ou atleta; obrigatório, permitindo zero explícito.
- O tempo reservado deve incluir o tempo previsto de jogo e uma margem escolhida pela organização para interrupções. Não alterar `duracao_jogo` ou o relógio ao mudar uma reserva.
- Orientar o responsável sobre durações e descanso, sem inventar valores esportivos obrigatórios.

### Etapa 4 — Conferir a proposta

- Tabela com confronto, participantes ou origem dos classificados, data, início, término, local e alteração proposta.
- Resumo: selecionados, encaixados, já preservados e não encaixados.
- Explicar cada pendência: falta de janela, local incompatível, dependência fora da seleção sem programação, descanso ou conflito de participantes.
- Mostrar alertas conservadores quando atletas de fases futuras ainda forem apenas possíveis participantes.
- Permitir ajustar parâmetros ou uma linha e recalcular a prévia inteira.
- A confirmação exige que todos os confrontos da seleção caibam. Se não couberem, o usuário pode reduzir explicitamente a seleção e gerar nova prévia; nunca salvar silenciosamente apenas uma parte.

## 4. Modelo para reservar fases futuras

Criar uma reserva de programação independente da existência de `jogos.id_jogo`. Isso permite agendar uma final antes de os finalistas serem conhecidos, sem antecipar a criação de partidas esportivas e interferir no ranking.

Identidade estável proposta: edição + modalidade + versão do chaveamento + tag da posição. Reutilizar tags `MM:...` e `POS:...` já utilizadas no domínio, sem mudar a identificação das mutações offline. Para modalidade individual e jogo avulso, definir chaves próprias explícitas, sem fabricar tags de mata-mata.

Novas estruturas propostas:

| Estrutura | Conteúdo e responsabilidade |
| --- | --- |
| Versão do chaveamento | Identidade da geração da chave, distinta da revisão da agenda. Avançar vencedores não muda essa versão; refazer o sorteio muda. |
| `agenda_blocos` | Edição, responsável, parâmetros normalizados, identificação da confirmação, datas de criação e revisão. |
| `agenda_reservas` | Identidade estável, vínculo opcional com jogo materializado, data/início/término/local, intervalo do espaço, descanso, bloco de origem e revisão. |
| Histórico de agenda | Autor, valores anteriores/novos, motivo da reprogramação e operação de origem. Reutilizar infraestrutura de auditoria se disponível. |

As reservas confirmadas são a fonte da programação; os quatro campos de agenda em `jogos` são a projeção para os consumidores atuais. Toda atualização da reserva e do jogo vinculado ocorre na mesma transação. Criar/editar um jogo manual também passa por esse serviço, evitando duas fontes independentes.

Aplicar unicidade à identidade da posição e, quando presente, ao vínculo com o jogo. Essa unicidade evita reservas duplicadas; não resolve sobreposição de intervalos, que exige validação transacional.

Novos jogos reais começam com os quatro campos nulos quando ainda não há reserva. Ao materializar uma fase, consultar a reserva exata e copiá-la para o jogo. O organizador também deve poder visualizar posições futuras ainda sem reserva.

Uma nova migração torna data, início e local opcionais em `jogos`, preserva chaves estrangeiras e acrescenta as estruturas necessárias. Não reescrever migrações aplicadas. Não prever rollback transacional como garantia de reversão de alterações estruturais: preparar backup e procedimento de recuperação, validar instalação limpa, atualização e repetição do runner.

### Dados existentes e reconstrução

- Não limpar agendas antigas pelo fato de conterem 08:00 ou a data da geração.
- Inventariar os jogos existentes. Incorporar agendas completas ao modelo de reservas preservando valores; apresentar conflitos antigos à organização para correção, sem deslocar partidas automaticamente.
- Para registros incompletos antigos, preservar os valores disponíveis e mostrá-los como pendência administrativa; não liberá-los ao mesário como programação nova completa.
- Manter partidas legadas em andamento/pausadas acessíveis para continuidade e concluídas no histórico, mesmo com campos incompletos. Essa exceção de transição não autoriza novos inícios incompletos.
- Correção de resultado que conserve a estrutura mantém reservas e revisa participantes/conflitos. Alteração que afete jogos já operados exige o tratamento existente de correção, sem apagar histórico pelo agendador.
- Refazer um chaveamento invalida a associação com sua versão anterior. Mostrar reservas afetadas e solicitar confirmação na ação de refazer; reservas antigas ficam no histórico e não são reaproveitadas silenciosamente.

## 5. Algoritmo de distribuição em blocos

Implementar um serviço de simulação no servidor, independente da interface. O navegador apresenta a proposta; a validação definitiva ocorre no servidor.

### 5.1 Preparar os dados

1. Validar permissões, edição, seleção, datas, locais, durações e limites do tamanho do pedido.
2. Construir um grafo de confrontos a partir da estrutura real da chave. Cada partida tem predecessoras, participantes conhecidos ou possíveis e duração reservada. Resolver byes estruturalmente sem criar ocupação.
3. Incluir reservas futuras mesmo sem `id_jogo`; não depender de consultas com junção obrigatória de participantes.
4. Identificar atletas compartilhados entre equipes/modalidades pelas inscrições. Para fases futuras, usar a união conservadora dos possíveis classificados. Não garantir ausência de conflitos de atletas olhando apenas a equipe nominal.
5. Carregar a agenda existente e os bloqueios dos locais, não apenas os jogos selecionados. Preservar jogos fora da seleção e todos os já iniciados, pausados ou concluídos.
6. Validar também dependências com sucessoras já programadas fora da seleção: reagendar uma semifinal não pode torná-la posterior à final existente.
7. Unificar e ordenar janelas válidas, subtraindo pausas. Usar datas e horários locais da edição, inicialmente `America/Sao_Paulo`; evitar conversão de datas puras pelo UTC do navegador.

### 5.2 Encontrar o primeiro encaixe válido

Usar uma heurística determinística de primeiro encaixe com dependências, não um otimizador que promete a melhor programação possível.

```text
proposta = vazia
ocupacoes = reservas fixas + bloqueios
pendentes = confrontos selecionados em ordem topológica

enquanto houver confrontos pendentes:
    elegiveis = confrontos cujas predecessoras tenham término conhecido
    se não houver elegiveis:
        registrar dependências não resolvidas e encerrar

    ordenar elegiveis por prioridade do responsável,
        menos locais compatíveis, maior duração e identidade estável

    progresso = falso
    para cada confronto elegível:
        calcular início mínimo após predecessoras e descanso aplicável
        procurar o menor início viável em cada janela/local compatível
        avançar candidatos até o fim de cada conflito encontrado
        respeitar sucessoras fixas, participantes, pausas e fim da janela
        se existir candidato:
            escolher por início mais cedo, preferência de local e ID
            reservar na proposta e atualizar ocupações
            remover confronto dos pendentes
            progresso = verdadeiro

    se não houver progresso:
        registrar motivos para os restantes e encerrar

validar novamente a proposta completa e devolver prévia e pendências
```

Buscar candidatos nos limites de janelas e términos de impedimentos, evitando percorrer cada minuto de vários dias. Restringir a quantidade de confrontos/janelas e o tempo de simulação com limites configuráveis e mensagem clara quando excedidos.

Um confronto sem predecessoras é imediatamente elegível. Uma predecessora fora da seleção precisa de reserva conhecida; caso contrário, a interface orienta incluí-la ou agendá-la. Descendentes de jogos não encaixados recebem motivo derivado dessa pendência.

### 5.3 Regras de intervalo e recursos

- Intervalos são semiabertos: `[início, término)`. Sem intervalo de troca, um jogo pode começar exatamente quando o anterior termina.
- Conflito existe quando `inicioA < fimB` e `inicioB < fimA`, considerando a ocupação do espaço até término + troca.
- Para simplificar a primeira versão, partida e troca devem caber na janela útil; não atravessar pausas, encerramento do dia ou bloqueios externos.
- Descanso dos participantes é uma restrição separada da troca do espaço. Considerar agendas em outros locais e modalidades.
- Usar a duração reservada para calcular o término. Exigir `término > início`; não usar horário 00:00 como indicador de ausência.
- Para partidas individuais, bloquear todos os atletas inscritos no jogo agrupador.
- O conjunto conservador de possíveis classificados pode reduzir o paralelismo. Explicar isso na prévia; a organização pode recalcular quando os participantes estiverem definidos.
- Uma proposta incompleta significa “não foi encontrado encaixe com esta ordem e estas condições”, não prova matemática de impossibilidade. Oferecer alterar prioridades, janelas ou locais.

### 5.4 Exemplo de aceite do algoritmo

Seleção: duas semifinais e uma final; um local; janela 08:00–10:00; duração reservada de 20 minutos; troca de 5 minutos; descanso de 15 minutos.

| Confronto | Horário | Justificativa |
| --- | --- | --- |
| Semifinal 1 | 08:00–08:20 | Primeiro horário disponível. |
| Semifinal 2 | 08:25–08:45 | Respeita cinco minutos de troca. |
| Final | 09:00–09:20 | Espera o fim da segunda semifinal e quinze minutos de descanso. |

A final já tem reserva e aparece como aguardando classificados. A identificação dos finalistas não altera seu horário. Se a janela terminasse às 09:10, a seleção completa não poderia ser confirmada nessa proposta.

## 6. Confirmação, concorrência e edição manual

Propor endpoints sob `/api/v1/agenda`: `POST /simular` e `POST /confirmar`, com controladores finos, serviço de aplicação e repositório transacional. Integrar o registro das rotas e contratos HTTP ao padrão existente.

A simulação recebe a seleção e parâmetros, e retorna proposta, pendências e revisão da agenda/chave/inscrições usadas. Não altera jogos nem ocupa horários permanentemente. A confirmação recebe uma identificação única da operação e a proposta vinculada à revisão validada; nunca confiar em horários devolvidos pelo cliente sem revalidá-los.

Na confirmação:

1. Validar novamente níveis 0/1, edição e identidade de todos os confrontos/locais.
2. Abrir transação e bloquear uma linha de controle da agenda da edição. Todos os escritores de agenda, inclusive edição manual, limpeza, recriação e materialização, seguem o mesmo protocolo. Adotar ordem de bloqueio uniforme para evitar deadlocks.
3. Reler revisões e dados usados: agenda, estrutura da chave, estado dos jogos, locais e inscrições. Escritas nesses dados relevantes também devem participar do protocolo de versão/bloqueio; um token isolado não elimina corrida.
4. Se a prévia estiver desatualizada, responder `409`, sem gravação, e solicitar nova simulação. Para validações do conteúdo, retornar `422` com conflitos identificados.
5. Verificar ocupação completa, dependências e participantes, excluindo da ocupação antiga somente reservas que o próprio lote substituirá.
6. Salvar bloco, reservas, projeções nos jogos existentes e histórico; incrementar a revisão; confirmar tudo de uma vez.
7. Reenvio com a mesma identificação e mesmo conteúdo retorna o resultado original; mesma identificação com conteúdo diferente é rejeitada. Não criar blocos duplicados por duplo clique ou perda de resposta.

O bloqueio por edição é adequado aos locais vinculados à edição no modelo atual. Se dois cadastros de edições diferentes representarem o mesmo espaço físico, o modelo atual não permite detectar essa equivalência; não prometer prevenção entre esses cadastros. Um cadastro compartilhado de recursos seria uma extensão própria.

Edição manual usa as mesmas regras e atualiza reserva/projeção atomicamente. Campo omitido preserva o valor; `null` limpa o campo. Uma reserva tornada incompleta perde a condição de programação completa e retorna às pendências. Limpar ou alterar predecessoras deve revalidar sucessoras existentes; bloquear a alteração isolada quando exigir reprogramação conjunta.

Salvar agenda nunca envia placar nem conclui jogo. Revisar o formulário do chaveamento, que também possui edição de resultados, para separar explicitamente as duas ações.

## 7. Mesário, início de partidas e operação offline

### Lista e autorização

- A consulta operacional aplica o filtro no servidor e no cache: agenda completa e edição ativa. Não basta esconder cards no navegador.
- A lista apresenta programados, em andamento e pausados; concluídos ficam em histórico. Reservas futuras completas aparecem como aguardando classificados.
- Reservas não possuem um jogo fictício iniciável; tornam-se operáveis quando o confronto real for materializado.
- Bloquear agendamento, reprogramação e limpeza para nível 2 e nível 3 em todos os endpoints, inclusive POST, payload com `null`, alterações só de início/término e IDs negativos.
- Acesso direto ao placar de partida sem programação deve ser impedido no fluxo operacional. Preservar acesso administrativo de consulta e a exceção legada documentada.
- Para novo início: exigir agenda completa, participantes resolvidos e estado operacional válido. Manter a política atual de data permitida e não transformar o horário previsto em bloqueio rígido de pontualidade nesta entrega.
- Revalidar a transição no servidor dentro do fluxo do cronômetro. Pausar, retomar e sincronizar operações já executadas não devem ser tratados indistintamente como um novo início.

### Programação disponível sem rede

1. O preparo offline baixa também as reservas confirmadas de todas as fases da edição/modalidade acessível, com identidade da chave e revisão.
2. Guardar os novos metadados nos objetos existentes do cache de chaveamentos, sem alterar stores, versão do IndexedDB ou identificadores de mutação apenas por acrescentar campos.
3. Indicar quando a preparação terminou e qual programação foi carregada. O bloco só deve ser considerado pronto para operação offline depois desse carregamento.
4. Ao avançar vencedor offline, localizar a reserva da posição e versão corretas. Se existir, criar o jogo temporário com a programação reservada e habilitar operação quando os participantes estiverem definidos.
5. Sem reserva, o avanço esportivo permanece registrado, mas o novo jogo não entra na lista operacional. Mostrar aviso agregado para procurar a organização após reconectar; não dar ao mesário um formulário de agendamento.
6. A organização agenda pela aplicação online nesta versão. Não enfileirar confirmações de blocos offline: exigem consulta consistente da ocupação.
7. Na reconexão, vincular o temporário ao jogo definitivo pela identidade já usada no domínio, acrescida da verificação da versão da chave. Preservar a reserva do servidor e deduplicar a materialização.
8. Sincronização de resultados não pode apagar nem substituir programação. Preservar a distinção entre campo ausente e `null` no merge.
9. Se a organização tiver reprogramado enquanto o mesário estava offline, preservar mutações e resultados executados na revisão carregada, registrar divergência e manter a agenda atual do servidor. Não descartar fila por estar desatualizada. Verificar autorização, edição e identidade antes de aceitar os eventos; divergências de chave/participantes exigem conciliação, sem aplicação ao jogo errado.
10. Informar que um dispositivo desconectado não recebe alterações novas. A organização deve atualizar a preparação dos dispositivos antes de operar a programação revisada; não prometer revogação instantânea sem rede.

O teste de torneio completo offline deve incluir finais previamente reservadas e progressão sem necessidade de agendamento pelo mesário. Reservas de terceiro lugar seguem o gatilho atual após a final, tanto no servidor quanto no motor offline; não antecipar o gatilho apenas para preencher a agenda.

## 8. Telas administrativas e do aluno

- Organização: abas “Aguardando agendamento” e “Programados”; filtro independente do mês para pendências; ação de bloco e reprogramação explícita.
- Chaveamento: exibir agenda da reserva mesmo antes de existir jogo real; mostrar “A definir” onde faltar informação. Não exigir equipes já materializadas para renderizar uma posição futura.
- Aluno: manter a consulta da chave e mostrar “A definir” onde necessário; no calendário, somente programação completa. Não apresentar horário/local fictício ou uma reserva futura como jogo com classificados conhecidos.
- Agenda: valores nulos não podem desaparecer por `INNER JOIN locais`; revisar consultas para `LEFT JOIN` e ordenar pendências explicitamente.
- Atualização de tela e cache deve considerar revisão e campos da programação, não somente IDs e status esportivos.
- Assistente responsivo, com rótulos claros, navegação por teclado, resumo de erros e prévia legível no celular.

## 9. Mapa de implementação

| Área | Arquivos/ações principais |
| --- | --- |
| Banco | Nova migração em `database/migrations/`; reservas, revisão, auditoria e agenda nullable. |
| Serviços | Novo serviço de programação e simulador no módulo Competicoes; reserva da fase futura e regras compartilhadas. |
| Criação e avanço | `MysqliChaveamentoRepository.php`, `MysqliIndividualRepository.php`: remover padrões, manter byes e resolver reserva na materialização. |
| API de jogos | `JogoController.php`, `JogoService.php`, `MysqliJogoGateway.php`, repositório de jogos: autorização, nulos, edição centralizada e filtros. |
| Cronômetro | `CronometroService.php` e repositório: dados suficientes para validar novo início sem quebrar retomada/replay. |
| Sincronização | `MysqliChaveamentoSyncGateway.php`, `MysqliPartidaGateway.php`: identidade, reserva e preservação dos resultados. |
| Agenda | `resources/js/pages/eventos/configurar-agenda.js` e template: assistente e prévia; retirar gravação sequencial do lote. |
| Chaveamento | `resources/js/pages/competicoes/chaveamento.js` e template: reservas futuras, edição e separação de resultados. |
| Offline | `chaveamento-engine.js`, `mesario-data.js`, preparação SPA e fila: reservas versionadas, ausência de fallbacks e reconciliação. |
| Mesário/aluno | `placar.js`, listagens de competições e páginas de aluno: filtros e apresentação coerentes. |

Resolver caminhos completos das classes antes de editar. Preservar alterações de homologação já presentes no workspace e integrar os controles de agenda ao ajuste recente que separa agenda de cronômetro.

## 10. Ordem de execução e entregáveis

1. **Baseline e contratos:** executar as verificações do projeto em ambiente isolado; inventariar produtores/consumidores de agenda; fechar contratos de identidade, reserva, prévia, confirmação e replay. Entregável: casos de aceite e contratos revisáveis.
2. **Dados e reservas:** nova migração, inventário de dados antigos, resolução de posições futuras e repositório transacional. Entregável: criar chave sem horários fictícios e reservar final sem participantes.
3. **Algoritmo e API:** simulação determinística, conflitos, descanso, dependências, idempotência e concorrência. Entregável: exemplo da seção 5 aprovado por testes, sem gravação parcial.
4. **Organização:** assistente, prévia, pendências, edição manual compartilhada e auditoria. Entregável: programação de um torneio inteiro pelo responsável.
5. **Mesário e offline:** preparo de reservas, filtro operacional, bloqueio de início e avanço sincronizável. Entregável: torneio previamente programado concluído offline e sincronizado sem duplicidade.
6. **Regressão e entrega:** executar as suítes requeridas, compilar assets, retestar homologação e registrar evidências. Publicar a funcionalidade somente com o fluxo offline pronto, evitando entregar o filtro antes de existir a reserva das próximas fases.

## 11. Testes e critérios de aceite

### Algoritmo e persistência

- [ ] Mesma entrada e revisão produzem a mesma proposta.
- [ ] Um e vários locais, durações diferentes, múltiplos dias e pausas respeitam todas as janelas.
- [ ] Partidas adjacentes funcionam com troca zero; troca positiva ocupa o espaço corretamente.
- [ ] Semifinal/final respeitam dependências e descanso; byes não ocupam agenda.
- [ ] Equipe ou atleta compartilhado entre modalidades não recebe horários conflitantes, incluindo possíveis classificados.
- [ ] Falta de capacidade gera pendências e nenhuma gravação.
- [ ] Reprogramar predecessora valida sucessoras fixas e reprogramar uma linha recalcula conflitos do conjunto.
- [ ] Confirmações concorrentes para o mesmo horário não gravam conflitos; repetir a mesma confirmação não duplica o bloco.
- [ ] Falha no meio da gravação reverte todo o lote, preservando a programação anterior.
- [ ] Criação e edição manuais aplicam as mesmas restrições do bloco.

### Chaveamento, permissões e offline

- [ ] Chave nova exibe data/local/horários “A definir”; bye mantém estado concluído.
- [ ] Final é reservada antes dos classificados e sua materialização mantém a reserva.
- [ ] Disputa de terceiro lugar segue o gatilho atual e recebe a reserva correta.
- [ ] Refazer a chave não reutiliza reserva de outra versão; correção de resultado preserva agenda quando a identidade é mantida.
- [ ] Mesário só recebe programação completa e não altera agenda nem por URL/API direta, `null`, horário isolado ou ID negativo.
- [ ] Jogo com reserva e classificados indefinidos não inicia; jogo completo e pronto inicia conforme regras existentes.
- [ ] Partidas legadas em andamento continuam operáveis sem liberar novos inícios incompletos.
- [ ] Torneio previamente agendado avança até o final offline e sincroniza resultados, ranking e reservas sem duplicação.
- [ ] Sem reserva, a próxima fase não aparece como operável e nenhum fallback inventa programação.
- [ ] Reprogramação durante desconexão não elimina fila nem sobrescreve a agenda nova; divergências ficam registradas.
- [ ] Mobile, calendário do aluno e chaveamento mostram informações coerentes, sem perda de jogos por junções ou filtros.

### Validação de engenharia

Executar antes e após a refatoração, conforme `docs/testing.md` e as instruções do projeto: `php tests/run_all.php` em servidor/banco isolados, `composer verify`, `npm run check`, `npm test`, `npm run build` e `npm --prefix tests/browser test`. Dados de demonstração somente no ambiente de teste. Acrescentar testes de migração e concorrência com conexões independentes, além dos testes de unidade, HTTP e navegador do fluxo acima.

Este documento foi produzido por inspeção e planejamento. As caixas de aceite permanecem abertas; não representam testes já executados ou funcionalidades entregues.
