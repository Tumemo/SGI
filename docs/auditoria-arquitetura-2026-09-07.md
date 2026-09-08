# Auditoria da arquitetura e plano de correções do SGI

Data: 07/09/2026. Referência: `aedbc420ea34aa759db1b14a8307abe4eb478cfa`, branch `codex/refatoracao-arquitetura-limpeza`.

**Implementação por IA:** o plano foi expandido no [roteiro detalhado para Luna / Extra alto](plano-implementacao-luna/README.md), com 30 tarefas sequenciais, arquivos, decisões de comportamento, testes, critérios de aceitação e registro de retomada. Para implementar, comece por esse roteiro; este documento preserva o diagnóstico e o resumo das etapas.

## Parecer

A refatoração estrutural foi bem encaminhada, mas não considero a arquitetura completamente consolidada nem todos os fluxos corretos. O projeto tem uma base adequada de monólito modular; os problemas restantes exigem correções de comportamento e melhor distribuição de responsabilidades.

A migração eliminou entradas procedurais e organizou os arquivos. Entretanto, regras de autorização, pontuação, cronômetro e inscrições ainda ficam distribuídas entre controladores, gateways, repositórios e JavaScript. Há falhas concretas nesses contratos, mesmo com todas as suítes existentes aprovadas. A declaração de conclusão em `docs/migration-completion-plan.md` deve ser entendida como conclusão da migração estrutural, não como comprovação integral da correção funcional.

Esta auditoria não alterou a implementação. Foram executados testes e reproduções em ambiente isolado; este documento é o plano solicitado. Os achados descrevem o estado atual, sem afirmar que todos foram introduzidos pela refatoração.

## Verificação realizada

| Verificação | Resultado nesta auditoria |
| --- | --- |
| `composer verify` | Aprovado: 116 testes, 1.514 asserções; sintaxe PHP, PHPStan e estilo sem falhas |
| `npm run check` | 39 arquivos JavaScript válidos |
| `npm test` | 5 testes aprovados |
| `npm run build` | 230 arquivos preparados |
| `php tests/run_all.php` | 224/224 asserções aprovadas |
| `npm --prefix tests/browser test` | 26/26 testes aprovados, incluindo torneios online/offline e comparação visual |
| Reproduções adicionais no banco/HTTP | Quatro defeitos confirmados, descritos em A1–A4 |
| Reproduções isoladas de JavaScript | Três defeitos lógicos confirmados, descritos em A4–A6 |

As verificações locais de qualidade usaram PHP 8.4.25. HTTP e navegador usaram PHP 8.2.12 com MySQLi e MariaDB 10.4.32, no banco exclusivo `sgi_test_audit_20260907`, servido em `127.0.0.1:8109`. O PHP padrão do terminal não tinha MySQLi; foi utilizado o PHP do XAMPP para a integração. Nenhum banco de trabalho foi usado.

Os registros locais estão em `test-results/audit-architecture/http-suite.log`, `browser-suite.log` e `probes.log`. As reproduções de banco foram revertidas por transação; o registro sintético criado pela reprodução HTTP foi removido. Esses arquivos são evidência local ignorada pelo Git.

Não foram executados nesta auditoria a matriz remota de CI, um upgrade real da base anterior ou um ensaio de restauração. Os cenários concorrentes abaixo foram analisados pelo encadeamento das transações, sem execução simultânea. A aprovação das suítes demonstra os cenários que elas cobrem; as reproduções adicionais expõem lacunas nessa cobertura.

## O que está adequado

- Sete módulos coerentes: Acesso, Eventos, Participantes, Competicoes, Resultados, Disciplina e Sincronizacao.
- `public/index.php` pequeno, inicialização em `bootstrap/app.php` e composição explícita de dependências em `config/routes.php`.
- URLs antigas encaminhadas aos controladores versionados; templates privados e assets separados das fontes.
- Serviços e contratos existentes permitem testes sem banco. Há verificações automáticas de fronteiras entre camadas.
- Proteção pública de arquivos, rejeição de traversal, CSRF e uso de `password_hash`/`password_verify`.
- Migrações com checksum, trava de execução e marcador de aplicação incompleta.
- Identidade de mutação, repetição transacional e savepoints nos fluxos protegidos pela sincronização.
- Estado de tela isolado, gerenciamento de eventos e caches separados por usuário. Os identificadores antigos da fila são preservados.

## Achados e correções necessárias

