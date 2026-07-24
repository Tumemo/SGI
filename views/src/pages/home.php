<?php
$tituloPagina = 'SGI - Home';
$titulo = '';
$mostrarVoltar = false;
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'home';
$isAdmin = $nivelUsuario === 0;
$isColaborador = $nivelUsuario === 1;
$isMesario = $nivelUsuario === 2;
?>



<!-- main mobile -->
<main class="d-md-none" style="<?= $isMesario ? '' : 'margin-bottom: 120px;' ?>">
    <?php if ($isAdmin): ?>
    <button class="mx-4 btn btn-danger d-flex gap-2 mt-3 align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus-circle"></i>Criar Nova Edição
    </button>
    <?php endif; ?>
    <?php if ($isColaborador): ?>
    <div class="p-3">
    </div>
    <?php endif; ?>
    <div id="caixaListar" class="pb-5 mb-2 mt-2">
        <p class="text-center text-muted mt-3"><?= $isMesario ? 'Redirecionando...' : 'Carregando...' ?></p>
    </div>
</main>

<!-- main desktop -->
<main class="d-none d-md-flex main-desktop-layout">
    <section class="mt-4 container">

        <?php if ($isAdmin): ?>
        <button class="btn btn-outline-danger d-flex gap-2 mt-2 mb-4 align-items-center" data-bs-toggle="modal" data-bs-target="#exampleModal">
            <i class="bi bi-plus-circle"></i>Criar Nova Edição
        </button>
        <?php endif; ?>

        <div>
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-5 fw-medium px-2">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <div class="mt-2" id="listaDesktop">
                 <p class="text-center text-muted mt-5"><?= $isMesario ? 'Redirecionando...' : 'Carregando...' ?></p>
            </div>
        </div>

    </section>
</main>

<?php if ($isAdmin): ?>
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Edição</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6">Insira o nome da sua nova edição:</h2>
                <form id="formulario">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: interclasse 2026" id="nomeNovaEdicao" required>
                    </div>
                    <div class="mt-4">
                        <label for="anoNovaEdicao" class="form-label fs-6">Ano</label>
                        <input type="number" class="form-control" placeholder="Ex: 2026" id="anoNovaEdicao" value="2026" required>
                    </div>
                    <div id="caixaMensagem"></div>
                    <div class="d-flex justify-content-center gap-2 mt-5 pt-5">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnCriar">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if ($isMesario): ?>
function escaparHTML(string) {
    const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' };
    return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
}
<?php endif; ?>

<?php if (!$isMesario): ?>
const estadoInterclasses = { lista: [] };

function anoInterclasse(item) {
    return item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
}

function statusAtivo(item) {
    return String(item.status_interclasse) === '1';
}
<?php endif; ?>

<?php if ($isAdmin): ?>
async function atualizarStatusInterclasse(idInterclasse, ativo) {
    const body = new FormData();
    body.append('status_interclasse', ativo ? '1' : '0');
    const response = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, { method: 'POST', body });
    const raw = await response.text();
    let data;
    try { data = JSON.parse(raw); } catch { throw new Error('Erro no servidor ao atualizar status.'); }
    if (!response.ok || !data.success) throw new Error(data.message || 'Não foi possível atualizar o status.');
    await window.SGIInterclasse.refreshNavigation();
    return data;
}

async function ativarComExclusividade(idParaAtivar) {
    await atualizarStatusInterclasse(idParaAtivar, true);
}

function cardClassStatus(ativo) {
    return ativo ? '' : 'opacity-75';
}
<?php endif; ?>

async function listarInterclasses() {
    const listarMobile = document.getElementById('caixaListar');
    const listarDesktop = document.getElementById('listaDesktop');

    try {
        const res = await fetch('../../../api/interclasse.php?regulamento=true');
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            const msgVazia = '<p class="text-center text-muted mt-5">Nenhum interclasse encontrado</p>';
            listarMobile.innerHTML = msgVazia;
            listarDesktop.innerHTML = msgVazia;
            return;
        }

        const listaOrdenada = [...data].sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse));
<?php if (!$isMesario): ?>
        if (typeof estadoInterclasses !== 'undefined') estadoInterclasses.lista = listaOrdenada;
