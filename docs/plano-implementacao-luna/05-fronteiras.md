# Etapa 5 — Concluir as fronteiras dos casos de uso

Depende de T21. Executar T22–T25. Os defeitos devem estar corrigidos antes desta reorganização. Usar as regressões anteriores para manter o comportamento; não aproveitar a etapa para trocar regras ou envelopes.

## Desenho final esperado

```text
Controller
  traduz Request/sessão/arquivos para dados do caso de uso
  chama serviço de Application
  traduz resultado/exceções em Response

Application
  coordena autorização, validação, transação e sequência de operações
  depende de contratos e regras puras

Domain
  contém regras/valores/contratos sem HTTP, sessão ou banco concreto

Infrastructure
  implementa consultas, escrita, travas, armazenamento e adaptadores

config/routes.php / bootstrap
  conecta implementações concretas aos contratos
```

Não basta mover o arquivo e manter um serviço vazio chamando um gateway que ainda decide tudo. As decisões de “pode alterar”, “é final”, “quem recebe pontos” e “o que invalidar” precisam estar testáveis fora da infraestrutura.

## T22 — Coordenar resultado e transação em Application

**Ler/editar:** `ResultadoController.php`, `MysqliPartidaGateway.php`, `MysqliIndividualRepository.php`, `MysqliChaveamentoManagement.php`, `MysqliChaveamentoSyncGateway.php`, `MutationAction.php`, `Transaction.php`, `config/routes.php`.

### Passos

1. Reutilizar `ResultadoService` e `PontuacaoService` se já foram criados nas correções. Não criar uma segunda camada com nomes quase iguais.
2. Criar `Shared/Application/TransactionRunner.php` com `run(callable): mixed`. Implementar em `Shared/Database/MysqliTransactionRunner.php` usando `Transaction`, inclusive savepoints.
3. Em sucesso, retornar o valor da callback; em qualquer `Throwable`, rollback e relançar. Não devolver erro HTTP pelo adaptador.
4. Injetar uma única conexão na composição dos repositórios e do runner usados no mesmo caso de uso. Não criar `new mysqli` dentro de cada serviço.
5. Definir contratos pequenos em `Competicoes/Domain`: consultar/resolver referência do jogo; carregar/persistir partidas; carregar estado do chaveamento; persistir os efeitos calculados. Reutilizar contratos existentes quando adequados.
6. Extrair para `ResultadoService` a sequência de T15: autorização, leitura antiga, validação, persistência, determinação de avanço/invalidação e chamada da pontuação.
7. Extrair as decisões restantes do individual para caso de uso de Application. Manter SQL nos repositórios, sem transformar o serviço em mero alias de `salvarRanking`.
8. A integração com Resultados usa contrato público do módulo, por exemplo uma interface de atualização do pódio implementada por `PontuacaoService`. Não chamar `MysqliPodioRepository` diretamente do controller de Competicoes.
9. O módulo Sincronizacao continua responsável pela identidade/repetição/transporte; passa os dados de competição ao mesmo caso de uso. Não manter cópia de premiação/reconstrução dentro de `sync`.
10. Manter `MutationAction` como transação externa que grava a resposta. O runner interno usa savepoint e não confirma a transação externa prematuramente.
11. Remover os métodos de orquestração antigos somente depois de `rg` mostrar que seus consumidores foram encaminhados. Métodos puramente SQL podem continuar na classe existente, se bem definidos.
12. Atualizar a composição de todas as rotas/aliases relevantes e os testes de construção dos controllers.

### Testes de serviço sem banco

- Resultado válido invoca persistência/avanço/crédito em ordem coerente.
- Recurso não autorizado não chama escrita.
- Placar inválido não altera créditos.
- Mudança de vencedor usa crédito antigo e novo.
- Falha no avanço ou crédito interrompe o caso de uso.
- Adapter transacional real tem teste de rollback aninhado; um fake que só “conta chamadas” não prova atomicidade.

**Aceitação:** `ResultadoController` não depende de `MysqliPartidaGateway`; casos críticos têm testes sem banco; testes HTTP e de falha idempotente continuam aprovados.

## T23 — Retirar MySQLi e consultas de outros módulos do controller de usuários

**Ler/editar:** `UsuarioController.php`, `MysqliUsuarioGateway.php`, `UsuarioAdministrativoService.php`, `MysqliEdicaoConsulta.php`, contratos de Acesso e `config/routes.php`.

### Inventário obrigatório antes de mover

Registrar método, `acao`, permissão, entrada e envelope para:

- GET geral, `listar_competidores`, `listar_colaboradores`;
- PUT de status da edição;
- `validar_inscricao`;
- `criar_aluno`, `editar_aluno`, atribuição por query `id`;
- `cadastrar_usuario`, `atualizar_colaborador`, `atualizar_dados_colaborador`;
- `excluir_aluno`, `resetar_senha_aluno`, `excluir_colaborador`.

### Passos

