window.SGIPage.mount("eventos/configurar-turmas", function (pageConfig, pageScope) {

    const API = '/api/v1/';
    const urlParams = new URLSearchParams(window.location.search);
    const idInterclasse = urlParams.get('id');
    const idCategoriaUrl = urlParams.get('id_categoria');

    function esc(s) {
        return window.SGIHtml.escape(s);
    }

    if (!idInterclasse) {
        SGI.alert({ titulo: 'Interclasse não selecionado', mensagem: 'Nenhum interclasse foi selecionado. Você será redirecionado.', tipo: 'warning' })
            .then(() => { window.location.href = "/edicoes"; });
    }

    function getEl(id) {
        return document.getElementById(id);
    }

    // ─── BACK BUTTONS ────────────────────────────────────────────────
    if (idInterclasse) {
        ['btnVoltarTurmasMobile', 'btnVoltarTurmasDesk'].forEach(id => {
            const el = getEl(id);
            if (el) el.href = `/edicoes/categorias?id=${idInterclasse}`;
        });
        window.SGIInterclasse.getInterclasseById(idInterclasse).then(dados => {
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseTurmasMob', 'nomeInterclasseTurmasDesk'].forEach(id => {
                const el = getEl(id);
                if (el) el.innerText = nome;
            });
        }).catch(() => {});
    }

    // ═══════════════════════════════════════════════════════════════════
    // GESTÃO DE TURMAS
    // ═══════════════════════════════════════════════════════════════════
    let categoriaSelecionadaId = null;
    let todasTurmasAtuais = [];

    async function carregarCategorias() {
        try {
            const response = await fetch(`${API}categorias?id_interclasse=${idInterclasse}`);
            const categorias = await response.json();
            const container = getEl('listaCategorias');
            container.innerHTML = '';

            if (categorias && categorias.length > 0) {
                categorias.forEach(cat => {
                    const btn = document.createElement('button');
                    btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-4 border-bottom border-0 fs-6 fw-medium text-secondary';
                    btn.style.cursor = 'pointer';
                    btn.append(document.createTextNode(cat.nome_categoria == null ? '' : String(cat.nome_categoria)));
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-chevron-right text-muted';
                    btn.append(icon);

                    btn.onclick = () => {
                        document.querySelectorAll('#listaCategorias button').forEach(b => {
                            b.classList.remove('bg-light', 'text-dark', 'fw-bold');
                            b.classList.add('text-secondary');
                        });
                        btn.classList.add('bg-light', 'text-dark', 'fw-bold');
                        btn.classList.remove('text-secondary');
                        categoriaSelecionadaId = cat.id_categoria;
                        carregarTurmas(categoriaSelecionadaId);
                    };

                    container.appendChild(btn);
                    if (idCategoriaUrl && String(idCategoriaUrl) === String(cat.id_categoria)) {
                        btn.click();
                    }
                });
            } else {
                container.innerHTML = '<p class="text-muted p-3 text-center mb-0">Nenhuma categoria encontrada.</p>';
            }
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
        }
    }

    async function carregarTurmas(idCategoria) {
        try {
            const response = await fetch(`${API}turmas?id_categoria=${idCategoria}&id_interclasse=${idInterclasse}`);
            const turmas = await response.json();
            todasTurmasAtuais = turmas;
            renderizarTurmas(turmas);
        } catch (error) {
            console.error("Erro ao carregar turmas:", error);
        }
    }

    function renderizarTurmas(turmas) {
        const container = getEl('listaTurmas');
        const containerMob = getEl('listaTurmasMobile');
        container.innerHTML = '';
        if (containerMob) containerMob.innerHTML = '';

        if (turmas && turmas.length > 0) {
            const html = turmas.map(turma => `
                <a href="/turmas/alunos?id=${idInterclasse}&id_turma=${turma.id_turma}" class="text-decoration-none">
                    <div class="bg-white rounded-3 shadow-sm p-4 d-flex align-items-center justify-content-between">
                        <span class="fw-bold text-dark fs-5">${esc(turma.nome_turma)}</span>
                        <i class="bi bi-chevron-right text-muted"></i>
                    </div>
                </a>
            `).join('');
            container.innerHTML = html;
            if (containerMob) containerMob.innerHTML = html;
        } else {
            const msg = '<div class="text-center mt-5"><p class="text-muted fs-5">Nenhuma turma adicionada nesta categoria.</p></div>';
            container.innerHTML = msg;
            if (containerMob) containerMob.innerHTML = msg;
        }
    }

    // Search sync between mobile and desktop
    const inputBuscaTurma = getEl('inputBuscaTurma');
    const inputBuscaTurmaMob = getEl('inputBuscaTurmaMobile');

    function filtrarTurmasGestao(termo) {
        const t = (termo || '').toLowerCase();
        const filtradas = todasTurmasAtuais.filter(tur => tur.nome_turma.toLowerCase().includes(t));
        renderizarTurmas(filtradas);
    }
    if (inputBuscaTurma) {
        pageScope.listen(inputBuscaTurma, 'input', (e) => {
            if (inputBuscaTurmaMob) inputBuscaTurmaMob.value = e.target.value;
            filtrarTurmasGestao(e.target.value);
        });
    }
    if (inputBuscaTurmaMob) {
        pageScope.listen(inputBuscaTurmaMob, 'input', (e) => {
            if (inputBuscaTurma) inputBuscaTurma.value = e.target.value;
            filtrarTurmasGestao(e.target.value);
        });
    }

    // Create turma form
    const formTurma = getEl('formTurma');
    if (formTurma) {
        pageScope.listen(formTurma, 'submit', async (e) => {
            e.preventDefault();

            if (!categoriaSelecionadaId) {
                SGI.alert("Por favor, selecione uma categoria na lista ao lado primeiro!");
                const modalEl = getEl('modalCriarTurma');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                return;
            }

            const btnSalvar = getEl('btnSalvarTurma');
            const inputNome = getEl('inputNomeTurma');
            const inputNomeFantasia = getEl('inputNomeFantasiaTurma');
            const inputTurno = getEl('inputTurnoTurma');
            const msg = getEl('msgTurma');

            const mapaTurno = {
                'manhã': 'manha',
                'manha': 'manha',
                'tarde': 'tarde',
                'noite': 'noite',
                'integral': 'integral'
            };
            const turnoRaw = (inputTurno.value || '').trim().toLowerCase();
            const turnoNormalizado = mapaTurno[turnoRaw] || (turnoRaw || null);

            const dadosTurma = {
                interclasses_id_interclasse: parseInt(idInterclasse, 10),
                categorias_id_categoria: parseInt(String(categoriaSelecionadaId), 10),
                nome_turma: inputNome.value.trim(),
                nome_fantasia_turma: inputNomeFantasia.value.trim(),
                turno_turma: turnoNormalizado,
                status_turma: "1"
            };

            try {
                btnSalvar.disabled = true;
                msg.innerHTML = "Salvando turma...";
                const response = await fetch(`${API}turmas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(dadosTurma)
                });
                const result = await response.json();
                if (response.ok && result.success) {
                    todasTurmasAtuais = [...todasTurmasAtuais, {
                        id_turma: result.id_turma,
                        nome_turma: inputNome.value.trim(),
                        nome_fantasia_turma: inputNomeFantasia.value.trim(),
                        turno_turma: turnoNormalizado
                    }];
                    renderizarTurmas(todasTurmasAtuais);
                    msg.innerHTML = `<p class="text-success fw-bold mt-2 mb-0">Turma Adicionada!</p>`;
                    inputNome.value = '';
                    getEl('inputNomeFantasiaTurma').value = '';
                    getEl('inputTurnoTurma').value = '';
                    await carregarTurmas(categoriaSelecionadaId);
                    setTimeout(() => {
                        const modalEl = getEl('modalCriarTurma');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                        msg.innerHTML = '';
                    }, 1500);
                } else {
                    SGI.alert("Erro ao criar turma: " + (result.message || "Erro desconhecido."));
                    msg.innerHTML = "";
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                SGI.alert("Erro de conexão.");
                msg.innerHTML = "";
            } finally {
                btnSalvar.disabled = false;
            }
        });
    }

    // ─── INIT ────────────────────────────────────────────────────────
    if (idInterclasse) {
        window.SGIPage.ready( carregarCategorias);
    }

return {esc, getEl, carregarCategorias, carregarTurmas, renderizarTurmas, filtrarTurmasGestao};
});
