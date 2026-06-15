<?php
$tituloPagina = 'SGI - Inscrições';
$titulo = 'Inscrições';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
?>

<style>
    .modalidade-card {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        min-height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        color: #000;
        position: relative;
        user-select: none;
        transition: transform 0.15s, background-color 0.2s, color 0.2s;
        padding: 1rem 0.75rem;
    }
    .modalidade-card:hover {
        transform: translateY(-2px);
    }
    .modalidade-card.selected {
        background-color: #5cb85c;
        color: #fff;
    }
    .modalidade-card.selected i {
        color: #fff;
    }
    .modalidade-card.selected::after {
        content: "✓";
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        background-color: #5cb85c;
        border: 2px solid #fff;
        color: white;
        border-radius: 50%;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .btn-save {
        background: linear-gradient(135deg, #e60012, #ff3344);
        color: white;
        border: none;
        padding: 14px 28px;
        min-width: 220px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 25px rgba(230,0,18,.25);
        transition: all .25s ease;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(230,0,18,.35);
        color: white;
    }
    .btn-save:active {
        transform: scale(.98);
    }
    .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
</style>

<main class="container py-4">
    <div class="d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-trophy fs-3"></i>
        <h1 class="fs-4 fw-bold m-0">Modalidades</h1>
    </div>
    <p class="text-muted small mb-4" id="subtitulo">Carregando...</p>

    <div class="row row-cols-1 row-cols-md-3 g-3" id="modalidadesGrid">
        <div class="col text-center py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            Carregando modalidades...
        </div>
    </div>

    <div class="d-flex flex-column align-items-center gap-3 mt-4 pt-3">
        <p class="counter-text text-muted small mb-0" id="contador">Você pode escolher até 3 modalidades</p>
        <button type="button" class="btn-save" id="btnSalvar" onclick="salvarEscolhas()">
            <i class="bi bi-check-lg"></i> Salvar
        </button>
        <p class="bottom-label text-muted small" id="msgFeedback"></p>
    </div>
</main>

<?php
$paginaAtiva = 'inscricao';
include 'componentes/nav.php';
?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    let modalidadesData = [];

    async function carregarDados() {
        if (!idInterclasse) {
            document.getElementById('subtitulo').textContent = 'Nenhum interclasse selecionado.';
            return;
        }

        try {
            const resInter = await fetch('../../../../api/interclasse.php?regulamento=true');
            const listaInter = await resInter.json();
            const dadosInter = (Array.isArray(listaInter) ? listaInter : []).find(i => String(i.id_interclasse) === String(idInterclasse));
            if (dadosInter) {
                document.getElementById('subtitulo').textContent = dadosInter.nome_interclasse + ' — Selecione até 3 modalidades';
            }

            const res = await fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const lista = await res.json();
            modalidadesData = Array.isArray(lista) ? lista.filter(m => String(m.status_modalidade) === '1') : [];

            const grid = document.getElementById('modalidadesGrid');
            grid.innerHTML = '';

            if (modalidadesData.length === 0) {
                grid.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhuma modalidade disponível no momento.</div>';
                return;
            }

            modalidadesData.forEach(mod => {
                const genero = mod.genero_modalidade === 'MASC' ? '♂' : mod.genero_modalidade === 'FEM' ? '♀' : '⚤';
                const col = document.createElement('div');
                col.className = 'col';
                col.innerHTML = `
                    <div class="modalidade-card" data-id="${mod.id_modalidade}" onclick="toggleModalidade(this)">
                        <i class="bi bi-trophy"></i>
                        ${mod.nome_modalidade} (${genero})
                    </div>
                `;
                grid.appendChild(col);
            });
        } catch (e) {
            console.error(e);
            document.getElementById('modalidadesGrid').innerHTML = '<div class="col-12 text-center text-danger py-5">Erro ao carregar modalidades.</div>';
        }
    }

    function toggleModalidade(card) {
        const selecionados = document.querySelectorAll('.modalidade-card.selected');
        if (card.classList.contains('selected')) {
            card.classList.remove('selected');
        } else {
            if (selecionados.length >= 3) {
                document.getElementById('msgFeedback').textContent = 'Você só pode escolher até 3 modalidades!';
                setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
                return;
            }
            card.classList.add('selected');
        }
        const qtd = document.querySelectorAll('.modalidade-card.selected').length;
        document.getElementById('contador').textContent = `Você pode escolher até 3 modalidades (${qtd}/3)`;
    }

    async function salvarEscolhas() {
        const selecionados = document.querySelectorAll('.modalidade-card.selected');
        if (selecionados.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Por favor, escolha pelo menos 1 modalidade.';
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
            return;
        }
        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Salvando...';

        const ids = [];
        selecionados.forEach(card => ids.push(parseInt(card.dataset.id)));

        try {
            const res = await fetch('../../../../api/inscricao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_interclasse: parseInt(idInterclasse),
                    id_modalidades: ids
                })
            });
            const result = await res.json();
            document.getElementById('msgFeedback').textContent = result.message;
            if (result.success) {
                document.getElementById('msgFeedback').className = 'bottom-label text-success small';
                setTimeout(() => window.location.href = 'home.php', 1500);
            } else {
                document.getElementById('msgFeedback').className = 'bottom-label text-danger small';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
            }
        } catch (e) {
            console.error(e);
            document.getElementById('msgFeedback').textContent = 'Erro de conexão. Tente novamente.';
            document.getElementById('msgFeedback').className = 'bottom-label text-danger small';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
        }
    }

    document.addEventListener('DOMContentLoaded', carregarDados);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
