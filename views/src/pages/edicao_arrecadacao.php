<?php
$titulo = "Arrecadação";
$textTop = "Arrecadação";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<!-- main mobile -->
<main class="d-md-none" style="margin-bottom: 120px;">
    <div class="card shadow m-auto mt-4" style="width: 20rem;">
        <div class="card-header">
            Pontos da arrecadação
        </div>
        <ul class="list-group list-group-flush" id="listaArrecadacaoMobile">
            <li class="list-group-item text-center text-muted">(Carregando...)</li>
        </ul>
    </div>
</main>



<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">

    <div class="container-fluid px-0" style="max-width: 1000px;">

        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarArrecadacao" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseArrecadacao">Interclasse</span>
            </a>

            <div class="d-flex justify-content-Between aligni-items-center align-items-center">
                <h4 class="fw-bold text-dark mb-0">Arrecadações</h4>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <button class="btn bg-white fw-semibold rounded-3 px-4 py-2" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <button class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                        Salvar
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5" id="listaArrecadacaoDesktop"></div>



    </div>
</main>

<?php

require_once '../componentes/footer.php';
?>
<script>
    const storagePrefix = 'sgi_arrecadacao_';

    function getPontosLocal(idTurma) {
        return Number(localStorage.getItem(`${storagePrefix}${idTurma}`) || 0);
    }

    function salvarPontosLocal(idTurma, valor) {
        localStorage.setItem(`${storagePrefix}${idTurma}`, String(valor));
    }

    function renderLinhaMobile(turma) {
        return `
            <li class="list-group-item justify-content-around d-flex align-items-center gap-2">
                <span>${turma.nome_turma}</span>
                <input type="number" class="w-25 rounded border shadow-sm text-center arrec-input" data-id-turma="${turma.id_turma}" value="${getPontosLocal(turma.id_turma)}">
                <span>Pontos</span>
            </li>
        `;
    }

    function renderCardDesktop(turma) {
        return `
            <div class="col-12 col-md-6">
                <div class="bg-white border-0 shadow-sm rounded-3 p-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark fs-6">${turma.nome_turma}</span>
                    <input type="number" class="form-control text-center arrec-input" data-id-turma="${turma.id_turma}" value="${getPontosLocal(turma.id_turma)}" style="max-width: 90px;">
                </div>
            </div>
        `;
    }

    async function carregarArrecadacao() {
        const listaMobile = document.getElementById('listaArrecadacaoMobile');
        const listaDesktop = document.getElementById('listaArrecadacaoDesktop');

        try {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            if (!ativo) {
                listaMobile.innerHTML = '<li class="list-group-item text-center text-muted">Nenhum interclasse ativo.</li>';
                listaDesktop.innerHTML = '<p class="text-center text-muted">Nenhum interclasse ativo.</p>';
                return;
            }

            document.getElementById('nomeInterclasseArrecadacao').innerText = ativo.nome_interclasse;
            document.getElementById('btnVoltarArrecadacao').href = `./dashboard.php?id=${ativo.id_interclasse}`;
            window.SGIInterclasse.updatePageTitle(ativo.nome_interclasse);

            const res = await fetch(`../../../api/turmas.php?id_interclasse=${ativo.id_interclasse}`);
            const turmas = await res.json();
            if (!Array.isArray(turmas) || turmas.length === 0) {
                listaMobile.innerHTML = '<li class="list-group-item text-center text-muted">Nenhuma turma cadastrada.</li>';
                listaDesktop.innerHTML = '<p class="text-center text-muted">Nenhuma turma cadastrada.</p>';
                return;
            }

            listaMobile.innerHTML = turmas.map(renderLinhaMobile).join('');
            listaDesktop.innerHTML = turmas.map(renderCardDesktop).join('');
            document.querySelectorAll('.arrec-input').forEach((input) => {
                input.addEventListener('change', (event) => {
                    const idTurma = event.target.getAttribute('data-id-turma');
                    salvarPontosLocal(idTurma, Number(event.target.value || 0));
                });
            });
        } catch (error) {
            console.error(error);
            listaMobile.innerHTML = '<li class="list-group-item text-center text-danger">Erro ao carregar turmas.</li>';
            listaDesktop.innerHTML = '<p class="text-center text-danger">Erro ao carregar turmas.</p>';
        }
    }

    window.addEventListener('load', carregarArrecadacao);
</script>