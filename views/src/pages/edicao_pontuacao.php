<?php
$tituloPagina = 'SGI - Pontuações';
$titulo = 'Pontuações';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';

$ptcCards = [
    [
        'key'     => '1',
        'classe'  => 'gold',
        'icone'   => 'bi-trophy-fill',
        'titulo'  => '1º Lugar',
        'sub'     => 'Medalha de Ouro',
        'badge'   => 'Ouro',
        'label'   => 'Pontos',
        'valor'   => 10,
        'desc'    => 'Pontos atribuídos à 1ª colocação de cada modalidade.'
    ],
    [
        'key'     => '2',
        'classe'  => 'silver',
        'icone'   => 'bi-award-fill',
        'titulo'  => '2º Lugar',
        'sub'     => 'Medalha de Prata',
        'badge'   => 'Prata',
        'label'   => 'Pontos',
        'valor'   => 7,
        'desc'    => 'Pontos atribuídos à 2ª colocação de cada modalidade.'
    ],
    [
        'key'     => '3',
        'classe'  => 'bronze',
        'icone'   => 'bi-award-fill',
        'titulo'  => '3º Lugar',
        'sub'     => 'Medalha de Bronze',
        'badge'   => 'Bronze',
        'label'   => 'Pontos',
        'valor'   => 5,
        'desc'    => 'Pontos atribuídos à 3ª colocação de cada modalidade.'
    ],
    [
        'key'     => 'arr',
        'classe'  => 'multi',
        'icone'   => 'bi-lightning-charge-fill',
        'titulo'  => 'Multiplicador',
        'sub'     => 'Arrecadação da turma',
        'badge'   => 'Especial',
        'label'   => 'Multiplicador',
        'valor'   => 2,
        'desc'    => 'Fator aplicado sobre os kg arrecadados pela turma.'
    ],
];
?>

