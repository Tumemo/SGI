# Revisão pós-Luna e plano de limpeza

Data: 08/09/2026. Referência revisada: `1b1f81b`, branch `codex/refatoracao-arquitetura-limpeza`.

## Parecer

A refatoração melhorou a separação de responsabilidades e passa nas suítes locais existentes, mas **não está integralmente correta**. A revisão reproduziu falhas de consistência no placar e de atualização parcial de jogos, além de identificar um impedimento de portabilidade no teste de recuperação do CI.

Além do diagnóstico, esta rodada implementa as correções de produção e a limpeza direta que não dependem de uma migração ampla de rotas. Os assets foram reconstruídos para validar as fontes atuais.

A orientação do usuário passa a ser **não manter retrocompatibilidade com versões anteriores da aplicação**. Isso permite retirar aliases e formatos antigos, mas o próprio cliente atual ainda depende de vários deles. A substituição desses usos faz parte da limpeza. O funcionamento offline da versão atual continua sendo requisito.

## Implementado nesta rodada

R01 e R02 foram corrigidos no código e ganharam cobertura unitária e HTTP. O PUT de partida rejeita resultado negativo, não altera partidas encerradas e direciona a retificação para o fluxo completo de resultado. O PUT de jogo rejeita uma requisição que misture agenda e cronômetro, evitando confirmar campos ignorados.

R03 foi corrigido no ensaio de recuperação: `SGI_MYSQLDUMP_PATH` e `SGI_MYSQL_PATH` podem apontar para executáveis configurados, o PATH é usado por padrão e o fallback do XAMPP só é considerado no Windows. O job de integração instala `default-mysql-client` e define os dois caminhos.

Foram removidos `src/Modules/Acesso/Infrastructure/MysqliUsuarioGateway.php`, `src/Modules/Eventos/Infrastructure/MysqliEdicaoConsulta.php` e `database/archive/pre-refactor.sql`. A documentação de arquitetura, implantação, README e AGENTS foi alinhada ao contrato offline atual e à ausência de necessidade de compatibilidade com versões anteriores.

Também foram removidos os métodos sem consumidores `editionOfGame`, `editionOfPartida` e `reconciliarPodioConcluido` de `MysqliPartidaGateway`. Os três pacotes visuais duplicados (`bootstrap-login`, `bootstrap-pages` e `bootstrap-icons-login`) saíram do manifesto, lockfile e build; os templates agora usam Bootstrap 5.3.8 e Bootstrap Icons 1.11.3. O payload de início do jogo foi separado: metadados permanecem no contexto local e o servidor recebe apenas a mutação de cronômetro.

Validação após as alterações: `composer verify` aprovado com 164 testes PHPUnit, 1.813 asserções, PHPStan 211 arquivos e PHP CS Fixer 273 arquivos; `npm run check` 40 arquivos; `npm test` 16/16; `npm run build` preparou 134 arquivos; `php tests/run_all.php` 385/385 asserções. O cenário de mesário que havia falhado por payload misto passou isoladamente após o ajuste, e a suíte completa de navegador passou depois da reinicialização do banco isolado.

## Validação executada nesta revisão

| Verificação | Resultado |
| --- | --- |
| `composer verify` | Aprovado: 164 testes / 1.813 asserções; sintaxe, PHPStan e estilo aprovados |
| `npm run check` | 40 arquivos JavaScript válidos |
| `npm test` | 16/16 testes aprovados |
| `npm run build` | 134 arquivos preparados |
| `php tests/run_all.php` | 385/385 asserções aprovadas |
| `npm --prefix tests/browser test` | 32/32 cenários aprovados, incluindo os dois testes visuais, em 4,5 minutos; o global setup confirmou 385/385 asserções HTTP |
| Sondagens adicionais da revisão | Placar/pódio inconsistente, placar negativo e atualização mista incompleta reproduzidos antes das correções |

Ambiente: qualidade PHP em 8.4.25; integração no PHP 8.2 do XAMPP e banco MariaDB isolado em `127.0.0.1:3308`, `sgi_test_luna`; servidor `http://127.0.0.1:8110/`. O endpoint de saúde confirmou o banco de teste antes da execução. A validação final do navegador usou `SGI_E2E_RESET=1` e as credenciais do banco isolado. As suítes que alteram dados foram executadas sequencialmente. As fixtures adicionais foram removidas e a edição anteriormente ativa foi restaurada.

