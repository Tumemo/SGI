# Organização da camada de apresentação

`views/src` mantém as telas PHP existentes para preservar as URLs consumidas
pelos navegadores e pelo modo offline. A organização esperada para novas telas é:

- `pages/`: páginas renderizadas e controladores de apresentação;
- `pages/alunos/`: portal do competidor, isolado das telas administrativas;
- `pages/componentes/`: cabeçalho, navegação e componentes PHP reutilizáveis;
- `componentes/`: módulos JavaScript compartilhados, incluindo o runtime
  offline do mesário;
- `styles/`: folhas de estilo globais e específicas.

Novos arquivos devem usar nomes minúsculos em `kebab-case`. Os arquivos
existentes com nomes históricos, como `Comandooffline.js`, permanecem como
compatibilidade até que todas as referências sejam migradas em uma etapa
dedicada. Nenhum componente offline deve alterar o nome dos endpoints sem
atualizar também a fila de sincronização e seus testes de navegador.
