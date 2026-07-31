<?php
$tituloPagina = 'SGI - Colaborador - Locais';
$titulo = 'Locais';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';

include 'componentes/head.php';
include 'componentes/header.php';

$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$paginaAtiva = 'locais';

$cssExtra = '
    .local-card { border-radius: 12px; transition: box-shadow 0.2s ease; } 
    .local-card:hover { box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.08) !important; }
    .termo-clausula { border-left: 4px solid #dc3545; padding-left: 0.75rem; margin-bottom: 0.75rem; font-size: 0.9rem; }
';
?>
<script>const NIVEL_USUARIO = <?= $nivelUsuario ?>;</script>

<!-- LAYOUT MOBILE -->
<main class="d-md-none p-3" style="padding-top: 5rem; padding-bottom: 120px;">
    <div id="listaLocaisMobile" class="d-flex flex-column gap-3 mx-auto" style="max-width: 420px;">
        <p class="text-muted small text-center">Carregando…</p>
    </div>
    
    <div class="position-fixed start-0 end-0 bottom-0 p-3 bg-light border-top shadow-sm d-flex gap-2 align-items-center" style="z-index: 1030;">
        <?php if ($nivelUsuario >= 0): ?>
            <button type="button" class="btn btn-outline-danger fw-semibold rounded-3 p-2 d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#modalTermosColaborador" title="Termos do Colaborador" style="height: 42px; width: 42px;">
                <i class="bi bi-file-earmark-text fs-5"></i>
            </button>
        <?php endif; ?>

        <button type="button" class="btn btn-danger flex-grow-1 fw-semibold rounded-3" data-bs-toggle="modal" data-bs-target="#modalNovoLocal" style="height: 42px;">
            <i class="bi bi-plus-lg me-1"></i> Adicionar local
        </button>
        
        <a href="./dashboard.php" id="btnVoltarLocaisMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;height:42px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseLocaisMob">Interclasse</span>
        </a>
    </div>
</main>

<!-- LAYOUT DESKTOP -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 960px;">
        <div class="mb-4">
            <a href="./dashboard.php" id="btnVoltarLocaisDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseLocais">Interclasse</span>
            </a>
        </div>

        <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
            <?php if ($nivelUsuario >= 0): ?>
                <button type="button" class="btn btn-outline-danger fw-semibold rounded-3 px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTermosColaborador">
                    <i class="bi bi-file-earmark-text"></i> Termos do Colaborador
                </button>
            <?php endif; ?>

            <button type="button" class="btn btn-danger fw-semibold rounded-3 px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalNovoLocal" style="background-color: #ed1c24; border: none;">
                <i class="bi bi-plus-circle"></i> Adicionar local
            </button>
        </div>

        <div id="listaLocaisDesktop" class="row g-3">
            <p class="text-muted">Carregando…</p>
        </div>
    </div>
</main>

<!-- MODAL NOVO LOCAL -->
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

<!-- MODAL EDITAR LOCAL -->
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

<!-- MODAL DE TERMOS E REGULAMENTO PARA COLABORADORES -->
<div class="modal fade" id="modalTermosColaborador" tabindex="-1" aria-labelledby="modalTermosColaboradorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-danger" id="modalTermosColaboradorLabel">
                    <i class="bi bi-file-earmark-text me-2"></i>Termos e Regulamento do Colaborador
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body p-4">
                
                <!-- Termo de Responsabilidade para Colaboradores -->
                <section class="mb-4">
                    <h2 class="fs-6 fw-bold text-dark mb-3 text-uppercase">Termo de Responsabilidade da Organização e Gestão</h2>
                    <div class="bg-light rounded-3 p-3 border">
                        <p class="text-secondary small mb-3">
                            Declaro para os devidos fins que aceito e assumo inteira responsabilidade pelos termos e diretrizes abaixo para a organização, mediação e gestão do Interclasse:
                        </p>
                        
                        <div class="termo-clausula mb-2">
                            <strong>Conduta Profissional:</strong> Comprometo-me a atuar de forma ética, imparcial, respeitosa e zelosa no desempenho de minhas atribuições durante todas as etapas e eventos.
                        </div>
                        <div class="termo-clausula mb-2">
                            <strong>Cumprimento das Regras:</strong> Declaro conhecer integralmente o Regulamento Geral do Interclasse, aplicando-o estritamente e garantindo o respeito às decisões oficiais do evento.
                        </div>
                        <div class="termo-clausula mb-2">
                            <strong>Gestão de Materiais e Locais:</strong> Responsabilizo-me pelo uso adequado e supervisão dos materiais esportivos, espaços e instalações alocadas, zelando pela integridade do patrimônio institucional.
                        </div>
                        <div class="termo-clausula mb-2">
                            <strong>Segurança e Bem-estar:</strong> Comprometo-me a zelar pela integridade e segurança dos alunos e participantes, acionando o suporte adequado e informando a organização imediatamente diante de eventuais incidentes.
                        </div>
                        <div class="termo-clausula mb-2">
                            <strong>Uso de Imagem:</strong> Autorizo o uso de minha imagem e voz para fins institucionais e de divulgação oficial do evento nas mídias da instituição.
                        </div>
                        <div class="termo-clausula mb-2">
                            <strong>Confidencialidade e Dados:</strong> Comprometo-me a manter a confidencialidade e integridade dos dados, pontuações, classificações e registros administrativos aos quais eu tiver acesso.
                        </div>
                    </div>
                </section>

                <!-- Regulamento Geral (PDF) -->
                <section>
                    <h2 class="fs-6 fw-bold text-dark mb-3 text-uppercase">Regulamento Oficial</h2>
                    <div class="regulamento-card border rounded-3 p-3">
                        <p id="statusRegulamentoModal" class="text-muted mb-0 small">
                            <span class="spinner-border spinner-border-sm me-2 text-danger" role="status"></span>Carregando regulamento...
                        </p>
                        
                        <div id="containerPdfRegulamentoModal" class="card border-danger-subtle bg-danger-subtle bg-opacity-10 d-none">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-pdf-fill fs-3 text-danger"></i>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Regulamento Oficial (PDF)</h6>
                                        <small class="text-muted">Consulte o documento oficial e as diretrizes do evento.</small>
                                    </div>
                                </div>
                                <a id="btnBaixarPdfModal" href="#" target="_blank" class="btn btn-danger btn-sm rounded-3 fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-download"></i> Baixar / Ler PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Entendido / Fechar</button>
            </div>

        </div>
    </div>
</div>

<script>
    const API = '../../../api/';
    const params = new URLSearchParams(window.location.search);
    let idInterclasse = params.get('id');

    if (idInterclasse) {
        ['btnVoltarLocaisMobile', 'btnVoltarLocaisDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
        window.SGIInterclasse.getInterclasseById(idInterclasse).then(dados => {
            ['nomeInterclasseLocaisMob', 'nomeInterclasseLocais'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = dados?.nome_interclasse || 'Interclasse';
            });
        }).catch(() => {});
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function cardLocal(loc) {
        const isAdmin = NIVEL_USUARIO === 0;
        const isDisponivel = Number(loc.disponivel_local) === 1;
        const disp = isDisponivel ? 'Disponível' : 'Indisponível';
        const carga = loc.carga_local != null && loc.carga_local !== '' ? `Capacidade: ${esc(loc.carga_local)}` : 'Capacidade não informada';
        const botoes = isAdmin ? `
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
            </div>` : '';
        return `
            <div class="col-12 col-md-6">
                <div class="local-card bg-white border-0 shadow-sm p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" title="${esc(loc.nome_local)}">${esc(loc.nome_local)}</h5>
                        <span class="badge rounded-pill border ${isDisponivel ? 'text-success border-success' : 'text-secondary border-secondary'}">${disp}</span>
                    </div>
                    <p class="text-muted small mb-3 mt-auto">${carga}</p>
                    ${botoes}
                </div>
            </div>`;
    }

    function linhaLocalMobile(loc) {
        const isAdmin = NIVEL_USUARIO === 0;
        const isDisponivel = Number(loc.disponivel_local) === 1;
        const disp = isDisponivel ? 'Disponível' : 'Indisponível';
        const botoes = isAdmin ? `
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
            </div>` : '';
        return `
            <div class="local-card bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-bold text-dark text-truncate">${esc(loc.nome_local)}</div>
                    <div class="text-muted small">${disp}</div>
                </div>
                ${botoes}
                <i class="bi bi-geo-alt text-danger fs-4 flex-shrink-0 ms-2"></i>
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
        if (!idInterclasse) {
            const resolved = await window.SGIInterclasse.resolveId();
            if (resolved) {
                idInterclasse = resolved;
                ['btnVoltarLocaisMobile', 'btnVoltarLocaisDesk'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
                });
                const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse).catch(() => null);
                if (dados?.nome_interclasse) {
                    ['nomeInterclasseLocaisMob', 'nomeInterclasseLocais'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.innerText = dados.nome_interclasse;
                    });
                }
            }
        }
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

        const modalEditar = document.getElementById('modalEditarLocal');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', function (event) {
                const botao = event.relatedTarget;
                modalEditar.querySelector('#edit-local-id').value = botao.getAttribute('data-id');
                modalEditar.querySelector('#edit-local-nome').value = botao.getAttribute('data-nome');
                modalEditar.querySelector('#edit-local-disponivel').value = botao.getAttribute('data-disponivel');
                modalEditar.querySelector('#edit-local-carga').value = botao.getAttribute('data-carga');
                document.getElementById('msgEditarLocal').textContent = '';
            });
        }

        document.getElementById('formEditarLocal').addEventListener('submit', async function (e) {
            e.preventDefault();
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
                    disponivel_local: parseInt(disponivel, 10)
                };
                if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;
                if (idInterclasse) body.interclasses_id_interclasse = parseInt(idInterclasse, 10);
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

    // Função de Regulamento usando a rota exata da API já existente
    async function carregarRegulamentoModal() {
        const statusEl = document.getElementById('statusRegulamentoModal');
        const containerPdf = document.getElementById('containerPdfRegulamentoModal');
        const btnPdf = document.getElementById('btnBaixarPdfModal');

        try {
            const res = await fetch(`${API}interclasse.php?regulamento=true`);
            if (!res.ok) throw new Error('Erro na resposta da API');

            const data = await res.json();
            const lista = Array.isArray(data) ? data : (data?.data || [data]);
            const ativo = lista.find(i => String(i.status_interclasse) === '1') || lista[0];

            const regulamentoPath = ativo?.regulamento_interclasse || ativo?.regulamento;

            if (regulamentoPath && regulamentoPath.trim() !== '') {
                btnPdf.href = `../../../uploads/regulamentos/${regulamentoPath}`;
                statusEl.classList.add('d-none');
                containerPdf.classList.remove('d-none');
            } else {
                statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                statusEl.className = 'text-muted mb-0 small';
            }
        } catch (error) {
            console.error("Erro ao carregar regulamento:", error);
            statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
            statusEl.className = 'text-danger mb-0 small';
        }
    }

    document.getElementById('modalTermosColaborador')?.addEventListener('show.bs.modal', carregarRegulamentoModal);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>