<?php endif; ?>

        <?php if ($isColaborador): ?>
        // Colaborador: mostra apenas o ativo
        const ativo = listaOrdenada.find(item => String(item.status_interclasse) === '1');
        if (!ativo) {
            const msg = '<p class="text-center text-muted mt-5">Nenhum interclasse ativo no momento.</p>';
            listarMobile.innerHTML = msg;
            listarDesktop.innerHTML = msg;
            return;
        }
        const items = [ativo];
        <?php else: ?>
        const items = listaOrdenada;
        <?php endif; ?>

        let htmlMobile = '';
        let htmlDesktop = '';

        items.forEach((item) => {
            const anoStr = item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
            const ativo = String(item.status_interclasse) === '1';
            const statusBadge = ativo
                ? '<span class="bg-danger rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Ativo</span>'
                : '<span class="bg-secondary rounded-3 text-white px-3 py-1" style="font-size: 0.82rem;">Inativo</span>';
            <?php if ($isMesario): ?>
            const classeCard = ativo ? '' : 'opacity-75';
            <?php else: ?>
            const classeCard = <?= $isAdmin ? 'cardClassStatus(ativo)' : "''" ?>;
            <?php endif; ?>
            const nome = <?= $isMesario ? 'escaparHTML(item.nome_interclasse)' : 'item.nome_interclasse' ?>;

            htmlMobile += `
                <a href="./dashboard.php?id=${item.id_interclasse}" class="text-decoration-none text-dark">
                    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1 ${classeCard}" style="width: 90%;">
                        <div>
                            <h2 class="m-0 fs-4">${nome}</h2>
                            <p class="text-secondary m-0">${anoStr}</p>
                            <?php if ($isAdmin): ?>
                            <label class="form-check form-switch mt-2 mb-0">
                              <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                              <span class="small text-muted">Interclasse ativo</span>
                            </label>
                            <?php endif; ?>
                            <?php if (!$isAdmin && !$isMesario): ?>
                            <span class="badge bg-danger mt-2">Ativo</span>
                            <?php endif; ?>
                        </div>
                        <img src="../../public/icons/arrow-right.svg" alt="icone de seta">
                    </div>
                </a>
            `;

            htmlDesktop += `
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-3 align-items-center px-2 border border-1 ${classeCard}"
                     style="cursor: pointer; transition: background-color 0.2s ease;"
                     onmouseover="this.style.backgroundColor='#f8f9fa'"
                     onmouseout="this.style.backgroundColor='#ffffff'"
                     onclick="window.location.href='./dashboard.php?id=${item.id_interclasse}'">

                    <div class="col-4 fw-semibold text-dark text-truncate">${nome}</div>
                    <div class="col-4 text-center text-secondary">${anoStr}</div>
                    <div class="col-4 text-center">
                        ${statusBadge}
                        <?php if ($isAdmin): ?>
                        <div class="form-check form-switch d-flex justify-content-center mt-2">
                            <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            `;
        });

        listarMobile.innerHTML = htmlMobile;
        listarDesktop.innerHTML = htmlDesktop;

        <?php if ($isAdmin): ?>
        registrarEventosStatus();
        <?php endif; ?>

    } catch (error) {
        console.error(error);
        const msgErro = '<p class="mt-3 text-center text-danger">Erro ao carregar dados da API!</p>';
        listarMobile.innerHTML = msgErro;
        listarDesktop.innerHTML = msgErro;
    }
}

<?php if ($isAdmin): ?>
function registrarEventosStatus() {
    document.querySelectorAll('.status-switch').forEach((input) => {
        input.addEventListener('click', (event) => event.stopPropagation());
        input.addEventListener('change', async (event) => {
            const id = event.target.getAttribute('data-id');
            const checked = event.target.checked;
            event.target.disabled = true;
            try {
                if (checked) {
                    await ativarComExclusividade(id);
                } else {
                    await atualizarStatusInterclasse(id, false);
                }
                await listarInterclasses();
            } catch (error) {
                alert(error.message || 'Erro ao atualizar status do interclasse.');
                await listarInterclasses();
            } finally {
                event.target.disabled = false;
            }
        });
    });
}
<?php endif; ?>

<?php if ($isAdmin): ?>
document.getElementById('formulario').addEventListener('submit', async (event) => {
    event.preventDefault();
    const nome = document.getElementById('nomeNovaEdicao').value;
    const ano = document.getElementById('anoNovaEdicao').value;
    const dataAtual = new Date();
    const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
    const dia = String(dataAtual.getDate()).padStart(2, '0');
    const dataFormatada = `${ano}-${mes}-${dia}`;

    const novoInterclasse = { nome_interclasse: nome.trim(), ano_interclasse: dataFormatada };

    try {
        document.getElementById('btnCriar').disabled = true;
        document.getElementById('btnCriar').innerText = "Criando...";

        const res = await axios.post("../../../api/interclasse.php", novoInterclasse);

        if (res.data && res.data.success) {
            document.getElementById('caixaMensagem').innerHTML = '<p class="text-success text-center mt-3 mb-0 fw-bold">Criado com sucesso!</p>';
            const idCriado = res.data.id;
            await atualizarStatusInterclasse(idCriado, false);
            await window.SGIInterclasse.refreshNavigation();
            document.getElementById('formulario').reset();
            listarInterclasses();
            setTimeout(() => {
                window.location.href = `./edicao_categorias.php?id=${idCriado}`;
            }, 800);
        } else {
            throw new Error(res.data ? res.data.message : "Erro interno no servidor ao salvar.");
        }
    } catch (error) {
        const msgErro = error.response?.data?.message || error.message || "Erro desconhecido";
        document.getElementById('caixaMensagem').innerHTML = `<p class="text-danger text-center mt-3 mb-0 fw-bold">Erro: ${msgErro}</p>`;
    } finally {
        document.getElementById('btnCriar').disabled = false;
        document.getElementById('btnCriar').innerText = "Criar";
    }
});
<?php endif; ?>

<?php if ($isMesario): ?>
async function redirecionarParaUltimoInterclasse() {
    try {
        const res = await fetch('../../../api/interclasse.php?regulamento=true');
        const lista = await res.json();
        if (Array.isArray(lista) && lista.length > 0) {
            const ultimo = lista.sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse))[0];
            window.location.replace('./dashboard.php?id=' + ultimo.id_interclasse);
            return;
        }
    } catch (e) {
        console.error('Erro ao redirecionar:', e);
    }
    listarInterclasses();
}

document.addEventListener('DOMContentLoaded', redirecionarParaUltimoInterclasse);
<?php else: ?>
window.addEventListener('load', listarInterclasses);
<?php endif; ?>
</script>



<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
