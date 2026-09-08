# Etapa 6 — Upgrade, clientes antigos e comprovação final

Depende de T25. Executar T26–T29. Não publicar a aplicação nesta etapa; preparar evidências e procedimento executável.

## T26 — Ensaiar upgrade real e falhas de migração

**Ler:** `MigrationRunner.php`, `SqlScript.php`, `database/archive/pre-refactor.sql`, migrações, `MigrationsTest.php`, `docs/deployment.md`.

**Criar/estender:** `tests/Integration/LegacyUpgradeTest.php`, fixtures sintéticas e cenários de falha de migração. Registrar testes no runner ou em comando de integração explícito incluído na validação final.

### Preparação

1. Usar bancos adicionais com nomes de teste explícitos, por exemplo `sgi_test_luna_upgrade` e `sgi_test_luna_restore`.
2. O dump arquivado é referência de estrutura. Não importar dados pessoais eventualmente contidos nele para fixtures versionadas. Extrair/preparar uma base representativa com pessoas sintéticas.
3. Conferir que o esquema anterior realmente difere do atual. Remover o histórico de migrações de uma base recém-criada não é ensaio suficiente de upgrade.
4. Se o baseline atual rejeitar legitimamente a versão anterior, documentar/pré-implementar a transformação explícita necessária. Não reduzir a validação para aceitar esquema incompatível.

### Dados sintéticos mínimos

- Duas edições; a mesma matrícula em anos distintos; senhas com hash válido.
- Final e prova individual concluídas, com pontos concedidos conhecidos.
- Arrecadação com fração, registro inativo, valor antigo e novo.
- Penalidade individual e de turma; ajuste manual conhecido.
- Usuários dos quatro níveis; equipes e partidas relacionadas.
- Histórico de idempotência com fingerprint e um registro legado sem fingerprint.

### Casos a executar

| Caso | Verificação |
| --- | --- |
| Instalação vazia | Todas as migrações aplicam; não cria credencial de demonstração de produção |
| Base anterior sem baseline solicitado | Recusa sem apagar dados |
| Base anterior compatível + baseline | Registra adoção e aplica somente evoluções necessárias |
| Novas migrações de pontuação | Preserva saldo; diagnóstico/adoção T14 não duplica prêmios |
| Segunda execução | Nenhuma reaplicação/delta extra |
| Coluna/índice incompatível | Recusa diagnóstica, sem aprovação falsa |
| Checksum divergente | Bloqueia conforme contrato |
| Migração parcialmente aplicada | Mantém `dirty`; não marca sucesso |
| Falha após DDL | Procedimento explica reparação/restauração; não promete rollback transacional do DDL |

Para checksum/dirty, criar um diretório temporário de migrações em `test-results/` e instanciar o runner com esse diretório. Não editar migração real aplicada só para provocar uma falha.

### Conferências após upgrade

1. Comparar contagens, matrículas por edição e `password_verify` das contas sintéticas.
2. Conferir colunas, tipos, índices, unicidade efetiva e triggers relevantes. Um índice de nome correto pode não ser único; conferir `Non_unique`.
3. Conferir FKs e ausência de órfãos nos créditos de pódio.
4. Executar diagnóstico e adotar somente pódios cujo valor é conhecido pela fixture; corrigir vencedor depois e conferir deltas.
5. Executar cenário de artilharia recusada em edição inativa no banco atualizado.
6. Manter dados/artefatos apenas no ambiente de teste e registrar resultados por cenário.

## T27 — Validar caches e filas de versões anteriores

**Ler:** `offline-core.js`, `mesario-data.js`, `mesario-offline.js`, `chaveamento-engine.js`, testes de navegador offline e `config/routes/compatibility.php`.

### Fixtures de compatibilidade

Preparar registros antigos a partir do formato do commit de referência ou de exemplos reais sanitizados existentes. Não chamar um registro recém-gerado pela versão nova de “cache legado”. Não fazer checkout destrutivo no diretório compartilhado; usar leitura do Git/arquivos temporários quando necessário.

