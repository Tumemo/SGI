# Implementação das correções — Luna

Base auditada: `d3c4429a`. Leia [AGENTS.md](../../AGENTS.md), este arquivo, [STATUS](STATUS.md) e o achado da tarefa atual na [auditoria](../auditoria-codigo-2026-09-12.md). O plano de 11/09 está concluído e é histórico, não uma lista pendente.

## Ordem de execução

| Tarefa | Achado | Dependência | Entrega |
|---|---|---|---|
| L00 | A13 + baseline | nenhuma | Ambiente isolado reproduzível e situação inicial |
| L01 | A01 | L00 | Importações privadas |
| L02 | A02 | L00 | Texto e atributos seguros |
| L03 | A03 | L00 | GET de equipes sem escrita |
| L04 | A04 | L03 | Elenco administrativo elegível e limitado |
| L05 | A05 | L04 | Equipes sem transferência de histórico |
| L06 | A06 | L05 | Estado final de jogo e local coerente |
| L07 | A07 | L06 | Término competitivo pelo caso de uso correto |
| L08 | A08 | L07 | Mesmos invariantes na sincronização coletiva |
| L09 | A09 | L00 | Referências de ocorrência imutáveis pelo texto |
| L10 | A10 | L09 | Cartões derivados reconciliados |
| L11 | A11 | L04, L05, L10 | Transferência de aluno preserva história |
| L12 | A12 | L02, L04 | Adicionar atletas funciona em contexto novo |
| L13 | A14 | L00 | Último administrador protegido |
| L14 | investigação e fechamento | L01–L13 | Regressões, matriz disponível, documentação e diff final |

Trabalhar sequencialmente, em diffs pequenos. Não é necessário delegar agentes nem abrir outra tarefa. Cada Lxx contém na auditoria a causa, os arquivos, a reprodução, o desenho da correção e a cobertura esperada. Se houver alteração posterior à auditoria, reconfirmar o achado antes de editar.

## Ciclo obrigatório por tarefa

1. Registrar Git e pré-requisitos. Preservar alterações do usuário. Nenhum reset/seed em dados de trabalho.
2. Ler implementação, controlador, composição, migrações e testes dos contratos afetados. Rodar os testes existentes relevantes antes de editar; refatoração exige `all` antes/depois.
3. Acrescentar uma regressão permanente que falhe pelo defeito descrito. As sondagens da auditoria não são testes de entrega e podem não estar disponíveis em outro checkout.
4. Fazer a menor correção que satisfaça o contrato. Manter limites de camada e dependências explícitas em `config/routes.php`.
5. Rodar primeiro a regressão, depois `all`. Acrescentar `-IncludeVisual` se alterar aparência. Alterações SQL/migração exigem instalação vazia, atualização, repetição e os dois motores pertinentes.
6. Revisar efeitos nos fluxos vizinhos indicados. Não relaxar asserções, adicionar skips ou atualizar snapshots para esconder falha.
7. Atualizar STATUS com comando, resultado, arquivos, limitações e próxima tarefa. Seguir para a próxima tarefa autorizada.

Não marcar tarefa completa quando só a alteração de código foi feita. Se faltar um alvo de ambiente, registrar precisamente a pendência; avançar apenas no trabalho independente. Uma execução Windows/MariaDB 10.4 não comprova MySQL 8.4, MariaDB 10.11, PHP 8.4 ou visual Linux.

## Regras de desenho que se aplicam a todas as correções

