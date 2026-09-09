# Plano de migração completa para arquitetura única — execução pelo Luna

Data: 08/09/2026. Estado: planejado; implementação e validações ainda não executadas.

## 1. Objetivo e autoridade

Entregar o SGI para uma instalação nova, utilizando exclusivamente a arquitetura atual em `bootstrap/`, `src/Modules/`, `resources/` e `public/`. Remover suporte a URLs, registros, scripts e procedimentos de versões anteriores, substituindo primeiro os consumidores que ainda dependem deles.

Este plano atende à instrução do usuário de implantação do zero. Não há obrigação de atualizar clientes ou bancos de versões antigas. Isso não autoriza apagar dados locais, uploads, filas pendentes, alterações de terceiros ou reescrever migrações aplicadas. O funcionamento offline produzido pela própria versão entregue continua obrigatório.

Este documento passa a ser a referência de execução desta migração. Planos anteriores são evidência histórica, não critérios concorrentes. Conferir o código antes de cada etapa: os achados abaixo são uma fotografia, não uma autorização para excluir arquivos cegamente.

Não fazer deploy, publicar, trocar credenciais ou enviar mensagens externas. Não criar outra tarefa automaticamente. Executar sequencialmente; nenhuma etapa depende de subagentes. Não ampliar para troca de framework, redesign, atualização geral de dependências ou renomeação cosmética de tabelas.

## 2. Evidências verificadas

| Área | Evidência no checkout | Consequência |
| --- | --- | --- |
| API | `bootstrap/app.php` importa `config/routes/compatibility.php`, que contém 29 aliases | O backend ainda aceita endereços antigos |
| Consumidores | Busca textual encontrou 151 linhas com `api/*.php` em 31 arquivos de `src`, `config`, `bootstrap` e `resources` | Excluir somente aliases quebra o cliente atual |
| Páginas | `config/routes/web.php` publica 33 endereços antigos; 48 linhas com `views/src/pages/` em 9 arquivos do mesmo escopo | Navegação, redirects, permissões e SPA precisam mudar juntos |
| Assets | `config/assets.php` e `AssetResponder` traduzem caminhos antigos | Links devem usar os arquivos publicados pelo build |
| Upload | `Kernel` traduz página antiga de upload e campo `pdf` | Formulários devem usar a API atual e `pdf_arquivo` |
| Offline | `mesario-data.js` converte recursos v1 de volta em nomes `.php`; `offline-core.js` adota filas sem sessão | Padronizar o reconhecimento das operações e a identidade dos registros |
| Casca | `mesario-offline.js` ainda produz `script` com `tornarReexecutavel` e executa `rec.script` | O transformador tem consumidor atual, apesar da finalidade histórica |
| Banco | `MigrationRunner` e `bin/sgi.php` aceitam baseline; há adoção de pódio antigo | Separar instalação/recuperação atuais de upgrade antigo |
| Integridade | `MysqliMutationStore` admite fingerprint nulo; cronômetro prepara base legada | Corrigir produtores e invariantes antes de retirar os ramos |
| Testes | Há testes de upgrade, aliases, fila sem sessão e casca antiga | Substituir cobertura útil e remover exigências de retrocompatibilidade |

Contagens são linhas encontradas por padrões, não número de chamadas executadas. Repetir o inventário e conferir URLs concatenadas, formulários, CSS, redirects, fixtures e configuração de servidor.

## 3. Arquitetura final e invariantes

- Entrada HTTP exclusivamente por `public/index.php`, composição em `bootstrap/app.php`.
- Módulos: Acesso, Eventos, Participantes, Competicoes, Resultados, Disciplina e Sincronizacao. Preservar contratos entre camadas e testes de fronteira; não recriar Interclasses genérico.
- APIs exclusivamente `/api/v1/...`; páginas por rotas sem extensão; templates privados em `resources/views`.
- CSS, JS e imagens editados em `resources`; build em `public/assets`. Uploads continuam servidos apenas pelos caminhos e validações autorizados.
- URLs geradas por `Url`/`Assets` no PHP e configuração explícita compartilhada no JS. Manter implantação em `/` e `/SGI/` com DocumentRoot em `public`.
- Não confundir suporte a subdiretório, MySQL/MariaDB, campos de negócio existentes ou adaptadores entre módulos com legado descartável.
- Manter autorização por perfil e edição, edição ativa do mesário, CSRF, validação de origem, isolamento de operador, hashes de senha e matrícula por edição.
- Manter transações, fingerprints, identificadores estáveis de mutação, retry, ordenação, exclusão mútua entre abas, IDs negativos e resolução de dependências offline.
- Manter classificação, pódio, retificação, arrecadação e descontos disciplinares consistentes, sem duplicar créditos.
- Não prometer cold-open, refresh ou nova aba offline sem casca preparada; não adicionar Service Worker nesta migração.
- Nenhuma restrição de suporte antigo deve sobreviver apenas com nome diferente. Em contrapartida, funcionalidades atuais não podem ser eliminadas porque um comentário diz “legado”.

