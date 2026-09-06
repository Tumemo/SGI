window.SGIPage.mount("eventos/configurar-arrecadacao", function (pageConfig, pageScope) {

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    const storagePrefix = 'sgi_items_';
    const paramsArrecadacao = new URLSearchParams(window.location.search);
    const idInterclasseArrecadacao = paramsArrecadacao.get('id');
    const isAdminPage = pageConfig.value2;

    let todasAsTurmas = [];
    let idInterclasseResolvida = null;

    function getQuantidadePendente(turma) {
        const local = localStorage.getItem(`${storagePrefix}${turma.id_turma}`);
        return local !== null ? Number(local) : 0;
    }

    function salvarLocal(idTurma, valor) {
        localStorage.setItem(`${storagePrefix}${idTurma}`, String(valor));
    }

    function renderCard(turma) {
        const nome = esc(turma.nome_fantasia_turma || turma.nome_turma);
        const nomeJs = (turma.nome_fantasia_turma || turma.nome_turma || '').replace(/'/g, "\\'");
        return `
            <div class="ocr-card">
                <div class="ocr-card__icon"><i class="bi bi-people-fill"></i></div>
                <div class="ocr-card__info">
                    <p class="ocr-card__name">${nome}</p>
                    <span class="ocr-card__badge">${esc(turma.nome_categoria || 'Geral')}</span>
                </div>
                <div class="ocr-card__input-wrap">
                    <input type="number" step="0.1" min="0" class="ocr-card__input arrec-input"
                        data-id-turma="${turma.id_turma}"
                        value="${getQuantidadePendente(turma)}" placeholder="0">
                    <span class="ocr-card__input-suffix">Kg</span>
                </div>
                <button type="button" class="ocr-card__hist" onclick="abrirHistoricoTurma(${turma.id_turma}, '${nomeJs}')" title="Ver histórico">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button type="button" class="ocr-card__save" data-id-turma="${turma.id_turma}" onclick="salvarTurma(${turma.id_turma})" title="Salvar">
                    <i class="bi bi-check-lg"></i>
                </button>
            </div>
        `;
    }

    function renderizarTelas() {
        const listaMobile = document.getElementById('listaArrecadacaoMobile');
        const listaDesktop = document.getElementById('listaArrecadacaoDesktop');

        if (todasAsTurmas.length === 0) {
            const msg = '<div class="text-center text-muted py-5 sgi-inline-c5f53f82" ><i class="bi bi-inbox sgi-inline-43389611" ></i>Nenhuma turma encontrada.</div>';
            listaMobile.innerHTML = msg;
            listaDesktop.innerHTML = msg;
            return;
        }

        listaMobile.innerHTML = todasAsTurmas.map(renderCard).join('');
        listaDesktop.innerHTML = todasAsTurmas.map(renderCard).join('');

        vincularEventosInputs();
    }

    function getInputVisivel(idTurma) {
        const inputs = document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`);
        for (const input of inputs) {
            if (input.offsetParent !== null) return input;
        }
        return inputs[0] || null;
    }

    function getQuantidadeAtual(idTurma) {
        const input = getInputVisivel(idTurma);
        if (input && input.value !== '') {
            return Number(input.value);
        }
        return getQuantidadePendente({ id_turma: idTurma });
    }

    function vincularEventosInputs() {
        document.querySelectorAll('.arrec-input').forEach(input => {
            pageScope.listen(input, 'input', (e) => {
                const idTurma = e.target.dataset.idTurma;
                const valor = e.target.value;
                salvarLocal(idTurma, valor);
                document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`).forEach(inp => {
                    if (inp !== e.target) inp.value = valor;
                });
            });
        });
    }

    pageScope.listen(window, 'beforeunload', () => {
        const pendentes = todasAsTurmas.some(t => getQuantidadePendente(t) > 0);
        if (pendentes) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../../../api/arrecadacao.php', false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            const payload = {
                id_interclasse: idInterclasseResolvida || idInterclasseArrecadacao,
                arrecadacoes: todasAsTurmas.map(t => ({
                    id_turma: t.id_turma,
                    quantidade: getQuantidadePendente(t)
                })).filter((item) => item.quantidade > 0)
            };
            xhr.send(JSON.stringify(payload));
        }
    });

    async function carregarDados() {
        try {
            const ativo = idInterclasseArrecadacao
                ? await window.SGIInterclasse.getInterclasseById(idInterclasseArrecadacao)
                : await window.SGIInterclasse.getActiveInterclasse();

            if (!ativo) return;

            idInterclasseResolvida = ativo.id_interclasse;

            document.getElementById('nomeInterclasseArrecadacao').innerText = ativo.nome_interclasse;
            const nomeMob = document.getElementById('nomeInterclasseArrecadacaoMob');
            if (nomeMob) nomeMob.innerText = ativo.nome_interclasse;
            const vDesk = document.getElementById('btnVoltarArrecadacao');
            if (vDesk) {
                vDesk.href = `./dashboard.php?id=${idInterclasseArrecadacao || ativo.id_interclasse}`;
            }
            const vMob = document.getElementById('btnVoltarArrecadacaoMob');
            if (vMob) {
                vMob.href = `./dashboard.php?id=${idInterclasseArrecadacao || ativo.id_interclasse}`;
            }

            const res = await fetch(`../../../api/turmas.php?id_interclasse=${ativo.id_interclasse}`);
            todasAsTurmas = await res.json();

            renderizarTelas();
        } catch (error) {
            console.error("Erro ao carregar dados:", error);
        }
    }

    async function salvarTurma(idTurma) {
        const input = getInputVisivel(idTurma);
        const quantidade = getQuantidadeAtual(idTurma);

        if (!quantidade || quantidade <= 0) {
            alert('Informe a quantidade em kg a adicionar.');
            return;
        }

        if (input) salvarLocal(idTurma, input.value);

        const botoes = document.querySelectorAll(`.ocr-card__save[data-id-turma="${idTurma}"]`);
        botoes.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        });

        const payload = {
            id_interclasse: idInterclasseResolvida || idInterclasseArrecadacao,
            arrecadacoes: [{ id_turma: Number(idTurma), quantidade }]
        };

        try {
            const response = await fetch('../../../api/arrecadacao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                throw new Error(`Erro de rede: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                localStorage.removeItem(`${storagePrefix}${idTurma}`);
                document.querySelectorAll(`.arrec-input[data-id-turma="${idTurma}"]`).forEach(inp => {
                    inp.value = '0';
                });
                alert('Dados salvos com sucesso!');
            } else {
                alert('Erro do servidor: ' + result.message);
            }
        } catch (error) {
            alert('Erro de comunicação: Verifique se o ficheiro api/arrecadacao.php existe e se o banco de dados está online.');
            console.error('Falha no salvamento:', error);
        } finally {
            botoes.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            });
        }
    }

    let historicoRegistros = [];
    let filtroHistoricoAtual = 'adicionados';
    let historicoTurmaId = null;

    function abrirHistoricoTurma(idTurma, nomeTurma) {
        historicoTurmaId = idTurma;
        document.getElementById('modalHistoricoTurmaNome').innerText = nomeTurma;
        const modal = new bootstrap.Modal(document.getElementById('modalHistoricoArrecadacao'));
        modal.show();
        carregarHistorico(idTurma);
    }

    async function carregarHistorico(idTurma) {
        const conteudo = document.getElementById('historicoConteudo');
        conteudo.innerHTML = '<div class="spinner-border text-danger" role="status"><span class="visually-hidden">A carregar...</span></div>';

        if (pageConfig.value0) {
        const btnAdic = document.getElementById('btnFiltroAdicionados');
        const btnExcl = document.getElementById('btnFiltroExcluidos');
        if (btnAdic && btnExcl) {
            btnAdic.style.backgroundColor = 'var(--vermelho)';
            btnAdic.style.color = 'white';
            btnAdic.style.borderColor = 'var(--vermelho)';
            btnExcl.style.backgroundColor = '#f0f0f0';
            btnExcl.style.color = '#555';
            btnExcl.style.borderColor = '#e0e0e0';
        }
        filtroHistoricoAtual = 'adicionados';
        }

        const idInterclasse = idInterclasseResolvida || idInterclasseArrecadacao;
        if (!idInterclasse) {
            conteudo.innerHTML = '<p class="text-muted">Nenhuma interclasse selecionada.</p>';
            return;
        }

        try {
            const res = await fetch(`../../../api/arrecadacao.php?id_interclasse=${idInterclasse}`);
            historicoRegistros = await res.json();

            if (!Array.isArray(historicoRegistros)) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhum registro de arrecadação encontrado.</p>';
                return;
            }

            historicoRegistros = historicoRegistros.filter(r => Number(r.id_turma) === Number(idTurma));

            if (historicoRegistros.length === 0) {
                conteudo.innerHTML = '<p class="text-muted py-3">Nenhum registro de arrecadação encontrado para esta turma.</p>';
                return;
            }

            renderizarHistoricoFiltrado();

        } catch (error) {
            console.error("Erro ao carregar histórico:", error);
            conteudo.innerHTML = '<p class="text-danger">Erro ao carregar histórico. Verifique a ligação ao banco de dados.</p>';
        }
    }

    function filtrarHistorico(filtro) {
        filtroHistoricoAtual = filtro;

        if (pageConfig.value0) {
        const btnAdic = document.getElementById('btnFiltroAdicionados');
        const btnExcl = document.getElementById('btnFiltroExcluidos');

        if (filtro === 'adicionados') {
            btnAdic.style.backgroundColor = 'var(--vermelho)';
            btnAdic.style.color = 'white';
            btnAdic.style.borderColor = 'var(--vermelho)';
            btnExcl.style.backgroundColor = '#f0f0f0';
            btnExcl.style.color = '#555';
            btnExcl.style.borderColor = '#e0e0e0';
        } else {
            btnExcl.style.backgroundColor = 'var(--vermelho)';
            btnExcl.style.color = 'white';
            btnExcl.style.borderColor = 'var(--vermelho)';
            btnAdic.style.backgroundColor = '#f0f0f0';
            btnAdic.style.color = '#555';
            btnAdic.style.borderColor = '#e0e0e0';
        }
        }

        renderizarHistoricoFiltrado();
    }

    function renderizarHistoricoFiltrado() {
        const conteudo = document.getElementById('historicoConteudo');
        const lista = historicoRegistros.filter(r => {
            if (isAdminPage) {
                return filtroHistoricoAtual === 'adicionados' ? r.status_historico === '1' : r.status_historico === '0';
            }
            return r.status_historico === '1';
        });

        if (lista.length === 0) {
            const msg = isAdminPage && filtroHistoricoAtual === 'excluidos'
                ? '<p class="text-muted py-3">Nenhum registro excluído encontrado.</p>'
                : '<p class="text-muted py-3">Nenhum registro adicionado encontrado.</p>';
            conteudo.innerHTML = msg;
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover align-middle">';
        html += '<thead><tr class="table-light"><th>Data</th><th class="text-center">Kg</th><th class="text-center">Pts</th>';

        if (isAdminPage && filtroHistoricoAtual === 'adicionados') {
            html += '<th class="text-center">Ação</th>';
        } else if (isAdminPage && filtroHistoricoAtual === 'excluidos') {
            html += '<th class="text-center">Estado</th>';
        }

        html += '</tr></thead><tbody>';

        lista.forEach(r => {
            const data = new Date(r.data_registro);
            const dataFmt = data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

            html += '<tr>';

            if (isAdminPage && filtroHistoricoAtual === 'excluidos') {
                html += '<td class="small text-muted">' + esc(dataFmt) + '</td>';
                html += '<td class="text-center text-muted">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-danger">-' + r.pontos_adicionados + '</td>';
                html += '<td class="text-center"><span class="badge bg-secondary">Removido</span></td>';
            } else {
                html += '<td class="small">' + esc(dataFmt) + '</td>';
                html += '<td class="text-center">' + r.quantidade + '</td>';
                html += '<td class="text-center fw-bold text-success">+' + r.pontos_adicionados + '</td>';
                if (isAdminPage) {
                    html += '<td class="text-center">';
                    html += '<button class="btn btn-outline-danger btn-sm" title="Remover e reverter pontos" onclick="deletarHistorico(' + r.id_historico + ')">';
                    html += '<i class="bi bi-trash"></i>';
                    html += '</button>';
                    html += '</td>';
                }
            }

            html += '</tr>';
        });

        html += '</tbody></table></div>';
        conteudo.innerHTML = html;
    }

    async function deletarHistorico(idHistorico) {
        if (!confirm('Tem certeza que deseja remover este registro?\nOs pontos serão subtraídos automaticamente do ranking.')) {
            return;
        }

        const idInterclasse = idInterclasseResolvida || idInterclasseArrecadacao;

        try {
            const res = await fetch('../../../api/arrecadacao.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_historico: idHistorico, id_interclasse: idInterclasse })
            });

            const result = await res.json();

            if (result.success) {
                alert('Registro removido e pontos revertidos com sucesso!');
                carregarHistorico(historicoTurmaId);
            } else {
                alert('Erro: ' + result.message);
            }
        } catch (error) {
            console.error("Erro ao deletar:", error);
            alert('Erro de comunicação ao tentar remover o registro.');
        }
    }

    window.SGIPage.ready( carregarDados);

return {esc, getQuantidadePendente, salvarLocal, renderCard, renderizarTelas, getInputVisivel, getQuantidadeAtual, vincularEventosInputs, carregarDados, salvarTurma, abrirHistoricoTurma, carregarHistorico, filtrarHistorico, renderizarHistoricoFiltrado, deletarHistorico};
});
