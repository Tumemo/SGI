# Progresso — cronograma anterior às inscrições

## Referência e estado

- Plano criado em 21/09/2026.
- Checkout de referência: `c8e9c1be85a49bccfa0c6cca88db97d11f33ed2f`.
- Estado da funcionalidade: **implementação parcial; aceite do roteiro pendente**.
- Baseline funcional: **validado em 21/09/2026** no Compose descartável MariaDB 10.11/PHP 8.4, com visual.
- Inspeção inicial: árvore de trabalho limpa na origem da branch; leitura de README, AGENTS, guias, exemplos de planos, schema, rotas e pontos centrais de modalidade/equipe/inscrição/agenda.
- Escopo confirmado: sem cabo de guerra, sem disputa entre categorias; recursos compartilhados continuam sujeitos a conflitos.

## Tarefas

| Tarefa | Estado | Arquivos/decisões | Testes/evidências | Próxima ação |
| --- | --- | --- | --- | --- |
| T00 | Validada | Branch `codex/cronograma-inscricoes-luna-2026-09-21`; mapa inicial em `02-contratos-e-arquitetura.md`. | Baseline MariaDB oficial anterior: 376 PHPUnit/2.570 asserções, PHPStan 236/236, JS 129/129, integração 998/998, Playwright 167/167 e visual 2/2; código 0. | Manter o baseline como regressão e preservar o legado. |
| T01 | Validada | Migration `001_cronograma_inscricoes.sql` com tabelas auxiliares para modo, configuração, equipes planejadas e compromissos; regras puras e campos de modalidade. Edições novas recebem planejamento fechado; edições sem migration continuam legadas. | PHPUnit/qualidade e os testes de migration passaram nos dois motores; integração oficial MariaDB e MySQL: 1.002/1.002 asserções. | Evoluir o modelo de nós quando T03 for retomada. |
| T02 | Validada | Preparação idempotente cria a quantidade por turma com ordinal estável; redução com elenco/jogo/histórico é recusada; rotinas padrão não redistribuem o modo planejado. | `CronogramaPlanejadoTest` e suíte oficial exercitam repetição, FKs e limpeza sintética. | Adicionar cenário concorrente dedicado se a implementação de preparação for ampliada. |
| T03 | Implementada com validação pendente | O rascunho atual cria compromissos condicionais determinísticos por equipe e fase, permitindo inscrição antes de existir elenco. Ainda não persiste a árvore exata de nós, BYEs e dependências nem calcula a contagem de confrontos do chaveamento existente. | Geração/publicação básica passou em MariaDB/MySQL; não há aceite para os cenários 3/4/6/8 entradas do roteiro. | Implementar nós estáveis e integrar a projeção ao `MysqliChaveamentoManagement` sem criar atletas fictícios. |
| T04 | Implementada com validação pendente | Geração sequencial respeita janela, duração, descanso, locais e margem de 10 minutos; publicação revalida equipe/local e retorna pendências de janela. A simulação ainda não combina todas as reservas existentes nem os nós condicionais reais. | Integração oficial e regressões de agenda legada passaram; falta cenário de grade insuficiente e alteração externa invalidando a proposta. | Reusar as invariantes de blocos/sequencial na simulação geral e persistir uma proposta revisável. |
| T05 | Validada | Rotas versionadas para ativar, preparar, gerar, publicar, abrir e encerrar; revisão otimista, publicação antes da abertura e permissões administrativas. | `CronogramaPlanejadoTest` cobre publicação fechada e abertura posterior; MariaDB/MySQL oficiais passaram. | Acrescentar revisão/suspensão completa quando T08 for implementada. |
| T06 | Implementada com validação pendente | Inscrição planejada usa equipe exata, capacidade própria e compromissos publicados; conflitos de intervalo e margem são recusados no serviço, e inclusão administrativa consulta a mesma configuração. Fluxo legado permanece separado. | Suíte oficial completa passou, mas ainda não existe um teste de integração dedicado que inscreva duas modalidades com conflito e verifique o detalhe estruturado do erro. | Criar regressão explícita de conflito e cobrir transferência atômica/versionada. |
| T07 | Implementada com validação pendente | Cadastro de modalidade ganhou quantidade/formato/capacidade; agenda administrativa ganhou ativação, preparação, rascunho, publicação e abertura; mensagens usam componentes existentes. | Build, PHPStan, CS, JS, Playwright 167/167 e visual 2/2 passaram; os novos controles ainda não têm cenário Playwright dedicado. | Cobrir o fluxo novo na interface e validar reentrada/duplo clique em navegador. |
| T08 | Não iniciada | Proteções pontuais impedem geração padrão e inclusão incompatível no modo planejado, mas não há revisão operacional, materialização de sucessoras nem propagação de versão para preparo offline. | Testes offline legados passaram e nenhuma fila foi alterada; isso não valida os requisitos novos de revisão/offline. | Projetar a integração de versão com preparação do mesário sem limpar IndexedDB ou filas. |
| T09 | Implementada com validação pendente | Documentação de README, arquitetura e implantação atualizada; branch sem push. | `tools/test-docker.ps1 -Database mariadb -IncludeVisual`: PHPUnit 380/2.606, PHPStan 241/241, JS 44 arquivos, integração 1.002/1.002, Playwright 167/167, visual 2/2, código 0. `tools/test-docker.ps1 -Database mysql -SkipQuality -SkipBrowser`: integração 1.002/1.002, código 0. | Não declarar o roteiro concluído até T03/T04/T06/T07/T08 receberem os cenários pendentes. |