## 4. Contratos de destino

### 4.1 APIs e dados

O mapa canônico é `config/routes.php`. Usar o lado direito de cada entrada de `config/routes/compatibility.php` para migrar seus consumidores; não inventar pluralizações. Exemplos críticos: `artilheiro.php` → `/api/v1/artilheiros`, `lancar_resultado.php` → `/api/v1/resultados`, `CriarEquipes.php` → `/api/v1/equipes/gerar`, `sincronizar_chaveamento.php` → `/api/v1/sincronizacao/chaveamento`.

Manter parâmetros de negócio e query strings atuais quando válidos. Elaborar inventário por operação: método, URL, entrada, resposta, autenticação, CSRF, consumidor, projeção offline e teste. Verificar `acao=` e variantes de payload: conservar ações de negócio atuais, remover somente formatos alternativos de compatibilidade após migrar seus produtores.

Não padronizar todo JSON por substituição textual. Para cada operação, definir um contrato único, incluindo erro e confirmação de sucesso, e atualizar produtores, consumidores, mocks e testes juntos. `status: sucesso`, `success`, `data` e outros nomes não são prova isolada de legado. A fila nunca deve tratar HTTP 200 com HTML, JSON inválido ou erro de negócio como confirmação persistida.

Novas mutações de perfil devem ir para `/api/v1/perfil`: mover o POST tratado por `PageController` para composição HTTP de API, reutilizando `PerfilController`/`PerfilService`, com autorização do próprio usuário e CSRF. Páginas de perfil ficam somente GET/HEAD. Testar formulário real, foto, senha e ausência de atualização de outro usuário.

### 4.2 Mapa web final

Substituir as chaves antigas pelo mapa abaixo, mantendo os mesmos templates e parâmetros. A coluna antiga abrevia `/views/src/pages/`, exceto a entrada de login.

| Origem | Destino |
| --- | --- |
| `/views/index.php` | `/login` |
| `alunos/home.php` | `/aluno/inicio` |
| `alunos/jogos.php` | `/aluno/jogos` |
| `alunos/login.php` | `/aluno/login` |
| `alunos/modalidade.php` | `/aluno/modalidades` |
| `alunos/perfil.php` | `/aluno/perfil` |
| `alunos/ranking.php` | `/aluno/ranking` |
| `alunos/termos.php` | `/aluno/termos` |
| `categorias.php` | `/categorias` |
| `chaveamento_arvore.php` | `/chaveamento` |
| `colaboradores.php` | `/colaboradores` |
| `dashboard.php` | `/painel` |
| `edicao_agenda.php` | `/edicoes/agenda` |
| `edicao_arrecadacao.php` | `/edicoes/arrecadacao` |
| `edicao_categorias.php` | `/edicoes/categorias` |
| `edicao_equipes.php` | `/edicoes/equipes` |
| `edicao_locais.php` | `/edicoes/locais` |
| `edicao_modalidades.php` | `/edicoes/modalidades` |
| `edicao_pontuacao.php` | `/edicoes/pontuacao` |
| `edicao_resumo.php` | `/edicoes/resumo` |
| `edicao_turmas.php` | `/edicoes/turmas` |
| `elenco_equipe.php` | `/equipes/elenco` |
| `equipe_alunos.php` | `/equipes/alunos` |
| `home.php` | `/edicoes` |
| `jogos.php` | `/jogos/placar` |
| `jogos_lista.php` | `/jogos` |
| `modalidade_detalhes.php` | `/modalidades/detalhes` |
| `modalidades.php` | `/modalidades` |
| `ocorrencias.php` | `/ocorrencias` |
| `perfil.php` | `/perfil` |
| `ranking.php` | `/ranking` |
| `turma_alunos.php` | `/turmas/alunos` |
| `turmas.php` | `/turmas` |

