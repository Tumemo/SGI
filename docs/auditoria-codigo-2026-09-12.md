# Auditoria do código — 12/09/2026

Base: `d3c4429a` (`fix: implement SGI audit remediation plan`). O checkout estava limpo no início. Esta entrega é uma auditoria e um roteiro para o Luna; não implementa as correções nem altera o modelo selecionado.

Ponto de entrada para implementação: [plano e prompt do Luna](plano-correcao-luna-2026-09-12/README.md). Controle de execução: [STATUS](plano-correcao-luna-2026-09-12/STATUS.md).

## Escopo e limites

Foi feita uma varredura transversal de rotas, código PHP, JavaScript, templates, migrações, testes, executores e CI, com leitura aprofundada dos caminhos de autorização, persistência, placar, disciplina e sincronização. O inventário de `src`, `resources`, `bootstrap`, `config`, `database`, `tools`, `tests` e `.github` contém 513 arquivos versionados. Isso é inventário de escopo, não uma afirmação de leitura manual linha a linha dos 513 arquivos. Dependências e assets gerados não foram tratados como código autoral.

Os achados abaixo têm causa localizada no código corrente. As sondagens usam apenas dados sintéticos e os próprios adaptadores/rotas. Onde o teste só exercita uma função ou DOM controlado, isso é explicitado. Uma revisão ampla e uma suíte verde não garantem ausência de outros bugs. Não foi realizada uma auditoria de CVEs de dependências nem teste de uma implantação de produção.

O plano de 11/09 está concluído no commit auditado. Não reaplicar os seus achados como trabalho novo: esta rodada identifica caminhos que ficaram fora daquelas correções.

## Prioridades

P1: corrigir antes de disponibilizar a instalação para uso real; há exposição de dados, contorno de autorização ou corrupção de resultados/vínculos. P2: defeito funcional ou de operação que também deve ser corrigido nesta rodada. Não foi identificado um P0 nesta revisão.

| ID | Prioridade | Problema | Tarefa |
|---|---|---|---|
| A01 | P1 | PDF de importação acessível sem autenticação | L01 |
| A02 | P1 | HTML de categoria e atributos de equipe permitem injeção de script | L02 |
| A03 | P1 | GET de equipes permite ao aluno criar registros | L03 |
| A04 | P1 | Inclusão administrativa de atletas ignora elegibilidade e capacidade | L04 |
| A05 | P1 | Equipe com elenco e partidas pode ser transferida para outra edição | L05 |
| A06 | P1 | Edição de jogo permite quebrar os vínculos de modalidade/local | L06 |
| A07 | P1 | Cronômetro consegue encerrar jogo sem finalizar o resultado | L07 |
| A08 | P1 | Sincronização coletiva aceita final empatada e estado inconsistente | L08 |
| A09 | P1 | Descrição da ocorrência pode substituir referências já autorizadas | L09 |
| A10 | P2 | Vermelho automático não acompanha correção dos amarelos | L10 |
| A11 | P2 | Transferência de aluno muda o destinatário das penalidades históricas | L11 |
| A12 | P2 | Tela de adicionar atletas usa `esc` inexistente | L12 |
| A13 | P2 | Executor Windows não preserva configuração vazia contra `.env` | L00 |
| A14 | P2 | Atualização de papel permite remover o último administrador | L13 |

## A01 — Arquivo de importação exposto pelo servidor público

**Localização:** `src/Shared/Http/AssetResponder.php:24`, especialmente o mapeamento `uploads/turmas` na linha 35; `src/Shared/Http/Kernel.php`, determinação de `$protectedRoute`; `src/Modules/Participantes/Infrastructure/TurmaPdfStorage.php`, método `process`.

O upload exige administrador/colaborador, mas a leitura posterior não exige sessão. `AssetResponder` resolve `/uploads/turmas/turma_<id>.pdf` e o entrega com `Cache-Control: public`. O nome é previsível. O PDF importado contém as linhas usadas para extrair nome, matrícula, nascimento e gênero; estar fora de `public/` no disco não o torna privado quando esse adaptador o publica.

