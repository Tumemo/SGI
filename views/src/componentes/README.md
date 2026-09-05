# Componentes JavaScript compartilhados

Os componentes são carregados pelos cabeçalhos das páginas e não devem assumir
que uma tela específica esteja presente no DOM.

## Responsabilidades

- `offline-core.js`: interceptação de rede, cache e fila de sincronização;
- `mesario-data.js`: persistência local e projeções do mesário;
- `mesario-offline.js`: shell SPA e preload de páginas;
- `chaveamento-engine.js`: avanço local de mata-mata e IDs temporários;
- `Comandooffline.js`: compatibilidade de formulários offline legados.

Ao alterar qualquer componente, executar `frontend-regression.spec.cjs` e
`tournament-offline.spec.cjs`. A fila deve continuar aceitando mutações com
IDs temporários negativos até a resolução no servidor.
