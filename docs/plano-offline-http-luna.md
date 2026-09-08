# Plano de implementação para Luna — offline sem rede local, em HTTP

Data: 08/09/2026. Projeto: SGI. Este documento é uma especificação de trabalho; as melhorias abaixo ainda precisam ser implementadas e validadas.

## 1. Cenário obrigatório

O servidor PHP/MySQL fica exclusivamente na rede local, acessado por HTTP. Ele não será publicado na internet e não terá HTTPS. O mesário acessa e prepara a aplicação enquanto está conectado à rede local. Depois, seu dispositivo pode perder completamente essa rede: nessa condição não consegue acessar nenhuma API, página ou arquivo do servidor.

O objetivo é continuar operando na aba previamente preparada e sincronizar com segurança quando o dispositivo reencontrar o mesmo servidor local. Não confundir ausência de internet com ausência do servidor. Não consultar Google, serviços externos ou URLs da internet para descobrir se o SGI está disponível.

### Limites do escopo

- Não depender de Service Worker, Background Sync, Web Locks, instalação como PWA, configurações especiais do navegador ou permissões de contexto seguro.
- Não prometer carregamento garantido após F5, fechamento do navegador ou descarte da aba enquanto o servidor estiver inacessível. O cache HTTP pode ajudar em algumas situações, mas não constitui garantia.
- Diferenciar perda da interface de perda dos dados: a fila já confirmada pelo IndexedDB deve continuar recuperável quando o usuário voltar à mesma origem, no mesmo perfil do navegador, com acesso ao servidor. Limpeza do armazenamento pelo usuário/navegador e navegação privada ficam fora dessa garantia.
- Usar um endereço estável do servidor, incluindo protocolo, host e porta. Trocar IP por nome, porta ou perfil do navegador muda o acesso ao armazenamento local. Documentar isso para a operação do evento.
- HTTPS, aplicativo nativo e servidor instalado em cada dispositivo não fazem parte desta implementação.

## 2. Orientações para o Luna

1. Trabalhar neste projeto, respeitando `AGENTS.md` e `docs/testing.md`.
2. Ler primeiro o código atual. Não executar o plano antigo em `docs/plano-implementacao-luna/` como se aquelas correções ainda estivessem pendentes.
3. Preservar alterações existentes, inclusive as correções recentes ainda não commitadas. Não usar reset, limpeza de IndexedDB ou substituição de arquivos completos para facilitar o trabalho.
4. Executar as etapas na ordem abaixo. Cada etapa deve terminar com código funcional, testes pertinentes e uma atualização do registro de execução.
5. Não criar outro projeto, trocar PHP/Vanilla JS por framework ou delegar agentes sem instrução explícita.
6. Se uma decisão afetar os contratos de dados, descrever a decisão e sua compatibilidade antes de implementá-la. Resolver escolhas rotineiras autonomamente.
7. Criar `docs/offline-http-status.md` para registrar por etapa: pendente/em andamento/concluída/bloqueada, arquivos alterados, comandos executados, resultados e limitações comprovadas. Só marcar concluída com critérios de aceite atendidos.

## 3. Mapa de arquivos e contratos a preservar

| Componente | Local | Responsabilidade |
| --- | --- | --- |
| Transporte e fila principal | `resources/js/offline/offline-core.js` | Interceptação, confirmação, retry e fila `mutation_queue` do banco `sgi_offline` |
| Dados estruturados locais | `resources/js/offline/mesario-data.js` | Projeções e consultas do banco `sgi_mesario_dados` |
| Preparação e navegação interna | `resources/js/offline/mesario-offline.js` | Telas preparadas, banco `sgi_pages` e estado de prontidão |
| Chaveamento local | `resources/js/offline/chaveamento-engine.js` | Avanço e jogos temporários |
| Operação da partida | `resources/js/pages/competicoes/placar.js` | Cronômetro, placares, gols e ocorrências |
| Formulários e CSRF | `resources/js/offline/offline-form.js`, `resources/js/shared/http-client.js` | Integração dos consumidores com transporte e sessão |
| Carregamento dos scripts | `resources/views/components/admin-head.php` | Ordem das dependências e identificação do operador |
| Disponibilidade e sessão | `src/Shared/Http/HealthController.php`, `src/Modules/Acesso/Presentation/Http/SessionController.php`, `config/routes.php` | Endpoints locais para disponibilidade e autenticação |
| Identidade offline | `src/Modules/Acesso/Presentation/Http/OfflineSession.php` | Namespace por operador, atualmente derivado também do hash da senha |
| Idempotência no servidor | `src/Modules/Sincronizacao/Presentation/Http/MutationAction.php`, `src/Modules/Sincronizacao/Infrastructure/MysqliMutationStore.php` | Transação, resposta persistida e proteção contra replay |

