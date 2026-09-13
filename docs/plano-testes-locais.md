# Plano para simplificar os testes locais

Data: 10/09/2026. Status: primeira etapa implementada; benchmark e validação completa ainda pendentes.

## Recomendação

Adotar um comando único para execução nativa de PHP, Node e Playwright, com banco exclusivo de testes. Usar uma instância MySQL/MariaDB local já disponível quando estiver configurada; oferecer somente o banco em Docker quando não houver instância local. Manter o Docker completo como alternativa sem instalação de ferramentas e como referência do CI.

Uma base temporária tende a reduzir o custo de preparação quando as ferramentas já estão instaladas. Ela não substitui PHP, extensões, clientes de backup e navegador. Instalar e administrar um servidor SQL nativo apenas para evitar Docker pode tornar o primeiro uso mais complicado. Para esta máquina, decidir o backend após o diagnóstico do PHP/XAMPP; não instalar outra distribuição de banco antes disso.

Não substituir MySQL/MariaDB por SQLite: os testes exercitam migrações, triggers, concorrência e dump/restauração específicos desses motores.

## Entregue nesta etapa

- `tools/test-local.ps1` executa `quality`, `integration`, `browser`, `visual` e `all` em um comando, com backend SQL local ou um container SQL temporário.
- O executor escolhe um PHP único, valida extensões/dependências antes de criar recursos, usa nomes de base e artefatos por execução, aguarda o health endpoint, mantém um lock local e limpa servidor/base/container no `finally`.
- O Playwright aceita diretórios de artefatos por execução sem alterar o padrão usado pelo Docker/CI.
- O ensaio de recuperação usa o identificador da execução para evitar colisão das bases auxiliares.
- `tools/benchmark-tests.ps1` repete um perfil e grava duração/código de saída em JSON para comparar backend e cache.
- Os wrappers Docker executam qualidade sem subir SQL/HTTP e só constroem o navegador quando a suíte solicitada o utiliza.
- `docs/testing.md` documenta o caminho local e as variáveis de teste explícitas.

## Evidências do repositório e da máquina

- `tests/Support/TestDatabase.php` já descarta e recria o schema, aplica migrações e fixtures. Confere o nome de teste e a identidade informada pelo health endpoint antes do reset. Portanto, o banco descartável já existe na estratégia atual.
- `compose.test.yml` já usa `tmpfs` para o banco. Trocar somente o armazenamento não é a principal oportunidade; o ganho esperado está na preparação e orquestração.
- `tools/test-docker.ps1` e `.sh` sempre executam `build app browser`, inclusive com navegador desabilitado. Também sobem banco e aplicação antes dos checks que não precisam deles.
- `Dockerfile.test` e `Dockerfile.browser` copiam o projeto e executam build de assets; `quality` executa outro build. As instalações de dependências já estão antes de `COPY . .`, permitindo reaproveitamento de cache. Não se deve tratar toda execução Docker como reinstalação completa.
- `docs/testing.md` exige dois terminais e variáveis duplicadas no fluxo nativo. `start-test-server.ps1` e o setup do Playwright assumem PHP do XAMPP, enquanto Composer pode usar o PHP do PATH.
- O PHP encontrado no PATH é 8.4.25 e não lista `mysqli`. PHP, Composer, Node, npm e Docker estão no PATH; clientes SQL não foram encontrados nele. Existe `C:\xampp\mysql\bin\mysqld.exe`, mas disponibilidade e versão do servidor não foram verificadas.
- O acesso ao daemon Docker foi negado nesta sessão. Não foi feito benchmark nem executada a suíte. As conclusões de desempenho são hipóteses fundamentadas no fluxo, não tempos medidos.
- `tests/run_all.php` encadeia cenários que compartilham dados. Playwright usa um worker. `SGI_E2E_RESET=1` executa toda a suíte HTTP, não somente um seed.
- O ensaio de recuperação precisa de clientes SQL/dump e cria duas bases com nomes fixos. Tornar apenas o schema principal único não basta para permitir execuções simultâneas.
- O CI executa qualidade apenas em PHP 8.4; integração, navegador e contrato visual usam MariaDB 10.11 com PHP 8.4. MySQL 8.4 e PHP 8.2 ficam disponíveis para execuções locais.