`/` redireciona para `/login`. `/aluno/login` é uma entrada atual do portal: conferir o template e manter seu comportamento intencional usando somente destinos novos. Não manter redirects dos caminhos `/views/...`. Endereços antigos devem ficar sem rota e responder 404 em consultas GET anônimas e autenticadas; requisições inválidas podem ser rejeitadas antes por segurança. `public/index.php` continua existindo como entrada técnica, sem virar alias de uma página antiga.

### 4.3 Configuração de URL e identificação de página

Usar `Url::basePath()` como fonte da base e serializar configuração com `json_encode` seguro para HTML. Definir um helper compartilhado para APIs, páginas e assets; carregar antes de qualquer consumidor, também na casca. Não inferir base por quantidade de `../`, `/views/`, último segmento ou extensão `.php`.

Permissões, carregamento de contexto, perfil e identificação de telas devem usar metadados explícitos de rota/página. Eliminar em `PageController` os testes por `/alunos/` e `/perfil.php`; preservar os contextos especiais de jogos/modalidades do aluno. Evitar um segundo mapa de rotas divergente no JS: injetar os destinos necessários a partir da configuração PHP.

## 5. Sequência de execução

Cada etapa deve registrar arquivos, alterações de contrato, comandos, resultados, limitações e próximo passo em `docs/status-migracao-arquitetura-unica.md`. Criar esse status ao iniciar a implementação. Não registrar “passou” com base em execuções antigas. Ao retomar, ler status e diff; não recomeçar nem descartar alterações.

### M00 — Preparar e obter referência reproduzível

1. Ler `AGENTS.md`, este plano, `docs/testing.md`, CI e estado Git. Inventariar alterações preexistentes, inclusive `output/`; preservá-las.
2. Trabalhar em branch com prefixo `codex/` quando apropriado, sem sobrescrever branch ou trabalho existente. Commits locais pequenos, se permitidos pelo ambiente, somente com arquivos da etapa; nunca usar limpeza/reset indiscriminados.
3. Confirmar executáveis PHP/Node/Composer, extensões e banco isolado. Seguir `tools/start-test-server.ps1`; conferir saúde em modo test e nome da base antes de qualquer reset. Não copiar segredos para relatórios.
4. Executar referência completa: `composer verify`, `npm run check`, `npm test`, `npm run build`, `php tests/run_all.php`, `npm --prefix tests/browser test`. Suítes que escrevem no banco são sequenciais.
5. Registrar falhas preexistentes com reprodução. Se não houver ambiente, preparar o que for possível e registrar verificação pendente; não afirmar que a migração está validada.
6. Gerar o inventário de contratos de 4.1 e consumidores de rotas, assets, formatos, CLI e campos antigos. Conferir também `.htaccess`, CI e documentação.

Aceite: referência registrada, ambiente isolado confirmado e lista de consumidores suficiente para cada substituição. Testes existentes aprovados não provam ausência de legado.

### M01 — Preparar contratos e cobertura de regressão

1. Implementar configuração compartilhada de base/URLs e metadados de página, aproveitando `Url`, `Assets` e `SGIPage` existentes.
2. Adaptar testes de URL para raiz e subdiretório, query strings e caminhos aninhados; manter bloqueio de arquivos privados.
3. Preparar testes de contratos canônicos e matriz de permissões para os quatro perfis. Não introduzir novas rotas antigas.
4. Inventariar os scripts inline de páginas e componentes e sua ordem de execução. Separar JSON de configuração de programa executável.

Aceite: infraestrutura nova testada sem perda de comportamento. Compatibilidade existente pode permanecer temporariamente apenas até M03; isso não constitui entrega final.

### M02 — Migrar API, páginas, navegação e operações offline em conjunto

Arquivos centrais: `config/routes/web.php`, `config/routes.php`, `PageController`, `Kernel`, controllers de Acesso, `CsrfGuard`, componentes/templates, `resources/js/pages/`, `http-client.js` e os quatro arquivos de `resources/js/offline/`.

