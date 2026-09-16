const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes/, { timeout: 15_000 });
}

async function idEdicao(page) {
    return page.evaluate(async () => {
        const resposta = await fetch('/api/v1/edicoes?regulamento=true');
        if (!resposta.ok) throw new Error(`Consulta de edições: HTTP ${resposta.status}`);
        const edicoes = await resposta.json();
        const edicao = (Array.isArray(edicoes) ? edicoes : []).find((item) => String(item.status_interclasse) === '1')
            || (Array.isArray(edicoes) ? edicoes[0] : null);
        if (!edicao) throw new Error('O fixture não contém uma edição para abrir a gestão de turmas.');
        return Number(edicao.id_interclasse);
    });
}

async function criarCategoriasTemporarias(request, idInterclasse) {
    const login = await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' },
    });
    expect(login.ok(), 'a preparação das categorias precisa de uma sessão administrativa').toBeTruthy();

    const marcador = `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
    const categorias = [];
    try {
        for (const sufixo of ['A', 'B']) {
            const nome = `Categoria E05 ${sufixo} ${marcador}`;
            const resposta = await request.post('api/v1/categorias', {
                data: {
                    nome_categoria: nome,
                    status_categoria: '1',
                    interclasses_id_interclasse: idInterclasse,
                },
            });
            expect(resposta.ok(), `a categoria temporária ${sufixo} precisa ser criada`).toBeTruthy();
            const payload = await resposta.json();
            expect(payload.success).toBe(true);
            expect(Number(payload.id_categoria)).toBeGreaterThan(0);
            categorias.push({ id: Number(payload.id_categoria), nome });
        }
    } catch (error) {
        await removerCategoriasTemporarias(request, categorias);
        throw error;
    }

    return categorias;
}

async function removerCategoriasTemporarias(request, categorias) {
    for (const categoria of categorias) {
        const resposta = await request.delete(`api/v1/categorias?id_categoria=${categoria.id}`);
        expect(resposta.ok(), `a categoria temporária ${categoria.id} precisa ser removida`).toBeTruthy();
    }
}

async function simularTurmasERegistrarCriacao(page, idInterclasse, categorias) {
    const nomes = new Map([
        [categorias[0].id, ['9º Ano A', '11º Ano C']],
        [categorias[1].id, ['7º Ano D', '10º Ano B']],
    ]);
    const consultas = [];
    let turmaCriada = null;

    await page.route('**/api/v1/turmas**', async (route) => {
        const requisicao = route.request();
        const url = new URL(requisicao.url());
        if (requisicao.method() === 'GET') {
            consultas.push({
                idInterclasse: Number(url.searchParams.get('id_interclasse')),
                idCategoria: Number(url.searchParams.get('id_categoria')),
            });
            const turmas = (nomes.get(Number(url.searchParams.get('id_categoria'))) || []).map((nome, index) => ({
                id_turma: 9800 + index,
                nome_turma: nome,
                nome_fantasia_turma: '',
            }));
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(turmas),
            });
            return;
        }

        if (requisicao.method() === 'POST') {
            turmaCriada = requisicao.postDataJSON();
            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, id_turma: 9899 }),
            });
            return;
        }

        await route.continue();
    });

    return {
        consultas,
        turmaCriada: () => turmaCriada,
        idInterclasse,
    };
}

async function esperarSeletorCategoriaCompacto(page) {
    // Keep a stable DOM locator across viewport changes: the native selector
    // is intentionally hidden at the desktop breakpoint.
    const seletor = page.locator('#categoriaTurmasMobile');
    await expect(seletor).toBeVisible();
    return seletor;
}

async function esperarInstrucaoDeCategoria(page) {
    await expect(page.locator('#instrucaoCategoriaTurmasMobile'))
        .toContainText(/selecione uma categoria|escolha uma categoria/i);
}

async function adicionarTurmaNoContexto(page, contexto, categoria, nome) {
    const abrirModal = page.getByRole('button', { name: 'Adicionar turma', exact: true });
    await expect(abrirModal).toBeVisible();
    await abrirModal.click();

    const dialog = page.getByRole('dialog', { name: 'ADICIONAR TURMA' });
    await expect(dialog).toBeVisible();
    await page.locator('#inputNomeTurma').fill(nome);
    await page.locator('#btnSalvarTurma').click();
    await expect.poll(() => contexto.turmaCriada()).not.toBeNull();
    expect(contexto.turmaCriada()).toMatchObject({
        interclasses_id_interclasse: contexto.idInterclasse,
        categorias_id_categoria: categoria.id,
        nome_turma: nome,
    });
}

async function aguardarEventoDoModal(page, evento) {
    return page.locator('#modalCriarTurma').evaluate((element, nomeEvento) => new Promise((resolve) => {
        element.addEventListener(nomeEvento, resolve, { once: true });
    }), evento);
}

test('E03 dá nome à gestão de turmas nos dois breakpoints e aos campos do modal', async ({ page }) => {
    await entrarComoAdmin(page);
    const edicao = await idEdicao(page);
    await page.goto(`edicoes/turmas?id=${edicao}`, { waitUntil: 'domcontentloaded' });

    await page.setViewportSize({ width: 390, height: 844 });
    const tituloMobile = page.getByRole('heading', { level: 1, name: 'Turmas' });
    await expect(tituloMobile).toHaveCount(1);
    await expect(tituloMobile).toBeVisible();
    await expect(page.getByRole('searchbox', { name: 'Buscar turma' })).toBeVisible();

    const dialog = page.getByRole('dialog', { name: 'ADICIONAR TURMA' });
    const mostradoMobile = aguardarEventoDoModal(page, 'shown.bs.modal');
    await page.locator('#modalCriarTurma').evaluate((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });
    await mostradoMobile;
    await expect(dialog).toBeVisible();
    const ocultadoMobile = aguardarEventoDoModal(page, 'hidden.bs.modal');
    await page.keyboard.press('Escape');
    await ocultadoMobile;
    await expect(dialog).toBeHidden();

    await page.setViewportSize({ width: 1440, height: 900 });
    const tituloDesktop = page.getByRole('heading', { level: 1, name: 'Turmas' });
    await expect(tituloDesktop).toHaveCount(1);
    await expect(tituloDesktop).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Buscar turma' })).toBeVisible();

    const categoriaInicial = page.locator('#listaCategorias button').first();
    await expect(categoriaInicial).toBeVisible();
    await categoriaInicial.click();
    const acionar = page.locator('#btnAdicionarTurmaDesktop');
    await expect(acionar).toBeEnabled();
    const mostradoDesktop = aguardarEventoDoModal(page, 'shown.bs.modal');
    await acionar.focus();
    await page.keyboard.press('Enter');
    await mostradoDesktop;
    await expect(dialog).toBeVisible();
    for (const nome of ['Nome da turma:', 'Nome fantasia:', 'Turno:']) {
        await expect(dialog.getByLabel(nome)).toBeVisible();
    }

    const ocultadoDesktop = aguardarEventoDoModal(page, 'hidden.bs.modal');
    await page.keyboard.press('Escape');
    await ocultadoDesktop;
    await expect(dialog).toBeHidden();
    await expect(acionar).toBeFocused();
});

test('E03 mantém um único H1 visível nas páginas administrativas responsivas', async ({ page }) => {
    await entrarComoAdmin(page);
    const edicao = await idEdicao(page);
    const paginas = [
        'painel',
        'edicoes/resumo',
        'turmas',
        'modalidades',
        'jogos',
        'edicoes/agenda',
    ];

    for (const rota of paginas) {
        await page.goto(`${rota}?id=${edicao}`, { waitUntil: 'domcontentloaded' });
        for (const width of [390, 1440]) {
            await page.setViewportSize({ width, height: width === 390 ? 844 : 900 });
            const titulos = page.getByRole('heading', { level: 1 });
            await expect(titulos, `${rota} em ${width}px deve expor um único H1`).toHaveCount(1);
            await expect(titulos).toBeVisible();
        }
    }
});

test('E05: em compacto, informa que falta categoria e permite escolher antes de adicionar turma', async ({ page, request }) => {
    await entrarComoAdmin(page);
    const edicao = await idEdicao(page);
    const categorias = await criarCategoriasTemporarias(request, edicao);
    try {
        const contexto = await simularTurmasERegistrarCriacao(page, edicao, categorias);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`edicoes/turmas?id=${edicao}`, { waitUntil: 'domcontentloaded' });

        const seletor = await esperarSeletorCategoriaCompacto(page);
        await esperarInstrucaoDeCategoria(page);
        await expect(seletor).toHaveValue('');
        await seletor.selectOption(String(categorias[0].id));
        await expect.poll(() => contexto.consultas.some((consulta) =>
            consulta.idInterclasse === edicao && consulta.idCategoria === categorias[0].id
        )).toBe(true);

        await adicionarTurmaNoContexto(page, contexto, categorias[0], `Turma E05 ${Date.now()}`);
    } finally {
        await removerCategoriasTemporarias(request, categorias);
    }
});

test('E05: id_categoria inválido não escolhe categoria arbitrária e permite adicionar no contexto escolhido', async ({ page, request }) => {
    await entrarComoAdmin(page);
    const edicao = await idEdicao(page);
    const categorias = await criarCategoriasTemporarias(request, edicao);
    try {
        const contexto = await simularTurmasERegistrarCriacao(page, edicao, categorias);
        const idInvalido = Math.max(...categorias.map((categoria) => categoria.id)) + 10_000;
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`edicoes/turmas?id=${edicao}&id_categoria=${idInvalido}`, { waitUntil: 'domcontentloaded' });

        const seletor = await esperarSeletorCategoriaCompacto(page);
        await esperarInstrucaoDeCategoria(page);
        await expect(seletor).toHaveValue('');
        expect(contexto.consultas.some((consulta) => consulta.idCategoria === idInvalido)).toBe(false);

        await seletor.selectOption(String(categorias[1].id));
        await expect.poll(() => contexto.consultas.some((consulta) =>
            consulta.idInterclasse === edicao && consulta.idCategoria === categorias[1].id
        )).toBe(true);
        await adicionarTurmaNoContexto(page, contexto, categorias[1], `Turma E05 inválida ${Date.now()}`);
    } finally {
        await removerCategoriasTemporarias(request, categorias);
    }
});

test('E05: preseleciona categoria válida e sincroniza seleção e busca entre compacto e desktop', async ({ page, request }) => {
    await entrarComoAdmin(page);
    const edicao = await idEdicao(page);
    const categorias = await criarCategoriasTemporarias(request, edicao);
    try {
        const contexto = await simularTurmasERegistrarCriacao(page, edicao, categorias);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(
            `edicoes/turmas?id=${edicao}&id_categoria=${categorias[0].id}`,
            { waitUntil: 'domcontentloaded' }
        );

        const seletor = await esperarSeletorCategoriaCompacto(page);
        await expect(seletor).toHaveValue(String(categorias[0].id));
        const buscaMobile = page.locator('#inputBuscaTurmaMobile');
        const buscaDesktop = page.locator('#inputBuscaTurma');
        await buscaMobile.fill('9º');
        await expect(buscaDesktop).toHaveValue('9º');
        await expect(page.locator('#listaTurmasMobile')).toContainText('9º Ano A');
        await expect(page.locator('#listaTurmasMobile')).not.toContainText('11º Ano C');

        await seletor.selectOption(String(categorias[1].id));
        await expect.poll(() => contexto.consultas.some((consulta) =>
            consulta.idInterclasse === edicao && consulta.idCategoria === categorias[1].id
        )).toBe(true);
        await expect(buscaDesktop).toHaveValue('9º');
        await expect(page.locator('#listaTurmasMobile')).not.toContainText('9º Ano A');
        await expect(page.locator('#listaTurmasMobile')).not.toContainText('11º Ano C');

        await buscaMobile.fill('');
        await expect(buscaDesktop).toHaveValue('');
        await expect(page.locator('#listaTurmasMobile')).toContainText('7º Ano D');
        await expect(page.locator('#listaTurmasMobile')).toContainText('10º Ano B');

        await page.setViewportSize({ width: 1440, height: 900 });
        const categoriaDesktop = page.getByRole('button', { name: categorias[1].nome, exact: true });
        await expect(categoriaDesktop).toBeVisible();
        await expect.poll(() => categoriaDesktop.evaluate((element) =>
            element.getAttribute('aria-pressed') === 'true'
            || element.getAttribute('aria-current') === 'true'
            || element.classList.contains('bg-light')
        )).toBe(true);
        await expect(buscaDesktop).toHaveValue('');
        await expect(page.locator('#listaTurmas')).not.toContainText('9º Ano A');
        await expect(page.locator('#listaTurmas')).toContainText('7º Ano D');
        await expect(page.locator('#listaTurmas')).toContainText('10º Ano B');

        const categoriaA = page.getByRole('button', { name: categorias[0].nome, exact: true });
        await categoriaA.click();
        await expect(seletor).toHaveValue(String(categorias[0].id));
        await expect.poll(() => contexto.consultas.some((consulta) =>
            consulta.idInterclasse === edicao && consulta.idCategoria === categorias[0].id
        )).toBe(true);
        await expect(page.locator('#listaTurmas')).toContainText('9º Ano A');
        await expect(page.locator('#listaTurmas')).toContainText('11º Ano C');
        await expect(page.locator('#listaTurmas')).not.toContainText('7º Ano D');

        await buscaDesktop.fill('11º');
        await expect(buscaMobile).toHaveValue('11º');
        await expect(page.locator('#listaTurmas')).toContainText('11º Ano C');
        await expect(page.locator('#listaTurmas')).not.toContainText('9º Ano A');
        await adicionarTurmaNoContexto(page, contexto, categorias[0], `Turma E05 sincronizada ${Date.now()}`);
    } finally {
        await removerCategoriasTemporarias(request, categorias);
    }
});
