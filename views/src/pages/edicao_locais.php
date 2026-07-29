<?php
$tituloPagina = 'SGI - Locais e Regulamento do Interclasse';
$titulo = 'Locais e Regulamento do Interclasse';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<!-- ================= MOBILE ================= -->
<main class="d-md-none p-3" style="padding-top: 5rem; padding-bottom: 6rem;">
    <!-- Seção Regulamento Mobile -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Regulamento</h6>
                <span id="badgeRegulamentoMob" class="badge bg-secondary">Buscando...</span>
            </div>
            <p id="infoRegulamentoMob" class="small text-muted mb-2">Carregando informações...</p>
            <div class="d-flex gap-2">
                <a id="btnVerPdfMob" href="#" target="_blank" class="btn btn-sm btn-outline-danger w-100 d-none">
                    <i class="bi bi-eye"></i> Visualizar
                </a>
                <button type="button" class="btn btn-sm btn-danger w-100" data-bs-toggle="modal" data-bs-target="#modalRegulamento">
                    <i class="bi bi-upload"></i> Enviar PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Lista Locais Mobile -->
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

<!-- ================= DESKTOP ================= -->
<main class="d-none d-md-block main-desktop-layout my-4">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <a href="./dashboard.php" id="btnVoltarLocaisDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none shadow-sm" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i>
                <span id="nomeInterclasseLocais">Interclasse</span>
            </a>

            <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNovoLocal" style="background-color: #ed1c24; border: none;">
                <i class="bi bi-plus-circle"></i> Novo local
            </button>
        </div>

        <!-- Seção do Regulamento em Destaque Desktop -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger-subtle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="bi bi-file-earmark-pdf-fill fs-3 text-danger"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Regulamento Oficial</h5>
                        <p id="infoRegulamentoDesk" class="text-muted mb-0 small">Verificando regulamento cadastrado...</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a id="btnVerPdfDesk" href="#" target="_blank" class="btn btn-outline-danger fw-semibold rounded-3 px-3 d-none">
                        <i class="bi bi-file-earmark-pdf"></i> Visualizar PDF
                    </a>
                    <button type="button" class="btn btn-danger fw-semibold rounded-3 px-3" data-bs-toggle="modal" data-bs-target="#modalRegulamento" style="background-color: #ed1c24; border: none;">
                        <i class="bi bi-cloud-upload me-1"></i> Upload do Regulamento
                    </button>
                </div>
            </div>
        </div>

        <h5 class="fw-bold mb-3">Locais de Jogos</h5>
        <div id="listaLocaisDesktop" class="row g-3">
            <p class="text-muted">Carregando…</p>
        </div>
    </div>
</main>

<!-- ================= MODAL REGULAMENTO ================= -->
<div class="modal fade" id="modalRegulamento" tabindex="-1" aria-labelledby="modalRegulamentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="modalRegulamentoLabel">Atualizar Regulamento (PDF)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formRegulamento" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pdf_regulamento" class="form-label fw-medium">Selecione o arquivo em PDF</label>
                        <input type="file" class="form-control rounded-3" id="pdf_regulamento" name="pdf_regulamento" accept=".pdf" required>
                        <div class="form-text small">Tamanho máximo suportado e formato PDF.</div>
                    </div>
                    <div id="msgRegulamento" class="small text-center mb-2"></div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3 fw-semibold px-4" id="btnSalvarRegulamento">Enviar Arquivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL NOVO LOCAL ================= -->
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

