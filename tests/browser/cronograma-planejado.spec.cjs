const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:\?|$)/, { timeout: 20_000 });
    return page.evaluate(async () => {
        const response = await fetch('/api/v1/edicoes?regulamento=true');
        if (!response.ok) throw new Error(`Consulta da edição ativa: HTTP ${response.status}`);
        const edicoes = await response.json();
        const edicao = (Array.isArray(edicoes) ? edicoes : [])
            .find((item) => String(item.status_interclasse) === '1');
        if (!edicao) throw new Error('O fixture autenticado não contém edição ativa.');
        return Number(edicao.id_interclasse);
    });
}

test('cronograma planejado exibe a revisão e preserva a ação administrativa', async ({ page }) => {
    const idInterclasse = await entrarComoAdmin(page);
    let revisoes = 0;
    let versao = 3;
    await page.route('**/api/v1/modalidades*', async (route) => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify([{
            id_modalidade: 91001,
            interclasses_id_interclasse: idInterclasse,
            nome_modalidade: 'Futsal planejado fixture',
            nome_categoria: 'Sub-15',
            nome_tipo_modalidade: 'Mata-Mata',
            tipo_competicao: 'mata_mata',
        }]),
    }));
    await page.route('**/api/v1/locais*', async (route) => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({ data: [{ id_local: 91001, nome_local: 'Quadra fixture', disponivel_local: '1', status_local: '1' }] }),
    }));
    await page.route('**/api/v1/jogos*', async (route) => route.fulfill({ contentType: 'application/json', body: '[]' }));
    await page.route('**/api/v1/cronograma*', async (route) => {
        if (route.request().method() === 'POST') {
            revisoes += 1;
            versao = 4;
        }
        await route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                success: true,
                cronograma_status: revisoes ? 'revisao' : 'publicado',
                inscricoes_status: revisoes ? 'fechadas' : 'abertas',
                cronograma_versao: versao,
                modalidades: [{ id_modalidade: 91001, equipes_planejadas: 3 }],
                compromissos: [{ id_modalidade: 91001, chave_tag: 'PL:1:MM:2:0:N' }],
                nos: [{ id_modalidade: 91001, chave_tag: 'PL:1:MM:2:0:N', tipo_no: 'normal' }],
                equipes_incompletas: [],
                operacao: { requer_repreparo_mesario: Boolean(revisoes), fila_offline_preservada: true },
            }),
        });
    });

    await page.goto(`edicoes/agenda?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#painelCronogramaPlanejado')).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('#cronogramaPlanejadoStatus')).toContainText('publicado');
    await expect(page.locator('#cronogramaRevisar')).toBeEnabled();
    await page.locator('#cronogramaRevisar').click();
    await expect.poll(() => revisoes).toBe(1);
    await expect(page.locator('#cronogramaPlanejadoStatus')).toContainText('revisao');
    await expect(page.locator('#cronogramaPlanejadoResumo')).toContainText('precisam de resolução');
});