Regras obrigatórias:

- Preservar `X-SGI-Mutation-Id`, o corpo original persistido e os namespaces das filas existentes. Não regenerar a identidade durante retry.
- Preservar os campos já existentes: `projectionPending`, `remoteCommitted`, `serverResponse`, `dependsOn`, `needsReview` e seus significados.
- `sgi_offline.mutation_queue` continua sendo a fila de envio. A store `fila_sincronizacao` dos dados estruturados não deve se tornar um segundo remetente.
- Não alterar as versões/schemas dos bancos offline existentes nesta entrega. Metadados novos podem ir para um banco auxiliar próprio, com namespace e versão documentados; falha nele não pode apagar a fila original.
- Preservar cronômetro versão 2, referências de tempo, IDs negativos, ocorrências temporárias e equivalência entre aliases PHP e `/api/v1`.
- Se uma mudança realmente exigir migração SQL, adicionar nova migration, nunca reescrever uma aplicada; testar atualização e repetição.
- Editar arquivos em `resources/`, depois executar o build. Não editar manualmente `public/assets/`.

## Etapa 0 — Baseline e ambiente sem HTTPS

### Trabalho

1. Ler `AGENTS.md`, `docs/testing.md`, os componentes do mapa e os testes offline existentes.
2. Registrar `git status --short` e identificar as alterações preexistentes. Não sobrescrevê-las.
3. Usar servidor e banco de testes isolados, com nomes confirmados pelo endpoint de saúde. Não usar dados reais da escola.
4. Verificar PHP e extensão MySQLi tanto no runner quanto no servidor; o PHP padrão desta máquina pode diferir do PHP do XAMPP.
5. Executar a suíte inicial prevista em `AGENTS.md`. Não executar duas suítes que alterem o banco ao mesmo tempo.
6. Criar um cenário de navegador em uma origem HTTP não segura. `http://localhost` sozinho não comprova compatibilidade com HTTP da rede local, pois recebe tratamento especial dos navegadores.
7. A origem HTTP de testes pode ser um hostname de teste resolvido para o servidor isolado ou uma origem HTTP com respostas controladas no Playwright para testes de componentes. Os testes completos devem acessar o PHP/MySQL real do ambiente isolado.

### Aceite

- Documentar versões do PHP, navegador e banco utilizados.
- Confirmar `window.isSecureContext === false` no cenário HTTP não seguro.
- Confirmar escrita/leitura no IndexedDB e comunicação entre duas abas por BroadcastChannel sem HTTPS.
- Registrar falhas de baseline separadamente das mudanças desta entrega.

## Etapa 1 — Estado de conexão baseado no servidor local

### Problema atual

O núcleo usa `navigator.onLine`, eventos do navegador e uma janela de soft-offline. Isso não distingue adequadamente servidor acessível, servidor indisponível e sessão expirada. O envio da fila também não define um timeout explícito.

### Trabalho

1. Introduzir um estado de conexão com, pelo menos: `desconhecido`, `verificando`, `servidor_acessivel` e `servidor_indisponivel`. Manter autenticação e erros de operação como estados separados.
2. Tratar eventos `online`, `offline`, `focus` e `visibilitychange` como gatilhos, sem fazer de `navigator.onLine` a única fonte de verdade.
3. Criar uma sondagem pequena para o endpoint local `/api/v1/health`. Usar o transporte original, sem passar pelo cache GET nem pela projeção local. Configurar `cache: 'no-store'` e resposta HTTP sem cache. Uma resposta antiga nunca pode confirmar reconexão.
4. Validar a identificação do SGI no JSON, não apenas HTTP 200. HTML de login, de proxy ou de outro equipamento não confirma o serviço esperado.
5. O health atual identifica o serviço, mas não verifica o banco nem a sessão. Não tratá-lo como garantia de que uma mutação será aceita. Validar sessão separadamente e continuar tratando falhas reais das APIs.
6. Definir parâmetros centralizados: sugestão inicial de 5 segundos para sondagem, 20 segundos para tentativa de mutação, retry progressivo de 3 até 60 segundos com pequena variação aleatória. Ajustar se medições dos testes justificarem.
7. Cancelar requisições vencidas com AbortController; cobrir também a leitura do corpo da resposta. Cancelar a espera não comprova rollback no servidor: um retry mantém a mesma identidade e corpo.
8. Unificar sondagens simultâneas e liberar timers/handlers quando necessário. Não criar loops de consulta a cada renderização da SPA.
9. Ao detectar servidor disponível, verificar a sessão e solicitar retomada da fila. Com a aba suspensa, os timers podem atrasar; retomar ao voltar ao primeiro plano.