**Reprodução:** colocar somente um PDF sintético no diretório isolado e fazer GET sem cookie à URL correspondente. Verificar corpo e cabeçalhos, não apenas o status. Também testar HEAD.

**Correção L01:** retirar PDFs de turma da lista de arquivos públicos. Se houver necessidade de download, criar uma leitura autenticada com autorização de administrador/colaborador, identificação da turma pelo banco e resolução por `StoragePaths`/`PublicFileResolver`. A rota pública antiga deve recusar o arquivo mesmo para quem conhece o nome. Resposta privada não deve ser armazenável por cache compartilhado. Conferir os consumidores antes de mudar a URL. Manter acesso ao regulamento necessário antes do aceite de termos.

**Regressão:** ampliar `tests/Integration/PublicBoundaryTest.php`: anônimo, aluno e mesário sem acesso; administrador autorizado apenas pelo novo caminho, se este for necessário; traversal e arquivo inexistente; raiz e subdiretório. Usar arquivo sintético, nunca um PDF real de alunos.

## A02 — Escape incorreto em texto HTML e atributos

**Localização:** `resources/js/pages/eventos/configurar-turmas.js:56`; `resources/js/pages/eventos/configurar-equipes.js:20`, `:98`, `:106`, `:149`; helper seguro já existente em `resources/js/shared/html-utils.js`.

A primeira tela insere `cat.nome_categoria` diretamente em `innerHTML`. A segunda usa um `esc` baseado em `div.textContent` seguido de `div.innerHTML`: esse procedimento escapa `<` e `&`, mas preserva aspas, permitindo sair de atributos como `data-mod-nome="..."`. Categoria/modalidade/turma são entradas persistentes editáveis por rotas administrativas; colaboradores também podem gravar alguns desses dados. O script pode executar na sessão de outro operador que abra a tela.

**Evidência:** no Chromium, o nome de categoria sintético `<svg onload="window.auditXss=1">` se tornou um elemento SVG com handler executável. A sondagem disparou explicitamente o evento `load` e observou o marcador `1`; não dependeu de um disparo espontâneo de SVG. O valor `" onmouseover="window.auditAttrXss=1` atravessou o helper de atributos e criou um atributo `onmouseover`. Não houve exfiltração ou interação com dados reais.

**Correção L02:** preferir `textContent`, `dataset` e construção de nós. Quando preservar templates HTML, usar `SGIHtml.escape`, que inclui aspas simples e duplas, no contexto apropriado. Reexaminar todos os helpers locais semelhantes e seus usos em atributos; não fazer substituição global sem conferir o contexto. Para URLs, usar os resolvedores e codificação de parâmetros, pois escape HTML não valida esquema de URL. Não remover os ícones ou exigir sanitização destrutiva dos nomes no banco.

**Regressão:** teste de navegador com valores contendo `<`, `>`, `&`, ambas as aspas e nomes acentuados, gravados pela API; abrir as duas telas e acionar eventos relevantes. Asserir texto literal, ausência de elementos/handlers injetados e comportamento normal dos botões. Cobrir retorno pela navegação suportada. Se o layout mudar, executar também contrato visual.

## A03 — GET cria equipe com privilégios de aluno

**Localização:** `src/Modules/Competicoes/Presentation/Http/EquipeController.php:23`; `src/Modules/Competicoes/Infrastructure/MysqliEquipeGateway.php:41`.

GET é autorizado para níveis 0–3. O controlador força `_read_only=true` somente para mesário. Ao receber `id_modalidade` e `id_turma`, o gateway chama `buscarOuCriarEquipePadrao` para os demais perfis, incluindo aluno. A mutação ocorre sem CSRF, pois é GET. O filtro de leitura não é uma fronteira de autorização.

