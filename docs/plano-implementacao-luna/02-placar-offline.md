# Etapa 2 — Cronômetro, placar e ocorrências offline

Achados A4–A6. Depende de T06. Executar T07–T11.

## T07 — Especificar e testar o cronômetro como regra pura

**Ler:** `MysqliJogoGateway::list/update`, `placar.js` (`togglePause`, `adicionarTempoExtra`, `iniciarJogoServidor`, `carregarDados`) e a projeção de jogos em `mesario-data.js`.

**Criar:** `Competicoes/Domain/CronometroRules.php` e `tests/Unit/Modules/Competicoes/CronometroRulesTest.php`.

### Estado canônico

Reutilizar os campos existentes. Não criar um segundo conjunto de colunas para representar a mesma coisa.

| Campo | Significado após a correção |
| --- | --- |
| `duracao_jogo` | Duração base configurada, em segundos |
| `tempo_extra_jogo` | Soma dos acréscimos, em segundos |
| `tempo_restante_jogo` | Saldo no instante de referência; zero é um valor válido |
| `data_inicio_real` | Instante de referência da última retomada/snapshot em execução; nulo quando congelado |
| `status_jogo` | Agendado / Iniciado / Pausado / Concluido |

As regras recebem `agora` explicitamente como instante numérico. Não usar relógio global dentro dos testes.

```text
saldo_base = tempo_restante_jogo, quando não for NULL
saldo_base = duracao_jogo + tempo_extra_jogo, somente no fallback legado NULL

se status = Iniciado e há instante de referência:
    saldo_atual = max(0, saldo_base - max(0, agora - referencia))
caso contrário:
    saldo_atual = max(0, saldo_base)
```

### Transições a implementar

| Operação | Alteração |
| --- | --- |
| Iniciar jogo agendado | Saldo = duração + extra; referência = agora; status Iniciado |
| Pausar | Materializar saldo atual; referência NULL; status Pausado |
| Retomar pausado | Preservar saldo congelado; referência = agora; status Iniciado |
| Pausar novamente já pausado | Nenhuma redução adicional |
| Retomar novamente já iniciado sem novo snapshot | Não reiniciar a referência nem recuperar tempo |
| Acréscimo absoluto antigo `tempo_extra_jogo` | Calcular diferença entre total novo e antigo; materializar saldo; aplicar diferença uma vez |
| Alterar duração | Materializar saldo; acrescentar a diferença de duração sem recuperar o tempo consumido |
| Salvar saldo explícito em execução | Saldo enviado passa a ser a nova referência; atualizar seu instante junto |
| Concluir | Congelar o saldo e limpar referência; não reabrir implicitamente |

Se um payload contém saldo explícito e acréscimo/duração, não somar a diferença novamente: o saldo explícito já representa o resultado da operação. Validar inteiros não negativos, status permitido e duração positiva. Não tratar `0` como valor ausente.

### Testes exatos

1. 1.200 segundos; após 30, saldo 1.170.
2. Pausar nesse instante; aguardar mais 90; saldo continua 1.170.
3. Retomar; após 10, saldo 1.160.
4. Pausar/retomar repetido sem novo evento não recupera tempo.
5. Acrescentar 60 ao saldo 1.170 produz 1.230; consulta posterior não acrescenta mais 60.
6. Saldo zero permanece zero até acréscimo válido.
7. Registro legado com saldo NULL usa duração + extra; registro com saldo 0 não usa fallback.
8. Instante futuro não produz saldo acima do saldo de referência; entrada impossível é validada.

**Aceitação:** a classe é testável sem banco, sessão ou HTTP; fórmulas e transições estão cobertas.

## T08 — Aplicar o contrato no PHP e proteger o replay

**Ler/editar:** `JogoController.php`, `MysqliJogoGateway.php`, `config/routes.php`, `MutationAction.php`, `Transaction.php`.

**Criar:** `CronometroService.php`, contrato `Competicoes/Domain/CronometroRepository.php` e adaptador na infraestrutura. Reutilizar o gateway para consultas não relacionadas se não houver motivo para movê-las nesta tarefa.

### Implementação

1. Carregar o jogo com trava durante a transição; validar contexto/edição conforme etapa 1.
2. O serviço chama a regra pura com estado atual, comando e relógio; o repositório persiste todos os campos do snapshot de forma atômica.
3. GET usa a mesma fórmula. Não subtrair da duração total se já há saldo de referência.
4. PUT legado contendo só status continua aceito: calcular a transição usando o estado do servidor no instante da requisição.
5. Para novos clientes offline, aceitar um bloco opcional `cronometro` com `versao: 2`, `saldo_segundos` e `referencia_epoch_ms`. A referência representa o instante em que esse saldo foi capturado, não o instante tardio do envio.
6. Para pausa/conclusão versão 2, usar o saldo congelado. Para estado Iniciado versão 2, descontar o tempo desde a referência até a recepção e gravar uma nova referência coerente. Converter epoch para a convenção temporal do banco explicitamente, sem misturar timezone local com UTC.
7. Expor opcionalmente `servidor_epoch_ms` na consulta para o cliente estimar diferença de relógio. Validar tipos, limites e coerência; não permitir valores arbitrários negativos/NaN. A edição/operador continuam sendo validados independentemente do snapshot.
8. Adicionar proteção idempotente para a mutação de cronômetro usando uma chave de rota nova e estável, por exemplo `jogos.put`. Não alterar as chaves das rotas existentes. Reenvio deve devolver a resposta armazenada sem reaplicar transição.
9. Preservar o comportamento de jogos temporários: seus snapshots ficam locais até materialização. Não fingir que o servidor persistiu um ID negativo. Testar a passagem do estado final pelo resultado e a leitura posterior.
10. Não alterar em massa registros antigos. Um jogo já corrompido pela pausa antiga pode ter perdido seu histórico temporal; registrar essa limitação, sem inventar tempo consumido.

