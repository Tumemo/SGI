<?php
(session_status() === PHP_SESSION_NONE) && session_start();
require_once '../../../../config/db.php';

$id_usuario = (int)($_SESSION['id'] ?? 0);
$genero_usuario = 'MASC';
$categoria_usuario = 0;
$modalidades_inscritas = [];

$idInterclassePagina = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idInterclassePagina <= 0) {
    $resInt = $conn->query("SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' ORDER BY id_interclasse DESC LIMIT 1");
    if ($resInt && $rowInt = $resInt->fetch_assoc()) {
        $idInterclassePagina = (int) $rowInt['id_interclasse'];
    }
}

if ($id_usuario) {
    $stmt = $conn->prepare("SELECT u.genero_usuario, t.categorias_id_categoria, t.id_turma AS turmas_id_turma FROM usuarios u LEFT JOIN turmas t ON u.turmas_id_turma = t.id_turma WHERE u.id_usuario = ?");
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $genero_usuario = $row['genero_usuario'];
        $categoria_usuario = (int)($row['categorias_id_categoria'] ?? 0);
        $turma_usuario = (int)($row['turmas_id_turma'] ?? 0);
    }

    $sql = "SELECT m.id_modalidade, m.nome_modalidade, m.genero_modalidade, 
                   e.id_equipe, c.nome_categoria,
                   m.categorias_id_categoria
            FROM equipes_has_usuarios eu
            JOIN equipes e ON eu.equipes_id_equipe = e.id_equipe
            JOIN modalidades m ON e.modalidades_id_modalidade = m.id_modalidade
            JOIN categorias c ON m.categorias_id_categoria = c.id_categoria
            WHERE eu.usuarios_id_usuario = ? AND e.status_equipe = '1'";
    if ($idInterclassePagina > 0) {
        $sql .= " AND m.interclasses_id_interclasse = $idInterclassePagina";
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $modalidades_inscritas[] = $row;
    }
}

$tituloPagina = 'SGI - Inscrições';
$titulo = 'Inscrições';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = './home.php';
include 'componentes/head.php';
?>



<main class="modalidade-layout">

    <header class="page-header">
        <div class="page-header-inner">
            <span class="trophy-icon"><i class="bi bi-trophy-fill"></i></span>
            <div>
                <h1>Escolha suas modalidades</h1>
                <p class="subtitle">Selecione até 3 modalidades para participar do Interclasse.</p>
            </div>
        </div>
    </header>

    <section class="secao d-none" id="secaoInscricoes">
        <div class="secao-titulo">
            <span class="secao-titulo-icone"><i class="bi bi-person-check-fill"></i></span>
            <div class="secao-titulo-texto">
                <h2>Suas inscrições</h2>
                <p>Modalidades em que você já está confirmado.</p>
            </div>
            <span class="secao-badge" id="badgeInscricoes">0/3</span>
        </div>
        <div id="inscricoesAtuais"></div>
    </section>

    <section class="secao" id="secaoDisponiveis">
        <div class="secao-titulo">
            <span class="secao-titulo-icone"><i class="bi bi-grid-1x2-fill"></i></span>
            <div class="secao-titulo-texto">
                <h2>Disponíveis para escolha</h2>
                <p>Selecione até 3 modalidades para participar.</p>
            </div>
        </div>
        <div class="modalidades-grid" id="modalidadesGrid">
            <div class="col-12 text-center py-5">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Carregando modalidades...
            </div>
        </div>
    </section>

    <div id="acoesInscricao" class="d-none">
        <div class="resumo-selecao">
            <div class="counter-box">
                <span class="counter-label">Modalidades escolhidas</span>
                <div class="counter-value"><span id="counterNum">0</span>&nbsp;<span class="counter-total">/ 3</span></div>
                <span id="statusDefault" class="status-default">Em andamento</span>
                <span id="limiteBadge" class="limite-badge d-none"><i class="bi bi-check-circle-fill"></i> Limite atingido</span>
            </div>

            <div class="resumo-progress">
                <div class="progress-header">
                    <span>Modalidades selecionadas</span>
                    <span id="progressCount" class="progress-count">0 de 3</span>
                </div>
                <div class="progress-track" id="progressTrack">
                    <div class="progress-seg"></div>
                    <div class="progress-seg"></div>
                    <div class="progress-seg"></div>
                </div>
            </div>

            <div class="resumo-actions">
                <button type="button" class="btn-save" id="btnSalvar" onclick="salvarEscolhas()" disabled>
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
            </div>
        </div>

        <p class="bottom-label small text-secondary" id="msgFeedback"></p>

        <p id="contador" class="visually-hidden"></p>
    </div>

