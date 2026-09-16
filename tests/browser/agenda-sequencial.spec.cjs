const { test, expect } = require('./fixtures.cjs');

async function fulfillJson(route, payload, status = 200) {
    await route.fulfill({
        status,
        contentType: 'application/json; charset=utf-8',
        body: JSON.stringify(payload),
    });
}

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    const form = page.locator('#form_desktop');
    await form.locator('.ipt-matricula').fill('admin');
    await form.locator('.ipt-senha').fill('123');
    await form.locator('button[type="submit"]').click();
    await page.waitForURL(/\/(?:edicoes|painel)(?:\?|$)/, { timeout: 20_000 });

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

async function prepararTela(page, respostaPost) {
    const idInterclasse = await entrarComoAdmin(page);
    const idModalidade = 987654321;
    const idModalidadeIndividual = 987654322;
    let payloadPost = null;
    let postCount = 0;

    await page.route('**/api/v1/modalidades*', async (route) => {
        await fulfillJson(route, [{
            id_modalidade: idModalidade,
            interclasses_id_interclasse: idInterclasse,
            nome_modalidade: 'Futsal fixture',
            nome_categoria: 'Sub-15',
            nome_tipo_modalidade: 'Mata-Mata',
            tipo_competicao: 'mata_mata',
        }, {
            id_modalidade: idModalidadeIndividual,
            interclasses_id_interclasse: idInterclasse,
            nome_modalidade: 'Atletismo fixture',
            nome_categoria: 'Sub-15',
            nome_tipo_modalidade: 'Individual',
            tipo_competicao: 'individual',
        }]);
    });
    await page.route('**/api/v1/locais*', async (route) => {
        await fulfillJson(route, {
            success: true,
            data: [{
                id_local: 77,
                nome_local: 'Quadra fixture',
                disponivel_local: '1',
                status_local: '1',
            }, {
                id_local: 78,
                nome_local: 'Quadra inativa fixture',
                disponivel_local: '1',
                status_local: '0',
            }],
        });
    });
    await page.route('**/api/v1/jogos*', async (route) => {
        const url = new URL(route.request().url());
        if (url.searchParams.get('id_modalidade') !== String(idModalidade)) {
            await fulfillJson(route, []);
            return;
        }
        await fulfillJson(route, [{
            id_jogo: 4001,
            nome_jogo: 'MM:2:0:N',
            status_jogo: 'Agendado',
            data_jogo: null,
            inicio_jogo: null,
            termino_jogo: null,
            locais_id_local: null,
            modalidades_id_modalidade: idModalidade,
            tipo_competicao: 'mata_mata',
            nome_tipo_modalidade: 'Mata-Mata',
            nome_modalidade: 'Futsal fixture',
            nome_categoria: 'Sub-15',
        }]);
    });
    await page.route((url) => url.pathname.endsWith('/api/v1/agenda-blocos'), async (route) => {
        if (route.request().method() === 'POST') {
            payloadPost = route.request().postDataJSON();
            const resposta = Array.isArray(respostaPost)
                ? respostaPost[Math.min(postCount, respostaPost.length - 1)]
                : respostaPost;
            postCount += 1;
            await fulfillJson(route, resposta.body, resposta.status);
            return;
        }
        await fulfillJson(route, []);
    });

    await page.goto(`edicoes/agenda?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.sgi-agenda-desktop')).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('#auto-modalidade option[value="987654321"]')).toHaveCount(1, { timeout: 20_000 });
    await expect(page.locator(`#auto-modalidade option[value="${idModalidadeIndividual}"]`)).toHaveCount(0);
    await expect(page.locator('#seq-local option[value="77"]')).toHaveCount(1);
    await expect(page.locator('#seq-local option[value="78"]')).toHaveCount(0);
    await page.locator('.btn-trigger-datas-auto:visible').click();
    await expect(page.locator('#modalDatasAutomaticas')).toBeVisible();
    await expect(page.locator('#seq-reprogramar')).toHaveAccessibleName('Permitir reprogramar jogos já agendados');

    return { idModalidade, getPayload: () => payloadPost };
}

test.describe('Agendamento automático sequencial', () => {
    test('usa segunda como padrão e aceita datas livres e dia adicional', async ({ page }) => {
        const tela = await prepararTela(page, [
            {
                status: 200,
                body: {
                    success: true,
                    revisao: 4,
                    proposta: [{ chave_tag: 'MM:2:0:N', data_jogo: '2026-09-16', inicio_jogo: '08:00:00', termino_jogo: '09:00:00', locais_id_local: 77 }],
                    pendencias: [{ chave_tag: 'MM:4:0:N', motivo: 'Ainda não há espaço nesta sessão.' }],
                    resumo: { encaixados: 1, pendentes: 1 },
                    proximo_dia_sugerido: '2026-09-17',
                    proximo_inicio_sugerido: '08:00:00',
                    proximo_termino_sugerido: '11:30',
                },
            },
            {
                status: 200,
                body: {
                    success: true,
                    revisao: 5,
                    proposta: [
                        { chave_tag: 'MM:2:0:N', data_jogo: '2026-09-16', inicio_jogo: '08:00:00', termino_jogo: '09:00:00', locais_id_local: 77 },
                        { chave_tag: 'MM:4:0:N', data_jogo: '2026-09-20', inicio_jogo: '08:00:00', termino_jogo: '09:00:00', locais_id_local: 77 },
                    ],
                    pendencias: [],
                    resumo: { encaixados: 2, pendentes: 0 },
                },
            },
        ]);

        const datas = await page.evaluate(() => {
            const ymd = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
            const proximaQuarta = new Date();
            proximaQuarta.setDate(proximaQuarta.getDate() + ((3 - proximaQuarta.getDay() + 7) % 7));
            const proximoSabado = new Date(proximaQuarta);
            proximoSabado.setDate(proximoSabado.getDate() + 3);
            return { arbitraria: ymd(proximaQuarta), adicional: ymd(proximoSabado) };
        });
        const weekday = await page.locator('#seq-data').evaluate((input) => new Date(`${input.value}T12:00:00`).getDay());
        expect(weekday).toBe(1);

        await page.locator('#seq-data').fill(datas.arbitraria);
        await page.locator('#seq-simular-btn').click();
        await expect(page.locator('#seq-proximo-dia')).toBeVisible();
        await page.locator('#seq-proxima-data').fill(datas.adicional);
        await page.locator('#seq-adicionar-dia').click();
        await expect(page.locator('#seq-salvar-btn')).toBeEnabled();

        expect(tela.getPayload()).toMatchObject({
            acao: 'simular_sequencial',
            dias: [
                { data: datas.arbitraria, inicio: '08:00', fim: '11:30', local: 77 },
                { data: datas.adicional, inicio: '08:00:00', fim: '11:30', local: 77 },
            ],
        });
    });

    test('envia o contrato da tela e renderiza a prévia', async ({ page }) => {
        const tela = await prepararTela(page, {
            status: 200,
            body: {
                success: true,
                revisao: 4,
                proposta: [{
                    chave_tag: 'MM:2:0:N',
                    data_jogo: '2026-09-22',
                    inicio_jogo: '08:00:00',
                    termino_jogo: '09:00:00',
                    locais_id_local: 77,
                }],
                pendencias: [],
                resumo: { encaixados: 1 },
            },
        });

        await page.locator('#seq-simular-btn').click();
        await expect(page.locator('#seq-previa')).toContainText('1 jogo(s) programado(s)');
        await expect(page.locator('#seq-salvar-btn')).toBeEnabled();

        expect(tela.getPayload()).toMatchObject({
            acao: 'simular_sequencial',
            id_interclasse: expect.any(Number),
            id_modalidade: tela.idModalidade,
            todos_jogos: true,
            reprogramar: false,
            dias: [{ data: expect.stringMatching(/^\d{4}-\d{2}-\d{2}$/), inicio: '08:00', fim: '11:30', local: 77 }],
            opcoes: { duracao_min: 60, intervalo_troca_min: 10 },
        });

        await page.locator('#seq-reprogramar').check();
        await page.locator('#seq-simular-btn').click();
        await expect(page.locator('#seq-salvar-btn')).toBeEnabled();
        expect(tela.getPayload().reprogramar).toBe(true);

        await page.locator('#seq-duracao').fill('45');
        await expect(page.locator('#seq-salvar-btn')).toBeDisabled();
        await expect(page.locator('#seq-previa')).toHaveText('Os dados foram alterados. Clique em “Calcular prévia” novamente.');
    });

    test('mostra o motivo do 422 e mantém confirmação bloqueada', async ({ page }) => {
        await prepararTela(page, {
            status: 422,
            body: { success: false, message: 'Não há jogos pendentes de agendamento nesta modalidade.' },
        });

        await page.locator('#seq-simular-btn').click();
        await expect(page.locator('#seq-previa')).toHaveText('Não há jogos pendentes de agendamento nesta modalidade.');
        await expect(page.locator('#seq-previa')).toHaveClass(/text-danger/);
        await expect(page.locator('#seq-salvar-btn')).toBeDisabled();
    });
});
