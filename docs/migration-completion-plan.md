# Conclusão da migração do SGI

Data da verificação estrutural: 06/09/2026. Branch: `codex/refatoracao-arquitetura-limpeza`.

## Atualização funcional do plano Luna — 08/09/2026

As tarefas T00–T29 do plano de implementação foram executadas no mesmo branch e registradas em [`docs/plano-implementacao-luna/STATUS.md`](plano-implementacao-luna/STATUS.md). A validação local final aprovou `380/380` asserções HTTP, `163` testes PHPUnit com `1.815` asserções, PHPStan `213/213`, PHP CS Fixer `275/275`, `16/16` testes JavaScript e `32/32` cenários Playwright, incluindo operação online/offline, aliases, fila legada, subdiretório `/SGI` e o ensaio sintético de backup/restauração.

Durante a revisão final foi reproduzida e corrigida uma corrida em que o timer de fechamento de uma ocorrência anterior fechava um novo modal de edição offline. O fluxo de duas ocorrências, criação temporária, edição antes do POST, sincronização e inativação passou depois da correção. A auditoria original continua preservada como histórico dos defeitos que motivaram o plano.

A matriz CI continua sendo a evidência prevista para PHP 8.2/8.4, MySQL 8.4, MariaDB 10.11 e comparação visual Windows. Esses alvos externos não foram executados neste host local e permanecem pendência operacional explícita; não houve publicação nem deploy.

## Estado atual

A migração de código foi concluída. As sete APIs que ainda executavam arquivos
procedurais foram transferidas para controladores versionados e os aliases
antigos continuam apontando para os mesmos casos de uso. Os arquivos
procedurais removidos não participam mais do fluxo HTTP, e a conexão global
`config/db.php` foi eliminada em favor de `ConnectionFactory`.

Os módulos finais são Acesso, Eventos, Participantes, Competicoes, Resultados,
Disciplina e Sincronizacao. SQL e transações ficam na infraestrutura; serviços
e regras de negócio continuam independentes de HTTP e MySQLi.

## Entregas realizadas

- Histórico de turma em `Resultados`, com autorização do aluno e resposta compatível.
- Inscrições em `Participantes`, com validação, limite de três modalidades, transação e confirmação dos vínculos persistidos.
- Usuários em `Acesso`, incluindo listagem, criação/edição de alunos, colaboradores, validação de inscrição, importação, redefinição de senha, exclusão e atualização da edição.
- Jogos, partidas, placar, IDs temporários e avanço de chaveamento em `Competicoes`, com resolução transacional e idempotência.
- Sincronização de chaveamento em `Sincronizacao`, preservando as identidades de mutação e os caches offline.
- Proteção por edição real para jogos, resultados, partidas e ocorrências de turma quando operados pelo mesário.
- Ciclo de vida JavaScript corrigido para não colidir closures nem acumular eventos ao reativar telas; a identidade de mutação é persistida antes do reenvio offline.
- Teste arquitetural ampliado para todos os arquivos de `Presentation`.
- Árvore física legada removida: `views/` e o redirecionador raiz `index.php` não fazem parte do pacote; as URLs antigas continuam sendo atendidas pelo front controller e pelos aliases configurados.
- Adaptadores de importação e compatibilidade sem consumidores foram removidos: o diretório `api/`, seu conversor PDF, o bootstrap legado e classes auxiliares não fazem parte do pacote.
- Documentação de arquitetura atualizada e aliases antigos preservados.

## Evidência local

| Verificação | Resultado |
| --- | --- |
| `composer verify` | 116 testes PHPUnit, 1.514 asserções; lint, PHPStan e CS Fixer aprovados |
| `php tests/run_all.php` | 224/224 asserções HTTP aprovadas |
| `npm run check` | 39 arquivos JavaScript válidos |
| `npm test` | 5/5 testes JavaScript aprovados |
| `npm run build` | 230 assets preparados |
| `npm --prefix tests/browser test` | 26/26 testes Playwright aprovados |
| `git diff --check` | sem erros de whitespace |

Os testes HTTP e de navegador foram executados contra MariaDB isolado, com
servidor local e dados de demonstração. Os cenários incluem autenticação,
RBAC, importação PDF, inscrições, agenda, placar, ranking, migrações,
fronteira pública e torneio completo online/offline.

## Finalização operacional

Estas atividades não exigem nova refatoração de código, mas devem ser feitas
antes da publicação:

1. Executar a matriz de CI em PHP 8.2 e 8.4, MySQL 8.4 e MariaDB 10.11, além do navegador Windows.
2. Ensaiar instalação nova e atualização sobre uma cópia representativa da base anterior; conferir índices, senhas, matrículas por edição, triggers, arquivos persistentes e filas IndexedDB.
3. Simular backup, restauração e retorno para a versão anterior, registrando o pacote, a configuração e as condições de rollback.
4. Confirmar com a operação que nenhum consumidor externo dependia do conversor PDF removido; a rota oficial é `/api/v1/importacoes/turma-pdf` e seu alias `/api/upload_turma_pdf.php`.
5. Publicar os commits desta etapa após a revisão do diff e guardar os relatórios das execuções acima junto ao registro de implantação.

## Critério de encerramento

- [x] As sete APIs foram migradas e todas as ações de usuários foram mantidas.
- [x] URLs antigas e versionadas usam os mesmos controladores e IDs de mutação.
- [x] Não há executor procedural nem conexão global no fluxo HTTP.
- [x] As camadas de negócio não dependem de HTTP, sessão ou MySQLi.
- [x] Inscrições, placar, avanço e sincronização têm testes de persistência, atomicidade e reenvio.
- [x] Eventos JavaScript distintos e reativação de telas têm cobertura.
- [x] Acesso por edição real foi aplicado nos fluxos de operação do mesário.
- [x] Suítes locais PHP, HTTP, JavaScript e navegador estão verdes.
- [ ] Matriz de ambientes, atualização, restauração e rollback registrados em CI e no procedimento de implantação.

As alterações de esquema continuam sujeitas à regra existente: migrações já
aplicadas não devem ser reescritas; qualquer ajuste futuro entra como nova
migração com teste de repetição.
