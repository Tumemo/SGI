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

async function obterContexto(request, { criarCompetidor = true } = {}) {
    await jsonOrThrow(await request.post('api/v1/login', {
        data: { matricula: 'admin', senha: '123' }
    }), 'login de preparação do frontend');

    const edicoes = await jsonOrThrow(
        await request.get('api/v1/edicoes?regulamento=true'),
        'edições do frontend'
    );
    const listaEdicoes = Array.isArray(edicoes) ? edicoes : [];
    let edicao = listaEdicoes.find((item) => String(item.status_interclasse) === '1') || listaEdicoes[0];
    if (!edicao) throw new Error('Nenhuma edição disponível para a regressão visual.');

    let idInterclasse = Number(edicao.id_interclasse);
    let [categorias, modalidades, turmas, equipes, jogos] = await Promise.all([
        jsonOrThrow(await request.get(`api/v1/categorias?id_interclasse=${idInterclasse}`), 'categorias do frontend'),
        jsonOrThrow(await request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`), 'modalidades do frontend'),
        jsonOrThrow(await request.get(`api/v1/turmas?id_interclasse=${idInterclasse}`), 'turmas do frontend'),
        jsonOrThrow(await request.get(`api/v1/equipes?id_interclasse=${idInterclasse}`), 'equipes do frontend'),
        jsonOrThrow(await request.get(`api/v1/jogos?id_interclasse=${idInterclasse}`), 'jogos do frontend')
    ]);

    let listaJogos = Array.isArray(jogos) ? jogos : [];
    if (!listaJogos.some((j) => Number(j.id_jogo) > 0)) {
        for (const outra of listaEdicoes) {
            if (Number(outra.id_interclasse) === idInterclasse) continue;
            const fallbackJogos = await jsonOrThrow(await request.get(`api/v1/jogos?id_interclasse=${outra.id_interclasse}`), 'jogos de fallback');
            if (Array.isArray(fallbackJogos) && fallbackJogos.some((j) => Number(j.id_jogo) > 0)) {
                edicao = outra;
                idInterclasse = Number(outra.id_interclasse);
                await request.post(`api/v1/edicoes?id=${idInterclasse}`, {
                    data: { status_interclasse: '1' }
                });
                [categorias, modalidades, turmas, equipes, jogos] = await Promise.all([
                    jsonOrThrow(await request.get(`api/v1/categorias?id_interclasse=${idInterclasse}`), 'categorias do frontend'),
                    jsonOrThrow(await request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`), 'modalidades do frontend'),
                    jsonOrThrow(await request.get(`api/v1/turmas?id_interclasse=${idInterclasse}`), 'turmas do frontend'),
                    jsonOrThrow(await request.get(`api/v1/equipes?id_interclasse=${idInterclasse}`), 'equipes do frontend'),
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

    // Quando solicitado, cria um competidor efêmero para que a suíte não dependa
    // do estado de um RM importado por uma execução anterior ou desativado.
    const matriculaAluno = `98${Date.now().toString().slice(-7)}`;
    let senhaAluno = '';
    let idAluno = 0;
    if (criarCompetidor && idTurma > 0) {
        const aluno = await jsonOrThrow(await request.post('api/v1/usuarios?acao=criar_aluno', {
            data: {
                nome_usuario: 'Aluno Frontend E2E',
                matricula_usuario: matriculaAluno,
                genero_usuario: 'MASC',
                data_nasc_usuario: '2010-01-01',
                turmas_id_turma: idTurma
            }
        }), 'competidor efêmero do frontend');
        if (aluno.status !== 'sucesso') throw new Error(`Não foi possível criar o competidor visual: ${JSON.stringify(aluno)}`);
        idAluno = Number(aluno.id_usuario || 0);
        senhaAluno = String(aluno.senha_temporaria || '');
        if (senhaAluno === '') throw new Error('A API não retornou a senha temporária do competidor visual.');
    }

    return {
        idInterclasse,
        idCategoria: Number((equipe.categorias_id_categoria || modalidade.categorias_id_categoria || listaTurmas[0]?.categorias_id_categoria || categorias[0]?.id_categoria) || 0),
        idModalidade: Number(equipe.modalidades_id_modalidade || modalidade.id_modalidade || 0),
        idTurma,
        idEquipe: Number(equipe.id_equipe || 0),
        idJogo: Number(jogo.id_jogo || 0),
        idAluno,
        matriculaAluno,
        senhaAluno,
        categorias: Array.isArray(categorias) ? categorias : [],
        modalidades: listaModalidades,
        turmas: listaTurmas,
        equipes: listaEquipes,
        jogos: listaJogos
    };
}

async function entrar(page, matricula, senha = '123') {
    await page.goto('login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#form_desktop')).toBeVisible();
    await page.locator('#form_desktop .ipt-matricula').fill(String(matricula));
    await page.locator('#form_desktop .ipt-senha').fill(String(senha));
    await page.locator('#form_desktop button[type="submit"]').click();
    await page.waitForLoadState('domcontentloaded');
    await expect(page).not.toHaveURL(/login(?:\?|$)/, { timeout: 15_000 });
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
        await page.goto('login', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#form_desktop')).toBeVisible();
        await expect(page.locator('#form_desktop .ipt-matricula')).toBeVisible();
        await capturarTela(page, testInfo, '01-login-desktop');

        await page.setViewportSize({ width: 390, height: 844 });
        await expect(page.locator('#form_mobile')).toBeVisible();
        await capturarTela(page, testInfo, '02-login-mobile');
        expect(erros).toEqual([]);
    });

    test('adicionar atletas funciona em página nova, com busca e seleção sincronizada', async ({ page, request }) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request, { criarCompetidor: false });
        const dadosModalidade = await jsonOrThrow(await request.get(
            `api/v1/modalidades?id_modalidade=${ctx.idModalidade}`,
        ), 'modalidade da regressão L12');
        const modalidadesEncontradas = Array.isArray(dadosModalidade) ? dadosModalidade : [dadosModalidade];
        const modalidade = modalidadesEncontradas.find((item) => Number(item.id_modalidade) === ctx.idModalidade);
        const genero = String(modalidade?.genero_modalidade || 'MISTO') === 'FEM' ? 'FEM' : 'MASC';
        const sufixo = Date.now().toString().slice(-7);
        const matriculas = [`96${sufixo}`, `97${sufixo}`];
        const alunosCriados = [];

        try {
            const atletasCriados = [];
            for (const [indice, matricula] of matriculas.entries()) {
                const aluno = await jsonOrThrow(await request.post('api/v1/usuarios?acao=criar_aluno', {
                    data: {
                        nome_usuario: `L12 atleta ${indice + 1}`,
                        matricula_usuario: matricula,
                        genero_usuario: genero,
                        data_nasc_usuario: '2010-02-03',
                        turmas_id_turma: ctx.idTurma,
                    },
                }), `atleta ${indice + 1} da regressão L12`);
                const idUsuario = Number(aluno.id_usuario || 0);
                if (idUsuario > 0) alunosCriados.push(idUsuario);
                expect(aluno.status).toBe('sucesso');
                expect(idUsuario).toBeGreaterThan(0);
                atletasCriados.push(aluno);
            }
            const [primeiro, segundo] = atletasCriados;

            const lista = await jsonOrThrow(await request.get(
                `api/v1/usuarios?acao=listar_competidores&id_turma=${ctx.idTurma}&genero=${genero}`,
            ), 'consulta dos atletas L12');
            const competidores = Array.isArray(lista.competidores) ? lista.competidores : [];
            const primeiroDaLista = competidores.find((item) => item.matricula_usuario === matriculas[0]);
            const segundoDaLista = competidores.find((item) => item.matricula_usuario === matriculas[1]);
            expect(primeiroDaLista?.id_usuario).toBe(Number(primeiro.id_usuario));
            expect(segundoDaLista?.id_usuario).toBe(Number(segundo.id_usuario));

            await entrar(page, 'admin');
            await page.goto(
                `equipes/alunos?id=${ctx.idInterclasse}&id_turma=${ctx.idTurma}&id_equipe=${ctx.idEquipe}&id_categoria=${ctx.idCategoria}&id_modalidade=${ctx.idModalidade}`,
                { waitUntil: 'domcontentloaded' },
            );
            const desktopList = page.locator('#listaAlunosDesktop');
            const mobileList = page.locator('#listaAlunosMobile');
            await expect(desktopList).toContainText(matriculas[0]);
            await expect(desktopList).toContainText(matriculas[1]);
            await expect(desktopList).not.toContainText('Erro ao carregar alunos.');
            await expect(desktopList.locator('img, svg[onload]')).toHaveCount(0);

            await page.locator('#buscaAlunosDesktop').fill(matriculas[0]);
            await expect(desktopList.locator('label')).toHaveCount(1);
            const firstDesktopCheck = desktopList.locator('label').filter({ hasText: matriculas[0] }).locator('input');
            await firstDesktopCheck.check();
            const firstMobileCheck = mobileList.locator('label').filter({ hasText: matriculas[0] }).locator('input');
            await expect(firstMobileCheck).toBeChecked();

            await page.setViewportSize({ width: 390, height: 844 });
            await page.locator('#buscaAlunosMobile').fill(matriculas[1]);
            await expect(mobileList.locator('label')).toHaveCount(1);
            const secondMobileCheck = mobileList.locator('label').filter({ hasText: matriculas[1] }).locator('input');
            await secondMobileCheck.check();
            const secondDesktopCheck = desktopList.locator('label').filter({ hasText: matriculas[1] }).locator('input');
            await expect(secondDesktopCheck).toBeChecked();

            await secondMobileCheck.uncheck();
            await expect(secondDesktopCheck).not.toBeChecked();
            await secondMobileCheck.check();
            await page.setViewportSize({ width: 1280, height: 900 });
            await secondDesktopCheck.uncheck();
            await expect(secondMobileCheck).not.toBeChecked();

            await page.locator('#buscaAlunosDesktop').fill(matriculas[0]);
            await expect(desktopList.locator('label')).toHaveCount(1);
            await expect(firstDesktopCheck).toBeChecked();

            const saveResponse = page.waitForResponse((response) =>
                response.url().includes('/api/v1/equipes') && response.request().method() === 'POST',
            );
            const saveRequest = page.waitForRequest((request) =>
                request.url().includes('/api/v1/equipes') && request.method() === 'POST',
            );
            await page.locator('#btnSalvarAlunosDesktop').click();
            const saved = await saveResponse;
            const submitted = await saveRequest;
            expect(saved.status()).toBe(200);
            expect(JSON.parse(submitted.postData() || '{}').usuarios).toEqual([Number(primeiro.id_usuario)]);
            await expect(page.locator('#btnSalvarAlunosDesktop')).toBeEnabled();
            await expect(page.locator('#buscaAlunosDesktop')).toHaveValue(matriculas[0]);
            await expect(desktopList.locator('label')).toHaveCount(1);
            await expect(desktopList).toContainText(matriculas[0]);
            await expect(desktopList).not.toContainText(matriculas[1]);

            const roster = await jsonOrThrow(await request.get(`api/v1/equipes?id_equipe=${ctx.idEquipe}`), 'equipe após adição L12');
            const rosterIds = roster.map((item) => Number(item.id_usuario));
            expect(rosterIds).toContain(Number(primeiro.id_usuario));
            expect(rosterIds).not.toContain(Number(segundo.id_usuario));
            expect(erros).toEqual([]);
        } finally {
            const errosLimpeza = [];
            for (const idUsuario of alunosCriados) {
                try {
                    await jsonOrThrow(await request.post('api/v1/equipes', {
                        data: { acao: 'remover_aluno', id_equipe: ctx.idEquipe, id_usuario: idUsuario },
                    }), 'limpeza do vínculo criado pela regressão L12');
                } catch (error) {
                    errosLimpeza.push(error);
                }
                try {
                    await jsonOrThrow(await request.post('api/v1/usuarios?acao=excluir_aluno', {
                        data: { id_usuario: idUsuario },
                    }), 'desativação do aluno criado pela regressão L12');
                } catch (error) {
                    errosLimpeza.push(error);
                }
            }
            if (errosLimpeza.length > 0) throw new Error(`Falha na limpeza L12: ${errosLimpeza.map((error) => error.message).join('; ')}`);
        }
    });

    test('todas as telas administrativas com dados reais da edição ativa', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'admin');

        const base = '';
        const id = ctx.idInterclasse;
        const idCat = ctx.idCategoria;
        const idMod = ctx.idModalidade;
        const idTurma = ctx.idTurma;
        const idEquipe = ctx.idEquipe;
        const idJogo = ctx.idJogo;
        const telas = [
            ['03-admin-home', `${base}edicoes`, ['#listaDesktop']],
            ['04-admin-dashboard', `${base}painel?id=${id}`, ['#conteudo-principal']],
            ['05-admin-resumo', `${base}edicoes/resumo?id=${id}&modo=view`, ['#resumoModalidadesDesktop']],
            ['06-admin-categorias', `${base}categorias?id=${id}`, ['#listaCategoriasDesktop']],
            ['07-admin-edicao-categorias', `${base}edicoes/categorias?id=${id}&modo=view`, ['#listaCategoriasDesktop']],
            ['08-admin-modalidades', `${base}modalidades?id=${id}`, ['#listaModalidadesDesktop']],
            ['09-admin-edicao-modalidades', `${base}edicoes/modalidades?id=${id}&modo=view`, ['#listaModalidadesDesktop']],
            ['10-admin-modalidade-detalhes', `${base}modalidades/detalhes?id=${id}&id_modalidade=${idMod}`, ['#resumoModalidadeDesktop']],
            ['11-admin-pontuacao', `${base}edicoes/pontuacao?id=${id}&modo=view`, ['#btnSalvarPontuacao']],
            ['12-admin-locais', `${base}edicoes/locais?id=${id}`, ['#listaLocaisDesktop']],
            ['13-admin-agenda', `${base}edicoes/agenda?id=${id}&modo=view`, ['#lista-eventos']],
            ['14-admin-arrecadacao', `${base}edicoes/arrecadacao?id=${id}`, ['#listaArrecadacaoDesktop']],
            ['15-admin-turmas', `${base}turmas?id=${id}`, ['#listaTurmasDesktop']],
            ['16-admin-edicao-turmas', `${base}edicoes/turmas?id=${id}`, ['#listaTurmas']],
            ['17-admin-turma-alunos', `${base}turmas/alunos?id=${id}&id_turma=${idTurma}&id_categoria=${idCat}`, ['#tbodyAlunosTurmaDesk']],
            ['18-admin-equipes', `${base}edicoes/equipes?id=${id}`, ['#listaEquipesDesktop']],
            ['19-admin-elenco', `${base}equipes/elenco?id=${id}&id_turma=${idTurma}&id_equipe=${idEquipe}&id_categoria=${idCat}&id_modalidade=${idMod}`, ['#tbodyElencoDesk']],
            ['20-admin-equipe-alunos', `${base}equipes/alunos?id=${id}&id_turma=${idTurma}&id_equipe=${idEquipe}&id_categoria=${idCat}&id_modalidade=${idMod}`, ['#listaAlunosDesktop']],
            ['21-admin-colaboradores', `${base}colaboradores?id=${id}&modo=view`, ['#listaColaboradoresDesktop']],
            ['22-admin-ocorrencias', `${base}ocorrencias?id=${id}`, ['#listaOcorrenciasDesktop']],
            ['23-admin-ranking', `${base}ranking?id=${id}`, ['#listaDesk']],
            ['24-admin-chaveamento', `${base}chaveamento?id=${id}`, ['#bracketArea', '#tbodyJogos']],
            ['25-admin-jogos-lista', `${base}jogos?id=${id}`, ['#listaJogos']],
            ['26-admin-placar', `${base}jogos/placar?id_jogo=${idJogo}`, ['#placar-grid']]
        ];

        for (const [nome, caminho, seletores] of telas) {
            if (caminho.includes('id_modalidade=0') || caminho.includes('id_turma=0') || caminho.includes('id_equipe=0') || caminho.includes('id_jogo=0')) {
                throw new Error(`Contexto incompleto para a tela ${nome}: ${caminho}`);
            }
            await validarTela(page, testInfo, nome, caminho, seletores);
            if (nome === '04-admin-dashboard') {
                await expect(page.locator('#avisoFinalizacaoInterclasse')).toBeHidden();
            }
        }

        await page.goto(`${base}edicoes/modalidades?id=${id}&modo=view`, { waitUntil: 'domcontentloaded' });
        const destaquesResponsePromise = page.waitForResponse((response) => {
            const url = response.url();
            return url.includes('/api/v1/artilheiros')
                && url.includes('acao=destaques_modalidades');
        });
        await page.locator('button[data-bs-target="#modalDestaques"]').click();
        const destaquesResponse = await destaquesResponsePromise;
        expect(destaquesResponse.status()).toBe(200);
        const destaquesPayload = await destaquesResponse.json();
        expect(destaquesPayload.success).toBe(true);
        await expect(page.locator('#modalDestaques')).toBeVisible();
        await expect(page.locator('#corpoDestaques')).not.toContainText('Erro ao carregar os destaques.');
        await capturarTela(page, testInfo, '27-admin-modalidades-destaques');
        expect(erros).toEqual([]);
    });

    test('telas do colaborador e permissões de navegação', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'colab');
        const base = '';
        const id = ctx.idInterclasse;
        const telas = [
            ['27-colab-home', `${base}edicoes`, ['#listaDesktop']],
            ['28-colab-dashboard', `${base}painel?id=${id}`, ['#conteudo-principal']],
            ['29-colab-modalidades', `${base}modalidades?id=${id}`, ['#listaModalidadesDesktop']],
            // Pontuação, locais e equipes são configurações administrativas;
            // o colaborador opera agenda/resultados, mas não altera o cadastro.
            ['30-colab-agenda', `${base}edicoes/agenda?id=${id}&modo=view`, ['#lista-eventos']],
            ['31-colab-arrecadacao', `${base}edicoes/arrecadacao?id=${id}`, ['#listaArrecadacaoDesktop']],
            ['32-colab-ocorrencias', `${base}ocorrencias?id=${id}`, ['#listaOcorrenciasDesktop']],
            ['33-colab-categorias', `${base}categorias?id=${id}`, ['#listaCategoriasDesktop']],
            ['34-colab-turmas', `${base}turmas?id=${id}`, ['#listaTurmasDesktop']],
            ['35-colab-ranking', `${base}ranking?id=${id}`, ['#listaDesk']],
            ['36-colab-chaveamento', `${base}chaveamento?id=${id}`, ['#bracketArea', '#tbodyJogos']],
            ['37-colab-perfil', `${base}perfil`, ['#perfilNomeInfo']]
        ];
        for (const [nome, caminho, seletores] of telas) {
            await validarTela(page, testInfo, nome, caminho, seletores);
        }

        // O colaborador não deve receber os controles exclusivos de admin.
        await page.goto(`${base}edicoes/modalidades?id=${id}&modo=view`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('button[data-bs-target="#exampleModal"]:visible')).toHaveCount(0);
        await capturarTela(page, testInfo, '41-colab-modalidades-restritas');
        expect(erros).toEqual([]);
    });

    test('telas do mesário no shell SPA e cache pronto', async ({ page, request }, testInfo) => {
        const erros = ouvirErros(page);
        const ctx = await obterContexto(request);
        await entrar(page, 'mesario');
        await page.waitForURL(/painel\?id=\d+/, { waitUntil: 'domcontentloaded' });
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
        await entrar(page, ctx.matriculaAluno, ctx.senhaAluno);
        const base = 'aluno/';
        const id = ctx.idInterclasse;
        const telas = [
            ['48-aluno-home', `${base}inicio`, ['main', '.aluno-home']],
            ['49-aluno-modalidades', `${base}modalidades?id=${id}`, ['main', '#listaModalidades', '.aluno-page']],
            ['50-aluno-jogos', `${base}jogos`, ['main', '#listaJogos', '.aluno-page']],
            ['51-aluno-ranking', `${base}ranking?id=${id}`, ['main', '#listaDesk', '#listaMob', '.aluno-page']],
            ['52-aluno-termos', `${base}termos`, ['main', '#termosContainer', '.aluno-page']],
            ['53-aluno-perfil', `${base}perfil`, ['main', '#perfilNome', '.aluno-page']]
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
            ['54-mobile-dashboard', `painel?id=${id}`, ['#conteudo-principal']],
            ['55-mobile-agenda', `edicoes/agenda?id=${id}&modo=view`, ['#lista-eventos-mobile', '#calendario-grade-mobile']],
            ['56-mobile-modalidades', `edicoes/modalidades?id=${id}&modo=view`, ['#listaModalidadesDesktop']],
            ['57-mobile-turmas', `turmas?id=${id}`, ['#listaTurmasMobile']],
            ['58-mobile-ranking', `ranking?id=${id}`, ['main']]
        ];
        for (const [nome, caminho, seletores] of telas) {
            await validarTela(page, testInfo, nome, caminho, seletores);
        }
        expect(erros).toEqual([]);
    });
});