**Reprodução:** aluno autenticado e com termos aceitos consulta uma combinação válida de modalidade/turma sem equipe ativa. A contagem de equipes aumenta. É possível usar uma turma diferente da do aluno na mesma categoria da modalidade.

**Correção L03:** tornar toda consulta GET estritamente de leitura, inclusive para administradores. Mover qualquer garantia necessária de equipe padrão para uma mutação autorizada e protegida por CSRF ou para o caso de uso de preparação/inscrição já existente. Não aceitar `_read_only` do cliente como permissão de escrita. Conferir as telas que dependiam da criação implícita e preservar a preparação legítima.

**Regressão:** GET e HEAD não criam, reativam ou renomeiam equipes para nenhum perfil; consulta repetida mantém a contagem. POST de preparação funciona para perfis permitidos e falha sem CSRF ou com aluno/mesário. Confirmar o portal de inscrição após retirar o efeito colateral.

## A04 — Inclusão administrativa contorna regras de inscrição

**Localização:** `src/Modules/Competicoes/Presentation/Http/EquipeController.php:68`; `src/Modules/Competicoes/Application/EquipeService.php`, `adicionarUsuarios`; `src/Modules/Competicoes/Infrastructure/MysqliEquipeRepository.php:58`.

O caso de uso normaliza IDs positivos, e o repositório executa `INSERT IGNORE` em `equipes_has_usuarios`. Não valida edição, turma, nível, atividade do aluno/equipe, gênero/categoria, capacidade ou total de modalidades. As FKs validam a existência dos IDs, não a coerência entre eles. A correção anterior de `/inscricoes` não cobre este endpoint.

**Reprodução mínima:** administrador envia `acao=adicionar_usuarios` para equipe da edição A com ID de aluno inativo da edição B. A associação é persistida. Acrescentar cenários de última vaga, gênero incompatível e aluno já inscrito em três modalidades na regressão definitiva.

**Correção L04:** estabelecer regra compartilhada de elegibilidade de elenco, reaproveitando os contratos existentes sem importar infraestrutura de outro módulo. Validar contexto real sob transação; bloquear usuário/modalidade/equipe em ordem compatível com inscrição, criação e redistribuição. Preservar reenvio sem duplicação. Usar `Transaction`/`TransactionRunner` em vez de `begin_transaction` avulso quando houver composição. Não copiar cegamente toda a política do portal: o cadastro administrativo pode preparar elenco antes do aceite de termos; conferir e documentar essa diferença sem afrouxar edição, identidade ou capacidade. Para um lote inválido, adotar rejeição atômica e mensagem verificável, a menos que contrato explícito já exija parcialidade.

**Regressão:** unidade do caso de uso e HTTP com matriz de elegibilidade, limites, duplicatas e rollback. Concorrência entre inscrição do aluno e inclusão administrativa disputando a última vaga, com conexões/barreiras existentes. Conferir que os dois caminhos juntos não ultrapassam o limite.

## A05 — Transferência de equipe deixa descendentes na origem

**Localização:** `src/Modules/Competicoes/Infrastructure/MysqliEquipeRepository.php:101`, `lockScope`; `EquipeService::atualizar`.

O update valida a modalidade e turma de destino entre si, mas não examina os descendentes da equipe. Uma equipe A já referenciada por `partidas` e `equipes_has_usuarios` pode receber modalidade/turma B. O jogo continua em A, enquanto seu participante passa a ser uma equipe de B. Elenco, pontos e consultas históricas ficam incoerentes.

**Correção L05:** calcular o estado final sob bloqueio. Recusar mudança estrutural de turma/modalidade com elenco, partidas, pontos ou outros vínculos que seriam invalidados; permitir alterações de nome e status já autorizadas. Para equipe vazia e sem histórico, validar destino, capacidade e elegibilidade. Não apagar vínculos para conseguir transferir. Examinar todas as FKs das migrações atuais e não limitar a proteção apenas a equipes ativas.

