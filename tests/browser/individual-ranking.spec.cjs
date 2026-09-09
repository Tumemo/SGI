const { test, expect } = require('./fixtures.cjs');

test('placar de modalidade individual não cai no layout de mata-mata', async ({ page }) => {
    test.skip(process.env.SGI_INDIVIDUAL_BROWSER !== '1', 'Ative SGI_INDIVIDUAL_BROWSER=1 para executar o cenário com servidor e banco de homologação.');

    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');

    await page.route('**/api/v1/**', async (route) => {
        const url = new URL(route.request().url());
        const path = url.pathname;
        if (path.endsWith('/jogos') && url.searchParams.get('id_jogo') === '20') {
            return route.fulfill({ json: [{
                id_jogo: 20,
                nome_jogo: 'MM:2:0:N',
                nome_modalidade: 'Corrida',
                nome_tipo_modalidade: 'Individual',
                tipo_competicao: 'individual',
                tipos_modalidades_id_tipo_modalidade: 37,
                modalidades_id_modalidade: 10,
                status_jogo: 'Agendado',
                data_jogo: '2026-09-10',
                inicio_jogo: '08:00:00',
                termino_jogo: '09:00:00',
            }] });
        }
        if (path.endsWith('/partidas') && url.searchParams.get('id_jogo') === '20') {
            return route.fulfill({ json: [] });
        }
        if (path.endsWith('/chaveamentos') && url.searchParams.get('acao') === 'participantes') {
            return route.fulfill({ json: { success: true, participantes: [
                { id_usuario: 101, nome_usuario: 'Atleta A', nome_turma: '3º Médio' },
                { id_usuario: 102, nome_usuario: 'Atleta B', nome_turma: '3º Médio' },
                { id_usuario: 103, nome_usuario: 'Atleta C', nome_turma: '3º Médio' },
            ] } });
        }
        if (path.endsWith('/chaveamentos') && url.searchParams.get('acao') === 'ranking') {
            return route.fulfill({ json: { success: true, ranking: [], jogo: null } });
        }
        return route.fulfill({ json: [] });
    });

    await page.goto('jogos/placar?id_jogo=20', { waitUntil: 'commit' });
    await expect(page.locator('#placar-conteudo')).toBeVisible();
    await expect(page.locator('#placar-titulo-jogo')).toHaveText('Corrida');
    await expect(page.locator('#indSelectPrimeiro')).toBeVisible();
    await expect(page.locator('#btnIniciarProvaIndividual')).toBeVisible();
    await expect(page.locator('.btn-score-plus')).toHaveCount(0);
    await expect(page.locator('.mc-vs')).toHaveCount(0);
});
