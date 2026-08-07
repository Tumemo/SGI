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

<style>
    :root {
        --md-primary: #e30613;
        --md-primary-dark: #c82333;
        --md-primary-light: #fce4e6;
        --md-primary-subtle: #fff5f5;
        --md-success: #198754;
        --md-surface: #ffffff;
        --md-border: #e9ecef;
        --md-text: #1a1a2e;
        --md-text-secondary: #6c757d;
        --md-shadow: 0 6px 20px rgba(30, 30, 60, 0.08);
        --md-shadow-hover: 0 14px 32px rgba(30, 30, 60, 0.15);
    }

    /* ==================== LAYOUT GERAL ==================== */
    main.modalidade-layout {
        max-width: 1500px;
        width: calc(100% - 2.5rem);
        margin: 0 auto;
        padding: 2rem 0 3rem;
    }
    @media (min-width: 1400px) {
        main.modalidade-layout {
            padding: 2.5rem 0 3.5rem;
        }
    }
    @media (max-width: 575.98px) {
        main.modalidade-layout {
            width: calc(100% - 2rem);
        }
    }

    /* ==================== CABEÇALHO ==================== */
    .page-header {
        margin-bottom: 1.5rem;
    }
    .page-header-inner {
        display: flex;
        align-items: center;
        gap: 1.1rem;
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
        color: var(--md-text);
        letter-spacing: -0.02em;
        margin: 0 0 0.25rem;
    }
    .page-header .subtitle {
        font-size: clamp(0.95rem, 1.4vw, 1.05rem);
        color: var(--md-text-secondary);
        margin: 0;
    }

    /* ==================== CAIXA DE INFORMAÇÕES ==================== */
    .info-box {
        width: 100%;
        margin-bottom: 1.75rem;
        background: var(--md-primary-subtle);
        border: 1px solid #ffd9dc;
        border-left: 5px solid var(--md-primary);
        border-radius: 14px;
        padding: 0.9rem 1.15rem;
        display: flex;
        gap: 0.8rem;
        align-items: flex-start;
    }
    .info-box .info-icon { font-size: 1.25rem; line-height: 1.3; }
    .info-box .info-content { flex: 1; }
    .info-box .info-title {
        font-weight: 700;
        font-size: 0.85rem;
        color: var(--md-primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.3rem;
    }
    .info-box ul {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1.75rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .info-box li {
        font-size: 0.85rem;
        color: #6d2a2f;
        position: relative;
        padding-left: 1.1rem;
    }
    .info-box li::before {
        content: '•';
        position: absolute;
        left: 0;
        color: var(--md-primary);
        font-weight: 700;
    }

    /* ==================== TÍTULOS DE SEÇÃO ==================== */
    .secao {
        margin-bottom: 1.75rem;
    }
    .secao-titulo {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        margin-bottom: 1rem;
    }
    .secao-titulo-icone {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: var(--md-primary-light);
        color: var(--md-primary);
    }
    .secao-titulo-texto { flex: 1; min-width: 0; }
    .secao-titulo-texto h2 {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--md-text);
        margin: 0;
        letter-spacing: -0.01em;
    }
    .secao-titulo-texto p {
        font-size: 0.85rem;
        color: var(--md-text-secondary);
        margin: 0;
    }
    .secao-badge {
        flex-shrink: 0;
        font-size: 0.85rem;
        font-weight: 800;
        color: var(--md-primary);
        background: var(--md-primary-light);
        padding: 0.35rem 0.9rem;
        border-radius: 50px;
        white-space: nowrap;
    }

    /* ==================== STATUS DE VAGAS ==================== */
    .card-vagas {
        position: absolute;
        top: 10px;
        left: 10px;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        padding: 0.3rem 0.7rem;
        border-radius: 50px;
        text-transform: uppercase;
    }
    .card-vagas.vagas-livre {
        background: #d1e7dd;
        color: #0a5a33;
    }
    .card-vagas.vagas-poucas {
        background: #fff3cd;
        color: #8a6d1a;
    }
    .card-vagas.vagas-lotado {
        background: #f8d7da;
        color: #842029;
    }
    .modalidade-card.selected .card-vagas {
        background: #fff;
    }

    /* ==================== GRID DE MODALIDADES ==================== */
    .modalidades-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 1.1rem;
    }
    .modalidades-grid > .col {
        width: auto;
        flex: none;
        padding: 0;
        display: flex;
    }
    .modalidades-grid > .col-12 {
        grid-column: 1 / -1;
        width: 100%;
        padding: 0;
    }
    @media (min-width: 576px) and (max-width: 991.98px) {
        .modalidades-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 992px) and (max-width: 1399.98px) {
        .modalidades-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .modalidade-card {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        width: 100%;
        min-height: 150px;
        background: var(--md-surface);
        border: 2px solid var(--md-border);
        border-radius: 16px;
        padding: 1.35rem 1rem;
        cursor: pointer;
        user-select: none;
        text-align: center;
        box-shadow: var(--md-shadow);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease, background-color 0.22s ease;
    }
    .modalidade-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--md-shadow-hover);
        border-color: #f5b9be;
        background-color: #fffdfd;
    }
    .modalidade-card .card-icon-wrap {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        background: var(--md-primary-subtle);
        color: var(--md-primary);
        transition: transform 0.22s ease, background-color 0.22s ease, color 0.22s ease;
    }
    .modalidade-card:hover .card-icon-wrap { transform: scale(1.08); }
    .modalidade-card .card-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.3rem;
    }
    .modalidade-card .card-nome {
        font-weight: 600;
        font-size: 1.05rem;
        color: var(--md-text);
        line-height: 1.2;
    }
    .modalidade-card .card-categoria {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        color: var(--md-text-secondary);
        background: #f2f4f8;
        padding: 0.22rem 0.65rem;
        border-radius: 50px;
        text-transform: uppercase;
    }
    .modalidade-card .card-equipe {
        display: none;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--md-primary-dark);
        background: var(--md-primary-light);
        border: 1px dashed var(--md-primary);
        padding: 0.22rem 0.7rem;
        border-radius: 50px;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .modalidade-card.selected .card-equipe { display: inline-block; }

    /* Estado selecionado */
    .modalidade-card.selected {
        border-color: var(--md-primary);
        background: var(--md-primary-subtle);
        box-shadow: 0 0 0 4px rgba(227, 6, 19, 0.12), 0 10px 24px rgba(227, 6, 19, 0.15);
        animation: popIn 0.25s ease;
    }
    @keyframes popIn {
        0% { transform: scale(0.97); }
        60% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    .modalidade-card.selected .card-icon-wrap {
        background: var(--md-primary);
        color: #fff;
    }
    .modalidade-card.selected .card-nome { color: var(--md-primary-dark); }
    .modalidade-card.selected .card-categoria {
        background: #fff;
        color: var(--md-primary);
    }

    /* Check animado no canto */
    .card-check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--md-primary);
        color: #fff;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.4) rotate(-90deg);
        transition: opacity 0.2s ease, transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 4px 10px rgba(227, 6, 19, 0.35);
    }
    .modalidade-card.selected .card-check {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }

    /* Feedback de limite atingido */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-6px); }
        40% { transform: translateX(6px); }
        60% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
    }
    .modalidade-card.shake {
        animation: shake 0.45s ease;
        border-color: #ffb3ba;
    }

    /* ==================== MODAL DE ESCOLHA DE EQUIPE ==================== */
    .equipe-pick-row {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1rem;
        border: 1.5px solid var(--md-border);
        border-radius: 14px;
        background: var(--md-surface);
        cursor: pointer;
        margin-bottom: 0.6rem;
        transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }
    .equipe-pick-row:hover {
        transform: translateY(-2px);
        border-color: #f5b9be;
        box-shadow: var(--md-shadow);
    }
    .equipe-pick-row.selected {
        border-color: var(--md-primary);
        background: var(--md-primary-subtle);
        box-shadow: 0 0 0 3px rgba(227, 6, 19, 0.12);
    }
    .equipe-pick-icon {
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: var(--md-primary-light);
        color: var(--md-primary);
    }
    .equipe-pick-info { flex: 1; min-width: 0; }
    .equipe-pick-nome { font-weight: 700; font-size: 0.95rem; color: var(--md-text); line-height: 1.2; }
    .equipe-pick-sub { font-size: 0.78rem; color: var(--md-text-secondary); }
    .equipe-pick-check {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--md-primary);
        color: #fff;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(227, 6, 19, 0.3);
    }

    /* ==================== ÁREA INFERIOR / RESUMO ==================== */
    #acoesInscricao {
        display: grid;
        gap: 0.75rem;
        margin-top: 2rem;
        position: sticky;
        bottom: 0;
        z-index: 1015;
        background: rgba(248, 249, 250, 0.92);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 1rem 0 0.25rem;
        border-top: 1px solid rgba(233, 236, 239, 0.9);
    }
    @media (max-width: 767.98px) {
        #acoesInscricao { bottom: 68px; }
    }
    .resumo-selecao {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 1.5rem 2rem;
        padding: 1.25rem 1.5rem;
        background: var(--md-surface);
        border: 1px solid var(--md-border);
        border-radius: 16px;
        box-shadow: var(--md-shadow);
        transition: border-color 0.3s ease;
    }
    .resumo-selecao.atingiu { border-color: #b7e0c8; }

    .counter-box {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.1rem;
        white-space: nowrap;
    }
    .counter-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--md-text-secondary);
    }
    .counter-value {
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--md-primary);
        line-height: 1.1;
        transition: color 0.3s ease;
    }
    .counter-value .counter-total {
        color: #c4c9d4;
        font-weight: 600;
        font-size: 1.3rem;
    }
    .resumo-selecao.atingiu .counter-value { color: var(--md-success); }
    .limite-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: #d1e7dd;
        color: #0a5a33;
        font-size: 0.78rem;
        font-weight: 700;
        padding: 0.3rem 0.85rem;
        border-radius: 50px;
        animation: popIn 0.3s ease;
        white-space: nowrap;
    }
    .status-default {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--md-text-secondary);
    }

    .resumo-progress {
        width: 100%;
        max-width: 640px;
        justify-self: center;
    }
    .resumo-progress .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.82rem;
        color: var(--md-text-secondary);
        font-weight: 500;
        margin-bottom: 0.45rem;
    }
    .resumo-progress .progress-header .progress-count {
        font-weight: 700;
        color: var(--md-primary);
    }
    .progress-track {
        display: flex;
        gap: 0.5rem;
        background: #eef0f5;
        border-radius: 12px;
        padding: 0.4rem;
    }
    .progress-seg {
        flex: 1;
        height: 10px;
        border-radius: 8px;
        background: #dde1ea;
        transition: background-color 0.3s ease, transform 0.25s ease;
    }
    .progress-seg.active {
        background: var(--md-primary);
        transform: scaleY(1.2);
    }

    .resumo-actions {
        display: flex;
        justify-content: flex-end;
    }
    .btn-save {
        background: linear-gradient(135deg, #e30613, #ff3344);
        color: #fff;
        border: none;
        padding: 15px 40px;
        min-width: 250px;
        border-radius: 14px;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(227, 6, 19, 0.32);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-save::after {
        content: '';
        position: absolute;
        top: 0;
        left: -120%;
        width: 60%;
        height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.35), transparent);
        transform: skewX(-20deg);
        transition: left 0.5s ease;
    }
    .btn-save:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 36px rgba(227, 6, 19, 0.42);
        color: #fff;
    }
    .btn-save:hover::after { left: 130%; }
    .btn-save:active { transform: scale(0.96); }
    .btn-save:disabled {
        background: linear-gradient(135deg, #f2b3b8, #f6c6c9);
        color: #fff;
        box-shadow: none;
        cursor: not-allowed;
        transform: none;
        opacity: 0.55;
    }
    .btn-save:disabled::after { display: none; }

    .bottom-label {
        min-height: 1.2rem;
        margin: 0;
        font-size: 0.88rem;
        font-weight: 600;
        justify-self: center;
    }

    /* ==================== INSCRIÇÕES ATUAIS ==================== */
    .card-inscrito {
        background-color: #ffffff;
        border-radius: 16px;
        box-shadow: var(--md-shadow);
        padding: 1.25rem;
        border-left: 4px solid var(--md-success);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        height: 100%;
    }
    .card-inscrito:hover {
        transform: translateY(-3px);
        box-shadow: var(--md-shadow-hover);
    }
    .membro-equipe {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0;
    }
    .membro-equipe .voce {
        font-weight: 600;
        color: var(--md-success);
    }
    .btn-ver-equipe {
        font-size: 0.85rem;
        padding: 4px 14px;
        border-radius: 20px;
    }
    .membro-foto {
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.12);
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

            const res = await fetch(`../../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
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

    // Define o status de vagas de uma modalidade com base nas equipes inscritas
    function statusVagas(mod) {
        const maxTurmas = parseInt(mod.max_turmas) || 0;
        const ocupadas = parseInt(mod.qtd_equipes) || 0;
        const restantes = maxTurmas - ocupadas;
        if (maxTurmas <= 0 || restantes > 2) {
            return { cls: 'vagas-livre', icon: 'bi-check-circle-fill', label: 'Vagas disponíveis' };
        }
        if (restantes <= 0) {
            return { cls: 'vagas-lotado', icon: 'bi-x-circle-fill', label: 'Lotado' };
        }
        return { cls: 'vagas-poucas', icon: 'bi-exclamation-triangle-fill', label: 'Poucas vagas' };
    }

    function renderizarSelecao() {
        const grid = document.getElementById('modalidadesGrid');
        const acoes = document.getElementById('acoesInscricao');
        grid.innerHTML = '';

        const inscritosIds = new Set(modalidadesInscritas.map(m => String(m.id_modalidade)));
        const qtdInscritos = inscritosIds.size;

        if (qtdInscritos >= 3) {
            acoes.classList.add('d-none');
            grid.innerHTML = '<div class="col-12" style="flex-basis: 100%; width: 100%;"><div class="d-flex flex-column align-items-center justify-content-center text-center text-success py-5" style="min-height: 45vh;"><i class="bi bi-check-circle-fill fs-1 mb-2"></i><span>Você já está inscrito em 3 modalidades. Limite atingido.</span></div></div>';
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
            grid.innerHTML = '<div class="col-12" style="flex-basis: 100%; width: 100%;"><div class="d-flex flex-column align-items-center justify-content-center text-center text-muted py-5" style="min-height: 45vh;"><i class="bi bi-inbox fs-1 mb-2"></i><span>Nenhuma modalidade disponível para sua categoria no momento.</span></div></div>';
            return;
        }

        acoes.classList.remove('d-none');

        disponiveis.forEach(mod => {
            const col = document.createElement('div');
            col.className = 'col';
            const vagas = statusVagas(mod);
            col.innerHTML = `
                <div class="modalidade-card" data-id="${mod.id_modalidade}" data-nome="${esc(mod.nome_modalidade)}" onclick="abrirEquipesModalidade(this)">
                    <span class="card-check"><i class="bi bi-check-lg"></i></span>
                    <span class="card-vagas ${vagas.cls}"><i class="bi ${vagas.icon}"></i>${vagas.label}</span>
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
        const selecionados = document.querySelectorAll('.modalidade-card.selected').length;
        const inscritos = new Set(modalidadesInscritas.map(m => String(m.id_modalidade))).size;

        if (!card.classList.contains('selected') && selecionados + inscritos >= 3) {
            document.getElementById('msgFeedback').textContent = 'Você já selecionou o número máximo de modalidades.';
            card.classList.add('shake');
            setTimeout(() => card.classList.remove('shake'), 500);
            setTimeout(() => document.getElementById('msgFeedback').textContent = '', 2500);
            return;
        }

        const modal = new bootstrap.Modal(document.getElementById('modalEquipes'));
        document.getElementById('modalEquipesTitle').textContent = nomeModalidade || 'Escolha a equipe';
        document.getElementById('modalEquipesSubtitulo').textContent = 'Selecione em qual equipe da sua turma você vai jogar.';
        document.getElementById('modalEquipesCorpo').innerHTML = '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2" role="status"></div>Carregando equipes...</div>';
        modal.show();

        try {
            const res = await fetch(`../../../../api/equipes.php?id_modalidade=${idModalidade}&id_turma=${idTurmaUsuario}`);
            const dados = await res.json();
            const equipes = Array.isArray(dados) ? dados : [];

            if (equipes.length === 0) {
                document.getElementById('modalEquipesCorpo').innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                        Nenhuma equipe disponível para a sua turma nesta modalidade.
                    </div>`;
                return;
            }

            const equipeAtual = card.dataset.equipe || '';
            const jaSelecionada = card.classList.contains('selected');

            const botoesTopo = jaSelecionada
                ? `<button type="button" class="btn btn-outline-danger btn-sm w-100 mb-3" onclick="removerEquipeSelecionada(${idModalidade})">
                       <i class="bi bi-x-lg me-1"></i>Remover esta modalidade da seleção
                   </button>`
                : '';

            document.getElementById('modalEquipesCorpo').innerHTML = botoesTopo + equipes.map(e => `
                <div class="equipe-pick-row ${String(e.id_equipe) === equipeAtual ? 'selected' : ''}"
                     onclick="selecionarEquipe(this, ${idModalidade})"
                     data-equipe="${esc(e.id_equipe)}"
                     data-equipe-nome="${esc(e.nome_equipe)}">
                    <span class="equipe-pick-icon"><i class="bi bi-people-fill"></i></span>
                    <div class="equipe-pick-info">
                        <div class="equipe-pick-nome">${esc(e.nome_equipe)}</div>
                        <div class="equipe-pick-sub">${esc(e.nome_turma)} · ${parseInt(e.qtd_membros) || 0} membro(s)</div>
                    </div>
                    ${String(e.id_equipe) === equipeAtual ? '<span class="equipe-pick-check"><i class="bi bi-check-lg"></i></span>' : ''}
                </div>`).join('');
        } catch (e) {
            console.error(e);
            document.getElementById('modalEquipesCorpo').innerHTML = '<div class="text-danger small text-center py-4">Erro ao carregar equipes. Tente novamente.</div>';
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
