const { test, expect } = require('./fixtures.cjs');

const FIXTURE_MODALITY_ID = 987654321;

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

async function entrarComoAdmin(page, viewport) {
    await page.setViewportSize(viewport);
    await page.goto('login', { waitUntil: 'domcontentloaded' });

    const mobileForm = page.locator('#form_mobile');
    const form = (await mobileForm.isVisible()) ? mobileForm : page.locator('#form_desktop');
    await form.locator('.ipt-matricula').fill('admin');
    await form.locator('.ipt-senha').fill('123');
    await form.locator('button[type="submit"]').click();
    await page.waitForURL(/\/edicoes(?:\?|$)/, { timeout: 20_000 });

    return page.evaluate(async () => {
        const response = await fetch('/api/v1/edicoes?regulamento=true');
        if (!response.ok) throw new Error(`Consulta da edição ativa: HTTP ${response.status}`);
        const edicoes = await response.json();
        const edicao = (Array.isArray(edicoes) ? edicoes : [])
            .find((item) => String(item.status_interclasse) === '1');
        if (!edicao) throw new Error('O fixture autenticado não contém edição ativa para a agenda.');
        return Number(edicao.id_interclasse);
    });
}

async function prepararAgendaDeterministica(page, viewport) {
    const idInterclasse = await entrarComoAdmin(page, viewport);
    const datas = await page.evaluate(() => {
        const formatYmd = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        const today = new Date();
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
        const emptyDay = new Date(today.getFullYear(), today.getMonth(),
            today.getDate() < lastDay ? today.getDate() + 1 : today.getDate() - 1);
        return { today: formatYmd(today), emptyDay: formatYmd(emptyDay) };
    });

    const jogos = [
        {
            id_jogo: 987654321,
            nome_jogo: 'E06-fixture-1',
            data_jogo: datas.today,
            inicio_jogo: '08:00:00',
            termino_jogo: '08:20:00',
            status_jogo: 'Concluido',
            tipo_competicao: 'individual',
            nome_tipo_modalidade: 'Individual',
            nome_modalidade: 'Modalidade E06',
            nome_categoria: 'Categoria E06',
            nome_local: 'Ginásio E06',
            modalidades_id_modalidade: FIXTURE_MODALITY_ID,
            interclasses_id_interclasse: idInterclasse,
            equipes_nomes: 'Equipe E06 A vs Equipe E06 B',
            locais_id_local: 1,
        },
        {
            id_jogo: 987654322,
            nome_jogo: 'E06-fixture-2',
            data_jogo: datas.today,
            inicio_jogo: '08:30:00',
            termino_jogo: '08:50:00',
            status_jogo: 'Concluido',
            tipo_competicao: 'individual',
            nome_tipo_modalidade: 'Individual',
            nome_modalidade: 'Modalidade E06',
            nome_categoria: 'Categoria E06',
            nome_local: 'Ginásio E06',
            modalidades_id_modalidade: FIXTURE_MODALITY_ID,
            interclasses_id_interclasse: idInterclasse,
            equipes_nomes: 'Equipe E06 C vs Equipe E06 D',
            locais_id_local: 1,
        },
    ];

    // Keep the authenticated edition, routes and page shell real; replace only
    // the schedule response so date/count/empty-state assertions are stable.
    await page.route('**/api/v1/modalidades**', async (route) => {
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify([{
                id_modalidade: FIXTURE_MODALITY_ID,
                interclasses_id_interclasse: idInterclasse,
                nome_modalidade: 'Modalidade E06',
                nome_categoria: 'Categoria E06',
                nome_tipo_modalidade: 'Individual',
            }]),
        });
    });
    await page.route('**/api/v1/jogos**', async (route) => {
        const url = new URL(route.request().url());
        if (url.searchParams.get('id_modalidade') !== String(FIXTURE_MODALITY_ID)) {
            await route.continue();
            return;
        }
        await route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: JSON.stringify(jogos),
        });
    });

    await page.goto(`edicoes/agenda?id=${idInterclasse}`, { waitUntil: 'domcontentloaded' });
    const main = viewport.width < 768 ? '.sgi-agenda-mobile' : '.sgi-agenda-desktop';
    await expect(page.locator(main)).toBeVisible({ timeout: 20_000 });
    await expect(page.locator(`${main} [data-date="${datas.today}"]`)).toBeVisible({ timeout: 20_000 });
    return { main, datas };
}