Evidências locais: `test-results/review-composer.log`, `review-http.log`, `review-browser.log`, `review-probes.php` e `review-probes.json`. A primeira tentativa da sondagem parou por conferir o campo incorreto da resposta de login; o script foi ajustado ao contrato `status=sucesso` e executado novamente. Os resultados abaixo são da execução corrigida.

Esta revisão não executou a matriz MySQL 8.4/MariaDB 10.11 em Linux, integração HTTP com PHP 8.4 nem um segundo servidor em subdiretório. Os resultados anteriores registrados no STATUS não foram tratados como novas execuções desta revisão. Aprovação dos testes existentes não cobre os casos adicionais descobertos abaixo.

## Problemas encontrados

### R01 — Alta: PUT de partida contorna as regras de resultado

Fontes: `src/Modules/Competicoes/Presentation/Http/PartidaController.php:103`, `src/Modules/Competicoes/Application/PartidaService.php:37` e `src/Modules/Competicoes/Infrastructure/MysqliPartidaRepository.php`.

O PUT chama diretamente a atualização de campos. Não consulta o estado de encerramento do jogo, não passa pela reconciliação de resultado/pódio e aceita números negativos. A rotina de finalização em `ResultadoService` tem validações que esse caminho não utiliza.

Reprodução no contrato atual `/api/v1`, sem depender de aliases:

1. Final `MM:2:0:N` encerrada por `POST /api/v1/resultados` com A=2, B=1: HTTP 200; A recebe 10 pontos e B recebe 7.
2. `PUT /api/v1/partidas` para a partida de B, com `resultado_partida=3`: HTTP 200; placar persistido A=2, B=3.
3. `pontuacoes_podio` continua com A em primeiro e B em segundo, preservando os créditos anteriores.
4. Outro PUT com `resultado_partida=-5`: HTTP 200 e `-5` persistido.

Impacto: resultado esportivo e créditos de ranking podem divergir; a API admite placar inválido. O caminho PUT também é usado pelo placar atual para salvar pontuação em andamento. Não deve ser simplesmente eliminado junto dos aliases.

Correção proposta: validar placares inteiros não negativos no caso de uso; impedir alteração direta de placar encerrado e exigir o fluxo de retificação completo, ou encaminhar a alteração para uma operação transacional que recalcule o resultado completo, chaveamento e pódio. Recomenda-se rejeitar o PUT de placar encerrado e manter a retificação em `/api/v1/resultados`, ajustando o cliente quando necessário.

Aceite: HTTP rejeita placar negativo sem alterar dados; PUT em jogo concluído não consegue contornar a retificação; retificação válida inverte vencedor, créditos e fases derivadas de modo consistente; salvamento em andamento e fila offline continuam funcionando.

### R02 — Média: atualização mista de jogo retorna sucesso e ignora campos

Fontes: `src/Modules/Competicoes/Presentation/Http/JogoController.php:78`, `src/Modules/Competicoes/Application/CronometroService.php:28` e `src/Modules/Competicoes/Infrastructure/MysqliCronometroRepository.php:47`.

Quando existe qualquer campo de cronômetro, o controller retorna pela rotina do cronômetro e não processa os demais campos do jogo.

Reprodução: um jogo chamado `Antes`, com duração 1.200, recebeu `PUT /api/v1/jogos` contendo `nome_jogo=Depois` e `duracao_jogo=1500`. A resposta foi HTTP 200; a duração mudou para 1.500 e o nome permaneceu `Antes`.

Correção proposta: definir um contrato explícito. Preferencialmente separar atualização de agenda e de cronômetro e rejeitar pedidos mistos com erro de validação; alternativamente, processar todos os campos aceitos numa única transação. Não confirmar silenciosamente campos ignorados.

Aceite: pedido misto é integralmente aplicado ou rejeitado sem alteração parcial; adicionar cobertura HTTP com nome/data/local e duração/status. Conferir os corpos enviados pelo placar e pela agenda antes de alterar o contrato.

### R03 — Alta para a validação: recuperação do CI usa executáveis exclusivos do Windows

Fontes: `tests/Integration/RecoveryRehearsalTest.php:123` e `:143`, `tests/run_all.php:163` e `.github/workflows/ci.yml`.

