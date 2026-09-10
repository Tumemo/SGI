const { test, expect, request: playwrightRequest } = require('./fixtures.cjs');
const { agendarBloco } = require('./agenda-helper.cjs');

async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

async function capturarTela(page, testInfo, nome) {
    const caminho = testInfo.outputPath(`${nome}.png`);
    await page.screenshot({ path: caminho, fullPage: true });
    await testInfo.attach(`${nome}.png`, { path: caminho, contentType: 'image/png' });
}

async function criarPartidaFixture(request) {
    const adminLogin = await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' }
    });
    await jsonOrThrow(adminLogin, 'login administrativo');

    const interclassesResponse = await request.get('api/v1/edicoes?regulamento=true');
    const interclasses = await jsonOrThrow(interclassesResponse, 'edições');
    const edicao = interclasses.find((item) => String(item.status_interclasse) === '1');
    if (!edicao) throw new Error('Nenhuma edição ativa disponível para o teste visual.');
    const idInterclasse = Number(edicao.id_interclasse);

    const [equipesResponse, modalidadesResponse] = await Promise.all([
        request.get(`api/v1/equipes?id_interclasse=${idInterclasse}`),
        request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`)
    ]);

    const equipes = await jsonOrThrow(equipesResponse, 'equipes');
    const modalidades = await jsonOrThrow(modalidadesResponse, 'modalidades');

    const modalidade = modalidades.find((item) =>
        String(item.nome_tipo_modalidade || '').toLowerCase().includes('mata') &&
        String(item.nome_modalidade || '').toLowerCase().includes('futsal')
    );
    if (!modalidade) throw new Error('Nenhuma modalidade de futsal mata-mata disponível.');

    const equipesDaModalidade = equipes.filter((item) =>
        String(item.modalidades_id_modalidade) === String(modalidade.id_modalidade)
    );
    if (equipesDaModalidade.length < 2) {
        throw new Error('A modalidade do fixture precisa de duas equipes.');
    }

    const equipe1 = equipesDaModalidade[0];
    const equipe2 = equipesDaModalidade.find((item) => String(item.id_equipe) !== String(equipe1.id_equipe)) || equipesDaModalidade[1];

    const nomeJogo = `E2E Visual Offline ${Date.now()}`;
    // Cria um atleta efêmero pela própria API administrativa e faz a inscrição
    // real no fluxo do portal. Assim o modal de artilharia tem dados locais
    // suficientes para ser exercitado visualmente.
    const matriculaAtleta = String(900000000 + (Date.now() % 100000));
    const alunoResponse = await request.post('api/v1/usuarios?acao=criar_aluno', {
        data: {
            nome_usuario: 'Atleta E2E Offline',
            matricula_usuario: matriculaAtleta,
            genero_usuario: 'MASC',
            data_nasc_usuario: '2008-01-01',
            turmas_id_turma: Number(equipe1.turmas_id_turma)
        }
    });
    const aluno = await jsonOrThrow(alunoResponse, 'criação do atleta fixture');
    if (aluno.status !== 'sucesso') {
        throw new Error(`criação do atleta fixture: ${aluno.mensagem || JSON.stringify(aluno)}`);
    }
    const senhaAtleta = String(aluno.senha_temporaria || '');
    if (senhaAtleta === '') throw new Error('A API não retornou a senha temporária do atleta fixture.');

    const alunoApi = await playwrightRequest.newContext({
        baseURL: process.env.SGI_BASE_URL || 'http://localhost/SGI/'
    });
    try {
        const alunoLogin = await alunoApi.post('api/v1/login', {
            data: { matricula: matriculaAtleta, senha: senhaAtleta }
        });
        await jsonOrThrow(alunoLogin, 'login do atleta fixture');
        await jsonOrThrow(await alunoApi.post('api/v1/termos', { data: {} }), 'aceite dos termos do atleta fixture');
        const inscricao = await alunoApi.post('api/v1/inscricoes', {
            data: {
                id_interclasse: idInterclasse,
                id_equipes: [Number(equipe1.id_equipe)]
            }
        });
        const inscricaoPayload = await jsonOrThrow(inscricao, 'inscrição do atleta fixture');
        if (inscricaoPayload.success === false) {
            throw new Error(`inscrição do atleta fixture: ${inscricaoPayload.message || JSON.stringify(inscricaoPayload)}`);
        }
    } finally {
        await alunoApi.dispose();
    }

    // Esta rota de sincronização também cria as linhas de partidas, algo que a
    // tela de agendamento deixa para o gerador de chaveamento.
    const jogoResponse = await request.post('api/v1/sincronizacao/chaveamento', {
        data: {
            id_modalidade: Number(modalidade.id_modalidade),
            tipo_modalidade: 'mata_mata',
            jogos: [{
                nome_jogo: nomeJogo,
                status_jogo: 'Agendado',
                partidas: [
                    { id_equipe: Number(equipe1.id_equipe), resultado: 0 },
                    { id_equipe: Number(equipe2.id_equipe), resultado: 0 }
                ]
            }]
        }
    });
    await jsonOrThrow(jogoResponse, 'criação do jogo fixture');
    const jogosResponse = await request.get(`api/v1/jogos?id_modalidade=${Number(modalidade.id_modalidade)}`);
    const jogos = await jsonOrThrow(jogosResponse, 'consulta do jogo fixture');
    const jogo = jogos.find((item) => String(item.nome_jogo) === nomeJogo);
    const idJogo = Number(jogo && jogo.id_jogo);
    if (!idJogo) throw new Error(`A API não retornou o ID do jogo: ${JSON.stringify(jogo)}`);
    await agendarBloco(request, {
        idInterclasse,
        idModalidade: Number(modalidade.id_modalidade),
        jogos: [{ id_jogo: idJogo }],
        label: 'E2E-visual-offline',
    });

    return {
        idJogo,
        nomeJogo,
        idModalidade: Number(modalidade.id_modalidade),
        idInterclasse
    };
}

test.describe('Mesário — fluxo visual completo offline', () => {
    test('prepara, opera, enfileira e sincroniza uma partida sem rede', async ({ page, context, request }, testInfo) => {
        test.setTimeout(180_000);
        const fixture = await criarPartidaFixture(request);
        const dialogs = [];
        const pageErrors = [];
        page.on('pageerror', (error) => pageErrors.push(error.message));
        page.on('dialog', async (dialog) => {
            dialogs.push(dialog.message());
            await dialog.accept();
        });

        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();
        await expect(page.locator('#form_desktop h2')).toHaveText('Acesso ao sistema');
        await capturarTela(page, testInfo, '01-login');

        await page.locator('#form_desktop .ipt-matricula').fill('mesario');
        await page.locator('#form_desktop .ipt-senha').fill('123');
        await page.locator('#form_desktop button[type="submit"]').click();
        await page.waitForURL(/\/painel\?id=\d+/, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).not.toContainText('Download parcial');

        // O preload é sequencial por desenho: aguardamos o indicador verde que
        // confirma que as telas e os dados do confronto estão no IndexedDB.
        await expect(page.locator('#sgi-offline-ok')).toBeVisible({ timeout: 120_000 });
        await expect(page.locator('#sgi-offline-ok')).toContainText('Pronto para uso offline');
        await capturarTela(page, testInfo, '02-pronto-offline');

        await context.setOffline(true);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(false);
        await expect(page.locator('#sgi-offline-banner')).toContainText('OFFLINE');

        // A navegação abaixo é SPA e deve sair do cache de tela, sem request.
        await page.locator('#linkAgenda:visible').first().click();
        await expect(page.locator('#lista-eventos')).toBeVisible();
        await expect(page.locator('#lista-eventos .ag-event-card').filter({ hasText: 'E2E Visual Offline' }).first()).toBeVisible();
        await capturarTela(page, testInfo, '03-agenda-offline');

        const fixtureCard = page.locator('#lista-eventos .ag-event-card').filter({ hasText: fixture.nomeJogo }).first();
        await expect(fixtureCard).toBeVisible();
        await fixtureCard.locator('.iniciar-jogo-btn').click();
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending)).toBeGreaterThan(0);

        // O botão de placar nasce depois que o estado local passa a Iniciado.
        await expect(fixtureCard.locator('a[href*="jogos/placar"]')).toBeVisible();
        await fixtureCard.locator('a[href*="jogos/placar"]').click();
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await expect(page.locator('#placar-grid')).toBeVisible();
        await expect(page.locator('#mc-status-badge')).toContainText('Em andamento');

        // Registra um ponto somente depois de escolher o atleta. A tentativa
        // vazia não pode alterar o placar.
        await page.locator('.btn-score-plus').first().click();
        await expect(page.locator('#modalArtilheiro')).toBeVisible();
        await expect(page.locator('.score-number').first()).toHaveText('00');
        await page.locator('#btnSalvarArtilheiro').click();
        await expect(page.locator('#msgArtilheiro')).toContainText('Selecione o aluno responsável pela jogada', { timeout: 10_000 });
        await expect(page.locator('.score-number').first()).toHaveText('00');
        await expect.poll(() => page.locator('#selectAlunoArtilheiro option').count()).toBeGreaterThan(1);
        await page.locator('#selectAlunoArtilheiro').selectOption({ index: 1 });
        await page.locator('#btnSalvarArtilheiro').click();
        await expect(page.locator('#msgArtilheiro')).toContainText('Ponto registrado', { timeout: 10_000 });
        await expect(page.locator('#modalArtilheiro')).toBeHidden({ timeout: 10_000 });
        await expect(page.locator('.score-number').first()).toHaveText('01');

        // Registra uma ocorrência disciplinar usando as listas locais.
        await page.locator('#btnNovaOcorrencia').click();
        await expect(page.locator('#modalOcorrencia')).toBeVisible();
        await page.locator('label[data-tipo="Amarelo"]').click();
        await expect.poll(() => page.locator('#filtroTurmaOcorrencia option').count()).toBeGreaterThan(1);
        await page.locator('#filtroTurmaOcorrencia').selectOption({ index: 1 });
        await expect(page.locator('#selectAlunoOcorrencia')).toBeEnabled();
        await expect.poll(() => page.locator('#selectAlunoOcorrencia option').count()).toBeGreaterThan(1);
        await page.locator('#selectAlunoOcorrencia').selectOption({ index: 1 });
        await page.locator('#penalidadeOcorrencia').selectOption('2');
        await page.locator('#descricaoOcorrencia').fill('Registro visual offline');
        await page.locator('#btnSalvarOcorrencia').click();
        await expect(page.locator('#msgOcorrencia')).toContainText('Ocorrência registrada', { timeout: 10_000 });
        await expect(page.locator('#modalOcorrencia')).toBeHidden({ timeout: 10_000 });

        // Finaliza 1x0 localmente: a UI muda imediatamente e a mesma mutação
        // fica na fila para o servidor, junto com início, placar e ocorrência.
        await page.locator('button.mc-action-btn--finish').click();
        await expect(page.locator('#mc-status-badge')).toContainText('Encerrado');
        await expect(page.locator('#sgi-offline-banner')).toContainText('alteracao', { timeout: 10_000 });
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 20_000 }).toBeGreaterThanOrEqual(4);
        await expect.poll(() => page.evaluate(async (id) => {
            const jogos = await window.SGIDataLayer.read('jogos');
            return jogos.find((jogo) => Number(jogo.id_jogo) === Number(id))?.status_jogo || null;
        }, fixture.idJogo)).toBe('Concluido');
        await expect.poll(() => page.evaluate(async (id) => {
            const jogos = await window.SGIDataLayer.read('jogos');
            return Number(jogos.find((jogo) => Number(jogo.id_jogo) === Number(id))?.modalidades_id_modalidade || 0);
        }, fixture.idJogo)).toBe(fixture.idModalidade);
        await expect.poll(() => page.evaluate(async (id) => {
            const response = await fetch(`/api/v1/jogos?id_jogo=${id}`);
            const jogos = await response.json();
            return jogos[0]?.status_jogo || null;
        }, fixture.idJogo)).toBe('Concluido');
        await expect.poll(() => page.evaluate(async ({ idJogo, idModalidade }) => {
            const response = await fetch(`/api/v1/jogos?id_modalidade=${idModalidade}`);
            const jogos = await response.json();
            return jogos.find((jogo) => Number(jogo.id_jogo) === Number(idJogo))?.status_jogo || null;
        }, fixture)).toBe('Concluido');
        await capturarTela(page, testInfo, '04-partida-finalizada-offline');

        // Regressão: assim que o placar mostra "Encerrado", a persistência
        // local já precisa estar concluída. Ao voltar para a agenda ainda sem
        // rede, o mesmo jogo deve aparecer como concluído e nunca oferecer o
        // botão de iniciar novamente.
        await page.locator('#btnVoltarPlacar').click();
        await expect(page.locator('#lista-eventos')).toBeVisible();
        const fixtureConcluido = page.locator('#lista-eventos .ag-event-card').filter({ hasText: fixture.nomeJogo }).first();
        await expect(fixtureConcluido).toBeVisible();
        await expect(fixtureConcluido.locator('.ag-status-chip')).toContainText('Concluído');
        await expect(fixtureConcluido.locator('.iniciar-jogo-btn')).toHaveCount(0);
        await expect(fixtureConcluido.getByRole('link', { name: /Ver resultado/i })).toBeVisible();
        await capturarTela(page, testInfo, '05-agenda-concluida-offline');

        // Abrir novamente é permitido somente em modo de consulta; a tela não
        // pode reativar cronômetro nem oferecer nova finalização.
        await fixtureConcluido.getByRole('link', { name: /Ver resultado/i }).click();
        await expect(page.locator('#placar-conteudo')).toBeVisible();
        await expect(page.locator('#mc-status-badge')).toContainText('Encerrado');
        await expect(page.locator('button.mc-action-btn--finish')).toHaveCount(0);
        expect(pageErrors).toEqual([]);

        const offlineState = await page.evaluate(() => window.SGIOffline.getState());
        expect(offlineState.online).toBe(false);
        expect(offlineState.pending).toBeGreaterThanOrEqual(4);

        // Reconexão: o evento online dispara a sincronização automática.
        await context.setOffline(false);
        await expect.poll(() => page.evaluate(() => navigator.onLine)).toBe(true);
        await expect.poll(() => page.evaluate(() => window.SGIOffline.getState().pending), { timeout: 45_000 }).toBe(0);
        await expect(page.locator('#sgi-offline-banner')).toHaveClass(/sgi-hidden/);
        await expect(page.locator('#artilheiro-cards')).toContainText('Atleta E2E Offline', { timeout: 10_000 });
        await expect(page.locator('#artilheiro-cards')).toContainText('1 gol', { timeout: 10_000 });
        await expect(page.locator('#lista-ocorrencias')).toContainText('Registro visual offline', { timeout: 10_000 });

        // O perfil mesário consulta apenas a fila operacional; após concluído,
        // o jogo sai deliberadamente dessa fila. A auditoria final usa o
        // contexto administrativo do fixture para consultar o registro completo.
        const servidorResponse = await request.get(`api/v1/jogos?id_jogo=${fixture.idJogo}`);
        const servidor = await jsonOrThrow(servidorResponse, 'consulta administrativa do jogo sincronizado');
        expect(servidor[0].status_jogo).toMatch(/Concluido|Finalizado/);
        expect(Number(servidor[0].id_jogo)).toBe(fixture.idJogo);
        const artilhariaServidor = await page.evaluate(async (id) => {
            const response = await fetch(`/api/v1/artilheiros?id_jogo=${id}`);
            return response.json();
        }, fixture.idJogo);
        expect(artilhariaServidor.some((item) => Number(item.total_gols || item.num_gol) >= 1)).toBeTruthy();
        const ocorrenciasServidor = await page.evaluate(async (id) => {
            const response = await fetch(`/api/v1/ocorrencias?id_jogo=${id}`);
            return response.json();
        }, fixture.idJogo);
        expect(ocorrenciasServidor.some((item) => /Registro visual offline/i.test(item.descricao_ocorrencia || ''))).toBeTruthy();
        expect(dialogs.some((message) => /Jogo encerrado offline/i.test(message))).toBeTruthy();
        expect(dialogs.some((message) => /erro|falha/i.test(message))).toBeFalsy();
        expect(pageErrors).toEqual([]);

        await capturarTela(page, testInfo, '06-sincronizado-online');
    });
});
