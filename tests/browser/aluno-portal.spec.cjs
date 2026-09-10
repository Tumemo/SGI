const { test, expect } = require('./fixtures.cjs');

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

async function prepararAlunoFixture(request) {
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login administrativo de preparação');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'consulta edições'
    );
    const edicao = (Array.isArray(edicoes) ? edicoes : []).find((e) => String(e.status_interclasse) === '1') || edicoes[0];
    if (!edicao) throw new Error('Nenhuma edição disponível.');
    const idInterclasse = Number(edicao.id_interclasse);

    const turmas = await jsonOrThrow(
        await request.get(`api/v1/turmas?id_interclasse=${idInterclasse}`),
        'consulta turmas'
    );
    const turma = (Array.isArray(turmas) && turmas.length > 0) ? turmas[0] : null;
    if (!turma) throw new Error('Nenhuma turma encontrada.');
    const idTurma = Number(turma.id_turma);

    const matricula = `55${Date.now().toString().slice(-7)}`;
    const novoAluno = await jsonOrThrow(await request.post('api/v1/usuarios?acao=criar_aluno', {
        data: {
            nome_usuario: 'Aluno E2E Portal',
            matricula_usuario: matricula,
            genero_usuario: 'MASC',
            data_nasc_usuario: '2008-07-20',
            turmas_id_turma: idTurma
        }
    }), 'criação de aluno fixture');

    if (novoAluno.status !== 'sucesso') {
        throw new Error(`Falha ao criar aluno: ${JSON.stringify(novoAluno)}`);
    }

    return {
        idInterclasse,
        idTurma,
        matricula,
        senhaOriginal: String(novoAluno.senha_temporaria || '')
    };
}