O teste usa `C:\xampp\mysql\bin\mysqldump.exe` e `mysql.exe` como padrões. O runner sempre o executa. O job `integration` roda em `ubuntu-latest` e não define `SGI_MYSQLDUMP_PATH`/`SGI_MYSQL_PATH` nem prepara esses caminhos.

Consequência deduzida diretamente do código/configuração: mesmo que as etapas anteriores passem, o ensaio de recuperação não consegue iniciar esses executáveis no Linux. Não foi executado um job remoto nesta revisão; a passagem local no XAMPP não comprova a matriz declarada.

Correção proposta: resolver os executáveis por configuração ou PATH, instalar/selecionar clientes compatíveis com cada motor no CI e definir os caminhos explicitamente. Conferir opções do dump para MySQL e MariaDB. Manter o ensaio de backup/restauração após remover a parte de upgrade antigo.

Aceite: os dois alvos de banco executam a recuperação e os testes de navegador em Linux; Windows continua funcionando sem caminho obrigatório ao XAMPP. Ausência de cliente de backup deve produzir diagnóstico claro.

### Limite funcional/documental

`AGENTS.md` descreve Service Worker e operação 100% offline. O pacote atual usa uma SPA previamente preparada; `docs/testing.md` e os testes explicitam ausência de Service Worker e de suporte a abertura fria/refresh offline sem a casca carregada. Corrigir a descrição para o suporte efetivo. Implementar abertura fria offline seria uma funcionalidade separada, não uma remoção de legado.

## Inventário de remoção

### Remoção direta de código/histórico sem consumidor de execução encontrado

| Alvo | Evidência e providência |
| --- | --- |
| `src/Modules/Acesso/Infrastructure/MysqliUsuarioGateway.php` | Nenhum consumidor de produção encontrado. A única referência externa em código é uma asserção que proíbe seu uso em `UsuarioControllerArchitectureTest`. Remover o arquivo; preservar a intenção do teste de fronteira. |
| `src/Modules/Eventos/Infrastructure/MysqliEdicaoConsulta.php` | Nenhum consumidor de produção encontrado; aparece no mesmo teste de proibição. A composição atual usa `MysqliEdicaoConsultaRepository` e o contrato `EdicaoConsulta`. Remover somente a classe antiga. |
| `database/archive/pre-refactor.sql` e, depois, `database/archive/` vazio | Dump histórico de 25.223 bytes, sem consumidor no instalador, migrações ou testes de execução. As referências encontradas estão na documentação. Remover e atualizar README/documentação; o histórico Git preserva a referência. |

A busca considerou nomes de classes em fontes, composição, templates, ferramentas e testes versionados. Referência em teste negativo não foi confundida com uso da classe. Não foi encontrado outro arquivo de classe em `src/` sem consumidor de produção pelo mesmo levantamento. Isso é uma triagem estática, não prova de ausência de métodos mortos.

Os métodos candidatos `editionOfGame`, `editionOfPartida` e `reconciliarPodioConcluido` em `MysqliPartidaGateway` foram reavaliados por busca de consumidores e removidos individualmente. O arquivo do gateway continua ativo.

As antigas árvores físicas `api/` e `views/` já não existem no checkout. Não há diretórios correspondentes para apagar; restam referências de URL.

### Remoção condicionada à substituição dos usos atuais

