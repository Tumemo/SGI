const { test, expect } = require('./fixtures.cjs');

const MOCK_CATEGORIES = [
    { id_categoria: 21, nome_categoria: 'Sub-15', interclasses_id_interclasse: 1 },
    { id_categoria: 22, nome_categoria: 'Sub-17', interclasses_id_interclasse: 1 },
];

const MOCK_MODALITIES = [
    {
        id_modalidade: 11,
        id_tipo_modalidade: 1,
        interclasses_id_interclasse: 1,
        categorias_id_categoria: 21,
        nome_modalidade: 'Vôlei adaptado',
        nome_categoria: 'Sub-15',
        nome_tipo_modalidade: 'mata-mata',
        tipo_competicao: 'mata_mata',
        genero_modalidade: 'MISTO',
    },
    {
        id_modalidade: 12,
        id_tipo_modalidade: 1,
        interclasses_id_interclasse: 1,
        categorias_id_categoria: 22,
        nome_modalidade: 'Futsal',
        nome_categoria: 'Sub-17',
        nome_tipo_modalidade: 'mata-mata',
        tipo_competicao: 'mata_mata',
        genero_modalidade: 'MISTO',
    },
    {
        id_modalidade: 13,
        id_tipo_modalidade: 1,
        interclasses_id_interclasse: 1,
        categorias_id_categoria: 21,
        nome_modalidade: 'Futsal',
        nome_categoria: 'Sub-15',
        nome_tipo_modalidade: 'mata-mata',
        tipo_competicao: 'mata_mata',
        genero_modalidade: 'FEMININO',
    },
];

function jogoFixture({
    id,
    idModalidade,
    idCategoria,
    modalidade,
    categoria,
    equipeA,
    equipeB,
    status = 'Finalizado',
    golsA = 3,
    golsB = 1,
}) {
    const idEquipeA = id * 10 + 1;
    const idEquipeB = id * 10 + 2;
    return {
        id_jogo: id,
        id_interclasse: 1,
        id_modalidade: idModalidade,
        modalidades_id_modalidade: idModalidade,
        id_categoria: idCategoria,
        categorias_id_categoria: idCategoria,
        nome_modalidade: modalidade,
        nome_categoria: categoria,
        nome_tipo_modalidade: 'mata-mata',
        tipo_competicao: 'mata_mata',
        nome_jogo: 'MM:2:0:N',
        nome_fase: 'Final',
        fase_nivel: 2,
        posicao_na_chave: 1,
        status_jogo: status,
        data_jogo: '2026-09-14',
        inicio_jogo: '09:00:00',
        termino_jogo: '09:20:00',
        locais_id_local: 1,
        nome_local: 'Quadra E06',
        equipe_vencedora_id: idEquipeA,
        equipes_nomes: `${equipeA} vs ${equipeB}`,
        equipes: [
            { id_equipe: idEquipeA, nome_equipe: equipeA, gols: golsA },
            { id_equipe: idEquipeB, nome_equipe: equipeB, gols: golsB },
        ],
    };
}

const MOCK_GAMES = [
    jogoFixture({
        id: 501,
        idModalidade: 11,
        idCategoria: 21,
        modalidade: 'Vôlei adaptado',
        categoria: 'Sub-15',
        equipeA: 'Tubarões do Vôlei',
        equipeB: 'Falcões do Vôlei',
    }),
    jogoFixture({
        id: 502,
        idModalidade: 12,
        idCategoria: 22,
        modalidade: 'Futsal',
        categoria: 'Sub-17',
        equipeA: 'Corujas do Futsal',
        equipeB: 'Lobos do Futsal',
        golsA: 2,
        golsB: 0,
    }),
];

async function fulfillJson(route, payload) {
    await route.fulfill({
        status: 200,
        contentType: 'application/json; charset=utf-8',
        body: JSON.stringify(payload),
    });
}

async function entrarMesario(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    const formulario = page.locator('#form_mobile');
    const form = (await formulario.isVisible()) ? formulario : page.locator('#form_desktop');
    await form.locator('.ipt-matricula').fill('mesario');
    await form.locator('.ipt-senha').fill('123');
    await form.locator('button[type="submit"]').click();
    await page.waitForURL(/painel\?id=\d+/, { waitUntil: 'domcontentloaded', timeout: 15_000 });
}