### Testes e aceite

- Com `navigator.onLine` verdadeiro e servidor inacessível, a UI assume modo local e a fila permanece íntegra.
- Resposta de health em cache não simula reconexão.
- Requisição sem resposta termina no prazo e permite nova tentativa.
- Nenhum request a serviços externos é necessário para funcionamento ou detecção.
- Sessão expirada é exibida como problema de autenticação, não como perda de rede.

## Etapa 2 — Gravação local durável e um único caminho de envio

### Trabalho

1. Inventariar as mutações do mesário: início/pausa/retomada, ajustes de placar, finalização, gols, ocorrências e resultados individuais. Anotar consumidores que esperam IDs retornados pelo servidor.
2. Para essas mutações operacionais, persistir a intenção na fila antes de tentar enviá-la, inclusive quando houver rede. A UI confirma o estado “salvo neste dispositivo” somente depois do commit local.
3. Não aplicar essa alteração indiscriminadamente a login, logout, troca de senha e upload de arquivos. Manter restrições existentes e indicar claramente ações que exigem conexão.
4. Centralizar o envio no sincronizador. Não deixar a página enviar diretamente uma operação que o sincronizador também enviará.
5. Preservar o contrato dos consumidores: respostas locais identificadas como locais, IDs temporários para criações e resolução posterior da identidade real. Não retornar sucesso remoto quando só houve gravação local.
6. Definir explicitamente os estados: persistindo, salvo localmente, aguardando envio, enviando, confirmado remotamente, aguardando autenticação, aguardando revisão.
7. Não descartar a fila antes de confirmação positiva do servidor e conclusão da reconciliação local. Preservar o caminho `remoteCommitted` quando só a aplicação local da resposta falhar.
8. Tornar reexecução de projeção idempotente. Não somar novamente gols ou acréscimos ao reprocessar a mesma intenção.
9. Conferir `onabort` e `onerror` nas transações de escrita afetadas. Promessas não podem ficar penduradas nem anunciar sucesso após aborto.
10. Não comprimir ou eliminar snapshots da fila nesta fase. A identidade já pode ter chegado ao servidor e o corpo não deve mudar durante o retry.

### Testes e aceite

- Queda imediatamente após gravação mantém a alteração recuperável.
- Servidor aplica a operação, resposta se perde, retry não duplica o efeito.
- Falha de projeção depois da confirmação remota não reenvia desnecessariamente a mutação.
- Uma alteração nova durante a sincronização não é sobrescrita pela projeção da resposta de uma alteração anterior.
- Gols, cartões, cronômetro e encerramento continuam funcionando na navegação interna sem rede.

## Etapa 3 — Coordenação entre abas sem Web Locks

### Trabalho

1. Implementar uma reserva de sincronização no banco auxiliar de metadados, isolada por origem/namespace do operador. Não usar uma simples variável em memória nem leitura/gravação separadas em localStorage como trava.
2. Adquirir a reserva por uma única transação IndexedDB de leitura e escrita, comparando dono, geração e validade. Criar identificador de aba com recurso disponível em HTTP; não exigir `crypto.randomUUID()`.
3. Registrar `owner`, `generation` e `expiresAt`. Definir a validade maior que o timeout de uma tentativa, renovando enquanto houver trabalho.
4. Usar BroadcastChannel para avisos de fila/estado e despertar outras abas. A exclusão mútua pertence à transação, não às mensagens.
5. Se BroadcastChannel não estiver disponível, usar polling moderado do estado ou evento `storage` para avisos. O protocolo de reserva permanece no IndexedDB.
6. Antes de cada envio e antes de confirmar/remover um item, verificar a posse atual da reserva. Proteger a atualização da reserva por comparação de dono e geração.
7. Uma aba suspensa pode perder a reserva; ao retomar, ela não deve iniciar novos envios nem confirmar localmente uma tentativa obsoleta sem verificar a situação atual.
8. Uma reserva com expiração não impede fisicamente uma requisição antiga de já estar em trânsito. Manter idempotência no servidor e testar essa sobreposição, sem alegar garantia de execução única apenas pela reserva.
9. `syncNow()` concorrente deve aguardar ou observar o trabalho existente; não devolver um resumo vazio que possa ser confundido com sincronização concluída e disparar limpeza prematura.
10. Ao reler a fila, item ausente não deve ser reenviado usando uma cópia antiga do snapshot.