| Alvo | O que ainda depende dele | Trabalho anterior à remoção |
| --- | --- | --- |
| `config/routes/compatibility.php` | APIs antigas chamadas pelas telas, componentes, fila e testes | Migrar todas as chamadas para `/api/v1`, ajustar interpretação offline e só então remover o mapa e sua injeção em Kernel/bootstrap |
| `config/assets.php` | Imagens emitidas por componentes/JavaScript e aliases de arquivos antigos | Migrar referências para `Assets::url` ou URLs canônicas configuradas; retirar suporte a aliases no `AssetResponder` |
| Chaves `/views/...` em `config/routes/web.php` | Navegação, autenticação, permissões de páginas, identificação de telas e shell offline | Criar rotas web canônicas e migrar links, redirects e detecção de páginas; conservar o arquivo como mapa de rotas atual |
| Tratamento de upload antigo em `Kernel` | Alias de upload e normalização de `pdf` para `pdf_arquivo` | Usar apenas `/api/v1/importacoes/turma-pdf` e o campo canônico; atualizar formulários/testes |
| Ramo de fila sem `session` em `offline-core.js` | Somente adoção de registros antigos | Exigir identidade explícita nos registros atuais e remover o ramo de adoção, mantendo isolamento de operador e retry |
| Resposta idempotente sem `request_hash` em `MysqliMutationStore` | Registros de versões anteriores | Definir contrato de registros novos e retirar a exceção; não apagar a store nem deduplicação/transação da versão atual |
| `tornarReexecutavel`, `rec.script` e leitura de casca sem `pageSources` | Compatibilidade antiga e extração ainda ativa de scripts inline de componentes | Extrair o programa de `admin-header.php` e demais componentes para arquivos atuais, verificar a casca nova e então eliminar o transformador e o formato antigo |
| `bootstrap-login`, `bootstrap-pages`, `bootstrap-icons-login` | Login, cabeçalhos e páginas do aluno | Removidos do manifesto, lockfile e build; templates unificados em `bootstrap` 5.3.8 e `bootstrap-icons` 1.11.3, com validação de layout pendente apenas da suíte completa final |
| Ramos de nomes/campos antigos nos serviços/JS | Alguns nomes antigos ainda são produzidos pela versão atual | Padronizar produtor e consumidor juntos; não remover somente por comentário ou nome “legacy” |

Contagem indicativa da busca em `src`, `config`, `bootstrap` e `resources`: 151 ocorrências de URLs `api/*.php` em 31 arquivos e 49 ocorrências de `views/src/pages/` em 9 arquivos. Essas contagens não incluem todos os caminhos montados por concatenação nem os links relativos curtos. Uma substituição textual simples é insuficiente.

`mesario-data.js` converte inclusive caminhos `/api/v1` em identificadores internos terminados em `.php`; há tabelas de stores e condicionais baseadas nesses nomes. Migrar esse roteamento interno para nomes canônicos na mesma etapa das chamadas HTTP.

O comentário “legado” no cronômetro não basta para remover `prepararBaseLegada`: `MysqliJogoRepository::create` e a materialização de chaveamento ainda podem criar jogos sem duração. Inicializar o estado atual corretamente e testar criação/primeiro início antes de retirar esse fallback.

### Arquivos a manter

- `resources/`: fontes de JavaScript, CSS, imagens e templates. Todas as oito imagens possuem referências atuais; não há imagem aprovada para exclusão nesta revisão. Remover CSS por ausência de busca textual também seria inseguro devido a classes montadas em execução.
- `public/assets/`: saída gerada e necessária para servir a aplicação; já é ignorada pelo Git. Limpar/regenerar pelo build, não remover do pacote em execução.
- `public/index.php`, `bootstrap/`, `config/routes.php`, `config/routes/web.php`, `src/Shared/Http/`, `public/.htaccess`: composição e infraestrutura atuais.
- `MysqliPartidaGateway`, `MysqliJogoGateway`, `MysqliEquipeGateway`, `MysqliEquipePadraoRepositoryAdapter`: continuam atendendo fluxos atuais; o nome gateway/adapter não significa arquivo descartável.
- `database/migrations/001` a `004`, `MigrationRunner`, checksums e trava: formam a instalação e evolução do schema atual. Não reescrever migrações aplicadas nem apagar a tabela de histórico como parte de limpeza de aplicação.
- `Transaction`, `MysqliTransactionRunner`, interfaces e serviços: camadas complementares, não duplicatas descartáveis.
- Fila offline, IDs temporários, isolamento por operador, tokens CSRF, deduplicação e reconciliação: necessários para a versão atual.
- `composer.lock`, `package-lock.json`, lockfile do navegador, testes de regras, segurança, concorrência e offline, `tools/` e CI.
- `acorn`: utilizado em `tools/check-javascript.cjs`, independentemente do transformador do shell.
- `storage/`, uploads, PDFs, fotos e dados de trabalho. Retirar suporte a versões antigas não autoriza apagar dados existentes.

### Artefatos locais regeneráveis

Podem entrar numa limpeza local seletiva após encerrar os processos correspondentes: `.phpunit.cache/`, `.phpstan/`, `.php-cs-fixer.cache`, `tests/browser/test-results/`, `tests/browser/playwright-report/` e logs temporários escolhidos.

