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

<main class="main-desktop-layout main-ptc-layout">
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
