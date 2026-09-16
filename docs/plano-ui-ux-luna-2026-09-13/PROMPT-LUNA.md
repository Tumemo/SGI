# Prompt para executar posteriormente com Luna

Copie o texto abaixo como instrução da implementação na tarefa escolhida. Esta auditoria não iniciou outra tarefa nem implementou as correções.

---

Implemente as melhorias de UI/UX do SGI descritas em `docs/plano-ui-ux-luna-2026-09-13/`. Leia o `AGENTS.md` atual, o `README.md` da auditoria, `01-inventario.md`, `02-plano-luna.md` e `03-validacao.md` antes de editar.

O plano foi preparado a partir do checkout `914ff13c`. Confira o código atual e preserve alterações posteriores. Reproduza cada problema antes de corrigi-lo; se ele já tiver sido resolvido, registre a evidência e não refaça a mudança.

Execute E00–E11 na ordem e conforme dependências do plano. Comece pelos P1: sidebar/offset do aluno, overflow em retrato, login, teclado e formulários, gestão de turmas compacta, conclusão/ativação de edição e confirmação de importação. Cada etapa especifica onde editar, como implementar, comportamento a preservar e critérios de aceite. Não se limite a mudar cores ou fazer ajustes cosméticos.

Preserve Bootstrap local, identidade SESI, breakpoint de 1200px, `Assets`/`Url`, runtime de página, papéis/permissões, edição selecionada, regras de inscrição/primeiro acesso e contratos offline. Edite fontes em `resources/`; gere assets pelo build. Não introduza framework, endpoint procedural, migração, dependência ou nova regra de negócio por conveniência visual. Não limpe IndexedDB nem filas pendentes.

Faça regressões na camada apropriada junto com cada correção. Use ambiente isolado pelos executores oficiais. Registre a linha de base completa antes das refatorações; após cada mudança funcional, execute a regressão específica e depois `all -IncludeVisual`, conforme AGENTS. Teste os quatro perfis, retrato/paisagem/desktop, teclado, modais, erros, raiz/subdiretório e reentrada offline. O resultado de 101 testes JavaScript e as 165 renderizações estáticas registrados na auditoria não substituem sua validação da aplicação funcionando.

Use as capturas da auditoria como evidência do problema anterior, não como snapshots de aceite. Inspecione a aparência corrigida antes de atualizar referências visuais. Não enfraqueça testes nem copie snapshots entre plataformas para obter aprovação.

Crie um registro de progresso junto ao plano, indicando para cada etapa: estado, achados atendidos, arquivos, testes, evidências e pendências. Prossiga pelas etapas até concluir o escopo; se um pré-requisito impedir validação, documente o erro exato, conclua o trabalho independente possível e não declare aprovação parcial como completa. Ao entregar, resuma mudanças, comandos/resultados e limitações.
