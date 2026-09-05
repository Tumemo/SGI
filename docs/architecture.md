# Arquitetura de execução

O SGI continua sendo um monólito PHP, mas o código novo segue uma separação por
camadas:

```text
public/                      único DocumentRoot e front controller HTTP
api/                         adaptadores HTTP compatíveis com as telas atuais
src/<Modulo>/Domain/          contratos e regras independentes de MySQL
src/<Modulo>/Application/     casos de uso e validação de entrada
src/<Modulo>/Infrastructure/  implementações MySQLi
config/                      bootstrap e composição da aplicação
views/src/pages/             páginas PHP e componentes de apresentação
views/src/componentes/       motores JavaScript, incluindo o modo offline
storage/                     arquivos gerados em execução (não versionados)
tests/Unit/                   testes rápidos dos casos de uso
tests/Integration/            cenários legados executados por tests/run_all.php
```

## Regras para novas mudanças

1. Endpoints devem apenas interpretar a requisição, chamar um serviço e montar a
   resposta. SQL e transações ficam em `Infrastructure`.
2. Serviços recebem interfaces de repositório; isso permite testar regras sem
   abrir conexão com o banco.
3. Toda alteração de contrato HTTP precisa de um teste de regressão em
   `tests/Integration` ou em `tests/run_all.php`.
4. Entradas devem ser normalizadas e validadas antes do SQL. Mensagens de banco
   detalhadas devem ficar no log, não na resposta pública.
5. Arquivos enviados devem usar `StoragePaths` e variáveis `SGI_*_DIR`; nunca
   gravar diretamente em `docs/` ou montar nomes a partir de entrada do usuário.
6. O motor offline/chaveamento permanece compatível com IDs temporários
   negativos; sua migração para serviços deve preservar essa semântica.
7. O job `integration` do CI recria `sgi_test`, inicia o servidor com
   `public/index.php` e executa os fluxos HTTP, offline e de navegador antes de
   aceitar uma alteração.

## Fronteira HTTP

O servidor web deve apontar para `public/`. O front controller permite apenas
endpoints explicitamente listados de `api/`, páginas de `views/` e assets
necessários. Arquivos de configuração, dependências, testes, documentação e
armazenamento de execução retornam 404. Uploads continuam acessíveis pelas URLs
existentes, mas são resolvidos pelos diretórios `SGI_*_DIR`, fora do código.

## Módulos já migrados

- autenticação, sessão, troca de senha e aceite de termos;
- categorias;
- locais;
- tipos de modalidade;
- modalidades;
- pontuação;
- arrecadação e reversão transacional;
- turmas, equipes, ranking e classificação/pódio;
- agendamento inicial de jogos;
- atualização de partidas (PUT) com whitelist de campos;
- filtros de consulta compartilhados com retornos tipados;
- artilharia e ocorrências (incluindo ocorrências por turma);
- operações administrativas protegidas de usuários (remoção e reset de senha);
- caminhos de armazenamento e importação de PDF.

Os fluxos de atualização de jogos, lançamento de resultados e chaveamento
mantêm partes legadas deliberadamente encapsuladas nos adaptadores por causa da
operação offline. Essa fronteira preserva IDs temporários negativos e a
sincronização bidirecional sem expor SQL às telas; qualquer evolução desses
fluxos deve continuar usando os contratos de domínio e a suíte HTTP completa.
