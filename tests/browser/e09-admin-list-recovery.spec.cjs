const { test, expect } = require('./fixtures.cjs');

async function jsonOrThrow(response, label) {
    const payload = await response.json().catch(() => null);
    if (!response.ok() || payload == null || payload.success === false) {
        throw new Error(`${label}: HTTP ${response.status()} ${JSON.stringify(payload)}`);
    }
    return payload;
}

async function contextoEdicao(request) {
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' },
    }), 'login de preparação E09');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'edições de preparação E09',
    );
    const edicao = (Array.isArray(edicoes) ? edicoes : [])
        .find((item) => String(item.status_interclasse) === '1')
        || (Array.isArray(edicoes) ? edicoes[0] : null);
    if (!edicao) throw new Error('O fixture E09 precisa conter uma edição.');

    const idEdicao = Number(edicao.id_interclasse);
    const [categoriasResponse, turmasResponse] = await Promise.all([
        request.get(`api/v1/categorias?id_interclasse=${idEdicao}`),
        request.get(`api/v1/turmas?id_interclasse=${idEdicao}`),
    ]);
    const [categorias, turmas] = await Promise.all([
        jsonOrThrow(categoriasResponse, 'categorias de preparação E09'),
        jsonOrThrow(turmasResponse, 'turmas de preparação E09'),
    ]);

    return {
        edicao,
        idEdicao,
        categorias: Array.isArray(categorias) ? categorias : [],
        turmas: Array.isArray(turmas) ? turmas : [],
    };
}

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:[/?#]|$)/, { timeout: 15_000 });
}

function endpointEh(request, endpoint) {
    const url = new URL(request.url());
    return request.method() === 'GET'
        && url.pathname.endsWith(`/api/v1/${endpoint}`);
}