1. Aplicar o mapa web, configurações e metadados. Migrar links, formulários, redirects de login/logout/termos, menus, fotos e referências CSS. Login retorna destino com base correta, sem depender do endereço da API.
2. Migrar todas as chamadas para `/api/v1`, incluindo requests montados por concatenação, XHR, axios, formulários e fixtures.
3. Criar `/api/v1/perfil` conforme 4.1. Migrar upload para `/api/v1/importacoes/turma-pdf` e `pdf_arquivo`; verificar autenticação, limites, mensagens e limpeza de temporários.
4. Revisar CSRF pela rota normalizada canônica. Hoje a exceção de `usuarios.php?acao=validar_inscricao` é específica do alias: verificar semântica e métodos antes de decidir se a operação pública ainda exige exceção. Não isentar o recurso inteiro. Provar que autenticação e origem continuam corretas.
5. Substituir IDs de operação `.php` nos adaptadores offline por identificadores canônicos. Reconhecer o caminho completo relativo à base, incluindo `/equipes/gerar` e `/sincronizacao/chaveamento`, sem usar apenas o último segmento. Validar origem antes de interceptar.
6. Atualizar captura, seleção de store, projeção otimista, dependências, reenvio, materialização de IDs e resposta de cronômetro para os mesmos identificadores. Não gerar novo mutation ID em retry.
7. Atualizar shell: preload, lista de telas, interceptação de navegação, histórico, configuração de página e URLs de scripts/assets. Conferir query strings de edição/jogo.
8. Migrar testes para as novas rotas sem enfraquecer asserções de negócio. Não manter versões duplicadas do mesmo teste somente para testar aliases.

Aceite: fluxos funcionam exclusivamente por URLs novas na raiz e em `/SGI/`. Executar suíte completa antes e após a etapa; verificar tráfego real das jornadas, erros de console e respostas 404 inesperadas. Não entregar M02 isoladamente como migração concluída.

### M03 — Remover roteamento e publicação legados

1. Remover `config/routes/compatibility.php`, sua importação, argumento de aliases e tradução no `Kernel`.
2. Remover transformação do upload antigo/campo alternativo no `Kernel`.
3. Após zerar consumidores, remover `config/assets.php` e injeção de aliases no `AssetResponder`. Preservar resolução segura de `/assets` e `/uploads`, MIME, métodos e proteção contra traversal.
4. Substituir encaminhamento da `.htaccess` da raiz por proteção defensiva compatível com DocumentRoot em `public`; revisar `public/.htaccess` e servidor de teste. Não expor configuração, código, banco, dependências, logs ou testes.
5. Remover exceções/redirects antigos de autenticação e comentários que prometem atender clientes instalados antigos.
6. Adicionar testes negativos usando inventário explícito dos 29 aliases, upload antigo, todas as páginas antigas e aliases de assets; não depender do arquivo removido para gerar os testes.

Aceite: GETs antigos dão 404, rotas novas continuam funcionando, nenhum consumidor de produção emite o contrato removido e segurança de arquivos continua aprovada.

### M04 — Simplificar a casca sem perder reentrada de telas

1. Extrair programas inline dos componentes, especialmente `admin-header.php`, navegações e demais scripts inventariados, para fontes atuais. Manter somente dados serializados inline quando necessários.
2. Usar o ciclo existente `SGIPage`/`page-runtime.js` e closures por tela. Definir inicialização, reativação e descarte de listeners, timers, modais e recursos. Não duplicar globais a cada navegação.
3. Fazer o produtor de cache gravar somente o formato atual com versão explícita e fontes/metadados necessários. Depois remover `tornarReexecutavel`, criação/execução de `rec.script` e leitura de cache sem `pageSources`.
4. Cache ausente/inválido não pode ser executado por fallback antigo. Online: preparar novamente. Offline sem casca válida: apresentar estado claro de indisponibilidade sem apagar mutações.
5. Preservar ordem de dependências: configuração, bibliotecas, interceptação offline, cliente CSRF e scripts da tela. Inspecionar o comportamento efetivo, não confiar só na ordem dos nomes.
6. Manter `acorn` enquanto usado pelo verificador de JS. Extrair scripts não autoriza retirar handlers HTML ainda consumidos: migrá-los ou manter a exposição atual de ações pelo runtime.

