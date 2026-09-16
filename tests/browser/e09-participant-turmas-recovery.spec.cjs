const { test, expect } = require('./fixtures.cjs');

async function jsonOrThrow(response, label) {
    const payload = await response.json().catch(() => null);
    if (!response.ok() || payload == null || payload.success === false) {
        throw new Error(`${label}: HTTP ${response.status()} ${JSON.stringify(payload)}`);
    }
    return payload;
}

async function prepararEdicao(request) {
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
    return Number(edicao.id_interclasse);
}

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:[/?#]|$)/, { timeout: 15_000 });
}

function endpointEh(request, endpoint, idInterclasse) {
    const url = new URL(request.url());
    return request.method() === 'GET'
        && url.pathname.endsWith(`/api/v1/${endpoint}`)
        && url.searchParams.get('id_interclasse') === String(idInterclasse);
}

test.describe('E09 — recuperação da lista geral de turmas', () => {
    test('HTTP 500 não vira vazio; retry recupera mantendo a busca', async ({ page, request }) => {
        const idInterclasse = await prepararEdicao(request);
        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 390, height: 844 });

        let tentativas = 0;
        let recuperacaoPermitida = false;
        await page.route('**/api/v1/turmas**', async (route) => {
            if (!endpointEh(route.request(), 'turmas', idInterclasse)) {
                await route.continue();
                return;
            }

            tentativas += 1;
            if (!recuperacaoPermitida) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'Falha temporária.' }),
                });
                return;
            }

            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([
                    {
                        id_turma: 98131,
                        nome_turma: 'Turma E09 busca alvo',
                        nome_fantasia_turma: 'Alvo',
                        nome_categoria: 'Categoria E09',
                        turno_turma: 'manha',
                        categorias_id_categoria: 98130,
                    },
                    {
                        id_turma: 98132,
                        nome_turma: 'Turma E09 fora da busca',
                        nome_fantasia_turma: 'Outra',
                        nome_categoria: 'Categoria E09',
                        turno_turma: 'tarde',
                        categorias_id_categoria: 98130,
                    },
                ]),
            });
        });

        await page.goto(`turmas?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
        const lista = page.locator('#listaTurmasMobile');
        await expect(lista).toContainText(/não foi possível carregar as turmas/i);
        await expect(lista).not.toContainText(/nenhuma turma cadastrada/i);
        await expect(lista).not.toContainText(/criar turma/i);

        const busca = page.locator('#buscaTurmaMob');
        await busca.fill('alvo');
        await expect(busca).toHaveValue('alvo');
        await expect(lista).toContainText(/não foi possível carregar as turmas/i);
        await expect(lista).not.toContainText(/nenhum resultado/i);

        recuperacaoPermitida = true;
        await page.getByRole('button', { name: /tentar novamente/i }).click();
        await expect(lista).toContainText('Turma E09 busca alvo');
        await expect(lista).not.toContainText('Turma E09 fora da busca');
        await expect(busca).toHaveValue('alvo');
        expect(tentativas).toBe(2);
    });

    test('resposta JSON vazia válida continua sendo um estado sem turmas', async ({ page, request }) => {
        const idInterclasse = await prepararEdicao(request);
        await entrarComoAdmin(page);
        await page.setViewportSize({ width: 390, height: 844 });

        await page.route('**/api/v1/turmas**', async (route) => {
            if (!endpointEh(route.request(), 'turmas', idInterclasse)) {
                await route.continue();
                return;
            }
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: '[]',
            });
        });

        await page.goto(`turmas?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
        const lista = page.locator('#listaTurmasMobile');
        await expect(lista).toContainText('Nenhuma turma cadastrada neste interclasse ainda.');
        await expect(lista).toContainText('Criar Turma');
        await expect(lista).not.toContainText(/não foi possível carregar|tentar novamente/i);
    });
});
