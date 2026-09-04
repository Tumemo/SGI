<?php
$tituloPagina = 'SGI - Colaboradores';
$titulo = 'Colaboradores';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'colaboradores';

// Verifica se o usuário logado é administrador
$usuarioEhAdmin = (isset($_SESSION['nivel']) && (string)$_SESSION['nivel'] === '0');
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none sgi-inline-5460531b" >
    <div class="col-wrap">
        <a href="./dashboard.php" id="btnVoltarColabMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseColabMobile">Interclasse</span>
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsMobile">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalMob">-</div><div class="col-stat__label">Usuários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminMob">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioMob">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabMob">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar sgi-inline-3b7b4abf" >
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabMob" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosMob">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresMobile">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout col-page">
    <div class="col-wrap">
        <a href="./dashboard.php" id="btnVoltarColabDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none sgi-inline-e1bcebb6" >
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseColabDesk">Interclasse</span>
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar colaborador
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsDesktop">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalDesk">-</div><div class="col-stat__label">Usuários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminDesk">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioDesk">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabDesk">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar">
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabDesk" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosDesk">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresDesktop">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ MODAL ADICIONAR ═══ -->
<div class="modal fade col-modal" id="modalAdicionarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus text-danger me-2"></i>Adicionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email / Matrícula</label>
                        <input type="text" class="form-control" id="novoNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="text" class="form-control" id="novaSenhaColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="novoGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>

                    <?php if ($usuarioEhAdmin): ?>
                    <!-- Restrição: Exibido apenas se o usuário logado for Admin -->
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoAdminColaborador">
                        <label class="form-check-label" for="novoAdminColaborador">Administrador</label>
                    </div>
                    <?php endif; ?>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoMesarioColaborador" checked>
                        <label class="form-check-label" for="novoMesarioColaborador">Mesário</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoColaborador">
                        <label class="form-check-label" for="novoColaborador">Colaborador</label>
                    </div>
                    <div id="msgNovoColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary sgi-inline-0d1f6728" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-danger sgi-inline-be2e418b" id="btnSalvarColaborador" >Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDITAR ═══ -->
