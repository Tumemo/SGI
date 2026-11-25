const { test, expect } = require('./fixtures.cjs');

test('relatório final mantém a edição selecionada, respeita a artilharia e exporta PDF', async ({ page }) => {
    await page.route('**/api/v1/edicoes?regulamento=true', async (route) => route.fulfill({ json: [{
        id_interclasse: 77,
        nome_interclasse: 'Interclasse de Demonstração',
        ano_interclasse: '2026-01-01 00:00:00',
        status_interclasse: '1',
    }] }));

    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes/, { waitUntil: 'domcontentloaded' });

    await page.goto('painel?id=77', { waitUntil: 'domcontentloaded' });
    const dashboardReportsLink = page.locator('#linkRelatorios');
    await expect(dashboardReportsLink).toBeVisible();
    await expect(dashboardReportsLink).toHaveAttribute('href', /relatorios\?id=77/);

    await page.route('**/api/v1/relatorio-final?id_interclasse=77', async (route) => route.fulfill({ json: {
        success: true,
        relatorio: {
            edicao: { id_interclasse: 77, nome_interclasse: 'Interclasse de Demonstração', ano: '2026', valor_item_arrecadacao: 2 },
            metricas: {
                turmas_total: 1,
                categorias_total: 1,
                turmas_por_categoria: [{ nome_categoria: 'Categoria A', total_turmas: 1 }],
                doacoes_total_kg: 3,
                lider_doacoes: { nome_turma: 'Turma 1A', quantidade_kg: 3 },
                modalidades_total: 2,
                lider_absoluta: { nome_turma: 'Turma 1A', pontuacao_liquida: 12 },
            },
            ranking_geral: [{ id_turma: 1, nome_turma: 'Turma 1A', nome_categoria: 'Categoria A', posicao: 1, pontuacao_liquida: 12, doacoes_kg: 3, penalidades_pontos: 0, penalidades: [], nome_fantasia_turma: '' }],
            categorias: [{ id_categoria: 1, nome_categoria: 'Categoria A', total_turmas: 1, pontuacao_total: 12, ranking: [{ id_turma: 1, nome_turma: 'Turma 1A', nome_categoria: 'Categoria A', posicao: 1, pontuacao_liquida: 12, doacoes_kg: 3, penalidades_pontos: 0, penalidades: [], nome_fantasia_turma: '' }] }],
            modalidades: [
                { id_modalidade: 10, nome_modalidade: 'Futsal', nome_categoria: 'Categoria A', nome_tipo_modalidade: 'Coletiva', genero_modalidade: 'MISTO', status_modalidade: '1', contabiliza_artilharia: true, podios: [], artilheiros: [{ id_usuario: 1, nome_usuario: 'Atleta Habilitada', nome_turma: 'Turma 1A', gols: 2, posicao: 1 }] },
                { id_modalidade: 11, nome_modalidade: 'Queimada', nome_categoria: 'Categoria A', nome_tipo_modalidade: 'Coletiva', genero_modalidade: 'MISTO', status_modalidade: '1', contabiliza_artilharia: false, podios: [], artilheiros: [{ nome_usuario: 'Atleta da Queimada', nome_turma: 'Turma 1A', gols: 9, posicao: 1 }] },
            ],
            doacoes: { total_kg: 3, por_turma: [{ id_turma: 1, nome_turma: 'Turma 1A', nome_categoria: 'Categoria A', quantidade_kg: 3, pontuacao: 6 }] },
            observacao: 'Resumo consolidado da edição.',
        },
    } }));

    await page.route('**/api/v1/relatorio-final/pdf?id_interclasse=77', async (route) => route.fulfill({
        status: 200,
        contentType: 'application/pdf',
        headers: { 'Content-Disposition': 'attachment; filename="relatorio-interclasse-77.pdf"' },
        body: Buffer.from('%PDF-1.4\n%%EOF'),
    }));

    await page.goto('relatorios?id=77', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#sgiRelatorioEdicao')).toHaveText('Interclasse de Demonstração · 2026');
    await expect(page.getByRole('heading', { name: 'Ranking de Artilheiros' })).toBeVisible();
    await expect(page.locator('#sgiRelatorioArtilheiros')).toContainText('Atleta Habilitada');
    await expect(page.locator('#sgiRelatorioArtilheiros')).not.toContainText('Atleta da Queimada');
    await expect(page.locator('#sgiRelatorioModalidades')).toContainText('Queimada');

    const downloadPromise = page.waitForEvent('download');
    await page.locator('#btnGerarRelatorio').click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toBe('relatorio-interclasse-77.pdf');
});
