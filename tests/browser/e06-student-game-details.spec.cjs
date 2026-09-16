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

async function prepararAlunoFixture(request) {
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login administrativo de preparação');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'consulta de edições'
    );
    const listaEdicoes = Array.isArray(edicoes) ? edicoes : [];
    const edicao = listaEdicoes.find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('Nenhuma edição ativa disponível para a regressão do aluno.');

    const idInterclasse = Number(edicao.id_interclasse);
    const turmas = await jsonOrThrow(
        await request.get(`api/v1/turmas?id_interclasse=${idInterclasse}`),
        'consulta de turmas'
    );
    const turma = Array.isArray(turmas) ? turmas.find((item) => Number(item.id_turma) > 0) : null;
    if (!turma) throw new Error('Nenhuma turma disponível para a regressão do aluno.');

    const matricula = `56${Date.now().toString().slice(-7)}`;
    const aluno = await jsonOrThrow(await request.post('api/v1/usuarios?acao=criar_aluno', {
        data: {
            nome_usuario: 'Aluno E06 Teclado',
            matricula_usuario: matricula,
            genero_usuario: 'MASC',
            data_nasc_usuario: '2008-07-20',
            turmas_id_turma: Number(turma.id_turma),
        }
    }), 'criação de aluno fixture');

    if (aluno.status !== 'sucesso') {
        throw new Error(`Falha ao criar aluno fixture: ${JSON.stringify(aluno)}`);
    }
    if (!aluno.senha_temporaria) {
        throw new Error('A API não retornou a senha temporária do aluno fixture.');
    }

    return {
        idInterclasse,
        matricula,
        senhaTemporaria: String(aluno.senha_temporaria),
        senhaNova: 'AlunoE06#2026',
    };
}

async function entrarPeloPrimeiroAcessoEDeclararTermos(page, fixture) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
    await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaTemporaria);
    await page.locator('#form_desktop button[type="submit"]').click();

    await page.waitForURL(/\/aluno\/trocar-senha/, { timeout: 15_000 });
    await expect(page.locator('#formPrimeiroAcesso')).toBeVisible();
    await page.locator('#novaSenhaPrimeiroAcesso').fill(fixture.senhaNova);
    await page.locator('#confirmarSenhaPrimeiroAcesso').fill(fixture.senhaNova);
    await page.locator('#btnSalvarSenhaPrimeiroAcesso').click();

    await page.waitForURL(/\/aluno\/termos/, { timeout: 15_000 });
    await expect(page.locator('main')).toContainText('Termo de Responsabilidade');
    await page.locator('#btnAceitarTermos').click();
    await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });
}

async function instalarPartidaVisivel(page, idInterclasse) {
    const idJogo = 96006;
    const idModalidade = 96005;
    const idCategoria = 96003;
    const nomeJogo = 'Partida de teste E06';
    const linhasPartida = [
        {
            id_jogo: idJogo,
            nome_jogo: nomeJogo,
            status_jogo: 'Agendado',
            nome_modalidade: 'Futsal E06',
            modalidades_id_modalidade: idModalidade,
            categorias_id_categoria: idCategoria,
            nome_categoria: 'Sub-15 E06',
            data_jogo: '2026-09-15',
            inicio_jogo: '10:00:00',
            termino_jogo: '11:00:00',
            nome_local: 'Quadra E06',
            equipes_id_equipe: 96001,
            nome_fantasia_turma: 'Lobos E06',
            nome_turma: 'Turma Azul E06',
            resultado_partida: 0,
        },
        {
            id_jogo: idJogo,
            nome_jogo: nomeJogo,
            status_jogo: 'Agendado',
            nome_modalidade: 'Futsal E06',
            modalidades_id_modalidade: idModalidade,
            categorias_id_categoria: idCategoria,
            nome_categoria: 'Sub-15 E06',
            data_jogo: '2026-09-15',
            inicio_jogo: '10:00:00',
            termino_jogo: '11:00:00',
            nome_local: 'Quadra E06',
            equipes_id_equipe: 96002,
            nome_fantasia_turma: 'Tigres E06',
            nome_turma: 'Turma Verde E06',
            resultado_partida: 0,
        },
    ];

    await page.route('**/api/v1/**', async (route) => {
        const url = new URL(route.request().url());
        if (url.pathname.endsWith('/edicoes') && url.searchParams.get('regulamento') === 'true') {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([{
                    id_interclasse: idInterclasse,
                    nome_interclasse: 'Edição E06 de teste',
                    ano_interclasse: '2026-01-01',
                    status_interclasse: '1',
                }]),
            });
            return;
        }
        if (url.pathname.endsWith('/partidas')) {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify(linhasPartida),
            });
            return;
        }
        if (url.pathname.endsWith('/artilheiros')) {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify([]),
            });
            return;
        }
        await route.continue();
    });

    return { idJogo };
}