P1 significa corrigir antes de considerar esta versão pronta para operação. P2 significa corrigir na sequência, com regressão específica. A ordem abaixo combina impacto e dependências.

### A1 — P1: autorização do mesário não cobre o recurso efetivamente alterado

**Confirmado por HTTP:** um mesário autenticado conseguiu registrar artilharia em jogo de edição inativa. A resposta foi HTTP 200, `success: true`, com registro persistido; o esperado era HTTP 403.

Em [ArtilheiroController.php](../src/Modules/Competicoes/Presentation/Http/ArtilheiroController.php), linhas 33–50, a edição é selecionada na sessão, mas não se verifica a edição do jogo/aluno antes da gravação. A inspeção encontrou outras variantes:

- [ResultadoController.php](../src/Modules/Competicoes/Presentation/Http/ResultadoController.php), linha 38: a verificação só ocorre para ID positivo; o gateway resolve IDs temporários por tag/modalidade/equipes sem validar novamente a edição.
- [PartidaController.php](../src/Modules/Competicoes/Presentation/Http/PartidaController.php), linha 86: o jogo informado pode autorizar a operação, enquanto a atualização usa outro identificador de partida. IDs discordantes permitem alterar ou reassociar dados históricos.
- [OcorrenciaController.php](../src/Modules/Disciplina/Presentation/Http/OcorrenciaController.php), linhas 34–58: falta verificar o escopo dos IDs individuais envolvidos.

**Correção:** autorizar o recurso persistido, depois da resolução de IDs temporários e antes da mutação; conferir relações entre jogo, partida, modalidade, equipe e atleta. A política deve receber o operador e a edição explicitamente, sem depender somente da sessão dentro dos controladores.

**Aceitação:** duas edições, quatro perfis, IDs positivos/negativos e referências discordantes. Mesário nunca altera a edição inativa; operações válidas e reenvios offline continuam funcionando. As variantes além da artilharia foram identificadas por inspeção e precisam de regressões HTTP próprias.

### A2 — P1: alterar o valor de arrecadação apaga a parcela esportiva

**Confirmado no banco:** turma com 10 pontos esportivos e 10 itens a 2 pontos totaliza 30. Ao mudar o item para 3, o resultado deveria ser 40, mas permaneceu 30, pois os 10 pontos esportivos foram descartados.

O trigger em [001_initial_schema.sql](../database/migrations/001_initial_schema.sql), linhas 60–64, substitui o total por `qtd_itens_arrecadados × novo valor`. O mesmo campo recebe pontos esportivos.

Há um segundo problema relacionado, identificado por inspeção: [MysqliArrecadacaoRepository.php](../src/Modules/Resultados/Infrastructure/MysqliArrecadacaoRepository.php), linhas 130–137, estorna os pontos históricos. Dez itens registrados a 2, revalorizados para 3 e depois removidos deixam 10 pontos residuais com zero itens.

**Correção:** estabelecer uma regra única para revalorização e estorno; preservar separadamente as origens da pontuação. Adicionar uma nova migração para corrigir o trigger e, se necessário, o modelo de persistência. Não reescrever a migração 001.

**Aceitação:** alteração do valor com esportes, arrecadação, penalidades e ajustes existentes; estorno antes/depois da alteração; repetição da migração; reconciliação dos totais sem eliminar ajustes que não possam ser reconstruídos automaticamente.

### A3 — P1: correção do vencedor não transfere os pontos do pódio

**Confirmado no banco usando o gateway real:** a primeira final concedeu `[10, 7]` pontos. Após inverter o vencedor, os pontos continuaram `[10, 7]`; deveriam ser `[7, 10]`.

[MysqliPartidaGateway.php](../src/Modules/Competicoes/Infrastructure/MysqliPartidaGateway.php), linhas 96–114, premia somente na primeira conclusão. A alteração posterior atualiza partidas/chaveamento sem estornar e reaplicar a premiação. A mesma condição existe para prova individual em [MysqliIndividualRepository.php](../src/Modules/Competicoes/Infrastructure/MysqliIndividualRepository.php), linhas 145–146, identificada por inspeção.

**Correção:** contabilizar a premiação por origem, permitindo substituição atômica quando um pódio muda. O lançamento de resultado, o avanço do chaveamento e a pontuação precisam compartilhar a unidade transacional.

**Aceitação:** finais, terceiro lugar e provas individuais; troca de vencedor; reconstrução de fases; repetição da mesma mutação; falha intermediária com rollback. O ranking deve refletir os resultados efetivamente persistidos.

