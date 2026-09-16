const { test, expect } = require('./fixtures.cjs');

async function entrarComoAdmin(page) {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await page.locator('#form_desktop .ipt-matricula').fill('admin');
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForURL(/\/edicoes/, { timeout: 15_000 });
}

async function dadosEdicao(page) {
    return page.evaluate(async () => {
        const resposta = await fetch('/api/v1/edicoes?regulamento=true');
        if (!resposta.ok) throw new Error(`Consulta de edições: HTTP ${resposta.status}`);
        const edicoes = await resposta.json();
        const edicao = (Array.isArray(edicoes) ? edicoes : []).find((item) => String(item.status_interclasse) === '1')
            || (Array.isArray(edicoes) ? edicoes[0] : null);
        if (!edicao) throw new Error('O fixture não contém uma edição para os seletores de categoria/modalidade.');

        const [categoriasResposta, modalidadesResposta] = await Promise.all([
            fetch(`/api/v1/categorias?id_interclasse=${edicao.id_interclasse}`),
            fetch(`/api/v1/modalidades?id_interclasse=${edicao.id_interclasse}`),
        ]);
        if (!categoriasResposta.ok || !modalidadesResposta.ok) {
            throw new Error(`Consulta de configuração: categorias ${categoriasResposta.status}, modalidades ${modalidadesResposta.status}`);
        }
        const categoriasPayload = await categoriasResposta.json();
        const modalidadesPayload = await modalidadesResposta.json();
        const categorias = Array.isArray(categoriasPayload) ? categoriasPayload : categoriasPayload?.data;
        const modalidades = Array.isArray(modalidadesPayload) ? modalidadesPayload : modalidadesPayload?.data;
        const categoriaIds = new Set((Array.isArray(categorias) ? categorias : []).map((item) => String(item.id_categoria)));
        const listaModalidades = Array.isArray(modalidades) ? modalidades : [];
        const modalidade = listaModalidades.find((item) =>
            String(item.interclasses_id_interclasse) === String(edicao.id_interclasse)
            && categoriaIds.has(String(item.categorias_id_categoria))
        );
        if (!categoriaIds.size || !modalidade) {
            throw new Error('O fixture precisa fornecer uma categoria e uma modalidade na edição.');
        }
        return {
            edicao: Number(edicao.id_interclasse),
            categoria: (Array.isArray(categorias) ? categorias : []).find((item) => String(item.id_categoria) === String(modalidade.categorias_id_categoria)),
            modalidade,
        };
    });
}

function textoComoRegex(texto) {
    return new RegExp(String(texto).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
}

test('E05 permite selecionar categoria por teclado sem transformar o link de detalhes em botão aninhado', async ({ page }) => {
    await entrarComoAdmin(page);
    const dados = await dadosEdicao(page);
    const nome = String(dados.categoria.nome_categoria);
    const escapar = textoComoRegex(nome);
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(`edicoes/categorias?id=${dados.edicao}&modo=create`, { waitUntil: 'domcontentloaded' });

    const selecao = page.getByRole('button', { name: `Selecionar categoria: ${nome}`, exact: true });
    await expect(selecao).toBeVisible();
    await expect(selecao).toHaveAttribute('aria-pressed', 'false');
    await expect(selecao).toContainText('Selecionar categoria');
    await expect(selecao.locator('div,h1,h2,h3,h4,h5,h6')).toHaveCount(0);
    await selecao.focus();
    await page.keyboard.press('Enter');
    await expect(selecao).toHaveAttribute('aria-pressed', 'true');

    const continuar = page.getByRole('link', { name: 'Continuar' }).last();
    await expect(continuar).toHaveAttribute('href', new RegExp(`id=${dados.edicao}.*id_categoria=${dados.categoria.id_categoria}.*modo=create`));
    const detalhes = page.getByRole('link', { name: `VER DETALHES · ${nome}`, exact: true });
    await expect(detalhes).toBeVisible();
    await expect(detalhes).toHaveAttribute('href', new RegExp(`id=${dados.edicao}.*id_categoria=${dados.categoria.id_categoria}`));
    await expect(selecao.locator('a')).toHaveCount(0);

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`edicoes/categorias?id=${dados.edicao}&modo=create`, { waitUntil: 'domcontentloaded' });
    const selecaoMobile = page.getByRole('button', { name: `Selecionar categoria: ${nome}`, exact: true });
    await expect(selecaoMobile).toBeVisible();
    await expect(selecaoMobile).toContainText(nome);
    await expect(selecaoMobile.locator('h1,h2,h3,h4,h5,h6')).toHaveCount(0);
});

test('E05 expõe seleção de modalidade no modo criação e preserva o link no modo consulta', async ({ page }) => {
    await entrarComoAdmin(page);
    const dados = await dadosEdicao(page);
    const nome = String(dados.modalidade.nome_modalidade);
    const escapar = textoComoRegex(nome);
    const caminho = `edicoes/modalidades?id=${dados.edicao}&id_categoria=${dados.categoria.id_categoria}`;

    await page.goto(`${caminho}&modo=create`, { waitUntil: 'domcontentloaded' });
    const selecao = page.getByRole('button', { name: `Selecionar modalidade: ${nome}`, exact: true });
    await expect(selecao).toBeVisible();
    await expect(selecao).toHaveAttribute('aria-pressed', 'false');
    await expect(selecao).toContainText('Selecionar modalidade');
    await expect(selecao.locator('div,h1,h2,h3,h4,h5,h6')).toHaveCount(0);
    await selecao.focus();
    await page.keyboard.press('Space');
    await expect(selecao).toHaveAttribute('aria-pressed', 'true');
    await expect(page.getByRole('link', { name: 'Continuar' })).toHaveAttribute('href', new RegExp(`id=${dados.edicao}.*id_modalidade=${dados.modalidade.id_modalidade}`));

    await page.goto(caminho, { waitUntil: 'domcontentloaded' });
    await expect(page.getByRole('link', { name: `Ver detalhes de ${nome}` })).toBeVisible();
});

test('E05 informa o limite real do regulamento e a restrição adicional do PHP/servidor', async ({ page }) => {
    await entrarComoAdmin(page);
    const dados = await dadosEdicao(page);
    await page.goto(`edicoes/locais?id=${dados.edicao}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#modalRegulamento').evaluate((element) => {
        bootstrap.Modal.getOrCreateInstance(element).show();
    });

    const dialog = page.getByRole('dialog', { name: /Atualizar Regulamento \(PDF\)/i });
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText(/20\s*(?:MB|MiB)/i);
    await expect(dialog).toContainText(/limites? (?:reais )?do PHP|PHP\/servidor|servidor.*PHP/i);
});