**Regressão:** mudança A→B com elenco, com partida, com ponto e com equipe inativa deve preservar integralmente o estado original; mudança válida de equipe vazia e update sem troca estrutural continuam funcionando. Testar concorrência com inserção de descendente para que a verificação não seja apenas um SELECT anterior à transação.

## A06 — Atualização de jogo não valida o conjunto de vínculos

**Localização:** `src/Modules/Competicoes/Infrastructure/MysqliJogoGateway.php:134`, especialmente a lista de campos e a montagem de `$candidate`; `MysqliJogoRepository::create`; `MysqliLocalScheduleGuard::lockLocals`.

O PUT aceita `modalidades_id_modalidade` e `locais_id_local`, mas a transação nova de agenda protege conflito de horário, não a identidade esportiva. É possível mover um jogo com partidas para modalidade de outra edição sem mover suas equipes. A trava do local verifica existência, mas não demonstra que ele pertence à edição/modalidade nem que está ativo/disponível. Os contratos de criação e alteração precisam ser confrontados, pois o update também não reutiliza toda a validação temporal de `JogoService::agendar`.

**Correção L06:** adicionar validação de estado final à atualização, dentro da transação. Recusar troca de modalidade incompatível com participantes, pontos, chaveamento, pódio ou reservas já existentes. Validar vínculo e disponibilidade do local tanto no POST como no PUT. Validar data/horários e término posterior ao início quando houver janela completa, preservando os campos nulos permitidos para jogos pendentes. Proteger tags estruturais de chaveamento contra renomeação arbitrária por atualização de agenda. Reutilizar a trava por local introduzida anteriormente; não criar outro protocolo concorrente.

**Regressão:** jogo com participantes da modalidade A não passa para B; local de outra edição/inativo é recusado; horários invertidos são recusados sem alteração parcial; edição normal da agenda permanece válida. Executar os cenários existentes de agendamento manual, lote e disputa concorrente.

## A07 — Encerramento por cronômetro ignora o resultado

**Localização:** `JogoController::isCronometroMutation` e `__invoke`; `src/Modules/Competicoes/Application/CronometroService.php:30`; `CronometroRules::concluir`; `MysqliCronometroRepository::save`.

`PUT /api/v1/jogos` com `status_jogo=Concluido` é encaminhado ao cronômetro. Para modalidade coletiva, o serviço grava o estado final sem validar placar, eventos, avanço ou pódio. Um jogo 0×0 pode ficar encerrado apesar de `/api/v1/resultados` proibir esse resultado. Além disso, a próxima submissão pelo fluxo completo encontra `$closed=true`, mudando o caminho de reconciliação.

**Correção L07:** centralizar a transição esportiva para encerrado no caso de uso de resultado. Cronômetro deve cuidar do relógio e não produzir sozinho a conclusão competitiva. Analisar snapshots antigos que enviam `Concluido`: manter replay de confirmação já aplicada e interpretação compatível, sem permitir que um snapshot encerre um jogo aberto. Não alterar o corpo/chave de mutações persistidas. Rever também `aplicarSnapshot`, pois ele atribui estados diretamente e pode reabrir um jogo encerrado quando chega um snapshot atrasado de `Iniciado`/`Pausado`.

**Regressão:** PUT direto e snapshot v2 não encerram 0×0/empate nem reabrem jogo encerrado; finalização correta por resultado mantém placar, eventos, avanço e crédito na mesma transação; reenvio não duplica; falha na confirmação desfaz tudo. Navegador: pausa, retomada, término e reconexão na mesma aba preparada.

## A08 — Sincronização coletiva aceita final inválida

**Localização:** `src/Modules/Sincronizacao/Infrastructure/MysqliChaveamentoSyncGateway.php:64`, `:175`, `validarPontuacaoSincronizada`; `ChaveamentoRules::vencedorDePartidas`.

