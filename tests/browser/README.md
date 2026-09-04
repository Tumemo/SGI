# Fluxo visual do mesário offline

O teste usa Playwright porque o cenário precisa alternar a rede do navegador,
validar o IndexedDB e guardar screenshots das telas. Ele cobre login, preload,
navegação SPA offline, início da partida, placar, artilharia, ocorrência,
finalização local e sincronização automática ao reconectar.

Há dois fluxos visuais:

- `mesario-offline.spec.cjs`: uma partida completa, incluindo artilharia e ocorrência.
- `tournament-offline.spec.cjs`: quatro quartas, duas semifinais e a final
  gerada pelo chaveamento; o cenário online valida a progressão completa e o
  cenário offline joga todas as sete partidas sem rede, depois sincroniza em
  uma única reconexão e valida o campeão no servidor. O teste também confere,
  em cada partida positiva e nas fases negativas criadas localmente, título,
  modalidade, local, data/horário, equipes e placar exibidos no placar offline.
- `frontend-regression.spec.cjs`: mapa visual de todas as rotas de usuário do
  frontend (login desktop/mobile, administrador, colaborador, mesário SPA e
  portal do aluno), incluindo permissões, dados reais da edição ativa, layout
  responsivo e captura de cada tela.

## Instalação

Na raiz do projeto:

```powershell
npm --prefix tests/browser install
```

O teste utiliza o Chrome instalado em `C:\Program Files\Google\Chrome\Application\chrome.exe`.
Para indicar outro executável, defina `SGI_CHROME_PATH`.

## Execução

Com Apache/MySQL ativos e a base de desenvolvimento carregada:

```powershell
npm --prefix tests/browser test
```

Para executar somente o fluxo de torneio:

```powershell
npm --prefix tests/browser test -- tournament-offline.spec.cjs
```

Para validar diretamente o workspace (sem depender de uma cópia Apache),
aponte a base do Playwright para o servidor PHP em execução:

```powershell
$env:SGI_BASE_URL = 'http://127.0.0.1:8083/'
npm --prefix tests/browser test -- tournament-offline.spec.cjs
```

Para executar somente o mapa visual de regressão do frontend:

```powershell
npm --prefix tests/browser test -- frontend-regression.spec.cjs
```

Para recriar a base demo antes do teste (ação destrutiva somente para o ambiente
de desenvolvimento):

```powershell
$env:SGI_E2E_RESET = '1'
npm --prefix tests/browser test
```

Para acompanhar o navegador visivelmente:

```powershell
$env:SGI_HEADFUL = '1'
npm --prefix tests/browser run test:headed
```

Screenshots em caso de falha, trace e relatório HTML ficam em
`tests/browser/test-results` e `tests/browser/playwright-report`.

A suíte PHP também aceita `SGI_TEST_BASE_URL` para usar o mesmo servidor do
workspace (`C:/xampp/php/php.exe tests/run_all.php`), mantendo
`http://localhost/SGI` como padrão compatível com instalações existentes.

O mapa visual também salva as capturas aprovadas dentro da pasta de resultados
de cada teste. Elas servem como linha de base para comparar as telas antes e
depois da refatoração.
