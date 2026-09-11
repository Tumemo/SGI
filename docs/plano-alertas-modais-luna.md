# Plano de substituição dos diálogos nativos por modais SGI

Destinatário: agente Luna. Status: implementação concluída neste checkout; manter como referência de arquitetura e regressão.

## 1. Objetivo e limites

Substituir todos os `alert()` e `confirm()` da aplicação por modais responsivos, acessíveis e coerentes com a identidade visual do SGI, mantendo as regras de negócio, permissões e comportamento online/offline. A busca inicial em `resources/` encontrou **91 alerts e 18 confirms em 22 arquivos**, sem `prompt()`. Revalidar esse inventário antes de implementar, pois o checkout contém alterações em andamento.

O escopo é o diálogo nativo de mensagem ou confirmação. Preservar toasts existentes, avisos inline, validações junto aos campos e modais de formulários. Não converter indiscriminadamente classes Bootstrap `.alert` em diálogos. Modais de confirmação já existentes só precisam de harmonização visual e integração quando estiverem no fluxo migrado. Não alterar banco, endpoints ou contratos de sincronização.

## 2. Base existente e direção visual

- Bootstrap 5.3.8 já é dependência local; usar `bootstrap.Modal`, sem adicionar SweetAlert ou CDN.
- `resources/scss/_theme.scss` define vermelho principal `#e30613`, fonte Inter com alternativas de sistema e fundo `#f8f9fa`. Consumir as variáveis Bootstrap correspondentes, evitando duplicar esses valores.
- `resources/scss/shared.scss` e o bundle `shared` de `tools/css-bundles.json` atendem os dois portais. Concentrar ali os estilos específicos do novo componente.
- Os modais de `resources/views/pages/participantes/turma-alunos.php` já usam centralização e `rounded-4`; aproveitar essa linguagem visual.
- `resources/js/shared/bootstrap-feedback.js` expõe `SGI.showToast` e já é incluído nos heads administrativo e do aluno. Estendê-lo com a API de diálogos, preservando os toasts.

Especificação visual proposta:

| Elemento | Definição |
| --- | --- |
| Superfície | Card claro, cantos `rounded-4`, sombra discreta e backdrop Bootstrap |
| Tamanho | Largura máxima aproximada de 440px; margens de 16px em telas pequenas; corpo rolável em mensagens extensas |
| Conteúdo | Ícone semântico, título curto, descrição alinhada à esquerda e ações no rodapé |
| Tipografia | Fonte do tema; título destacado, corpo com legibilidade e quebras de linha preservadas |
| Variantes | Sucesso, informação, atenção e erro; cor semântica no ícone e pequeno destaque, sem pintar todo o card |
| Ações | Primária na cor do tema; destrutiva `btn-danger`; cancelamento `btn-outline-secondary` |
| Rótulos | “Entendi”, “Cancelar”, “Excluir modalidade”, “Encerrar jogo”, “Sair”; evitar “Sim/Não” genérico |
| Mobile | Botões confortáveis para toque, empilhados se necessário; sem corte horizontal |

Não fechar mensagens automaticamente. Cor e ícone não substituem o texto. Respeitar `prefers-reduced-motion` e funcionar com fonte de sistema quando offline.

## 3. Contrato do componente compartilhado

API proposta, a implementar:

```js
await SGI.alert({ titulo: 'Resultado salvo', mensagem, tipo: 'success' });
const autorizado = await SGI.confirm({
    titulo: 'Excluir modalidade?',
    mensagem: 'Esta ação não pode ser desfeita.',
    tipo: 'warning',
    textoConfirmar: 'Excluir modalidade',
    destrutivo: true
});
if (!autorizado) return;
```

Requisitos:

