window.SGIPage.mount("eventos/configurar-pontuacao", function (pageConfig, pageScope) {

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

    window.SGIPage.ready( () => {
        document.querySelectorAll('.ptc-stepper').forEach(stepper => {
            const input = stepper.querySelector('.ptc-step-input');
            const menos = stepper.querySelector('.ptc-step-btn--minus');
            const mais = stepper.querySelector('.ptc-step-btn--plus');
            if (menos) pageScope.listen(menos, 'click', () => alterarPontos(input.id, -1));
            if (mais) pageScope.listen(mais, 'click', () => alterarPontos(input.id, 1));
            pageScope.listen(input, 'input', marcarMudancas);
            pageScope.listen(input, 'change', () => validarPontos(input.id));
        });
    });

    window.SGIPage.ready( async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;

        const btnContinuar = document.getElementById('btnContinuarPontuacao');
        if (btnContinuar) {
            btnContinuar.href = `./edicao_resumo.php?id=${idInterclasse}&modo=create`;
            if (modo === 'create') btnContinuar.classList.remove('d-none');
        }
    });

return {getPontos, setPontos, marcarMudancas, resolverInterclasse};
});