</main>

<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-labelledby="modalDetalhesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalDetalhesTitle">Detalhes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalDetalhesCorpo"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEquipes" tabindex="-1" aria-labelledby="modalEquipesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="modalEquipesTitle">Escolha a equipe</h5>
                    <small class="text-muted" id="modalEquipesSubtitulo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalEquipesCorpo">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Carregando equipes...
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$paginaAtiva = 'inscricao';
include 'componentes/nav.php';
?>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    
    // CORREÇÃO: Transformado de "const" para "let" para permitir a reatribuição da variável depois
    let idInterclasse = urlParams.get('id'); 
    
    const generoUsuario = '<?= $genero_usuario ?>';
    const categoriaUsuario = <?= $categoria_usuario ?>;
    const idTurmaUsuario = <?= (int)($turma_usuario ?? 0) ?>;
    const modalidadesInscritas = <?= json_encode($modalidades_inscritas) ?>;
    const estaInscrito = modalidadesInscritas.length > 0;
    let modalidadesData = [];

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    // Mapa de ícones por modalidade (visual)
    function iconeModalidade(nome) {
        const n = (nome || '').toLowerCase();
        if (n.includes('futebol')) return 'bi-dribbble';
        if (n.includes('volei')) return 'bi-people-fill';
        if (n.includes('queimada')) return 'bi-bullseye';
        if (n.includes('basquet')) return 'bi-basket-fill';
        if (n.includes('handebol') || n.includes('handball')) return 'bi-person-arms-up';
        if (n.includes('corrida')) return 'bi-lightning-charge-fill';
        if (n.includes('atletis')) return 'bi-lightning-charge-fill';
        if (n.includes('xadrez')) return 'bi-puzzle-fill';
        if (n.includes('nata')) return 'bi-droplet-fill';
        if (n.includes('judo') || n.includes('judô') || n.includes('luta')) return 'bi-shield-fill';
        return 'bi-trophy-fill';
    }

    async function carregarDados() {
        try {
            if (!idInterclasse) {
                const listaInter = await (await fetch('../../../../api/interclasse.php?regulamento=true')).json();
                const ativos = (Array.isArray(listaInter) ? listaInter : []).filter(i => String(i.status_interclasse) === '1');
                if (ativos.length === 0) {
                    return;
                }
                idInterclasse = String(ativos[0].id_interclasse);
                const url = new URL(window.location);
                url.searchParams.set('id', idInterclasse);
                window.history.replaceState({}, '', url);
            }

            const resInter = await fetch('../../../../api/interclasse.php?regulamento=true');
            const listaInter = await resInter.json();
            const dadosInter = (Array.isArray(listaInter) ? listaInter : []).find(i => String(i.id_interclasse) === String(idInterclasse));
            if (dadosInter) {
                const msg = estaInscrito ? ' — Suas inscrições' : ' — Selecione até 3 modalidades';
            }

            let urlMod = `../../../../api/modalidades.php?id_interclasse=${idInterclasse}`;
            if (idTurmaUsuario > 0) urlMod += `&id_turma=${idTurmaUsuario}`;
            const res = await fetch(urlMod);
            const lista = await res.json();
            modalidadesData = Array.isArray(lista) ? lista.filter(m => String(m.status_modalidade) === '1') : [];

            if (estaInscrito) {
                renderizarInscricoes();
            } else {
                document.getElementById('inscricoesAtuais').innerHTML = '';
                const secao = document.getElementById('secaoInscricoes');
                if (secao) secao.classList.add('d-none');
            }
            renderizarSelecao();

        } catch (e) {
            console.error(e);
            document.getElementById('modalidadesGrid').innerHTML = '<div class="col-12 text-center text-danger py-5">Erro ao carregar modalidades.</div>';
        }
    }

    function atualizarContador() {
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;
        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const total = inscritos + selecionados;
        const restantes = Math.max(0, 3 - inscritos);
        document.getElementById('contador').textContent = `Você pode escolher até ${restantes} modalidade(s) (${total}/3)`;
    }

    // Atualiza o painel visual de progresso (contador grande, barra e botão)
    function atualizarProgresso() {
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;
        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const total = Math.min(3, inscritos + selecionados);

        const numEl = document.getElementById('counterNum');
        if (numEl) numEl.textContent = total;

        const badge = document.getElementById('limiteBadge');
        const statusDefault = document.getElementById('statusDefault');
        if (badge && statusDefault) {
            const atingiu = total >= 3;
            badge.classList.toggle('d-none', !atingiu);
            statusDefault.classList.toggle('d-none', atingiu);
        }

        const resumo = document.querySelector('.resumo-selecao');
        if (resumo) resumo.classList.toggle('atingiu', total >= 3);

        const countEl = document.getElementById('progressCount');
        if (countEl) countEl.textContent = `${total} de 3`;

        const segs = document.querySelectorAll('#progressTrack .progress-seg');
        segs.forEach((seg, i) => {
            if (i < total) seg.classList.add('active');
            else seg.classList.remove('active');
        });

        const btn = document.getElementById('btnSalvar');
        if (btn && btn.innerHTML.indexOf('Salvando') === -1) {
            btn.disabled = selecionados === 0;
        }
    }

    // Observa as mudanças de seleção dos cards para manter o progresso em dia
    function inicializarProgresso() {
        atualizarProgresso();
        const grid = document.getElementById('modalidadesGrid');
        if (!grid) return;
        new MutationObserver(() => atualizarProgresso())
            .observe(grid, { attributes: true, childList: true, subtree: true, attributeFilter: ['class'] });
    }

    // Define o status de vagas de uma modalidade com base na capacidade da turma do aluno.
    // Capacidade por turma = max_inscrito_modalidade x max_equipes (ilimitado se algum for ilimitado).
    function statusVagas(mod) {
        const maxInscrito = parseInt(mod.max_inscrito_modalidade) || 0;
        const maxEquipes = mod.max_equipes ? parseInt(mod.max_equipes) || 0 : 0;
        const capacidade = (maxInscrito > 0 && maxEquipes > 0) ? maxInscrito * maxEquipes : 0;
        if (capacidade <= 0) {
            return { cls: '', icon: '', label: '' };
        }
        const inscritos = parseInt(mod.qtd_inscritos_turma) || 0;
        const restantes = capacidade - inscritos;
        if (restantes <= 0) {
            return { cls: 'vagas-lotado', icon: 'bi-x-circle-fill', label: 'Lotado' };
        }
        if (restantes <= 2) {
            return { cls: 'vagas-poucas', icon: 'bi-exclamation-triangle-fill', label: 'Poucas vagas' };
        }
        return { cls: '', icon: '', label: '' };
    }

    function renderizarSelecao() {
        const grid = document.getElementById('modalidadesGrid');
        const acoes = document.getElementById('acoesInscricao');
        grid.innerHTML = '';

        const inscritosIds = new Set(modalidadesInscritas.map(m => String(m.id_modalidade)));
        const qtdInscritos = inscritosIds.size;

        if (qtdInscritos >= 3) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12 sgi-inline-b9d3ea53" ><div class="d-flex flex-column align-items-center justify-content-center text-center text-success py-5 sgi-inline-c083ecd3" ><i class="bi bi-check-circle-fill fs-1 mb-2"></i><span>Você já está inscrito em 3 modalidades. Limite atingido.</span></div></div>';
            return;
        }

        const filtradas = modalidadesData.filter(mod =>
            (mod.genero_modalidade === 'MISTO' || mod.genero_modalidade === generoUsuario) &&
            parseInt(mod.categorias_id_categoria) === categoriaUsuario
        );

        const disponiveis = filtradas.filter(mod => !inscritosIds.has(String(mod.id_modalidade)));

        atualizarContador();

        if (disponiveis.length === 0) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12 sgi-inline-b9d3ea53" ><div class="d-flex flex-column align-items-center justify-content-center text-center text-muted py-5 sgi-inline-c083ecd3" ><i class="bi bi-inbox fs-1 mb-2"></i><span>Nenhuma modalidade disponível para sua categoria no momento.</span></div></div>';
            return;
        }

        acoes.classList.remove('d-none');

        disponiveis.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            const vagas = statusVagas(mod);
            const lotado = vagas.cls === 'vagas-lotado';
            col.innerHTML = `
                <div class="modalidade-card${lotado ? ' lotado' : ''}" data-id="${mod.id_modalidade}" data-nome="${esc(mod.nome_modalidade)}" onclick="abrirEquipesModalidade(this)">
                    <span class="card-check"><i class="bi bi-check-lg"></i></span>
                    ${vagas.label ? `<span class="card-vagas ${vagas.cls}"><i class="bi ${vagas.icon}"></i>${vagas.label}</span>` : ''}
                    <div class="card-icon-wrap"><i class="bi ${iconeModalidade(mod.nome_modalidade)}"></i></div>
                    <div class="card-info">
                        <span class="card-nome">${esc(mod.nome_modalidade)}</span>
                        <span class="card-categoria">${esc(mod.nome_categoria || 'Categoria')}</span>
                        <span class="card-equipe"></span>
                    </div>
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function renderizarInscricoes() {
        const container = document.getElementById('inscricoesAtuais');
        container.innerHTML = '';

        if (modalidadesInscritas.length === 0) {
            document.getElementById('secaoInscricoes').classList.add('d-none');
            return;
        }

        document.getElementById('secaoInscricoes').classList.remove('d-none');
        const badge = document.getElementById('badgeInscricoes');
        if (badge) badge.textContent = `${modalidadesInscritas.length}/3`;

        const wrapper = document.createElement('div');
        wrapper.className = 'row row-cols-1 row-cols-md-3 g-3';

        const equipesParaCarregar = [];

        modalidadesInscritas.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="card-inscrito h-100" data-equipe="${mod.id_equipe}">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-trophy fs-4 text-success"></i>
                        <div>
                            <strong class="d-block">${esc(mod.nome_modalidade)}</strong>
                            <small class="text-muted">${esc(mod.nome_categoria)}</small>
                        </div>
                        <span class="badge bg-success-subtle text-success ms-auto rounded-pill">Inscrito</span>
                    </div>
                    <div class="membros-equipe mt-2" id="membros-${mod.id_equipe}">
                        <div class="text-center text-muted small py-2">
                            <div class="spinner-border spinner-border-sm me-1" role="status"></div>
                            Carregando equipe...
                        </div>
                    </div>
                    <div class="text-center mt-2">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-ver-detalhes"
                            data-modalidade-id="${mod.id_modalidade}"
                            data-modalidade-nome="${esc(mod.nome_modalidade)}"
                            onclick="verDetalhesModalidade(this)">
                            <i class="bi bi-calendar-event me-1"></i> Ver detalhes
                        </button>
                    </div>
                </div>
            `;
            wrapper.appendChild(col);
            equipesParaCarregar.push(mod.id_equipe);
        });

        container.appendChild(wrapper);

        equipesParaCarregar.forEach(idEquipe => carregarMembros(idEquipe));
    }

    function formatarData(dataStr) {
        if (!dataStr) return 'A definir';
        const d = new Date(dataStr + 'T00:00:00');
        if (isNaN(d.getTime())) return dataStr;
        return d.toLocaleDateString('pt-BR');
    }

    async function verDetalhesModalidade(btn) {
        const idModalidade = btn.dataset.modalidadeId;
        const nomeModalidade = btn.dataset.modalidadeNome;
        const corpo = document.getElementById('modalDetalhesCorpo');

        document.getElementById('modalDetalhesTitle').textContent = nomeModalidade || 'Detalhes';
        corpo.innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando jogos...</div>';

        const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
        modal.show();

        try {
            const res = await fetch(`../../../../api/jogos.php?id_modalidade=${idModalidade}&id_interclasse=${idInterclasse}`);
            const jogos = await res.json();
            const lista = Array.isArray(jogos) ? jogos : [];

            if (lista.length === 0) {
                corpo.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nenhum jogo agendado para esta modalidade ainda.</div>';
                return;
            }

            corpo.innerHTML = lista.map(j => {
                const status = j.status_jogo || 'Agendado';
                const ehFinalizado = String(status).toLowerCase() === 'concluido';
                const badgeCls = ehFinalizado ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark';
                const badgeTxt = ehFinalizado ? 'Finalizado' : (String(status).toLowerCase() === 'iniciado' ? 'Em andamento' : 'Agendado');

                const hora = j.inicio_jogo ? String(j.inicio_jogo).substring(0, 5) : '--:--';
                const horaFim = j.termino_jogo ? String(j.termino_jogo).substring(0, 5) : '';
                const local = j.nome_local || 'A definir';
                const confronto = j.equipes_nomes || 'A definir';

                return `
                    <div class="jogo-detalhe-item">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="detalhe-data"><i class="bi bi-calendar-event me-2 text-danger"></i>${formatarData(j.data_jogo)}</span>
                            <span class="badge rounded-pill ${badgeCls}">${badgeTxt}</span>
                        </div>
                        <div class="detalhe-meta">
                            <i class="bi bi-clock me-1"></i>${hora}${horaFim ? ' - ' + horaFim : ''}
                            <span class="mx-2">|</span>
                            <i class="bi bi-geo-alt me-1"></i>${esc(local)}
                        </div>
                        <div class="detalhe-meta mt-1">
                            <i class="bi bi-shield me-1"></i>${esc(confronto)}
                        </div>
                    </div>
                `;
            }).join('');

        } catch (e) {
            console.error('Erro ao carregar jogos:', e);
            corpo.innerHTML = '<div class="text-center text-danger py-4"><i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>Erro ao carregar os jogos. Tente novamente.</div>';
        }
    }

    async function carregarMembros(idEquipe) {
        const container = document.getElementById('membros-' + idEquipe);
        try {
            const res = await fetch(`../../../../api/equipes.php?id_equipe=${idEquipe}`);
            const data = await res.json();
            const membros = Array.isArray(data) ? data : [];

            container.innerHTML = '<div class="fw-semibold small text-muted mb-1"><i class="bi bi-people-fill me-1"></i>Sua equipe:</div>';

            if (membros.length === 0) {
                container.innerHTML += '<div class="text-muted small">Nenhum colega na equipe ainda.</div>';
                return;
            }

            const userId = <?= $id_usuario ?>;
            membros.forEach(m => {
                const ehVoce = String(m.id_usuario) === String(userId);
                const div = document.createElement('div');
                div.className = 'membro-equipe';
                const img = document.createElement('img');
                img.className = 'rounded-circle d-none object-fit-cover membro-foto';
                img.width = 26; img.height = 26;
                img.alt = '';
                img.onload = function() { img.classList.remove('d-none'); icon.classList.add('d-none'); };
                img.onerror = function() { img.classList.add('d-none'); icon.classList.remove('d-none'); };
                const icon = document.createElement('i');
                icon.className = 'bi bi-person-circle text-secondary';
                div.appendChild(img);
                div.appendChild(icon);
                const span = document.createElement('span');
                span.className = ehVoce ? 'voce' : '';
                span.textContent = esc(m.nome_usuario) + (ehVoce ? ' (Você)' : '');
                div.appendChild(span);
                container.appendChild(div);
                fetch('../../../../api/foto.php?user_id=' + m.id_usuario)
                    .then(r => r.json())
                    .then(d => { if (d.foto_usuario) img.src = '../../../../uploads/fotosUsuarios/' + d.foto_usuario; })
                    .catch(function() {});
            });
        } catch (e) {
            console.error('Erro ao carregar membros:', e);
            container.innerHTML = '<div class="text-danger small">Erro ao carregar equipe.</div>';
        }
    }

    async function abrirEquipesModalidade(card) {
        const idModalidade = card.dataset.id;
        const nomeModalidade = card.dataset.nome;

        if (card.classList.contains('lotado')) {
            document.getElementById('msgFeedback').textContent = 'Modalidade lotada. Não é possível se inscrever.';
            card.classList.add('shake');
            setTimeout(() => card.classList.remove('shake'), 500);
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
            return;
        }

        if (card.classList.contains('selected')) {
            card.classList.remove('selected');
            delete card.dataset.equipe;
            delete card.dataset.equipeNome;
            const chip = card.querySelector('.card-equipe');
            if (chip) chip.textContent = '';
            atualizarContador();
            return;
        }

        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;

        if (selecionados + inscritos >= 3) {
            document.getElementById('msgFeedback').textContent = 'Você já selecionou o número máximo de modalidades.';
            card.classList.add('shake');
            setTimeout(() => card.classList.remove('shake'), 500);
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
            return;
        }

        const chip = card.querySelector('.card-equipe');
        if (chip) chip.textContent = 'Carregando...';

        try {
            const res = await fetch(`../../../../api/equipes.php?id_modalidade=${idModalidade}&id_turma=${idTurmaUsuario}`);
            const dados = await res.json();
            const equipes = Array.isArray(dados) ? dados : [];

            if (chip) chip.textContent = '';

            if (equipes.length === 0) {
                document.getElementById('msgFeedback').textContent = 'Nenhuma equipe disponível para esta modalidade.';
                setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
                return;
            }

            document.getElementById('modalEquipesTitle').textContent = `Escolha a equipe`;
            document.getElementById('modalEquipesSubtitulo').textContent = nomeModalidade;
            
            const corpo = document.getElementById('modalEquipesCorpo');
            corpo.innerHTML = equipes.map(e => `
                <div class="equipe-pick-row" data-equipe="${e.id_equipe}" data-equipe-nome="${esc(e.nome_equipe)}" onclick="selecionarEquipe(this, '${idModalidade}')">
                    <div class="equipe-pick-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="equipe-pick-info">
                        <div class="equipe-pick-nome">${esc(e.nome_equipe)}</div>
                        <div class="equipe-pick-sub">Equipe da turma</div>
                    </div>
                    <div class="equipe-pick-check d-none"><i class="bi bi-check-lg"></i></div>
                </div>
            `).join('');
            
            const modal = new bootstrap.Modal(document.getElementById('modalEquipes'));
            modal.show();

        } catch (e) {
            console.error(e);
            if (chip) chip.textContent = 'Erro ao carregar';
        }
    }

    function selecionarEquipe(row, idModalidade) {
        const equipe = row.dataset.equipe;
        const equipeNome = row.dataset.equipeNome;

        document.querySelectorAll('.modalidade-card').forEach(card => {
            if (String(card.dataset.id) === String(idModalidade)) {
                card.classList.add('selected');
                card.dataset.equipe = equipe;
                card.dataset.equipeNome = equipeNome;
                const chip = card.querySelector('.card-equipe');
                if (chip) chip.textContent = 'Equipe: ' + equipeNome;
            }
        });

        bootstrap.Modal.getInstance(document.getElementById('modalEquipes'))?.hide();
        atualizarContador();
    }

    function removerEquipeSelecionada(idModalidade) {
        document.querySelectorAll('.modalidade-card').forEach(card => {
            if (String(card.dataset.id) === String(idModalidade)) {
                card.classList.remove('selected');
                delete card.dataset.equipe;
                delete card.dataset.equipeNome;
                const chip = card.querySelector('.card-equipe');
                if (chip) chip.textContent = '';
            }
        });

        bootstrap.Modal.getInstance(document.getElementById('modalEquipes'))?.hide();
        atualizarContador();
    }

    async function salvarEscolhas() {
        const selecionados = document.querySelectorAll('.modalidade-card.selected');
        if (selecionados.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Por favor, escolha pelo menos 1 modalidade.';
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
            return;
        }

        const lotados = Array.from(selecionados).filter(c => c.classList.contains('lotado'));
        if (lotados.length > 0) {
            document.getElementById('msgFeedback').textContent = 'Uma ou mais modalidades selecionadas ficaram lotadas. Remova-as e tente novamente.';
            lotados.forEach(c => {
                c.classList.remove('selected');
                delete c.dataset.equipe;
                delete c.dataset.equipeNome;
                const chip = c.querySelector('.card-equipe');
                if (chip) chip.textContent = '';
            });
            atualizarContador();
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 3000);
            return;
        }

        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Salvando...';

        const ids = [];
        selecionados.forEach(card => {
            const equipe = parseInt(card.dataset.equipe || 0);
            if (equipe > 0) ids.push(equipe);
        });

        if (ids.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Escolha uma equipe para cada modalidade selecionada.';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
            return;
        }

        try {
            const res = await fetch('../../../../api/inscricao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_interclasse: parseInt(idInterclasse),
                    id_equipes: ids
                })
            });
            const result = await res.json();
            document.getElementById('msgFeedback').textContent = result.message;
            if (result.success) {
                document.getElementById('msgFeedback').className = 'bottom-label text-success small';
                setTimeout(() => window.location.href = 'home.php', 1500);
            } else {
                document.getElementById('msgFeedback').className = 'bottom-label text-danger small';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
            }
        } catch (e) {
            console.error(e);
            document.getElementById('msgFeedback').textContent = 'Erro de conexão. Tente novamente.';
            document.getElementById('msgFeedback').className = 'bottom-label text-danger small';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar';
        }
    }

    document.addEventListener('DOMContentLoaded', carregarDados);
    document.addEventListener('DOMContentLoaded', inicializarProgresso);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
