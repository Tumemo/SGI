# Auditoria da arquitetura atual — 08/09/2026

## Veredito

A estrutura modular está presente, mas a migração não está concluída nem pronta para ser considerada validada. A declaração anterior de remoção integral de compatibilidade foi excessiva.

## Achados prioritários

1. **Crítico — login vazio:** `resources/views/pages/acesso/login.php` está vazio; o diff remove suas 70 linhas. O relatório Playwright registra ausência de `#form_desktop`. Restaurar o template com URLs atuais e testar login real nos quatro perfis.
2. **Alto — navegação offline inconsistente:** `resources/js/offline/mesario-offline.js` declara duas entradas `jogos` em `ARQ_TELA`; a última sobrescreve a lista. `mapearTela` usa somente o último segmento, portanto não encontra `edicoes/agenda` nem reconhece `jogos/placar`. `construirUrl` encaminha o placar para `jogos?id_jogo=...`, que é a lista. Corrigir o reconhecimento de caminhos completos e testar ida/volta offline.
3. **Alto — base do chaveamento antiga:** `resources/js/offline/chaveamento-engine.js::apiBase` ainda transforma `/views/src/pages/...` em `/api/`. Nos caminhos atuais a substituição não ocorre, produzindo URLs incorretas. Centralizar a base da aplicação e verificar instalação na raiz e em subdiretório.
4. **Alto — aquecimento com endpoint inexistente:** o shell usa `ocorrencias_turmas`, enquanto o roteador registra `/api/v1/ocorrencias-turmas`. Corrigir e testar disponibilidade das ocorrências da turma sem conexão.
5. **Médio — compatibilidade remanescente:** `MysqliPodioRepository::fonteAtualValida` ainda aceita `legado_conferido`; `MysqliMutationStore` permite replay sem comparar fingerprint quando `request_hash` é nulo. O motor de chaveamento ainda reconhece endpoints `.php`. O transformador `tornarReexecutavel` permanece definido, e o teste de adoção foi comentado em vez de removido.
6. **Médio — testes e documentação desatualizados:** testes de navegador ainda esperam URLs `/views/...`; `docs/architecture.md` descreve o arquivo de aliases removido e o adaptador antigo; `docs/deployment.md` instrui usar `migrate --baseline`, agora recusado. O status da migração também está desatualizado.

## Evidência de validação

- Nesta revisão: PHPUnit aprovado, 164 testes e 1.837 asserções.
- Execução anterior: 351 asserções HTTP aprovadas antes das últimas alterações de pódio; não representa validação integral do estado atual.
- Execução anterior do navegador: interrompida com falhas; relatório de autenticação confirma formulário ausente. Não atribuir todas as falhas somente a expectativas antigas.
- Nenhum código de produção foi alterado nesta auditoria.

## Critérios para concluir

Restaurar o login, corrigir URLs e navegação offline, retirar compatibilidade e código morto, atualizar documentação e expectativas dos testes. Acrescentar verificações de conteúdo nas páginas, cobertura de navegação offline e instalação em subdiretório. Depois executar `composer verify`, `npm run check`, `npm test`, `npm run build`, a suíte HTTP isolada e toda a suíte de navegador sobre o mesmo estado final. Manter as migrações já aplicadas intactas e preservar identificadores de mutações pendentes.

## Fechamento da auditoria — 09/09/2026

Os achados desta auditoria foram corrigidos e validados no estado final da migração. O login foi restaurado, as páginas e APIs passaram a usar apenas os caminhos canônicos, a navegação offline foi ajustada para raiz e subdiretório, e o aquecimento passou a usar `ocorrencias-turmas`. A compatibilidade de pontuação e replay sem fingerprint, os endpoints `.php`, o transformador sem consumidor e o cenário de adoção histórica foram removidos.

As evidências estão consolidadas em `docs/status-migracao-arquitetura-unica.md`: `composer verify`, `npm run check`, `npm test`, `npm run build`, a suíte HTTP isolada e os 46 cenários do navegador foram aprovados no mesmo estado final. A arquitetura única está concluída.