Esse caminho grava `status_jogo` e partidas diretamente. A validação verifica equipes existentes na modalidade e contagem de pontos por equipe, mas não aplica a regra de placar vencedor, quantidade correta de participantes, correspondência integral com participantes já persistidos ou topologia autorizada. Uma final `MM:2:0:N` com duas equipes e resultado 0×0 satisfaz a contagem de pontos vinculados: zero eventos para zero gols. Depois, o código tenta reconciliar pódio/avanço; a regra auxiliar escolhe o menor ID em empate. Não remover esse desempate auxiliar globalmente, pois ele também atende outros contextos históricos.

**Correção L08:** fazer a sincronização usar os mesmos invariantes de resultado e materialização de jogos temporários. Validar a lista inteira antes da primeira escrita e revalidar o contexto protegido. Distinguir bye legítimo de partida normal: não proibir indiscriminadamente jogo com uma equipe. Validar identidade e topologia, recusando resolução ambígua, regressão indevida de status e adição de terceira equipe a jogo existente. Tratar explicitamente nomes e formatos legados: não exigir indiscriminadamente uma tag `MM` de toda operação persistida. Conferir os fixtures que hoje criam jogos com outros nomes pela sincronização; quando a preparação precisar mudar para uma API apropriada, preservar as asserções do comportamento testado. Reconciliar também disputa de terceiro lugar pela regra pública, sem depender exclusivamente da busca `nome_jogo LIKE 'MM:%'`. Essa extensão deve ter reprodução própria antes de qualquer alteração adicional.

**Regressão:** final 0×0/empatada, score fracionário/negativo, normal com participante faltante/extra, tag inválida, outro operador/edição, bye legítimo, correção de vencedor e terceiro lugar. Em erro no segundo jogo do lote, o primeiro e a idempotência também devem voltar ao estado anterior. Rodar filas legadas e torneio completo online/offline.

## A09 — Metadados textuais de ocorrência contornam autorização

**Localização:** `src/Modules/Disciplina/Presentation/Http/OcorrenciaController.php:132`; `OcorrenciaService::registrar`; `MysqliOcorrenciaQueries::referencesFromDescription`.

O PUT autoriza as referências da descrição antiga. Se a nova descrição começar com `[JOGO:` ou `[TURMA:`, ela não recebe o prefixo antigo: o texto enviado é persistido como veio. Assim, o operador altera as referências reais sem informar os campos estruturados que seriam comparados. Uma ocorrência de A pode passar a mencionar jogo/turma B. Os filtros e a elegibilidade disciplinar usam esses marcadores. O POST sem referência estruturada também precisa ser examinado para impedir que o texto livre invente metadados.

**Correção L09:** separar parsing de referências e texto livre. No PUT, preservar referências canônicas do registro e rejeitar tentativa de modificá-las pela descrição. No POST, construir o prefixo exclusivamente dos IDs validados, tratando marcadores no texto conforme regra explícita. Usar um contrato compartilhado de parsing para servidor, projeção offline e dados legados; não espalhar regex diferentes. Migração estrutural para colunas próprias é opção apenas se necessária e acompanhada de estratégia para descrições antigas, nunca uma exigência de reescrita completa desta correção.

**Regressão:** texto iniciado por outro prefixo, marcadores duplicados/no meio do texto, troca de turma, remoção do prefixo e registro sem jogo. Validar edição, cartões, listagem e reenvio. Exigir que um texto com marcadores não retire uma punição do jogo de origem nem a aplique em outro.

## A10 — Vermelho automático fica órfão após corrigir amarelo

**Localização:** `src/Modules/Disciplina/Infrastructure/MysqliOcorrenciaRepository.php:56`, `:154`; exclusão por status em `OcorrenciaController`.

