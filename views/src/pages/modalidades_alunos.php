<?php
$titulo = "Modalidades / Alunos";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    /* Borda dourada suave para o aluno destaque */
    .borda-destaque {
        border: 2px solid #D4AF37 !important;
        transition: border 0.3s ease-in-out;
    }

    /* Cor para a estrela preenchida */
    .estrela-dourada {
        color: #D4AF37 !important;
    }
</style>

<main class="d-md-none" style="margin-bottom:120px;">
    <div class="container mt-3">
        <a href="#" data-sgi-header-back="true" class="btn btn-danger btn-sm mb-3 d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left-circle"></i> Voltar
        </a>
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <input type="text" id="buscaNomeMobile" class="form-control form-control-sm rounded-pill" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-filtro="limpar">Sem filtro</button>
        </div>
        <div id="listaAlunosMobile"></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="mx-auto" style="max-width: 720px;">
        <a href="#" data-sgi-header-back="true" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>
        <div class="d-flex gap-2 mb-4 overflow-auto pb-1 pt-2 ps-1" style="white-space: nowrap; scrollbar-width: none;">
            <input type="text" id="buscaNome" class="form-control form-control-sm rounded-pill p-2 ps-4" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
            <button type="button" class="btn btn-light btn-sm rounded-pill px-3" data-filtro="limpar">Sem filtro</button>
        </div>
        <div id="listaAlunosDesktop" class="d-flex flex-column gap-3">
            <p class="text-center text-muted">(Carregando alunos...)</p>
        </div>
    </div>
</main>