## Comparação

| Opção | Preparação recorrente | Primeiro uso | Uso recomendado |
| --- | --- | --- | --- |
| Tudo em Docker | Inclui build, subida e remoção dos serviços; cache reduz parte do custo | Centraliza as dependências | CI, compatibilidade e máquinas sem ferramentas |
| Ferramentas nativas + banco em Docker | Evita builds da aplicação/navegador; mantém inicialização do SQL | Exige runtimes e clientes locais | Alternativa local quando não há SQL instalado |
| Ferramentas nativas + schema descartável em SQL local | Evita build e subida de containers | Simples se o ambiente já estiver pronto | Preferência para máquina de desenvolvimento configurada |

O schema separado compartilha versão, configuração e recursos do servidor local. Docker isola também essas dependências. Ganho de tempo e equivalência dos resultados devem ser avaliados separadamente.

## Etapas de implementação

### 1. Diagnóstico e medição

Criar um modo `doctor` que resolve um único executável PHP e o usa no servidor, Composer, integração e setup do navegador. Validar versões, extensões exigidas pelo Composer, `mysqli`, clientes SQL/dump, dependências instaladas, Chromium, conexão SQL e portas disponíveis. Não imprimir senhas nem carregar implicitamente credenciais da base de trabalho.

Medir preparação, build de assets, qualidade, reset/migrações, integração, navegador e limpeza. Comparar Docker completo, banco em Docker e SQL local com mesmo código, fixtures, versões compatíveis e mesmos testes. Separar primeira execução de três repetições com cache; registrar mediana e etapas. Comparar também uma repetição após alteração de código. Não apagar caches globais para simular execução fria.

### 2. Um comando local e configuração única

Foi criado `tools/test-local.ps1`, priorizando o Windows usado neste projeto. A interface é:

```powershell
powershell -File tools/test-local.ps1 -Suite all -DatabaseBackend local
powershell -File tools/test-local.ps1 -Suite all -DatabaseBackend docker
powershell -File tools/test-local.ps1 -Suite quality
```

O executor deve:

1. Validar pré-requisitos antes de criar recursos. Instalações de dependências ficam em uma etapa explícita de setup; não repetir `composer install`, `npm ci` ou instalação do Chromium a cada teste.
2. Configurar o ambiente de todos os processos filhos a partir de uma única configuração de teste ignorada pelo Git, com exemplo versionado e opções de linha de comando. Restaurar variáveis ao terminar.
3. Obter lock por instância SQL para impedir suítes concorrentes enquanto houver bases auxiliares fixas. Gerar nome exclusivo `sgi_test_<execucao>` para a base principal.
4. Criar diretório próprio de sessões, uploads, imports e logs em `test-results/<execucao>/`, ajustando os caminhos compartilhados necessários. Escolher porta livre e iniciar o servidor PHP oculto, guardando seu PID.
5. Aguardar health com timeout e validar modo de teste, identidade do banco e servidor esperado. Preservar a verificação existente antes de qualquer reset.
6. Executar os testes em sequência, propagar códigos de saída e mostrar duração por etapa.
7. Em `finally`, encerrar somente processos criados pelo executor e remover somente schemas/recursos pertencentes à execução. Preservar relatórios; permitir `-Keep` para diagnóstico. Registrar falhas de limpeza sem ocultar a falha original.

Para o backend Docker, criar configuração específica de banco, publicando a porta somente em `127.0.0.1`, com nome de projeto separado do Compose completo, healthcheck e versão explícita. O Compose atual não publica a porta SQL para processos do host. Reutilizar o serviço SQL durante a sessão pode ser opção explícita posterior; sempre recriar o schema e nunca reutilizar fixtures alteradas.