1. `SGI.alert` retorna Promise resolvida ao fechar; `SGI.confirm` retorna Promise booleana, verdadeira somente por confirmação explícita. Fechar, Escape ou desativar a página significa cancelar.
2. Não sobrescrever `window.alert` ou `window.confirm`: uma Promise não preserva o contrato síncrono dessas funções. Migrar cada chamada e seus chamadores explicitamente.
3. Criar DOM com APIs seguras e `textContent`, inclusive para nomes vindos do servidor e senhas temporárias. Preservar novas linhas com CSS. Não aceitar HTML arbitrário na mensagem.
4. Manter um único diálogo de feedback ativo e uma fila para chamadas concorrentes. Resolver cada Promise exatamente uma vez, após a transição de fechamento. Limpar conteúdo sensível ao fechar.
5. Identificar título e descrição com ARIA, manter foco dentro do modal e devolver foco ao acionador quando ele ainda existir. Foco inicial em “Cancelar” para operações destrutivas; em “Entendi” para mensagens. Backdrop estático para evitar fechamento acidental; Escape cancela/fecha, nunca confirma.
6. Proteger a ação de origem contra cliques repetidos desde a abertura da confirmação até o término da operação. Falha de inicialização nunca deve autorizar a ação; não usar fallback de diálogo nativo.
7. Integrar a limpeza com `SGIPage`/`onDeactivate`: cancelar confirmações pendentes, descartar mensagens obsoletas e remover listeners/backdrops sem interferir em outras telas. Após um `await`, conferir se o contexto continua ativo antes de navegar ou executar mutações.
8. Não empilhar modais Bootstrap. Se a mensagem surgir de um formulário aberto, suspender o modal de origem, aguardar `hidden.bs.modal`, apresentar o feedback e restaurar o formulário com valores e foco quando o fluxo ainda exigir correção. Se o formulário foi concluído, não reabri-lo. Coordenar foco e backdrop em um ponto compartilhado.
9. Os heads carregam o helper e o bundle Bootstrap é incluído em `resources/views/components/footer.php`. Auditar a ordem real: o helper pode ser declarado antes, mas a primeira abertura precisa esperar Bootstrap e DOM disponíveis. Cobrir também validações executadas na inicialização de página.

## 4. Inventário e ordem de migração

Todos os caminhos JS abaixo são relativos a `resources/js/`.

| Lote | Arquivos com chamadas nativas | Atenção principal |
| --- | --- | --- |
| A — acesso | `pages/acesso/colaboradores.js`, `pages/acesso/perfil.js`, `pages/aluno/perfil.js` | Remoção de colaborador/foto; cancelamento não envia requisição |
| A — navegação | `resources/views/components/admin-nav.php`, `resources/views/components/aluno-nav.php` | Remover `onclick` com `return confirm`; preservar destino e confirmação de saída |
| B — eventos | `pages/eventos/categorias.js`, `configurar-categorias.js`, `configurar-turmas.js`, `configurar-equipes.js`, `configurar-locais.js`, `configurar-pontuacao.js`, `configurar-arrecadacao.js`, `configurar-agenda.js`, `lista.js` | Exclusões, restauração de valores, upload, agenda, arrecadação e redirecionamentos |
| C — competição e disciplina | `pages/competicoes/modalidades.js`, `modalidade-detalhes.js`, `elenco-equipe.js`, `chaveamento.js`, `pages/disciplina/ocorrencias.js` | Redistribuição, remoção, estados offline e erros de consulta |
| D — participantes | `pages/participantes/turma-alunos.js` | Senhas temporárias e transição a partir de modais existentes |
| E — operação offline | `pages/competicoes/placar.js`, `offline/offline-form.js` | Persistência, finalização, artilharia, ocorrências e avanço de chave |

Os nomes abreviados no lote B pertencem todos à pasta `pages/eventos/`. Confirmar os demais arquivos alcançados por novas buscas; números e lista são um retrato do checkout, não uma dispensa de varredura final.

## 5. Cuidados obrigatórios nos fluxos

- **Logout:** interceptar o clique com `preventDefault()` antes de qualquer `await`; confirmar e só então navegar para o destino já existente. Considerar desktop, mobile, reinicialização SPA e acionamento por teclado. Não retornar uma Promise em `onclick` esperando impedir navegação.
- **Mensagens antes de navegação:** por exemplo, `configurar-turmas.js` informa ausência de interclasse e redireciona. Aguardar o fechamento antes do redirecionamento. Auditar também reload, fechamento de modal, refresh de listagem e código subsequente em cada ocorrência.
- **Promises e callbacks:** transformar os handlers necessários em `async` ou encadear a Promise e retorná-la. Tratar rejeições nos chamadores e preservar `return`, `catch`, `finally` e liberação de botões. Não fazer substituição textual automática global.
- **Placar:** verificar estado novamente após a confirmação, porque cronômetro, sincronização e outras ações continuam enquanto o modal está aberto. Preservar travas de finalização; cancelar não pode concluir jogo nem gravar resultado final. Não pausar o relógio automaticamente por abrir um modal.
- **Offline:** informar sucesso apenas após persistência local confirmada; preservar mensagens distintas de resultado local, adversário pendente e campeão definido. Falha local mantém jogo em andamento. A confirmação visual nunca deve antecipar avanço ou provocar uma segunda mutação.
- **Senhas temporárias:** apresentar o valor completo, selecionável e sem expiração automática; preservar distinção entre cadastro e reset. Não armazenar a senha no cache, logs ou fila de diálogos após fechamento/desativação.
- **Textos de erro:** manter informação útil em português claro. Substituir mensagens que expõem caminhos internos por orientação acionável, sem inventar sucesso ou esconder a natureza da falha.

