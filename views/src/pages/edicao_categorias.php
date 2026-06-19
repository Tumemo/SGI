<?php
$titulo = "Categorias";
$textTop = "Categorias";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<style>
    .categoria-item--selected {
        border: 2px solid #ed1c24 !important;
        box-shadow: 0 0.25rem 0.75rem rgba(237, 28, 36, 0.12) !important;
    }
</style>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <a href="./dashboard.php" id="btnVoltarCatMobile" class="btn btn-danger btn-sm mt-3 ms-3 d-inline-flex align-items-center gap-1">
        <i class="bi bi-arrow-left-circle"></i> Voltar
    </a>
    <p class="text-secondary text-center my-3" style="font-size: 14px;">Selecione uma categoria para adicionar turmas</p>
    
    <div id="listaCategoriasMobile" class="d-flex flex-column align-items-center w-100">
        <p class="text-muted small mt-3">(Carregando categorias...)</p>
    </div>

    <section class="d-flex gap-3 mt-3 position-fixed translate-middle flex-wrap justify-content-center" style="width: max-content; max-width: 96vw; top: 85%; left: 50%; z-index: 10;">
        <button type="button" id="btnEditarCategoriaMobile" class="btn btn-outline-danger" disabled onclick="abrirModalEditarCategoria()">Editar</button>
        <button data-bs-toggle="modal" data-bs-target="#modalCriarCategoria" class="btn btn-outline-danger">Adicionar Categoria</button>
        <a href="#" id="btnContinuarMobile" class="btn btn-danger">Continuar</a>
    </section>
</main>

<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0 position-relative">
        <div class="mb-5">
            <a href="./dashboard.php" id="btnVoltarCatDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseCategoria">Interclasse</span>
            </a>

            <h4 class="fw-bold d-flex align-items-center gap-2 text-dark mb-0">
                <i class="bi bi-bookmark fs-5"></i> Categorias
            </h4>
        </div>

        <div class="row g-4" id="listaCategoriasDesktop">
            <p class="text-muted">(Carregando categorias...)</p>
        </div>

        <div class="position-fixed d-flex flex-row gap-3" style="bottom: 40px; right: 5%; z-index: 1050;">
            <button type="button" id="btnEditarCategoriaDesktop" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg" style="color: #ed1c24; border: 2px solid #ed1c24;" disabled onclick="abrirModalEditarCategoria()">
                <i class="bi bi-pencil-square"></i> Editar
            </button>

            <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-lg" style="color: #ed1c24; border: 2px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#modalCriarCategoria">
                <i class="bi bi-plus-circle"></i> Adicionar
            </button>
            
            <a href="#" id="btnContinuarDesktop" class="btn fw-semibold rounded-3 px-5 py-2 text-white text-decoration-none shadow-lg d-flex align-items-center justify-content-center" style="background-color: #ed1c24; border: 2px solid #ed1c24;">
                Continuar
            </a>
            
        </div>
    </div>
</main>

<!-- modal de adicionar nova turma -->
<div class="modal fade" id="criarTurma" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurmaCategoria">
                    <div class="mb-3">
                        <label for="inputNomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="inputNomeTurma" placeholder="Ex: 1º Médio A" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputNomeFantasiaTurma" class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="inputNomeFantasiaTurma" placeholder="Ex: Turma dos Campeões">
                    </div>
                    <div class="mb-3">
                        <label for="inputTurnoTurma" class="form-label">Turno:</label>
                        <select class="form-select" id="inputTurnoTurma">
                            <option value="">Selecione o turno</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p class="text-center" style="font-size: 13px;">Adicione aqui o pdf dos alunos da turma criada</p>
                        <label for="arquivoUpload" class="btn btn-light border rounded-circle p-3" style="cursor:pointer;">
                            <i class="bi bi-upload fs-4"></i>
                        </label>
                        <span id="nomeArquivo" class="text-muted mt-2"></span>
                    </div>
                    <div id="msgNovaTurmaCategoria" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnCriarTurmaCategoria">Criar e enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- modal de criar nova categoria -->