### A4 — P1: pausa e retomada não preservam corretamente o cronômetro

**Confirmado no banco:** jogo de 1.200 segundos, iniciado 30 segundos antes, permaneceu com 1.200 segundos salvos ao pausar; eram esperados 1.170. O instante de início foi apagado.

[placar.js](../resources/js/pages/competicoes/placar.js), linha 404, envia somente o status de pausa. [MysqliJogoGateway.php](../src/Modules/Competicoes/Infrastructure/MysqliJogoGateway.php), linhas 125–129, altera/apaga o instante de início sem preservar o tempo consumido. A consulta nas linhas 65–70 calcula a retomada a partir da duração total. A execução isolada das funções reais de JavaScript também confirmou a gravação local de 1.200 em vez de 1.170 segundos.

**Correção:** definir um contrato único para tempo restante/acumulado, último instante de retomada e acréscimos; aplicá-lo no caso de uso PHP e na projeção offline.

**Aceitação:** iniciar 20 minutos, consumir 30 segundos, pausar, sair, voltar e retomar mantendo 19m30. Repetir online, offline, após reconectar e com acréscimos, verificando também a consulta seguinte do servidor.

### A5 — P2: sair rapidamente da tela pode descartar um gol

[placar.js](../resources/js/pages/competicoes/placar.js), linhas 444–446, posterga a gravação por 400 ms. A limpeza da tela, linhas 78–84, cancela os temporizadores sem descarregar as alterações.

**Confirmado em execução isolada das funções reais:** alterar o placar e desmontar a tela antes dos 400 ms produziu zero gravações, embora a interface já mostrasse a alteração. A reprodução usou dependências simuladas, não o navegador completo.

**Correção:** persistir a alteração local antes de considerá-la concluída; limitar o atraso ao envio remoto, ou aguardar explicitamente a descarga antes de desmontar.

**Aceitação:** adicionar/remover gol e navegar imediatamente; retornar e reconectar preserva o valor e não duplica a alteração.

### A6 — P2: consulta offline de ocorrência ignora o ID solicitado

[mesario-data.js](../resources/js/offline/mesario-data.js), linhas 156–171, não aplica o filtro `id_ocorrencia`. [placar.js](../resources/js/pages/competicoes/placar.js), linhas 1425–1426, usa o primeiro registro retornado para preencher a edição.

**Confirmado em execução isolada:** consulta do ID 20, com registros 10 e 20, devolveu ambos. Quando o fallback estruturado é utilizado, a edição pode carregar os dados do registro 10 e salvá-los sobre o 20.

**Correção:** contratos de consulta/projeção explícitos por recurso, filtro por identificador e conferência do ID na tela. Completar a projeção local das alterações de ocorrência.

**Aceitação:** duas ocorrências distintas, pendência local e edição da segunda sem rede; somente o registro selecionado muda, inclusive após reconexão.

### A7 — P2: invariantes de concorrência e limites incompletos

Três problemas identificados por inspeção:

- **Estorno duplicado:** [MysqliArrecadacaoRepository.php](../src/Modules/Resultados/Infrastructure/MysqliArrecadacaoRepository.php), linhas 107–141, lê o histórico ativo sem trava e depois desconta sem exigir que ele ainda esteja ativo. Dois operadores podem descontar o mesmo lançamento. Travar o histórico ou realizar uma transição condicional atômica.
- **Mais de três modalidades:** [MysqliInscricaoRepository.php](../src/Modules/Participantes/Infrastructure/MysqliInscricaoRepository.php), linhas 28–84, consulta/valida o limite antes da transação. Duas solicitações para modalidades diferentes podem exceder o limite do mesmo aluno. Travar o aluno antes da leitura e validar sob a mesma transação; preservar as travas de capacidade.
- **Limite de equipes ignorado com nome explícito:** [MysqliEquipeRepository.php](../src/Modules/Competicoes/Infrastructure/MysqliEquipeRepository.php), linha 22, só passa pela validação de limite, linha 206, ao gerar o nome. Validar o limite independentemente da nomenclatura e proteger a criação concorrente.

**Aceitação:** dois processos independentes estornando o mesmo registro; duas sessões inscrevendo um aluno que já tem duas modalidades; criação de equipe acima do limite com e sem nome fornecido. Conferir os valores finais do banco, não apenas as respostas HTTP.