## 6. Entrega em etapas para Luna

1. Ler `AGENTS.md`, `docs/testing.md` e as alterações atuais com `git diff`. Preservar trabalho existente. Recontar as ocorrências em fontes JS, templates PHP e demais entradas da aplicação, distinguindo métodos PHP homônimos de diálogos do navegador.
2. Executar a baseline completa no ambiente isolado descrito em `docs/testing.md`, antes da refatoração. Registrar falhas preexistentes.
3. Implementar API, estilos compartilhados, ciclo de vida e testes focados do componente. Validar uma mensagem, uma confirmação e um erro originado em formulário aberto, nos dois portais.
4. Migrar os lotes A a D, revisando a sequência assíncrona em cada chamada e atualizando os testes de fluxo correspondentes.
5. Migrar o lote E e verificar a integração com `resources/js/offline/mesario-offline.js`. O helper e CSS devem estar disponíveis na casca preparada, sem novas dependências de rede. Se necessário, atualizar somente a versão da casca pelo mecanismo existente; preservar stores, fila e identificadores de mutação. Não prometer cold-open offline, não suportado pelo fluxo atual.
6. Executar `npm run build` para gerar `public/assets/`; nunca editar os arquivos gerados manualmente. Rodar a verificação completa e inspecionar visualmente desktop/mobile. Atualizar referências visuais apenas após revisar a mudança.
7. Entregar relação de arquivos alterados, evidências de testes, imagens dos estados principais e limitações reais. Não incluir alterações de domínio alheias a este plano.

## 7. Testes e aceite

Atualizar testes que aceitam diálogos nativos para localizar o modal visível por papel, título e botão. Foram identificadas dependências em `admin-lifecycle.spec.cjs`, `aluno-portal.spec.cjs`, `auth-rbac.spec.cjs`, `mesario-offline.spec.cjs` e `tournament-offline.spec.cjs`. Preservar a intenção de `clock-persistence.spec.cjs`, que exige ausência de diálogos inesperados. Auditar também mocks e expectativas nos testes JavaScript.

Cobertura comportamental mínima:

- Cancelar/Escape/fechar não dispara mutação; confirmar dispara exatamente uma, inclusive com clique duplo.
- Duas mensagens concorrentes aparecem em sequência e ambas as Promises terminam.
- Feedback vindo de formulário preserva dados, foco e capacidade de corrigir/enviar novamente, sem backdrops presos.
- Conteúdo com tags HTML é exibido como texto; nomes longos e mensagens multilinha não rompem o layout.
- Teclado, restauração de foco, rolagem e viewport pequena funcionam; testar os perfis administrador, colaborador, mesário e aluno conforme suas permissões.
- Alertas antes de redirecionamento permanecem visíveis até confirmação; sair só navega após confirmar.
- Reentrar na mesma tela SPA não duplica handlers; sair durante confirmação não executa ação na tela antiga.
- Placar online/offline preserva relógio, fila, resultado, avanço e sincronização única. Cobrir falha de armazenamento e confirmação cujo estado mudou enquanto aberta.
- Nenhum diálogo nativo é emitido nos cenários migrados. Adicionar verificação estática das chamadas nativas nas fontes próprias, sem falsos positivos em métodos PHP ou bibliotecas de terceiros.

Verificação obrigatória antes e após a refatoração, com servidor/banco isolados conforme `docs/testing.md`: `php tests/run_all.php`, `composer verify`, `npm run check`, `npm test` e `npm --prefix tests/browser test`. Usar o executor documentado para preparar esse ambiente; não apontar o runner para a base de trabalho. Executar também o build e a inspeção visual dos novos modais.

O trabalho estará concluído quando não restarem chamadas a diálogos nativos na aplicação, todos os fluxos inventariados estiverem migrados, o componente seguir o tema compartilhado, o modo offline permanecer funcional e as verificações obrigatórias tiverem resultado documentado. Esta implementação atende à guarda estática e aos testes do componente; a suíte HTTP/browser completa ainda depende do servidor e banco isolados descritos em `docs/testing.md`.
