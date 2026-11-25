window.SGIPage.mount('resultados/relatorio-final', function (_pageConfig, pageScope) {
    'use strict';

    const APP_BASE = String(window.SGI_BASE_PATH || '').replace(/\/+$/, '');
    const API_BASE = String(window.SGI_API_BASE || `${APP_BASE}/api/v1/`).replace(/\/?$/, '');
    const params = new URLSearchParams(window.location.search);
    const elements = {
        status: document.getElementById('sgiRelatorioStatus'),
        content: document.getElementById('sgiRelatorioConteudo'),
        edition: document.getElementById('sgiRelatorioEdicao'),
        pdf: document.getElementById('btnGerarRelatorio'),
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
    const number = (value, decimals = 0) => new Intl.NumberFormat('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(Number(value) || 0);
    const widthClass = (value, maximum) => `sgi-relatorio-barra--width-${Math.round(Math.max(0, Number(value) || 0) / Math.max(1, Number(maximum) || 1) * 10) * 10}`;

    function setStatus(message, kind = 'info') {
        elements.status.className = `alert alert-${kind}`;
        elements.status.textContent = message;
        elements.status.classList.remove('d-none');
    }

    function renderMetrics(report) {
        const metrics = report.metricas;
        const categoryCounts = (metrics.turmas_por_categoria || [])
            .map((entry) => `${escapeHtml(entry.nome_categoria)}: ${number(entry.total_turmas)}`)
            .join(' · ');
        const donationLeader = metrics.lider_doacoes;
        const absoluteLeader = metrics.lider_absoluta;
        document.getElementById('sgiRelatorioMetricas').innerHTML = [
            `<article class="sgi-relatorio-indicador"><span>Turmas participantes</span><strong>${number(metrics.turmas_total)}</strong><small>${number(metrics.categorias_total)} categorias${categoryCounts ? ` · ${categoryCounts}` : ''}</small></article>`,
            `<article class="sgi-relatorio-indicador"><span>Doações solidárias</span><strong>${number(metrics.doacoes_total_kg, 2)} kg</strong><small>${donationLeader ? `${escapeHtml(donationLeader.nome_turma)} lidera com ${number(donationLeader.quantidade_kg, 2)} kg` : 'Sem registro de doações'}</small></article>`,
            `<article class="sgi-relatorio-indicador"><span>Modalidades</span><strong>${number(metrics.modalidades_total)}</strong><small>no interclasse selecionado</small></article>`,
            `<article class="sgi-relatorio-indicador"><span>Líder absoluta</span><strong>${absoluteLeader ? escapeHtml(absoluteLeader.nome_turma) : 'Sem classificação'}</strong><small>${number(absoluteLeader?.pontuacao_liquida)} pontos líquidos</small></article>`,
        ].join('');
    }

    function renderChart(targetId, entries, valueKey, maxValue, suffix, variant) {
        const target = document.getElementById(targetId);
        if (!entries.length) {
            target.innerHTML = '<p class="text-body-secondary mb-0">Sem dados registrados nesta edição.</p>';
            return;
        }
        target.innerHTML = entries.map((entry) => {
            const title = valueKey === 'pontuacao_liquida'
                ? `${number(entry.posicao)}º · ${escapeHtml(entry.nome_turma)}`
                : escapeHtml(entry.nome_turma);
            return `<div class="sgi-relatorio-barra-linha"><span>${title}</span><span class="sgi-relatorio-barra-trilho"><span class="sgi-relatorio-barra sgi-relatorio-barra--${variant} ${widthClass(entry[valueKey], maxValue)}"></span></span><strong>${number(entry[valueKey], suffix === 'kg' ? 2 : 0)} ${suffix}</strong></div>`;
        }).join('');
    }

    function renderPenaltyDetails(penalties) {
        if (!penalties.length) return '<span class="text-body-secondary">Sem penalidades registradas</span>';
        return `<ul class="sgi-relatorio-lista-detalhes">${penalties.map((penalty) => {
            const person = penalty.aluno ? ` · ${escapeHtml(penalty.aluno)}` : '';
            const description = penalty.descricao ? ` — ${escapeHtml(penalty.descricao)}` : '';
            return `<li><strong>${escapeHtml(penalty.titulo)}</strong>${person}${description}</li>`;
        }).join('')}</ul>`;
    }

    function renderCategory(category, report) {
        const highlights = new Map();
        report.modalidades.forEach((modality) => modality.podios.forEach((podium) => {
            const list = highlights.get(Number(podium.id_turma)) || [];
            list.push(`${modality.nome_modalidade} — ${podium.posicao}º lugar`);
            highlights.set(Number(podium.id_turma), list);
        }));
        const rows = category.ranking.map((entry) => `<tr>
            <td>${number(entry.posicao)}º</td>
            <th scope="row"><strong>${escapeHtml(entry.nome_turma)}</strong>${entry.nome_fantasia_turma ? `<small class="d-block text-body-secondary">${escapeHtml(entry.nome_fantasia_turma)}</small>` : ''}</th>
            <td>${number(entry.doacoes_kg, 2)} kg</td>
            <td>${renderPenaltyDetails(entry.penalidades)}</td>
            <td>${number(entry.penalidades_pontos)} pts</td>
            <td><strong>${number(entry.pontuacao_liquida)} pts</strong></td>
            <td>${escapeHtml((highlights.get(Number(entry.id_turma)) || []).join(', ') || '—')}</td>
        </tr>`).join('');
        return `<section class="sgi-relatorio-secao card shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div><h2 class="h4 fw-bold mb-1">${escapeHtml(category.nome_categoria)} — resumo e classificação final</h2><p class="text-body-secondary small mb-0">${number(category.total_turmas)} turmas participantes</p></div>
                    <span class="badge text-bg-light border text-body-secondary">${number(category.pontuacao_total)} pontos líquidos somados</span>
                </div>
                <div class="table-responsive"><table class="table table-sm align-middle sgi-relatorio-tabela"><thead><tr><th scope="col">Pos.</th><th scope="col">Turma</th><th scope="col">Doações</th><th scope="col">Penalidades identificadas</th><th scope="col">Descontos</th><th scope="col">Total líquido</th><th scope="col">Destaques esportivos</th></tr></thead><tbody>${rows || '<tr><td colspan="7" class="text-body-secondary">Nenhuma turma cadastrada nesta categoria.</td></tr>'}</tbody></table></div>
            </div>
        </section>`;
    }

    function renderModalities(report) {
        const target = document.getElementById('sgiRelatorioModalidades');
        if (!report.modalidades.length) {
            target.innerHTML = '<p class="text-body-secondary mb-0">Nenhuma modalidade cadastrada nesta edição.</p>';
            return;
        }
        target.innerHTML = report.modalidades.map((modality) => {
            const podiumRows = modality.podios.map((podium) => `<tr><td>${number(podium.posicao)}º lugar</td><td>${escapeHtml(podium.nome_turma)}</td><td>${escapeHtml(podium.destaque)}</td><td>${number(podium.pontos)} pts</td></tr>`).join('');
            return `<article class="sgi-relatorio-modalidade card border-0 bg-body-tertiary">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><div><h3 class="h5 fw-bold mb-1">${escapeHtml(modality.nome_modalidade)}</h3><p class="small text-body-secondary mb-0">${escapeHtml(modality.nome_categoria)} · ${escapeHtml(modality.nome_tipo_modalidade)} · ${escapeHtml(modality.genero_modalidade)}</p></div><span class="badge ${modality.status_modalidade === '1' ? 'text-bg-success' : 'text-bg-secondary'} align-self-start">${modality.status_modalidade === '1' ? 'Ativa' : 'Inativa'}</span></div>
                    ${podiumRows ? `<div class="table-responsive"><table class="table table-sm align-middle sgi-relatorio-tabela"><thead><tr><th scope="col">Colocação</th><th scope="col">Turma</th><th scope="col">Destaque</th><th scope="col">Pontos</th></tr></thead><tbody>${podiumRows}</tbody></table></div>` : '<p class="small text-body-secondary mb-0">Sem pódios registrados.</p>'}
                </div>
            </article>`;
        }).join('');
    }

    function renderArtillery(report) {
        const eligible = report.modalidades.filter((modality) => modality.contabiliza_artilharia);
        const section = document.getElementById('sgiRelatorioSecaoArtilharia');
        section.classList.toggle('d-none', eligible.length === 0);
        if (!eligible.length) return;
        document.getElementById('sgiRelatorioArtilheiros').innerHTML = eligible.map((modality) => {
            const rows = modality.artilheiros.map((scorer) => `<tr><td>${number(scorer.posicao)}º</td><th scope="row">${escapeHtml(scorer.nome_usuario)}</th><td>${escapeHtml(scorer.nome_turma)}</td><td>${number(scorer.gols)}</td></tr>`).join('');
            return `<section class="sgi-relatorio-artilharia"><h3 class="h6 fw-bold">${escapeHtml(modality.nome_categoria)} · ${escapeHtml(modality.nome_modalidade)}</h3>${rows ? `<div class="table-responsive"><table class="table table-sm align-middle sgi-relatorio-tabela"><thead><tr><th scope="col">Pos.</th><th scope="col">Competidor</th><th scope="col">Turma</th><th scope="col">Gols válidos</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<p class="small text-body-secondary">Sem registros de artilharia nesta modalidade.</p>'}</section>`;
        }).join('');
    }

    function renderDonations(report) {
        const target = document.getElementById('sgiRelatorioTabelaDoacoes');
        target.innerHTML = report.doacoes.por_turma.map((entry, index) => `<tr><td>${number(index + 1)}º</td><th scope="row">${escapeHtml(entry.nome_turma)}</th><td>${escapeHtml(entry.nome_categoria)}</td><td>${number(entry.quantidade_kg, 2)} kg</td><td>${number(entry.pontuacao)} pts</td></tr>`).join('') || '<tr><td colspan="5" class="text-body-secondary">Nenhuma doação registrada nesta edição.</td></tr>';
        document.getElementById('sgiRelatorioTotalDoacoesTabela').innerHTML = `Total arrecadado: <strong>${number(report.doacoes.total_kg, 2)} kg</strong>`;
    }

    async function resolveEditionId() {
        const queryId = Number(params.get('id') || params.get('id_interclasse'));
        if (Number.isInteger(queryId) && queryId > 0) return queryId;
        const active = await window.SGIInterclasse.getActiveInterclasse();
        const activeId = Number(active?.id_interclasse || 0);
        if (activeId > 0) {
            window.history.replaceState(null, '', `${APP_BASE}/relatorios?id=${activeId}`);
            return activeId;
        }
        return 0;
    }

    async function init() {
        try {
            const editionId = await resolveEditionId();
            if (!editionId) {
                setStatus('Selecione um interclasse na lista de edições para consultar o relatório.', 'warning');
                return;
            }
            const response = await fetch(`${API_BASE}/relatorio-final?id_interclasse=${encodeURIComponent(editionId)}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok || payload?.success !== true || !payload.relatorio) {
                throw new Error(payload?.message || 'A consulta do relatório foi recusada.');
            }

            const report = payload.relatorio;
            elements.edition.textContent = `${report.edicao.nome_interclasse} · ${report.edicao.ano}`;
            elements.pdf.href = `${API_BASE}/relatorio-final/pdf?id_interclasse=${encodeURIComponent(editionId)}`;
            elements.pdf.setAttribute('aria-disabled', 'false');
            elements.pdf.removeAttribute('tabindex');
            renderMetrics(report);
            renderChart('sgiRelatorioGraficoPontos', report.ranking_geral, 'pontuacao_liquida', report.ranking_geral[0]?.pontuacao_liquida || 1, 'pts', 'pontos');
            renderChart('sgiRelatorioGraficoDoacoes', report.doacoes.por_turma, 'quantidade_kg', report.metricas.doacoes_total_kg ? Math.max(...report.doacoes.por_turma.map((entry) => Number(entry.quantidade_kg) || 0)) : 1, 'kg', 'doacoes');
            document.getElementById('sgiRelatorioTotalDoacoes').textContent = `Total registrado: ${number(report.doacoes.total_kg, 2)} kg`;
            document.getElementById('sgiRelatorioCategorias').innerHTML = report.categorias.map((category) => renderCategory(category, report)).join('') || '<section class="card shadow-sm"><div class="card-body text-body-secondary">Nenhuma categoria cadastrada nesta edição.</div></section>';
            document.getElementById('sgiRelatorioObservacao').textContent = report.observacao;
            document.getElementById('sgiRelatorioRegraDoacao').textContent = `A pontuação de doações considera ${number(report.edicao.valor_item_arrecadacao)} ponto(s) por kg conforme configuração da edição.`;
            renderModalities(report);
            renderArtillery(report);
            renderDonations(report);
            elements.content.classList.remove('d-none');
            setStatus('Relatório consolidado com os dados do interclasse selecionado.', 'success');
        } catch (error) {
            console.error('Falha ao carregar relatório final:', error);
            setStatus(error instanceof Error ? error.message : 'Não foi possível carregar o relatório.', 'danger');
        }
    }

    pageScope.listen(elements.pdf, 'click', (event) => {
        if (elements.pdf.getAttribute('aria-disabled') === 'true') event.preventDefault();
    });

    void init();
    return {};
});