async function instalarDadosDeTela(page) {
    await page.route((url) => url.pathname.endsWith('/api/v1/modalidades'), async (route) => {
        const idEdicao = new URL(route.request().url()).searchParams.get('id_interclasse');
        await fulfillJson(route, MOCK_MODALITIES.map((item) => ({ ...item, interclasses_id_interclasse: Number(idEdicao) })));
    });

    await page.route((url) => url.pathname.endsWith('/api/v1/categorias'), async (route) => {
        const idEdicao = new URL(route.request().url()).searchParams.get('id_interclasse');
        await fulfillJson(route, MOCK_CATEGORIES.map((item) => ({ ...item, interclasses_id_interclasse: Number(idEdicao) })));
    });

    await page.route((url) => url.pathname.endsWith('/api/v1/jogos'), async (route) => {
        const query = new URL(route.request().url()).searchParams;
        const idModalidade = query.get('id_modalidade');
        const idCategoria = query.get('id_categoria');
        const jogos = MOCK_GAMES.filter((item) =>
            (!idModalidade || Number(item.modalidades_id_modalidade) === Number(idModalidade))
            && (!idCategoria || Number(item.categorias_id_categoria) === Number(idCategoria))
        );
        await fulfillJson(route, jogos);
    });

    await page.route((url) => url.pathname.endsWith('/api/v1/chaveamentos'), async (route) => {
        const idModalidade = new URL(route.request().url()).searchParams.get('id_modalidade');
        const jogos = MOCK_GAMES.filter((item) => Number(item.modalidades_id_modalidade) === Number(idModalidade));
        await fulfillJson(route, { success: true, jogos });
    });

    await page.route((url) => url.pathname.endsWith('/api/v1/agenda-blocos'), async (route) => {
        await fulfillJson(route, []);
    });
}