- SQL/locks em Infrastructure; decisão de negócio em Application/Domain; autorização HTTP e envelopes em Presentation. Não ampliar exceções arquiteturais.
- Coordenar alterações e confirmação idempotente pela mesma conexão/transação com savepoints. Não introduzir `begin_transaction` aninhado avulso.
- Identificar recursos pelo banco. O ID enviado pelo cliente nunca comprova edição, turma ou autoria.
- Preservar placar vinculado, autoria/anulação, chave da jogada, progressão e crédito por origem. Não resolver inconsistência habilitando modo legado para jogos novos.
- Preservar IndexedDB, filas, versões, corpos e IDs de mutação já persistidos. Em correção de protocolo, interpretar os formatos antigos no servidor/projeção; operações recusadas continuam recuperáveis.
- Editar fontes em `resources/` e gerar assets. Reutilizar `SGIHtml`, `SGIPage`, `Assets` e `Url`.
- Migrações aplicadas não são editáveis. Se necessária uma nova, descobrir a próxima numeração no checkout; na auditoria, a última era 010.
- Não fazer upgrade geral de dependências, trocar framework, publicar, fazer push/merge ou redefinir contas existentes.
- Mensagem de erro esperada não pode vazar SQL/caminhos. Rejeição precisa preservar todas as pós-condições anteriores, não somente devolver 4xx.

## Descoberta dos testes

- PHPUnit: arquivos em `tests/Unit/`, conforme `phpunit.xml`.
- JavaScript: `tests/javascript/*.test.cjs`, executados por `npm test`.
- Integração: ampliar uma classe já chamada por `tests/run_all.php` ou registrar explicitamente a nova classe no runner. Arquivo novo isolado não basta.
- Navegador: `tests/browser/*.spec.cjs`, segundo a configuração Playwright corrente. Utilizar fixtures/IDs preparados pelo teste e assertivas de dados visíveis/persistidos.
- Concorrência: conexões/processos e barreiras do suporte existente. Não usar sleep arbitrário ou duas requisições da mesma sessão PHP como prova de disputa real.

## Comandos

Conferidos com os executores do commit auditado. Configurar credenciais sintéticas no processo, sem copiar as do `.env`; após L00, registrar o comando exato que garante inclusive valores vazios.

```powershell
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite quality
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local
# Quando houver mudança de aparência:
powershell -ExecutionPolicy Bypass -File tools/test-local.ps1 -Suite all -DatabaseBackend local -IncludeVisual
# Com Docker disponível:
powershell -File tools/test-docker.ps1 -Database mariadb
powershell -File tools/test-docker.ps1 -Database mysql
git diff --check
```

Os perfis também aceitam `-PhpPath`, `-DatabaseHost`, `-DatabasePort`, `-DatabaseUser` e `-DatabasePassword`. Não executar integração/Playwright soltos: seguir [docs/testing.md](../testing.md) para preparar base, servidor, sessões e uploads isolados. Não executar duas suítes que alterem banco ao mesmo tempo no checkout.

## Prompt pronto para o Luna

> Implemente localmente o plano `docs/plano-correcao-luna-2026-09-12/README.md` em `C:\Projetos\SGI`. Leia `AGENTS.md`, o README do plano e `STATUS.md`; comece pela primeira tarefa pendente. Para cada Lxx, leia o achado correspondente em `docs/auditoria-codigo-2026-09-12.md`, reconfirme no checkout atual, execute o baseline relevante e acrescente teste permanente que reproduza a falha. Faça a correção mínima, rode a regressão e as suítes exigidas e registre as evidências no STATUS. Continue sequencialmente até L14, sem pedir confirmações rotineiras. Preserve dados de trabalho, migrações aplicadas, arquitetura, CSRF, auth_version, contratos, filas e identificadores offline. Não reimplemente os planos antigos concluídos. Não enfraqueça testes nem declare alvo não executado como aprovado. Os pontos de investigação exigem reprodução ou refutação antes de criar nova correção. Não publique, não faça push/merge nem altere contas ou banco de trabalho. Ao interromper, deixe o ponto exato de retomada. O objetivo é implementar e validar, não apenas reescrever o relatório.

## Critério de conclusão

A01–A14 corrigidos ou demonstrados como já resolvidos no checkout corrente, cada um ligado a regressão descoberta pelo runner. L14 deve registrar a conclusão ou refutação dos pontos adicionais, a execução das suítes exigidas, os alvos ainda indisponíveis e o diff final. Atualizar README/guias somente quando a correção mudar contrato/configuração/operação.
