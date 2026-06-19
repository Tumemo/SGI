<?php
$tituloPagina = 'SGI - Colaborador - Colaboradores';
$titulo = 'Colaboradores';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '.colaborador-card { border: 1px solid #ececec; border-left: 4px solid #ed1c24; }';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'colaboradores';
?>

<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="container mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">Equipe de apoio</h5>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-circle"></i> Adicionar</button>
        </div>
        <div id="listaColaboradoresMobile">
            <p class="text-muted text-center">(Carregando colaboradores...)</p>
        </div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 980px;">
        <a href="./dashboard.php" class="btn btn-outline-danger btn-sm mb-3 d-inline-flex align-items-center gap-1 text-decoration-none">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Colaboradores</h4>
            <div class="d-flex gap-2">
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

<script>
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');

    (async () => {
        const ic = idInterclasseColab
            ? await window.SGIInterclasse.getInterclasseById(idInterclasseColab)
            : await window.SGIInterclasse.getActiveInterclasse();
        if (ic) {
            const el = document.querySelector('.btn-outline-danger');
            if (el) {
                el.href = `./dashboard.php?id=${ic.id_interclasse}`;
                el.innerHTML = `<i class="bi bi-arrow-left"></i> ${ic.nome_interclasse || 'Voltar'}`;
            }
        }
    })();

    function cardColaborador(item) {
        let legendaPapel = '';
        const nivel = String(item.nivel_usuario);

        if (nivel === '0') {
            legendaPapel = '<span class="badge bg-danger">Administrador</span>';
        } else if (nivel === '2') {
            legendaPapel = '<span class="badge bg-primary">Mesário</span>';
        } else {
            legendaPapel = '<span class="badge bg-secondary">Colaborador</span>';
        }

        return `
            <div class="colaborador-card bg-white rounded-3 shadow-sm p-3 mb-3" data-id="${item.id_usuario}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="fw-bold mb-1">${esc(item.nome_usuario)}</h6>
                        <small class="text-muted d-block mb-2">Matrícula: ${esc(item.matricula_usuario)}</small>
                        ${legendaPapel}
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
            const q = idInterclasseColab ? `&id_interclasse=${encodeURIComponent(idInterclasseColab)}` : '';
            const response = await fetch(`../../../../api/usuarios.php?acao=listar_colaboradores${q}`);
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao listar colaboradores.');
            }
            const lista = resultado.colaboradores || [];
            const html = lista.length ?
                lista.map(cardColaborador).join('') :
                '<p class="text-muted text-center">Nenhum colaborador cadastrado.</p>';
            alvo.forEach((el) => {
                if (el) el.innerHTML = html;
            });
        } catch (error) {
            alvo.forEach((el) => {
                if (el) el.innerHTML = `<p class="text-danger text-center">${error.message}</p>`;
            });
        }
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

            const response = await fetch('../../../../api/usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') {
                throw new Error(resultado.mensagem || 'Falha ao cadastrar.');
            }

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

    window.addEventListener('load', carregarColaboradores);
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
