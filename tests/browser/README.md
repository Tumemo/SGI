# Testes do navegador

Consulte [o guia de execução](../../docs/testing.md) para preparar banco, servidor, Chromium e variáveis de ambiente.

- `auth-rbac.spec.cjs`: autenticação, saída e permissões.
- `admin-lifecycle.spec.cjs`: criação e configuração de uma edição.
- `aluno-portal.spec.cjs`: termos, inscrições, agenda e perfil.
- `frontend-regression.spec.cjs`: navegação, conteúdo e layout das telas de todos os perfis.
- `mesario-offline.spec.cjs`: partida offline com gol, ocorrência e sincronização.
- `tournament-offline.spec.cjs`: sete partidas online e sete sem rede, com confirmação do campeão.
- `offline-tournament-bracket.spec.cjs`: projeções locais e árvore completa do torneio.
- `visual-contract.spec.cjs`: comparação das imagens de login no Windows.

As suítes compartilham uma base isolada e executam com um único worker. Não rode dois processos Playwright sobre a mesma base ou pasta de resultados. As preparações por API usam o token CSRF real da sessão.