### Testes e aceite

- Duas abas iniciando sincronização juntas não duplicam efeitos remotos.
- Fechamento/suspensão da aba líder permite retomada por outra após expiração.
- A aba antiga voltando depois da tomada da reserva não sobrescreve estado nem apaga pendências novas.
- Operadores distintos continuam separados. Coordenação entre abas não é coordenação entre dispositivos.

## Etapa 4 — Recuperação automática e autenticação

### Trabalho

1. Separar falhas transitórias de falhas que exigem ação. Falhas de conexão, timeout e indisponibilidade temporária não devem virar revisão humana somente por atingir cinco tentativas.
2. Manter número de tentativas e último erro para diagnóstico, com retry progressivo e pausa enquanto o servidor estiver indisponível.
3. Tratar 401 como autenticação pendente. Um 403 pode representar CSRF ou falta de permissão: consultar o estado da sessão e não assumir que todo 403 se resolve entrando novamente.
4. Ao voltar a rede, permitir reautenticação do mesmo operador sem limpar os bancos locais. Renovar o token CSRF em todos os consumidores e preservar os IDs das mutações.
5. Validar novamente operador, edição e permissões antes do replay. Se a edição ativa mudou, conservar os dados e explicar por que a operação não pode ser enviada; não vinculá-la silenciosamente à nova edição.
6. Considerar que mudança de senha altera o namespace atual. Não recuperar fila antiga simplesmente atribuindo-a a qualquer conta; tratar esse caso como recuperação assistida, com origem identificável e autorização do servidor.
7. Manter a ordem de dependências de jogos temporários, resultados e ocorrências. Não permitir que uma operação dependente ultrapasse sua criação recusada.
8. Nesta entrega, manter parada conservadora da fila em conflito definitivo se não houver grafo completo de dependências. Não liberar operações por serem de “outro jogo”: chaveamento, pódio e ranking podem criar dependências indiretas.
9. Ações de revisão não devem oferecer “forçar sucesso”. Se for necessário corrigir uma operação recusada, criar nova intenção rastreável e tratar seus dependentes; não modificar o corpo de uma identidade já enviada.

### Testes e aceite

- Mais de cinco falhas de rede seguidas permitem recuperação automática posterior, sem perda nem duplicação.
- Erro de validação não provoca loop de envio contínuo.
- Reautenticação do mesmo operador retoma a fila; troca de operador não envia dados da conta anterior.
- Edição encerrada/inativa gera mensagem útil e conserva os registros locais.

## Etapa 5 — Preparação offline verificável e retomável

### Problema atual

Em `mesario-offline.js`, `verificarPronto()` aceita uma marca antiga em localStorage ou uma agenda armazenada. Um download parcial também pode voltar a mostrar o badge de pronto. Isso não comprova que placares, atletas, ocorrências e chaveamentos necessários estão disponíveis.

### Trabalho

1. Criar um manifesto de preparo no banco auxiliar com operador, edição, versão dos arquivos, data de atualização e lista de recursos obrigatórios/opcionais.
2. Classificar como obrigatórios os dados e telas necessários para agenda, placar, cronômetro, atletas/gols, ocorrências, chaveamentos coletivos e modalidades individuais existentes na edição. Foto de perfil não deve impedir preparo esportivo quando for opcional.
3. Verificar os formatos recebidos, o contexto da edição e o commit no armazenamento. Ausência de exceção no fetch não equivale a recurso válido.
4. Não marcar pronto por porcentagem, existência isolada de agenda ou flag de localStorage. O indicador deve vir da validação dos recursos obrigatórios.
5. Substituir exceções ignoradas na descoberta dos dados por falhas identificadas no manifesto quando afetarem recursos obrigatórios.
6. Salvar progresso por recurso e retomar apenas os faltantes/desatualizados. Manter baixa concorrência e medir o resultado no PHP local; sessões podem serializar requisições.
7. Durante atualização, manter o último conjunto completo utilizável. Só promover o novo manifesto a ativo quando validado; não destruir um preparo válido por um download parcial.
8. Não substituir dados com projeções pendentes por snapshots remotos antigos. Definir e testar a precedência entre cache HTTP, dados estruturados e intenções da fila.
9. Mostrar estados distintos: não preparado, preparando, parcialmente preparado, pronto e atualização pendente. Exibir edição e horário da última preparação completa.
10. Incluir CSS, scripts e dependências locais necessários ao uso da aba preparada. Não introduzir dependências de CDN.
11. Tratar armazenamento indisponível/cheio como falha explícita. Não depender de `navigator.storage.persist()` para garantir funcionamento em HTTP.