async function abrirChaveamento(page, width = 640, height = 768) {
    await page.setViewportSize({ width, height });
    await entrarMesario(page);
    const idInterclasse = new URL(page.url()).searchParams.get('id');
    expect(Number(idInterclasse)).toBeGreaterThan(0);

    await instalarDadosDeTela(page);
    await page.goto(`chaveamento?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
    // The SGI shell keeps its compact composition below 1200px, including
    // tablet widths such as 1024px.
    const mobile = width < 1200;
    const selectors = mobile
        ? { main: '.sgi-chaveamento-mobile', games: '#tbodyJogosMob', bracket: '#bracketAreaMob' }
        : { main: '.sgi-chaveamento-desktop', games: '#tbodyJogos', bracket: '#bracketArea' };
    await expect(page.locator(selectors.main)).toBeVisible({ timeout: 8_000 });
    await expect(page.locator(`${selectors.games} tr`)).toHaveCount(2, { timeout: 10_000 });
    return { idInterclasse: Number(idInterclasse), ...selectors, mobile };
}

async function abrirBuscaKvs(seletor) {
    const trigger = seletor.locator('.kvs__trigger');
    await expect(trigger).toBeVisible({ timeout: 5_000 });
    await trigger.click({ timeout: 5_000 });

    // Assert the popup opened before querying its textbox. If activation is
    // immediately dismissed, fail here with a short, useful error instead of
    // waiting for the test-wide timeout on a textbox that is not exposed.
    const painel = seletor.locator('.kvs__panel');
    await expect(painel).toBeVisible({ timeout: 5_000 });
    const busca = painel.getByRole('combobox', { name: 'Buscar modalidade' });
    await expect(busca).toBeVisible({ timeout: 5_000 });
    return busca;
}

async function selecionarModalidadeNoHistorico(page, nome) {
    const mobile = await page.locator('.sgi-chaveamento-mobile').isVisible();
    const seletor = page.locator(mobile
        ? '#kvs-wrap-filtroModalidadeJogosMob'
        : '#kvs-wrap-filtroModalidadeJogos');
    const busca = await abrirBuscaKvs(seletor);
    await busca.fill(nome);
    const opcao = seletor.locator('.kvs__opcao').filter({ hasText: nome });
    await expect(opcao).toHaveCount(1, { timeout: 5_000 });
    await opcao.click({ timeout: 5_000 });
}

async function selecionarModalidadeDoChaveamento(page, nome) {
    const mobile = await page.locator('.sgi-chaveamento-mobile').isVisible();
    const seletor = page.locator(mobile
        ? '#kvs-wrap-selectModalidadeMob'
        : '#kvs-wrap-selectModalidade');
    const busca = await abrirBuscaKvs(seletor);
    await busca.fill(nome);
    const opcao = seletor.locator('.kvs__opcao').filter({ hasText: nome });
    await expect(opcao).toHaveCount(1, { timeout: 5_000 });
    await opcao.click({ timeout: 5_000 });
}

test.describe('E06 — filtros, leitura e ações do chaveamento', () => {
    test('filtrar por modalidade no compacto atualiza os jogos exibidos', async ({ page }) => {
        await abrirChaveamento(page);

        await selecionarModalidadeNoHistorico(page, 'Vôlei adaptado');

        const historico = page.locator('#tbodyJogosMob');
        await expect(historico.locator('tr')).toHaveCount(1);
        await expect(historico).toContainText('Tubarões do Vôlei');
        await expect(historico).not.toContainText('Corujas do Futsal');
        await expect(page.locator('#filtroModalidadeJogosMob')).toHaveValue('11');
    });

    test('filtrar por categoria no compacto atualiza os jogos exibidos', async ({ page }) => {
        await abrirChaveamento(page);

        await page.locator('#filtroCategoriaJogosMob').selectOption('21');

        const historico = page.locator('#tbodyJogosMob');
        await expect(historico.locator('tr')).toHaveCount(1);
        await expect(historico).toContainText('Tubarões do Vôlei');
        await expect(historico).not.toContainText('Corujas do Futsal');
    });

    test('busca nomes sem diacríticos e mantém a modalidade selecionada ao pesquisar e redimensionar', async ({ page }) => {
        await abrirChaveamento(page);

        const seletor = page.locator('#kvs-wrap-filtroModalidadeJogosMob');
        const busca = await abrirBuscaKvs(seletor);
        await busca.fill('volei');
        await expect(seletor.locator('.kvs__opcao').filter({ hasText: 'Vôlei adaptado' })).toHaveCount(1);
        await seletor.locator('.kvs__opcao').filter({ hasText: 'Vôlei adaptado' }).click();

        const triggerMob = seletor.locator('.kvs__trigger');
        const triggerDesk = page.locator('#kvs-wrap-filtroModalidadeJogos .kvs__trigger');
        await expect(triggerMob).toBeFocused();
        await page.setViewportSize({ width: 1200, height: 900 });
        await expect(triggerDesk).toBeFocused({ timeout: 5_000 });
        await page.setViewportSize({ width: 640, height: 768 });
        await expect(triggerMob).toBeVisible();
        await triggerMob.focus();
        await expect(triggerMob).toBeFocused({ timeout: 5_000 });

        const buscaReaberta = await abrirBuscaKvs(seletor);
        await buscaReaberta.fill('volei adaptado');
        await expect(seletor.locator('.kvs__opcao--ativa')).toContainText('Vôlei adaptado');

        await page.setViewportSize({ width: 1200, height: 900 });
        await expect(page.locator('.sgi-chaveamento-desktop')).toBeVisible();
        await expect(page.locator('#kvs-wrap-filtroModalidadeJogos .kvs__trigger')).toBeFocused({ timeout: 5_000 });
        await expect(page.locator('#filtroModalidadeJogos')).toHaveValue('11');
        await expect(page.locator('#kvs-wrap-filtroModalidadeJogos .kvs__trigger')).toContainText('Vôlei adaptado');
        await expect(page.locator('#tbodyJogos tr')).toHaveCount(1);
        await expect(page.locator('#tbodyJogos')).toContainText('Tubarões do Vôlei');
        await expect(page.locator('#tbodyJogos')).not.toContainText('Corujas do Futsal');
    });

    test('distingue modalidades com o mesmo nome pelo grupo de categoria e seleciona o ID correto', async ({ page }) => {
        await abrirChaveamento(page);

        const seletor = page.locator('#kvs-wrap-filtroModalidadeJogosMob');
        const busca = await abrirBuscaKvs(seletor);
        await busca.fill('futsal');

        const lista = seletor.locator('[role="listbox"]');
        const grupoSub15 = lista.getByRole('group', { name: 'Sub-15' });
        const grupoSub17 = lista.getByRole('group', { name: 'Sub-17' });
        await expect(grupoSub15.getByRole('option')).toHaveCount(1, { timeout: 5_000 });
        await expect(grupoSub17.getByRole('option')).toHaveCount(1, { timeout: 5_000 });
        await expect(grupoSub15.getByRole('option')).toContainText('Futsal');
        await expect(grupoSub17.getByRole('option')).toContainText('Futsal');

        await grupoSub15.getByRole('option').click();
        await expect(page.locator('#filtroModalidadeJogosMob')).toHaveValue('13');
        await expect(seletor.locator('.kvs__trigger')).toContainText('Futsal — Sub-15');
        await expect(page.locator('#tbodyJogosMob')).toContainText('Nenhum jogo encontrado.');
    });

    test('KVS funciona por teclado, fecha com Escape e Tab e restaura o foco', async ({ page }) => {
        await abrirChaveamento(page, 640, 360);

        const seletor = page.locator('#kvs-wrap-filtroModalidadeJogosMob');
        const trigger = seletor.locator('.kvs__trigger');
        const painel = seletor.locator('.kvs__panel');
        const busca = painel.getByRole('combobox', { name: 'Buscar modalidade' });

        await expect(trigger).toHaveCount(1);
        // Bootstrap enables smooth scrolling by default. Focusing an offscreen
        // trigger starts a native scroll animation; the KVS correctly closes on
        // real scrolling, so settle the focus target before testing activation.
        await page.evaluate(() => {
            const alvo = document.querySelector('#kvs-wrap-filtroModalidadeJogosMob .kvs__trigger');
            alvo.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'instant' });
            alvo.focus({ preventScroll: true });
        });
        await expect(trigger).toBeFocused();
        await page.keyboard.press('Enter');
        await expect(painel).toBeVisible({ timeout: 5_000 });
        await expect(busca).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        // The app has already focused the search field. Type through that
        // keyboard focus instead of Playwright's fill() action, which may
        // scroll an offscreen fixed popup into view during activation.
        await page.keyboard.insertText('volei');
        await expect(busca).toHaveValue('volei');
        await expect(busca).toBeFocused();
        await expect(painel).toBeVisible();
        if (process.env.E06_CAPTURE === '1') {
            await page.screenshot({
                path: '/app/test-results/ui-ux-audit-20260913/E06-kvs-landscape-open.png',
                fullPage: false,
            });
            await expect(painel).toBeVisible();
            await expect(busca).toHaveValue('volei');
            await expect(busca).toBeFocused();
        }
        await page.keyboard.press('ArrowDown');
        await expect(busca).toHaveValue('volei');
        await expect(seletor.locator('[role="option"]')).toHaveCount(1);
        const idOpcaoAtiva = await busca.getAttribute('aria-activedescendant');
        expect(idOpcaoAtiva).toBeTruthy();
        const opcaoAtiva = seletor.locator(`#${idOpcaoAtiva}`);
        await expect(opcaoAtiva).toHaveAttribute('role', 'option');
        await expect(opcaoAtiva).toContainText('Vôlei adaptado');
        await page.keyboard.press('Enter');
        await expect(painel).not.toBeVisible({ timeout: 5_000 });
        await expect(trigger).toBeFocused();
        await expect(page.locator('#filtroModalidadeJogosMob')).toHaveValue('11');
        await expect(trigger).toContainText('Vôlei adaptado');

        await page.keyboard.press('Enter');
        await expect(busca).toBeFocused();
        await page.keyboard.press('Escape');
        await expect(painel).not.toBeVisible({ timeout: 5_000 });
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await page.keyboard.press('Enter');
        await expect(busca).toBeFocused();
        await page.keyboard.press('Tab');
        await expect(painel).not.toBeVisible({ timeout: 5_000 });
        await expect(page.locator('#filtroCategoriaJogosMob')).toBeFocused({ timeout: 5_000 });

        // Return through the keyboard before testing an actual page scroll;
        // after scrolling, the focused trigger may be outside the viewport.
        await page.keyboard.press('Shift+Tab');
        await expect(trigger).toBeFocused();
        await page.keyboard.press('Enter');
        await expect(busca).toBeFocused();
        const scrollAoAbrir = await page.evaluate(() => window.scrollY);
        await page.evaluate(() => {
            const maxY = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
            const atual = window.scrollY;
            let destino = atual === 0 ? Math.min(120, maxY) : 0;
            if (destino === atual && maxY > 0) destino = atual < maxY ? maxY : 0;
            const comportamentoAnterior = document.documentElement.style.scrollBehavior;
            document.documentElement.style.scrollBehavior = 'auto';
            window.scrollTo(0, destino);
            document.documentElement.style.scrollBehavior = comportamentoAnterior;
        });
        await expect.poll(() => page.evaluate(() => window.scrollY), { timeout: 5_000 }).not.toBe(scrollAoAbrir);
        await expect(painel).not.toBeVisible({ timeout: 5_000 });
        await expect(trigger).toBeFocused();
    });

    test('cabeçalhos do histórico compacto continuam associados às células', async ({ page }) => {
        await abrirChaveamento(page);

        const haAssociacoesSemanticas = await page.locator('#secaoJogosMob table').evaluate((table) => {
            const cabecalhos = Array.from(table.querySelectorAll('thead th'));
            const linhas = Array.from(table.querySelectorAll('tbody tr'))
                .filter((linha) => linha.querySelectorAll('td').length === cabecalhos.length);
            if (cabecalhos.length !== 8 || linhas.length === 0) return false;

            const cabecalhosVisiveis = cabecalhos.every((th) => {
                const estilo = getComputedStyle(th);
                return th.scope === 'col' && estilo.display !== 'none' && estilo.visibility !== 'hidden';
            });
            if (cabecalhosVisiveis) return true;

            return linhas.every((linha) => Array.from(linha.querySelectorAll('td')).every((celula, indice) => {
                const cabecalho = cabecalhos[indice];
                const referencias = `${celula.getAttribute('headers') || ''} ${celula.getAttribute('aria-labelledby') || ''}`
                    .split(/\s+/)
                    .filter(Boolean);
                if (cabecalho.id && referencias.includes(cabecalho.id)) return true;

                const rotulo = cabecalho.textContent.trim();
                return Array.from(celula.querySelectorAll('*')).some((elemento) =>
                    elemento.textContent.trim() === rotulo && elemento.getAttribute('aria-hidden') !== 'true'
                );
            }));
        });

        expect(haAssociacoesSemanticas).toBe(true);
    });

    test('identifica o vencedor do chaveamento por texto além da cor', async ({ page }) => {
        const tela = await abrirChaveamento(page, 1024);
        await selecionarModalidadeDoChaveamento(page, 'Vôlei adaptado');

        const partida = page.locator(`${tela.bracket} .bkt-match--concluido`);
        await expect(partida).toBeVisible({ timeout: 8_000 });
        const vencedor = partida.locator('.bkt-team--winner');
        await expect(vencedor).toContainText(/vencedor(a)?|campe[aã]o|equipe vencedora/i, { timeout: 5_000 });
    });

    test('mostra ações do chaveamento em tablet sem depender de hover', async ({ page }) => {
        const tela = await abrirChaveamento(page, 1024);
        await selecionarModalidadeDoChaveamento(page, 'Vôlei adaptado');
        await page.mouse.move(0, 0);

        const partida = page.locator(`${tela.bracket} .bkt-match--concluido`);
        await expect(partida).toBeVisible({ timeout: 8_000 });
        const acoes = partida.locator('.bkt-match__actions');
        await expect(acoes).toBeVisible({ timeout: 5_000 });
        await expect(acoes.locator('button').filter({ hasText: 'Editar' })).toBeVisible({ timeout: 5_000 });
        await expect(acoes).toHaveCSS('opacity', '1', { timeout: 5_000 });
    });
});
