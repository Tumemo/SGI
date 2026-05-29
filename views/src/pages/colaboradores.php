<?php
$titulo = "Colaboradores";
$textTop = "Colaboradores";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .colaborador-card {
        border: 1px solid #ececec;
        border-left: 4px solid #ed1c24;
    }

    .colaborador-switch.form-check-input:checked {
        background-color: #ed1c24;
        border-color: #ed1c24;
    }
</style>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <a href="#" data-sgi-header-back="true" class="btn btn-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left-circle"></i> Voltar
        </a>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Equipe de apoio</h5>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-circle"></i> Adicionar</button>
        </div>
        <div id="listaColaboradoresMobile">
            <p class="text-muted text-center">(Carregando colaboradores...)</p>
        </div>
        <a href="#" id="btnContinuarColaboradoresMobile" class="btn btn-danger w-100 mt-3 d-none">Continuar</a>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 980px;">
        <a href="#" data-sgi-header-back="true" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Colaboradores</h4>
            <div class="d-flex gap-2">
                <a href="#" id="btnContinuarColaboradoresDesktop" class="btn btn-danger d-none">Continuar</a>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-circle"></i> Adicionar colaborador</button>
            </div>
        </div>
        <div id="listaColaboradoresDesktop">
            <p class="text-muted text-center">(Carregando colaboradores...)</p>
        </div>
    </div>
</main>

<div class="modal fade" id="modalAdicionarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Adicionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="novoNifColaborador" required>
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
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoAdminColaborador">
                        <label class="form-check-label" for="novoAdminColaborador">Administrador</label>
                    </div>

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
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarColaborador">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger">Editar colaborador</h5>
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
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoColaborador">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');
    let colaboradoresData = [];
    let editColaboradorId = null;

    function cardColaborador(item) {
        const admin = String(item.nivel_usuario) !== '0';
        const mesario = String(item.mesario_usuario) === '1';
        return `
            <div class="colaborador-card bg-white rounded-3 shadow-sm p-3 mb-3" data-id="${item.id_usuario}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1">${item.nome_usuario}</h6>
                        <small class="text-muted">Matrícula: ${item.matricula_usuario}</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-editar="${item.id_usuario}"><i class="bi bi-pencil-square"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remover="${item.id_usuario}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                <div class="d-flex gap-4 mt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input colaborador-switch" type="checkbox" data-papel="admin" data-id="${item.id_usuario}" ${admin ? 'checked' : ''}>
                        <label class="form-check-label">Administrador</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input colaborador-switch" type="checkbox" data-papel="mesario" data-id="${item.id_usuario}" ${mesario ? 'checked' : ''}>
                        <label class="form-check-label">Mesário</label>
                    </div>
                </div>
            </div>
        `;
    }

    async function carregarColaboradores() {
        const alvo = [
            document.getElementById('listaColaboradoresDesktop'),
            document.getElementById('listaColaboradoresMobile')
        ];
        try {
            const response = await fetch('../../../api/usuarios.php?acao=listar_colaboradores');
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao listar colaboradores.');
            }
            const lista = resultado.colaboradores || [];
            colaboradoresData = lista;
            const html = lista.length ?
                lista.map(cardColaborador).join('') :
                '<p class="text-muted text-center">Nenhum colaborador cadastrado.</p>';
            alvo.forEach((el) => {
                if (el) el.innerHTML = html;
            });
            vincularEventosLista();
        } catch (error) {
            alvo.forEach((el) => {
                if (el) el.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
            });
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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
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

        document.querySelectorAll('.colaborador-switch').forEach((input) => {
            input.addEventListener('change', async () => {
                const id = input.getAttribute('data-id');
                const papel = input.getAttribute('data-papel');
                const card = input.closest('.colaborador-card');
                const adminEl = card?.querySelector('[data-papel="admin"]');
                const mesarioEl = card?.querySelector('[data-papel="mesario"]');
                const body = new URLSearchParams();
                body.append('acao', 'atualizar_colaborador');
                body.append('id_usuario', id);
                body.append('is_admin_clicado', adminEl?.checked ? '1' : '0');
                body.append('is_mesario_clicado', mesarioEl?.checked ? '1' : '0');
                const resp = await fetch('../../../api/usuarios.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: body.toString()
                });
                const json = await resp.json();
                if (json.status !== 'sucesso') {
                    alert(json.mensagem || 'Erro ao atualizar.');
                    await carregarColaboradores();
                }
            });
        });
    }

    document.getElementById('formNovoColaborador').addEventListener('submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarColaborador');
        const msg = document.getElementById('msgNovoColaborador');
        const nome = document.getElementById('novoNomeColaborador').value.trim();
        const matricula = document.getElementById('novoNifColaborador').value.trim();
        const senha = document.getElementById('novaSenhaColaborador').value.trim();
        const admin = document.getElementById('novoAdminColaborador').checked;
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
            body.append('competidor_usuario', '0');

            const response = await fetch('../../../api/usuarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao cadastrar.');
            }

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador cadastrado com sucesso.</p>';
            document.getElementById('formNovoColaborador').reset();
            setTimeout(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAdicionarColaborador')).hide(), 700);
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
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao atualizar.');
            }

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador atualizado.</p>';
            setTimeout(() => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarColaborador')).hide(), 700);
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
require_once '../componentes/footer.php';
?>