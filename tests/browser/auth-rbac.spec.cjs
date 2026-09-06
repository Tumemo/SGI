const { test, expect, request: playwrightRequest } = require('./fixtures.cjs');

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

test.describe('Autenticação, RBAC e Segurança de Rotas', () => {

    test('rejeição de credenciais inválidas com feedback visual adequado em desktop e mobile', async ({ page }) => {
        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();

        // 1. Senha incorreta no desktop
        await page.locator('#form_desktop .ipt-matricula').fill('admin');
        await page.locator('#form_desktop .ipt-senha').fill('senha_errada_999');
        await page.locator('#form_desktop button[type="submit"]').click();

        await expect(page.locator('#msg_erro_desktop')).toBeVisible();
        await expect(page.locator('#msg_erro_desktop')).toContainText(/Matrícula ou Senha incorretos|incorretos/i);
        await expect(page).toHaveURL(/views\/index\.php(?:\?|$)/);

        // 2. Matrícula inexistente no desktop
        await page.locator('#form_desktop .ipt-matricula').fill('usuario_que_nao_existe_xyz');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();

        await expect(page.locator('#msg_erro_desktop')).toBeVisible();
        await expect(page.locator('#msg_erro_desktop')).toContainText(/Matrícula ou Senha incorretos|incorretos/i);

        // 3. Senha incorreta no formulário mobile
        await page.setViewportSize({ width: 390, height: 844 });
        await expect(page.locator('#form_mobile')).toBeVisible();

        await page.locator('#form_mobile .ipt-matricula').fill('admin');
        await page.locator('#form_mobile .ipt-senha').fill('senha_errada_mobile');
        await page.locator('#form_mobile button[type="submit"]').click();

        await expect(page.locator('#msg_erro_mobile')).toBeVisible();
        await expect(page.locator('#msg_erro_mobile')).toContainText(/Matrícula ou Senha incorretos|incorretos/i);
        await expect(page).toHaveURL(/views\/index\.php(?:\?|$)/);
    });

    test('proteção de rotas restritas para acessos anônimos', async ({ browser }) => {
        // Novo contexto sem cookies/sessão
        const context = await browser.newContext();
        const page = await context.newPage();

        try {
            // Tenta acessar home administrativa sem login
            await page.goto('views/src/pages/home.php', { waitUntil: 'domcontentloaded' });
            await expect(page).toHaveURL(/views\/index\.php/);

            // Tenta acessar dashboard administrativa sem login
            await page.goto('views/src/pages/dashboard.php?id=1', { waitUntil: 'domcontentloaded' });
            await expect(page).toHaveURL(/views\/index\.php/);

            // Tenta acessar ranking staff sem login
            await page.goto('views/src/pages/ranking.php?id=1', { waitUntil: 'domcontentloaded' });
            await expect(page).toHaveURL(/views\/index\.php/);

            // Tenta acessar portal do aluno sem login
            await page.goto('views/src/pages/alunos/home.php', { waitUntil: 'domcontentloaded' });
            await expect(page).toHaveURL(/views\/index\.php/);
        } finally {
            await context.close();
        }
    });

    test('aluno autenticado não pode acessar rotas da administração (RBAC)', async ({ page, request }) => {
        // Obter uma turma para vincular o aluno
        const adminLogin = await request.post('api/login.php', {
            data: { matricula: 'admin', senha: '123' }
        });
        await jsonOrThrow(adminLogin, 'login de admin');

        const turmasRes = await request.get('api/turmas.php');
        const turmas = await jsonOrThrow(turmasRes, 'consulta turmas');
        const turma = Array.isArray(turmas) && turmas.length > 0 ? turmas[0] : null;
        const idTurma = turma ? Number(turma.id_turma) : 1;

        // Cria competidor efêmero
        const matriculaAluno = `88${Date.now().toString().slice(-7)}`;
        await jsonOrThrow(await request.post('api/usuarios.php?acao=criar_aluno', {
            data: {
                nome_usuario: 'Aluno RBAC Test',
                matricula_usuario: matriculaAluno,
                genero_usuario: 'MASC',
                data_nasc_usuario: '2009-05-15',
                turmas_id_turma: idTurma
            }
        }), 'criação do aluno');

        // Loga como aluno
        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill(matriculaAluno);
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();

        await page.waitForURL(/\/views\/src\/pages\/alunos\/home\.php/, { timeout: 15_000 });
        await expect(page).toHaveURL(/\/views\/src\/pages\/alunos\/home\.php/);

        // Aluno tenta navegar para tela staff
        await page.goto('views/src/pages/home.php', { waitUntil: 'domcontentloaded' });
        // Deve ser bloqueado ou redirecionado para o portal de aluno / login
        await expect(page).not.toHaveURL(/views\/src\/pages\/home\.php$/);

        await page.goto('views/src/pages/dashboard.php?id=1', { waitUntil: 'domcontentloaded' });
        await expect(page).not.toHaveURL(/views\/src\/pages\/dashboard\.php/);
    });

    test('logout encerra sessão com segurança e impede reentrada pelo histórico', async ({ page }) => {
        page.on('dialog', async (dialog) => {
            await dialog.accept();
        });

        // 1. Login como admin
        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await page.locator('#form_desktop .ipt-matricula').fill('admin');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();

        await page.waitForURL(/\/views\/src\/pages\/home\.php/, { timeout: 15_000 });
        await expect(page.locator('#listaDesktop')).toBeVisible({ timeout: 15_000 });

        // 2. Acionar logout no menu de navegação visível
        const logoutLink = page.locator('a[href*="logout.php"]:visible');
        await expect(logoutLink).toBeVisible();
        await logoutLink.click();

        // 3. Confirmar que redirecionou para tela de login
        await page.waitForURL(/views\/index\.php/, { timeout: 15_000 });
        await expect(page.locator('#form_desktop')).toBeVisible();

        // 4. Tentar acessar página interna diretamente após logout
        await page.goto('views/src/pages/home.php', { waitUntil: 'domcontentloaded' });
        await expect(page).toHaveURL(/views\/index\.php/);
    });

});
