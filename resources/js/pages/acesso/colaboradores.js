window.SGIPage.mount("acesso/colaboradores", function (pageConfig, pageScope) {

    const usuarioEhAdmin = pageConfig.value2;
    const API_BASE = String(window.SGI_API_BASE || `${window.SGI_BASE_PATH || ''}/api/v1/`).replace(/\/?$/, '/');
    const BASE_PATH = String(window.SGI_BASE_PATH || '').replace(/\/$/, '');
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');
    let colaboradoresData = [];
    let filtroNivelAtual = 'todos';
    let buscaAtual = '';
    let colaboradoresCarregados = false;
    let erroColaboradores = false;
    let carregandoColaboradores = false;

    function definirVisibilidadeSenha(inputId, visivel) {
        const input = document.getElementById(inputId);
        const toggle = document.querySelector(`[data-password-target="${inputId}"]`);
        if (!input || !toggle) return;

        const texto = visivel ? 'Ocultar senha' : 'Mostrar senha';
        input.type = visivel ? 'text' : 'password';
        toggle.setAttribute('aria-label', texto);
        toggle.setAttribute('aria-pressed', visivel ? 'true' : 'false');
        toggle.title = texto;
        const label = toggle.querySelector('[data-password-label]');
        if (label) label.textContent = texto;
        const icon = toggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-eye', !visivel);
            icon.classList.toggle('bi-eye-slash', visivel);
        }
    }

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        pageScope.listen(toggle, 'click', () => {
            const targetId = toggle.getAttribute('data-password-target');
            const input = targetId ? document.getElementById(targetId) : null;
            if (input) definirVisibilidadeSenha(targetId, input.type === 'password');
        });
    });

    pageScope.listen(document.getElementById('modalAdicionarColaborador'), 'show.bs.modal', () => {
        definirVisibilidadeSenha('novaSenhaColaborador', false);
    });

    (async () => {
        const ic = idInterclasseColab
            ? await window.SGIInterclasse.getInterclasseById(idInterclasseColab)
            : await window.SGIInterclasse.getActiveInterclasse();
        if (ic) {
            ['nomeInterclasseColabMobile', 'nomeInterclasseColabDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = ic?.nome_interclasse || 'Interclasse';
            });
            ['btnVoltarColabMobile', 'btnVoltarColabDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.href = `${BASE_PATH}/painel?id=${encodeURIComponent(ic.id_interclasse)}`;
            });
        }
    })();

    let editColaboradorId = null;

    function roleClass(nivel) {
        const map = { '0': 'admin', '1': 'colab', '2': 'mesario' };
        return map[String(nivel)] || 'colab';
    }

    function roleName(nivel) {
        const map = { '0': 'Administrador', '1': 'Colaborador', '2': 'Mesário' };
        return map[String(nivel)] || 'Colaborador';
    }

    function roleIcon(nivel) {
        const map = { '0': 'bi-shield-fill', '1': 'bi-person', '2': 'bi-clipboard-check' };
        return map[String(nivel)] || 'bi-person';
    }

    function avatarInitial(nome) {
        return (nome || '?').charAt(0).toUpperCase();
    }

    function cardColaborador(item) {
        const rc = roleClass(item.nivel_usuario);
        const rn = roleName(item.nivel_usuario);
        const ri = roleIcon(item.nivel_usuario);
        const nivel = String(item.nivel_usuario);

        const roleBadge = {admin: 'text-bg-danger', mesario: 'text-bg-primary', colab: 'text-bg-secondary'}[rc] || 'text-bg-secondary';

        return `
            <div class="col">
                <article class="card h-100 border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3" data-id="${item.id_usuario}" data-nivel="${nivel}">
                    <div class="rounded-3 bg-body-secondary text-body d-inline-flex align-items-center justify-content-center flex-shrink-0 p-3 fs-5 fw-bold">${avatarInitial(item.nome_usuario)}</div>
                    <div class="flex-grow-1 sgi-u-min-width-0">
                        <p class="fw-semibold mb-1 text-truncate">${esc(item.nome_usuario)}</p>
                        <div class="d-flex align-items-center gap-2 flex-wrap small text-body-secondary">
                            <span><i class="bi bi-hash me-1"></i>${esc(item.matricula_usuario || '')}</span>
                            <span class="badge rounded-pill ${roleBadge}"><i class="bi ${ri} me-1"></i>${rn}</span>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-editar="${item.id_usuario}" title="Editar" aria-label="Editar colaborador ${escAttr(item.nome_usuario)}"><i class="bi bi-pencil"></i></button>
                        ${nivel !== '0' ? `<button type="button" class="btn btn-outline-danger btn-sm" data-remover="${item.id_usuario}" title="Excluir" aria-label="Excluir colaborador ${escAttr(item.nome_usuario)}"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </article>
            </div>`;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function escAttr(s) {
        return esc(s).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function renderizarEstatisticas(lista) {
        const total = lista.length;
        const admins = lista.filter(c => String(c.nivel_usuario) === '0').length;
        const mesarios = lista.filter(c => String(c.nivel_usuario) === '2').length;
        const colabs = lista.filter(c => String(c.nivel_usuario) === '1').length;

        const ids = [
            ['statTotalMob', 'statTotalDesk'],
            ['statAdminMob', 'statAdminDesk'],
            ['statMesarioMob', 'statMesarioDesk'],
            ['statColabMob', 'statColabDesk']
        ];
        const valores = [total, admins, mesarios, colabs];

        ids.forEach(([mob, desk], index) => {
            const m = document.getElementById(mob);
            const d = document.getElementById(desk);
            if (m) m.textContent = valores[index];
            if (d) d.textContent = valores[index];
        });
    }

    function montarFiltros(lista) {
        const niveis = [...new Set(lista.map(c => String(c.nivel_usuario)))].sort();
        const nomes = { '0': 'Administrador', '1': 'Colaborador', '2': 'Mesário' };
        ['filtrosMob', 'filtrosDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = `<button class="btn btn-sm ${filtroNivelAtual === 'todos' ? 'btn-primary' : 'btn-outline-primary'}" data-filtro="todos" aria-pressed="${filtroNivelAtual === 'todos'}">Todos</button>`;
            niveis.forEach(n => {
                const selecionado = filtroNivelAtual === n;
                el.innerHTML += `<button class="btn btn-sm ${selecionado ? 'btn-primary' : 'btn-outline-primary'}" data-filtro="${n}" aria-pressed="${selecionado}">${nomes[n] || 'Nível ' + n}</button>`;
            });
            el.querySelectorAll('[data-filtro]').forEach(chip => {
                pageScope.listen(chip, 'click', () => {
                    filtroNivelAtual = chip.dataset.filtro;
                    el.querySelectorAll('[data-filtro]').forEach(c => {
                        c.classList.toggle('btn-primary', c === chip);
                        c.classList.toggle('btn-outline-primary', c !== chip);
                        c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
                    });
                    aplicarFiltros();
                });
            });
        });
    }

    function aplicarFiltros() {
        let lista = [...colaboradoresData];
        if (filtroNivelAtual !== 'todos') {
            lista = lista.filter(c => String(c.nivel_usuario) === filtroNivelAtual);
        }
        if (buscaAtual) {
            const b = buscaAtual.toLowerCase();
            lista = lista.filter(c =>
                (c.nome_usuario || '').toLowerCase().includes(b) ||
                (c.matricula_usuario || '').toLowerCase().includes(b) ||
                roleName(c.nivel_usuario).toLowerCase().includes(b)
            );
        }

        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        if (erroColaboradores && !colaboradoresCarregados) {
            const markup = '<div class="col-12 text-center py-5" role="alert"><i class="bi bi-exclamation-triangle text-danger fs-1 d-block mb-3" aria-hidden="true"></i><p class="text-danger mb-3">Não foi possível carregar os colaboradores.</p><button type="button" class="btn btn-outline-primary" data-retry-colaboradores>Tentar novamente</button></div>';
            if (desk) desk.innerHTML = markup;
            if (mob) mob.innerHTML = markup;
            vincularEventosLista();
            return;
        }

        const avisoErro = erroColaboradores
            ? '<div class="col-12" role="alert"><div class="alert alert-warning d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3"><span>Não foi possível atualizar os colaboradores. Os dados carregados permanecem visíveis.</span><button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" data-retry-colaboradores>Tentar novamente</button></div></div>'
            : '';
        let conteudo;
        if (lista.length > 0) {
            conteudo = lista.map(cardColaborador).join('');
        } else if (colaboradoresData.length > 0 || buscaAtual || filtroNivelAtual !== 'todos') {
            conteudo = `<div class="col-12 text-center text-body-secondary py-5" role="status" tabindex="-1"><i class="bi bi-search fs-1 d-block mb-3 text-body-tertiary" aria-hidden="true"></i><p class="mb-3">Nenhum colaborador corresponde à busca ou aos filtros.</p><button type="button" class="btn btn-outline-primary" data-clear-colaboradores>Limpar busca e filtros</button></div>`;
        } else {
            conteudo = `<div class="col-12 text-center text-body-secondary py-5" role="status" tabindex="-1"><i class="bi bi-people fs-1 d-block mb-3 text-body-tertiary" aria-hidden="true"></i><p class="mb-3">Ainda não há colaboradores cadastrados.</p>${usuarioEhAdmin ? '<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Adicionar colaborador</button>' : ''}</div>`;
        }
        const html = avisoErro + conteudo;

        if (desk) desk.innerHTML = html;
        if (mob) mob.innerHTML = html;
        vincularEventosLista();
    }

    async function carregarColaboradores() {
        if (carregandoColaboradores) return;
        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        const carregavaAntes = colaboradoresCarregados;
        const conteinerComFoco = [desk, mob].find((lista) => lista?.contains(document.activeElement));
        carregandoColaboradores = true;
        if (!carregavaAntes) {
            const loading = '<div class="col-12 text-center text-body-secondary py-5" role="status"><div class="spinner-border text-primary me-2" aria-hidden="true"></div>Carregando colaboradores...</div>';
            if (desk) desk.innerHTML = loading;
            if (mob) mob.innerHTML = loading;
        }
        [desk, mob].forEach((lista) => lista?.setAttribute('aria-busy', 'true'));

        try {
            const response = await fetch(`${API_BASE}usuarios?acao=listar_colaboradores`);
            const resultado = await response.json();
            if (!response.ok || !resultado || resultado.status !== 'sucesso' || !Array.isArray(resultado.colaboradores)) {
                throw new Error('Resposta inválida ao carregar os colaboradores.');
            }
            const lista = resultado.colaboradores;
            colaboradoresData = lista;
            colaboradoresCarregados = true;
            erroColaboradores = false;
            renderizarEstatisticas(lista);
            montarFiltros(lista);
            aplicarFiltros();
            if (conteinerComFoco) {
                const destino = conteinerComFoco.querySelector('[data-editar], [data-bs-toggle="modal"], [data-clear-colaboradores], [role="status"]');
                destino?.focus({ preventScroll: true });
            }
        } catch (error) {
            console.error(error);
            erroColaboradores = true;
            if (carregavaAntes) {
                aplicarFiltros();
            } else {
                const msg = '<div class="col-12 text-center py-5" role="alert"><i class="bi bi-exclamation-triangle text-danger fs-1 d-block mb-3" aria-hidden="true"></i><p class="text-danger mb-3">Não foi possível carregar os colaboradores.</p><button type="button" class="btn btn-outline-primary" data-retry-colaboradores>Tentar novamente</button></div>';
                if (desk) desk.innerHTML = msg;
                if (mob) mob.innerHTML = msg;
            }
            if (conteinerComFoco) conteinerComFoco.querySelector('[data-retry-colaboradores]')?.focus({ preventScroll: true });
        } finally {
            carregandoColaboradores = false;
            [desk, mob].forEach((lista) => lista?.setAttribute('aria-busy', 'false'));
        }
    }

    function tratarAcoesLista(event) {
        const retry = event.target.closest('[data-retry-colaboradores]');
        if (retry) {
            event.preventDefault();
            carregarColaboradores();
            return;
        }
        const limpar = event.target.closest('[data-clear-colaboradores]');
        if (!limpar) return;
        event.preventDefault();
        filtroNivelAtual = 'todos';
        buscaAtual = '';
        ['buscaColabDesk', 'buscaColabMob'].forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.value = '';
        });
        montarFiltros(colaboradoresData);
        aplicarFiltros();
        document.getElementById(window.matchMedia('(min-width: 768px)').matches ? 'buscaColabDesk' : 'buscaColabMob')?.focus();
    }

    pageScope.listen(document.getElementById('listaColaboradoresDesktop'), 'click', tratarAcoesLista);
    pageScope.listen(document.getElementById('listaColaboradoresMobile'), 'click', tratarAcoesLista);

    function vincularEventosLista() {
        document.querySelectorAll('[data-remover]').forEach((btn) => {
            pageScope.listen(btn, 'click', async () => {
                if (!await SGI.confirm({ titulo: 'Remover colaborador?', mensagem: 'O colaborador será removido do acesso ao SGI.', textoConfirmar: 'Remover', destrutivo: true })) return;
                const id = btn.getAttribute('data-remover');
                const body = new URLSearchParams();
                body.append('acao', 'excluir_colaborador');
                body.append('id_usuario', id);

                const resp = await fetch(`${API_BASE}usuarios`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const json = await resp.json();
                if (json.status !== 'sucesso') {
                    SGI.alert(json.mensagem || 'Erro ao remover.');
                    return;
                }
                await carregarColaboradores();
            });
        });

        document.querySelectorAll('[data-editar]').forEach((btn) => {
            pageScope.listen(btn, 'click', () => {
                const id = Number(btn.getAttribute('data-editar'));
                const colab = colaboradoresData.find(c => c.id_usuario === id);
                if (!colab) return;

                editColaboradorId = id;
                document.getElementById('editNomeColaborador').value = colab.nome_usuario || '';
                document.getElementById('editNifColaborador').value = colab.matricula_usuario || '';
                document.getElementById('editSenhaColaborador').value = '';
                definirVisibilidadeSenha('editSenhaColaborador', false);
                document.getElementById('editGeneroColaborador').value = colab.genero_usuario || 'MASC';
                document.getElementById('msgEditarColaborador').innerHTML = '';

                const modal = new bootstrap.Modal(document.getElementById('modalEditarColaborador'));
                modal.show();
            });
        });
    }

    pageScope.listen(document.getElementById('buscaColabDesk'), 'input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabMob').value = e.target.value;
        aplicarFiltros();
    });
    pageScope.listen(document.getElementById('buscaColabMob'), 'input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabDesk').value = e.target.value;
        aplicarFiltros();
    });

    pageScope.listen(document.getElementById('formNovoColaborador'), 'submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarColaborador');
        const msg = document.getElementById('msgNovoColaborador');
        const nome = document.getElementById('novoNomeColaborador').value.trim();
        const matricula = document.getElementById('novoNifColaborador').value.trim();
        const senha = document.getElementById('novaSenhaColaborador').value.trim();
        const adminInput = document.getElementById('novoAdminColaborador');
        const admin = adminInput ? adminInput.checked : false;

        // Validação extra de segurança no Front-end
        if (admin && !usuarioEhAdmin) {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">Apenas administradores podem cadastrar outros administradores.</p>';
            return;
        }

        const mesario = document.getElementById('novoMesarioColaborador').checked;
        const genero = document.getElementById('novoGeneroColaborador').value;

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            msg.innerHTML = '';

            const body = new URLSearchParams();
            body.append('acao', 'cadastrar_usuario');
            body.append('nome_usuario', nome);
            body.append('matricula_usuario', matricula);
            body.append('senha_usuario', senha);
            body.append('data_nasc_usuario', '2000-01-01');
            body.append('is_admin_clicado', admin ? '1' : '0');
            body.append('is_mesario_clicado', mesario ? '1' : '0');
            body.append('sigla_usuario', 'SS');
            body.append('genero_usuario', genero);

            const response = await fetch(`${API_BASE}usuarios`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao cadastrar.');

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador cadastrado com sucesso.</p>';
            document.getElementById('formNovoColaborador').reset();
            definirVisibilidadeSenha('novaSenhaColaborador', false);
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalAdicionarColaborador')).hide(), 700);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${esc(error.message)}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Cadastrar';
        }
    });

    pageScope.listen(document.getElementById('formEditarColaborador'), 'submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarEdicaoColaborador');
        const msg = document.getElementById('msgEditarColaborador');

        const nome = document.getElementById('editNomeColaborador').value.trim();
        const matricula = document.getElementById('editNifColaborador').value.trim();
        const senha = document.getElementById('editSenhaColaborador').value.trim();
        const genero = document.getElementById('editGeneroColaborador').value;

        if (!nome || !matricula) {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">Nome e matrícula são obrigatórios.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            msg.innerHTML = '';

            const body = new URLSearchParams();
            body.append('acao', 'atualizar_dados_colaborador');
            body.append('id_usuario', String(editColaboradorId));
            body.append('nome_usuario', nome);
            body.append('matricula_usuario', matricula);
            body.append('genero_usuario', genero);
            if (senha) body.append('senha_usuario', senha);

            const response = await fetch(`${API_BASE}usuarios`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao atualizar.');

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador atualizado.</p>';
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalEditarColaborador')).hide(), 700);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${esc(error.message)}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Salvar';
        }
    });

    window.SGIPage.ready( carregarColaboradores);

return {roleClass, roleName, roleIcon, avatarInitial, cardColaborador, esc, renderizarEstatisticas, montarFiltros, aplicarFiltros, carregarColaboradores, vincularEventosLista};
});