Estados permitidos: não iniciada, em andamento, implementada com validação pendente, validada, impedida. Registrar condição concreta para impedimentos. Não promover etapa a validada sem os testes exigidos.

## Execuções finais registradas

```text
Data: 21/09/2026 (America/Sao_Paulo)
Branch: codex/cronograma-inscricoes-luna-2026-09-21

powershell -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mariadb -IncludeVisual
exit code 0; PHPUnit 380/380 e 2.606 asserções; PHPStan 241/241; CS Fixer 0/320 arquivos; npm check 129/129 testes; integração 1.002/1.002; Playwright 167/167; visual 2/2.

powershell -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mariadb -SkipQuality -SkipBrowser
exit code 0; integração 1.002/1.002.

powershell -ExecutionPolicy Bypass -File tools/test-docker.ps1 -Database mysql -SkipQuality -SkipBrowser
exit code 0; integração e migration 1.002/1.002.

vendor/bin/phpunit --filter CronogramaRulesTest
exit code 0; 4 testes e 6 asserções.
```

Os containers e bancos usados acima foram descartáveis e removidos pelo executor. O teste local executado fora do executor Docker encontrou o MySQL/XAMPP indisponível e não é evidência de aprovação. Não houve push.

## Decisões técnicas a registrar durante T00/T01

- Inventário de rotas escritoras e ordem de locks existente/proposta.
- DDL final, número da migration e convergência baseline/upgrade.
- Mapeamento de entradas individuais para os participantes reais existentes.
- Identidade dos nós, momento de materialização e integração com avanço offline.
- Contratos HTTP finais e permissões por operação.
- Política de adoção das edições antigas e tratamento de ambiguidades.
- Tokens/componentes reutilizados e telas impactadas.

## Configuração pendente do evento real

Datas, durações por confronto/prova, recursos simultâneos, turmas participantes, mínimos de elenco, formatos dos representantes, agenda final por categoria e tratamento do terceiro lugar quando o campeão teve BYE. Não são motivo para inventar valores de produção; desenvolvimento usa cenários sintéticos explícitos.

## Modelo de registro por execução

```text
Tarefa / IDs Vxx:
Data/hora e fuso:
Commit e alterações sob teste:
Ambiente/URL isolada e motor SQL:
Comando exato:
Exit code e contagens:
Cenário / esperado / observado:
Logs e relatório/capturas:
Falhas preexistentes ou introduzidas:
Limitações e próximos passos:
```

## Retomada

Retomar por T03 em [etapas](03-etapas-de-implementacao.md), começando pela árvore persistida de nós e pela regressão explícita de conflito de inscrição. Não executar migrations em base de trabalho, não iniciar implementação de ranking fora do escopo e não considerar decisões históricas de outros planos como autorização para apagar dados.

## Verificação documental em 21/09/2026

- Documentação de uso, arquitetura, implantação e execução atualizada junto da implementação.
- `git diff --check`: sem erros após a bateria oficial; não houve migration aplicada em base de trabalho.
- O primeiro teste local fora do executor Docker encontrou ambiente XAMPP/MySQL indisponível; não foi usado como evidência de produto. As evidências oficiais foram produzidas exclusivamente pelos containers descartáveis recomendados pelo AGENTS.
- Os testes oficiais não cobrem ainda a árvore exata de nós/BYEs nem a revisão/offline novos; por isso este arquivo mantém o estado parcial.
