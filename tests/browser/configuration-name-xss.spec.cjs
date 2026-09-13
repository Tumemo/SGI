const { test, expect } = require('./fixtures.cjs');

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    const payload = await response.json();
    if (payload && payload.success === false) {
        throw new Error(`${label}: ${payload.message || payload.mensagem || 'resposta recusada'}`);
    }
    return payload;
}

async function criarDadosXss(request) {
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login de preparação XSS');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'edições para regressão XSS'
    );
    const edicao = (Array.isArray(edicoes) ? edicoes : []).find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('A regressão XSS precisa de uma edição ativa.');

    const idInterclasse = Number(edicao.id_interclasse);
    const modalidades = await jsonOrThrow(
        await request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`),
        'modalidades para regressão XSS'
    );
    const modalidadeBase = (Array.isArray(modalidades) ? modalidades : []).find((item) =>
        Number(item.id_tipo_modalidade || item.tipos_modalidades_id_tipo_modalidade) > 0
    );
    if (!modalidadeBase) throw new Error('O fixture precisa de uma modalidade ativa para informar o tipo válido.');

    const suffix = Math.random().toString(36).slice(2, 6);
    const marker = `x${suffix}`;
    const nomes = {
        categoria: `<svg onload="window.${marker}=1"></svg> &"'é`,
        turma: `T${suffix} " onmouseover="window.${marker}=1 & 'é`,
        modalidade: `M${suffix} <>& " onmouseover="window.${marker}=1 'é`,
    };

    const categoria = await jsonOrThrow(await request.post('api/v1/categorias', {
        data: {
            nome_categoria: nomes.categoria,
            status_categoria: '1',
            interclasses_id_interclasse: idInterclasse,
        }
    }), 'criação de categoria sintética');
    const idCategoria = Number(categoria.id_categoria);
    if (idCategoria <= 0) throw new Error('A API não retornou o ID da categoria sintética.');

    let idTurma = 0;
    let idModalidade = 0;
    try {
        const turma = await jsonOrThrow(await request.post('api/v1/turmas', {
            data: {
                nome_turma: nomes.turma,
                turno_turma: 'manha',
                status_turma: '1',
                interclasses_id_interclasse: idInterclasse,
                categorias_id_categoria: idCategoria,
            }
        }), 'criação de turma sintética');
        idTurma = Number(turma.id_turma);
        if (idTurma <= 0) throw new Error('A API não retornou o ID da turma sintética.');

        const modalidade = await jsonOrThrow(await request.post('api/v1/modalidades', {
            data: {
                nome_modalidade: nomes.modalidade,
                genero_modalidade: modalidadeBase.genero_modalidade || 'MASC',
                tipos_modalidades_id_tipo_modalidade: Number(
                    modalidadeBase.id_tipo_modalidade || modalidadeBase.tipos_modalidades_id_tipo_modalidade
                ),
                categorias_id_categoria: idCategoria,
                interclasses_id_interclasse: idInterclasse,
            }
        }), 'criação de modalidade sintética');
        idModalidade = Number(modalidade.id_modalidade || modalidade.id);
        if (idModalidade <= 0) throw new Error('A API não retornou o ID da modalidade sintética.');

        const [categoriasLidas, turmasLidas, modalidadesLidas] = await Promise.all([
            request.get(`api/v1/categorias?id_interclasse=${idInterclasse}`),
            request.get(`api/v1/turmas?id_interclasse=${idInterclasse}`),
            request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`),
        ]);
        const rows = await Promise.all([
            jsonOrThrow(categoriasLidas, 'leitura da categoria sintética'),
            jsonOrThrow(turmasLidas, 'leitura da turma sintética'),
            jsonOrThrow(modalidadesLidas, 'leitura da modalidade sintética'),
        ]);
        expect(rows[0].some((item) => Number(item.id_categoria) === idCategoria && item.nome_categoria === nomes.categoria)).toBe(true);
        expect(rows[1].some((item) => Number(item.id_turma) === idTurma && item.nome_turma === nomes.turma)).toBe(true);
        expect(rows[2].some((item) => Number(item.id_modalidade) === idModalidade && item.nome_modalidade === nomes.modalidade)).toBe(true);

        return { idInterclasse, idCategoria, idTurma, idModalidade, marker, nomes };
    } catch (error) {
        await removerDadosXss(request, { idCategoria, idTurma, idModalidade });
        throw error;
    }
}

async function removerDadosXss(request, fixture) {
    if (fixture.idCategoria > 0) {
        await jsonOrThrow(await request.delete(`api/v1/categorias?id_categoria=${fixture.idCategoria}`), 'desativação da categoria sintética');
    }
}

test('nomes persistidos de categoria, turma e modalidade permanecem texto e dados de atributo', async ({ page, request }) => {
    let fixture = null;
    let idCategoria = 0;

    try {
        fixture = await criarDadosXss(request);
        idCategoria = fixture.idCategoria;

        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill('admin');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/edicoes(?:\?|$|\/)/, { timeout: 15_000 });

        await page.goto(`edicoes/turmas?id=${fixture.idInterclasse}`, { waitUntil: 'domcontentloaded' });
        const botaoCategoria = page.locator('#listaCategorias button').filter({ hasText: fixture.nomes.categoria });
        await expect(botaoCategoria).toHaveCount(1);
        await expect(botaoCategoria).toHaveText(fixture.nomes.categoria);
        await expect(botaoCategoria.locator('svg, [onload]')).toHaveCount(0);
        await expect(botaoCategoria.locator('i.bi-chevron-right')).toHaveCount(1);
        expect(await page.evaluate((marker) => window[marker], fixture.marker)).toBeUndefined();

        await botaoCategoria.click();
        const linkTurma = page.locator('#listaTurmas a').filter({ hasText: fixture.nomes.turma });
        await expect(linkTurma).toHaveCount(1);
        await expect(linkTurma).toHaveText(fixture.nomes.turma);

        await page.goto(
            `edicoes/equipes?id=${fixture.idInterclasse}&id_categoria=${fixture.idCategoria}`,
            { waitUntil: 'domcontentloaded' }
        );
        const filtroCategoria = page.locator(`#filtroCategoria button[data-id="${fixture.idCategoria}"]`);
        await expect(filtroCategoria).toHaveText(fixture.nomes.categoria);

        const card = page.locator(`#listaEquipesDesktop article.aluno-card[data-mod="${fixture.idModalidade}"]`);
        await expect(card).toHaveCount(1);
        await expect(card.locator('.card-header span')).toHaveText(fixture.nomes.modalidade);
        await expect(card).toHaveAttribute('data-mod-nome', fixture.nomes.modalidade);
        await expect(card.locator('[onmouseover]')).toHaveCount(0);

        const botaoEquipes = card.locator('.ver-equipes-btn');
        await expect(botaoEquipes).toHaveAttribute('data-turma-nome', fixture.nomes.turma);
        await expect(botaoEquipes.locator('[onmouseover]')).toHaveCount(0);

        await card.dispatchEvent('mouseover');
        await botaoEquipes.dispatchEvent('mouseover');
        expect(await page.evaluate((marker) => window[marker], fixture.marker)).toBeUndefined();

        await botaoEquipes.click();
        await expect(card.locator('.equipes-view')).toHaveClass(/active/);
        const conteudoEquipes = card.locator('.aluno-equipes-content');
        const nomeEquipe = conteudoEquipes.locator('.aluno-equipe-nome');
        await expect(nomeEquipe).toHaveCount(1);
        await expect(nomeEquipe).toContainText(fixture.nomes.turma);
        await expect(nomeEquipe).toContainText(fixture.nomes.modalidade);
        await expect(nomeEquipe.locator('svg, [onload], [onmouseover]')).toHaveCount(0);
        await expect(conteudoEquipes.locator('[onload], [onmouseover]')).toHaveCount(0);
    } finally {
        await removerDadosXss(request, {
            idCategoria,
            idTurma: fixture?.idTurma || 0,
            idModalidade: fixture?.idModalidade || 0,
        });
    }
});
