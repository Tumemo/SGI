window.SGIPage.mount("participantes/turma-alunos", function (pageConfig, pageScope) {

    const API = '/api/v1/';
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = Number(params.get('id') || 0);
    const idCategoria = Number(params.get('id_categoria') || 0);
    const idTurma = Number(params.get('id_turma') || 0);
    const podeGerenciar = pageConfig.value2;
    const podeExcluir   = pageConfig.value3;
    const podeResetarSenha = pageConfig.value4;

    const POR_PAGINA = 10;
    let alunosTodos = [];
    let alunosMap = {};
    let paginaAtual = 1;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function normalizar(s) {
        return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function generoLabel(g) {
        if (g === 'FEM') return 'Feminino';
        if (g === 'MASC') return 'Masculino';
        return 'Não informado';
    }

    function formatarData(s) {
        if (!s) return '—';
        const partes = String(s).split('-');
        return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : s;
    }

    function setNomeTurma(nome) {
        ['nomeTurmaDesk', 'nomeTurmaMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = nome;
        });
    }

    function setVoltar() {
        const q = new URLSearchParams();
        if (idInterclasse) q.set('id', idInterclasse);
        if (idCategoria) q.set('id_categoria', idCategoria);
        const href = `${idCategoria ? '/turmas' : '/edicoes/turmas'}?${q.toString()}`;
        ['btnVoltarTurmaAlunosMob', 'btnVoltarTurmaAlunosDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = href;
        });
    }

    async function carregarNomeInterclasseTurmaAlunos() {
        if (!idInterclasse) return;
        try {
            const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseTurmaAlunosMob', 'nomeInterclasseTurmaAlunosDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = nome;
            });
        } catch (e) {}
    }

    async function carregarAlunos() {
        await carregarNomeInterclasseTurmaAlunos();
        setVoltar();
        if (!idTurma || isNaN(idTurma) || !idInterclasse || isNaN(idInterclasse)) {
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-muted">Parâmetros inválidos.</p>';
            return;
        }
        let nomeTurma = '';
        try {
            const rT = await fetch(`${API}turmas?id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const textTurmas = await rT.text();
            let turmas = null;
            try { turmas = JSON.parse(textTurmas || 'null'); } catch (_) { turmas = null; }
            const t = Array.isArray(turmas) ? turmas[0] : null;
            nomeTurma = t?.nome_turma || 'Turma';
        } catch (_) {
        }
        setNomeTurma(nomeTurma);

        try {
            const r = await fetch(`${API}usuarios?acao=listar_competidores&id_turma=${encodeURIComponent(idTurma)}&id_interclasse=${encodeURIComponent(idInterclasse)}`);
            const textData = await r.text();
            let data;
            try { data = JSON.parse(textData || '{}'); } catch (_) { data = {}; }
            alunosTodos = data.competidores || data.usuarios || (Array.isArray(data) ? data : []);
            alunosMap = {};
            alunosTodos.forEach(a => { alunosMap[a.id_usuario] = a; });
            paginaAtual = 1;
            renderizarAlunos();
        } catch (e) {
            console.error(e);
            document.getElementById('listaAlunosTurmaMob').innerHTML = '<p class="text-danger">Erro ao carregar.</p>';
            document.getElementById('tbodyAlunosTurmaDesk').innerHTML =
                `<tr><td colspan="4" class="text-danger text-center py-4">Erro ao carregar alunos.</td></tr>`;
        }
    }

    function aplicarFiltro() {
        paginaAtual = 1;
        renderizarAlunos();
    }

    function renderizarAlunos() {
        const termo = normalizar(
            (document.getElementById('buscaAlunoDesk')?.value || '') + ' ' +
            (document.getElementById('buscaAlunoMob')?.value || '')
        );
        const filtrados = termo
            ? alunosTodos.filter(a => normalizar((a.nome_usuario || '') + ' ' + (a.matricula_usuario || '')).includes(termo))
            : alunosTodos;

        const total = filtrados.length;
        const totalPaginas = Math.max(1, Math.ceil(total / POR_PAGINA));
        if (paginaAtual > totalPaginas) paginaAtual = totalPaginas;

        const ini = (paginaAtual - 1) * POR_PAGINA;
        const pagina = filtrados.slice(ini, ini + POR_PAGINA);

        const mob = document.getElementById('listaAlunosTurmaMob');
        const desk = document.getElementById('tbodyAlunosTurmaDesk');

        if (!total) {
            const vazio = termo
                ? '<i class="bi bi-search"></i><p><strong>Nenhum resultado para sua busca.</strong></p><p class="small text-muted">Tente buscar por nome ou RM.</p>'
                : '<i class="bi bi-people"></i><p><strong>Nenhum aluno cadastrado nesta turma.</strong></p><p class="small text-muted">Clique em "Adicionar Aluno" ou importe um PDF para começar.</p>';
            mob.innerHTML = `<div class="text-center py-5 text-body-secondary">${vazio}</div>`;
            desk.innerHTML = `<tr><td colspan="4"><div class="text-center py-5 text-body-secondary">${vazio}</div></td></tr>`;
        } else {
            const acoesMob = (u) => `
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-light border text-primary px-2 py-1" data-bs-toggle="tooltip" title="Visualizar" onclick="verAlunoId(${u.id_usuario})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${podeGerenciar ? `
                    <button type="button" class="btn btn-sm btn-light border text-secondary px-2 py-1" data-bs-toggle="tooltip" title="Editar" onclick="abrirModalAlunoId(${u.id_usuario})">
                        <i class="bi bi-pencil"></i>
                    </button>` : ''}
                    ${podeExcluir ? `
                    <button type="button" class="btn btn-sm btn-light border text-danger px-2 py-1" data-bs-toggle="tooltip" title="Excluir" onclick="confirmarExcluir(${u.id_usuario})">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                    ${podeResetarSenha ? `
                    <button type="button" class="btn btn-sm btn-light border text-warning-emphasis px-2 py-1" data-bs-toggle="tooltip" title="Resetar senha" onclick="resetarSenha(${u.id_usuario})">
                        <i class="bi bi-key-fill"></i>
                    </button>` : ''}
                </div>`;

            mob.innerHTML = pagina.map((u) => `
                <div class="card border shadow-sm p-3 d-flex flex-row align-items-center gap-3${Number(u.inscrito || 0) === 0 ? ' border-danger bg-danger-subtle' : ''}">
                    <div class="rounded-circle bg-danger-subtle text-danger-emphasis fw-semibold fs-5 d-flex align-items-center justify-content-center flex-shrink-0 p-2">${esc((u.nome_usuario || 'A').charAt(0)).toUpperCase()}</div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-body text-truncate">${esc(u.nome_usuario)}${Number(u.inscrito || 0) === 0 ? '<span class="badge rounded-pill text-bg-danger ms-2">Sem inscrição</span>' : ''}</div>
                        <div class="small text-body-secondary">${esc(u.matricula_usuario || '—')} · ${esc(generoLabel(u.genero_usuario))}</div>
                    </div>
                    ${acoesMob(u)}
                </div>`).join('');

            const acoesDesk = (u) => `
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="btn btn-sm btn-light border text-primary px-2 py-1" data-bs-toggle="tooltip" title="Visualizar" onclick="verAlunoId(${u.id_usuario})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${podeGerenciar ? `
                    <button type="button" class="btn btn-sm btn-light border text-secondary px-2 py-1" data-bs-toggle="tooltip" title="Editar" onclick="abrirModalAlunoId(${u.id_usuario})">
                        <i class="bi bi-pencil"></i>
                    </button>` : ''}
                    ${podeExcluir ? `
                    <button type="button" class="btn btn-sm btn-light border text-danger px-2 py-1" data-bs-toggle="tooltip" title="Excluir" onclick="confirmarExcluir(${u.id_usuario})">
                        <i class="bi bi-trash"></i>
                    </button>` : ''}
                    ${podeResetarSenha ? `
                    <button type="button" class="btn btn-sm btn-light border text-warning-emphasis px-2 py-1" data-bs-toggle="tooltip" title="Resetar senha" onclick="resetarSenha(${u.id_usuario})">
                        <i class="bi bi-key-fill"></i>
                    </button>` : ''}
                </div>`;

            desk.innerHTML = pagina.map((u) => `
                <tr class="${Number(u.inscrito || 0) === 0 ? 'table-danger' : ''}">
                    <td class="fw-semibold text-body">
                        <span class="rounded-circle bg-danger-subtle text-danger-emphasis fw-semibold d-inline-flex align-items-center justify-content-center p-1 me-2">${esc((u.nome_usuario || 'A').charAt(0)).toUpperCase()}</span>${esc(u.nome_usuario)}${Number(u.inscrito || 0) === 0 ? '<span class="badge rounded-pill text-bg-danger ms-2">Sem inscrição</span>' : ''}
                    </td>
                    <td>${esc(u.matricula_usuario)}</td>
                    <td>
                        <span class="badge rounded-pill text-bg-light border ${u.genero_usuario === 'FEM' ? 'bg-danger-subtle text-danger-emphasis' : ''}">
                            <i class="bi ${u.genero_usuario === 'FEM' ? 'bi-gender-female' : 'bi-gender-male'}"></i>
                            ${esc(generoLabel(u.genero_usuario))}
                        </span>
                    </td>
                    <td class="text-center">${acoesDesk(u)}</td>
                </tr>`).join('');
        }

        const rotulo = total
            ? `Mostrando ${ini + 1}–${Math.min(ini + POR_PAGINA, total)} de ${total} aluno${total !== 1 ? 's' : ''}`
            : 'Nenhum aluno encontrado';
        document.getElementById('taInfoPaginaDesk').textContent = rotulo;
        document.getElementById('taInfoPaginaMob').textContent = rotulo;
        document.getElementById('contadorAlunosDesk').textContent = `${total} aluno${total !== 1 ? 's' : ''}`;
        document.getElementById('taTableCount').textContent = `${total} aluno${total !== 1 ? 's' : ''}`;

        construirPaginacao('paginacaoDesk', total, totalPaginas);
        construirPaginacao('paginacaoMob', total, totalPaginas);

        if (window.bootstrap && bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                if (bootstrap.Tooltip.getInstance(el)) bootstrap.Tooltip.getInstance(el).dispose();
                new bootstrap.Tooltip(el, { placement: 'top' });
            });
        }
    }

    function construirPaginacao(containerId, total, totalPaginas) {
        const cont = document.getElementById(containerId);
        if (!cont) return;
        cont.innerHTML = '';
        if (totalPaginas <= 1) return;

        const nova = (label, pagina, desabilitado, ativo) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (ativo ? ' active' : '') + (desabilitado ? ' disabled' : '');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-link';
            btn.innerHTML = label;
            if (!desabilitado && !ativo) {
                pageScope.listen(btn, 'click', () => {
                    paginaAtual = pagina;
                    renderizarAlunos();
                });
            }
            li.appendChild(btn);
            cont.appendChild(li);
        };

        nova('&laquo;', 1, paginaAtual === 1, false);

        let inicio = Math.max(1, paginaAtual - 2);
        let fim = Math.min(totalPaginas, inicio + 4);
        inicio = Math.max(1, fim - 4);

        if (inicio > 1) {
            nova('1', 1, false, false);
            if (inicio > 2) nova('…', 1, true, false);
        }
        for (let p = inicio; p <= fim; p++) nova(String(p), p, false, p === paginaAtual);
        if (fim < totalPaginas) {
            if (fim < totalPaginas - 1) nova('…', totalPaginas, true, false);
            nova(String(totalPaginas), totalPaginas, false, false);
        }

        nova('&raquo;', totalPaginas, paginaAtual === totalPaginas, false);
    }

    function abrirModalAlunoId(id) {
        abrirModalAluno(alunosMap[id] || null);
    }

    function verAlunoId(id) {
        const aluno = alunosMap[id];
        if (!aluno) return;
        verAluno(aluno);
    }

    function verAluno(aluno) {
        document.getElementById('verInicial').textContent = (aluno.nome_usuario || 'A').charAt(0).toUpperCase();
        document.getElementById('verNome').textContent = aluno.nome_usuario || '—';
        document.getElementById('verRm').textContent = aluno.matricula_usuario || '—';
        const g = document.getElementById('verGenero');
        g.innerHTML = `<i class="bi ${aluno.genero_usuario === 'FEM' ? 'bi-gender-female' : 'bi-gender-male'}"></i> ${esc(generoLabel(aluno.genero_usuario))}`;
        document.getElementById('verDataNasc').textContent = formatarData(aluno.data_nasc_usuario);
        new bootstrap.Modal(document.getElementById('modalVerAluno')).show();
    }

    function abrirModalAluno(aluno) {
        const modal = new bootstrap.Modal(document.getElementById('modalAluno'));
        document.getElementById('alunoId').value = aluno ? aluno.id_usuario : '';
        document.getElementById('alunoNome').value = aluno ? aluno.nome_usuario : '';
        document.getElementById('alunoRm').value = aluno ? aluno.matricula_usuario : '';
        document.getElementById('alunoGenero').value = aluno ? (aluno.genero_usuario || 'MASC') : 'MASC';
        document.getElementById('alunoDataNasc').value = aluno && aluno.data_nasc_usuario ? aluno.data_nasc_usuario : '';
        document.getElementById('modalAlunoTitulo').textContent = aluno ? 'Editar aluno' : 'Adicionar aluno';
        document.getElementById('msgAluno').innerHTML = '';
        modal.show();
    }

    async function salvarAluno(e) {
        e.preventDefault();
        const id = document.getElementById('alunoId').value;
        const nome = document.getElementById('alunoNome').value.trim();
        const rm = document.getElementById('alunoRm').value.trim();
        const genero = document.getElementById('alunoGenero').value;
        const dataNasc = document.getElementById('alunoDataNasc').value;
        const msgEl = document.getElementById('msgAluno');
        const btn = document.getElementById('btnSalvarAluno');

        if (!nome || !rm || !dataNasc) {
            msgEl.innerHTML = '<span class="text-danger">Preencha todos os campos.</span>';
            return;
        }

        const fd = new FormData();
        fd.append('acao', id ? 'editar_aluno' : 'criar_aluno');
        if (id) fd.append('id_usuario', id);
        fd.append('nome_usuario', nome);
        fd.append('matricula_usuario', rm);
        fd.append('genero_usuario', genero);
        fd.append('data_nasc_usuario', dataNasc);
        fd.append('turmas_id_turma', idTurma);
        fd.append('interclasses_id_interclasse', idInterclasse);

        try {
            btn.disabled = true;
            const r = await fetch(`${API}usuarios`, { method: 'POST', body: fd, credentials: 'include' });
            const js = await r.json();
            if (js.status === 'sucesso') {
                bootstrap.Modal.getInstance(document.getElementById('modalAluno')).hide();
                if (js.senha_temporaria) {
                    alert(`${js.mensagem || 'Aluno cadastrado.'}\nSenha temporária: ${js.senha_temporaria}`);
                }
                carregarAlunos();
            } else {
                msgEl.innerHTML = `<span class="text-danger">${esc(js.mensagem || 'Erro ao salvar.')}</span>`;
            }
        } catch (err) {
            msgEl.innerHTML = `<span class="text-danger">Falha de conexão.</span>`;
        } finally {
            btn.disabled = false;
        }
    }

    let idAlunoExcluir = 0;
    function confirmarExcluir(id) {
        const aluno = alunosMap[id];
        idAlunoExcluir = id;
        document.getElementById('nomeAlunoExcluir').textContent = aluno ? aluno.nome_usuario : '';
        new bootstrap.Modal(document.getElementById('modalConfirmarExcluir')).show();
    }

    async function executarExcluir() {
        const btn = document.getElementById('btnConfirmarExcluir');
        try {
            btn.disabled = true;
            const fd = new FormData();
            fd.append('acao', 'excluir_aluno');
            fd.append('id_usuario', idAlunoExcluir);
            const r = await fetch(`${API}usuarios`, { method: 'POST', body: fd, credentials: 'include' });
            const js = await r.json();
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmarExcluir')).hide();
            if (js.status === 'sucesso') {
                carregarAlunos();
            } else {
                alert(js.mensagem || 'Erro ao excluir.');
            }
        } catch (_) {
            alert('Falha de conexão.');
        } finally {
            btn.disabled = false;
        }
    }

    let idAlunoResetar = 0;
    function resetarSenha(id) {
        const aluno = alunosMap[id];
        idAlunoResetar = id;
        document.getElementById('nomeAlunoResetar').textContent = aluno ? aluno.nome_usuario : '';
        new bootstrap.Modal(document.getElementById('modalResetarSenha')).show();
    }

    async function executarResetar() {
        const btn = document.getElementById('btnConfirmarResetar');
        try {
            btn.disabled = true;
            const fd = new FormData();
            fd.append('acao', 'resetar_senha_aluno');
            fd.append('id_usuario', idAlunoResetar);
            const r = await fetch(`${API}usuarios`, { method: 'POST', body: fd, credentials: 'include' });
            const js = await r.json();
            bootstrap.Modal.getInstance(document.getElementById('modalResetarSenha')).hide();
            if (js.status === 'sucesso') {
                const temporaryPassword = js.senha_temporaria ? `\nSenha temporária: ${js.senha_temporaria}` : '';
                alert(`${js.mensagem || 'Senha temporária gerada.'}${temporaryPassword}`);
                carregarAlunos();
            } else {
                alert(js.mensagem || 'Erro ao resetar a senha.');
            }
        } catch (_) {
            alert('Falha de conexão.');
        } finally {
            btn.disabled = false;
        }
    }

    /* ── Upload de PDF com progresso ── */
    function setBtnLoading(btn, carregando) {
        if (!btn) return;
        if (carregando) {
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Importando…';
        } else {
            btn.disabled = false;
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        }
    }

    function progressoHelper(containerId, barId, textoId) {
        const cont = document.getElementById(containerId);
        const bar = document.getElementById(barId);
        const txt = document.getElementById(textoId);
        if (!cont || !bar || !txt) return null;
        bar.style.width = '0%';
        return {
            start() { cont.classList.remove('d-none'); bar.style.width = '8%'; txt.textContent = 'Enviando arquivo…'; },
            progress(p) { bar.style.width = p + '%'; txt.textContent = `Enviando… ${p}%`; },
            done() { bar.style.width = '100%'; txt.textContent = 'Processando alunos…'; },
            reset() { bar.style.width = '0%'; txt.textContent = 'Enviando…'; }
        };
    }

    function enviarPdf(form, msgEl, btn, fallbackEl, cfg) {
        msgEl.innerHTML = '';
        if (fallbackEl) fallbackEl.classList.add('d-none');

        const fd = new FormData(form);
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            fd.append('pdf_arquivo', fileInput.files[0]);
        }
        fd.append('id_interclasse', idInterclasse || '');
        fd.append('id_categoria', idCategoria || '');
        fd.append('id_turma', idTurma || '');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/v1/importacoes/turma-pdf');
        xhr.withCredentials = true;

        pageScope.listen(xhr.upload, 'progress', (e) => {
            if (e.lengthComputable && cfg) cfg.progress(Math.round((e.loaded / e.total) * 100));
        });

        xhr.onload = () => {
            setBtnLoading(btn, false);
            let js = {};
            try { js = JSON.parse(xhr.responseText); } catch (_) { js = {}; }
            if (xhr.status >= 200 && xhr.status < 300 && js.success !== false) {
                if (cfg) cfg.done();
                msgEl.innerHTML = '<span class="text-success">Importação concluída. Atualizando…</span>';
                setTimeout(() => window.location.reload(), 1200);
            } else {
                if (cfg) cfg.reset();
                msgEl.innerHTML = `<span class="text-danger">${esc(js.message || 'Falha no upload: ' + xhr.responseText)}</span>`;
                if (fallbackEl && js.fallback_converter) fallbackEl.classList.remove('d-none');
            }
        };

        xhr.onerror = () => {
            setBtnLoading(btn, false);
            if (cfg) cfg.reset();
            msgEl.innerHTML = '<span class="text-danger">Falha de conexão.</span>';
        };

        setBtnLoading(btn, true);
        if (cfg) cfg.start();
        xhr.send(fd);
    }

    /* ── Drag and Drop ── */
    function configurarDropzone(dropzoneId, inputId, nomeId) {
        const dropzone = document.getElementById(dropzoneId);
        const input = document.getElementById(inputId);
        const nome = document.getElementById(nomeId);
        if (!dropzone || !input || !nome) return;

        pageScope.listen(dropzone, 'click', () => input.click());

        pageScope.listen(input, 'change', () => {
            if (input.files && input.files[0]) {
                nome.textContent = input.files[0].name;
                nome.classList.remove('d-none');
                dropzone.classList.add('border-success', 'bg-success-subtle');
            } else {
                nome.textContent = '';
                nome.classList.add('d-none');
                dropzone.classList.remove('border-success', 'bg-success-subtle');
            }
        });

        ['dragenter', 'dragover'].forEach(ev =>
            pageScope.listen(dropzone, ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('border-danger', 'bg-danger-subtle');
            })
        );
        ['dragleave', 'drop'].forEach(ev =>
            pageScope.listen(dropzone, ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('border-danger', 'bg-danger-subtle');
            })
        );
        pageScope.listen(dropzone, 'drop', (e) => {
            const arquivos = e.dataTransfer ? e.dataTransfer.files : null;
            if (arquivos && arquivos.length) {
                try {
                    const dt = new DataTransfer();
                    Array.from(arquivos).forEach(f => dt.items.add(f));
                    input.files = dt.files;
                } catch (_) {
                    input.value = '';
                }
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    window.SGIPage.ready( () => {
        carregarAlunos();

        pageScope.listen(document.getElementById('formAluno'), 'submit', salvarAluno);
        pageScope.listen(document.getElementById('btnConfirmarExcluir'), 'click', executarExcluir);
        pageScope.listen(document.getElementById('btnConfirmarResetar'), 'click', executarResetar);

        const buscaDesk = document.getElementById('buscaAlunoDesk');
        const buscaMob = document.getElementById('buscaAlunoMob');
        if (buscaDesk && buscaMob) {
            pageScope.listen(buscaDesk, 'input', () => { buscaMob.value = buscaDesk.value; aplicarFiltro(); });
            pageScope.listen(buscaMob, 'input', () => { buscaDesk.value = buscaMob.value; aplicarFiltro(); });
        }

        const colapsoMob = document.getElementById('blocoPdfMob');
        const colapsoDesk = document.getElementById('blocoPdfDesk');
        const botaoMob = document.getElementById('botaoPdfMob');
        const botaoDesk = document.getElementById('botaoPdfDesk');
        const atualizarChevron = (botao, aberto) => {
            const chevron = botao?.querySelector('[data-pdf-chevron]');
            if (!chevron) return;
            chevron.classList.toggle('bi-chevron-down', !aberto);
            chevron.classList.toggle('bi-chevron-up', aberto);
        };
        if (colapsoMob && botaoMob) {
            pageScope.listen(colapsoMob, 'show.bs.collapse', () => atualizarChevron(botaoMob, true));
            pageScope.listen(colapsoMob, 'hide.bs.collapse', () => atualizarChevron(botaoMob, false));
        }
        if (colapsoDesk && botaoDesk) {
            pageScope.listen(colapsoDesk, 'show.bs.collapse', () => atualizarChevron(botaoDesk, true));
            pageScope.listen(colapsoDesk, 'hide.bs.collapse', () => atualizarChevron(botaoDesk, false));
        }

        configurarDropzone('dropzoneMob', 'pdfInputMob', 'pdfNomeMob');
        configurarDropzone('dropzoneDesk', 'pdfInputDesk', 'pdfNomeDesk');

        const fMob = document.getElementById('formPdfTurmaMob');
        const fDesk = document.getElementById('formPdfTurmaDesk');
        const cfgMob = progressoHelper('progressMob', 'progressBarMob', 'progressTextoMob');
        const cfgDesk = progressoHelper('progressDesk', 'progressBarDesk', 'progressTextoDesk');

        if (fMob) {
            pageScope.listen(fMob, 'submit', (e) => {
                e.preventDefault();
                enviarPdf(fMob, document.getElementById('msgPdfMob'), fMob.querySelector('button[type="submit"]'), document.getElementById('fallbackMob'), cfgMob);
            });
        }
        if (fDesk) {
            pageScope.listen(fDesk, 'submit', (e) => {
                e.preventDefault();
                enviarPdf(fDesk, document.getElementById('msgPdfDesk'), fDesk.querySelector('button[type="submit"]'), document.getElementById('fallbackDesk'), cfgDesk);
            });
        }
    });

return {esc, normalizar, generoLabel, formatarData, setNomeTurma, setVoltar, carregarNomeInterclasseTurmaAlunos, carregarAlunos, aplicarFiltro, renderizarAlunos, construirPaginacao, abrirModalAlunoId, verAlunoId, verAluno, abrirModalAluno, salvarAluno, confirmarExcluir, executarExcluir, resetarSenha, executarResetar, setBtnLoading, progressoHelper, enviarPdf, configurarDropzone};
});