1. Criar contratos de consulta/gestão onde ainda falta interface. Reutilizar `UsuarioAdministrativoService` para as operações que já cobre.
2. Injetar serviços/queries por contrato no controller. Remover o parâmetro `mysqli` de seu construtor.
3. Substituir `MysqliEdicaoConsulta` por contrato público de Eventos/Acesso para consulta da edição ativa; ativação usa o caso de uso de T21.
4. Regras de validação de inscrição do aluno ficam no caso de uso. Criar/regenerar sessão e construir resposta continuam na apresentação.
5. Traduzir upload em dados/objeto de arquivo na apresentação; persistência de foto usa o contrato de storage existente ou adaptador equivalente. Não mover `$_FILES` para Application.
6. Preservar restrições de administrador/colaborador e proteção de contas; não aproveitar a refatoração para alterar senha padrão/testes legados ou permissões.
7. Se o gateway misturar ações independentes, separar por responsabilidade em pequenos repositórios; fazer uma ação/grupo por vez, mantendo o inventário visível.
8. Remover código antigo só quando todas as linhas do inventário tiverem consumidor novo e teste correspondente.

**Aceitação:** construtor sem MySQLi; nenhum acesso direto à infraestrutura de Eventos na apresentação de Acesso; todas as ações do inventário preservadas, incluindo autenticação por validação de inscrição e upload.

## T24 — Extrair regras de inscrição/equipes e formalizar dependências

**Ler/editar:** `InscricaoService.php`, `MysqliInscricaoRepository.php`, `EquipeService.php`, `MysqliEquipeRepository.php`, `MysqliEquipePadraoRepository.php`, `EdicaoService.php` e criação de padrões de edição.

### Passos

1. Levar cálculo de união de modalidades e limite de três a uma regra pura de Participantes, recebendo IDs já normalizados.
2. Levar capacidade/limite de equipes a regra explícita de Competicoes, sem dependência da geração do nome.
3. A infraestrutura fornece estado bloqueado e operações de persistência; o serviço decide se autoriza inserir. Não mover SQL ou `FOR UPDATE` para o serviço.
4. A consulta de estado e a decisão continuam na mesma transação. Extração de uma classe não pode reintroduzir a leitura fora da trava corrigida em T19.
5. Para buscar/criar equipe padrão a partir de Participantes ou Eventos, usar contrato de Competicoes, evitando chamar infraestrutura concreta de outro módulo.
6. Não tentar refatorar todos os helpers estáticos de uma vez. Começar pelos consumidores do fluxo de inscrição e criação de edição que cruzam módulos; manter testes de quantidade de equipes padrão.
7. Registrar as dependências permitidas em `docs/architecture.md`: Competicoes pode usar a API de pontuação de Resultados; Participantes usa contratos de equipes; Eventos coordena padrões por contratos. Presentation/Infrastructure não viram atalhos públicos entre módulos.
8. Queries somente de leitura podem usar contratos específicos sem criar entidades artificiais. Não impor DDD completo a telas simples.
9. Ajustar os fakes unitários para dados reais representativos; não deixar métodos vazios retornando sucesso sempre.

**Aceitação:** regra de limite testável sem banco; concorrência continua protegida; criação padrão preservada; dependências cruzadas dos casos críticos passam por contratos explícitos.

## T25 — Fortalecer testes arquiteturais sem mascarar exceções

**Ler/editar:** `tests/Unit/Architecture/LayerDependenciesTest.php`, `ModuleLayoutTest.php`, `PresentationBoundaryTest.php`, `phpstan.neon` e `docs/architecture.md`.

### Passos

1. Manter todos os checks existentes de camadas, SQL em apresentação e templates privados.
2. Adicionar check que os controllers críticos migrados não recebam/importem `mysqli` nem gateways concretos dos fluxos extraídos.
3. Verificar que Domain/Application não referenciem HTTP, sessão, Infrastructure, conexão, `ConnectionFactory` ou executem consultas.
4. Verificar dependências entre módulos usando os contratos permitidos. Preferir análise de imports/tokens a busca textual que confunde comentários com código.
5. A composição em `config/routes.php` e bootstrap pode importar implementações concretas; não proibi-la.
6. Se ainda existir dependência concreta fora do escopo crítico, listá-la individualmente com motivo e localização. Não excluir diretórios inteiros, reduzir contagem de arquivos ou criar wildcard de exceção para fazer o teste passar.
7. Adicionar teste de composição/contrato que alcance cada rota crítica. Ter uma classe `ResultadoService` sem a rota usá-la não cumpre o plano.
8. Não aumentar nível do PHPStan como substituto das correções. Manter o nível atual e resolver problemas introduzidos; elevação geral seria trabalho separado.

### Fechamento

Executar a suíte completa exigida pelo projeto antes/depois das refatorações desta etapa. A aprovação final requer comportamento preservado e dependências verificadas, não apenas novos diretórios/classes.
