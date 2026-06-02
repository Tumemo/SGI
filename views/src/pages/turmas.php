<?php
$titulo = "Turmas";
$textTop = "Turmas";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="position-relative d-md-none" style="margin-bottom: 120px;">
    <p class="text-center mt-3 text-secondary">Editar detalhes da turma</p>
    <section id="listaTurmasMobile">
        <p class="text-center text-muted mt-4">(Carregando...)</p>
    </section>

    <button class="border border-none bg-danger rounded-circle p-3 fs-2 d-flex align-items-center justify-content-center position-fixed position-absolute" style="height: 60px; width: 60px; top: 80%; left: 80%; z-index: 10; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#exampleModal">
        <i class="bi bi-plus text-white" style="font-size: 1.4em;"></i>
    </button>
</main>

<main class="d-none d-md-flex flex-column main-desktop-layout">
    <a href="./dashboard.php" id="btnVoltarTurmasDesk" class="btn btn-danger d-inline-flex align-items-center mb-4 border-0 shadow-sm text-decoration-none" style="border-radius: 4px; padding: 8px 15px;">
        <i class="bi bi-arrow-left-circle me-2"></i>
        <span style="font-size: 0.9rem; font-weight: 400;" id="nomeInterclasseTurmas">Interclasse</span>
    </a>

    <h1 class="fw-bold text-dark mb-5 d-flex align-items-center gap-2 fs-2">
        <i class="bi bi-people-fill"></i>
        <span>Turmas</span>
    </h1>

    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4" id="listaTurmasDesktop"></div>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Turma</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurma">
                    <div class="mb-3">
                        <label for="nomeTurma" class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="nomeTurma" placeholder="Turma A" required>
                    </div>
                    <div class="mb-3">
                        <label for="nomeFantasia" class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="nomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label for="turnoTurma" class="form-label">Turno:</label>
                        <select class="form-select" id="turnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="categoriaTurma" class="form-label">Categoria:</label>
                        <select class="form-select" id="categoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex align-items-center gap-2 flex-column">
                        <input type="file" id="arquivoUpload" class="d-none" accept=".pdf" onchange="mostrarNomeArquivo()">
                        <p style="font-size: 14px;">Adicione aqui o pdf dos alunos da turma criada</p>

                        <label for="arquivoUpload" class="">
                            <i class="bi bi-upload"></i>
                        </label>

                        <span id="nomeArquivo" class="text-muted"></span>
                    </div>
                    <div class=" d-flex justify-content-center gap-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Criar</button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarTurma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold">Editar Turma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarTurma">
                    <div class="mb-3">
                        <label class="form-label">Nome da turma:</label>
                        <input type="text" class="form-control" id="editNomeTurma" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nome fantasia:</label>
                        <input type="text" class="form-control" id="editNomeFantasia" placeholder="Ex: Lobos">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Turno:</label>
                        <select class="form-select" id="editTurnoTurma">
                            <option value="">Selecione...</option>
                            <option value="Manhã">Manhã</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noite">Noite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria:</label>
                        <select class="form-select" id="editCategoriaTurma" required>
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div id="msgEditarTurma" class="mt-2"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoTurma">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let turmasData = [];
    let editTurmaId = null;

    function mostrarNomeArquivo() {
        const input = document.getElementById('arquivoUpload');
        const span = document.getElementById('nomeArquivo');

        if (input.files && input.files.length > 0) {
            span.textContent = input.files[0].name;
        } else {
            span.textContent = "Nenhum arquivo selecionado";
        }
    }

    async function carregarCategoriasModal() {
        try {
            const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
            if (!interclasseAtivo) return;

            const res = await fetch(`../../../api/categorias.php?id_interclasse=${interclasseAtivo.id_interclasse}`);
            const categorias = await res.json();
            const sel = document.getElementById('categoriaTurma');
            sel.innerHTML = '<option value="">Selecione...</option>';
            (categorias || []).forEach(cat => {
                sel.innerHTML += `<option value="${cat.id_categoria}">${cat.nome_categoria}</option>`;
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
        }
    }

    document.getElementById('exampleModal').addEventListener('show.bs.modal', () => {
        carregarCategoriasModal();
    });

    document.getElementById('formNovaTurma').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
            if (!interclasseAtivo) {
                alert('Nenhum interclasse ativo.');
                return;
            }

            const body = {
                interclasses_id_interclasse: interclasseAtivo.id_interclasse,
                categorias_id_categoria: document.getElementById('categoriaTurma').value,
                nome_turma: document.getElementById('nomeTurma').value.trim(),
                nome_fantasia_turma: document.getElementById('nomeFantasia').value.trim() || null,
                turno_turma: document.getElementById('turnoTurma').value || null,
                status_turma: '1'
            };

            const res = await fetch('../../../api/turmas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });

            const data = await res.json();
            if (data.success) {
                const pdf = document.getElementById('arquivoUpload').files?.[0];
                if (pdf) {
                    const formData = new FormData();
                    formData.append('pdf', pdf);
                    formData.append('nome_turma', body.nome_turma);
                    formData.append('id_interclasse', String(interclasseAtivo.id_interclasse));
                    formData.append('id_categoria', String(body.categorias_id_categoria));
                    const up = await fetch('../../../api/upload_turma_pdf.php', { method: 'POST', body: formData });
                    const upJson = await up.json().catch(() => ({}));
                    if (!up.ok || upJson.success === false) {
                        alert('Turma criada, mas falha ao processar PDF: ' + (upJson.message || 'Erro desconhecido'));
                    } else {
                        alert('Turma criada e PDF processado com sucesso!');
                    }
                } else {
                    alert('Turma criada com sucesso!');
                }
                bootstrap.Modal.getInstance(document.getElementById('exampleModal')).hide();
                document.getElementById('formNovaTurma').reset();
                carregarTurmasAtivas();
            } else {
                alert('Erro: ' + data.message);
            }
        } catch (error) {
            console.error(error);
            alert('Erro ao criar turma.');
        }
    });
