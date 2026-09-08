# Status da implementação offline HTTP

Atualizado em 08/09/2026.

## Concluído nesta entrega

- O navegador agora distingue `navigator.onLine` do servidor SGI local por uma sondagem do endpoint `/api/v1/health`, sem consultar a internet.
- Ao preparar o modo offline ou retomar a sincronização, o cliente confirma também `/api/v1/session`; sessão expirada fica separada de servidor indisponível e não dispara reenvio cego.
- A sondagem usa `cache: no-store`, valida JSON (`success`, `status` e `service`) e possui timeout próprio. A resposta PHP também envia `Cache-Control: no-store` e `Pragma: no-cache`.
- O endereço da sondagem acompanha instalações em subdiretório (`SGI_BASE_PATH`) usando a origem do asset carregado.
- GETs e reenvios de mutações possuem timeout; uma falha mantém a intenção na fila.
- Sincronizações concorrentes na mesma origem/operador usam uma reserva transacional no banco IndexedDB auxiliar `sgi_offline_coord`, com expiração e fallback seguro quando esse banco não pode ser aberto.
- BroadcastChannel avisa outras abas sobre mudança da fila e servidor recuperado; a exclusão mútua não depende do canal.
- O estado público inclui servidor acessível/indisponível e erro de diagnóstico.
- A casca exige páginas obrigatórias com `schemaVersion` e `pageSources` para mostrar “pronto para offline”; uma agenda isolada ou uma marca antiga não é suficiente.
- A fila pode ser exportada para JSON sem cookies, senhas ou tokens CSRF. O arquivo preserva as identidades das mutações, rejeita importação de outro operador e pode ser importado pelo próprio banner offline.

## Validação executada

- `npm run check`
- `npm test`
- `npm --prefix tests/browser test -- offline-queue-regression.spec.cjs` (17 testes)
- `npm --prefix tests/browser test -- mesario-offline.spec.cjs`
- `npm --prefix tests/browser test -- legacy-offline-compat.spec.cjs`
- Suíte completa de navegador com `SGI_E2E_RESET=1` (47 testes, executada antes da última rodada de endurecimento de sessão/reserva; as regressões direcionadas foram repetidas depois).

Os testes de regressão offline cobrem timeout/servidor local, sessão expirada, respostas inválidas, dependências temporárias, aborto de transação local, chaveamento por rota v1, duas abas sincronizando simultaneamente e importação/exportação da fila.

## Ainda limitado por HTTP sem servidor

- F5, nova aba ou reabertura depois de fechar o navegador sem acesso ao servidor não são garantidos. A casca preparada continua sendo a unidade de execução offline.
- Background Sync, Service Worker e Web Locks não são usados.
- Conflitos entre dois dispositivos que alteram o mesmo jogo ainda dependem da idempotência da mutação; não há controle de versão concorrente no servidor.
- A casca depende de ter sido preparada enquanto o servidor estava acessível; o navegador não promete reconstruir a aplicação inteira depois de limpar o armazenamento local.

## Próxima etapa recomendada

Se o fluxo crescer, adicionar uma tela de pendências com lista por partida e reautenticação. O banner já oferece tentativa manual, exportação e seleção de arquivo para importação usando `SGIOffline.getPendingList()`, `syncNow()`, `downloadPending()` e `importPending(file)`; a tela não deve remover registros diretamente do IndexedDB.