### Testes

`tests/Integration/CronometroPersistenceTest.php`, registrado no runner:

- preparar início 30 segundos no passado por fixture controlada; pausa deve persistir 1.170 e referência nula;
- retomar, consultar e pausar novamente sem retornar a 1.200;
- payload antigo só com status; payload novo com snapshot atrasado;
- mesma chave e mesmo corpo executados duas vezes; nenhum desconto/extra duplicado;
- chave igual e corpo diferente rejeitados;
- rejeição por edição e rollback de snapshot inválido;
- falha ao persistir confirmação idempotente reverte também o cronômetro.

**Não usar:** `sleep(30)` para testar passagem do tempo. Injetar relógio nas regras e preparar o instante de referência no banco.

## T09 — Projetar o mesmo cronômetro no navegador

**Editar:** `resources/js/pages/competicoes/placar.js`, `resources/js/offline/mesario-data.js`; se extrair regra JS, criar `resources/js/shared/cronometro.js` e carregar pelo mecanismo de assets/configuração já utilizado.

### Implementação

1. Criar a regra equivalente em JavaScript, com timestamp recebido como argumento. Reutilizar os mesmos casos numéricos de T07 em `tests/javascript/cronometro.test.cjs`.
2. Timer visual deve calcular saldo a partir da referência, não depender exclusivamente de decrementar uma variável por `setInterval`; abas em segundo plano podem atrasar intervalos.
3. Antes de pausar, capturar saldo calculado e atualizar `estadoJogo.tempo_restante_jogo`. Não salvar o objeto antigo de 1.200 segundos quando a tela mostra 1.170.
4. Produzir o bloco versão 2 no instante da ação, usando o deslocamento de relógio estimado na última consulta do servidor. Preservar essa referência no IndexedDB e no corpo da fila.
5. Em retomada, criar nova referência e preservar saldo. Em pausa, limpar referência de execução. Em acréscimo, aplicar uma vez e salvar estado completo coerente.
6. Ao sair/voltar à página, renderizar a partir do snapshot persistido. Não sobrescrever `_pendente: true` com `false` só porque o objeto da tela foi salvo.
7. A projeção de `jogos.php` deve interpretar snapshots e status legados de forma explícita. Não depender apenas de `Object.assign` para transições temporais.
8. No replay de registro antigo que só contém status, não acrescentar campos ao corpo já persistido. O servidor executa o fallback de T08. Sem snapshot/tempo de evento antigo não há como reconstruir exatamente tempo já perdido; a limitação deve ser registrada na etapa 6.
9. Se gravação local falhar, mostrar falha e permitir nova tentativa. Não confirmar operação salva com `.catch(function(){})` vazio.
10. Executar build antes de validar pelo navegador.

### Aceitação no navegador

Criar `tests/browser/clock-persistence.spec.cjs`: 20min → consumir 30s com relógio controlado → pausar → navegar → voltar → retomar. Repetir online, offline e com envio atrasado. Conferir IndexedDB e GET do servidor depois da reconexão. A tolerância do teste integrado deve considerar apenas latência curta documentada, nunca aceitar voltar a 20min.

## T10 — Persistir o placar antes da desmontagem

**Ler/editar:** `placar.js` (`agendarSalvarPartida`, `salvarPartida`, `ajustarGols`, `finalizarJogo`, callbacks de limpeza), `offline-core.js` (`queueMutation`, `syncQueue`) e `page-runtime.js`.

### Caminho escolhido

Eliminar o atraso de 400ms para a persistência. Usar a fila existente como registro durável das alterações de partidas com ID positivo, inclusive online; não criar outro banco de outbox.

### Implementação

