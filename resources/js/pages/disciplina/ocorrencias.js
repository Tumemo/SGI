window.SGIPage.mount("disciplina/ocorrencias", function (pageConfig, pageScope) {

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    const params = new URLSearchParams(window.location.search);
    let idInterclasse = params.get('id');
    let todasTurmas = [];
    let todasCategorias = [];
    let historicoRegistros = [];
    let modalTurmaId = null;
    let historicoTurmaId = null;

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo.');
            window.location.href = 'home.php';
            return;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        ['nomeInterclasseOcr', 'nomeInterclasseOcrMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = dados?.nome_interclasse || 'Interclasse';
        });
        ['btnVoltarOcr', 'btnVoltarOcrMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `./dashboard.php?id=${idInterclasse}`;
        });
    }

    const API_BASE = window.location.pathname.replace(/\/views\/src\/pages\/.*$/, '/api');

    async function carregarDados() {
        await resolverInterclasse();

        if (!idInterclasse) return;

        const resTurmas = await fetch(`${API_BASE}/turmas.php?id_interclasse=${idInterclasse}`);
        if (!resTurmas.ok) {
            alert('Erro ao carregar turmas.');
            return;
        }
        todasTurmas = await resTurmas.json();

        carregarLista();
    }

    function turmasFiltradas() {
        return todasTurmas;
    }

    function carregarLista() {
        function renderCard(turma) {
            return `
                <div class="ocr-card">
                    <div class="ocr-card__icon"><i class="bi bi-people-fill"></i></div>
                    <div class="ocr-card__info">
                        <p class="ocr-card__name">${esc(turma.nome_fantasia_turma || turma.nome_turma)}</p>
                        <span class="ocr-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                    </div>
                    <button class="ocr-card__hist" onclick="abrirHistoricoTurma(${turma.id_turma}, '${esc(turma.nome_fantasia_turma || turma.nome_turma)}')" title="Ver histórico">
                        <i class="bi bi-clock-history"></i>
                    </button>
                    <button class="ocr-card__add" onclick="abrirModalOcorrencia(${turma.id_turma}, '${esc(turma.nome_fantasia_turma || turma.nome_turma)}')" title="Adicionar ocorrência">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>`;
        }

        const listaDesk = document.getElementById('listaOcorrenciasDesktop');
        const listaMob = document.getElementById('listaOcorrenciasMobile');
        const turmas = turmasFiltradas();

        if (turmas.length === 0) {
            const msg = '<div class="text-center text-muted py-5 sgi-inline-c5f53f82" ><i class="bi bi-inbox sgi-inline-43389611" ></i>Nenhuma turma encontrada.</div>';
            listaDesk.innerHTML = msg;
            listaMob.innerHTML = msg;
        } else {
            listaDesk.innerHTML = turmas.map(renderCard).join('');
            listaMob.innerHTML = turmas.map(renderCard).join('');
        }
    }

    function abrirModalOcorrencia(idTurma, nomeTurma) {
        modalTurmaId = idTurma;
        document.getElementById('modalTurmaNome').innerText = nomeTurma;
        document.getElementById('ocrTituloModal').value = '';
        document.getElementById('ocrPontosModal').value = '';
        document.getElementById('msgOcrModal').innerHTML = '';

        const modal = new bootstrap.Modal(document.getElementById('modalNovaOcorrencia'));
        modal.show();
    }

    async function salvarOcorrenciaModal() {
        const titulo = document.getElementById('ocrTituloModal').value.trim();
        const pontos = parseInt(document.getElementById('ocrPontosModal').value) || 0;
        const data = new Date().toISOString().split('T')[0];
        const msgEl = document.getElementById('msgOcrModal');
        const btnEl = document.getElementById('btnSalvarOcrModal');

        if (!titulo || !modalTurmaId) {
            msgEl.innerHTML = '<span class="sgi-inline-bb8f15e7">Preencha o título.</span>';
            return;
        }

        btnEl.disabled = true;
        const originalText = btnEl.innerHTML;
        btnEl.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';

        try {
            const resp = await fetch(`${API_BASE}/ocorrencias_turmas.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    turmas_id_turma: Number(modalTurmaId),
                    interclasses_id_interclasse: Number(idInterclasse),
                    titulo_ocorrencia: titulo,
                    pontos_descontados: pontos,
                    data_ocorrencia: data
                })
            });
            const result = await resp.json();

            if (result.success) {
                msgEl.innerHTML = '<span class="sgi-inline-fc3b2320">Ocorrência registrada!</span>';
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaOcorrencia'));
                    if (modal) modal.hide();
                    msgEl.innerHTML = '';
                }, 1000);
            } else {
                msgEl.innerHTML = '<span class="sgi-inline-bb8f15e7">' + esc(result.message || 'Erro ao salvar.') + '</span>';
            }
        } catch (e) {
            msgEl.innerHTML = '<span class="sgi-inline-bb8f15e7">Erro de conexão.</span>';
        } finally {
            btnEl.disabled = false;
            btnEl.innerHTML = originalText;
        }
    }

    function abrirHistoricoTurma(idTurma, nomeTurma) {
        historicoTurmaId = idTurma;
        document.getElementById('modalHistoricoTurmaNome').innerText = nomeTurma;
        const modal = new bootstrap.Modal(document.getElementById('modalHistoricoOcorrencias'));
        modal.show();
        carregarHistorico(idTurma);
    }

    async function carregarHistorico(idTurma) {
        const conteudo = document.getElementById('historicoConteudo');
        conteudo.innerHTML = '<div class="spinner-border text-danger" role="status"></div>';

        try {
            if (!idInterclasse) return;
            const res = await fetch(`${API_BASE}/ocorrencias_turmas.php?id_interclasse=${idInterclasse}&id_turma=${idTurma}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros) || historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhuma ocorrência registrada para esta turma.</p>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
            html += '<thead><tr class="table-light"><th>Data</th><th>Título</th><th>Descrição</th><th class="text-center">Pontos</th><th class="text-center">Ação</th></tr></thead><tbody>';

            historicoRegistros.forEach(r => {
                html += '<tr>';
                html += '<td class="small text-muted">' + esc(r.data_ocorrencia) + '</td>';
                html += '<td>' + esc(r.titulo_ocorrencia) + '</td>';
                html += '<td class="small text-muted">' + esc(r.descricao_ocorrencia || '-') + '</td>';
                html += '<td class="text-center"><span class="ocr-badge ocr-badge--pontos">-' + r.pontos_descontados + ' pts</span></td>';
                html += '<td class="text-center">';
                html += '<button class="btn btn-outline-danger btn-sm" title="Remover" onclick="removerOcorrencia(' + r.id_ocorrencia_turma + ')"><i class="bi bi-trash"></i></button>';
                html += '</td></tr>';
            });

            html += '</tbody></table></div>';
            conteudo.innerHTML = html;
        } catch (e) {
            conteudo.innerHTML = '<p class="text-danger">Erro ao carregar histórico.</p>';
        }
    }

    async function removerOcorrencia(id) {
        if (!confirm('Tem certeza que deseja remover esta ocorrência?')) return;

        try {
            const res = await fetch(`${API_BASE}/ocorrencias_turmas.php`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_ocorrencia_turma: id })
            });
            const result = await res.json();
            if (result.success) {
                alert('Ocorrência removida!');
                carregarHistorico(historicoTurmaId);
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (e) {
            alert('Erro de conexão.');
        }
    }

    window.SGIPage.ready( carregarDados);

return {esc, resolverInterclasse, carregarDados, turmasFiltradas, carregarLista, abrirModalOcorrencia, salvarOcorrenciaModal, abrirHistoricoTurma, carregarHistorico, removerOcorrencia};
});