**Não remover `test-results/` inteiro neste ambiente:** ele contém `mysql-t00/`, sessões, uploads de teste e backups de recuperação, além dos relatórios. Há um processo MySQL em execução; não foi confirmado pelo sistema operacional qual diretório de dados ele usa. Identificar a instância e encerrar somente o ambiente descartável pertinente antes de considerar a pasta do banco. Preservar os dumps/evidências que ainda forem necessários.

`node_modules/`, `tests/browser/node_modules/` e `vendor/` são dependências instaladas, não sobras da refatoração. Podem ser reinstaladas por seus lockfiles; apagar para “limpar” não reduz a arquitetura do projeto. No deploy, excluir dependências de desenvolvimento pelo processo de empacotamento.

## Plano de execução

Executar em etapas pequenas, registrando resultado e commit de cada etapa. A ordem abaixo não exige atendimento a clientes de versões anteriores.

Estado após esta implementação: **L00, L01, L02, L03 e L07 concluídos**. L04–L06 e L08–L10 permanecem condicionados às migrações coordenadas descritas abaixo; os aliases de API, páginas e assets não foram apagados enquanto ainda são emitidos pelo cliente atual.

| Etapa | Trabalho | Dependência / aceite |
| --- | --- | --- |
| L00 — Ajustar diretrizes | Atualizar `AGENTS.md`, arquitetura e implantação para suporte somente ao contrato atual. Corrigir a descrição do modo offline. Definir que migrações/dados atuais continuam preservados. | Remover instruções que obrigam aliases e caches antigos; não adicionar compromisso de migração de clientes antigos. |
| L01 — Corrigir consistência | Implementar R01 e R02; adicionar os testes que reproduzem os casos descritos. | Nenhuma confirmação de placar inválido, retificação sem pódio ou atualização parcial silenciosa. |
| L02 — Corrigir CI | Implementar R03; configurar clientes de backup por motor/plataforma. | Matriz PHP 8.2/8.4 de qualidade e MySQL 8.4/MariaDB 10.11 de integração executável. |
| L03 — Excluir arquivos sem uso | Remover as duas classes antigas, os métodos confirmados sem uso e o dump de `database/archive/`; atualizar referências documentais. | Busca de consumidores, `composer verify` e integração aprovados. Não remover diretórios de módulos ativos. |
| L04 — Unificar APIs | Migrar chamadas de páginas/componentes e testes para `/api/v1`; padronizar a identificação de operações em `mesario-data.js`, `offline-core.js`, `chaveamento-engine.js` e `offline-form.js`. Remover `compatibility.php`, injeção de aliases e ramo de upload antigo. | Online e offline completos; nenhuma chamada antiga emitida pela versão atual; antigas APIs sem rota passam a responder 404. Conferir redirects de login/logout e exceções de CSRF. |
| L05 — Unificar páginas e assets | Migrar mapa web, navegação e redirects; injetar base de instalação em configuração, eliminando dependência de profundidade `../../../`. Atualizar `PageController`, controles de acesso e reconhecimento de telas no shell. Migrar imagens para Assets e remover `config/assets.php`. | Login, aluno, administrador e mesário funcionam na raiz e em `/SGI/`; acesso direto a templates/arquivos internos continua bloqueado; recursos antigos respondem 404. |
| L06 — Reduzir compatibilidade offline | Usar somente schema de cache/fila atual; extrair scripts inline de componentes e remover transformador/formato antigo quando sem consumidor. Retirar adoção sem sessão e exceção de fingerprint antigo. | Instalação limpa prepara a casca, navega, registra placar/cronômetro/ocorrências, materializa IDs e sincroniza sem duplicação. Preservar retry e isolamento entre operadores. |
| L07 — Unificar bibliotecas visuais | Padronizar Bootstrap e ícones nas versões já presentes; retirar os três pacotes alternativos do manifesto/lockfile/build e reconstruir assets. | Comparação visual revisada em desktop/mobile, modais e navegação aprovados; não atualizar snapshots apenas para esconder diferenças. |
| L08 — Encerrar instrumentos de migração antiga | Retirar entrada `--baseline`, adoção de créditos antigos e cenários exclusivamente de upgrade, após separar qualquer lógica compartilhada da instalação atual. | Instalação vazia, reaplicação sem delta, checksum, falha/rollback e restauração do schema atual continuam testados. Sem exclusão de dados para viabilizar a remoção. |
| L09 — Consolidar documentação | Incorporar informações operacionais ainda úteis em README, arquitetura, testes e implantação. Depois remover planos concluídos e a auditoria anterior. | Nenhum link ativo quebrado; histórico Git preserva decisões anteriores. |
| L10 — Higiene e aceite final | Limpar apenas artefatos selecionados, revisar diff e executar a matriz final; preparar lista final de exclusões. | Critérios abaixo cumpridos; não fazer deploy automaticamente como parte deste plano. |

