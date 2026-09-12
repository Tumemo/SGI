# Plano de melhorias e correções — Luna

Data: 11/09/2026. Base auditada: `f4c3f4902effde9a5e2e862d5481dd13909673f6`.

Este plano é para implementação local pelo Luna. Sua criação não muda o modelo, não cria outra tarefa e não inicia a implementação. O roteiro anterior em `docs/plano-implementacao-luna` está concluído e serve como histórico.

Leia primeiro `AGENTS.md`, [STATUS](STATUS.md) e a etapa atual. A justificativa está na [auditoria](../auditoria-codigo-2026-09-11.md). O [plano T00–T29](../plano-implementacao-luna/README.md) está concluído e é mantido como arquivo histórico; não o trate como trabalho pendente. Não é necessário carregar todo o código ou o plano antigo em cada retomada.

## Ordem de execução

| Ordem | Tarefa | Prioridade | Dependência | Entrega |
| --- | --- | --- | --- | --- |
| 0 | N00 — Base de comparação e ambiente | Preparação | Nenhuma | Situação inicial registrada sem tocar dados de trabalho |
| 1 | N01 — Qualidade e testes confiáveis | P2 | N00 | Estilo reproduzível, pós-condições testadas, cleanup confiável |
| 2 | N02 — Escopo de pontos | P1 | N01 | Mesário não consulta/altera pontos de outra edição |
| 3 | N03 — Publicação do ranking | P1 | N01 | Aluno só consulta ranking encerrado e publicado |
| 4 | N04 — Autoria offline | P1 | N02 | Autor/anulador derivados de contexto confiável |
| 5 | N05 — Elegibilidade de inscrição | P1 | N01 | Gênero/categoria protegidos no servidor |
| 6 | N06 — Retry de inscrição | P2 | N05 | Repetição informa inscrição existente sem duplicação |
| 7 | N07 — Erros seguros | P2 | N05 | SQL/parser internos não aparecem nas respostas |
| 8 | N08 — Entradas de resultado | P2 | N04 | Placar e eventos inválidos rejeitados integralmente |
| 9 | N09 — Limites de modalidade | P2 | N05 | Limites inválidos não viram ilimitados |
| 10 | N10 — Vínculos entre edições | P2 | N09 | Cadastros/updates não deslocam vínculos históricos |
| 11 | N11 — Anulação × finalização | P2, investigar primeiro | N02, N04, N08 | Intercalação concorrente reproduzida e protegida, ou hipótese refutada |
| 12 | N12 — Concorrência de agendamento | P2, investigar primeiro | N01 | Conflitos manuais/lote protegidos por protocolo comum |
| 13 | N13 — Validação e documentação | Obrigatória | Todas anteriores | Suítes e matriz disponíveis, relatório final e retomada exata |

Detalhamento:

1. [Preparação e correções prioritárias — N00 a N07](01-preparacao-e-seguranca.md).
2. [Integridade, concorrência e entrega — N08 a N13](02-integridade-e-entrega.md).

Executar sequencialmente, uma tarefa por vez. Em N01, separar normalização de estilo, melhoria dos testes e manutenção do executor em diffs revisáveis. Em N10, separar modalidades e turmas. Não criar uma refatoração geral enquanto corrige um contrato pequeno.

## Regras de implementação

- Reconfirmar o achado no checkout corrente. Se uma correção posterior já o resolveu, demonstrar com teste e registrar como atendido; não implementar outra solução.
- Antes de editar, executar testes relevantes existentes. Para refatoração, registrar `all` antes e depois; se o estado inicial falhar, registrar o motivo e não chamá-lo de aprovado.
- Escrever regressão que falhe pelo comportamento esperado, fazer a menor correção e executar regressão seguida de `all`. Acrescentar visual quando mudar aparência.
- Preservar envelopes públicos, permissões administrativas, strings inteiras válidas, status/aliases necessários, identificadores e bodies já persistidos de mutações.
- Autorização usa recurso do banco. Validar dentro do trecho protegido quando houver risco de corrida; SQL/locks na infraestrutura, contexto/regras em Application/Domain, composição em `config/routes.php`.
- Reutilizar `TransactionRunner`, savepoints, `MutationAction`, `CompetitionAccess`, `EdicaoAccessPolicy` e contratos existentes. Não resolver transação aninhada com `begin_transaction()` avulso.
- Não remover cache/fila/IndexedDB, mudar schema offline sem estratégia, desativar CSRF, ampliar exceções arquiteturais ou editar migrações aplicadas. Não renormalizar SQL histórico para corrigir estilo PHP.
- Nenhum seed/reset em base de trabalho, nenhum deploy/push/merge. Não redefinir contas nem publicar servidor externo.
- Teste novo precisa ser descoberto: PHPUnit conforme `phpunit.xml`; JavaScript em `tests/javascript/*.test.cjs`; integração conforme includes/chamadas de `tests/run_all.php`; navegador conforme configuração Playwright existente.
- N11/N12 começam por reprodução. Não inventar prova de concorrência usando atrasos arbitrários; usar processos/conexões independentes e barreiras do suporte existente. PHP com uma única sessão pode serializar requisições e mascarar a disputa.
- Não transformar melhorias opcionais em bloqueio. Não adicionar dependências/frameworks nem ampliar escopo para reescrever a aplicação.

## Comandos de validação

Usar o PHP disponível e configurar credenciais de teste somente no processo, conforme `docs/testing.md`. Os comandos abaixo foram confrontados com os scripts atuais; nunca apontá-los para uma base de trabalho.

```powershell
# Qualidade sem banco
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite quality

# Cobertura completa isolada
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local

# Acrescentar para mudança visual
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local -IncludeVisual

# Matriz SQL, quando o Docker estiver acessível
powershell -File tools/test-docker.ps1 -Database mariadb
powershell -File tools/test-docker.ps1 -Database mysql

git diff --check
```

Não executar integração ou Playwright soltos sem o preparo documentado. Para teste direcionado de HTTP/navegador, seguir o fluxo manual isolado do guia; o runner local não oferece parâmetro de filtro de cenário. Não executar duas suítes que alterem banco simultaneamente.

## Prompt para o Luna

> Implemente o plano `docs/plano-melhorias-luna-2026-09-11/README.md` em `C:\Projetos\SGI`. Leia `AGENTS.md`, o README do plano, `STATUS.md` e somente a etapa atual. Comece pela primeira tarefa pendente; reconfirme cada achado no código atual, crie uma regressão descoberta pelos executores existentes e demonstre a falha antes da correção quando viável. Corrija localmente, execute a regressão e as suítes exigidas e atualize STATUS com evidência real e próximo passo. Priorize escopo de pontos, publicação do ranking, autoria offline e elegibilidade de inscrição. Preserve dados de trabalho, migrações aplicadas, CSRF, regras entre camadas, envelopes, filas e identificadores de mutação. N11 e N12 são hipóteses de concorrência: reproduza antes de alterar. Não refaça o plano antigo concluído, não enfraqueça testes, não publique nem faça push. Continue nas tarefas autorizadas sem pedir confirmações rotineiras. Se faltar ambiente, registre exatamente o que não foi validado e avance apenas no trabalho independente. Não encerre como implementado um item apenas planejado.

## Critério de conclusão

N00–N13 com estado e evidências; C01–C10 cobertos por testes ou resolvidos com evidência específica; R01/R02 reproduzidos e corrigidos ou refutados com teste determinístico. Qualquer alvo não executado permanece pendência explícita. Suíte parcial não é aprovação de integração/navegador/CI.
