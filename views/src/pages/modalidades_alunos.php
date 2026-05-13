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
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <input type="text" id="buscaNomeMobile" class="form-control form-control-sm rounded-pill" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button id="filtroMasculinoMobile" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button id="filtroFemininoMobile" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button id="filtroDestaqueMobile" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
        </div>
        <div id="listaAlunosMobile"></div>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="mx-auto" style="max-width: 720px;">
        <div class="d-flex gap-2 mb-4 overflow-auto pb-1 pt-2 ps-1" style="white-space: nowrap; scrollbar-width: none;">
            <input type="text" id="buscaNome" class="form-control form-control-sm rounded-pill p-2 ps-4" placeholder="Buscar por nome..." style="max-width: 200px;">
            <button id="filtroMasculino" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="masculino">Masculino</button>
            <button id="filtroFeminino" class="btn btn-outline-secondary btn-sm rounded-pill px-3 filtro-btn" data-filtro="feminino">Feminino</button>
            <button id="filtroDestaque" class="btn btn-outline-warning btn-sm rounded-pill px-3 filtro-btn" data-filtro="destaque">Destaque</button>
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
        const genero = aluno.genero_usuario || 'M'; // Assumindo M ou F
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

    let filtrosAtivos = { genero: null, destaque: false };

    function aplicarFiltro() {
        const busca = document.getElementById('buscaNome')?.value.toLowerCase() || document.getElementById('buscaNomeMobile')?.value.toLowerCase() || '';
        document.querySelectorAll('.aluno-card').forEach((card) => {
            let mostrar = true;
            if (busca && !card.dataset.nome.includes(busca)) {
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
    }

    function toggleFiltro(filtro) {
        if (filtro === 'masculino' || filtro === 'feminino') {
            if (filtrosAtivos.genero === filtro) {
                filtrosAtivos.genero = null;
            } else {
                filtrosAtivos.genero = filtro === 'masculino' ? 'M' : 'F';
            }
            // Reset outros botões de gênero
            document.querySelectorAll('[data-filtro="masculino"], [data-filtro="feminino"]').forEach(btn => {
                const isActive = filtrosAtivos.genero === (btn.dataset.filtro === 'masculino' ? 'M' : 'F');
                btn.classList.toggle('btn-secondary', isActive);
                btn.classList.toggle('btn-outline-secondary', !isActive);
                btn.classList.toggle('text-white', isActive);
            });
        } else if (filtro === 'destaque') {
            filtrosAtivos.destaque = !filtrosAtivos.destaque;
            const btns = document.querySelectorAll('[data-filtro="destaque"]');
            btns.forEach(btn => {
                if (filtrosAtivos.destaque) {
                    btn.classList.add('btn-warning');
                    btn.classList.remove('btn-outline-warning');
                } else {
                    btn.classList.remove('btn-warning');
                    btn.classList.add('btn-outline-warning');
                }
            });
        }
        aplicarFiltro();
    }

    async function carregarAlunos() {
        const desktop = document.getElementById('listaAlunosDesktop');
        const mobile = document.getElementById('listaAlunosMobile');
        try {
            const res = await fetch('../../../api/usuarios.php');
            const data = await res.json();
            alunosLista = data.usuarios || [];
            if (!alunosLista.length) {
                // Adicionar alunos estáticos como exemplo
                alunosLista = [
                    { id_usuario: 1, nome_usuario: 'Gustavo', genero_usuario: 'M' },
                    { id_usuario: 2, nome_usuario: 'Hugio', genero_usuario: 'M' },
                    { id_usuario: 3, nome_usuario: 'Maria', genero_usuario: 'F' },
                    { id_usuario: 4, nome_usuario: 'Ana', genero_usuario: 'F' }
                ];
            }
            desktop.innerHTML = alunosLista.map((a) => cardAluno(a, false)).join('');
            mobile.innerHTML = alunosLista.map((a) => cardAluno(a, true)).join('');
        } catch (error) {
            console.error(error);
            // Fallback para alunos estáticos
            alunosLista = [
                { id_usuario: 1, nome_usuario: 'Gustavo', genero_usuario: 'M' },
                { id_usuario: 2, nome_usuario: 'Hugio', genero_usuario: 'M' },
                { id_usuario: 3, nome_usuario: 'Maria', genero_usuario: 'F' },
                { id_usuario: 4, nome_usuario: 'Ana', genero_usuario: 'F' }
            ];
            desktop.innerHTML = alunosLista.map((a) => cardAluno(a, false)).join('');
            mobile.innerHTML = alunosLista.map((a) => cardAluno(a, true)).join('');
        }
    }

    document.querySelectorAll('[data-filtro="masculino"]').forEach(btn => btn.addEventListener('click', () => toggleFiltro('masculino')));
    document.querySelectorAll('[data-filtro="feminino"]').forEach(btn => btn.addEventListener('click', () => toggleFiltro('feminino')));
    document.querySelectorAll('[data-filtro="destaque"]').forEach(btn => btn.addEventListener('click', () => toggleFiltro('destaque')));

    document.getElementById('buscaNome')?.addEventListener('input', aplicarFiltro);
    document.getElementById('buscaNomeMobile')?.addEventListener('input', aplicarFiltro);

    window.addEventListener('load', carregarAlunos);
</script>

<?php
require_once '../componentes/footer.php';
?>