</script>

<script>
    async function carregarTurmasAtivas() {
        const listaMobile = document.getElementById('listaTurmasMobile');
        const listaDesktop = document.getElementById('listaTurmasDesktop');

        try {
            const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
            if (!interclasseAtivo) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhum interclasse ativo.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhum interclasse ativo.</p>';
                return;
            }

            document.getElementById('nomeInterclasseTurmas').innerText = interclasseAtivo.nome_interclasse;
            document.getElementById('btnVoltarTurmasDesk').href = `./dashboard.php?id=${interclasseAtivo.id_interclasse}`;
            window.SGIInterclasse.updatePageTitle(interclasseAtivo.nome_interclasse);

            const turmasRes = await fetch(`../../../api/turmas.php?id_interclasse=${interclasseAtivo.id_interclasse}`);
            const listaFinal = await turmasRes.json();

            turmasData = Array.isArray(listaFinal) ? listaFinal : [];

            if (!listaFinal || !listaFinal.length) {
                listaMobile.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma turma cadastrada.</p>';
                listaDesktop.innerHTML = '<p class="text-center text-muted mt-4">Nenhuma turma cadastrada.</p>';
                return;
            }

            // Correção crucial: uso de aspas simples externas no onclick do botão para blindar a renderização do HTML
            listaMobile.innerHTML = listaFinal.map((turma) => `
                <div class="d-flex m-auto justify-content-between align-items-center shadow py-4 px-4 mb-3 border border-1 rounded-3" style="width: 90%;">
                    <a href="./modalidades_alunos.php?id=${interclasseAtivo.id_interclasse}&id_turma=${turma.id_turma}" class="text-decoration-none text-dark flex-grow-1">
                        <h2 class="m-0 fs-5">${turma.nome_turma}</h2>
                        <small class="text-muted">${turma.nome_categoria || 'Categoria vinculada'}</small>
                    </a>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link text-primary p-0" title="Editar turma" onclick='editarTurma(${turma.id_turma})'>
                            <i class="bi bi-pencil-square fs-5"></i>
                        </button>
                        <button type="button" class="btn btn-link text-danger p-0" title="Excluir turma" onclick='excluirTurma(${turma.id_turma}, "${turma.nome_turma}")'>
                            <i class="bi bi-trash fs-5"></i>
                        </button>
                        <a href="./modalidades_alunos.php?id=${interclasseAtivo.id_interclasse}&id_turma=${turma.id_turma}">
                            <img src="../../public/icons/arrow-right.svg" alt="Ver detalhes">
                        </a>
                    </div>
                </div>
            `).join('');

            listaDesktop.innerHTML = listaFinal.map((turma) => `
                <div class="col">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0 text-dark" style="font-size: 1.3rem;">${turma.nome_turma}</h5>
                            <div>
                                <button type="button" class="btn btn-link text-primary p-0 me-2" title="Editar turma" onclick='editarTurma(${turma.id_turma})'>
                                    <i class="bi bi-pencil-square fs-4"></i>
                                </button>
                                <button type="button" class="btn btn-link text-danger p-0" title="Excluir turma" onclick='excluirTurma(${turma.id_turma}, "${turma.nome_turma}")'>
                                    <i class="bi bi-trash fs-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 text-muted">${turma.nome_categoria || 'Categoria vinculada'}</div>
                        <div class="mt-auto">
                            <a href="./modalidades_alunos.php?id=${interclasseAtivo.id_interclasse}&id_turma=${turma.id_turma}" class="text-decoration-none">
                                <button type="button" class="btn btn-danger w-100 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-1" style="font-size: 0.85rem; padding: 12px; border-radius: 6px;">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </button>
                            </a>
                        </div>
                    </div>
                </div>
            `).join('');
        } catch (error) {
            console.error(error);
            listaMobile.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
            listaDesktop.innerHTML = '<p class="text-center text-danger mt-4">Erro ao carregar turmas.</p>';
        }
    }

    async function carregarCategoriasEdicao(selectedId) {
        try {
            const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
            if (!interclasseAtivo) return;
            const res = await fetch(`../../../api/categorias.php?id_interclasse=${interclasseAtivo.id_interclasse}`);
            const cats = await res.json();
            const sel = document.getElementById('editCategoriaTurma');
            sel.innerHTML = '<option value="">Selecione...</option>';
            (cats || []).forEach(cat => {
                const selAttr = cat.id_categoria == selectedId ? 'selected' : '';
                sel.innerHTML += `<option value="${cat.id_categoria}" ${selAttr}>${cat.nome_categoria}</option>`;
            });
        } catch (e) {
            console.error('Erro ao carregar categorias:', e);
        }
    }

    window.editarTurma = async function(idTurma) {
        const turma = turmasData.find(t => t.id_turma == idTurma);
        if (!turma) return;

        editTurmaId = turma.id_turma;
        document.getElementById('editNomeTurma').value = turma.nome_turma || '';
        document.getElementById('editNomeFantasia').value = turma.nome_fantasia_turma || '';
        document.getElementById('editTurnoTurma').value = turma.turno_turma || '';
        document.getElementById('msgEditarTurma').innerHTML = '';

        await carregarCategoriasEdicao(turma.categorias_id_categoria);

        const modal = new bootstrap.Modal(document.getElementById('modalEditarTurma'));
        modal.show();
    };

    document.getElementById('formEditarTurma').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btnSalvarEdicaoTurma');
        const msg = document.getElementById('msgEditarTurma');

        const nome = document.getElementById('editNomeTurma').value.trim();
        if (!nome) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">O nome não pode estar vazio.</p>';
            return;
        }

        const interclasseAtivo = await window.SGIInterclasse.getActiveInterclasse();
        if (!interclasseAtivo) { msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Nenhum interclasse ativo.</p>'; return; }

        const body = {
            id_turma: editTurmaId,
            nome_turma: nome,
            nome_fantasia_turma: document.getElementById('editNomeFantasia').value.trim() || null,
            turno_turma: document.getElementById('editTurnoTurma').value || null,
            categorias_id_categoria: parseInt(document.getElementById('editCategoriaTurma').value)
        };

        if (!body.categorias_id_categoria) {
            msg.innerHTML = '<p class="text-danger text-center fw-bold mb-0">Selecione uma categoria.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerHTML = 'Salvando...';

            const resp = await fetch('../../../api/turmas.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao atualizar.');

            msg.innerHTML = '<p class="text-success text-center fw-bold mb-0">Salvo com sucesso!</p>';
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('modalEditarTurma')).hide();
                carregarTurmasAtivas();
            }, 800);
        } catch (err) {
            msg.innerHTML = `<p class="text-danger text-center fw-bold mb-0">${err.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Salvar Alterações';
        }
    });

    // Declaração única vinculada ao escopo do objeto global window
    window.excluirTurma = async function(idTurma, nomeTurma) {
        if (!confirm(`Deseja excluir a turma "${nomeTurma}"?\nEsta ação não pode ser desfeita.`)) {
            return;
        }
        try {
            const res = await fetch(`../../../api/turmas.php?id_turma=${parseInt(idTurma)}`, {
                method: 'DELETE'
            });
            
            const textoResposta = await res.text();
            let data = null;
            
            try {
                data = JSON.parse(textoResposta);
            } catch (e) {
                console.error("Resposta não pôde ser convertida em JSON:", textoResposta);
            }
            
            if (!res.ok || !data || data.success === false) {
                const mensagemErro = (data && data.message) 
                    ? data.message 
                    : 'Não é possível excluir esta turma porque existem registros vinculados a ela (alunos, modalidades ou jogos).';
                
                throw new Error(mensagemErro);
            }
            
            alert('Turma excluída com sucesso!');
            await carregarTurmasAtivas();
        } catch (error) {
            alert(error.message);
        }
    }

    window.addEventListener('load', carregarTurmasAtivas);
</script>

<?php
require_once '../componentes/footer.php';
?>