Cada fixture deve indicar versão de origem, formato do registro e resultado esperado. Não versionar cookies, CSRF real, senhas ou chave opaca de usuário de uma conta real.

### Matriz obrigatória

| Cenário | Esperado |
| --- | --- |
| URL antiga de API após atualização | Mesmo caso de uso da rota versionada |
| Registro HTML antigo e shell versão2 | Navegação suportada sem colisão de estado |
| Fila com `X-SGI-Mutation-Id` antigo | Mesma identidade; não gera outra ação |
| Token CSRF antigo na fila | Atualiza token da sessão ao reenviar, sem mudar corpo/fingerprint |
| Resposta idempotente antiga sem fingerprint | Mantém compatibilidade prevista no store |
| Jogo negativo e registros dependentes | Materializa primeiro; depois aplica dependentes sem perda |
| Edição mudou desde a gravação offline | Rejeita operação proibida e mantém para revisão |
| Troca de usuário | Não mistura cache/filas entre usuários |
| Ocorrência criada/editada offline | Alvo correto depois da resolução de ID, sem reescrever ação já confirmada |
| Snapshot novo de relógio com envio atrasado | Saldo coerente depois de reconectar |
| Pausa antiga sem snapshot | Caminho legado aceito, limitação de reconstrução registrada |
| Falha de rede no meio do envio | Reenvio confirma uma vez; fila não some por erro |

### Recarga e alcance do offline

1. Preparar uma sessão real até “pronto offline”; desabilitar rede pelo Playwright.
2. Testar navegação na SPA, refresh, nova aba e reabertura do contexto persistente de navegador separadamente.
3. O código de referência usa shell sem Service Worker. Não afirmar que suporta abertura a frio só porque a SPA já aberta navega sem rede.
4. Se refresh/reabertura falharem, registrar o comportamento e delimitar a garantia no README/documentação. Não adicionar Service Worker neste pacote por impulso: isso exige requisitos de cache/autenticação/atualização próprios.
5. Não deixar uma falha classificada como cenário “não suportado” esconder regressão de uma capacidade que antes funcionava e era coberta. Comparar com a versão de referência quando necessário.

**Aceitação:** operações e formatos legados cobertos por testes, sem apagar caches/filas para fazer a nova versão funcionar. Limites de abertura a frio explicitamente documentados.

## T28 — Executar matriz, subdiretório e ensaio de recuperação

**Ler/editar:** `.github/workflows/ci.yml`, `compose.test.yml`, `docs/testing.md`, `docs/deployment.md`.

### Matriz de execução

- Qualidade/unitários em PHP8.2 e PHP8.4.
- Integração e concorrência em MySQL e MariaDB nas versões configuradas pelo projeto.
- Navegador: fluxos online/offline; comparação visual no Windows com as referências existentes.
- Instalação em raiz e sob `/SGI`, incluindo login, redirecionamentos, assets e aliases.
- Upgrade e migração interrompida do T26.

Não alterar o CI só para retirar o motor/plataforma que falhou. Se uma ferramenta de teste de travas precisa de consultas específicas por motor, mantê-las no helper e exercitar ambas.

### Subdiretório

Iniciar um servidor separado com `SGI_BASE_PATH=SGI`; apontar `SGI_BASE_URL` para esse prefixo e executar `deployment-paths.spec.cjs`. Não executar o teste da raiz contra esse servidor usando URL sem prefixo e interpretar redirects como aprovação.

### Recuperação, somente no banco de teste

1. Gerar backup sintético com schema, dados e triggers antes do upgrade. Guardar hash e versão do código em `test-results/`.
2. Aplicar upgrade e mudanças de teste; restaurar o backup em outro banco isolado com nome confirmado.
3. Comparar matrículas, hashes, total por origem, registros de sincronização e arquivos de teste associados.
4. Documentar se o código anterior consegue ler o schema novo. Não assumir que reverter Git desfaz migrações nem que restaurar banco preserva mutações recebidas depois do backup.
5. Descrever como suspender escritas, preservar fila/novas mutações e escolher a fronteira de retorno. Não executar esse procedimento em produção.
6. Para pódios legados, incluir diagnóstico, arquivo de adoção conferido e validação dos totais antes de liberar alterações históricas.

