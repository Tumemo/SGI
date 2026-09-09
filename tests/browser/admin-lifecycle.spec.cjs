const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes/, { timeout: 15_000 });
}

test.describe.serial('Gestão Administrativa Completa (Admin Lifecycle)', () => {
    let idEdicaoCriada = null;
    let idEdicaoOriginal = null;
    const nomeEdicaoTeste = `Interclasse E2E Playwright ${Date.now()}`;

    test.beforeAll(async ({ request }) => {
        const login = await request.post('api/v1/login', { data: { matricula: 'admin', senha: '123' } });
        expect(login.ok()).toBeTruthy();
        const res = await request.get('api/v1/edicoes?regulamento=true');
        if (res.ok()) {
            const data = await res.json();
            const ativa = Array.isArray(data) ? data.find(e => String(e.status_interclasse) === '1') : null;
            if (ativa) idEdicaoOriginal = Number(ativa.id_interclasse);
        }
    });

    test.afterAll(async ({ request }) => {
        if (idEdicaoOriginal) {
            await request.post('api/v1/login', { data: { matricula: 'admin', senha: '123' } });
            const restored = await request.post(`api/v1/edicoes?id=${idEdicaoOriginal}`, {
                data: { status_interclasse: '1' }
            });
            expect((await restored.json()).success).toBe(true);
        }
    });

    test('criação de nova edição via modal e navegação para o dashboard', async ({ page }) => {
        await entrarComoAdmin(page);

        // Abrir modal de criação de edição
        const btnNovaEdicao = page.locator('button[data-bs-target="#exampleModal"]:visible');
        await expect(btnNovaEdicao).toBeVisible();
        await btnNovaEdicao.click();

        const modal = page.locator('#exampleModal');
        await expect(modal).toBeVisible();

        // Preencher nome e ano
        await page.locator('#nomeNovaEdicao').fill(nomeEdicaoTeste);
        await page.locator('#anoNovaEdicao').fill('2026');

        // Submeter criação
        await page.locator('#btnCriar').click();

        // A tela exibe confirmação e redireciona automaticamente para o dashboard da nova edição
        await page.waitForURL(/\/painel\?id=\d+/, { timeout: 20_000 });
        const url = new URL(page.url());
        idEdicaoCriada = Number(url.searchParams.get('id'));
        expect(idEdicaoCriada).toBeGreaterThan(0);
        await expect(page.locator('#conteudo-principal')).toBeVisible();

        // Volta para a home e valida se o card da nova edição está listado
        await page.goto('edicoes', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#listaDesktop')).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('#listaDesktop .row', { hasText: nomeEdicaoTeste })).toBeVisible({ timeout: 15_000 });
    });

    test('configuração de pontuações de pódio e arrecadação com persistência', async ({ page }) => {
        expect(idEdicaoCriada).toBeTruthy();
        await entrarComoAdmin(page);

        await page.goto(`edicoes/pontuacao?id=${idEdicaoCriada}&modo=view`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#pontos-1')).toBeVisible({ timeout: 15_000 });

        // Ajustar pontuações personalizadas
        await page.locator('#pontos-1').fill('25');
        await page.locator('#pontos-2').fill('15');
        await page.locator('#pontos-3').fill('10');
        await page.locator('#pontos-arr').fill('5');

        // Salvar alterações
        const btnSalvar = page.locator('#btnSalvarPontuacao');
        await btnSalvar.click();

        // Validar confirmação visual
        await expect(btnSalvar).toContainText(/Salvo!|Salvar/, { timeout: 10_000 });

        // Recarregar a página e conferir se os dados foram persistidos no MySQL
        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(page.locator('#pontos-1')).toHaveValue('25');
        await expect(page.locator('#pontos-2')).toHaveValue('15');
        await expect(page.locator('#pontos-3')).toHaveValue('10');
        await expect(page.locator('#pontos-arr')).toHaveValue('5');
    });

    test('cadastro de novo local de jogos via modal e listagem imediata', async ({ page }) => {
        expect(idEdicaoCriada).toBeTruthy();
        await entrarComoAdmin(page);

        await page.goto(`edicoes/locais?id=${idEdicaoCriada}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#listaLocaisDesktop')).toBeVisible({ timeout: 15_000 });

        const nomeLocal = `Arena Playwright ${Date.now()}`;

        // Abrir modal de novo local
        const btnNovoLocal = page.locator('button[data-bs-target="#modalNovoLocal"]:visible');
        await expect(btnNovoLocal).toBeVisible();
        await btnNovoLocal.click();

        const modalLocal = page.locator('#modalNovoLocal');
        await expect(modalLocal).toBeVisible();

        // Preencher dados do local
        await page.locator('#inputNomeLocal').fill(nomeLocal);
        await page.locator('#selectDisponivelLocal').selectOption('1');
        await page.locator('#inputCargaLocal').fill('600');

        // Salvar local
        await page.locator('#btnSalvarLocal').click();
        await expect(modalLocal).toBeHidden({ timeout: 10_000 });

        // Verificar que o novo local aparece na listagem
        await expect(page.locator('#listaLocaisDesktop')).toContainText(nomeLocal, { timeout: 15_000 });
    });

    test('criação de nova categoria e nova modalidade esportiva', async ({ page }) => {
        expect(idEdicaoCriada).toBeTruthy();
        await entrarComoAdmin(page);

        // 1. Criar nova categoria
        await page.goto(`edicoes/categorias?id=${idEdicaoCriada}&modo=view`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#listaCategoriasDesktop')).toBeVisible({ timeout: 15_000 });

        const nomeCategoria = `Categoria E2E ${Date.now()}`;
        const btnNovaCat = page.locator('button[data-bs-target="#modalCriarCategoria"]:visible');
        await expect(btnNovaCat).toBeVisible();
        await btnNovaCat.click();

        const modalCat = page.locator('#modalCriarCategoria');
        await expect(modalCat).toBeVisible();
        await page.locator('#inputNomeCategoriaNova').fill(nomeCategoria);
        await page.locator('#btnSalvarCategoria').click();
        await expect(modalCat).toBeHidden({ timeout: 10_000 });

        // Conferir se a categoria aparece na lista
        await expect(page.locator('#listaCategoriasDesktop')).toContainText(nomeCategoria, { timeout: 15_000 });

        // 2. Criar nova modalidade
        await page.goto(`edicoes/modalidades?id=${idEdicaoCriada}&modo=view`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#listaModalidadesDesktop')).toBeVisible({ timeout: 15_000 });

        const nomeModalidade = `Queimada E2E ${Date.now()}`;
        const btnNovaMod = page.locator('button[data-bs-target="#exampleModal"]:visible');
        await expect(btnNovaMod).toBeVisible();
        await btnNovaMod.click();

        const modalMod = page.locator('#exampleModal');
        await expect(modalMod).toBeVisible();

        await page.locator('#inputNomeModalidade').fill(nomeModalidade);
        await page.locator('#inputGeneroModalidade').selectOption('MISTO');
        await page.locator('#inputMaxInscritos').fill('15');
        await page.locator('#inputMaxEquipes').fill('2');

        // Aguardar o carregamento das opções dos selects
        await expect.poll(async () => {
            const countTipos = await page.locator('#inputTipoModalidade option').count();
            const countCats = await page.locator('#inputCategoriaModalidade option').count();
            return countTipos > 1 && countCats > 1;
        }, { timeout: 15_000 }).toBe(true);

        await page.locator('#inputTipoModalidade').selectOption({ index: 1 });
        await page.locator('#inputCategoriaModalidade').selectOption({ index: 1 });

        await page.locator('#btnSalvarModalidade').click();
        await expect(modalMod).toBeHidden({ timeout: 15_000 });

        // Conferir se a modalidade aparece na listagem
        await expect(page.locator('#listaModalidadesDesktop')).toContainText(nomeModalidade, { timeout: 15_000 });
    });

    test('registro de ocorrência disciplinar, arrecadação e conferência no ranking geral', async ({ page }) => {
        expect(idEdicaoCriada).toBeTruthy();
        await entrarComoAdmin(page);

        // 1. Lançar ocorrência disciplinar em uma turma
        await page.goto(`ocorrencias?id=${idEdicaoCriada}`, { waitUntil: 'domcontentloaded' });
        const gridOcorrencias = page.locator('#listaOcorrenciasDesktop');
        await expect(gridOcorrencias).toBeVisible({ timeout: 15_000 });
        await expect(gridOcorrencias.locator('.ocr-card').first()).toBeVisible({ timeout: 15_000 });

        // Clicar no botão de adicionar ocorrência no primeiro card de turma
        const btnAddOcr = gridOcorrencias.locator('.ocr-card__add').first();
        await btnAddOcr.click();

        const modalOcr = page.locator('#modalNovaOcorrencia');
        await expect(modalOcr).toBeVisible();
        await page.locator('#ocrTituloModal').fill('Ocorrência E2E Penalidade');
        await page.locator('#ocrPontosModal').fill('8');
        await page.locator('#btnSalvarOcrModal').click();
        await expect(modalOcr).toBeHidden({ timeout: 10_000 });

        // 2. Lançar arrecadação solidária na turma
        await page.goto(`edicoes/arrecadacao?id=${idEdicaoCriada}`, { waitUntil: 'domcontentloaded' });
        const gridArrecadacao = page.locator('#listaArrecadacaoDesktop');
        await expect(gridArrecadacao).toBeVisible({ timeout: 15_000 });
        await expect(gridArrecadacao.locator('.ocr-card').first()).toBeVisible({ timeout: 15_000 });

        const primeiroCard = gridArrecadacao.locator('.ocr-card').first();
        const inputQtd = primeiroCard.locator('.ocr-card__input');
        const btnSalvarArr = primeiroCard.locator('.ocr-card__save');

        await inputQtd.fill('35');

        const dialogPromise = page.waitForEvent('dialog');
        await btnSalvarArr.click();
        const dialog = await dialogPromise;
        await dialog.accept();

        // Aguarda a finalização do salvamento
        await expect(primeiroCard.locator('.ocr-card__save')).not.toBeDisabled({ timeout: 10_000 });

        // 3. Consultar a tela de Ranking Geral
        await page.goto(`ranking?id=${idEdicaoCriada}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#listaDesk')).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('#totalTurmasDesk')).toContainText(/Turmas/, { timeout: 15_000 });
    });

});