### Testes e aceite

- Interromper o preparo em pontos diferentes nunca produz falso “pronto”.
- Falta de atletas ou da árvore de uma modalidade é identificada.
- Falha apenas em recurso opcional é distinguida de preparo operacional incompleto.
- Retomada não baixa novamente todos os recursos já válidos.
- Repreparar com fila pendente conserva placar, cronômetro e ocorrências locais.

## Etapa 6 — Tela de pendências e recuperação por arquivo

### Trabalho

1. Criar painel acessível pelo indicador offline, usando a estrutura visual existente e textos simples.
2. Exibir: partida/modalidade, tipo de alteração, horário local, estado, última tentativa e motivo de bloqueio. Identificadores técnicos ficam em detalhes de diagnóstico.
3. Oferecer “Tentar novamente”, “Atualizar acesso” quando aplicável e “Exportar pendências”. Não oferecer exclusão geral como solução de erro.
4. Exportar um arquivo JSON versionado por Blob/download normal, sem File System Access API. Incluir identidade das mutações, corpo, dependências, edição e metadados mínimos de reconciliação.
5. Não exportar cookies, senhas, tokens CSRF ou dados pessoais desnecessários. O arquivo pode conter informações operacionais dos alunos: explicar sua finalidade no fluxo de exportação.
6. Implementar importação com seleção de arquivo, validação de versão, tamanho, tipos e estrutura. Não executar conteúdo do arquivo nem aceitar URLs arbitrárias de envio.
7. Validar rotas permitidas e método; preservar a identidade original, ignorar duplicatas conhecidas e não aceitar silenciosamente mesmo ID com corpo divergente.
8. Fazer primeiro uma prévia da importação. Confirmar edição/operador/servidor antes de incorporar os dados; alterações já confirmadas no servidor precisam permanecer idempotentes.
9. Remover credenciais exportadas antigas e usar somente autenticação atual no envio. Importação entre operadores, origens ou após troca de senha exige fluxo assistido com validação do servidor; não implementar adoção automática.
10. Se a origem dos dados não puder ser validada com os contratos existentes, entregar exportação e diagnóstico e registrar a importação assistida como bloqueada por requisito concreto, sem inventar autorização baseada apenas no arquivo.
11. Pode haver aviso ao tentar sair com pendências, mas não depender de `beforeunload` para salvar dados ou impedir fechamento. Toda persistência ocorre antes.

### Testes e aceite

- Exportar e importar duas vezes não duplica o efeito de nenhuma mutação.
- Arquivo malformado, grande demais, URL externa, conta incompatível e ID conflitante são recusados sem afetar a fila atual.
- A UI nunca chama “sincronizado” algo que está apenas salvo localmente.

## Etapa 7 — Reconciliação ao recuperar a rede local

### Trabalho

1. Executar a sequência: confirmar servidor, validar sessão/edição, adquirir reserva, enviar fila, confirmar projeções, atualizar dados remotos pertinentes e liberar reserva.
2. Se novas intenções surgirem durante o envio, processá-las em rodada seguinte sem perder a ordem nem apagar sua projeção ao receber dados remotos.
3. Reconsultar partidas, cronômetro, gols, ocorrências e chaveamento afetados. Não fazer recarga completa da página como mecanismo principal de reconciliação.
4. Limpar derivados locais somente quando sua cadeia estiver confirmada e as referências definitivas puderem ser recuperadas. Um resumo provisório ou uma fila de outro operador não autoriza limpeza.
5. Não transformar repetição idempotente em uma nova ação. Preservar a resposta armazenada pelo servidor e o tratamento de confirmação local pendente.
6. Documentar a política para dois dispositivos operando o mesmo jogo. Idempotência de uma mutação não detecta duas intenções diferentes e conflitantes. Não anunciar resolução desse caso sem controle de versão no servidor.