Para SQL local, usar conta dedicada às bases de teste, com permissões necessárias para migrações, triggers e recuperação, sem privilégios sobre bases de trabalho. Conferir também host/porta efetivos usados pelo servidor e runner: comparar apenas o nome do schema não prova que apontam para a mesma instância.

### 3. Seleção de suítes sem perda de cobertura

| Perfil proposto | Execução |
| --- | --- |
| `quality` | `composer validate`, `composer verify`, build de assets, `npm run check`, `npm test`; sem SQL/servidor |
| `integration` | Build necessário e runner HTTP completo, incluindo migrações e recuperação |
| `browser` | Preparação HTTP uma vez e Playwright funcional/online/offline |
| `all` | Qualidade → integração → navegador, sem repetir preparação HTTP |
| `visual` | Contrato visual explícito, preservando referências por plataforma |

Desabilitar `SGI_E2E_RESET` quando o executor já preparou a base. Na primeira implementação, manter a suíte HTTP inteira para preparar o navegador; extrair fixtures independentes é uma melhoria posterior, condicionada a reproduzir os mesmos dados. Não oferecer seleção arbitrária de classes HTTP dependentes nem aumentar workers antes de isolar seus dados.

Perfis reduzidos servem ao feedback durante desenvolvimento. Não substituem a suíte completa antes/depois de refatorações exigida pelo `AGENTS.md`, nem a matriz do CI.

### 4. Corrigir desperdícios no Docker existente

- Construir `browser` somente se testes funcionais ou visuais forem solicitados.
- Permitir qualidade sem subir SQL/HTTP, como o CI já faz.
- Revisar builds redundantes de assets: o build validado precisa ser exatamente o servido à integração/navegador; não remover etapas sem garantir isso.
- Preservar as camadas de dependências e evitar `--no-cache` no fluxo comum. Como o código é copiado para a imagem, não reutilizar uma imagem antiga após editar fontes sem reconstruí-la.
- Manter comportamento equivalente nos wrappers PowerShell e shell.

### 5. Documentação e validação de aceitação

Atualizar `docs/testing.md` com um caminho principal local, setup único, diagnóstico, comandos por perfil e Docker completo como alternativa. Manter os comandos manuais para investigação, sem denominá-los obrigatoriamente legados.

Aceitar a mudança quando:

- Uma máquina preparada executar `all` em um comando e um terminal, sem instalações repetidas.
- Qualidade não iniciar banco/HTTP; execução completa preparar a base uma única vez.
- Falhas de dependência, porta ocupada, health incorreto, teste reprovado e interrupção liberarem os recursos próprios ou relatarem claramente resíduos.
- Nomes não autorizados forem recusados e o lock impedir colisões, inclusive nas bases auxiliares de recuperação.
- Duas execuções sequenciais completas passarem com fixtures limpas, mesmos cenários e mesmos critérios de aprovação do executor atual.
- A suíte completa exigida pelo projeto passar antes/depois da implementação, e o CI preservar a matriz atual.
- O benchmark indicar onde houve ganho; se o modo nativo não melhorar o tempo total, justificar sua adoção pela redução de passos, sem alegar aceleração não observada.

## Ordem sugerida

O diagnóstico e o executor local foram implementados primeiro. Os wrappers Docker agora executam qualidade antes de subir SQL/HTTP e constroem o navegador somente quando solicitado. O benchmark, a extração de fixtures independentes e a eventual execução SQL concorrente continuam como etapas posteriores; não é necessário reescrever os testes nem remover Docker.

## Referências externas

A documentação oficial explica a [invalidação de cache por COPY](https://docs.docker.com/build/cache/invalidation/) e a [organização das camadas para reaproveitamento](https://docs.docker.com/build/cache/optimize/). Isso sustenta preservar o cache existente e medir reconstruções após mudanças, em vez de assumir reinstalação completa em cada execução.