async function abrirResumoComTecla(page, controle, tecla) {
    const modal = page.locator('#modalModalidade');
    const modalExibido = modal.evaluate((element) => new Promise((resolve) => {
        element.addEventListener('shown.bs.modal', resolve, { once: true });
    }));
    await controle.focus();
    await expect(controle).toBeFocused();
    await page.keyboard.press(tecla);
    await modalExibido;
    await expect(modal).toBeVisible();
    await expect(modal).toHaveAttribute('aria-modal', 'true');
    await expect(modal).toBeFocused();
    await expect(page.getByRole('dialog', { name: 'Resumo da Partida' })).toBeVisible();
}

async function fecharResumoEValidarFoco(page, controle) {
    const modal = page.locator('#modalModalidade');
    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(controle).toBeFocused();
}

test('cartão do jogo abre com Enter e Espaço, fecha com Escape e mantém foco sem controles aninhados', async ({ page, request }) => {
    const fixture = await prepararAlunoFixture(request);
    await entrarPeloPrimeiroAcessoEDeclararTermos(page, fixture);

    const jogo = await instalarPartidaVisivel(page, fixture.idInterclasse);
    await page.goto(`aluno/jogos?id=${fixture.idInterclasse}`, { waitUntil: 'domcontentloaded' });

    const cartao = page.locator(`article[data-jogo-id="${jogo.idJogo}"]`);
    await expect(cartao).toBeVisible();

    const botaoDetalhes = cartao.getByRole('button', { name: 'Ver detalhes do jogo', exact: true });
    const botoesDetalhes = await botaoDetalhes.count();
    let controle;

    if (botoesDetalhes === 1) {
        // Solução recomendada: artigo informativo com uma ação explícita.
        await expect(cartao).not.toHaveAttribute('role', 'button');
        await expect(cartao.locator('a, button, input, select, textarea, [role="button"], [tabindex]')).toHaveCount(1);
        controle = botaoDetalhes;

        await page.setViewportSize({ width: 390, height: 844 });
        const alturaBotao = await botaoDetalhes.evaluate((element) => element.getBoundingClientRect().height);
        expect(alturaBotao).toBeGreaterThanOrEqual(48);
    } else {
        // Alternativa permitida pelo plano: o cartão inteiro atua como botão e
        // não contém controles interativos descendentes.
        await expect(cartao).toHaveAttribute('role', 'button');
        await expect(cartao).toHaveAttribute('tabindex', '0');
        await expect(cartao.locator('a, button, input, select, textarea, [role="button"], [tabindex]')).toHaveCount(0);
        controle = cartao;
    }

    await abrirResumoComTecla(page, controle, 'Enter');
    if (process.env.E06_CAPTURE === '1') {
        await page.screenshot({
            path: '/app/test-results/ui-ux-audit-20260913/E06-aluno-detalhes-mobile.png',
            fullPage: true,
        });
    }
    await fecharResumoEValidarFoco(page, controle);

    await abrirResumoComTecla(page, controle, 'Space');
    await fecharResumoEValidarFoco(page, controle);
});