test.describe('E09 — recuperação das listas de administração', () => {
    test('falha ao carregar turmas mantém categoria e busca e permite tentar novamente', async ({ page, request }) => {
        const contexto = await contextoEdicao(request);
        const categoria = contexto.categorias.find((item) => Number(item.id_categoria) > 0);
        if (!categoria) throw new Error('O fixture E09 precisa conter uma categoria.');

        const idCategoria = String(categoria.id_categoria);
        let tentativas = 0;
        const consultas = [];
        await page.route('**/api/v1/turmas**', async (route) => {
            const requisicao = route.request();
            const url = new URL(requisicao.url());
            if (!endpointEh(requisicao, 'turmas')
                || url.searchParams.get('id_categoria') !== idCategoria) {
                await route.continue();
                return;
            }

            consultas.push({
                idCategoria: url.searchParams.get('id_categoria'),
                idEdicao: url.searchParams.get('id_interclasse'),
            });
            tentativas += 1;
            if (tentativas === 1) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária no serviço.' }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([
                    { id_turma: 98101, nome_turma: 'Turma E09 recuperação alvo' },
                    { id_turma: 98102, nome_turma: 'Turma E09 fora da busca' },
                ]),
            });
        });

        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`edicoes/turmas?id=${contexto.idEdicao}&id_categoria=${idCategoria}`, {
            waitUntil: 'domcontentloaded',
        });

        const lista = page.locator('#listaTurmasMobile');
        await expect(lista).toContainText(/não foi possível carregar as turmas/i);
        await expect(lista.locator('[role="alert"][aria-live="assertive"]')).toBeVisible();
        await expect(lista).not.toContainText(/nenhuma turma adicionada|nenhuma turma corresponde à busca/i);
        await expect(page.locator('#categoriaTurmasMobile')).toHaveValue(idCategoria);

        const busca = page.locator('#inputBuscaTurmaMobile');
        await busca.fill('recuperação alvo');
        await expect(busca).toHaveValue('recuperação alvo');
        await expect(lista).toContainText(/não foi possível carregar as turmas/i);

        const tentarNovamente = page.getByRole('button', { name: /tentar novamente/i });
        await expect(tentarNovamente).toBeVisible();
        await tentarNovamente.click();

        await expect(lista).toContainText('Turma E09 recuperação alvo');
        await expect(lista).not.toContainText('Turma E09 fora da busca');
        await expect(page.locator('#categoriaTurmasMobile')).toHaveValue(idCategoria);
        await expect(busca).toHaveValue('recuperação alvo');
        expect(consultas).toEqual([
            { idCategoria, idEdicao: String(contexto.idEdicao) },
            { idCategoria, idEdicao: String(contexto.idEdicao) },
        ]);
    });

    test('falha ao carregar categorias anuncia erro e permite recuperar a seleção', async ({ page, request }) => {
        const contexto = await contextoEdicao(request);
        const categoria = contexto.categorias.find((item) => Number(item.id_categoria) > 0);
        if (!categoria) throw new Error('O fixture E09 precisa conter uma categoria.');

        let tentativas = 0;
        await page.route('**/api/v1/categorias**', async (route) => {
            const requisicao = route.request();
            const url = new URL(requisicao.url());
            if (!endpointEh(requisicao, 'categorias')
                || url.searchParams.get('id_interclasse') !== String(contexto.idEdicao)) {
                await route.continue();
                return;
            }

            tentativas += 1;
            if (tentativas === 1) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária no serviço.' }),
                });
                return;
            }
            await route.continue();
        });

        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`edicoes/turmas?id=${contexto.idEdicao}`, { waitUntil: 'domcontentloaded' });

        const categorias = page.locator('#listaCategorias');
        const erro = categorias.locator('[role="alert"][aria-live="assertive"]');
        await expect(erro).toContainText(/não foi possível carregar as categorias/i);
        await expect(page.locator('#listaTurmas [role="alert"][aria-live="assertive"]')).toContainText(/não foi possível carregar as categorias/i);

        await categorias.locator('[data-sgi-retry="categorias"]').click();
        await expect(categorias.getByRole('button', { name: categoria.nome_categoria, exact: true })).toBeVisible();
        await expect(categorias.locator('[role="alert"]')).toHaveCount(0);
        expect(tentativas).toBe(2);
    });

    test('falha em categorias do resumo fica localizada e é recuperável sem apagar edição e pontuação', async ({ page, request }) => {
        const contexto = await contextoEdicao(request);
        let tentativasCategorias = 0;
        await page.route('**/api/v1/categorias**', async (route) => {
            const requisicao = route.request();
            const url = new URL(requisicao.url());
            if (!endpointEh(requisicao, 'categorias')
                || url.searchParams.get('id_interclasse') !== String(contexto.idEdicao)) {
                await route.continue();
                return;
            }

            tentativasCategorias += 1;
            if (tentativasCategorias === 1) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária no serviço.' }),
                });
                return;
            }
            await route.continue();
        });

        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(`edicoes/resumo?id=${contexto.idEdicao}&modo=view`, {
            waitUntil: 'domcontentloaded',
        });

        const resumoCategorias = page.locator('#resumoCategoriasDesktop');
        const resumoTurmas = page.locator('#resumoTurmasDesktop');
        const resumoModalidades = page.locator('#resumoModalidadesDesktop');
        const resumoPontuacao = page.locator('#resumoRegulamentosDesktop');
        const esperadoTurmas = contexto.turmas.length
            ? `${contexto.turmas.length} turma(s) cadastrada(s)`
            : '(Nenhuma turma cadastrada)';
        const esperadoPontuacao = `1º: ${Number(contexto.edicao.ponto_1_lugar)} · 2º: ${Number(contexto.edicao.ponto_2_lugar)} · 3º: ${Number(contexto.edicao.ponto_3_lugar)} · arrecadação/kg: ${Number(contexto.edicao.valor_item_arrecadacao)}`;

        await expect(page.locator('#nomeInterclasseResumo')).toHaveText(contexto.edicao.nome_interclasse);
        await expect(resumoPontuacao).toHaveText(esperadoPontuacao);
        await expect(resumoCategorias).toContainText(/erro ao carregar categorias/i);
        await expect(resumoCategorias).not.toContainText(/nenhuma categoria cadastrada/i);
        await expect(resumoTurmas).toHaveText(esperadoTurmas);
        await expect(resumoModalidades).not.toContainText(/erro ao carregar/i);

        const tentarNovamente = page.getByRole('button', { name: /tentar novamente/i });
        await expect(tentarNovamente).toBeVisible();
        await tentarNovamente.click();

        await expect(resumoCategorias).toHaveText(`${contexto.categorias.length} categoria(s) cadastrada(s)`);
        await expect(page.locator('#nomeInterclasseResumo')).toHaveText(contexto.edicao.nome_interclasse);
        await expect(resumoPontuacao).toHaveText(esperadoPontuacao);
        expect(tentativasCategorias).toBe(2);
    });

    test('falha no endpoint de locais mostra erro em vez de vazio e permite tentar novamente', async ({ page, request }) => {
        const contexto = await contextoEdicao(request);
        let tentativas = 0;
        const edicoesConsultadas = [];
        await page.route('**/api/v1/locais**', async (route) => {
            const requisicao = route.request();
            const url = new URL(requisicao.url());
            if (!endpointEh(requisicao, 'locais')) {
                await route.continue();
                return;
            }

            edicoesConsultadas.push(url.searchParams.get('id_interclasse'));
            tentativas += 1;
            if (tentativas === 1) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária no serviço.' }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: true,
                    data: [{
                        id_local: 98103,
                        nome_local: 'Local E09 recuperado',
                        disponivel_local: 1,
                        carga_local: 80,
                    }],
                }),
            });
        });

        await entrarComoAdmin(page);
        await page.goto(`edicoes/locais?id=${contexto.idEdicao}`, { waitUntil: 'domcontentloaded' });

        const lista = page.locator('#listaLocaisDesktop');
        await expect(lista).toContainText(/erro ao carregar locais|não foi possível carregar os locais/i);
        await expect(lista.locator('[role="alert"][aria-live="assertive"]')).toContainText(/não foi possível carregar os locais/i);
        await expect(lista).not.toContainText(/nenhum local cadastrado/i);

        const tentarNovamente = page.getByRole('button', { name: /tentar novamente/i });
        await expect(tentarNovamente).toBeVisible();
        await tentarNovamente.click();

        await expect(lista).toContainText('Local E09 recuperado');
        expect(edicoesConsultadas).toEqual([String(contexto.idEdicao), String(contexto.idEdicao)]);
    });
});
