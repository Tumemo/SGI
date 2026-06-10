<?php
$tituloPagina = 'SGI - Mesário - Individual';
$titulo = 'Individual';
$mostrarVoltar = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
include 'componentes/header.php';
include 'componentes/nav.php';
?>

<style>
    .podium-card {
        background: white;
        border-radius: 22px;
        border: none;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }
    .podium-card .pos-1 {
        border-left: 5px solid #ffd700;
    }
    .podium-card .pos-2 {
        border-left: 5px solid #c0c0c0;
    }
    .podium-card .pos-3 {
        border-left: 5px solid #cd7f32;
    }
    .podium-place {
        font-weight: 700;
        color: #1f2937;
    }
    .medal {
        font-size: 1.4rem;
    }
    .select-wrapper {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }
    .btn-salvar {
        background: #E30613;
        border: none;
        padding: 12px 32px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
    }
    .btn-salvar:hover {
        background: #bb0812;
    }
</style>

<main class="main-desktop-layout py-4">
    <div class="container-fluid" style="max-width: 720px;">

        <div class="mb-4">
            <a href="./home.php"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-3 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                <span id="nomeInterclasseIndividual">Interclasse</span>
            </a>
            <h2 class="fw-bold d-flex align-items-center gap-2">
                <i class="bi bi-trophy"></i>
                Ranking Individual
            </h2>
            <p class="text-muted mb-0">
                Registre o 1º, 2º e 3º lugar das modalidades individuais (corrida, natação, etc.).
            </p>
        </div>

        <div class="select-wrapper mb-4">
            <label class="form-label fw-semibold" for="selectModalidadeIndividual">
                <i class="bi bi-trophy me-1"></i> Modalidade
            </label>
            <select class="form-select" id="selectModalidadeIndividual">
                <option value="">Selecione uma modalidade</option>
            </select>
        </div>

        <div id="rankingContainer">
            <div class="text-center text-muted py-5">
                <i class="bi bi-hand-index fs-1 d-block mb-2"></i>
                <p>Selecione uma modalidade individual acima.</p>
            </div>
        </div>

    </div>
</main>

<div id="toastMensagem" class="position-fixed top-0 start-50 translate-middle-x z-3 p-3" style="display:none; margin-top: 10px;">
    <div class="d-flex align-items-center gap-2 px-4 py-3 rounded-3 shadow-lg" id="toastConteudo" style="min-width: 280px; background: white; border-left: 5px solid #198754;">
        <i class="bi fs-4" id="toastIcone"></i>
        <span class="fw-semibold" id="toastTexto"></span>
    </div>
</div>