Aceite: visitar/revisitar placar, agenda, ocorrências e chaveamento várias vezes online/offline sem redeclaração, clique duplicado, listener/timer residual ou modal travado. Testar retorno por histórico e mudança entre jogos.

### M05 — Remover formatos antigos de fila, sessão e idempotência

1. Exigir identidade explícita de operador nos novos registros; remover `filaLegadaPertenceASessao` e adoção de filas sem sessão. Não atribuir registros inválidos ao usuário atual nem excluí-los silenciosamente.
2. Verificar todos os produtores e caminhos de login/preload antes de retirar geração de namespace efêmero para sessão legada em `OfflineSession`. Sessão autenticada inconsistente deve exigir nova autenticação; páginas públicas não devem depender de namespace autenticado.
3. Preservar identidade estável do mesmo operador após expiração de sessão e isolamento entre operadores/edições. Retomar fila atual após login do mesmo usuário com token CSRF vigente; não reaproveitar credencial de outro usuário.
4. Remover aceitação de fingerprint nulo em `MysqliMutationStore`. Garantir que todos os INSERTs atuais forneçam fingerprint. Se necessário, acrescentar migração para NOT NULL após verificação explícita de dados válidos; não inventar fingerprint para payload desconhecido nem reescrever a 002.
5. Testar mesma identidade/mesmo payload como replay idempotente e mesma identidade/payload distinto como conflito sem nova escrita. Garantir atomicidade entre efeito e registro de replay.
6. Transferir de `legacy-offline-compat.spec.cjs` retry e isolamento para testes do contrato atual antes de excluir o arquivo. Remover apenas cenários que exigem interpretar registros antigos.

Aceite: fila da versão nova sincroniza após falha, sessão expirada, navegação e reconexão sem perda/duplicação. Nenhum leitor adota formatos históricos. Preservar schema e IDs atuais onde não é necessário alterá-los; testar dados pendentes criados pela própria versão nova, sem limpar IndexedDB para esconder defeitos.

### M06 — Eliminar inicializações e créditos de origem antiga

1. Inventariar todos os produtores de jogos: criação manual, agenda, geração/materialização de chaveamento, sincronização e fixtures.
2. Definir o estado inicial válido do cronômetro a partir das regras atuais, sem inventar duração arbitrária. Se jogo ainda não configurado for estado legítimo, representá-lo explicitamente no contrato atual e impedir início inválido.
3. Corrigir produtores e consumidores; só depois retirar `prepararBaseLegada` e fallbacks equivalentes em gateways. Testar primeiro início de jogo manual e derivado, pausa, retomada, acréscimo, encerramento e recarga online.
4. Remover `pontuacao:adotar`, método `adotar`, tratamento `legado_conferido` e caminhos exclusivos de conciliação histórica. Revisar `PodioRepository`, `MysqliPodioRepository`, `PontuacaoService`, `MysqliIndividualRepository` e testes.
5. Conferir `MysqliHistoricoTurmaRepository` e ranking de administrador/aluno. Substituir “saldo/ajuste legado” por registros atuais completos; não esconder diferença de saldo apenas removendo o rótulo. Se ajuste manual existir como função atual, preservá-lo com origem explícita e teste.
6. Manter diagnóstico de integridade útil para instalação atual; remover somente os campos/comandos exclusivos de adoção antiga. Erros de consistência devem continuar detectáveis.

Aceite: retificação inverte vencedor e créditos corretamente; mata-mata e individual, arrecadação/reavaliação e ocorrências reconciliam o total com o histórico. PUT de placar negativo ou de jogo encerrado continua rejeitado; payload misto de agenda/cronômetro não confirma atualização parcial.

### M07 — Instalação e recuperação somente da arquitetura atual