1. Capturar imediatamente jogo, partida, novo placar e identidade da ação. Não deixar a gravação consultar variáveis de outra tela quando a Promise terminar.
2. Para ID positivo, chamar `SGIOffline.queueMutation('PUT', url, corpo, cabecalhos)` uma única vez. A função já grava a fila, gera/preserva identidade e chama a projeção local.
3. Quando online, acionar o envio automático existente após a gravação. Não chamar também o `fetch` original com outro identificador, pois isso duplicaria o caminho de escrita.
4. `syncNow()` força tentativa manual de itens em revisão; não usar esse modo para contornar automaticamente um bloqueio. Se necessário expor um método de sincronização automática, ele deve chamar `syncQueue(false)` e respeitar `needsReview`.
5. Garantir ordem por partida: fila local recebe 1, 2, 3 na ordem dos cliques; nenhuma resposta atrasada de 1 sobrescreve a projeção de 3. Manter valores absolutos de placar compatíveis com o endpoint.
6. Para partida/jogo temporário, persistir imediatamente na store existente. Não enviar string `mm_local_*` ou ID negativo como partida real; o resultado final materializa e transporta os placares.
7. A limpeza da página interrompe renderização/listeners/intervalos, mas não cancela intenções já capturadas. `SGIPage.deactivate()` atual é síncrono: apenas devolver uma Promise em `onDeactivate` não fará o shell aguardá-la.
8. A finalização precisa aguardar as gravações locais já iniciadas antes de ler o placar e enfileirar o resultado final.
9. Se IndexedDB estiver indisponível, expor erro recuperável e não declarar alteração durável. Não usar sucesso fictício.
10. Não coalescer entradas antigas da fila nem alterar bodies já identificados. Otimizar volume de envio seria uma tarefa posterior.

### Testes

- Clique + e navegação imediata antes de 400ms; voltar mantém o gol.
- Sequência + + − e navegação: valor final correto, fila em ordem.
- Partida temporária e real, online e offline.
- Fechar modal/finalizar enquanto há escrita local pendente não perde o último valor.
- Falha simulada do IndexedDB não aparece como sucesso.
- Após reconexão, placar no banco corresponde ao valor exibido; nenhuma dupla gravação lógica.

## T11 — Consultar e editar a ocorrência certa offline

**Ler/editar:** `mesario-data.js` (`urlInfo`, `localGet`, `project`, `itemAfetaLeitura`, `respostaLocalComDados`) e `placar.js` (`editarOcorrencia`, `salvarOcorrencia`, `excluirOcorrencia`).

### Implementação

1. Definir adaptadores de recursos para as URLs legadas e suas equivalentes `/api/v1`. Consultar `config/routes/compatibility.php`; não inventar pluralização.
2. Para ocorrências, filtrar `id_ocorrencia` antes dos demais critérios. Comparar IDs como strings para preservar IDs temporários, sem converter `temp_*` em zero.
3. Consulta de ID único ausente retorna lista vazia válida, não a lista inteira. Não substituir uma resposta local autoritativa vazia por snapshot antigo que ressuscita registro excluído.
4. `editarOcorrencia` deve buscar o registro cujo ID coincide com o solicitado, em vez de usar sempre `lista[0]`. Se não o encontrar, informar indisponibilidade e não abrir edição com dados de outra ocorrência.
5. Projetar PUT por ID mesclando o registro existente. Inativação por `status_ocorrencia='0'` deve respeitar os filtros de listagem do endpoint.
6. Ocorrência criada offline tem ID temporário. Ao editar antes de sincronizar, não reescrever o POST identificado. Para novas alterações, registrar desde o enfileiramento uma referência opcional à chave da mutação de criação e ao ID temporário; a alteração posterior mantém corpo e chave imutáveis. O servidor pode resolver a referência pela resposta idempotente da criação, exigindo mesmo operador, rota de ocorrência e escopo autorizado. Se a criação ainda não estiver confirmada, retornar409. Se o mecanismo atual já oferecer mapeamento equivalente, reutilizá-lo. Uma entrada antiga sem referência recuperável permanece para revisão; nunca adivinhar o alvo nem reenviar silenciosamente com corpo diferente. Testar criação, edição dependente, replay e usuário diferente tentando reutilizar a referência.
7. Para DELETE, implementar somente onde o contrato atual suporta DELETE; ocorrência individual usa inativação por PUT. Não criar uma rota nova por suposição.
8. Corrigir também a identificação de ocorrência de turma (`id_ocorrencia_turma`) onde houver confusão com `id_ocorrencia`.
9. Preservar namespace por usuário e filtro de edição. Não servir registros de outro usuário para preencher ausência local.

**Detalhe da referência de criação:** o store atual não expõe uma coluna simples de proprietário. Não aceitar uma chave de mutação como prova de acesso. Para uma nova edição dependente, a referência pode incluir a chave e o corpo original exato do POST, capturados antes de ele sair da fila. No servidor, recomputar `MutationIdentity` com o usuário autenticado, comparar seu fingerprint com `request_hash` da rota de criação e só então usar o ID da resposta. Validar também o acesso ao recurso resolvido. Se não houver fingerprint antigo verificável, recusar essa resolução com conflito e preservar a pendência. Não aceitar `id_usuario` informado pelo cliente como confirmação do proprietário.

### Testes

Criar `tests/javascript/mesario-data.test.cjs` e cenário de navegador para duas ocorrências com textos/penalidades distintos. Pedir a segunda por ID, editar offline e reconectar: somente ela muda. Cobrir ausente, inativada, ID temporário, registro pendente, URL legada/versionada e falha de gravação.

### Fechamento

Executar testes PHP/JS novos, build e os cenários de placar/torneio offline já existentes. Não marcar T09/T10/T11 concluídas apenas pelos testes puros: a aceitação exige persistência local e consulta do servidor após reconexão.
