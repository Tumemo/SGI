<?php
(session_status() === PHP_SESSION_NONE) && session_start();
require_once '../../../../config/db.php';

$id_usuario = (int)($_SESSION['id'] ?? 0);

$categoria_usuario = 0;
if ($id_usuario) {
    $stmtCat = $conn->prepare("SELECT u.genero_usuario, t.categorias_id_categoria FROM usuarios u LEFT JOIN turmas t ON u.turmas_id_turma = t.id_turma WHERE u.id_usuario = ?");
    $stmtCat->bind_param('i', $id_usuario);
    $stmtCat->execute();
    $resCat = $stmtCat->get_result();
    if ($rowCat = $resCat->fetch_assoc()) {
        $categoria_usuario = (int)($rowCat['categorias_id_categoria'] ?? 0);
    }
}

$tituloPagina = 'SGI - Jogos';
$titulo = 'Tabela de Jogos';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = './home.php';

include 'componentes/head.php';
?>

<style>
    :root {
        --aluno-primary: #e30613;
        --aluno-primary-dark: #c82333;
        --aluno-primary-soft: #fdeef0;
        --aluno-surface: #ffffff;
        --aluno-border: #e9ecef;
        --aluno-text: #1a1a2e;
        --aluno-text-muted: #6c757d;
        --aluno-radius: 16px;
        --aluno-shadow: 0 6px 20px rgba(30, 30, 60, 0.08);
        --aluno-shadow-hover: 0 14px 32px rgba(30, 30, 60, 0.15);
        --aluno-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    main.jogos-layout {
        width: 100%;
        padding: 2.25rem 1.5rem 3rem;
    }
    @media (max-width: 575.98px) {
        main.jogos-layout { padding: 1.5rem 1rem 2.5rem; }
    }

    /* ==================== CABEÇALHO ==================== */
    .page-header {
        display: flex;
        align-items: center;
        gap: 1.1rem;
        margin-bottom: 1.25rem;
    }
    .page-header .trophy-icon {
        flex-shrink: 0;
        width: 60px;
        height: 60px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #e30613, #ff5560);
        color: #fff;
        font-size: 1.8rem;
        box-shadow: 0 10px 24px rgba(227, 6, 19, 0.35);
        animation: floatIcon 4s ease-in-out infinite;
    }
    @keyframes floatIcon {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }
    .page-header h1 {
        font-size: clamp(1.5rem, 3vw, 2.1rem);
        font-weight: 800;
        color: var(--aluno-text);
        letter-spacing: -0.02em;
        margin: 0 0 0.25rem;
    }
    .page-header .subtitle {
        font-size: clamp(0.9rem, 1.4vw, 1.05rem);
        color: var(--aluno-text-muted);
        margin: 0;
    }

    /* ==================== BARRA DE FILTROS ==================== */
    .filtro-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.5rem;
    }
    .filtro-jogos {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        flex: 1;
        min-width: 0;
    }
    .filtro-btn {
        padding: 0.42rem 1.2rem;
        border-radius: 50px;
        border: 1.5px solid var(--aluno-border);
        background: var(--aluno-surface);
        color: var(--aluno-text);
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--aluno-transition);
        white-space: nowrap;
    }
    .filtro-btn:hover { border-color: #f5b9be; }
    .filtro-btn.active {
        background: var(--aluno-primary);
        border-color: var(--aluno-primary);
        color: #fff;
        box-shadow: 0 4px 12px rgba(227, 6, 19, 0.28);
    }

    /* Dropdown discreto de modalidade */
    .filtro-modalidade {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filtro-modalidade .mod-label {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--aluno-text-muted);
        white-space: nowrap;
    }
    .filtro-modalidade select {
        border: 1.5px solid var(--aluno-border);
        border-radius: 7px;
        padding: 0.42rem 2.1rem 0.42rem 1rem;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--aluno-text);
        background-color: var(--aluno-surface);
        cursor: pointer;
        max-width: 220px;
        transition: border-color var(--aluno-transition);
    }
    .filtro-modalidade select:focus {
        outline: none;
        border-color: var(--aluno-primary);
        box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.12);
    }

    /* ==================== CARTÕES DE JOGO ==================== */
    /* ==================== CAMPEÕES ==================== */
    .campeoes-section {
        margin-bottom: 1.75rem;
        padding: 1.4rem 1.5rem;
        border-radius: var(--aluno-radius);
        background: linear-gradient(135deg, #fff7e0, #fffdf5);
        border: 1.5px solid #f5c04a;
        box-shadow: 0 10px 30px rgba(245, 192, 74, 0.16);
    }
    .campeoes-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: #92400e;
        margin-bottom: 1.1rem;
        letter-spacing: -0.01em;
    }
    .campeoes-title i { color: #d97706; font-size: 1.3rem; }
    .campeoes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    .campeao-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 50%, #fef9c3 100%);
        border: 2px solid #f59e0b;
        border-radius: 14px;
        padding: 1.25rem 1.15rem;
        text-align: center;
        box-shadow: 0 8px 24px rgba(245, 158, 11, 0.18);
        transition: transform 0.22s ease, box-shadow 0.22s ease;
    }
    .campeao-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(245, 158, 11, 0.28);
    }
    .campeao-card__icon { font-size: 2.1rem; margin-bottom: 8px; }
    .campeao-card__label {
        font-size: 0.7rem;
        font-weight: 700;
        color: #92400e;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 6px;
    }
    .campeao-card__name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #78350f;
        line-height: 1.3;
    }
    .campeao-card__mod {
        font-size: 0.8rem;
        color: #b45309;
        margin-top: 6px;
        font-weight: 500;
    }

    .jogos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(440px, 1fr));
        gap: 1.1rem;
    }
    @media (max-width: 575.98px) {
        .jogos-grid { grid-template-columns: 1fr; }
    }
    .jogo-card {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        background: var(--aluno-surface);
        border-radius: var(--aluno-radius);
        border: 1px solid var(--aluno-border);
        padding: 1.15rem 1.25rem;
        box-shadow: var(--aluno-shadow);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }
    .jogo-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--aluno-shadow-hover);
        border-color: #f5b9be;
    }

    .jogo-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .jogo-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem 1rem;
        font-size: 0.8rem;
        color: var(--aluno-text-muted);
    }
    .jogo-meta .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
    }
    .jogo-meta .meta-item i { color: var(--aluno-primary); }

    /* ==================== BADGES DE STATUS ==================== */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.32rem 0.8rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .status-badge .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        flex-shrink: 0;
    }
    .status-andamento {
        background: #C81927;
        color: #fff;
        box-shadow: 0 4px 12px rgba(200, 25, 39, 0.35);
    }
    .status-andamento .status-dot { animation: pulseDot 1.3s ease-in-out infinite; }
    .status-aguardando { background: #E9ECEF; color: #6C757D; }
    .status-finalizado { background: #D1E7DD; color: #0F5132; }

    @keyframes pulseDot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.35; transform: scale(0.7); }
    }

    /* ==================== LINHA DA MODALIDADE ==================== */
    .modalidade-row {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }
    .modalidade-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.38rem 0.9rem;
        border-radius: 50px;
        border: none;
        background: var(--aluno-primary-soft);
        color: var(--aluno-primary);
        font-size: 0.82rem;
        font-weight: 700;
        cursor: pointer;
        transition: all var(--aluno-transition);
    }
    .modalidade-chip:hover {
        background: var(--aluno-primary);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(227, 6, 19, 0.28);
    }
    .modalidade-chip i { font-size: 0.9rem; }
    .fase-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.75rem;
        border-radius: 8px;
        background: #f2f4f8;
        color: var(--aluno-text-muted);
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* ==================== CONFRONTO ==================== */
    .confronto-area {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        padding-top: 0.15rem;
    }
    .equipe {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.45rem;
        text-align: center;
    }
    .turma-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.24rem 0.7rem;
        border-radius: 50px;
        background: var(--aluno-primary);
        color: #fff;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: 0 3px 8px rgba(227, 6, 19, 0.25);
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .equipe-nome {
        font-weight: 700;
        color: var(--aluno-text);
        font-size: 0.98rem;
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .placar-box {
        display: flex;
        align-items: baseline;
        justify-content: center;
        gap: 0.3rem;
        min-width: 88px;
        background: #f8f9fa;
        border: 1px solid var(--aluno-border);
        border-radius: 12px;
        padding: 0.55rem 0.9rem;
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--aluno-text);
    }
    .placar-box .placar-num { color: var(--aluno-primary); }
    .placar-box .vs-text {
        font-size: 0.8rem;
        color: var(--aluno-text-muted);
        font-weight: 700;
    }
    .placar-box .placar-pendente { color: #c4c9d4; }

    /* ==================== ESTADOS VAZIOS ==================== */
    .empty-state {
        grid-column: 1 / -1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        gap: 0.4rem;
        padding: 3.5rem 1rem;
        color: var(--aluno-text-muted);
    }
    .empty-state i { font-size: 2.6rem; }

    /* ==================== MODAL ==================== */
    .modalidade-modal .modal-content { border-radius: 18px; border: none; overflow: hidden; }
    .modalidade-modal .modal-header {
        background: linear-gradient(135deg, #e30613, #c81927);
        color: #fff;
        border-bottom: none;
        padding: 1rem 1.25rem;
    }
    .modalidade-modal .modal-header .btn-close { filter: invert(1); }
    .modalidade-modal .modal-title { font-weight: 800; font-size: 1.1rem; }

    .modal-section { margin-bottom: 1.25rem; }
    .modal-section-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--aluno-text-muted);
        margin-bottom: 0.75rem;
    }
    .modal-section-title i { color: var(--aluno-primary); font-size: 0.95rem; }

    /* Pódio */
    .podio-wrap { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.7rem; }
    .podio-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
        padding: 1rem 0.6rem;
        border-radius: 14px;
        border: 1px solid var(--aluno-border);
        background: #f8f9fa;
        text-align: center;
    }
    .podio-item .medalha {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: #e9ecef;
        color: #6c757d;
    }
    .podio-item .podio-turma {
        font-weight: 700;
        font-size: 0.82rem;
        color: var(--aluno-text);
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    .podio-item .podio-status {
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .podio-item.podio-1 { background: var(--aluno-primary-soft); border-color: #f5b9be; }
    .podio-item.podio-1 .medalha { background: var(--aluno-primary); color: #fff; box-shadow: 0 5px 12px rgba(227, 6, 19, 0.3); }
    .podio-item.podio-1 .podio-turma { color: var(--aluno-primary-dark); }
    .podio-item.podio-1 .podio-status { color: var(--aluno-primary); }
    .podio-item.podio-2 .medalha { background: #c0c0c0; color: #fff; }
    .podio-item.podio-3 .medalha { background: #cd7f32; color: #fff; }

    /* Sala vencedora (destaque único) */
    .podio-item.vencedor-card {
        flex-direction: row;
        justify-content: center;
        gap: 0.8rem;
        padding: 1.1rem 1rem;
        width: 100%;
    }
    .podio-item.vencedor-card .medalha { width: 48px; height: 48px; font-size: 1.5rem; }
    .podio-item.vencedor-card .podio-turma { font-size: 1rem; }
    .podio-item.vencedor-card .podio-status { font-size: 0.75rem; }

    /* Destaques */
    .destaque-card {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        border: 1px solid var(--aluno-border);
        background: #fff;
        margin-bottom: 0.5rem;
    }
    .destaque-card .destaque-icone {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--aluno-primary-soft);
        color: var(--aluno-primary);
        font-size: 1.15rem;
    }
    .destaque-card .destaque-foto {
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--aluno-primary-soft);
    }
    .destaque-card .destaque-nome { font-weight: 700; font-size: 0.9rem; color: var(--aluno-text); }
    .destaque-card .destaque-sub { font-size: 0.78rem; color: var(--aluno-text-muted); }
    .destaque-card .destaque-valor {
        margin-left: auto;
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--aluno-primary);
    }
    .destaque-card .destaque-valor small { display: block; font-size: 0.62rem; color: var(--aluno-text-muted); font-weight: 600; text-align: right; }

    /* Chaveamento / Fase */
    .fase-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.85rem;
        border-radius: 10px;
        background: #f8f9fa;
        border: 1px solid var(--aluno-border);
        margin-bottom: 0.45rem;
        font-size: 0.85rem;
    }
    .fase-item .fase-nome { font-weight: 700; color: var(--aluno-text); }
    .fase-item .fase-status { margin-left: auto; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .fase-item.fase-atual { background: var(--aluno-primary-soft); border-color: #f5b9be; }

    /* Acordeão de equipes */
    .accordion-soft { display: flex; flex-direction: column; gap: 0.5rem; }
    .accordion-soft .accordion-item {
        border: 1px solid var(--aluno-border);
        border-radius: 12px !important;
        overflow: hidden;
    }
    .accordion-soft .accordion-button {
        font-size: 0.88rem;
        font-weight: 700;
        color: var(--aluno-text);
        background: #fff;
        padding: 0.75rem 1rem;
        box-shadow: none !important;
    }
    .accordion-soft .accordion-button:not(.collapsed) {
        background: var(--aluno-primary-soft);
        color: var(--aluno-primary-dark);
    }
    .accordion-soft .accordion-button:focus { box-shadow: none !important; }
    .membro-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.4rem 0;
        font-size: 0.88rem;
        color: var(--aluno-text);
    }
    .membro-item i { color: var(--aluno-text-muted); }

    .modal-empty {
        text-align: center;
        color: var(--aluno-text-muted);
        padding: 1rem 0.5rem;
        font-size: 0.9rem;
    }
</style>

<main class="jogos-layout">

    <header class="page-header">
        <span class="trophy-icon"><i class="bi bi-calendar-event"></i></span>
        <div>
            <h1>Cronograma de Jogos</h1>
            <p class="subtitle">Acompanhe as datas, horários e resultados das partidas.</p>
        </div>
    </header>

    <!-- Filtros -->
    <div class="filtro-bar">
        <div class="filtro-jogos">
            <button class="filtro-btn active" data-filter="all">Todos</button>
            <button class="filtro-btn" data-filter="agendado">Próximos Jogos</button>
            <button class="filtro-btn" data-filter="finalizado">Resultados</button>
        </div>

        <div class="filtro-modalidade">
            <span class="mod-label">Modalidade</span>
            <select id="filtroModalidade" aria-label="Filtrar por modalidade">
                <option value="all">Todas</option>
            </select>
        </div>

        <div class="filtro-modalidade">
            <span class="mod-label">Categoria</span>
            <select id="filtroCategoria" aria-label="Filtrar por categoria">
                <option value="all">Todas</option>
            </select>
        </div>
    </div>

    <!-- Container dos Campeões -->
    <div id="campeoesSection" class="campeoes-section" hidden>
        <div class="campeoes-title"><i class="bi bi-trophy-fill"></i>Campeões</div>
        <div id="campeoesGrid" class="campeoes-grid"></div>
    </div>

    <!-- Container dos Jogos -->
    <div id="listaJogos" class="jogos-grid">
        <div class="empty-state">
            <div class="spinner-border spinner-border-sm text-danger mb-2" role="status"></div>
            Carregando tabela de jogos...
        </div>
    </div>

</main>

<!-- Modal de Detalhes da Modalidade -->
<div class="modal fade modalidade-modal" id="modalModalidade" tabindex="-1" aria-labelledby="modalModalidadeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalModalidadeTitle"><i class="bi bi-trophy-fill me-2"></i>Detalhes da Modalidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalModalidadeCorpo">
                <div class="text-center text-muted py-4">
                    <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
                    Carregando detalhes...
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$paginaAtiva = 'jogos';
include 'componentes/nav.php';
?>

<script>
    let todosOsJogos = [];
    let filtroStatus = 'all';
    let filtroModalidade = 'all';
    let filtroCategoria = '<?= $categoria_usuario > 0 ? (string) $categoria_usuario : 'all' ?>';
    let anoInterclasse = new Date().getFullYear();
    let idInterclasse = null;

    // Função de segurança para escapar HTML
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

    // Abrevia o nome da turma para uma etiqueta curta (ex: "3º Ano Médio" -> "3º MED")
    function abreviarTurma(nomeTurma, nomeFantasia) {
        const base = nomeFantasia || nomeTurma || '';
        const s = String(base).toLowerCase();
        if (s.includes('fundamental')) return String(base).trim();
        const m = String(base).match(/(\d)(º|o|°)\.?\s*(ano\s*)?(médio|medio|méd|ef|ensino\s*fundamental|ensino\s*médio)/i);
        if (m) {
            const grau = m[1];
            const tipo = /méd|medio/.test(m[4]) ? 'MED' : 'EF';
            return `${grau}º ${tipo}`;
        }
        return base;
    }

    // Formata data ISO (YYYY-MM-DD) para dd/mm/aaaa
    function formatarData(dataStr) {
        if (!dataStr) return '—';
        const partes = String(dataStr).split('-');
        if (partes.length !== 3) return dataStr;
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    // Formata horário (HH:MM:SS) para HH:MM
    function formatarHora(horaStr) {
        if (!horaStr) return '—';
        return String(horaStr).substring(0, 5);
    }

    // Função para traduzir os códigos "MM:4:0:B" / "POS:3:0:N" para nomes reais
    function traduzirNomeJogo(codigo) {
        if (!codigo || typeof codigo !== 'string') return 'Partida';

        if (codigo.startsWith('POS:')) {
            const partes = codigo.split(':');
            const posicao = partes[1] || '3';
            return `Disputa de ${posicao}º lugar`;
        }

        // Se não for um código automático do Mata-Mata, exibe como está no banco
        if (!codigo.startsWith('MM:')) return codigo;

        const partes = codigo.split(':');
        const fase = partes[1];
        const indexJogo = parseInt(partes[2] || '0') + 1;

        let nomeFase = 'Eliminatórias';

        if (fase === '16') nomeFase = 'Oitavas de Final';
        else if (fase === '8') nomeFase = 'Quartas de Final';
        else if (fase === '4') nomeFase = 'Semifinal';
        else if (fase === '2') nomeFase = 'Final';
        else if (fase === '1') nomeFase = 'Campeão';

        return `${nomeFase} (Jogo ${indexJogo})`;
    }

    // Jogo de campeão = código MM:1 (largura 1, última fase da chave)
    function eJogoCampeao(codigo) {
        return typeof codigo === 'string' && /^MM:1:/i.test(codigo.trim());
    }

    // Monta a seção de campeões (um card por modalidade/categoria)
    function renderizarCampeoes() {
        const section = document.getElementById('campeoesSection');
        const grid = document.getElementById('campeoesGrid');
        if (!section || !grid) return;

        const campeoes = [];
        const vistos = new Set();

        todosOsJogos.forEach(j => {
            if (!eJogoCampeao(j.nome_jogo_raw)) return;
            if (String(j.status_jogo).toLowerCase() !== 'concluido') return;

            const chave = `${j.id_modalidade}|${j.id_categoria}`;
            if (vistos.has(chave)) return;
            vistos.add(chave);

            const eqA = j.equipes[0];
            const eqB = j.equipes[1];

            // O jogo de campeão só tem um time (o vencedor da final), sem adversário
            let campeao;
            if (eqB) {
                campeao = (Number(eqA.placar) || 0) >= (Number(eqB.placar) || 0) ? eqA : eqB;
            } else if (eqA) {
                campeao = eqA;
            } else {
                return;
            }

            campeoes.push({
                nome: campeao.nome,
                tag: campeao.tag,
                modalidade: j.nome_modalidade,
                categoria: j.nome_categoria
            });
        });

        if (campeoes.length === 0) {
            section.hidden = true;
            return;
        }

        section.hidden = false;
        grid.innerHTML = campeoes.map(c => `
            <div class="campeao-card">
                <div class="campeao-card__icon">🏆</div>
                <div class="campeao-card__label">Campeão</div>
                <div class="campeao-card__name">${esc(c.nome)}</div>
                <div class="campeao-card__mod">${esc(c.modalidade)}${c.categoria ? ' · ' + esc(c.categoria) : ''}</div>
            </div>`).join('');
    }

    // Mapa de badge de status (EM ANDAMENTO / AGUARDANDO / FINALIZADO)
    function badgeStatus(status) {
        const s = String(status || '').toLowerCase();
        if (s === 'iniciado') {
            return {
                classe: 'status-andamento',
                texto: 'Em Andamento',
                dot: true
            };
        }
        if (s === 'concluido' || s === 'finalizado') {
            return {
                classe: 'status-finalizado',
                texto: 'Finalizado',
                dot: false
            };
        }
        return {
            classe: 'status-aguardando',
            texto: s === 'pausado' ? 'Pausado' : 'Aguardando',
            dot: false
        };
    }

    async function inicializarJogos() {
        const container = document.getElementById('listaJogos');

        try {
            // 1. Descobrir o Interclasse Ativo
            const resInter = await fetch('../../../../api/interclasse.php?regulamento=true');
            const dataInter = await resInter.json();
            const listaInter = Array.isArray(dataInter) ? dataInter : [dataInter];
            const ativo = listaInter.find(i => String(i.status_interclasse) === '1');

            if (!ativo) {
                container.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x mb-2"></i>Nenhuma competição ativa no momento.</div>';
                return;
            }

            idInterclasse = ativo.id_interclasse;
            if (ativo.ano_interclasse) {
                const anoMatch = String(ativo.ano_interclasse).match(/^\d{4}/);
                if (anoMatch) anoInterclasse = parseInt(anoMatch[0], 10);
            }

            // 2. Buscar as partidas da API (agora com data, horário, local e modalidade)
            const resJogos = await fetch(`../../../../api/partidas.php?id_interclasse=${idInterclasse}`);

            if (!resJogos.ok) throw new Error('Erro ao buscar partidas');

            const rawData = await resJogos.json();

            // 3. AGRUPAR OS DADOS: A API retorna uma linha por time, então agrupamos pelo "id_jogo"
            const jogosAgrupados = {};

            rawData.forEach(row => {
                if (!jogosAgrupados[row.id_jogo]) {
                    jogosAgrupados[row.id_jogo] = {
                        id_jogo: row.id_jogo,
                        nome_jogo: traduzirNomeJogo(row.nome_jogo),
                        nome_jogo_raw: row.nome_jogo,
                        status_jogo: row.status_jogo,
                        nome_modalidade: row.nome_modalidade,
                        id_modalidade: row.modalidades_id_modalidade,
                        id_categoria: row.categorias_id_categoria,
                        nome_categoria: row.nome_categoria,
                        data_jogo: row.data_jogo,
                        inicio_jogo: row.inicio_jogo,
                        termino_jogo: row.termino_jogo,
                        nome_local: row.nome_local,
                        equipes: []
                    };
                }
                jogosAgrupados[row.id_jogo].equipes.push({
                    nome: row.nome_fantasia_turma || row.nome_turma || 'Time Desconhecido',
                    tag: abreviarTurma(row.nome_turma, row.nome_fantasia_turma),
                    placar: row.resultado_partida
                });
            });

            todosOsJogos = Object.values(jogosAgrupados);

            if (todosOsJogos.length === 0) {
                container.innerHTML = '<div class="empty-state"><i class="bi bi-inbox mb-2"></i>Nenhum jogo agendado ainda.</div>';
                return;
            }

            // Preencher os dropdowns de modalidade e categoria
            preencherFiltroModalidades();
            preencherFiltroCategorias();

            renderizarCampeoes();
            renderizarJogos();

        } catch (error) {
            console.error("Erro ao carregar jogos:", error);
            container.innerHTML = `
                <div class="empty-state text-danger">
                    <i class="bi bi-exclamation-triangle mb-2"></i>
                    Erro ao carregar a tabela de jogos. Tente novamente mais tarde.
                </div>`;
        }
    }

    function preencherFiltroModalidades() {
        const select = document.getElementById('filtroModalidade');
        const modalidades = [];
        const vistos = new Set();

        todosOsJogos.forEach(j => {
            const chave = String(j.id_modalidade);
            if (!vistos.has(chave)) {
                vistos.add(chave);
                modalidades.push({ id: j.id_modalidade, nome: j.nome_modalidade });
            }
        });

        select.innerHTML = '<option value="all">Todas</option>' +
            modalidades.map(m => `<option value="${esc(m.id)}">${esc(m.nome)}</option>`).join('');
    }

    function preencherFiltroCategorias() {
        const select = document.getElementById('filtroCategoria');
        const categorias = [];
        const vistos = new Set();

        todosOsJogos.forEach(j => {
            const chave = String(j.id_categoria);
            if (!vistos.has(chave)) {
                vistos.add(chave);
                categorias.push({ id: j.id_categoria, nome: j.nome_categoria });
            }
        });

        select.innerHTML = '<option value="all">Todas</option>' +
            categorias.map(c => `<option value="${esc(c.id)}">${esc(c.nome)}</option>`).join('');

        if (filtroCategoria !== 'all' && vistos.has(String(filtroCategoria))) {
            select.value = String(filtroCategoria);
        } else {
            filtroCategoria = 'all';
            select.value = 'all';
        }
    }

    function renderizarJogos() {
        const container = document.getElementById('listaJogos');

        // Jogos de campeão (MM:1) não entram na lista — eles aparecem na seção "Campeões"
        let jogosFiltrados = todosOsJogos.filter(j => !eJogoCampeao(j.nome_jogo_raw));

        if (filtroStatus === 'agendado') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.status_jogo).toLowerCase() !== 'concluido');
        } else if (filtroStatus === 'finalizado') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.status_jogo).toLowerCase() === 'concluido');
        }

        if (filtroModalidade !== 'all') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.id_modalidade) === String(filtroModalidade));
        }

        if (filtroCategoria !== 'all') {
            jogosFiltrados = jogosFiltrados.filter(j => String(j.id_categoria) === String(filtroCategoria));
        }

        if (jogosFiltrados.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="bi bi-search mb-2"></i>Nenhum jogo encontrado para este filtro.</div>';
            return;
        }

        container.innerHTML = jogosFiltrados.map(jogo => {
            const status = badgeStatus(jogo.status_jogo);
            const isFinalizado = String(jogo.status_jogo).toLowerCase() === 'concluido';

            const eqA = jogo.equipes[0] || { nome: 'A Definir', tag: '??', placar: '-' };
            const eqB = jogo.equipes[1] || { nome: 'A Definir', tag: '??', placar: '-' };

            const placarA = isFinalizado ? (eqA.placar ?? '0') : '-';
            const placarB = isFinalizado ? (eqB.placar ?? '0') : '-';

            const metaInfo = `
                <span class="meta-item"><i class="bi bi-calendar3"></i>${formatarData(jogo.data_jogo)}</span>
                <span class="meta-item"><i class="bi bi-clock"></i>${formatarHora(jogo.inicio_jogo)}${jogo.termino_jogo ? '–' + formatarHora(jogo.termino_jogo) : ''}</span>
                <span class="meta-item"><i class="bi bi-geo-alt"></i>${esc(jogo.nome_local || 'Quadra')}</span>
            `;

            const dot = status.dot ? '<span class="status-dot"></span>' : '';

            return `
                <div class="jogo-card">
                    <div class="jogo-top">
                        <div class="jogo-meta">${metaInfo}</div>
                        <span class="status-badge ${status.classe}">${dot}${esc(status.texto)}</span>
                    </div>

                    <div class="modalidade-row">
                        <button type="button" class="modalidade-chip"
                            data-modalidade-id="${esc(jogo.id_modalidade)}"
                            data-modalidade-nome="${esc(jogo.nome_modalidade)}"
                            onclick="abrirDetalhesModalidade(this)">
                            <i class="bi ${iconeModalidade(jogo.nome_modalidade)}"></i>${esc(jogo.nome_modalidade)}
                        </button>
                        <span class="fase-tag"><i class="bi bi-diagram-3 me-1"></i>${esc(jogo.nome_jogo)}</span>
                    </div>

                    <div class="confronto-area">
                        <div class="equipe">
                            <span class="turma-tag">${esc(eqA.tag)}</span>
                            <span class="equipe-nome">${esc(eqA.nome)}</span>
                        </div>

                        <div class="placar-box">
                            <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarA)}</span>
                            <span class="vs-text mx-1">x</span>
                            <span class="${isFinalizado ? 'placar-num' : 'placar-pendente'}">${esc(placarB)}</span>
                        </div>

                        <div class="equipe">
                            <span class="turma-tag">${esc(eqB.tag)}</span>
                            <span class="equipe-nome">${esc(eqB.nome)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ============================ MODAL DE DETALHES ============================

    async function abrirDetalhesModalidade(btn) {
        const idModalidade = btn.dataset.modalidadeId;
        const nomeModalidade = btn.dataset.modalidadeNome;
        const corpo = document.getElementById('modalModalidadeCorpo');

        document.getElementById('modalModalidadeTitle').innerHTML =
            `<i class="bi bi-trophy-fill me-2"></i>${esc(nomeModalidade)}`;

        corpo.innerHTML = `
            <div class="text-center text-muted py-4">
                <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
                Carregando detalhes...
            </div>`;

        const modal = new bootstrap.Modal(document.getElementById('modalModalidade'));
        modal.show();

        try {
            const [resPodio, resDestaques, resFases, resEquipes] = await Promise.all([
                fetch(`../../../../api/classificacao.php?id_modalidade=${idModalidade}`),
                fetch(`../../../../api/artilheiro.php?id_modalidade=${idModalidade}&ano=${anoInterclasse}`),
                fetch(`../../../../api/chaveamento.php?id_modalidade=${idModalidade}&acao=arvore`),
                fetch(`../../../../api/equipes.php?id_modalidade=${idModalidade}`)
            ]);

            const dadosPodio = await resPodio.json();
            const destaques = await resDestaques.json();
            const dadosFases = await resFases.json();
            const equipes = await resEquipes.json();

            corpo.innerHTML = montarHTMLModal(dadosPodio, destaques, dadosFases, equipes);
            inicializarAcordeoes();
        } catch (e) {
            console.error('Erro ao carregar detalhes da modalidade:', e);
            corpo.innerHTML = `
                <div class="modal-empty">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2 text-danger"></i>
                    Erro ao carregar os detalhes. Tente novamente.
                </div>`;
        }
    }

    function montarHTMLModal(dadosPodio, destaques, dadosFases, equipes) {
        let html = '';

        // ===== SALA VENCEDORA =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-trophy-fill"></i>Sala Vencedora</div>';

        const podio = (dadosPodio && dadosPodio.success && Array.isArray(dadosPodio.podio)) ? dadosPodio.podio : [];
        const campeao = podio.find(p => parseInt(p.posicao) === 1) || null;

        if (campeao) {
            html += `
                <div class="podio-item podio-1 vencedor-card">
                    <span class="medalha"><i class="bi bi-trophy-fill"></i></span>
                    <span class="podio-turma">${esc(campeao.fantasia || campeao.equipe)}</span>
                    <span class="podio-status">${esc(campeao.status || 'Campeão')}</span>
                </div>`;
        } else {
            html += '<div class="modal-empty"><i class="bi bi-hourglass-split d-block mb-1"></i>A sala vencedora será definida após a final.</div>';
        }
        html += '</div>';

        // ===== DESTAQUE / ARTILHEIRO =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-lightning-charge-fill"></i>Destaque & Artilharia</div>';

        const artilheiros = Array.isArray(destaques) ? destaques : [];
        const artilheiroTop = artilheiros[0];
        if (artilheiroTop) {
            const foto = artilheiroTop.foto_usuario ? `../../../../uploads/fotosUsuarios/${encodeURIComponent(artilheiroTop.foto_usuario)}` : '';
            html += `
                <div class="destaque-card">
                    ${foto
                        ? `<img class="destaque-foto" src="${foto}" alt="${esc(artilheiroTop.nome_usuario)}">`
                        : `<span class="destaque-icone"><i class="bi bi-award-fill"></i></span>`}
                    <div>
                        <div class="destaque-nome">${esc(artilheiroTop.nome_usuario)} <i class="bi bi-star-fill text-warning"></i></div>
                        <div class="destaque-sub">${esc(artilheiroTop.nome_fantasia_turma || artilheiroTop.nome_turma || '')}</div>
                    </div>
                    <div class="destaque-valor">${esc(artilheiroTop.total_gols)}<small>gols</small></div>
                </div>`;
        } else {
            html += '<div class="modal-empty"><i class="bi bi-person-dash d-block mb-1"></i>Ainda não há artilharia registrada para esta modalidade.</div>';
        }
        html += '</div>';

        // ===== CHAVEAMENTO / FASE =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-diagram-3-fill"></i>Chaveamento & Fases</div>';

        const jogosFases = (dadosFases && dadosFases.success && Array.isArray(dadosFases.jogos)) ? dadosFases.jogos : [];
        const fases = agruparFases(jogosFases);

        if (fases.length > 0) {
            const indiceAtual = fases.findIndex(f => f.pendentes > 0);
            const faseAtual = indiceAtual >= 0 ? indiceAtual : fases.length - 1;

            html += fases.map((f, i) => {
                const atual = i === faseAtual && indiceAtual >= 0;
                const concluida = f.pendentes === 0;
                return `
                    <div class="fase-item ${atual ? 'fase-atual' : ''}">
                        <span class="fase-nome">${esc(f.nome)}</span>
                        <span class="text-muted small">${f.concluidos}/${f.total} jogos</span>
                        <span class="fase-status ${concluida ? 'text-success' : (atual ? 'text-danger' : 'text-muted')}">
                            ${concluida ? 'Concluída' : (atual ? 'Em andamento' : 'Aguardando')}
                        </span>
                    </div>`;
            }).join('');
        } else {
            html += '<div class="modal-empty"><i class="bi bi-diagram-3 d-block mb-1"></i>Chaveamento ainda não gerado para esta modalidade.</div>';
        }
        html += '</div>';

        // ===== EQUIPES (acordeão) =====
        html += '<div class="modal-section">';
        html += '<div class="modal-section-title"><i class="bi bi-people-fill"></i>Equipes Participantes</div>';

        const equipesLista = Array.isArray(equipes) ? equipes : [];
        if (equipesLista.length > 0) {
            html += '<div class="accordion accordion-soft" id="accordionEquipes">';
            html += equipesLista.map((e, i) => `
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#equipe-${e.id_equipe}"
                            aria-expanded="false"
                            aria-controls="equipe-${e.id_equipe}">
                            <i class="bi bi-shield-fill me-2 text-danger"></i>
                            ${esc(e.nome_equipe || e.nome_turma || `Equipe ${i + 1}`)}
                        </button>
                    </h2>
                    <div id="equipe-${e.id_equipe}" class="accordion-collapse collapse"
                        data-bs-parent="#accordionEquipes">
                        <div class="accordion-body">
                            <div class="membros-carregando text-muted small py-2">
                                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                Carregando integrantes...
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            html += '</div>';
        } else {
            html += '<div class="modal-empty"><i class="bi bi-people d-block mb-1"></i>Nenhuma equipe vinculada ainda.</div>';
        }
        html += '</div>';

        return html;
    }

    // Agrupa jogos do chaveamento por fase e calcula progresso
    function agruparFases(jogos) {
        const mapa = {};
        jogos.forEach(j => {
            const nome = j.nome_fase || 'Fase';
            if (!mapa[nome]) {
                mapa[nome] = { nome: nome, total: 0, concluidos: 0 };
            }
            mapa[nome].total++;
            const s = String(j.status_jogo || '').toLowerCase();
            if (s === 'concluido' || s === 'finalizado') mapa[nome].concluidos++;
        });

        const fases = Object.values(mapa);
        fases.sort((a, b) => (b.total - a.total));

        fases.forEach(f => { f.pendentes = f.total - f.concluidos; });
        return fases;
    }

    // Carrega os membros quando um acordeão é aberto
    function inicializarAcordeoes() {
        document.querySelectorAll('#accordionEquipes .accordion-item').forEach(item => {
            item.querySelector('.accordion-collapse').addEventListener('show.bs.collapse', () => {
                const corpo = item.querySelector('.accordion-body');
                if (!corpo.dataset.carregado) {
                    carregarMembrosEquipe(item, corpo);
                }
            });
        });
    }

    async function carregarMembrosEquipe(item, corpo) {
        if (corpo.dataset.carregado) return;
        corpo.dataset.carregado = '1';

        const target = item.querySelector('.accordion-button').getAttribute('data-bs-target');
        const idEquipe = target.replace('#equipe-', '');

        try {
            const res = await fetch(`../../../../api/equipes.php?id_equipe=${idEquipe}`);
            const membros = await res.json();
            const lista = Array.isArray(membros) ? membros : [];

            if (lista.length === 0) {
                corpo.innerHTML = '<div class="modal-empty text-start p-0 py-1">Nenhum integrante vinculado a esta equipe.</div>';
                return;
            }

            corpo.innerHTML = lista.map(m => `
                <div class="membro-item">
                    <i class="bi bi-person-circle fs-5"></i>
                    <span>${esc(m.nome_usuario)}</span>
                </div>
            `).join('');
        } catch (e) {
            console.error('Erro ao carregar integrantes:', e);
            corpo.innerHTML = '<div class="text-danger small">Erro ao carregar integrantes.</div>';
        }
    }

    // Filtro de status
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            filtroStatus = e.target.dataset.filter;
            renderizarJogos();
        });
    });

    // Filtro de modalidade
    document.getElementById('filtroModalidade').addEventListener('change', (e) => {
        filtroModalidade = e.target.value;
        renderizarJogos();
    });

    // Filtro de categoria
    document.getElementById('filtroCategoria').addEventListener('change', (e) => {
        filtroCategoria = e.target.value;
        renderizarJogos();
    });

    document.addEventListener('DOMContentLoaded', inicializarJogos);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