O segundo amarelo insere um vermelho independente, com penalidade 1. Editar ou inativar um amarelo não reconcilia esse vermelho. O atleta continua expulso e a turma pode continuar penalizada, embora só reste um amarelo. Como a condição é `$total >= 2`, novos amarelos também podem produzir vermelhos automáticos extras. Reenvio da mesma chave é protegido; isso não resolve ações diferentes ou correções do fato de origem.

**Correção L10:** modelar a relação de derivação do vermelho automático e recalculá-la ao criar, editar ou inativar amarelos. Diferenciar vermelho manual de automático; nunca remover vermelho manual por essa rotina. Usar bloqueio/transação por atleta e jogo compatível com as operações de pontos/resultado. Se for necessária coluna/tabela de origem, adicionar migração após a última corrente; tratar registros antigos sem adivinhar causalidade a partir apenas de uma frase livre.

**Regressão:** dois amarelos geram exatamente um automático; remover/corrigir o segundo retira somente o efeito derivado; terceiro amarelo não duplica expulsão; reativação, retry e duas gravações concorrentes mantêm a invariância. Conferir tanto o ranking quanto a lista de atletas aptos a pontuar.

## A11 — Mudança de turma desloca histórico disciplinar

**Localização:** `src/Modules/Acesso/Infrastructure/MysqliUsuarioManagementRepository.php:58`; `src/Modules/Resultados/Infrastructure/MysqliRankingRepository.php`, subconsulta de penalidades.

`assignStudent` verifica apenas se aluno e turma pertencem à edição ativa e muda `turmas_id_turma`. Não verifica elenco ou ocorrências. O ranking agrupa penalidades pela turma atual do usuário, não pela turma do fato. A transferência deixa associações na equipe antiga e desloca o desconto histórico para a turma nova.

**Correção L11:** para a correção mínima, recusar mudança de turma de aluno com vínculos esportivos ou disciplinares incompatíveis, usando transação e estado real. Transferência de aluno sem esses vínculos deve continuar possível. Caso o produto venha a exigir transferências com histórico, a solução exige origem imutável para a ocorrência e migração/reconciliação explícita; não mover descontos nem apagar inscrições silenciosamente nesta rodada. Conferir também edição de gênero para não invalidar um elenco existente sem tratamento.

**Regressão:** aluno com equipe/partida/ocorrência não muda de turma e o ranking das duas turmas permanece igual; aluno sem histórico pode mudar dentro da edição; outra edição e outro perfil são recusados. Cobrir integração com A04/A05/A10.

## A12 — `esc` ausente impede renderizar atletas

**Localização:** `resources/js/pages/competicoes/equipe-alunos.js:12`, `cardAluno`, e retorno da factory; template `resources/views/pages/competicoes/equipe-alunos.php`.

`cardAluno` chama `esc`, mas o arquivo não o declara. O template não instala um helper global com esse nome. A sondagem Node/VM, em um contexto de página novo, reproduziu `ReferenceError: esc is not defined`. A exceção acontece quando há aluno para renderizar e é capturada por `carregar`, que troca a lista por uma mensagem de erro; testes que só verificam o container podem passar. Outra página pode ter exportado `esc` por `SGIPage`, mascarando a falha na navegação.

**Correção L12:** usar explicitamente `SGIHtml.escape` ou helper local seguro e garantir a ordem de carregamento. Não depender de globals deixados por outras telas. Testar primeira abertura com elenco não vazio. Revisar também os checkboxes desktop/mobile: ambos os conjuntos existem no DOM, e `salvar` lê todos os marcados. Se mantiver a tela como adição, deixar essa semântica explícita e sincronizar seleção; não implementar remoção implícita sem contrato. A remoção explícita pelo fluxo existente deve continuar funcionando.

**Regressão:** teste JavaScript descoberto pelo runner invoca renderização com aluno realista; Playwright abre diretamente `/equipes/alunos` em contexto novo, vê nome/matrícula, seleciona um aluno e confirma persistência. Repetir em desktop/mobile e com busca, evitando enviar seleção obsoleta da composição escondida. Asserir ausência da mensagem de erro, não apenas presença da raiz.

