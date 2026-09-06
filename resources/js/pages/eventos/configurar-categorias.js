window.SGIPage.mount("eventos/configurar-categorias", function (pageConfig, pageScope) {

    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'view';
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
        ['btnEditarCategoriaMobile', 'btnEditarCategoriaDesktop',
         'btnExcluirCategoriaMobile', 'btnExcluirCategoriaDesktop'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('d-none', !categoriaSelecionada);
        });

        const rota = modo === 'view' ? './dashboard.php' : './edicao_modalidades.php';
        const sufixoCategoria = categoriaSelecionada ? `&id_categoria=${categoriaSelecionada}` : '';
        document.getElementById('btnContinuarMobile').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
        document.getElementById('btnContinuarDesktop').href = `${rota}?id=${idInterclasse}${sufixoCategoria}${modo !== 'view' ? '&modo=create' : ''}`;
        aplicarModoContinuar();

        if (modo === 'view') {
            ['btnVoltarCatMobile', 'btnVoltarCatDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
            });
        }
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
        if (categoriaSelecionada === Number(idCategoria)) {
            categoriaSelecionada = null;
            el.classList.remove('categoria-item--selected');
        } else {
            categoriaSelecionada = Number(idCategoria);
            document.querySelectorAll('.categoria-item').forEach((item) => {
                item.classList.remove('categoria-item--selected');
            });
            el.classList.add('categoria-item--selected');
        }
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
                ['nomeInterclasseCategoria', 'nomeInterclasseCatMob'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.innerText = dados.nome_interclasse;
                });
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
                    <button type="button" class="categoria-item bg-white d-flex m-auto justify-content-between align-items-center shadow-sm py-3 px-4 mb-3 border border-1 rounded-3 sgi-inline-8dd04718"  data-id="${cId}">
                        <i class="bi bi-trophy fs-3"></i>
                        <h2 class="m-0 fs-5 text-truncate px-3 w-100 text-start">${categoria.nome_categoria}</h2>
                        <picture><img src="../../public/icons/arrow-right.svg" alt="Seta para direita"></picture>
                    </button>
                `;

                divDesktop.innerHTML += `
                    <div class="col-12 col-md-6 col-lg-5 col-xl-4">
                        <div class="categoria-item card border-0 shadow-sm h-100 p-4 sgi-inline-69ca131c"  data-id="${cId}">
                            <div class="card-body p-0 d-flex flex-column">
                                <h4 class="fw-bold text-dark mb-4 pb-2 text-truncate" title="${categoria.nome_categoria}">${categoria.nome_categoria}</h4>
                                <div class="d-flex gap-3 mb-4">
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm sgi-inline-3255edaf" >
                                        <div class="text-dark fw-medium mb-1 sgi-inline-67736efe" >EQUIPES</div>
                                        <div class="fs-5 text-dark">${eq}</div>
                                    </div>
                                    <div class="rounded-3 p-2 px-3 flex-fill border border-light-subtle shadow-sm sgi-inline-3255edaf" >
                                        <div class="text-dark fw-medium mb-1 sgi-inline-67736efe" >PARTIDAS</div>
                                        <div class="fs-5 text-dark">${pt}</div>
                                    </div>
                                </div>
                                <a class="btn btn-danger w-100 fw-semibold text-uppercase mt-auto border-0 sgi-inline-55d15a66"  href="./edicao_turmas.php?id=${idInterclasse}&id_categoria=${cId}">
                                    VER DETALHES <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                `;

            });

            document.querySelectorAll('.categoria-item').forEach((btn) => {
                pageScope.listen(btn, 'click', (ev) => {
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

    pageScope.listen(document.getElementById('formEditarCategoria'), 'submit', async (e) => {
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

    window.excluirCategoria = async function() {
        if (!categoriaSelecionada) return;
        if (!confirm('Tem certeza que deseja excluir esta categoria?')) return;

        const btn = document.getElementById('btnExcluirCategoriaDesktop');
        const btnMob = document.getElementById('btnExcluirCategoriaMobile');
        const desabilitar = (d) => { if (btn) btn.disabled = d; if (btnMob) btnMob.disabled = d; };

        try {
            desabilitar(true);

            const resp = await fetch('../../../api/categorias.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_categoria: categoriaSelecionada })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao excluir.');

            carregarCategorias();
        } catch (err) {
            alert(err.message);
        } finally {
            desabilitar(false);
        }
    };

    // Lógica para enviar Nova Categoria para a API
    pageScope.listen(document.getElementById('formNovaCategoria'), 'submit', async (e) => {
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

    pageScope.listen(document.getElementById('formNovaTurmaCategoria'), 'submit', (e) => {
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
        window.SGIPage.ready( carregarCategorias);
    }

return {aplicarModoContinuar, atualizarAcoesCategoria, enviarPdf, selecionarCategoria, carregarCategorias, mostrarNomeArquivo};
});