test.describe.serial('Portal do Aluno — Jornada Interativa e Regras de Negócio', () => {
    let fixture = null;

    test.beforeAll(async ({ request }) => {
        fixture = await prepararAlunoFixture(request);
    });

    test('login do aluno e leitura dos termos de responsabilidade', async ({ page }) => {
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();

        await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
        await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaOriginal);
        await page.locator('#form_desktop button[type="submit"]').click();

        await page.waitForURL(/\/aluno\/termos/, { timeout: 15_000 });
        await expect(page.locator('main')).toBeVisible();

        // Validar presença do Termo de Responsabilidade e suas cláusulas fundamentais
        await expect(page.locator('main')).toContainText('Termo de Responsabilidade');
        await expect(page.locator('main')).toContainText('Conduta:');
        await expect(page.locator('main')).toContainText('Regras:');
        await expect(page.locator('main')).toContainText('Saúde:');

        // O acesso direto por URL e a API permanecem bloqueados antes do aceite.
        for (const paginaProtegida of [
            'aluno/inicio',
            'aluno/modalidades',
            'aluno/jogos',
            'aluno/perfil',
            'aluno/ranking',
        ]) {
            await page.goto(paginaProtegida, { waitUntil: 'domcontentloaded' });
            await expect(page).toHaveURL(/\/aluno\/termos/);
            await expect(page.locator('body')).not.toContainText('Cronograma de Jogos');
        }

        await expect(page.locator('nav a[aria-label="Termos"], nav a[title="Termos"]')).not.toHaveCount(0);
        for (const seletorProtegido of [
            'nav a[aria-label="Início"], nav a[title="Início"]',
            'nav a[aria-label="Jogos"], nav a[title="Jogos"]',
            'nav a[aria-label="Perfil"], nav a[title="Perfil"]',
        ]) {
            await expect(page.locator(seletorProtegido)).toHaveCount(0);
        }

        const jogosSemAceite = await page.evaluate(async () => {
            const response = await fetch('/api/v1/jogos');
            return response.status;
        });
        expect(jogosSemAceite).toBe(403);

        const inscricaoSemAceite = await page.evaluate(async () => {
            const response = await fetch('/api/v1/inscricoes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-SGI-CSRF': window.SGI_CSRF_TOKEN || '',
                },
                body: JSON.stringify({ id_interclasse: 1, id_equipes: [] }),
            });
            return response.status;
        });
        expect(inscricaoSemAceite).toBe(403);

        await page.goto('aluno/termos', { waitUntil: 'domcontentloaded' });
        const btnAceitar = page.locator('#btnAceitarTermos');
        await expect(btnAceitar).toBeVisible({ timeout: 15_000 });
        await btnAceitar.click();
        await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });
    });

    test('inscrição em modalidades, escolha de equipe e validação de regras', async ({ page }) => {
        // Login com o aluno
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
        await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaOriginal);
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });

        // Ir para a tela de inscrição de modalidades
        await page.goto(`aluno/modalidades?id=${fixture.idInterclasse}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#modalidadesGrid')).toBeVisible({ timeout: 15_000 });

        const cards = page.locator('.modalidade-card:not(.lotado)');
        await expect(cards.first()).toBeVisible({ timeout: 15_000 });

        // Clica no primeiro card disponível para abrir o modal de equipes
        await cards.first().click();

        const modalEquipes = page.locator('#modalEquipes');
        await expect(modalEquipes).toBeVisible({ timeout: 10_000 });

        const linhaEquipe = modalEquipes.locator('.equipe-pick-row').first();
        await expect(linhaEquipe).toBeVisible({ timeout: 10_000 });
        await linhaEquipe.click();
        await expect(modalEquipes).toBeHidden({ timeout: 10_000 });

        // Confirmar que o card recebeu a classe de selecionado
        await expect(page.locator('.modalidade-card.selected')).toHaveCount(1);

        // Salva a escolha
        const btnSalvar = page.locator('#btnSalvar');
        await expect(btnSalvar).toBeEnabled({ timeout: 10_000 });
        await btnSalvar.click();

        // Confirma feedback de salvamento
        await expect(page.locator('#msgFeedback')).toContainText(/sucesso|salvo/i, { timeout: 10_000 });
    });

    test('navegação e consulta da agenda de jogos e do ranking pelo aluno', async ({ page }) => {
        // Login com o aluno
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
        await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaOriginal);
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });

        await expect(page.locator('nav a[aria-label="Rankings publicados"], nav a[title="Rankings publicados"]')).toHaveCount(0);
        await expect(page.locator('.aluno-card[data-status="active"] .btn-card').first()).toContainText('Ver Detalhes');

        // 1. Tela de Jogos
        await page.goto(`aluno/jogos?id=${fixture.idInterclasse}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('main')).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('body')).not.toContainText(/Fatal error|Warning:/i);

        // 2. Tela de Ranking
        await page.goto(`aluno/ranking?id=${fixture.idInterclasse}`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('main:visible')).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('main:visible')).toContainText('Ranking Oculto', { timeout: 15_000 });
        await expect(page.locator('body')).not.toContainText(/Fatal error|Warning:/i);
    });

    test('gestão de perfil, validação de senha e alteração com reautenticação', async ({ page }) => {
        // Login com o aluno
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
        await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaOriginal);
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });

        // Acessar perfil
        await page.goto('aluno/perfil', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#perfilNomeDesk, #perfilNomeInfo').first()).toBeVisible({ timeout: 15_000 });

        // Abrir modal de alteração de senha
        const btnAlterarSenha = page.locator('button[data-bs-target="#modalAlterarSenha"]:visible');
        await expect(btnAlterarSenha).toBeVisible({ timeout: 10_000 });
        await btnAlterarSenha.click();

        const modalSenha = page.locator('#modalAlterarSenha');
        await expect(modalSenha).toBeVisible({ timeout: 10_000 });

        // 1. Testar senha atual incorreta
        await page.locator('#editarSenhaAtual').fill('senha_errada_xyz');
        await page.locator('#editarNovaSenha').fill('novaSenha123');
        await page.locator('#editarConfirmarSenha').fill('novaSenha123');
        await page.locator('#btnSalvarSenha').click();

        await expect(page.locator('#msgAlterarSenha')).toContainText(/Senha atual incorreta|incorreta/i, { timeout: 10_000 });

        // 2. Testar senhas que não coincidem
        await page.locator('#editarSenhaAtual').fill(fixture.senhaOriginal);
        await page.locator('#editarNovaSenha').fill('novaSenha123');
        await page.locator('#editarConfirmarSenha').fill('outraSenhaDiferente');
        await page.locator('#btnSalvarSenha').click();

        await expect(page.locator('#msgAlterarSenha')).toContainText(/não coincidem/i, { timeout: 10_000 });

        // 3. Alterar com dados corretos
        const novaSenhaCorreta = 'novaSenha123';
        await page.locator('#editarConfirmarSenha').fill(novaSenhaCorreta);
        await page.locator('#btnSalvarSenha').click();

        // Modal deve fechar após sucesso
        await expect(modalSenha).toBeHidden({ timeout: 15_000 });

        // 4. Logout e reautenticação com a nova senha
        page.on('dialog', async (dialog) => dialog.accept());
        const linkLogout = page.locator('a[href*="api/v1/logout"]:visible');
        await expect(linkLogout).toBeVisible({ timeout: 10_000 });
        await linkLogout.click();
        await page.waitForURL(/login/, { timeout: 15_000 });

        // Tentativa com senha antiga deve falhar
        await page.locator('#form_desktop .ipt-matricula').fill(fixture.matricula);
        await page.locator('#form_desktop .ipt-senha').fill(fixture.senhaOriginal);
        await page.locator('#form_desktop button[type="submit"]').click();
        await expect(page.locator('#msg_erro_desktop')).toBeVisible({ timeout: 10_000 });

        // Login com nova senha deve suceder
        await page.locator('#form_desktop .ipt-senha').fill(novaSenhaCorreta);
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/aluno\/inicio/, { timeout: 15_000 });
        await expect(page).toHaveURL(/\/aluno\/inicio/);
    });

});