### A8 — P2: histórico da turma e ranking usam conceitos diferentes de total

[MysqliHistoricoTurmaRepository.php](../src/Modules/Resultados/Infrastructure/MysqliHistoricoTurmaRepository.php), linha 76, calcula esportes como `total salvo − arrecadação + penalidades`. [MysqliRankingRepository.php](../src/Modules/Resultados/Infrastructure/MysqliRankingRepository.php), linhas 20–21, considera o total salvo bruto e aplica o desconto depois.

**Identificado por inspeção:** 80 pontos de arrecadação, penalidade de 10 e nenhum esporte são interpretados pelo histórico como 10 pontos esportivos; o ranking apresenta 70. O histórico ainda retorna uma lista vazia para as modalidades esportivas.

**Correção e aceitação:** compartilhar o cálculo e as origens da pontuação com A2/A3. Histórico, ranking e resultados devem reconciliar por turma/edição e justificar cada componente, incluindo penalidades e ajustes.

### A9 — P2: ativação da edição possui dois comportamentos

[UsuarioController.php](../src/Modules/Acesso/Presentation/Http/UsuarioController.php), linha 50, chama [MysqliUsuarioGateway.php](../src/Modules/Acesso/Infrastructure/MysqliUsuarioGateway.php), linhas 276–288, para ativar uma edição sem desativar as demais. O fluxo de Eventos faz a desativação em transação.

**Identificado por inspeção:** o caminho de usuários pode deixar duas edições ativas. Encaminhar ambos ao mesmo caso de uso de Eventos e garantir a invariante sob concorrência.

**Aceitação:** alternância pelos dois caminhos, solicitações simultâneas e ID inexistente; nunca mais de uma edição ativa e nenhuma desativação acidental das demais por entrada inválida.

### A10 — P2: alguns controladores expõem mensagens internas

[UsuarioController.php](../src/Modules/Acesso/Presentation/Http/UsuarioController.php), linhas 162–164, devolve `getMessage()` de qualquer exceção. [ResultadoController.php](../src/Modules/Competicoes/Presentation/Http/ResultadoController.php), linhas 53–54, também devolve mensagens de `RuntimeException`, hierarquia que inclui falhas MySQLi.

**Identificado por inspeção:** falhas de constraint/esquema podem escapar do tratamento seguro e uniforme. Separar exceções de negócio das falhas de infraestrutura, devolvendo mensagens públicas estáveis e registrando detalhes no log.

**Aceitação:** entradas inválidas, falha de chave estrangeira e indisponibilidade de persistência retornam status apropriado, sem SQL, nomes internos de constraints ou caminhos locais.

## Refatoração arquitetural recomendada

Manter o monólito e os sete módulos. A prioridade arquitetural é levar decisões de negócio aos casos de uso, com contratos claros para persistência e acesso entre módulos.

| Área | Situação atual | Direção proposta |
| --- | --- | --- |
| Resultados de partidas | `ResultadoController` chama gateway concreto que decide finalização, avanço e premiação | Casos de uso de lançar/corrigir resultado em Competicoes, com colaboração explícita de Resultados |
| Pontuação | Incrementos dispersos, trigger e cálculos divergentes | Regra compartilhada em Resultados e persistência por origem; projeção/reconciliação do total |
| Cronômetro | Transições divididas entre página, gateway e cache | Estado e transições definidos por contrato, com implementação testável e projeção offline equivalente |
| Edições | Ativação também implementada em Acesso | Um caso de uso de Eventos; compatibilidade apenas encaminha |
| Usuários e consultas | `UsuarioController` recebe MySQLi e consulta infraestrutura de Eventos | Injeção de casos de uso e contratos de consulta; composição permanece no bootstrap/configuração |
| Inscrições e equipes | Limites escondidos em persistência/geração de nome | Regras explícitas e unidade transacional que proteja as invariantes |
| Offline | Regras de consulta inferidas genericamente pelo nome da URL | Adaptadores por recurso, preservando aliases, registros antigos de cache e identidades de mutação |

A importação de infraestrutura em um controlador não prova, isoladamente, um defeito. Aqui a necessidade é demonstrada pela concentração de regras em gateways e pelos comportamentos divergentes. Por exemplo, `MysqliPartidaGateway::launch` decide premiação e reconstrução, enquanto `MysqliIndividualRepository` também decide regras do pódio. Os testes atuais proíbem SQL em controladores, mas não garantem que esses casos de uso estejam na camada Application.

