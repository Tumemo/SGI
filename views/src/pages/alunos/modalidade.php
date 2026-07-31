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
    $stmt = $conn->prepare("SELECT u.genero_usuario, t.categorias_id_categoria FROM usuarios u LEFT JOIN turmas t ON u.turmas_id_turma = t.id_turma WHERE u.id_usuario = ?");
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $genero_usuario = $row['genero_usuario'];
        $categoria_usuario = (int)($row['categorias_id_categoria'] ?? 0);
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

<style>
    .modalidade-card {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        min-height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        color: #000;
        position: relative;
        user-select: none;
        transition: transform 0.15s, background-color 0.2s, color 0.2s;
        padding: 1rem 0.75rem;
    }
    .modalidade-card:hover {
        transform: translateY(-2px);
    }
    .modalidade-card.selected {
        background-color: #5cb85c;
        color: #fff;
    }
    .modalidade-card.selected i {
        color: #fff;
    }
    .modalidade-card.selected::after {
        content: "✓";
        position: absolute;
        top: -6px;
        right: -6px;
        width: 22px;
        height: 22px;
        background-color: #5cb85c;
        border: 2px solid #fff;
        color: white;
        border-radius: 50%;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .btn-save {
        background: linear-gradient(135deg, #e60012, #ff3344);
        color: white;
        border: none;
        padding: 14px 28px;
        min-width: 220px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 10px 25px rgba(230,0,18,.25);
        transition: all .25s ease;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 30px rgba(230,0,18,.35);
        color: white;
    }
    .btn-save:active {
        transform: scale(.98);
    }
    .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .card-inscrito {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.25rem;
        border-left: 4px solid #28a745;
        transition: transform 0.15s;
    }
    .card-inscrito:hover {
        transform: translateY(-2px);
    }
    .membro-equipe {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }
    .membro-equipe .voce {
        font-weight: 600;
        color: #28a745;
    }
    .btn-ver-equipe {
        font-size: 0.85rem;
        padding: 4px 14px;
        border-radius: 20px;
    }
    .membro-foto {
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.12);
    }

    .btn-ver-detalhes {
        border-radius: 20px;
        font-size: 0.8rem;
        padding: 4px 16px;
    }

    .jogo-detalhe-item {
        padding: 0.85rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    .jogo-detalhe-item:last-child {
        border-bottom: none;
    }
    .jogo-detalhe-item .detalhe-data {
        font-weight: 600;
        color: #1a1a2e;
    }
    .jogo-detalhe-item .detalhe-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>

<main class="container py-4">

    <div id="inscricoesAtuais" class="mb-4"></div>

    <div class="row row-cols-1 row-cols-md-3 g-3" id="modalidadesGrid">
        <div class="col text-center py-5">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
            Carregando modalidades...
        </div>
    </div>

    <div id="acoesInscricao" class="d-none d-flex flex-column align-items-center gap-3 mt-4 pt-3">
        <p class="counter-text text-muted small mb-0" id="contador">Você pode escolher até 3 modalidades</p>
        <button type="button" class="btn-save" id="btnSalvar" onclick="salvarEscolhas()">
            <i class="bi bi-check-lg"></i> Salvar
        </button>
        <p class="bottom-label text-muted small" id="msgFeedback"></p>
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
    const modalidadesInscritas = <?= json_encode($modalidades_inscritas) ?>;
    const estaInscrito = modalidadesInscritas.length > 0;
    let modalidadesData = [];

    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

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

            const res = await fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const lista = await res.json();
            modalidadesData = Array.isArray(lista) ? lista.filter(m => String(m.status_modalidade) === '1') : [];

            if (estaInscrito) {
                renderizarInscricoes();
            } else {
                document.getElementById('inscricoesAtuais').innerHTML = '';
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

    function renderizarSelecao() {
        const grid = document.getElementById('modalidadesGrid');
        const acoes = document.getElementById('acoesInscricao');
        grid.innerHTML = '';

        const inscritosIds = new Set(modalidadesInscritas.map(m => String(m.id_modalidade)));
        const qtdInscritos = inscritosIds.size;

        if (qtdInscritos >= 3) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12 text-center text-success py-4"><i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>Você já está inscrito em 3 modalidades. Limite atingido.</div>';
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
            grid.innerHTML = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhuma modalidade disponível para sua categoria no momento.</div>';
            return;
        }

        acoes.classList.remove('d-none');

        disponiveis.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
                <div class="modalidade-card" data-id="${mod.id_modalidade}" onclick="toggleModalidade(this)">
                    <i class="bi bi-trophy"></i>
                    ${esc(mod.nome_modalidade)}
                </div>
            `;
            grid.appendChild(col);
        });
    }

    function renderizarInscricoes() {
        const container = document.getElementById('inscricoesAtuais');
        container.innerHTML = '';

        if (modalidadesInscritas.length === 0) {
            return;
        }

        container.innerHTML = `<h5 class="fs-6 fw-bold text-dark mb-3"><i class="bi bi-person-check text-success me-2"></i>Suas inscrições (${modalidadesInscritas.length}/3)</h5>`;

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

    function toggleModalidade(card) {
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;
        const selecionados = document.querySelectorAll('.modalidade-card.selected');

        if (card.classList.contains('selected')) {
            card.classList.remove('selected');
        } else {
            if (selecionados.length + inscritos >= 3) {
                document.getElementById('msgFeedback').textContent = 'Você só pode escolher até 3 modalidades!';
                setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
                return;
            }
            card.classList.add('selected');
        }
        atualizarContador();
    }

    async function salvarEscolhas() {
        const selecionados = document.querySelectorAll('.modalidade-card.selected');
        if (selecionados.length === 0) {
            document.getElementById('msgFeedback').textContent = 'Por favor, escolha pelo menos 1 modalidade.';
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2000);
            return;
        }
        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Salvando...';

        const ids = [];
        selecionados.forEach(card => ids.push(parseInt(card.dataset.id)));

        try {
            const res = await fetch('../../../../api/inscricao.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_interclasse: parseInt(idInterclasse),
                    id_modalidades: ids
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>