<div class="modal fade" id="modalRemoverAluno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width: 320px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center p-4 pt-5 pb-4">
                <h5 class="mb-4" style="font-size: 1.2rem; font-weight: 400; color: #000;">Deseja remover esse aluno?</h5>
                <div class="d-flex justify-content-center gap-3 mt-4">
                    <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">Não</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-dismiss="modal">Remover</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let alunosLista = [];

    function cardAluno(aluno, mobile = false) {
        const nome = aluno.nome_usuario || aluno.nome || `Aluno ${aluno.id_usuario || aluno.id}`;
        const id = aluno.id_usuario || aluno.id;
        const genero = (aluno.genero_usuario || 'MASC').toUpperCase();
        const classe = mobile ? 'mb-2' : 'mb-0';
        return `
            <div id="card-aluno-${id}${mobile ? '-m' : ''}" class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center aluno-card ${classe}" data-genero="${genero}" data-destaque="0" data-nome="${nome.toLowerCase()}">
                <h6 class="mb-0 fw-bold text-dark">${nome}</h6>
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#modalRemoverAluno"><i class="bi bi-trash"></i> Remover</button></li>
                        <li><button type="button" class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="alternarDestaque(${id})"><i id="icone-estrela-${id}" class="bi bi-star"></i> Destacar</button></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><button type="button" class="dropdown-item text-danger d-flex align-items-center gap-2 rounded-2" style="font-size: 0.85rem;" onclick="resetarSenha(${id})"><i class="bi bi-shield-lock"></i> Resetar senha</button></li>
                    </ul>
                </div>
            </div>
        `;
    }

    function alternarDestaque(idAluno) {
        document.querySelectorAll(`#card-aluno-${idAluno}, #card-aluno-${idAluno}-m`).forEach((card) => {
            if (!card) return;
            card.classList.toggle('borda-destaque');
            card.dataset.destaque = card.dataset.destaque === '1' ? '0' : '1';
        });
        const iconeEstrela = document.getElementById(`icone-estrela-${idAluno}`);
        if (iconeEstrela) {
            if (iconeEstrela.classList.contains('bi-star')) {
                iconeEstrela.classList.remove('bi-star');
                iconeEstrela.classList.add('bi-star-fill', 'estrela-dourada');
            } else {
                iconeEstrela.classList.remove('bi-star-fill', 'estrela-dourada');
                iconeEstrela.classList.add('bi-star');
            }
        }
    }

    function resetarSenha(idAluno) {
        if (confirm('Tem certeza que deseja resetar a senha deste aluno?')) {
            // Aqui você pode adicionar a lógica para resetar a senha via API
            alert('Senha resetada com sucesso!');
        }
    }

    const paramsAlunos = new URLSearchParams(window.location.search);
    const idTurmaAlunos = paramsAlunos.get('id_turma');

    let filtrosAtivos = { busca: '', genero: null, destaque: false };

    function sincronizarBusca(origem) {
        const valor = origem?.value ?? '';
        filtrosAtivos.busca = valor.toLowerCase();
        const outro = document.getElementById('buscaNome') && document.getElementById('buscaNomeMobile')
            ? (origem?.id === 'buscaNome' ? document.getElementById('buscaNomeMobile') : document.getElementById('buscaNome'))
            : null;
        if (outro && outro.value !== valor) outro.value = valor;
    }

    function atualizarEstiloBotoesFiltro() {
        document.querySelectorAll('[data-filtro="masculino"], [data-filtro="feminino"]').forEach((btn) => {
            const generoBtn = btn.dataset.filtro === 'masculino' ? 'MASC' : 'FEM';
            const ativo = filtrosAtivos.genero === generoBtn;
            btn.classList.toggle('btn-secondary', ativo);
            btn.classList.toggle('btn-outline-secondary', !ativo);
            btn.classList.toggle('text-white', ativo);
        });
        document.querySelectorAll('[data-filtro="destaque"]').forEach((btn) => {
            btn.classList.toggle('btn-warning', filtrosAtivos.destaque);
            btn.classList.toggle('btn-outline-warning', !filtrosAtivos.destaque);
        });
        document.querySelectorAll('[data-filtro="limpar"]').forEach((btn) => {
            const semFiltro = !filtrosAtivos.busca && !filtrosAtivos.genero && !filtrosAtivos.destaque;
            btn.classList.toggle('btn-secondary', semFiltro);
            btn.classList.toggle('text-white', semFiltro);
        });
    }

    function aplicarFiltro() {
        document.querySelectorAll('.aluno-card').forEach((card) => {
            let mostrar = true;
            if (filtrosAtivos.busca && !card.dataset.nome.includes(filtrosAtivos.busca)) {
                mostrar = false;
            }
            if (filtrosAtivos.genero && card.dataset.genero !== filtrosAtivos.genero) {
                mostrar = false;
            }
            if (filtrosAtivos.destaque && card.dataset.destaque !== '1') {
                mostrar = false;
            }
            card.classList.toggle('d-none', !mostrar);
        });
        atualizarEstiloBotoesFiltro();
    }

    function limparFiltros() {
        filtrosAtivos = { busca: '', genero: null, destaque: false };
        document.getElementById('buscaNome')?.value = '';
        document.getElementById('buscaNomeMobile')?.value = '';
        aplicarFiltro();
    }

    function toggleFiltro(filtro) {
        if (filtro === 'limpar') {
            limparFiltros();
            return;
        }
        if (filtro === 'masculino' || filtro === 'feminino') {
            const alvo = filtro === 'masculino' ? 'MASC' : 'FEM';
            filtrosAtivos.genero = filtrosAtivos.genero === alvo ? null : alvo;
        } else if (filtro === 'destaque') {
            filtrosAtivos.destaque = !filtrosAtivos.destaque;
        }
        aplicarFiltro();
    }

    async function carregarAlunos() {
        const desktop = document.getElementById('listaAlunosDesktop');
        const mobile = document.getElementById('listaAlunosMobile');
        if (!idTurmaAlunos) {
            const msg = '<p class="text-muted text-center">Turma não informada na URL.</p>';
            desktop.innerHTML = msg;
            mobile.innerHTML = msg;
            return;
        }
        try {
            const res = await fetch(`../../../api/usuarios.php?acao=listar_competidores&id_turma=${encodeURIComponent(idTurmaAlunos)}`);
            const data = await res.json();
            if (data.status !== 'sucesso') {
                throw new Error(data.mensagem || 'Falha ao carregar alunos.');
            }
            alunosLista = data.competidores || [];
            if (!alunosLista.length) {
                const msg = '<p class="text-muted text-center">Nenhum aluno cadastrado nesta turma.</p>';
                desktop.innerHTML = msg;
                mobile.innerHTML = msg;
                return;
            }
            desktop.innerHTML = alunosLista.map((a) => cardAluno(a, false)).join('');
            mobile.innerHTML = alunosLista.map((a) => cardAluno(a, true)).join('');
            aplicarFiltro();
        } catch (error) {
            console.error(error);
            const msg = `<p class="text-danger text-center">${error.message}</p>`;
            desktop.innerHTML = msg;
            mobile.innerHTML = msg;
        }
    }

    document.getElementById('buscaNome')?.addEventListener('input', (e) => {
        sincronizarBusca(e.target);
        aplicarFiltro();
    });
    document.getElementById('buscaNomeMobile')?.addEventListener('input', (e) => {
        sincronizarBusca(e.target);
        aplicarFiltro();
    });
    document.querySelectorAll('[data-filtro]').forEach((btn) => {
        btn.addEventListener('click', () => toggleFiltro(btn.dataset.filtro));
    });

    window.addEventListener('load', carregarAlunos);
</script>

<?php
require_once '../componentes/footer.php';
?>