L04 e L05 devem ser coordenadas: o login atual devolve caminhos relativos compatíveis com a URL antiga da API, e o shell infere a base a partir de `/views/src/pages/`. Mudar apenas o endereço de login ou a rota da página pode quebrar o redirecionamento e o cálculo das chamadas.

Rotas web sugeridas, a definir consistentemente no mapa: `/login`, `/aluno/login`, `/aluno/inicio`, `/edicoes`, `/edicoes/agenda`, `/jogos`, `/jogos/placar`, `/chaveamento`, `/ranking`. Não é necessário introduzir parâmetros em caminho: os IDs atuais podem continuar em query string. É necessário manter o mapa de templates, não seus endereços antigos.

Ao padronizar a implantação em `public/`, retirar da `.htaccess` da raiz o encaminhamento para instalações antigas somente depois de configurar o servidor. Preferir manter uma regra de negação defensiva na raiz em vez de apagar sua proteção. A ausência de retrocompatibilidade não elimina a fronteira pública do projeto.

## Tratamento dos testes e documentos antigos

- `tests/browser/legacy-offline-compat.spec.cjs`: remover cenários de casca antiga e fila sem sessão; transferir retry após falha de rede e isolamento entre usuários para a suíte do contrato atual antes de excluir o arquivo.
- `tests/Integration/RefactorContractsTest.php`: retirar comparações alias/versionada, preservando CSRF, origem externa, autenticação e bloqueio de templates privados. Renomear se o nome ficar sem propósito.
- `tests/Unit/Architecture/PresentationBoundaryTest.php`: substituir a exigência de URLs `/views/` pela verificação do mapa atual e templates privados existentes.
- `tests/Integration/LegacyUpgradeTest.php`: pode sair em L08 quando seu registro for removido do runner e os testes de migração atual conservarem checksum, estado dirty, repetição e recusa de schema inválido que ainda fizerem parte do contrato.
- `tests/Integration/RecoveryRehearsalTest.php`: adaptar para backup/restauração de uma instalação atual; preservar verificação de dados e triggers, sem exigir upgrade de aplicação anterior.
- `docs/plano-implementacao-luna/`, `docs/migration-completion-plan.md` e `docs/auditoria-arquitetura-2026-09-07.md`: remover em L09 após consolidar conteúdo útil. Não criar outra pasta de arquivo morto no checkout.
- Esta revisão e o plano devem permanecer até o aceite de L10; depois podem ser consolidados pelo mesmo processo.

## Critérios de conclusão

1. R01, R02 e R03 resolvidos com evidências específicas, além dos testes já existentes.
2. `composer verify`, `npm run check`, `npm test`, `npm run build`, `php tests/run_all.php` e a suíte completa de navegador aprovados.
3. Integração/recuperação nos dois bancos da matriz e navegação na raiz e em subdiretório aprovadas.
4. Versão atual não emite URLs antigas nem cria registros no formato removido; testar com cache novo e com dados offline criados pela própria versão atual.
5. Não há aliases de API/assets antigos nem transformador de scripts sem consumidor. Asserções de segurança e retry permanecem.
6. Instalação e evolução de schema funcionam sem reescrever migrações aplicadas; nenhum upload ou dado operacional foi removido como “legado”.
7. Cada diretório aprovado para exclusão está vazio ou tem conteúdo explicitamente inventariado. Antes de exclusão recursiva, conferir caminho absoluto dentro do projeto e ausência de processo usando-o.
8. README, AGENTS e documentação descrevem a mesma arquitetura, rotas e suporte offline que o código entregue.

O ganho imediato comprovado é a retirada de duas classes, três métodos sem consumidores, um dump histórico e três pacotes visuais duplicados, com os diretórios vazios quando aplicável. A redução maior ainda está nas camadas de compatibilidade de API, páginas e assets; elas permanecem até que os usos atuais sejam migrados em conjunto.
