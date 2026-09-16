const { test, expect } = require('./fixtures.cjs');

const nomeEdicao = `E08 edição selecionada ${Date.now()}`;
let idEdicao = null;
let idEdicaoOriginal = null;

async function jsonOrThrow(response, label) {
    const json = await response.json().catch(() => null);
    if (!response.ok() || !json || json.success === false) {
        throw new Error(`${label}: HTTP ${response.status()} ${JSON.stringify(json)}`);
    }
    return json;
}

async function loginAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:[/?#]|$)/, { timeout: 15_000 });
}

async function abrirResumo(page, id = idEdicao, modo = 'view', largura = 1440) {
    await loginAdmin(page);
    await page.setViewportSize({ width: largura, height: largura < 500 ? 844 : 900 });
    await page.goto(`edicoes/resumo?id=${id}&modo=${modo}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('main:visible').first()).toBeVisible();
    await expect(page.locator('#nomeInterclasseResumoMob')).toContainText(nomeEdicao);
}

async function ajustarEdicao(request, id, status) {
    return jsonOrThrow(await request.post(`api/v1/edicoes?id=${id}`, {
        data: { status_interclasse: String(status) },
    }), `status da edição ${id}`);
}

async function restaurarAtivaOriginal(request) {
    if (!idEdicaoOriginal) return;
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' },
    }), 'login para restaurar a edição ativa E08');
    await ajustarEdicao(request, idEdicaoOriginal, 1);
}

async function capturarE08(page, nome) {
    if (process.env.E08_CAPTURE !== '1') return;
    const modal = page.locator('.modal.show').first();
    if (await modal.count()) await expect(modal).toHaveCSS('opacity', '1');
    const caminho = require('node:path').join(__dirname, 'test-results', `${nome}.png`);
    await page.screenshot({ path: caminho, fullPage: true });
}

async function confirmarAtivacao(page, captura = null) {
    const dialog = page.getByRole('dialog', { name: 'Ativar edição?' });
    await expect(dialog).toBeVisible();
    await expect(dialog.locator('.sgi-feedback-modal__message')).toContainText(/edição ativa atual.*será desativada/i);
    if (captura) await capturarE08(page, captura);
    await dialog.getByRole('button', { name: 'Ativar edição', exact: true }).click();
    await expect(dialog).toHaveCount(0);
}

test.describe('E08 — contexto, resumo e conclusão da edição', () => {
    test.setTimeout(60_000);

    test.beforeAll(async ({ request }) => {
        await jsonOrThrow(await request.post('api/v1/login', {
            data: { matricula: 'admin', senha: '123' },
        }), 'login de preparação E08');
        const edicoes = await jsonOrThrow(await request.get('api/v1/edicoes?regulamento=true'), 'edições de preparação E08');
        const ativa = edicoes.find((item) => String(item.status_interclasse) === '1');
        if (!ativa) throw new Error('O fixture E08 precisa de uma edição ativa.');
        idEdicaoOriginal = Number(ativa.id_interclasse);

        const criada = await jsonOrThrow(await request.post('api/v1/edicoes', {
            data: { nome_interclasse: nomeEdicao, ano_interclasse: '2026-01-01' },
        }), 'criação de edição E08');
        idEdicao = Number(criada.id);
        if (!Number.isSafeInteger(idEdicao) || idEdicao <= 0) throw new Error('A edição E08 não retornou um ID.');
        await ajustarEdicao(request, idEdicao, 0);
        await ajustarEdicao(request, idEdicaoOriginal, 1);
    });

    test.beforeEach(async ({ request, page }) => {
        page.setDefaultTimeout(5_000);
        await jsonOrThrow(await request.post('api/v1/login', {
            data: { matricula: 'admin', senha: '123' },
        }), 'login antes do caso E08');
        await ajustarEdicao(request, idEdicao, 0);
        await ajustarEdicao(request, idEdicaoOriginal, 1);
    });

    test.afterEach(async ({ request }) => {
        await restaurarAtivaOriginal(request);
    });

    test.afterAll(async ({ request }) => {
        await restaurarAtivaOriginal(request);
    });

    test('resumo preserva edição e modo, exibe metadados e oferece a mesma ação em mobile e desktop', async ({ page }) => {
        await abrirResumo(page, idEdicao, 'view', 390);
        const linksMobile = {
            categorias: page.locator('#linkEditarCategoriasMobile'),
            modalidades: page.locator('#linkEditarModalidadesMobile'),
            pontuacao: page.locator('#linkEditarRegulamentosMobile'),
            turmas: page.locator('#linkEditarTurmasMobile'),
            voltar: page.locator('#btnVoltarMobile'),
        };
        await expect(linksMobile.categorias).toHaveAttribute('href', new RegExp(`edicoes/categorias\\?id=${idEdicao}&modo=view`));
        await expect(linksMobile.modalidades).toHaveAttribute('href', new RegExp(`edicoes/modalidades\\?id=${idEdicao}&modo=view`));
        await expect(linksMobile.pontuacao).toHaveAttribute('href', new RegExp(`edicoes/pontuacao\\?id=${idEdicao}&modo=view`));
        await expect(linksMobile.turmas).toHaveAttribute('href', new RegExp(`turmas\\?id=${idEdicao}&modo=view`));
        await expect(linksMobile.voltar).toHaveAttribute('href', new RegExp(`edicoes/pontuacao\\?id=${idEdicao}&modo=view`));
        await expect(page.locator('main:visible')).toContainText(/2026/);
        await expect(page.locator('main:visible')).toContainText(/Inativa/);
        await expect(page.locator('#acoesResumoMobile')).toContainText(/Editar categorias/i);
        await expect(page.locator('#acoesResumoMobile')).toContainText(/Ativar edição e abrir painel/i);
        await capturarE08(page, 'E08-resumo-mobile');

        await page.setViewportSize({ width: 1440, height: 900 });
        await expect(page.locator('#linkEditarCategoriasDesktop')).toHaveAttribute('href', new RegExp(`edicoes/categorias\\?id=${idEdicao}&modo=view`));
        await expect(page.locator('#linkEditarTurmasDesktop')).toHaveAttribute('href', new RegExp(`turmas\\?id=${idEdicao}&modo=view`));
        await expect(page.locator('#linkEditarRegulamentosDesktop')).toHaveAttribute('href', new RegExp(`edicoes/pontuacao\\?id=${idEdicao}&modo=view`));
        await expect(page.locator('#acoesResumoDesktop')).toContainText(/Ativar edição e abrir painel/i);
        await expect(page.locator('#acoesResumoDesktop')).not.toContainText(/Criar interclasse/i);
        await expect(page.locator('#linkEditarRegulamentosDesktop')).toContainText(/10.*7.*5.*2/s);
        await capturarE08(page, 'E08-resumo-desktop');
    });

    test('concluir uma edição já ativa apenas abre o painel e não a ativa novamente', async ({ page, request }) => {
        await ajustarEdicao(request, idEdicao, 1);
        await page.setViewportSize({ width: 1440, height: 900 });
        await loginAdmin(page);
        let postsAtivacao = 0;
        page.on('request', (requestAtual) => {
            const url = new URL(requestAtual.url());
            if (requestAtual.method() === 'POST' && url.pathname.endsWith('/api/v1/edicoes')
                && url.searchParams.get('id') === String(idEdicao)) postsAtivacao += 1;
        });
        await page.goto(`edicoes/resumo?id=${idEdicao}&modo=view`, { waitUntil: 'domcontentloaded' });
        const acao = page.locator('#btnAcaoFinalizacaoResumoDesktop');
        await expect(acao).toContainText(/Concluir configuração/i);
        await expect(acao).toHaveAttribute('href', new RegExp(`painel\\?id=${idEdicao}`));
        await acao.click();
        await expect(page).toHaveURL(new RegExp(`/painel\\?id=${idEdicao}`));
        expect(postsAtivacao).toBe(0);
    });

    test('ativar edição inativa faz um único POST confirmado e preserva a exclusividade', async ({ page, request }) => {
        await abrirResumo(page, idEdicao, 'view', 390);
        const requisicoes = [];
        page.on('request', (requestAtual) => {
            const url = new URL(requestAtual.url());
            if (requestAtual.method() === 'POST' && url.pathname.endsWith('/api/v1/edicoes')) {
                requisicoes.push({ id: url.searchParams.get('id'), body: requestAtual.postData() });
            }
        });
        await page.locator('#btnAcaoFinalizacaoResumoMobile').click();
        await confirmarAtivacao(page, 'E08-ativacao-mobile');
        await expect(page).toHaveURL(new RegExp(`/painel\\?id=${idEdicao}`));
        expect(requisicoes).toEqual([{ id: String(idEdicao), body: expect.stringContaining('status_interclasse') }]);

        const edicoes = await jsonOrThrow(await request.get('api/v1/edicoes?regulamento=true'), 'edições após ativação');
        expect(edicoes.filter((item) => String(item.status_interclasse) === '1').map((item) => Number(item.id_interclasse))).toEqual([idEdicao]);
    });

    test('HTTP ruim, HTML 200 e JSON de recusa ficam no resumo e permitem nova tentativa', async ({ page }) => {
        await abrirResumo(page, idEdicao, 'view', 1440);
        const respostas = [
            { status: 500, contentType: 'application/json', body: '{"success":true}' },
            { status: 200, contentType: 'text/html', body: '<html>manutenção</html>' },
            { status: 200, contentType: 'application/json', body: '{"success":false,"message":"Falha temporária"}' },
        ];
        let tentativa = 0;
        await page.route('**/api/v1/edicoes?id=*', async (route) => {
            const requisicao = route.request();
            const url = new URL(requisicao.url());
            if (requisicao.method() === 'POST' && url.searchParams.get('id') === String(idEdicao) && tentativa < respostas.length) {
                await route.fulfill(respostas[tentativa++]);
                return;
            }
            await route.continue();
        });

        const acao = page.locator('#btnAcaoFinalizacaoResumoDesktop');
        for (let indice = 0; indice < respostas.length; indice += 1) {
            await acao.click();
            await confirmarAtivacao(page);
            const erro = page.getByRole('dialog', { name: /não foi possível ativar/i });
            await expect(erro).toBeVisible();
            await erro.getByRole('button', { name: 'Entendi' }).click();
            await expect(erro).toHaveCount(0);
            await expect(page).toHaveURL(new RegExp(`/edicoes/resumo\\?id=${idEdicao}&modo=view`));
            await expect(acao).toBeEnabled();
        }

        await acao.click();
        await confirmarAtivacao(page);
        await expect(page).toHaveURL(new RegExp(`/painel\\?id=${idEdicao}`));
        expect(tentativa).toBe(respostas.length);
    });

    test('duplo clique na ação final não dispara duas ativações', async ({ page }) => {
        await abrirResumo(page, idEdicao, 'view', 1440);
        let postsAtivacao = 0;
        page.on('request', (requestAtual) => {
            const url = new URL(requestAtual.url());
            if (requestAtual.method() === 'POST' && url.pathname.endsWith('/api/v1/edicoes')
                && url.searchParams.get('id') === String(idEdicao)) postsAtivacao += 1;
        });
        await page.locator('#btnAcaoFinalizacaoResumoDesktop').evaluate((button) => {
            button.click();
            button.click();
        });
        await confirmarAtivacao(page);
        await expect(page).toHaveURL(new RegExp(`/painel\\?id=${idEdicao}`));
        expect(postsAtivacao).toBe(1);
    });

    test('continuar com pontuação alterada permite cancelar e descartar sem salvar', async ({ page, request }) => {
        await loginAdmin(page);
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`edicoes/pontuacao?id=${idEdicao}&modo=create`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#pontos-1')).toHaveValue('10');
        await expect(page.locator('#ptcEditionName')).toHaveText(nomeEdicao);
        await expect(page.locator('#ptcEditionYear')).toHaveText('Ano 2026');
        await expect(page.locator('#ptcEditionStatus')).toHaveText('Inativa');
        await page.locator('#pontos-1').fill('21');

        await page.locator('#btnVoltarPontuacao').click();
        let dialog = page.getByRole('dialog', { name: 'Alterações não salvas' });
        await expect(dialog).toBeVisible();
        await capturarE08(page, 'E08-pontuacao-alteracoes-nao-salvas');
        await dialog.getByRole('button', { name: 'Cancelar navegação' }).click();
        await expect(dialog).toHaveCount(0);
        await expect(page).toHaveURL(new RegExp(`/edicoes/pontuacao\\?id=${idEdicao}&modo=create`));
        await expect(page.locator('#pontos-1')).toHaveValue('21');

        await page.locator('#btnContinuarPontuacao').click();
        dialog = page.getByRole('dialog', { name: 'Alterações não salvas' });
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Descartar e continuar' }).click();
        await expect(page).toHaveURL(new RegExp(`/edicoes/resumo\\?id=${idEdicao}&modo=create`));
        const detalhes = await jsonOrThrow(await request.get(`api/v1/edicoes?id=${idEdicao}`), 'pontuação após descarte');
        expect(Number((Array.isArray(detalhes) ? detalhes[0] : detalhes).ponto_1_lugar)).toBe(10);
    });

    test('salvar alterações de pontuação e continuar persiste antes de navegar', async ({ page, request }) => {
        await loginAdmin(page);
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`edicoes/pontuacao?id=${idEdicao}&modo=create`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#pontos-1')).toHaveValue('10');
        await page.locator('#pontos-1').fill('23');
        await page.locator('#btnContinuarPontuacao').click();
        const dialog = page.getByRole('dialog', { name: 'Alterações não salvas' });
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Salvar e continuar' }).click();
        await expect(page).toHaveURL(new RegExp(`/edicoes/resumo\\?id=${idEdicao}&modo=create`));
        const detalhes = await jsonOrThrow(await request.get(`api/v1/edicoes?id=${idEdicao}`), 'pontuação após salvar');
        expect(Number((Array.isArray(detalhes) ? detalhes[0] : detalhes).ponto_1_lugar)).toBe(23);
    });

    test('links do fluxo mantêm prefixo de subdiretório sem duplicá-lo', async ({ page }) => {
        await loginAdmin(page);
        await page.addInitScript(() => {
            Object.defineProperty(window, 'SGI_BASE_PATH', {
                configurable: true,
                get: () => '/SGI',
                set: () => {},
            });
        });
        await page.route('**/SGI/api/v1/**', async (route) => {
            const url = new URL(route.request().url());
            url.pathname = url.pathname.replace(/^\/SGI(?=\/api\/v1\/)/, '');
            await route.continue({ url: url.toString() });
        });
        const rotas = ['edicoes/resumo', 'edicoes/pontuacao', 'edicoes/modalidades'];
        for (const rota of rotas) {
            const modo = rota === 'edicoes/resumo' ? 'view' : 'create';
            await page.goto(`${rota}?id=${idEdicao}&modo=${modo}`, { waitUntil: 'domcontentloaded' });
            await expect(page.locator('main:visible').first()).toBeVisible();
            await expect.poll(() => page.evaluate(() => String(window.SGI_BASE_PATH || ''))).toBe('/SGI');
            if (rota === 'edicoes/resumo') {
                await expect(page.locator('#nomeInterclasseResumo')).toContainText(nomeEdicao);
                await expect(page.locator('#linkEditarTurmasDesktop')).toHaveAttribute('href', `/SGI/turmas?id=${idEdicao}&modo=view`);
                await expect(page.locator('#linkEditarCategoriasDesktop')).toHaveAttribute('href', `/SGI/edicoes/categorias?id=${idEdicao}&modo=view`);
            } else if (rota === 'edicoes/pontuacao') {
                await expect(page.locator('#btnVoltarPontuacao')).toHaveAttribute('href', `/SGI/edicoes/modalidades?id=${idEdicao}&modo=create`);
                await expect(page.locator('#btnContinuarPontuacao')).toHaveAttribute('href', `/SGI/edicoes/resumo?id=${idEdicao}&modo=create`);
            } else {
                await expect(page.locator('#btnVoltarModalidades')).toHaveAttribute('href', `/SGI/edicoes/categorias?id=${idEdicao}&modo=create`);
                await expect(page.locator('#btnContinuarDesktop')).toHaveAttribute('href', new RegExp(`^/SGI/edicoes/pontuacao\\?id=${idEdicao}&modo=create`));
            }
        }
    });
});
