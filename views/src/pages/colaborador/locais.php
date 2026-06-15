<?php
$tituloPagina = 'SGI - Colaborador - Locais';
$titulo = 'Locais';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
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
    <p class="text-secondary text-center small mb-3">Locais onde os jogos acontecem</p>
    <div id="listaLocaisMobile" class="d-flex flex-column gap-3 mx-auto" style="max-width: 420px;">
        <p class="text-muted small text-center">Carregando…</p>
    </div>
    <div class="position-fixed start-0 end-0 bottom-0 p-3 bg-light border-top shadow-sm d-flex gap-2" style="z-index: 1030;">
        <button type="button" class="btn btn-danger flex-grow-1 fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#modalNovoLocal">
            <i class="bi bi-plus-lg me-1"></i> Adicionar local
        </button>
        <a href="./dashboard.php" id="btnVoltarLocaisMobile" class="btn btn-outline-danger fw-semibold rounded-3">Voltar</a>
    </div>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarLocaisDesk" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 mb-3 text-decoration-none">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <h4 class="fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                <i class="bi bi-geo-alt fs-4"></i> Locais
            </h4>
            <p class="text-muted small mt-2 mb-0">Visualize e cadastre os locais da competição.</p>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNovoLocal" style="background-color: #ed1c24; border: none;">
                <i class="bi bi-plus-circle"></i> Adicionar local
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
                        <input type="text" class="form-control rounded-3" id="inputNomeLocal" name="nome_local" required maxlength="45" placeholder="Ex.: Quadra poliesportiva">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium" for="inputEnderecoLocal">Endereço</label>
                        <input type="text" class="form-control rounded-3" id="inputEnderecoLocal" name="endereco_local" maxlength="100" placeholder="Ex.: Bloco B, primeiro andar">
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
    const API = '../../../../api/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');

    if (idInterclasse) {
        document.getElementById('btnVoltarLocaisMobile').href = `./dashboard.php?id=${idInterclasse}`;
        document.getElementById('btnVoltarLocaisDesk').href = `./dashboard.php?id=${idInterclasse}`;
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function cardLocal(loc) {
        return `
            <div class="col-12 col-md-6">
                <div class="local-card bg-white border-0 shadow-sm p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" title="${esc(loc.nome_local)}">${esc(loc.nome_local)}</h5>
                        <i class="bi bi-geo-alt text-danger fs-4 flex-shrink-0"></i>
                    </div>
                    <p class="text-muted small mb-0">${loc.endereco_local ? esc(loc.endereco_local) : 'Endereço não informado'}</p>
                </div>
            </div>`;
    }

    function linhaLocalMobile(loc) {
        return `
            <div class="local-card bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-dark text-truncate">${esc(loc.nome_local)}</div>
                    <div class="text-muted small">${loc.endereco_local ? esc(loc.endereco_local) : 'Endereço não informado'}</div>
                </div>
                <i class="bi bi-geo-alt text-danger fs-4 flex-shrink-0 ms-2"></i>
            </div>`;
    }

    async function carregarLocais() {
        const mob = document.getElementById('listaLocaisMobile');
        const desk = document.getElementById('listaLocaisDesktop');
        try {
            const q = idInterclasse ? `?id_interclasse=${encodeURIComponent(idInterclasse)}` : '';
            const res = await fetch(`${API}locais.php${q}`);
            const data = await res.json();
            const lista = (data && Array.isArray(data.data)) ? data.data : [];
            if (lista.length === 0) {
                const msg = '<p class="text-muted text-center w-100 mb-0">Nenhum local cadastrado.</p>';
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

    document.addEventListener('DOMContentLoaded', async () => {
        await carregarLocais();

        document.getElementById('formNovoLocal').addEventListener('submit', async (e) => {
            e.preventDefault();
            const nome = document.getElementById('inputNomeLocal').value.trim();
            const endereco = document.getElementById('inputEnderecoLocal').value.trim();
            const msg = document.getElementById('msgNovoLocal');
            const btn = document.getElementById('btnSalvarLocal');
            const modalEl = document.getElementById('modalNovoLocal');

            msg.textContent = '';
            btn.disabled = true;
            try {
                if (!idInterclasse) {
                    throw new Error('Nenhuma edição do interclasse selecionada.');
                }
                const body = {
                    nome_local: nome,
                    endereco_local: endereco,
                    interclasses_id_interclasse: parseInt(idInterclasse, 10)
                };

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
                msg.className = 'small text-center mb-2 text-danger';
            } finally {
                btn.disabled = false;
            }
        });
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../../componentes/footer.php';
?>