<div class="modal fade" id="modalCriarCategoria" tabindex="-1" aria-labelledby="modalNovaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="modalNovaCategoriaLabel">Criar nova Categoria</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h2 class="fs-6 mb-3">Insira o nome da sua nova categoria:</h2>
                <form id="formNovaCategoria">
                    <div>
                        <input type="text" class="form-control" placeholder="Ex: Ensino Médio" id="inputNomeCategoriaNova" required>
                    </div>
                    <div class="d-flex justify-content-center gap-3 pt-5">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarCategoria">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarCategoria">
                    <h2 class="fs-6 mb-3">Altere o nome da categoria:</h2>
                    <input type="text" class="form-control" id="editNomeCategoria" required>
                    <div id="msgEditarCategoria" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-3 pt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-outline-danger" id="btnExcluirCategoria">Excluir</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoCategoria">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'create';
    let categoriaSelecionada = null;
    let categoriasData = [];
    let editCategoriaId = null;

    function aplicarModoContinuar() {
        const vis = modo !== 'view';
        ['btnContinuarMobile', 'btnContinuarDesktop'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('d-none', !vis);
        });
    }

    function atualizarAcoesCategoria() {
        const btnEditarMobile = document.getElementById('btnEditarCategoriaMobile');
        const btnEditarDesktop = document.getElementById('btnEditarCategoriaDesktop');
        if (btnEditarMobile) btnEditarMobile.disabled = !categoriaSelecionada;
        if (btnEditarDesktop) btnEditarDesktop.disabled = !categoriaSelecionada;

        const rota = modo === 'view' ? './dashboard.php' : './edicao_modalidades.php';
        const sufixoCategoria = categoriaSelecionada ? `&id_categoria=${categoriaSelecionada}` : '';
        document.getElementById('btnContinuarMobile').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
        document.getElementById('btnContinuarDesktop').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
        aplicarModoContinuar();

        ['btnVoltarCatMobile', 'btnVoltarCatDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
    }

    async function enviarPdf(form, msgEl, btn, idManual = null) {
        msgEl.innerHTML = '';
        if (!form) {
            throw new Error('Formulário de upload não encontrado.');
        }
        const fd = new FormData(form);
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('pdf_arquivo', fileInput.files[0]);
        }
        fd.append('id_interclasse', String(idInterclasse));
        fd.append('id_categoria', String(categoriaSelecionada));
        if (idManual !== null) {
            fd.append('id_turma', String(idManual));
        }

        const response = await fetch('../../../api/upload_turma_pdf.php', { method: 'POST', body: fd, credentials: 'include' });
        const text = await response.text();
        let json = {};
        try {
            json = JSON.parse(text);
        } catch (err) {
            throw new Error('Resposta inválida do servidor: ' + text.slice(0, 200));
        }
        if (!response.ok || json.success === false) {
            throw new Error(json.message || 'Falha ao enviar o PDF.');
        }
        return idManual !== null ? Object.assign(json, { id_turma: idManual }) : json;
    }

    function selecionarCategoria(idCategoria, el) {
        categoriaSelecionada = Number(idCategoria);
        document.querySelectorAll('.categoria-item').forEach((item) => {
            item.classList.remove('categoria-item--selected');
        });
        el.classList.add('categoria-item--selected');
        atualizarAcoesCategoria();
    }

    if (!idInterclasse) {
        // Tenta resolver para o interclasse ativo
        window.SGIInterclasse.getActiveInterclasse().then(ativo => {
            if (ativo) {
                window.location.href = `./edicao_categorias.php?id=${ativo.id_interclasse}&${modo !== 'view' ? 'modo=create' : 'modo=view'}`;
                return;
            }
            document.getElementById('listaCategoriasMobile').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('listaCategoriasDesktop').innerHTML = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('btnContinuarMobile').href = './dashboard.php';
            document.getElementById('btnContinuarDesktop').href = './dashboard.php';
        });
    } else {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then((dados) => {
            if (dados?.nome_interclasse) {
                document.getElementById('nomeInterclasseCategoria').innerText = dados.nome_interclasse;
                window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
            }
        }).catch(console.error);
        atualizarAcoesCategoria();
        aplicarModoContinuar();
    }

    async function carregarCategorias() {
        const divMobile = document.getElementById('listaCategoriasMobile');
        const divDesktop = document.getElementById('listaCategoriasDesktop');

        try {
            const respostas = await Promise.allSettled([
                fetch(`../../../api/categorias.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../api/turmas.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../api/equipes.php`).then(r => r.json()),
                fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../api/jogos.php?id_interclasse=${idInterclasse}`).then(r => r.json()),
                fetch(`../../../api/partidas.php`).then(r => r.json()),
            ]);

            const extrair = (res, padrao) => (res.status === 'fulfilled' && Array.isArray(res.value)) ? res.value : padrao;

            const categorias = extrair(respostas[0], []);
            categoriasData = categorias;
            const listaTurmas = extrair(respostas[1], []);
            const listaEquipes = extrair(respostas[2], []);
            const listaModalidades = extrair(respostas[3], []);
            const listaJogos = extrair(respostas[4], []);
            const listaPartidas = extrair(respostas[5], []);

            // -- CONSTRUIR MAPAS DE RELACIONAMENTO --
            const turmaParaCategoria = {};
            listaTurmas.forEach(t => { turmaParaCategoria[t.id_turma] = Number(t.categorias_id_categoria); });

            const modalidadeParaCategoria = {};
            listaModalidades.forEach(m => { modalidadeParaCategoria[m.id_modalidade] = Number(m.categorias_id_categoria); });

            const jogoParaModalidade = {};
            listaJogos.forEach(j => { jogoParaModalidade[j.id_jogo] = Number(j.modalidades_id_modalidade); });

            // -- CONTAR EQUIPES POR CATEGORIA --
            const qtdEquipes = {};
            listaEquipes.forEach(e => {
                const catId = turmaParaCategoria[e.turmas_id_turma];
                if (catId) qtdEquipes[catId] = (qtdEquipes[catId] || 0) + 1;
            });

            // -- CONTAR PARTIDAS POR CATEGORIA --
            const qtdPartidas = {};
            listaPartidas.forEach(p => {
                const modId = jogoParaModalidade[p.id_jogo];
                if (modId) {
                    const catId = modalidadeParaCategoria[modId];
                    if (catId) qtdPartidas[catId] = (qtdPartidas[catId] || 0) + 1;
                }
            });

            divMobile.innerHTML = '';
            divDesktop.innerHTML = '';

            if (!categorias.length) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma categoria cadastrada ainda.</p>';
                divMobile.innerHTML = msgVazia;
                divDesktop.innerHTML = msgVazia;
                return;
            }

            categorias.forEach((categoria) => {
                const cId = Number(categoria.id_categoria);
                const eq = qtdEquipes[cId] || 0;
                const pt = qtdPartidas[cId] || 0;

                divMobile.innerHTML += `
                    <button type="button" class="categoria-item bg-white d-flex m-auto justify-content-between align-items-center shadow-sm py-3 px-4 mb-3 border border-1 rounded-3" style="width: 90%;" data-id="${cId}">
                        <i class="bi bi-trophy fs-3"></i>
                        <h2 class="m-0 fs-5 text-truncate px-3 w-100 text-start">${categoria.nome_categoria}</h2>
                        <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
                    </button>
                `;

                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="categoria-item card border-0 shadow-sm h-100 p-4" style="border-radius: 12px; cursor: pointer;" data-id="${cId}">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">EQUIPES</div>
                                        <div class="fs-5 text-dark">${eq}</div>
                                    </div>
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm" style="background-color: #f8f9fc;">
                                        <div class="text-dark fw-medium mb-1" style="font-size: 0.65rem;">PARTIDAS</div>
                                        <div class="fs-5 text-dark">${pt}</div>
                                    </div>
                                </div>
                                <a class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0" style="background-color: #ed1c24; border-radius: 6px; font-size: 0.8rem; padding: 0.75rem;" href="./edicao_turmas.php?id=${idInterclasse}&id_categoria=${cId}">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;

            });

            document.querySelectorAll('.categoria-item').forEach((btn) => {
                btn.addEventListener('click', (ev) => {
                    if (ev.target.closest('a[href]')) return;
                    ev.preventDefault();
                    selecionarCategoria(btn.dataset.id, btn);
                });
            });
            categoriaSelecionada = null;
            atualizarAcoesCategoria();
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            divMobile.innerHTML = '<p class="text-danger mt-4 text-center">Erro ao carregar categorias.</p>';
            divDesktop.innerHTML = '<p class="text-danger mt-4">Erro ao carregar categorias.</p>';
        }
    }

    window.abrirModalEditarCategoria = function() {
        if (!categoriaSelecionada) return;
        const cat = categoriasData.find(c => c.id_categoria == categoriaSelecionada);
        if (!cat) return;

        editCategoriaId = cat.id_categoria;
        document.getElementById('editNomeCategoria').value = cat.nome_categoria || '';
        document.getElementById('msgEditarCategoria').innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));
        modal.show();
    };

    document.getElementById('formEditarCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarEdicaoCategoria');
        const msg = document.getElementById('msgEditarCategoria');

        const nome = document.getElementById('editNomeCategoria').value.trim();
        if (!nome) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">O nome não pode estar vazio.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const resp = await fetch('../../../api/categorias.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_categoria: editCategoriaId, nome_categoria: nome })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao atualizar.');

            msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Salvo com sucesso!</p>';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarCategoria')).hide();
                carregarCategorias();
            }, 800);
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar';
        }
    });

    document.getElementById('btnExcluirCategoria').addEventListener('click', async () => {
        if (!editCategoriaId) return;
        if (!confirm('Tem certeza que deseja excluir esta categoria?')) return;

        const btn = document.getElementById('btnExcluirCategoria');
        const msg = document.getElementById('msgEditarCategoria');

        try {
            btn.disabled = true;
            btn.innerHTML = 'Excluindo...';

            const resp = await fetch('../../../api/categorias.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_categoria: editCategoriaId })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao excluir.');

            bootstrap.Modal.getInstance(document.getElementById('modalEditarCategoria')).hide();
            carregarCategorias();
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Excluir';
        }
    });

    // Lógica para enviar Nova Categoria para a API
    document.getElementById('formNovaCategoria').addEventListener('submit', async (e) => {
        e.preventDefault();

        const inputNome = document.getElementById('inputNomeCategoriaNova');
        const btnSalvar = document.getElementById('btnSalvarCategoria');
        
        // Injetando o id_interclasse no payload
        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_categoria: inputNome.value.trim(),
            status_categoria: 1 //Definido como ativo por padrao
        };

        btnSalvar.disabled = true;
        btnSalvar.innerHTML = "Salvando...";

        try {
            const response = await fetch('../../../api/categorias.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });

            const result = await response.json();

            if (response.ok && result.success) {
                inputNome.value = "";
                
                // Fechando o modal corretamente no Bootstrap 5
                const modalEl = document.getElementById('modalCriarCategoria');
                const modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalObj.hide();

                // Recarrega a tela para exibir a categoria recém-criada
                carregarCategorias();
            } else {
                alert("Erro: " + (result.message || "Não foi possível criar a categoria."));
            }
        } catch (error) {
            console.error("Erro ao criar categoria:", error);
            alert("Erro de conexão com o servidor ao criar categoria.");
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    document.getElementById('formNovaTurmaCategoria').addEventListener('submit', (e) => {
        e.preventDefault();
        if (!categoriaSelecionada) {
            alert("Selecione uma categoria antes de criar a turma.");
            return;
        }

        const btn = document.getElementById('btnCriarTurmaCategoria');
        const msg = document.getElementById('msgNovaTurmaCategoria');
        const nomeTurma = document.getElementById('inputNomeTurma').value.trim();
        const nomeFantasia = document.getElementById('inputNomeFantasiaTurma').value.trim();
        const turno = document.getElementById('inputTurnoTurma').value;
        const pdf = document.getElementById('arquivoUpload').files?.[0];

        btn.disabled = true;
        btn.innerText = "Criando...";
        msg.innerHTML = '';

        const payloadTurma = {
            interclasses_id_interclasse: Number(idInterclasse),
            categorias_id_categoria: Number(categoriaSelecionada),
            nome_turma: nomeTurma,
            nome_fantasia_turma: nomeFantasia,
            turno_turma: turno,
            status_turma: "1"
        };

        fetch('../../../api/turmas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payloadTurma)
        })
        .then((resTurma) => {
            return resTurma.json().then((turmaCriada) => {
                if (!resTurma.ok || !turmaCriada.success) {
                    throw new Error(turmaCriada.message || 'Falha ao criar turma.');
                }
                if (pdf) {
                    return enviarPdf(document.getElementById('formNovaTurmaCategoria'), msg, btn, turmaCriada.id_turma);
                }
                return turmaCriada;
            });
        })
        .then((result) => {
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Turma criada com sucesso!</p>';
            document.getElementById('inputNomeTurma').value = '';
            document.getElementById('inputNomeFantasiaTurma').value = '';
            document.getElementById('inputTurnoTurma').value = '';
            document.getElementById('arquivoUpload').value = '';
            document.getElementById('nomeArquivo').innerText = '';

            const turmaId = (result && result.id_turma) ? result.id_turma : null;
            setTimeout(() => {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('criarTurma')).hide();
                msg.innerHTML = '';
                if (turmaId) {
                    window.location.href = `./turma_alunos.php?id=${encodeURIComponent(idInterclasse)}&id_categoria=${encodeURIComponent(categoriaSelecionada)}&id_turma=${encodeURIComponent(turmaId)}`;
                } else {
                    carregarCategorias();
                }
            }, 900);
        })
        .catch((error) => {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message || 'Erro ao criar turma.'}</p>`;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = "Criar e enviar";
        });
    });

    function mostrarNomeArquivo() {
        const inputUpload = document.getElementById('arquivoUpload');
        const displayNome = document.getElementById('nomeArquivo');
        if (inputUpload.files && inputUpload.files.length > 0) {
            displayNome.innerText = inputUpload.files[0].name;
        } else {
            displayNome.innerText = "";
        }
    }

    if (idInterclasse) {
        window.addEventListener('load', carregarCategorias);
    }
</script>

<?php
require_once '../componentes/footer.php';
?>