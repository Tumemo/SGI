# Status da migração para arquitetura única

## Fechamento — 09/09/2026

O plano `docs/plano-migracao-arquitetura-unica-luna.md` foi concluído considerando todos os achados de `docs/auditoria-arquitetura-atual.md`. A aplicação agora inicia pelo bootstrap modular, expõe somente páginas canônicas e APIs `/api/v1`, e não carrega aliases ou caminhos da implementação anterior.

Foram corrigidos o template de login, a navegação e o aquecimento do shell offline, a resolução de URLs em raiz e subdiretório, o motor de chaveamento, a validação de replay por `request_hash` e o fluxo de créditos de pódio. Também foram removidos os adaptadores de compatibilidade, o transformador de scripts sem uso, a adoção histórica e o teste de upgrade legado. Os testes, seeds e a documentação foram alinhados com a arquitetura única.

## Evidências

- `composer verify`: 164 testes PHPUnit, 1.837 asserções; 211 arquivos PHPStan; 271 arquivos PHP CS Fixer sem alterações; sintaxe PHP aprovada.
- `npm run check`: 40 arquivos JavaScript válidos.
- `npm test`: 17 testes JavaScript aprovados.
- `npm run build`: 134 assets compilados.
- `php tests/run_all.php`: 347/347 asserções HTTP aprovadas.
- `npm --prefix tests/browser test`: 46/46 cenários Playwright aprovados, incluindo login, RBAC, jornadas administrativas e do aluno, mesário online/offline, persistência de cronômetro e placar, chaveamento completo e instalação em subdiretório.
- Varredura final sem referências ativas a `views/src`, endpoints `.php`, caminhos relativos legados, `tornarReexecutavel`, `legado_conferido` ou `adotar`.

O servidor de testes permanece disponível em `http://127.0.0.1:8099`. Não há pendências funcionais identificadas na auditoria; qualquer evolução posterior deve ser feita dentro dos módulos canônicos e das rotas versionadas.