### Aceite

- Jogar um torneio completo sem rede, incluindo derivados, recuperar a rede e conferir todos os resultados no banco.
- Não duplicar gols, ocorrências, pontuação de pódio ou partidas.
- A UI converge para os valores confirmados sem piscar para placares antigos que ainda tenham alterações locais pendentes.

## Etapa 8 — Testes finais e entrega

### Matriz obrigatória

| Cenário | Resultado esperado |
| --- | --- |
| HTTP não seguro, sem internet externa | Preparação e funcionamento online pelo servidor local |
| Perda completa da rede depois do preparo | Navegação interna e operação continuam; nenhuma dependência externa |
| Wi-Fi conectado, servidor desligado | Operação local; diagnóstico de servidor inacessível |
| Rede intermitente durante envio | Retry com mesma identidade e nenhum efeito duplicado |
| Servidor aplica e conexão cai antes da resposta | Replay retorna confirmação da mesma operação |
| Sessão expirada na reconexão | Pendências preservadas e reautenticação orientada |
| Duas abas e suspensão da líder | Retomada sem perda, duplicação ou confirmação por dono obsoleto |
| Armazenamento abortado/indisponível | Nenhum falso salvamento |
| Download parcial ou contexto antigo | Nenhum falso “pronto” |
| Nova mutação durante sync/atualização de cache | Nenhuma sobrescrita por snapshot anterior |
| Troca de operador/edição | Isolamento e nenhuma adoção silenciosa da fila |
| F5 ou fechamento sem rede | Limitação documentada; não alegar reabertura garantida |
| Reabrir com rede após fechar com pendências | Recuperação no mesmo perfil/origem/operador, sem limpeza |
| Origem em subdiretório | Caminhos da API e dos recursos preservam o prefixo |
| Torneio de sete partidas e ocorrência temporária editada | IDs resolvidos e servidor consistente |

### Comandos

Executar na raiz, observando o ambiente isolado descrito em `docs/testing.md`:

```text
composer verify
npm run build
npm run check
npm test
php tests/run_all.php
npm --prefix tests/browser test
git diff --check
```

Não omitir falhas nem atualizar snapshots visuais para esconder regressões. Testes de integração e navegador que alteram SQL rodam sequencialmente. Chromium é o mínimo automatizado; registrar navegadores/dispositivos efetivamente verificados e não extrapolar suporte.

### Entrega do Luna

1. Código e assets preparados.
2. Testes de regressão que reproduzem os problemas e validam a solução.
3. `docs/offline-http-status.md` preenchido e `docs/testing.md` atualizado.
4. Guia curto do mesário: preparar com rede, conferir edição/prontidão, operar na aba aberta, identificar pendências, reconectar, resolver autenticação e exportar recuperação.
5. Resumo final com o que foi implementado, testes aprovados, limitações restantes e qualquer etapa incompleta. Não declarar ausência total de erros.

## Evoluções posteriores — não misturar à primeira entrega

- Controle de revisão por jogo no servidor para concorrência entre dispositivos: requer desenho de comandos, idempotência antes da verificação de revisão e reconciliação de uma sequência de intenções criada offline. Não basta acrescentar um número fixo de versão em todos os itens da fila.
- Execução parcial da fila enquanto outra cadeia está em conflito: depende de grafo explícito com relações entre jogos, fases, pódio e ranking. Manter bloqueio conservador até demonstrar independência.
- Reabertura garantida sem servidor: exige outra decisão de distribuição/infraestrutura e não faz parte do cenário web HTTP atual.

## Prompt de encaminhamento ao Luna

> Implemente as melhorias descritas em `docs/plano-offline-http-luna.md`, seguindo as etapas e critérios de aceite. O SGI será servido somente por HTTP na rede local, sem internet e sem HTTPS. Offline significa que o dispositivo perdeu totalmente essa rede e não alcança o servidor. Preserve as correções e alterações já presentes, os schemas offline existentes e os identificadores de mutação. Não dependa de Service Worker, Web Locks ou APIs restritas a contexto seguro. Comece pelo baseline, registre a execução em `docs/offline-http-status.md` e avance por etapas com testes. Não considere F5/reabertura sem servidor uma capacidade garantida. Entregue código, testes, documentação e os limites efetivamente validados.
