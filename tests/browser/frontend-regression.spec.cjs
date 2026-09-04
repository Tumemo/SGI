const { test, expect } = require('@playwright/test');

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

async function obterContexto(request) {
    await jsonOrThrow(await request.post('api/login.php', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login de preparação do frontend');

    const edicoes = await jsonOrThrow(
        await request.get('api/interclasse.php?regulamento=true'),
        'edições do frontend'
    );
    const listaEdicoes = Array.isArray(edicoes) ? edicoes : [];
    let edicao = listaEdicoes.find((item) => String(item.status_interclasse) === '1') || listaEdicoes[0];
    if (!edicao) throw new Error('Nenhuma edição disponível para a regressão visual.');

    let idInterclasse = Number(edicao.id_interclasse);
    let [categorias, modalidades, turmas, equipes, jogos] = await Promise.all([
        jsonOrThrow(await request.get(`api/categorias.php?id_interclasse=${idInterclasse}`), 'categorias do frontend'),
        jsonOrThrow(await request.get(`api/modalidades.php?id_interclasse=${idInterclasse}`), 'modalidades do frontend'),
        jsonOrThrow(await request.get(`api/turmas.php?id_interclasse=${idInterclasse}`), 'turmas do frontend'),
        jsonOrThrow(await request.get(`api/equipes.php?id_interclasse=${idInterclasse}`), 'equipes do frontend'),
        jsonOrThrow(await request.get(`api/jogos.php?id_interclasse=${idInterclasse}`), 'jogos do frontend')
    ]);

    let listaJogos = Array.isArray(jogos) ? jogos : [];
    if (!listaJogos.some((j) => Number(j.id_jogo) > 0)) {
        for (const outra of listaEdicoes) {
            if (Number(outra.id_interclasse) === idInterclasse) continue;
            const fallbackJogos = await jsonOrThrow(await request.get(`api/jogos.php?id_interclasse=${outra.id_interclasse}`), 'jogos de fallback');
            if (Array.isArray(fallbackJogos) && fallbackJogos.some((j) => Number(j.id_jogo) > 0)) {
                edicao = outra;
                idInterclasse = Number(outra.id_interclasse);
                await request.post(`api/interclasse.php?id=${idInterclasse}`, {
                    data: { status_interclasse: '1' }
                });
                [categorias, modalidades, turmas, equipes, jogos] = await Promise.all([
                    jsonOrThrow(await request.get(`api/categorias.php?id_interclasse=${idInterclasse}`), 'categorias do frontend'),
                    jsonOrThrow(await request.get(`api/modalidades.php?id_interclasse=${idInterclasse}`), 'modalidades do frontend'),
                    jsonOrThrow(await request.get(`api/turmas.php?id_interclasse=${idInterclasse}`), 'turmas do frontend'),
                    jsonOrThrow(await request.get(`api/equipes.php?id_interclasse=${idInterclasse}`), 'equipes do frontend'),
                    Promise.resolve(fallbackJogos)
                ]);
                listaJogos = fallbackJogos;
                break;
            }
        }
    }

    const listaModalidades = Array.isArray(modalidades) ? modalidades : [];
    const listaTurmas = Array.isArray(turmas) ? turmas : [];
    const listaEquipes = Array.isArray(equipes) ? equipes : [];
    const modalidade = listaModalidades.find((item) => Number(item.id_modalidade) > 0) || {};
    const equipe = listaEquipes.find((item) => Number(item.id_equipe) > 0 && Number(item.turmas_id_turma || item.id_turma) > 0) || {};
    const turma = listaTurmas.find((item) => Number(item.id_turma) > 0) || {};
    const jogo = listaJogos.find((item) => Number(item.id_jogo) > 0) || {};
    const idTurma = Number(equipe.turmas_id_turma || equipe.id_turma || turma.id_turma || 0);

    // Cria um competidor efêmero para que a suíte não dependa do estado de um
    // RM importado por uma execução anterior (ou que tenha sido desativado).
    const matriculaAluno = `98${Date.now().toString().slice(-7)}`;
    if (idTurma > 0) {
        const aluno = await jsonOrThrow(await request.post('api/usuarios.php?acao=criar_aluno', {
            data: {
                nome_usuario: 'Aluno Frontend E2E',
                matricula_usuario: matriculaAluno,
                genero_usuario: 'MASC',
                data_nasc_usuario: '2010-01-01',
                turmas_id_turma: idTurma
            }
        }), 'competidor efêmero do frontend');
        if (aluno.status !== 'sucesso') throw new Error(`Não foi possível criar o competidor visual: ${JSON.stringify(aluno)}`);
    }

    return {
        idInterclasse,
        idCategoria: Number((equipe.categorias_id_categoria || modalidade.categorias_id_categoria || listaTurmas[0]?.categorias_id_categoria || categorias[0]?.id_categoria) || 0),
        idModalidade: Number(equipe.modalidades_id_modalidade || modalidade.id_modalidade || 0),
        idTurma,
        idEquipe: Number(equipe.id_equipe || 0),
        idJogo: Number(jogo.id_jogo || 0),
        matriculaAluno,
        categorias: Array.isArray(categorias) ? categorias : [],
        modalidades: listaModalidades,
        turmas: listaTurmas,
        equipes: listaEquipes,
        jogos: listaJogos
    };
}

async function entrar(page, matricula) {
    await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill(String(matricula));
    await page.locator('#form_desktop .ipt-senha').fill('123');
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');
    await expect(page).not.toHaveURL(/views\/index\.php(?:\?|$)/, { timeout: 15_000 });
}

async function validarTela(page, testInfo, nome, caminho, seletores = ['main']) {
    await page.goto(caminho, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).not.toContainText(/Fatal error|Parse error|Warning:|Call to undefined/i);
    await page.waitForLoadState('networkidle', { timeout: 8_000 }).catch(() => {});

    await expect.poll(async () => {
        for (const seletor of seletores) {
            const visivel = await page.locator(seletor).evaluateAll((elementos) => elementos.some((el) => {
                const estilo = window.getComputedStyle(el);
                const caixa = el.getBoundingClientRect();
                return estilo.display !== 'none' && estilo.visibility !== 'hidden' && caixa.width > 0 && caixa.height > 0;
            })).catch(() => false);
            if (visivel) return true;
        }
        return false;
    }, { timeout: 15_000 }).toBe(true);

    await page.waitForTimeout(350);
    await capturarTela(page, testInfo, nome);
}

async function validarTelaMesario(page, testInfo, nome, callback, seletores = ['main']) {
    await page.evaluate(callback);
    await expect.poll(async () => {
        for (const seletor of seletores) {
            const visivel = await page.locator(seletor).evaluateAll((elementos) => elementos.some((el) => {
                const estilo = window.getComputedStyle(el);
                const caixa = el.getBoundingClientRect();
                return estilo.display !== 'none' && estilo.visibility !== 'hidden' && caixa.width > 0 && caixa.height > 0;
            })).catch(() => false);
            if (visivel) return true;
        }
        return false;
    }, { timeout: 20_000 }).toBe(true);
    await page.waitForTimeout(350);
    await capturarTela(page, testInfo, nome);
}

function ouvirErros(page) {
    const erros = [];
    page.on('pageerror', (error) => erros.push(error.message));
    return erros;
}

async function capturarTela(page, testInfo, nome) {
    const caminho = testInfo.outputPath('screens', `${nome}.png`);
    await page.screenshot({ path: caminho, fullPage: true });
    await testInfo.attach(`${nome}.png`, { path: caminho, contentType: 'image/png' });
}

test.describe('Frontend — regressão visual por perfil', () => {
    test('tela pública de login em desktop e mobile', async ({ page }, testInfo) => {
        const erros = ouvirErros(page);
        await page.goto('views/index.php', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();
        await expect(page.locator('#form_desktop .ipt-matricula')).toBeVisible();
        await capturarTela(page, testInfo, '01-login-desktop');

        await page.setViewportSize({ width: 390, height: 844 });
        await expect(page.locator('#form_mobile')).toBeVisible();
        await capturarTela(page, testInfo, '02-login-mobile');
        expect(erros).toEqual([]);
    });

    test('todas as telas administrativas com dados reais da edição ativa', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'admin');

        const base = 'views/src/pages/';
        const id = ctx.idInterclasse;
        const idCat = ctx.idCategoria;
        const idMod = ctx.idModalidade;
        const idTurma = ctx.idTurma;
        const idEquipe = ctx.idEquipe;
        const idJogo = ctx.idJogo;
        const telas = [
            ['03-admin-home', `${base}home.php`, ['#listaDesktop']],
            ['04-admin-dashboard', `${base}dashboard.php?id=${id}`, ['#conteudo-principal']],
            ['05-admin-resumo', `${base}edicao_resumo.php?id=${id}&modo=view`, ['#resumoModalidadesDesktop']],
            ['06-admin-categorias', `${base}categorias.php?id=${id}`, ['#listaCategoriasDesktop']],
            ['07-admin-edicao-categorias', `${base}edicao_categorias.php?id=${id}&modo=view`, ['#listaCategoriasDesktop']],
            ['08-admin-modalidades', `${base}modalidades.php?id=${id}`, ['#listaModalidadesDesktop']],
            ['09-admin-edicao-modalidades', `${base}edicao_modalidades.php?id=${id}&modo=view`, ['#listaModalidadesDesktop']],
            ['10-admin-modalidade-detalhes', `${base}modalidade_detalhes.php?id=${id}&id_modalidade=${idMod}`, ['#resumoModalidadeDesktop']],
            ['11-admin-pontuacao', `${base}edicao_pontuacao.php?id=${id}&modo=view`, ['#btnSalvarPontuacao']],
            ['12-admin-locais', `${base}edicao_locais.php?id=${id}`, ['#listaLocaisDesktop']],
            ['13-admin-agenda', `${base}edicao_agenda.php?id=${id}&modo=view`, ['#lista-eventos']],
            ['14-admin-arrecadacao', `${base}edicao_arrecadacao.php?id=${id}`, ['#listaArrecadacaoDesktop']],
            ['15-admin-turmas', `${base}turmas.php?id=${id}`, ['#listaTurmasDesktop']],
            ['16-admin-edicao-turmas', `${base}edicao_turmas.php?id=${id}`, ['#listaTurmas']],
            ['17-admin-turma-alunos', `${base}turma_alunos.php?id=${id}&id_turma=${idTurma}&id_categoria=${idCat}`, ['#tbodyAlunosTurmaDesk']],
            ['18-admin-equipes', `${base}edicao_equipes.php?id=${id}`, ['#listaEquipesDesktop']],
            ['19-admin-elenco', `${base}elenco_equipe.php?id=${id}&id_turma=${idTurma}&id_equipe=${idEquipe}&id_categoria=${idCat}&id_modalidade=${idMod}`, ['#tbodyElencoDesk']],
            ['20-admin-equipe-alunos', `${base}equipe_alunos.php?id=${id}&id_turma=${idTurma}&id_equipe=${idEquipe}&id_categoria=${idCat}&id_modalidade=${idMod}`, ['#listaAlunosDesktop']],
            ['21-admin-colaboradores', `${base}colaboradores.php?id=${id}&modo=view`, ['#listaColaboradoresDesktop']],
            ['22-admin-ocorrencias', `${base}ocorrencias.php?id=${id}`, ['#listaOcorrenciasDesktop']],
            ['23-admin-ranking', `${base}ranking.php?id=${id}`, ['#listaDesk']],
            ['24-admin-chaveamento', `${base}chaveamento_arvore.php?id=${id}`, ['#bracketArea', '#tbodyJogos']],
            ['25-admin-jogos-lista', `${base}jogos_lista.php?id=${id}`, ['#listaJogos']],
            ['26-admin-placar', `${base}jogos.php?id_jogo=${idJogo}`, ['#placar-grid']]
        ];

        for (const [nome, caminho, seletores] of telas) {
            if (caminho.includes('id_modalidade=0') || caminho.includes('id_turma=0') || caminho.includes('id_equipe=0') || caminho.includes('id_jogo=0')) {
                throw new Error(`Contexto incompleto para a tela ${nome}: ${caminho}`);
            }
            await validarTela(page, testInfo, nome, caminho, seletores);
        }
        expect(erros).toEqual([]);
    });

    test('telas do colaborador e permissões de navegação', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'colab');
        const base = 'views/src/pages/';
        const id = ctx.idInterclasse;
        const telas = [
            ['27-colab-home', `${base}home.php`, ['#listaDesktop']],
            ['28-colab-dashboard', `${base}dashboard.php?id=${id}`, ['#conteudo-principal']],
            ['29-colab-modalidades', `${base}modalidades.php?id=${id}`, ['#listaModalidadesDesktop']],
            ['30-colab-pontuacao', `${base}edicao_pontuacao.php?id=${id}&modo=view`, ['#btnSalvarPontuacao']],
            ['31-colab-locais', `${base}edicao_locais.php?id=${id}`, ['#listaLocaisDesktop']],
            ['32-colab-agenda', `${base}edicao_agenda.php?id=${id}&modo=view`, ['#lista-eventos']],
            ['33-colab-arrecadacao', `${base}edicao_arrecadacao.php?id=${id}`, ['#listaArrecadacaoDesktop']],
            ['34-colab-ocorrencias', `${base}ocorrencias.php?id=${id}`, ['#listaOcorrenciasDesktop']],
            ['35-colab-categorias', `${base}categorias.php?id=${id}`, ['#listaCategoriasDesktop']],
            ['36-colab-turmas', `${base}turmas.php?id=${id}`, ['#listaTurmasDesktop']],
            ['37-colab-equipes', `${base}edicao_equipes.php?id=${id}`, ['#listaEquipesDesktop']],
            ['38-colab-ranking', `${base}ranking.php?id=${id}`, ['#listaDesk']],
            ['39-colab-chaveamento', `${base}chaveamento_arvore.php?id=${id}`, ['#bracketArea', '#tbodyJogos']],
            ['40-colab-perfil', `${base}perfil.php`, ['#perfilNomeInfo']]
        ];
        for (const [nome, caminho, seletores] of telas) {
            await validarTela(page, testInfo, nome, caminho, seletores);
        }

        // O colaborador não deve receber os controles exclusivos de admin.
        await page.goto(`${base}edicao_modalidades.php?id=${id}&modo=view`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('button[data-bs-target="#exampleModal"]:visible')).toHaveCount(0);
        await capturarTela(page, testInfo, '41-colab-modalidades-restritas');
        expect(erros).toEqual([]);
    });

    test('telas do mesário no shell SPA e cache pronto', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'mesario');
        await page.waitForURL(/dashboard\.php\?id=\d+/, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#sgi-offline-ok')).toContainText('Pronto para uso offline', { timeout: 120_000 });
        await expect.poll(() => page.evaluate(() => window.__SGI_SPA__ && window.__SGI_SPA__.status()), { timeout: 120_000 })
            .toMatchObject({ pronto: true, preloading: false });

        await capturarTela(page, testInfo, '42-mesario-dashboard');
        const id = ctx.idInterclasse;
        await validarTelaMesario(page, testInfo, '43-mesario-agenda', (interclasse) => {
            window.__SGI_SPA__.navegarPara('agenda', { id: interclasse });
        }, ['#lista-eventos', '#calendario-grade']);
        await validarTelaMesario(page, testInfo, '44-mesario-chaveamento', (interclasse) => {
            window.__SGI_SPA__.navegarPara('chaveamento', { id: interclasse });
        }, ['#bracketArea', '#tbodyJogos']);
        await validarTelaMesario(page, testInfo, '45-mesario-ocorrencias', (interclasse) => {
            window.__SGI_SPA__.navegarPara('ocorrencias', { id: interclasse });
        }, ['#listaOcorrenciasDesktop', '#listaOcorrenciasMobile']);
        await validarTelaMesario(page, testInfo, '46-mesario-jogos', (interclasse) => {
            window.__SGI_SPA__.navegarPara('jogoslista', { id: interclasse });
        }, ['#listaJogos']);
        await validarTelaMesario(page, testInfo, '47-mesario-perfil', () => {
            window.__SGI_SPA__.navegarPara('perfil', {});
        }, ['#perfilNomeInfo']);
        expect(erros).toEqual([]);
    });

    test('portal do aluno em todas as telas', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, ctx.matriculaAluno);
        const base = 'views/src/pages/alunos/';
        const id = ctx.idInterclasse;
        const telas = [
            ['48-aluno-home', `${base}home.php`, ['main', '.aluno-home']],
            ['49-aluno-modalidades', `${base}modalidade.php?id=${id}`, ['main', '#listaModalidades', '.aluno-page']],
            ['50-aluno-jogos', `${base}jogos.php?id=${id}`, ['main', '#listaJogos', '.aluno-page']],
            ['51-aluno-ranking', `${base}ranking.php?id=${id}`, ['main', '#listaDesk', '#listaMob', '.aluno-page']],
            ['52-aluno-termos', `${base}termos.php`, ['main', '#termosContainer', '.aluno-page']],
            ['53-aluno-perfil', `${base}perfil.php`, ['main', '#perfilNome', '.aluno-page']]
        ];
        for (const [nome, caminho, seletores] of telas) {
            await validarTela(page, testInfo, nome, caminho, seletores);
        }
        expect(erros).toEqual([]);
    });

    test('layout responsivo das telas críticas', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'admin');
        await page.setViewportSize({ width: 390, height: 844 });
        const id = ctx.idInterclasse;
        const telas = [
            ['54-mobile-dashboard', `views/src/pages/dashboard.php?id=${id}`, ['#conteudo-principal']],
            ['55-mobile-agenda', `views/src/pages/edicao_agenda.php?id=${id}&modo=view`, ['#lista-eventos-mobile', '#calendario-grade-mobile']],
            ['56-mobile-modalidades', `views/src/pages/edicao_modalidades.php?id=${id}&modo=view`, ['#listaModalidadesDesktop']],
            ['57-mobile-turmas', `views/src/pages/turmas.php?id=${id}`, ['#listaTurmasMobile']],
            ['58-mobile-ranking', `views/src/pages/ranking.php?id=${id}`, ['main']]
        ];
        for (const [nome, caminho, seletores] of telas) {
            await validarTela(page, testInfo, nome, caminho, seletores);
        }
        expect(erros).toEqual([]);
    });
});