<div class="modal fade col-modal" id="modalEditarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square text-danger me-2"></i>Editar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matrícula / NIF</label>
                        <input type="text" class="form-control" id="editNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova senha <small class="text-muted">(deixe em branco para manter)</small></label>
                        <input type="text" class="form-control" id="editSenhaColaborador">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="editGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>
                    <div id="msgEditarColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary sgi-inline-0d1f6728" data-bs-dismiss="modal" >Cancelar</button>
                        <button type="submit" class="btn btn-danger sgi-inline-be2e418b" id="btnSalvarEdicaoColaborador" >Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const usuarioEhAdmin = <?php echo json_encode($usuarioEhAdmin); ?>;
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');
    let colaboradoresData = [];
    let filtroNivelAtual = 'todos';
    let buscaAtual = '';

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
                if (el) el.href = `./dashboard.php?id=${ic.id_interclasse}`;
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

        return `
            <div class="col-card col-card--${rc}" data-id="${item.id_usuario}" data-nivel="${nivel}">
                <div class="col-avatar col-avatar--${rc}">${avatarInitial(item.nome_usuario)}</div>
                <div class="col-info">
                    <p class="col-info__name">${esc(item.nome_usuario)}</p>
                    <div class="col-info__meta">
                        <span class="col-info__detail"><i class="bi bi-hash"></i>${esc(item.matricula_usuario || '')}</span>
                        <span class="col-role col-role--${rc}"><i class="bi ${ri}"></i>${rn}</span>
                    </div>
                </div>
                <div class="col-actions">
                    <button type="button" class="col-action col-action--edit" data-editar="${item.id_usuario}" title="Editar"><i class="bi bi-pencil"></i></button>
                    ${nivel !== '0' ? `<button type="button" class="col-action col-action--delete" data-remover="${item.id_usuario}" title="Excluir"><i class="bi bi-trash"></i></button>` : ''}
                </div>
            </div>`;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
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
            el.innerHTML = '<button class="col-chip col-chip--active" data-filtro="todos">Todos</button>';
            niveis.forEach(n => {
                el.innerHTML += `<button class="col-chip" data-filtro="${n}">${nomes[n] || 'Nível ' + n}</button>`;
            });
            el.querySelectorAll('.col-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    filtroNivelAtual = chip.dataset.filtro;
                    el.querySelectorAll('.col-chip').forEach(c => c.classList.remove('col-chip--active'));
                    chip.classList.add('col-chip--active');
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

        const html = lista.length
            ? lista.map(cardColaborador).join('')
            : '<div class="col-empty"><i class="bi bi-people"></i><p>Nenhum colaborador encontrado.</p><button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-lg"></i> Adicionar colaborador</button></div>';

        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        if (desk) desk.innerHTML = html;
        if (mob) mob.innerHTML = html;
        vincularEventosLista();
    }

    async function carregarColaboradores() {
        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        const loading = '<div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>';
        if (desk) desk.innerHTML = loading;
        if (mob) mob.innerHTML = loading;

        try {
            const response = await fetch('../../../api/usuarios.php?acao=listar_colaboradores');
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao listar colaboradores.');
            const lista = resultado.colaboradores || [];
            colaboradoresData = lista;
            renderizarEstatisticas(lista);
            montarFiltros(lista);
            aplicarFiltros();
        } catch (error) {
            const msg = `<div class="col-empty"><i class="bi bi-exclamation-triangle"></i><p class="text-danger">${error.message}</p></div>`;
            if (desk) desk.innerHTML = msg;
            if (mob) mob.innerHTML = msg;
        }
    }

    function vincularEventosLista() {
        document.querySelectorAll('[data-remover]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!confirm('Remover este colaborador?')) return;
                const id = btn.getAttribute('data-remover');
                const body = new URLSearchParams();
                body.append('acao', 'excluir_colaborador');
                body.append('id_usuario', id);

                const resp = await fetch('../../../api/usuarios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const json = await resp.json();
                if (json.status !== 'sucesso') {
                    alert(json.mensagem || 'Erro ao remover.');
                    return;
                }
                await carregarColaboradores();
            });
        });

        document.querySelectorAll('[data-editar]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-editar'));
                const colab = colaboradoresData.find(c => c.id_usuario === id);
                if (!colab) return;

                editColaboradorId = id;
                document.getElementById('editNomeColaborador').value = colab.nome_usuario || '';
                document.getElementById('editNifColaborador').value = colab.matricula_usuario || '';
                document.getElementById('editSenhaColaborador').value = '';
                document.getElementById('editGeneroColaborador').value = colab.genero_usuario || 'MASC';
                document.getElementById('msgEditarColaborador').innerHTML = '';

                const modal = new bootstrap.Modal(document.getElementById('modalEditarColaborador'));
                modal.show();
            });
        });
    }

    document.getElementById('buscaColabDesk').addEventListener('input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabMob').value = e.target.value;
        aplicarFiltros();
    });
    document.getElementById('buscaColabMob').addEventListener('input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabDesk').value = e.target.value;
        aplicarFiltros();
    });

    document.getElementById('formNovoColaborador').addEventListener('submit', async (event) => {
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

            const response = await fetch('../../../api/usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao cadastrar.');

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador cadastrado com sucesso.</p>';
            document.getElementById('formNovoColaborador').reset();
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalAdicionarColaborador')).hide(), 700);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Cadastrar';
        }
    });

    document.getElementById('formEditarColaborador').addEventListener('submit', async (event) => {
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

            const response = await fetch('../../../api/usuarios.php', {
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
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Salvar';
        }
    });

    window.addEventListener('load', carregarColaboradores);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