function localizedDateExpression(isoDate) {
    const [year, month, day] = isoDate.split('-').map(Number);
    const fullDate = new Intl.DateTimeFormat('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(year, month - 1, day, 12));
    return new RegExp(escapeRegExp(fullDate), 'i');
}

async function observarAnunciosDaAgenda(page, mainSelector) {
    await page.evaluate((selector) => {
        const root = document.querySelector(selector);
        if (!root) throw new Error(`Não foi encontrada a agenda visível: ${selector}`);
        const liveRegion = Array.from(root.querySelectorAll('[role="status"], [aria-live]'))
            .find((element) => {
                const style = getComputedStyle(element);
                return style.display !== 'none' && style.visibility !== 'hidden' && element.getClientRects().length > 0;
            });
        if (!liveRegion) throw new Error(`Não foi encontrada uma região viva visível: ${selector}`);

        window.__e06AgendaAnnouncements = [];
        window.__e06LastAgendaAnnouncement = '';
        const coletarTexto = () => {
            const text = (liveRegion.innerText || liveRegion.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();
            if (text && text !== window.__e06LastAgendaAnnouncement) {
                window.__e06LastAgendaAnnouncement = text;
                window.__e06AgendaAnnouncements.push(text);
            }
        };
        new MutationObserver(coletarTexto).observe(liveRegion, {
            childList: true,
            characterData: true,
            subtree: true,
        });
    }, mainSelector);
}

async function anunciosDaAgenda(page) {
    return page.evaluate(() => window.__e06AgendaAnnouncements || []);
}

for (const layout of [
    { name: 'desktop', viewport: { width: 1440, height: 900 }, main: '.sgi-agenda-desktop', grid: '#calendario-grade' },
    { name: 'mobile', viewport: { width: 390, height: 844 }, main: '.sgi-agenda-mobile', grid: '#calendario-grade-mobile' },
]) {
    test(`agenda ${layout.name}: dias são botões com data completa, estado de hoje e quantidade de jogos`, async ({ page }) => {
        const { datas } = await prepararAgendaDeterministica(page, layout.viewport);
        const dia = page.locator(`${layout.main} ${layout.grid} button[data-date="${datas.today}"]`);

        await expect(dia).toHaveCount(1, { timeout: 5_000 });
        await expect(dia).toHaveAttribute('type', 'button');
        await expect(dia).toHaveAccessibleName(localizedDateExpression(datas.today));
        await expect(dia).toHaveAccessibleName(/hoje/i);
        await expect(dia).toHaveAccessibleName(/2 jogos/i);
        await expect(dia).toHaveAttribute('aria-current', 'date');
        await expect(dia).toHaveAttribute('aria-pressed', 'false');
        await dia.click();
        await expect(page.locator(`${layout.main} ${layout.grid} button[data-date="${datas.today}"]`))
            .toHaveAttribute('aria-pressed', 'true');

        const diasDaSemana = page.locator(`${layout.main} [aria-label="Dias da semana"] .ag-cal-weekday`);
        await expect(diasDaSemana).toHaveCount(7);
        for (const [index, weekday] of [
            'Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'
        ].entries()) {
            await expect(diasDaSemana.nth(index)).toContainText(weekday);
        }

        if (layout.name === 'mobile') {
            const [year, month, day] = datas.today.split('-').map(Number);
            const monthName = new Intl.DateTimeFormat('pt-BR', { month: 'long' })
                .format(new Date(year, month - 1, day, 12));
            await expect(page.locator('#select-mes option:checked')).toHaveText(new RegExp(monthName, 'i'));
            const linkGoogleCalendar = page.getByRole('link', { name: /Visitar Google Calendar.*nova guia/i });
            await expect(linkGoogleCalendar).toHaveAttribute('href', 'https://calendar.google.com');
            await expect(linkGoogleCalendar).toHaveAttribute('target', '_blank');
        }
    });
}

test('agenda permite selecionar um dia sem jogos e limpar o filtro por teclado', async ({ page }) => {
    const { main, datas } = await prepararAgendaDeterministica(page, { width: 1440, height: 900 });
    const prefix = `${main} #calendario-grade`;
    const diaVazio = page.locator(`${prefix} button[data-date="${datas.emptyDay}"]`);

    await expect(diaVazio).toHaveCount(1, { timeout: 5_000 });
    await expect(diaVazio).toHaveAccessibleName(localizedDateExpression(datas.emptyDay));
    await expect(diaVazio).toHaveAccessibleName(/0 jogos|sem jogos/i);
    await diaVazio.focus();
    await page.keyboard.press('Space');

    await expect(page.locator(`${prefix} button[data-date="${datas.emptyDay}"]`)).toHaveAttribute('aria-pressed', 'true');
    await expect.poll(() => page.evaluate(() => document.activeElement?.dataset?.date || '')).toBe(datas.emptyDay);
    await expect(page.locator(`${main} #lista-eventos`)).toContainText('Nenhum jogo nesta data.');
    const limpar = page.locator(`${main} #btn-mostrar-todos`);
    await expect(limpar).toBeVisible();
    await limpar.focus();
    await page.keyboard.press('Enter');

    await expect(page.locator(`${main} #lista-eventos > .ag-event-card`)).toHaveCount(2);
    await expect(page.locator(`${prefix} button[data-date="${datas.emptyDay}"]`)).toHaveAttribute('aria-pressed', 'false');
    await expect.poll(() => page.evaluate(() => document.activeElement?.dataset?.date || '')).toBe(datas.emptyDay);
});

test('agenda compacta em paisagem 640×360 mantém calendário e filtros dentro da viewport', async ({ page }) => {
    const { main, datas } = await prepararAgendaDeterministica(page, { width: 640, height: 360 });
    const dia = page.locator(`${main} #calendario-grade-mobile button[data-date="${datas.today}"]`);
    const larguraDocumento = await page.evaluate(() => document.documentElement.scrollWidth);
    const controles = page.locator(`${main} #btn-prev-mobile, ${main} #select-mes, ${main} #select-ano, ${main} #btn-next-mobile`);

    expect(larguraDocumento).toBeLessThanOrEqual(640);
    await expect(controles).toHaveCount(4);
    await expect(dia).toBeVisible();
    const alvo = await dia.boundingBox();
    expect(alvo?.width).toBeGreaterThanOrEqual(40);
    expect(alvo?.height).toBeGreaterThanOrEqual(40);

    if (process.env.E06_CAPTURE === '1') {
        await page.screenshot({
            path: '/app/test-results/ui-ux-audit-20260913/E06-agenda-landscape-640x360.png',
            fullPage: false,
        });
    }
});

test('agenda conserva foco e anuncia uma vez o resultado após selecionar data e trocar mês', async ({ page }) => {
    const { main, datas } = await prepararAgendaDeterministica(page, { width: 1440, height: 900 });
    const prefix = `${main} #calendario-grade`;
    await observarAnunciosDaAgenda(page, main);

    const dia = page.locator(`${prefix} button[data-date="${datas.today}"]`);
    await expect(dia).toHaveCount(1, { timeout: 5_000 });
    await dia.focus();
    await page.keyboard.press('Enter');

    const diaSelecionado = page.locator(`${prefix} button[data-date="${datas.today}"]`);
    await expect(diaSelecionado).toHaveAttribute('aria-pressed', 'true');
    await expect.poll(() => page.evaluate(() => document.activeElement?.dataset?.date || '')).toBe(datas.today);
    await expect(page.locator(`${main} #lista-eventos > .ag-event-card`)).toHaveCount(2);
    await expect.poll(() => anunciosDaAgenda(page)).toHaveLength(1);
    expect((await anunciosDaAgenda(page))[0]).toMatch(/2 jogos/i);
    expect((await anunciosDaAgenda(page))[0]).toMatch(localizedDateExpression(datas.today));

    await page.evaluate(() => {
        window.__e06AgendaAnnouncements = [];
        window.__e06LastAgendaAnnouncement = '';
    });
    const monthHeading = page.locator(`${main} #calendario-mes`);
    const monthBefore = await monthHeading.innerText();
    const nextMonth = page.locator(`${main} #btn-next`);
    await nextMonth.focus();
    await page.keyboard.press('Enter');

    await expect.poll(() => monthHeading.innerText()).not.toBe(monthBefore);
    await expect.poll(() => page.evaluate(() => document.activeElement?.id || '')).toBe('btn-next');
    await expect(page.locator(`${main} #lista-eventos`)).toContainText('Nenhum jogo neste mês.');
    await expect.poll(() => anunciosDaAgenda(page)).toHaveLength(1);
    expect((await anunciosDaAgenda(page))[0]).toMatch(/nenhum jogo|0 jogos/i);
});

test('agenda anuncia buscas diferentes mesmo quando ambas retornam a mesma quantidade de jogos', async ({ page }) => {
    const { main } = await prepararAgendaDeterministica(page, { width: 1440, height: 900 });
    await observarAnunciosDaAgenda(page, main);
    const busca = page.locator(`${main} #agenda-busca`);
    const lista = page.locator(`${main} #lista-eventos`);

    await busca.fill('Equipe E06 A');
    await expect(lista.locator(':scope > .ag-event-card')).toHaveCount(1);
    await expect(lista).toContainText('Equipe E06 A');
    await expect(lista).not.toContainText('Equipe E06 C');
    await expect.poll(() => anunciosDaAgenda(page)).toHaveLength(1);
    expect((await anunciosDaAgenda(page))[0]).toMatch(/1 jogo/i);
    expect((await anunciosDaAgenda(page))[0]).toContain('Busca: Equipe E06 A');

    await page.evaluate(() => {
        window.__e06AgendaAnnouncements = [];
        window.__e06LastAgendaAnnouncement = '';
    });
    await busca.fill('Equipe E06 C');
    await expect(lista.locator(':scope > .ag-event-card')).toHaveCount(1);
    await expect(lista).toContainText('Equipe E06 C');
    await expect(lista).not.toContainText('Equipe E06 A');
    await expect.poll(() => anunciosDaAgenda(page)).toHaveLength(1);
    expect((await anunciosDaAgenda(page))[0]).toMatch(/1 jogo/i);
    expect((await anunciosDaAgenda(page))[0]).toContain('Busca: Equipe E06 C');
});