1. Remover parâmetro/opção `--baseline`, `assertBaseline` e adoção de schema sem histórico. A CLI deve rejeitar opções removidas com saída não zero, sem ignorá-las silenciosamente.
2. Base vazia: aplicar migrações em ordem. Base atual com histórico: verificar checksums e aplicar apenas pendências. Base preexistente sem histórico: recusar claramente, sem adotar ou apagar dados.
3. Preservar migrações 001–004 e seus checksums conforme AGENTS; elas compõem a instalação atual. Mudanças necessárias usam próxima migração numerada disponível. Não criar um segundo dump instalador concorrente.
4. Remover `LegacyUpgradeTest` do runner depois de transferir testes úteis: repetição sem delta, checksum alterado, estado dirty, concorrência/trava e recusa de schema inválido.
5. Reescrever `RecoveryRehearsalTest` para instalar schema atual vazio, popular pelas regras atuais, fazer backup e restaurar em outra base descartável. Conferir dados, triggers, versões e integridade após restauração. Não partir de `createLegacySchema`.
6. Não pressupor rollback transacional de DDL MySQL/MariaDB. Testar falha parcial, sinalização dirty e recuperação documentada; manter rollback transacional para mutações de negócio.
7. Validar clientes de dump/restauração e healthcheck nos dois motores da matriz; não inferir compatibilidade pelo sucesso no XAMPP.

Aceite: instalação vazia, repetição, evolução por nova migração quando houver e restauração atuais aprovadas nos dois bancos. Nenhuma opção operacional de upgrade legado permanece.

### M08 — Consolidar documentação e impedir reintrodução

1. Atualizar README, AGENTS, arquitetura, implantação, testes e README do navegador para os contratos finais. Corrigir referência de AGENTS a `api/lancar_resultado.php` e transformador removido.
2. Consolidar conteúdo útil antes de retirar planos antigos: `docs/plano-implementacao-luna/`, `migration-completion-plan.md`, auditoria anterior e revisão pós-Luna. Revisar também planos/status offline; não apagar conteúdo operacional ainda único.
3. Não criar pasta de arquivo morto. Histórico fica no Git; este plano e status ficam até o aceite, depois podem ser condensados em documentação operacional.
4. Criar verificação de arquitetura para ausência de aliases, URLs antigas emitidas e símbolos de fallback removidos. Escopo de produção separado de testes negativos e histórico de migrações. Evitar regex indiscriminada que proíba `.php` de templates privados ou `baseline` de CSS.
5. Revisar arquivos aparentemente sem uso por consumidores reais. Preservar gateways/adapters ativos, dependências instaladas, locks, fontes, storage e uploads. Não limpar `test-results/` recursivamente: pode conter banco ativo e backups.

Aceite: documentação coerente, links válidos e guardas automáticas contra retorno dos contratos removidos. Arquivos de produção sem referências legadas executáveis.

### M09 — Aceite integrado e entrega

Executar novamente todas as verificações de M00 no estado final, seguidas da matriz abaixo. Revisar diff completo, exclusões, contratos e código gerado pelo build. Não alterar snapshots visuais para mascarar diferença: inspecionar qualquer mudança e manter aparência atual.

Entregar resumo de arquivos removidos, contratos substituídos, evidências por ambiente e limitações. Marcar concluído somente quando todos os critérios obrigatórios tiverem evidência; matriz indisponível permanece pendente, não aprovada por dedução.

## 6. Matriz obrigatória de validação

| Grupo | Cenários e resultado esperado |
| --- | --- |
| Qualidade | `composer verify`, `npm run check`, `npm test`, `npm run build` aprovados; sem enfraquecer regras para passar |
| HTTP | `php tests/run_all.php`; CRUDs, importação PDF, sessão, autorização, resultado, recuperação e concorrência |
| Navegador | `npm --prefix tests/browser test`; fixtures e global setup usando somente rotas atuais |
| Perfis | Login/logout dos níveis 0–3; operações vedadas continuam vedadas; mesário somente edição ativa; aluno somente seus dados |
| Participantes | PDF, turmas, equipes, termos, inscrição, limites, gênero, categorias, perfil, foto e senha |
| Competições | Agenda, primeiro início do cronômetro, pausa/retomada, gols/artilheiros, conclusão, retificação, individual e mata-mata |
| Chaveamento | Torneio completo e número ímpar de equipes; avanço automático; jogo negativo materializado sem duplicação |
| Offline | Casca limpa preparada online → perda de rede → navegação/mutações → reconexão → conferir persistência SQL e fila vazia |
| Falhas de rede | Soft-offline, erro transitório, resposta perdida após commit, HTTP 200 inválido, conflito de payload; fila preservada até confirmação válida |
| Identidade | Troca de operador não consome fila alheia; mesmo operador reautentica e retoma pendências atuais; coordenação entre abas |
| Persistência local | Aborto de transação IndexedDB não confirma gravação; exportação/importação atual idempotente e sem credencial CSRF |
| Reentrada SPA | Repetir navegação e operações sem handlers/timers duplicados, estado de outro jogo ou falha em modais |
| Pontuação | Histórico reconcilia com total; retificação, reavaliação de arrecadação e disciplinas sem crédito duplicado |
| Implantação | Servidores em `/` e `/SGI/`, ambos com raiz pública correta; login, assets, links profundos, API e jornada offline representativa |
| Segurança | CSRF/origem, perfis, edição, traversal e acesso direto a templates/config/logs/SQL; URLs removidas sem rota |
| Plataformas | Qualidade PHP 8.2/8.4; integração/recuperação MySQL 8.4 e MariaDB 10.11; visual Windows conforme referências existentes |