<style>
    :root {
        --ptc-red: #e30613;
        --ptc-red-dark: #b9050f;
        --ptc-text: #1f2937;
        --ptc-text-2: #4b5563;
        --ptc-text-3: #6b7280;
        --ptc-border: #e5e7eb;
        --ptc-shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.06);
        --ptc-shadow-hover: 0 10px 24px rgba(16,24,40,.10);
        --ptc-pad-t: 1.05rem;
    }

    .ptc-container { max-width: 1400px; margin: 3rem auto 0; width: 100%; }

    /* ── Cabeçalho ─────────────────────────────────────────────── */
    .ptc-btn-interclasse {
        display: inline-flex; align-items: center; gap: .5rem;
        background: var(--ptc-red); color: #fff; font-weight: 600;
        border-radius: 10px; padding: 9px 18px; text-decoration: none;
        box-shadow: 0 3px 10px rgba(227,6,19,.28);
        transition: background .2s ease, transform .15s ease, box-shadow .2s ease;
        flex-shrink: 0;
    }
    .ptc-btn-interclasse:hover { background: var(--ptc-red-dark); color: #fff; transform: translateY(-1px); box-shadow: 0 5px 16px rgba(227,6,19,.38); }

    .ptc-header { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; margin-bottom: 3rem; }
    .ptc-title-wrap { min-width: 0; flex: 1 1 280px; }
    .ptc-title {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1.65rem; font-weight: 700; color: var(--ptc-text); margin: 0; letter-spacing: -.01em;
    }
    .ptc-title i { color: var(--ptc-red); }
    .ptc-subtitle { font-size: .9rem; color: var(--ptc-text-2); margin: .35rem 0 0; }
    .ptc-actions { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }

    .ptc-btn-salvar, .ptc-btn-default, .ptc-btn-continuar {
        border-radius: 10px; font-weight: 600; padding: .6rem 1.2rem;
        display: inline-flex; align-items: center; gap: .5rem;
        font-size: .95rem;
    }
    .ptc-btn-salvar { box-shadow: 0 3px 10px rgba(227,6,19,.28); }
    .ptc-btn-default { color: #4b5563; border-color: #d1d5db; }
    .ptc-btn-default:hover { background: #f3f4f6; color: #1f2937; }
    .ptc-btn-continuar { background: #1f2937; border-color: #1f2937; color: #fff; }
    .ptc-btn-continuar:hover { background: #111827; border-color: #111827; color: #fff; }

    .ptc-unsaved {
        display: inline-flex; align-items: center; gap: .4rem; font-size: .75rem; font-weight: 600;
        color: #b45309; background: #fffbeb; border: 1px solid #fde68a;
        padding: .4rem .8rem; border-radius: 50px;
    }

    /* ── Cards de pontuação ────────────────────────────────────── */
    .ptc-card {
        position: relative; border-radius: 18px;
        display: flex; flex-direction: column; justify-content: space-between;
        padding: var(--ptc-pad-t) 1.1rem 1rem; height: 100%;
        background: #fff; border: 1px solid var(--ptc-border);
        border-left: 4px solid var(--ptc-border);
        box-shadow: var(--ptc-shadow);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .ptc-card:hover { transform: translateY(-3px); box-shadow: var(--ptc-shadow-hover); }

    .ptc-card--gold { background: linear-gradient(160deg, #fffef8 0%, #fff8e1 100%); border-left-color: #f59e0b; }
    .ptc-card--silver { background: linear-gradient(160deg, #fbfcff 0%, #eef2f7 100%); border-left-color: #64748b; }
    .ptc-card--bronze { background: linear-gradient(160deg, #fdf9f5 0%, #f8ecdf 100%); border-left-color: #d97706; }
    .ptc-card--multi { background: linear-gradient(160deg, #fff7f7 0%, #fdeaea 100%); border-left-color: #ef4444; }

    .ptc-card-head { display: flex; align-items: center; gap: .75rem; }
    .ptc-card-icon {
        width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .ptc-card--gold .ptc-card-icon { background: linear-gradient(135deg, #fde68a, #f59e0b); color: #7c2d12; }
    .ptc-card--silver .ptc-card-icon { background: linear-gradient(135deg, #e8edf3, #94a3b8); color: #334155; }
    .ptc-card--bronze .ptc-card-icon { background: linear-gradient(135deg, #f3d5b0, #c98a4b); color: #7c3f1a; }
    .ptc-card--multi .ptc-card-icon { background: linear-gradient(135deg, #ff8a8a, #e30613); color: #fff; }

    .ptc-card-title { font-weight: 700; font-size: 1.02rem; color: var(--ptc-text); margin: 0; line-height: 1.25; }
    .ptc-card-sub { font-size: .78rem; color: var(--ptc-text-2); margin: .1rem 0 0; }

    .ptc-rank-badge {
        position: absolute; top: calc(var(--ptc-pad-t) + 9px); right: 1.1rem; z-index: 2;
        font-size: .64rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        padding: .28rem .7rem; border-radius: 50px;
    }
    .ptc-card--gold .ptc-rank-badge { background: #fef3c7; color: #92400e; }
    .ptc-card--silver .ptc-rank-badge { background: #f1f5f9; color: #475569; }
    .ptc-card--bronze .ptc-rank-badge { background: #fde8d6; color: #9a5b23; }
    .ptc-card--multi .ptc-rank-badge { background: #fde8e8; color: #b91c1c; }

    .ptc-card-value { text-align: center; margin: 1rem 0 .1rem; }
    .ptc-card-label {
        font-size: .66rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        color: var(--ptc-text-3);
    }

    .ptc-stepper { display: flex; align-items: center; justify-content: center; gap: .6rem; margin-top: .5rem; }
    .ptc-step-btn {
        width: 44px; height: 44px; border-radius: 12px; border: none; font-size: 1.15rem;
        display: flex; align-items: center; justify-content: center; cursor: pointer;
        transition: all .18s ease; flex-shrink: 0;
    }
    .ptc-step-btn:active { transform: scale(.92); }
    .ptc-card--gold .ptc-step-btn { background: #fef3c7; color: #b45309; }
    .ptc-card--gold .ptc-step-btn:hover { background: #f59e0b; color: #fff; }
    .ptc-card--silver .ptc-step-btn { background: #f1f5f9; color: #475569; }
    .ptc-card--silver .ptc-step-btn:hover { background: #64748b; color: #fff; }
    .ptc-card--bronze .ptc-step-btn { background: #fde8d6; color: #b45309; }
    .ptc-card--bronze .ptc-step-btn:hover { background: #d97706; color: #fff; }
    .ptc-card--multi .ptc-step-btn { background: #fde8e8; color: #dc2626; }
    .ptc-card--multi .ptc-step-btn:hover { background: #ef4444; color: #fff; }

    .ptc-step-input {
        width: 88px; height: 50px; border-radius: 12px; border: 2px solid #d1d5db;
        text-align: center; font-size: 1.4rem; font-weight: 700; color: var(--ptc-text);
        background: #fff; -moz-appearance: textfield;
        transition: border-color .2s, box-shadow .2s;
    }
    .ptc-step-input::-webkit-outer-spin-button,
    .ptc-step-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .ptc-step-input:focus { outline: none; border-color: var(--ptc-red); box-shadow: 0 0 0 3px rgba(227,6,19,.1); }
    .ptc-step-input.ptc-pop { animation: ptcPop .25s ease; }
    @keyframes ptcPop {
        0%   { transform: scale(1); }
        40%  { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .ptc-card-foot {
        margin-top: .7rem; padding-top: .6rem; border-top: 1px dashed #d1d5db;
        font-size: .74rem; color: var(--ptc-text-3); text-align: center; line-height: 1.45;
    }

    /* ── Rodapé da seção ───────────────────────────────────────── */
    .ptc-note {
        display: flex; align-items: center; gap: .5rem;
        margin-top: 1.75rem; padding: .8rem 1.1rem;
        background: #fff; border: 1px solid var(--ptc-border); border-radius: 14px;
        color: var(--ptc-text-2); font-size: .85rem;
    }
    .ptc-note i { color: var(--ptc-red); font-size: 1.1rem; }

    /* ── Mobile ────────────────────────────────────────────────── */
    @media (max-width: 767.98px) {
        .main-desktop-layout { margin-left: 0 !important; padding: 1rem 1rem 5.5rem !important; }
        .ptc-header { gap: 1rem; }
        .ptc-title { font-size: 1.4rem; }
        .ptc-step-btn { width: 42px; height: 42px; }
        .ptc-step-input { width: 80px; height: 48px; font-size: 1.3rem; }
    }
</style>

<main class="main-desktop-layout">
    <div class="px-0 ptc-container">

        <div class="ptc-header">
            <a href="./dashboard.php" id="btnVoltarPontuacao" class="ptc-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclassePontuacao">Interclasse</span>
            </a>
            <div class="ptc-title-wrap">
                <h1 class="ptc-title"><i class="bi bi-award"></i> Edição de Pontuações</h1>
                <p class="ptc-subtitle">Ajuste os pontos de cada colocação e os multiplicadores de evento</p>
            </div>
            <div class="ptc-actions">
                <span class="ptc-unsaved d-none" id="ptcUnsaved">
                    <i class="bi bi-exclamation-circle-fill"></i> Alterações não salvas
                </span>
                <button type="button" class="btn ptc-btn-default" id="btnRestaurarPadrao" onclick="restaurarPadrao()">
                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar Padrão
                </button>
                <button type="button" class="btn btn-danger ptc-btn-salvar" id="btnSalvarPontuacao" onclick="salvarPontuacao()">
                    <i class="bi bi-check-lg"></i> Salvar
                </button>
                <a href="#" id="btnContinuarPontuacao" class="btn ptc-btn-continuar d-none">
                    Continuar <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($ptcCards as $c): ?>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="ptc-card ptc-card--<?= $c['classe'] ?>">
                    <span class="ptc-rank-badge"><?= $c['badge'] ?></span>

                    <div class="ptc-card-head">
                        <div class="ptc-card-icon"><i class="bi <?= $c['icone'] ?>"></i></div>
                        <div>
                            <div class="ptc-card-title"><?= $c['titulo'] ?></div>
                            <div class="ptc-card-sub"><?= $c['sub'] ?></div>
                        </div>
                    </div>

                    <div class="ptc-card-value">
                        <span class="ptc-card-label"><?= $c['label'] ?></span>
                        <div class="ptc-stepper">
                            <button type="button" class="ptc-step-btn ptc-step-btn--minus" aria-label="Diminuir <?= $c['titulo'] ?>">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="ptc-step-input" id="pontos-<?= $c['key'] ?>"
                                   value="<?= $c['valor'] ?>" min="0" step="1" inputmode="numeric">
                            <button type="button" class="ptc-step-btn ptc-step-btn--plus" aria-label="Aumentar <?= $c['titulo'] ?>">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="ptc-card-foot"><?= $c['desc'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="ptc-note">
            <i class="bi bi-info-circle"></i>
            <span>Os valores são aplicados ao Interclasse ativo. Altere com os botões <strong>+</strong> e <strong>&minus;</strong> ou digite diretamente no campo central.</span>
        </div>

    </div>
</main>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const modo = urlParams.get('modo') || 'view';

    const PADRAO = { 'pontos-1': 10, 'pontos-2': 7, 'pontos-3': 5, 'pontos-arr': 2 };
    let VALORES_INICIAIS = {};

    function getPontos(id) {
        const el = document.getElementById(id);
        const v = parseInt(el ? el.value : '', 10);
        return isNaN(v) ? 0 : v;
    }

    function setPontos(id, v) {
        const el = document.getElementById(id);
        if (!el) return;
        const n = parseInt(v, 10);
        el.value = isNaN(n) ? PADRAO[id] : n;
    }

    window.alterarPontos = function (id, delta) {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = Math.max(0, getPontos(id) + delta);
        el.classList.remove('ptc-pop');
        void el.offsetWidth;
        el.classList.add('ptc-pop');
        marcarMudancas();
    };

    window.validarPontos = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        let v = parseInt(el.value, 10);
        if (isNaN(v) || v < 0) v = 0;
        el.value = v;
        marcarMudancas();
    };

    window.restaurarPadrao = function () {
        if (!confirm('Restaurar os valores padrão (1º: 10, 2º: 7, 3º: 5, Multiplicador: 2)?')) return;
        Object.entries(PADRAO).forEach(([id, v]) => {
            const el = document.getElementById(id);
            if (el) el.value = v;
        });
        marcarMudancas();
    };

    function marcarMudancas() {
        const pill = document.getElementById('ptcUnsaved');
        if (!pill) return;
        const mudou = Object.keys(VALORES_INICIAIS).some(id => getPontos(id) !== VALORES_INICIAIS[id]);
        pill.classList.toggle('d-none', !mudou);
    }

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const nome = dados?.nome_interclasse || 'Interclasse';
        document.getElementById('nomeInterclassePontuacao').innerText = nome;
        window.SGIInterclasse.updatePageTitle(dados?.nome_interclasse);

        const btnBack = document.getElementById('btnVoltarPontuacao');
        if (btnBack) {
            btnBack.href = modo === 'view'
                ? `./dashboard.php?id=${idInterclasse}`
                : `./edicao_modalidades.php?id=${idInterclasse}&modo=create`;
        }

        if (dados) {
            setPontos('pontos-1', dados.ponto_1_lugar);
            setPontos('pontos-2', dados.ponto_2_lugar);
            setPontos('pontos-3', dados.ponto_3_lugar);
            setPontos('pontos-arr', dados.valor_item_arrecadacao);
        }

        VALORES_INICIAIS = {
            'pontos-1': getPontos('pontos-1'),
            'pontos-2': getPontos('pontos-2'),
            'pontos-3': getPontos('pontos-3'),
            'pontos-arr': getPontos('pontos-arr')
        };
        marcarMudancas();
        return idInterclasse;
    }

    window.salvarPontuacao = async function () {
        const btn = document.getElementById('btnSalvarPontuacao');
        const pontos1 = getPontos('pontos-1');
        const pontos2 = getPontos('pontos-2');
        const pontos3 = getPontos('pontos-3');
        const pontosArr = getPontos('pontos-arr');

        try {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando…';

            const formData = new FormData();
            formData.append('ponto_1_lugar', pontos1);
            formData.append('ponto_2_lugar', pontos2);
            formData.append('ponto_3_lugar', pontos3);
            formData.append('valor_item_arrecadacao', pontosArr);

            const resp = await fetch(`../../../api/interclasse.php?id=${idInterclasse}`, {
                method: 'POST',
                body: formData
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao salvar.');

            VALORES_INICIAIS = { 'pontos-1': pontos1, 'pontos-2': pontos2, 'pontos-3': pontos3, 'pontos-arr': pontosArr };
            marcarMudancas();

            btn.classList.remove('btn-danger');
            btn.classList.add('btn-success');
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvo!';
            setTimeout(() => {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
            }, 2000);
        } catch (err) {
            alert(err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Salvar';
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.ptc-stepper').forEach(stepper => {
            const input = stepper.querySelector('.ptc-step-input');
            const menos = stepper.querySelector('.ptc-step-btn--minus');
            const mais = stepper.querySelector('.ptc-step-btn--plus');
            if (menos) menos.addEventListener('click', () => alterarPontos(input.id, -1));
            if (mais) mais.addEventListener('click', () => alterarPontos(input.id, 1));
            input.addEventListener('input', marcarMudancas);
            input.addEventListener('change', () => validarPontos(input.id));
        });
    });

    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        const btnContinuar = document.getElementById('btnContinuarPontuacao');
        if (btnContinuar) {
            btnContinuar.href = `./edicao_resumo.php?id=${idInterclasse}&modo=create`;
            if (modo === 'create') btnContinuar.classList.remove('d-none');
        }
    });
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