## A13 — Valores vazios desaparecem no ambiente do executor Windows

**Localização:** `tools/test-local.ps1:96`, `Start-TestServer`; `src/Shared/Config/EnvLoader.php`.

O comando documentado usa Windows PowerShell (`powershell.exe`). Nesse ambiente, `Set-Item Env:SGI_DB_PASSWORD ''` remove a variável em vez de transmitir um valor vazio ao processo PHP. `EnvLoader` então pode carregar a senha do `.env` de trabalho. A execução com banco descartável sem senha falhou com `Access denied ... (using password: YES)`, embora o runner tivesse escolhido senha vazia. O código de `EnvLoader` preserva corretamente variáveis realmente presentes; o defeito está na fronteira de configuração do executor. O runner também não força `SGI_BASE_PATH`/`SGI_APP_URL`, deixando o ambiente isolado exposto a configurações de implantação da instalação de trabalho.

**Correção L00:** tornar o ambiente de teste explícito, incluindo valores vazios, sem editar `.env` nem imprimir senhas. Uma opção é suportar no bootstrap um modo de configuração exclusivo de teste que não carregue `.env`, ativado pelo executor; outra é um mecanismo equivalente que represente vazio inequivocamente. Avaliar alcance nos executores manual/Docker antes de escolher. Não usar um valor falso como senha só para fazer os testes passarem. Configurar raiz/URL coerentes por execução e oferecer cenário separado de subdiretório.

**Regressão:** PowerShell 5.1 → processo PHP, com arquivo `.env` sintético conflitante: senha vazia continua vazia, senha não vazia é preservada sem exposição, host/banco não vêm do arquivo de trabalho, `SGI_BASE_PATH` não contamina raiz. Incluir falha/cleanup e restauração do ambiente do chamador. Não testar isso contra a configuração ou base real do usuário.

## A14 — Último administrador pode ser rebaixado

**Localização:** `UsuarioService::atualizarColaborador`; `src/Modules/Acesso/Infrastructure/MysqliUsuarioManagementRepository.php:121`; `UsuarioController`, ação `atualizar_colaborador`.

A proteção de administrador aplicada à exclusão não existe na mudança de papel. O update usa somente ID/edição, pode atingir a própria conta e incrementa `auth_version`. Rebaixar o único administrador deixa zero contas administrativas e invalida a sessão atual na requisição seguinte. Isso pode bloquear a gestão pelo produto. O provisionamento CLI não é um mecanismo de recuperação cotidiana para uma edição de papel feita pela UI.

**Correção L13:** proteger a existência de ao menos um administrador ativo em mudança de papel/status. Aplicar sob transação e bloqueio comum para que dois administradores não consigam remover simultaneamente o último privilégio. Preservar `auth_version` em alterações efetivas. Validar que a ação de colaborador atinge um perfil permitido e informar recurso inexistente/fora de escopo, em vez de sucesso para zero linhas. Não impedir toda edição de administrador: manter a operação legítima quando houver outro administrador ativo.

**Regressão:** único administrador, dois administradores, própria conta, conta inativa, ID inexistente, aluno passado à ação de colaborador, edição divergente, CSRF e concorrência entre duas mudanças de papel. A recusa não altera versão, senha ou sessão.

## Pontos adicionais a investigar, sem correção especulativa