Ampliar CI para executar verificação de subdiretório, que não fica comprovada pela execução atual somente na raiz. Usar processos separados e bases isoladas ou execuções sequenciais; não resetar base em uso por outra suíte. Registrar URL, PHP, motor/versão e resultado sem senhas.

## 7. Regras de contenção e recuperação

- Ao surgir regressão, parar a etapa dependente, reproduzir o menor caso, corrigir e repetir testes afetados mais suíte obrigatória da etapa. Não continuar acumulando falhas.
- Cada alteração deve incluir produtor, consumidor e teste correspondentes. Compatibilidade temporária só é tolerada durante implementação; M09 deve comprovar sua remoção.
- Não reduzir asserções, trocar testes reais por mocks de sucesso ou aumentar retries para esconder falhas. Preservar regressões de segurança, consistência e offline ao remover testes antigos.
- Reverter uma etapa significa desfazer apenas suas mudanças identificadas; nunca `reset --hard`, limpeza geral ou restauração de arquivos de terceiros.
- Recuperação do ambiente de teste usa apenas banco isolado verificado. Não usar migração reversa destrutiva como substituto de backup/restauração.
- Se houver formato antigo inesperado em dados reais locais, não criar compatibilidade nova nem apagar dados: registrar o caso e manter esses dados fora dos testes descartáveis. A entrega nova continua direcionada à instalação vazia.
- Se uma mudança envolver dados pendentes da versão nova, comprovar preservação e retry. “Implantação do zero” não permite perder dados gerados após o primeiro uso.

## 8. Checklist de conclusão

- [ ] M00–M09 concluídas com evidências recentes.
- [ ] Nenhum alias de API, página ou asset antigo registrado.
- [ ] Nenhuma chamada antiga emitida pelo frontend atual, incluindo URLs montadas e navegação offline.
- [ ] Páginas e APIs usam configuração explícita de base; raiz e subdiretório aprovados.
- [ ] Nenhum transformador de script ou leitor de casca/fila antiga.
- [ ] Nenhuma adoção de fingerprint ausente, sessão legada, baseline ou pódio histórico.
- [ ] Primeiro uso cria estados válidos sem reparo legado.
- [ ] Instalação, repetição, integridade e recuperação do schema atual aprovadas.
- [ ] Todos os fluxos funcionais, permissões, pontuação e offline preservados.
- [ ] Testes negativos comprovam ausência das rotas removidas; testes atuais preservam regras de negócio.
- [ ] Qualidade, integração, navegador, visual e matriz documentados sem pendências ocultas.
- [ ] Nenhuma migração aplicada reescrita; nenhum dado operacional ou alteração de terceiro removido.
- [ ] Documentação descreve exclusivamente a operação atual; diff final revisado.

## 9. Instrução pronta para o Luna

> Implemente integralmente `docs/plano-migracao-arquitetura-unica-luna.md`, seguindo AGENTS.md. O destino é uma instalação nova do SGI, sem compatibilidade com versões anteriores. Comece pela referência de testes e pelo inventário, mantenha `docs/status-migracao-arquitetura-unica.md` atualizado e execute M00–M09 em ordem. Migre produtores e consumidores juntos; preserve regras de negócio, segurança, pontuação e offline atual. Não pare após trocar URLs: retire aliases, formatos antigos, transformador, baseline e adoção histórica, e conclua a documentação e validação. Não apague dados nem reescreva migrações aplicadas. Não faça deploy. Se algum teste ou ambiente estiver indisponível, registre a limitação e não declare aceite completo sem evidência.