## Plano de execução

### Etapa 1 — Fechar as falhas de maior impacto

1. Criar regressões que reproduzam A1–A4 antes de alterar o comportamento.
2. Corrigir autorização por recurso e referências discordantes, incluindo IDs temporários.
3. Corrigir o contrato de pausa/retomada nos dois modos.
4. Implementar a correção atômica do pódio e o cálculo consistente de arrecadação/estorno.

**Saída:** reproduções passam com os valores esperados; operações inválidas não gravam; rollback e reenvio não alteram indevidamente o total. Mudanças podem ser entregues separadamente, cada uma com sua regressão.

### Etapa 2 — Consolidar integridade e persistência offline

1. Resolver A5/A6 com testes de navegação imediata e seleção de ocorrências.
2. Proteger estorno, inscrição e criação de equipes concorrentes.
3. Unificar a ativação da edição, a apresentação de erros e a reconciliação do histórico.
4. Na evolução de esquema, adicionar migração nova; produzir uma conferência de dados existentes por origem e preservar ajustes não reconstruíveis até sua classificação.

**Saída:** nenhum teste concorrente viola os limites; valores sobrevivem à desmontagem, troca de tela e reconexão; histórico e ranking fecham os mesmos totais.

### Etapa 3 — Concluir as fronteiras de aplicação

1. Extrair os casos de uso de resultado, pódio, cronômetro, ativação e inscrições, aproveitando as correções anteriores.
2. Substituir dependências concretas relevantes na apresentação por contratos; remover MySQLi de `UsuarioController`.
3. Manter SQL e mecanismos de trava nos adaptadores de persistência; definir claramente quem controla a transação do caso de uso.
4. Ampliar testes arquiteturais para dependências entre módulos e passagem dos fluxos críticos por Application. Tratar exceções justificadas explicitamente, sem exigir camadas artificiais para toda consulta simples.

**Saída:** regras críticas testáveis sem HTTP/banco, política de acesso reutilizada e uma definição única para cada comportamento compartilhado. Aliases e protocolo offline continuam compatíveis.

### Etapa 4 — Comprovar atualização e operação

1. Ensaiar instalação nova e atualização a partir de uma cópia representativa do esquema anterior, com dados sintéticos de matrículas repetidas entre edições, pódios, arrecadações e penalidades.
2. Cobrir repetição, checksum divergente, migração interrompida e baseline incompatível. O teste atual de baseline parte de uma base recém-migrada; não comprova o upgrade histórico.
3. Testar cache antigo, fila pendente, troca de usuário/edição, nova versão dos assets e reconexão.
4. Testar recarga, nova aba e reabertura sem rede. O shell atual declara implementação sem Service Worker; não foi comprovada falha desses cenários, mas a garantia “100% offline” precisa ser delimitada e testada. Só adotar outro mecanismo de cache se o requisito de reabertura exigir.
5. Executar `composer verify`, `npm run build`, `npm run check`, `npm test`, `php tests/run_all.php` e `npm --prefix tests/browser test`; validar também subdiretório e matriz PHP 8.2/8.4, MySQL/MariaDB.
6. Registrar backup/restauração e compatibilidade de retorno de versão com migrações e clientes offline. Atualizar os documentos de arquitetura, testes e conclusão conforme a evidência obtida.

**Saída:** relatório reproduzível com todos os checks aprovados, atualização ensaiada e limites do offline documentados. As suítes que modificam banco devem continuar sequenciais e usar somente ambiente isolado.

## Critério para considerar o trabalho concluído

- [ ] A1–A10 corrigidos com as regressões descritas.
- [ ] Ranking, histórico e origens da pontuação reconciliados após correção, estorno e alteração de valores.
- [ ] Autorização aplicada ao recurso resolvido, inclusive IDs temporários e clientes antigos.
- [ ] Cronômetro e alterações do placar preservados em navegação, pausa e reconexão.
- [ ] Invariantes de inscrição, equipe, edição ativa e estorno resistentes à concorrência.
- [ ] Casos de uso críticos independentes de apresentação e persistência concretas.
- [ ] Nova instalação, upgrade real, repetição e recuperação de migração validados.
- [ ] Suítes e matriz de ambientes aprovadas; alcance da operação offline documentado.

Não é necessário reiniciar a refatoração. A base existente deve ser preservada e concluída por mudanças pequenas, orientadas pelos defeitos e pelos contratos acima.