**Aceitação:** matriz efetivamente executada ou pendência externa claramente indicada; procedimento de recuperação ensaiado em dados sintéticos. A existência do arquivo de CI não comprova aprovação remota.

## T29 — Revisar implementação e entregar evidência final

### Passos

1. Conferir STATUS tarefa por tarefa; nenhuma pendência pode ser marcada concluída apenas para encerrar o trabalho.
2. Pesquisar os caminhos antigos corrigidos: soma de pódio apenas na primeira finalização; `getMessage()` em captura ampla; validação de limite em `generateName`; `saveTimers` descartando gravações; `lista[0]` sem conferir ID da ocorrência; UPDATE de edição independente.
3. Pesquisar todas as escritas em `pontuacao_turma` e confirmar que seguem delta/origem ou ajuste explícito. Não deixar uma rotina antiga somando em paralelo ao serviço novo.
4. Conferir que os aliases apontam para controllers compostos com serviços novos, não para adaptadores esquecidos.
5. Confirmar que novas migrações são aditivas/versionadas e que as migrações antigas não mudaram.
6. Executar todos os checks abaixo de forma sequencial e parar para investigar qualquer falha:

```text
composer verify
npm run build
npm run check
npm test
php tests/run_all.php
npm --prefix tests/browser test
git diff --check
```

7. Inspecionar diff final e arquivos não versionados. Remover somente artefatos temporários próprios que não sejam evidência necessária; não apagar trabalho alheio. Dados/logs de testes devem permanecer em diretórios ignorados.
8. Atualizar `docs/architecture.md`, `docs/testing.md`, `docs/deployment.md` e o documento de conclusão com o estado real. A auditoria permanece como registro histórico dos defeitos anteriores; acrescentar ligação para a correção, não reescrever a história dizendo que nunca existiram.
9. Registrar no STATUS o commit de referência, alterações locais, versão dos ambientes, resultados, migrações e limitações. Não incluir credenciais/tokens.
10. Encerrar apenas os servidores/processos de teste iniciados por esta execução, verificando PIDs/instâncias. Não desligar serviços que já estavam em uso por outro trabalho.
11. Entregar ao usuário um resumo do que mudou, testes, migrações e pendências operacionais. Não fazer push/deploy sem instrução correspondente.

## Mapa final de rastreabilidade

| Achado | Tarefas que o resolvem | Evidência mínima |
| --- | --- | --- |
| A1 | T02–T05, T22 | Matriz HTTP por perfil/edição + banco sem escrita em recusas |
| A2 | T12–T13 | Revalorização/estorno, incluindo frações |
| A3 | T14–T16 | Troca de pódio e rollback, individual/online/offline |
| A4 | T07–T09 | Saldo persistido após pausa/retomada/navegação/replay |
| A5 | T10 | Clique e navegação imediata preservam placar |
| A6 | T11 | Ocorrência alvo correta no IndexedDB e servidor |
| A7 | T13, T18–T20, T24 | Processos concorrentes + limites finais no banco |
| A8 | T17 | Histórico/ranking com parcelas reconciliadas |
| A9 | T21, T23 | Caminhos de ativação convergentes e no máximo uma edição ativa |
| A10 | T06 | Falhas de banco retornam resposta segura e rollback |
| Fronteiras | T22–T25 | Serviços testáveis sem banco e composição correta |
| Upgrade/compatibilidade | T26–T28 | Base anterior, migrações, filas/cache antigos e restauração |

Se houver evidência ausente, descrevê-la como pendência concreta. Não substituir “não foi possível validar” por “está tudo correto”.