- **Modal de ocorrência (falha observada no navegador):** a suíte encontrou o modal visível e depois perdeu o botão de salvar em `tests/browser/occurrence-offline-edit.spec.cjs:194`. Em L14, reproduzir com trace e acompanhar abertura, resposta do salvamento anterior, timer de fechamento e eventos `hide/hidden/shown` do Bootstrap. Inspecionar `editarOcorrencia` e `salvarOcorrencia` em `resources/js/pages/competicoes/placar.js`: já existe cancelamento de timer, mas uma resposta assíncrona anterior pode precisar de controle de geração e a reabertura durante transição precisa ser coordenada. A causa ainda não está confirmada. Se reproduzida, criar regressão determinística controlando resposta/transição, sem acrescentar espera arbitrária ou apenas aumentar timeout. Reexecutar também `offline-queue-regression.spec.cjs`, cujo Chromium não chegou a iniciar naquela execução; separar esse problema de infraestrutura de uma falha funcional da fila.
- **Concorrência de autorização:** alguns controladores autorizam a edição antes do bloco que altera dados. Após bloquear transferências estruturais inválidas em A05/A06, testar troca simultânea da edição ativa com operação de mesário. Só propor protocolo adicional se reproduzir autorização fora do contrato.
- **Sincronização parcial de partidas:** testar `PUT /partidas` alterando somente `status_partida` em jogo encerrado e comparar placar/eventos. A permissão atual existe; não remover o endpoint inteiro sem verificar filas antigas.
- **Credenciais importadas:** o importador PDF ainda cria alunos com a senha padrão `123`, enquanto cadastro manual gera senha aleatória. O produto sinaliza troca, mas o bloqueio central exige termos, não troca de senha. Registrar decisão de produto sobre entrega/primeiro acesso antes de redesenhar esse fluxo. Não confundir senha de fixture com senha de importação normal.
- **Snapshots offline:** testar Request com body no objeto `Request`, erro de quota em projeções e troca de sessão durante reenvio. A fila já possui proteção de idempotência, confirmação, revisão e recuperação; não removê-la ou mudar o schema por suspeita.
- **Importação que falha:** a nova tentativa substitui o PDF antes do parsing/commit no banco. Determinar se o arquivo anterior precisa ser preservado como evidência operacional; se esse for o contrato, reproduzir com falha de parser e promover a tarefa separada.

Esses pontos são investigação, não bugs adicionais comprovados por esta entrega. O Luna deve registrar reprodução ou refutação, sem implementar mudanças extensas para apenas “endurecer” código.

## Evidências e validação

O registro final dos comandos, contagens e limitações está no [STATUS](plano-correcao-luna-2026-09-12/STATUS.md). As sondagens em `test-results/` são instrumentos temporários da auditoria, ignorados pelo Git. Elas não substituem os testes permanentes exigidos para a implementação. Os resultados relevantes devem continuar registrados no STATUS mesmo se os artefatos locais forem removidos futuramente.

## Mapa da revisão

| Área | Fronteiras verificadas | Resultado principal |
|---|---|---|
| HTTP e configuração | Kernel, sessão, CSRF, rotas, arquivos, ambiente e respostas | A01/A03/A13; revalidação e CSRF existentes devem ser preservados |
| Acesso | Login, gestão de usuários, senha, termos, fotos, edição | A11/A14; investigação separada sobre importação e primeiro acesso |
| Eventos | Edições, categorias, locais, publicação e arquivos | A02/A06; não reaplicar correção de ranking já concluída |
| Participantes | Turmas, importação, inscrição e contexto do portal | A01/A04/A11; distinguir cadastro administrativo do aceite do aluno |
| Competições | Elenco, modalidades, partidas, pontos, jogos, agenda, cronômetro e chaveamento | A04–A08/A12 |
| Disciplina | Referências, criação/edição de cartões e elegibilidade | A09/A10 |
| Resultados | Ranking, penalidades, arrecadação, pódio e classificação | Efeitos de A05/A07/A08/A10/A11; reconciliação existente não deve ser substituída |
| Sincronização | Identidade, transação, fila, projeções, temporários e confirmação | A08/A09; manter aliases e formato persistido |
| Frontend | Helpers de HTML, ciclo de página, templates e páginas administrativas/operacionais | A02/A12; sondagens DOM e suíte de navegador |
| Banco, testes e operação | Migrações 001–010, FKs, executores, suite integrada e matriz CI | A13; SQL novo exige matriz e migração imutável |
