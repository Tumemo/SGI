<?php
$titulo = "Colaboradores";
$textTop = "Colaboradores";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .colaborador-card { border: 1px solid #ececec; border-left: 4px solid #ed1c24; }
    .colaborador-switch.form-check-input:checked { background-color: #ed1c24; border-color: #ed1c24; }
</style>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Equipe de apoio</h5>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-circle"></i> Adicionar</button>
        </div>
        <div id="listaColaboradoresMobile"><p class="text-muted text-center">(Carregando colaboradores...)</p></div>
        <a href="#" id="btnContinuarColaboradoresMobile" class="btn btn-danger w-100 mt-3 d-none">Continuar</a>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 980px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Colaboradores</h4>
            <div class="d-flex gap-2">
                <a href="#" id="btnContinuarColaboradoresDesktop" class="btn btn-danger d-none">Continuar</a>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-circle"></i> Adicionar colaborador</button>
            </div>
        </div>
        <div id="listaColaboradoresDesktop"><p class="text-muted text-center">(Carregando colaboradores...)</p></div>
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
                        <label class="form-label">Matrícula / NIF</label>
                        <input type="text" class="form-control" id="novoNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="text" class="form-control" id="novaSenhaColaborador" required>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="novoAdminColaborador" checked>
                        <label class="form-check-label" for="novoAdminColaborador">Administrador</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="novoMesarioColaborador" checked>
                        <label class="form-check-label" for="novoMesarioColaborador">Mesário</label>
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

<script>
    const STORAGE_KEY_COLABORADORES = 'sgi_colaboradores_locais';
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');
    const modoColab = paramsColab.get('modo');

    function obterColaboradores() {
        const raw = localStorage.getItem(STORAGE_KEY_COLABORADORES);
        if (raw) return JSON.parse(raw);
        
        localStorage.setItem(STORAGE_KEY_COLABORADORES, JSON.stringify(seed));
        return seed;
    }

    function salvarColaboradores(lista) {
        localStorage.setItem(STORAGE_KEY_COLABORADORES, JSON.stringify(lista));
    }

    function cardColaborador(item) {
        return `
            <div class="colaborador-card bg-white rounded-3 shadow-sm p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1">${item.nome}</h6>
                        <small class="text-muted">Matrícula: ${item.matricula}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removerColaborador('${item.id}')"><i class="bi bi-trash"></i></button>
                </div>
                <div class="d-flex gap-4 mt-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input colaborador-switch" type="checkbox" id="admin_${item.id}" ${item.admin ? 'checked' : ''} onchange="alternarPapel('${item.id}','admin', this.checked)">
                        <label class="form-check-label" for="admin_${item.id}">Administrador</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input colaborador-switch" type="checkbox" id="mesario_${item.id}" ${item.mesario ? 'checked' : ''} onchange="alternarPapel('${item.id}','mesario', this.checked)">
                        <label class="form-check-label" for="mesario_${item.id}">Mesário</label>
                    </div>
                </div>
            </div>
        `;
    }

    function renderizarColaboradores() {
        const lista = obterColaboradores();
        const html = lista.length
            ? lista.map(cardColaborador).join('')
            : '<p class="text-muted text-center">Nenhum colaborador cadastrado.</p>';
        document.getElementById('listaColaboradoresDesktop').innerHTML = html;
        document.getElementById('listaColaboradoresMobile').innerHTML = html;
    }

    window.alternarPapel = (id, chave, valor) => {
        const lista = obterColaboradores();
        const alvo = lista.find((item) => item.id === id);
        if (!alvo) return;
        alvo[chave] = !!valor;
        salvarColaboradores(lista);
    };

    window.removerColaborador = (id) => {
        const lista = obterColaboradores().filter((item) => item.id !== id);
        salvarColaboradores(lista);
        renderizarColaboradores();
    };

    document.getElementById('formNovoColaborador').addEventListener('submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarColaborador');
        const msg = document.getElementById('msgNovoColaborador');
        const nome = document.getElementById('novoNomeColaborador').value.trim();
        const matricula = document.getElementById('novoNifColaborador').value.trim();
        const senha = document.getElementById('novaSenhaColaborador').value.trim();
        const admin = document.getElementById('novoAdminColaborador').checked;
        const mesario = document.getElementById('novoMesarioColaborador').checked;

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

            const response = await fetch('../../../api/usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao cadastrar.');
            }

            const lista = obterColaboradores();
            lista.unshift({ id: `c_${Date.now()}`, nome, matricula, admin, mesario });
            salvarColaboradores(lista);
            renderizarColaboradores();
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

    window.addEventListener('load', renderizarColaboradores);
</script>

<?php
require_once '../componentes/footer.php';
?>