<script>
    const API = '../../../../api/';
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    function mostrarToast(tipo, texto) {
        const container = document.getElementById('toastMensagem');
        const conteudo = document.getElementById('toastConteudo');
        const icone = document.getElementById('toastIcone');
        const txt = document.getElementById('toastTexto');
        const cor = tipo === 'sucesso' ? '#198754' : '#dc3545';
        const iconeNome = tipo === 'sucesso' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger';
        conteudo.style.borderLeftColor = cor;
        icone.className = `bi ${iconeNome} fs-4`;
        txt.textContent = texto;
        container.style.display = 'block';
        clearTimeout(container._timer);
        container._timer = setTimeout(() => { container.style.display = 'none'; }, 4000);
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function getTipoIndividualId() {
        try {
            const resp = await fetch('../../../api/tipoModalidade.php');
            const data = await resp.json();
            const tipos = Array.isArray(data) ? data : (data.data || []);
            const ind = tipos.find(t => t.nome_tipo_modalidade === 'Individual');
            return ind ? Number(ind.id_tipo_modalidade) : null;
        } catch {
            return null;
        }
    }

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo encontrado.');
            window.location.href = 'home.php';
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        document.getElementById('nomeInterclasseIndividual').innerText = dados?.nome_interclasse || 'Interclasse';
        return idInterclasse;
    }

    async function carregarModalidades() {
        const select = document.getElementById('selectModalidadeIndividual');
        try {
            const [resp, tipoId] = await Promise.all([
                fetch(`${API}modalidades.php?id_interclasse=${idInterclasse}`),
                getTipoIndividualId()
            ]);
            const data = await resp.json();
            const modalidades = Array.isArray(data) ? data : [];

            if (tipoId === null || !modalidades.some(m => Number(m.id_tipo_modalidade) === tipoId)) {
                document.getElementById('rankingContainer').innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-info-circle fs-1 d-block mb-2"></i>
                        <p>Nenhuma modalidade individual encontrada neste interclasse.</p>
                    </div>`;
                return;
            }

            select.innerHTML = '<option value="">Selecione uma modalidade</option>';
            modalidades.forEach(mod => {
                if (Number(mod.id_tipo_modalidade) !== tipoId) return;
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                select.innerHTML += `<option value="${mod.id_modalidade}">${esc(mod.nome_modalidade)}${genero}${categoria}</option>`;
            });
        } catch (e) {
            console.error('Erro ao carregar modalidades:', e);
        }
    }

    async function carregarRanking(idModalidade) {
        const container = document.getElementById('rankingContainer');

        if (!idModalidade) {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-hand-index fs-1 d-block mb-2"></i>
                    <p>Selecione uma modalidade individual acima.</p>
                </div>`;
            return;
        }

        container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-danger" role="status"></div><p class="text-muted mt-2">Carregando...</p></div>`;

        try {
            const [resParticipantes, resRanking] = await Promise.all([
                fetch(`${API}chaveamento.php?tipo_modalidade=individual&acao=participantes&id_modalidade=${idModalidade}`),
                fetch(`${API}chaveamento.php?tipo_modalidade=individual&acao=ranking&id_modalidade=${idModalidade}`)
            ]);

            const dadosParticipantes = await resParticipantes.json();
            const dadosRanking = await resRanking.json();

            const participantes = (dadosParticipantes.success && dadosParticipantes.participantes) ? dadosParticipantes.participantes : [];
            const rankingAtual = (dadosRanking.success && dadosRanking.ranking) ? dadosRanking.ranking : [];

            if (participantes.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        <p class="fw-semibold">Nenhum participante encontrado</p>
                        <p class="small">Vincule alunos às equipes desta modalidade primeiro.</p>
                    </div>`;
                return;
            }

            const primeiroAtual = rankingAtual.find(r => r.posicao === 1);
            const segundoAtual = rankingAtual.find(r => r.posicao === 2);
            const terceiroAtual = rankingAtual.find(r => r.posicao === 3);

            const selectOpts = participantes.map(p =>
                `<option value="${p.id_usuario}">${esc(p.nome_usuario)} — ${esc(p.nome_turma)}</option>`
            ).join('');

            const selectEmpty = '<option value="">Selecione...</option>';

            const html = `
                <div class="podium-card p-4 mb-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-trophy-fill text-warning me-2"></i>Registrar Resultado</h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><span class="medal">🥇</span> 1º Lugar</label>
                            <select class="form-select" id="selectPrimeiro">${selectEmpty}${selectOpts}</select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><span class="medal">🥈</span> 2º Lugar</label>
                            <select class="form-select" id="selectSegundo">${selectEmpty}${selectOpts}</select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><span class="medal">🥉</span> 3º Lugar</label>
                            <select class="form-select" id="selectTerceiro">${selectEmpty}${selectOpts}</select>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-3 align-items-center">
                        <button class="btn-salvar" id="btnSalvarRanking"><i class="bi bi-floppy me-1"></i> Salvar Ranking</button>
                        <span id="msgRanking" class="small"></span>
                    </div>
                </div>

                <div class="podium-card p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-list-ol me-2"></i>Ranking Atual</h5>
                    ${rankingAtual.length === 0 ? `
                        <p class="text-muted small mb-0">Nenhum ranking registrado ainda.</p>
                    ` : rankingAtual.map(r => {
                        const posClass = r.posicao === 1 ? 'pos-1' : r.posicao === 2 ? 'pos-2' : 'pos-3';
                        const medalha = r.posicao === 1 ? '🥇' : r.posicao === 2 ? '🥈' : '🥉';
                        return `
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 ${posClass} mb-1 rounded-2">
                                <span class="podium-place">${medalha} ${esc(r.nome_usuario)}</span>
                                <span class="text-muted small">${esc(r.nome_turma)}</span>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;

            container.innerHTML = html;

            if (primeiroAtual) document.getElementById('selectPrimeiro').value = primeiroAtual.id_usuario;
            if (segundoAtual) document.getElementById('selectSegundo').value = segundoAtual.id_usuario;
            if (terceiroAtual) document.getElementById('selectTerceiro').value = terceiroAtual.id_usuario;

            document.getElementById('btnSalvarRanking').addEventListener('click', () => salvarRanking(idModalidade));

        } catch (e) {
            console.error('Erro ao carregar ranking:', e);
            container.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    <p>Erro ao carregar dados.</p>
                </div>`;
        }
    }

    async function salvarRanking(idModalidade) {
        const primeiro = Number(document.getElementById('selectPrimeiro').value);
        const segundo = Number(document.getElementById('selectSegundo').value);
        const terceiro = Number(document.getElementById('selectTerceiro').value);
        const btn = document.getElementById('btnSalvarRanking');
        const msg = document.getElementById('msgRanking');

        if (!primeiro || !segundo || !terceiro) {
            msg.innerHTML = '<span class="text-danger fw-bold">Selecione 1º, 2º e 3º lugar.</span>';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Salvando...';
        msg.innerHTML = '';

        try {
            const resp = await fetch(`${API}chaveamento.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo_modalidade: 'individual',
                    id_modalidade: idModalidade,
                    ranking: { primeiro, segundo, terceiro }
                })
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Erro ao salvar.');
            mostrarToast('sucesso', 'Ranking salvo com sucesso!');
            await carregarRanking(idModalidade);
        } catch (e) {
            msg.innerHTML = `<span class="text-danger fw-bold">${esc(e.message)}</span>`;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Salvar Ranking';
        }
    }

    document.getElementById('selectModalidadeIndividual').addEventListener('change', function() {
        carregarRanking(this.value);
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await carregarModalidades();
    });
</script>

<?php
require_once '../../componentes/footer.php';
?>