<!-- ================= MODAL EDITAR LOCAL ================= -->
<div class="modal fade" id="modalEditarLocal" tabindex="-1" aria-labelledby="modalEditarLocalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="modalEditarLocalLabel">Atualizar Local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarLocal">
                <div class="modal-body">
                    <input type="hidden" id="edit-local-id" name="id_local">

                    <div class="mb-3">
                        <label for="edit-local-nome" class="form-label fw-medium">Nome do Local</label>
                        <input type="text" class="form-control rounded-3" id="edit-local-nome" name="nome_local" required maxlength="45">
                    </div>

                    <div class="mb-3">
                        <label for="edit-local-disponivel" class="form-label fw-medium">Disponível para uso</label>
                        <select class="form-select rounded-3" id="edit-local-disponivel" name="disponivel_local">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit-local-carga" class="form-label fw-medium">Capacidade (opcional)</label>
                        <input type="number" class="form-control rounded-3" id="edit-local-carga" name="carga_local" min="0">
                    </div>
                    <div id="msgEditarLocal" class="small text-center mb-2"></div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger rounded-3 fw-semibold px-4" id="btnAtualizarLocal">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    let idInterclasse = params.get('id');

    // Função para buscar o ID do Interclasse Ativo caso não exista parâmetro na URL
    async function obterInterclasseAtivo() {
        if (idInterclasse) return idInterclasse;

        try {
            // Chamada ajustada para a API interclasse.php (no singular)
            const res = await fetch(`${API}interclasse.php?status_interclasse=1`);
            const data = await res.json();

            const ativo = Array.isArray(data) ? data[0] : data;
            if (ativo && ativo.id_interclasse) {
                idInterclasse = ativo.id_interclasse;

                const btnVoltarMob = document.getElementById('btnVoltarLocaisMobile');
                const btnVoltarDesk = document.getElementById('btnVoltarLocaisDesk');
                if (btnVoltarMob) btnVoltarMob.href = `./dashboard.php?id=${idInterclasse}`;
                if (btnVoltarDesk) btnVoltarDesk.href = `./dashboard.php?id=${idInterclasse}`;
                if (ativo.nome_interclasse) {
                    const elNome = document.getElementById('nomeInterclasseLocais');
                    if (elNome) elNome.textContent = ativo.nome_interclasse;
                }

                return idInterclasse;
            }
        } catch (e) {
            console.error('Erro ao obter interclasse ativo:', e);
        }
        return null;
    }

    if (idInterclasse) {
        document.getElementById('btnVoltarLocaisMobile').href = `./dashboard.php?id=${idInterclasse}`;
        document.getElementById('btnVoltarLocaisDesk').href = `./dashboard.php?id=${idInterclasse}`;
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // --- LÓGICA DO REGULAMENTO ---
    async function carregarRegulamento() {
        if (!idInterclasse) await obterInterclasseAtivo();
        if (!idInterclasse) return;

        try {
            // Chamada ajustada para a API interclasse.php (no singular)
            const res = await fetch(`${API}interclasse.php?id_interclasse=${idInterclasse}&regulamento=true`);
            const data = await res.json();

            const item = Array.isArray(data) ? data[0] : data;

            const pdfName = item?.regulamento_interclasse;
            const badgeMob = document.getElementById('badgeRegulamentoMob');
            const infoMob = document.getElementById('infoRegulamentoMob');
            const infoDesk = document.getElementById('infoRegulamentoDesk');
            const btnVerDesk = document.getElementById('btnVerPdfDesk');
            const btnVerMob = document.getElementById('btnVerPdfMob');

            if (pdfName && pdfName.trim() !== '') {
                const pdfUrl = `../../../uploads/regulamentos/${pdfName}`;

                if (badgeMob) {
                    badgeMob.textContent = 'Cadastrado';
                    badgeMob.className = 'badge bg-success';
                }
                if (infoMob) infoMob.textContent = 'Regulamento disponível em PDF.';
                if (infoDesk) infoDesk.textContent = 'O regulamento em PDF está atualizado e disponível para consulta.';

                if (btnVerDesk) {
                    btnVerDesk.href = pdfUrl;
                    btnVerDesk.classList.remove('d-none');
                }

                if (btnVerMob) {
                    btnVerMob.href = pdfUrl;
                    btnVerMob.classList.remove('d-none');
                }
            } else {
                if (badgeMob) {
                    badgeMob.textContent = 'Pendente';
                    badgeMob.className = 'badge bg-warning text-dark';
                }
                if (infoMob) infoMob.textContent = 'Nenhum regulamento enviado.';
                if (infoDesk) infoDesk.textContent = 'Nenhum arquivo de regulamento foi enviado até o momento.';

                if (btnVerDesk) btnVerDesk.classList.add('d-none');
                if (btnVerMob) btnVerMob.classList.add('d-none');
            }
        } catch (e) {
            console.error('Erro ao buscar regulamento:', e);
        }
    }

    function cardLocal(loc) {
        const isDisponivel = Number(loc.disponivel_local) === 1;
        const disp = isDisponivel ? 'Disponível' : 'Indisponível';
        const carga = loc.carga_local != null && loc.carga_local !== '' ? `Capacidade: ${esc(loc.carga_local)}` : 'Capacidade não informada';
        return `
            <div class="col-12 col-md-6">
                <div class="local-card bg-white border-0 shadow-sm p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" title="${esc(loc.nome_local)}">${esc(loc.nome_local)}</h5>
                        <span class="badge rounded-pill border ${isDisponivel ? 'text-success border-success' : 'text-secondary border-secondary'}">${disp}</span>
                    </div>
                    <p class="text-muted small mb-3 mt-auto">${carga}</p>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" 
                                class="btn btn-link text-primary p-0" 
                                title="Editar local"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditarLocal"
                                data-id="${loc.id_local}" 
                                data-nome="${esc(loc.nome_local)}" 
                                data-disponivel="${isDisponivel ? '1' : '0'}"
                                data-carga="${loc.carga_local || ''}">
                            <i class="bi bi-pencil-square fs-4"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-link text-danger p-0" 
                                title="Excluir local"
                                onclick='excluirLocal(${loc.id_local}, "${esc(loc.nome_local)}")'>
                            <i class="bi bi-trash fs-4"></i>
                        </button>
                    </div>
                </div>
            </div>`;
    }

    function linhaLocalMobile(loc) {
        const isDisponivel = Number(loc.disponivel_local) === 1;
        const disp = isDisponivel ? 'Disponível' : 'Indisponível';
        return `
            <div class="local-card bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-dark text-truncate">${esc(loc.nome_local)}</div>
                    <div class="text-muted small">${disp}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" 
                            class="btn btn-link text-primary p-0" 
                            title="Editar local"
                            data-bs-toggle="modal" 
                            data-bs-target="#modalEditarLocal"
                            data-id="${loc.id_local}" 
                            data-nome="${esc(loc.nome_local)}" 
                            data-disponivel="${isDisponivel ? '1' : '0'}"
                            data-carga="${loc.carga_local || ''}">
                        <i class="bi bi-pencil-square fs-5"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-link text-danger p-0" 
                            title="Excluir local"
                            onclick='excluirLocal(${loc.id_local}, "${esc(loc.nome_local)}")'>
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                    <i class="bi bi-geo-alt text-danger fs-4 flex-shrink-0"></i>
                </div>
            </div>`;
    }

    window.excluirLocal = async function(idLocal, nomeLocal) {
        if (!confirm(`Deseja excluir o local "${nomeLocal}"?\nEsta ação não pode ser desfeita.`)) {
            return;
        }
        try {
            const res = await fetch(`${API}locais.php?id_local=${parseInt(idLocal)}`, {
                method: 'DELETE'
            });
            const data = await res.json();
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Não foi possível excluir o local.');
            }
            await carregarLocais();
        } catch (error) {
            alert(error.message);
        }
    };

    async function carregarLocais() {
        const mob = document.getElementById('listaLocaisMobile');
        const desk = document.getElementById('listaLocaisDesktop');

        if (!idInterclasse) await obterInterclasseAtivo();

        try {
            // Envia o id_interclasse na Query String para filtrar só os do interclasse atual
            const q = idInterclasse ? `?id_interclasse=${encodeURIComponent(idInterclasse)}` : '';
            const res = await fetch(`${API}locais.php${q}`);
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

    document.addEventListener('DOMContentLoaded', async () => {
        if (!idInterclasse) {
            await obterInterclasseAtivo();
        }

        if (idInterclasse) {
            try {
                if (window.SGIInterclasse?.getInterclasseById) {
                    const d = await window.SGIInterclasse.getInterclasseById(idInterclasse);
                    if (d?.nome_interclasse) {
                        document.getElementById('nomeInterclasseLocais').textContent = d.nome_interclasse;
                        window.SGIInterclasse.updatePageTitle(d.nome_interclasse);
                    }
                }
            } catch (_) {
                /* ok */
            }
        }

        await carregarLocais();
        await carregarRegulamento();

        // Envio do FORMULÁRIO REGULAMENTO
        // Envio do FORMULÁRIO REGULAMENTO (Popup de Sucesso)
        document.getElementById('formRegulamento').addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msgRegulamento');
            const btn = document.getElementById('btnSalvarRegulamento');
            const fileInput = document.getElementById('pdf_regulamento');
            const modalEl = document.getElementById('modalRegulamento');

            if (!idInterclasse) {
                await obterInterclasseAtivo();
            }

            if (!idInterclasse) {
                alert('Nenhuma edição do interclasse encontrada ou ativa.');
                return;
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Por favor, selecione um arquivo PDF.');
                return;
            }

            const formData = new FormData();
            formData.append('pdf_regulamento', fileInput.files[0]);

            msg.textContent = '';
            btn.disabled = true;

            try {
                const res = await fetch(`${API}interclasse.php?id=${idInterclasse}`, {
                    method: 'POST',
                    body: formData
                });

                const js = await res.json();
                if (!res.ok || js.success === false) {
                    throw new Error(js.message || 'Falha ao salvar o regulamento.');
                }

                // 1. Fecha o modal
                bootstrap.Modal.getInstance(modalEl)?.hide();

                // 2. Limpa o input de arquivo
                document.getElementById('formRegulamento').reset();

                // 3. Exibe o Popup de Sucesso 🎉
                alert('Regulamento enviado e atualizado com sucesso! :)');

                // 4. Recarrega as informações na tela
                await carregarRegulamento();

            } catch (err) {
                alert(err.message || 'Erro no envio do regulamento.');
            } finally {
                btn.disabled = false;
            }
        });;

        // Envio do formulário NOVO LOCAL
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
                if (!idInterclasse) {
                    await obterInterclasseAtivo();
                }
                if (!idInterclasse) {
                    throw new Error('Nenhuma edição do interclasse selecionada/ativa.');
                }
                const body = {
                    nome_local: nome,
                    disponivel_local: parseInt(disponivel, 10),
                    interclasses_id_interclasse: parseInt(idInterclasse, 10)
                };
                if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;

                const res = await fetch(`${API}locais.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
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

        // Configuração ao abrir o Modal EDITAR LOCAL
        const modalEditar = document.getElementById('modalEditarLocal');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', function(event) {
                const botao = event.relatedTarget;

                const id = botao.getAttribute('data-id');
                const nome = botao.getAttribute('data-nome');
                const disponivel = botao.getAttribute('data-disponivel');
                const carga = botao.getAttribute('data-carga');

                modalEditar.querySelector('#edit-local-id').value = id;
                modalEditar.querySelector('#edit-local-nome').value = nome;
                modalEditar.querySelector('#edit-local-disponivel').value = disponivel;
                modalEditar.querySelector('#edit-local-carga').value = carga;

                document.getElementById('msgEditarLocal').textContent = '';
            });
        }

        // Envio do formulário EDITAR LOCAL
       document.getElementById('formEditarLocal').addEventListener('submit', async function (e) {
    e.preventDefault();
    
    // Garante que temos o ID do interclasse atual antes de enviar
    if (!idInterclasse) {
        await obterInterclasseAtivo();
    }
    
    const id = document.getElementById('edit-local-id').value;
    const nome = document.getElementById('edit-local-nome').value.trim();
    const disponivel = document.getElementById('edit-local-disponivel').value;
    const cargaVal = document.getElementById('edit-local-carga').value;
    const carga = cargaVal === '' ? null : parseInt(cargaVal, 10);
    
    const msg = document.getElementById('msgEditarLocal');
    const btn = document.getElementById('btnAtualizarLocal');
    
    msg.textContent = '';
    btn.disabled = true;
    
    try {
        const body = { 
            id_local: parseInt(id, 10),
            nome_local: nome, 
            disponivel_local: parseInt(disponivel, 10),
            status_local: disponivel,
            interclasses_id_interclasse: parseInt(idInterclasse, 10) // VINCULA AO INTERCLASSE ATUAL
        };
        if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;
        
        const res = await fetch(`${API}locais.php`, {
            method: 'PUT', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const js = await res.json();
        if (!res.ok || js.success === false) throw new Error(js.message || 'Não foi possível atualizar.');
        
        bootstrap.Modal.getInstance(modalEditar)?.hide();
        await carregarLocais();
    } catch (err) {
        msg.textContent = err.message || 'Erro ao atualizar.';
        msg.className = 'small text-center mb-2 text-danger';
    } finally {
        btn.disabled = false;
    }
});
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>