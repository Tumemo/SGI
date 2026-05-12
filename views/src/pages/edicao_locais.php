<?php
$titulo = "Locais";
$textTop = "Locais";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .local-card {
        border-radius: 12px;
        transition: box-shadow 0.2s ease;
    }
    .local-card:hover {
        box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
</style>

<main class="d-md-none p-3" style="padding-top: 5rem; padding-bottom: 6rem;">
    <p class="text-secondary text-center small mb-3">Locais onde os jogos acontecem (ginásio, quadra, etc.)</p>
    <div id="listaLocaisMobile" class="d-flex flex-column gap-3 mx-auto" style="max-width: 420px;">
        <p class="text-muted small text-center">Carregando…</p>
    </div>
    <div class="position-fixed start-0 end-0 bottom-0 p-3 bg-light border-top shadow-sm d-flex gap-2" style="z-index: 1030;">
        <button type="button" class="btn btn-danger flex-grow-1 fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#modalNovoLocal">
            <i class="bi bi-plus-lg me-1"></i> Novo local
        </button>
        <a href="./dashboard.php" id="btnVoltarLocaisMobile" class="btn btn-outline-danger fw-semibold rounded-3">Voltar</a>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarLocaisDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 text-decoration-none shadow-sm" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i>
                <span id="nomeInterclasseLocais">Interclasse</span>
            </a>
            <h4 class="fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-geo-alt fs-4"></i> Locais de competição
            </h4>
            <p class="text-muted small mt-2 mb-0">Cadastre os espaços antes de agendar jogos. Estes registros são usados em toda a competição.</p>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNovoLocal" style="background-color: #ed1c24; border: none;">
                <i class="bi bi-plus-circle"></i> Novo local
            </button>
        </div>

        <div id="listaLocaisDesktop" class="row g-3">
            <p class="text-muted">Carregando…</p>
        </div>
    </div>
</main>

<div class="modal fade" id="modalNovoLocal" tabindex="-1" aria-labelledby="tituloModalLocal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="tituloModalLocal">Novo local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoLocal">
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="inputNomeLocal">Nome do local</label>
                        <input type="text" class="form-control rounded-3" id="inputNomeLocal" required maxlength="45" placeholder="Ex.: Quadra poliesportiva — Bloco B">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="selectDisponivelLocal">Disponível para uso</label>
                        <select class="form-select rounded-3" id="selectDisponivelLocal">
                            <option value="1" selected>Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="inputCargaLocal">Capacidade (opcional)</label>
                        <input type="number" class="form-control rounded-3" id="inputCargaLocal" min="0" placeholder="Público ou lotação">
                    </div>
                    <div id="msgNovoLocal" class="small text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger rounded-3 fw-semibold px-4" id="btnSalvarLocal">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function cardLocal(loc) {
        const disp = String(loc.disponivel_local) === '1' ? 'Disponível' : 'Indisponível';
        const carga = loc.carga_local != null && loc.carga_local !== '' ? `Capacidade: ${esc(loc.carga_local)}` : 'Capacidade não informada';
        return `
            <div class="col-12 col-md-6">
                <div class="local-card bg-white border-0 shadow-sm p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" title="${esc(loc.nome_local)}">${esc(loc.nome_local)}</h5>
                        <span class="badge rounded-pill border ${String(loc.disponivel_local) === '1' ? 'text-success border-success' : 'text-secondary border-secondary'}">${disp}</span>
                    </div>
                    <p class="text-muted small mb-0 mt-auto">${carga}</p>
                </div>
            </div>`;
    }

    function linhaLocalMobile(loc) {
        const disp = String(loc.disponivel_local) === '1' ? 'Disponível' : 'Indisponível';
        return `
            <div class="local-card bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div class="min-w-0">
                    <div class="fw-bold text-dark text-truncate">${esc(loc.nome_local)}</div>
                    <div class="text-muted small">${disp}</div>
                </div>
                <i class="bi bi-geo-alt text-danger fs-4 flex-shrink-0"></i>
            </div>`;
    }

    async function carregarLocais() {
        const mob = document.getElementById('listaLocaisMobile');
        const desk = document.getElementById('listaLocaisDesktop');
        try {
            const res = await fetch(`${API}locais.php`);
            const data = await res.json();
            const lista = (data && Array.isArray(data.data)) ? data.data : [];
            if (lista.length === 0) {
                const msg = '<p class="text-muted text-center w-100 mb-0">Nenhum local cadastrado. Toque em &quot;Novo local&quot;.</p>';
                mob.innerHTML = msg;
                desk.innerHTML = `<div class="col-12">${msg}</div>`;
                return;
            }
            mob.innerHTML = lista.map(linhaLocalMobile).join('');
            desk.innerHTML = lista.map(cardLocal).join('');
        } catch (e) {
            console.error(e);
            mob.innerHTML = '<p class="text-danger small text-center">Erro ao carregar locais.</p>';
            desk.innerHTML = '<p class="text-danger">Erro ao carregar locais.</p>';
        }
    }

    document.getElementById('formNovoLocal').addEventListener('submit', async (e) => {
        e.preventDefault();
        const nome = document.getElementById('inputNomeLocal').value.trim();
        const disponivel = document.getElementById('selectDisponivelLocal').value;
        const cargaVal = document.getElementById('inputCargaLocal').value;
        const carga = cargaVal === '' ? null : parseInt(cargaVal, 10);
        const msg = document.getElementById('msgNovoLocal');
        const btn = document.getElementById('btnSalvarLocal');
        const modalEl = document.getElementById('modalNovoLocal');
        msg.textContent = '';
        btn.disabled = true;
        try {
            const body = { nome_local: nome, disponivel_local: disponivel };
            if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;
            const res = await fetch(`${API}locais.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const js = await res.json();
            if (!res.ok || js.success === false) throw new Error(js.message || 'Não foi possível salvar.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            document.getElementById('formNovoLocal').reset();
            await carregarLocais();
        } catch (err) {
            msg.textContent = err.message || 'Erro.';
            msg.classList.add('text-danger');
        } finally {
            btn.disabled = false;
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        const dash = idInterclasse ? `./dashboard.php?id=${encodeURIComponent(idInterclasse)}` : './dashboard.php';
        document.getElementById('btnVoltarLocaisDesk').href = dash;
        document.getElementById('btnVoltarLocaisMobile').href = dash;
        if (idInterclasse) {
            try {
                const d = await window.SGIInterclasse.getInterclasseById(idInterclasse);
                if (d?.nome_interclasse) {
                    document.getElementById('nomeInterclasseLocais').textContent = d.nome_interclasse;
                    window.SGIInterclasse.updatePageTitle(d.nome_interclasse);
                }
            } catch (_) { /* ok */ }
        }
        carregarLocais();
    });
</script>

<?php
require_once '../componentes/footer.php';
?>
