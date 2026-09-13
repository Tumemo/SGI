window.SGIPage.mount("competicoes/equipe-alunos", function (pageConfig, pageScope) {

let alunos = [];
let alunosNaEquipe = [];
let alunosSelecionados = new Set();
let generoDaModalidade = 'MISTO';
let _idEquipe = null;

function mostrarToast(tipo, texto) {
    window.SGI.showToast(texto, tipo === 'sucesso' ? 'success' : 'error');
}

function cardAluno(aluno) {
    const idAluno = String(aluno.id_usuario || '');
    const estaNaEquipe = alunosNaEquipe.some(a => String(a.id_usuario) === idAluno);
    const selecionado = estaNaEquipe || alunosSelecionados.has(idAluno);
    const semInscricao = Number(aluno.inscrito || 0) === 0;
    const badge = semInscricao ? '<span class="badge text-bg-primary ms-2">Sem inscrição</span>' : '';
    const badgeEquipe = estaNaEquipe ? '<span class="badge text-bg-secondary ms-2">Já na equipe</span>' : '';
    return `
        <label class="col d-flex align-items-center justify-content-between gap-3 border rounded-3 p-3 bg-body ${semInscricao ? 'border-2 border-primary bg-primary-subtle' : ''}">
            <div>
                <strong>${window.SGIHtml.escape(aluno.nome_usuario)}</strong>${badge}${badgeEquipe}
                <div class="text-muted small">${window.SGIHtml.escape(aluno.matricula_usuario)} (${window.SGIHtml.escape(aluno.genero_usuario || 'Não informado')})</div>
            </div>
            <input class="form-check-input aluno-check" type="checkbox" value="${window.SGIHtml.escape(idAluno)}" ${selecionado ? 'checked' : ''} ${estaNaEquipe ? 'disabled aria-label="Aluno já vinculado à equipe"' : 'aria-label="Adicionar aluno à equipe"'}>
        </label>
    `;
}

function renderizar(lista) {
    const mobile = document.getElementById('listaAlunosMobile');
    const desktop = document.getElementById('listaAlunosDesktop');

    if (!lista.length) {
        const msg = '<div class="text-center py-5 text-body-secondary"><i class="bi bi-people fs-1 d-block mb-3" aria-hidden="true"></i><h5 class="fw-semibold mb-2">Nenhum aluno disponível</h5><p class="small mb-0">Nenhum aluno foi encontrado para esta turma com o gênero compatível com a modalidade.</p></div>';
        mobile.innerHTML = msg;
        desktop.innerHTML = msg;
        return;
    }

    const html = lista.map(cardAluno).join('');
    mobile.innerHTML = html;
    desktop.innerHTML = html;
}

function filtrar(termo) {
    const t = termo.trim().toLowerCase();
    const filtrados = alunos.filter(aluno =>
        String(aluno.nome_usuario || '').toLowerCase().includes(t) ||
        String(aluno.matricula_usuario || '').toLowerCase().includes(t)
    );
    renderizar(filtrados);
}

function sincronizarSelecao(event) {
    const checkbox = event.target;
    if (!checkbox?.classList?.contains('aluno-check')) return;

    const idAluno = String(checkbox.value);
    if (alunosNaEquipe.some(aluno => String(aluno.id_usuario) === idAluno)) {
        checkbox.checked = true;
        return;
    }
    if (checkbox.checked) alunosSelecionados.add(idAluno);
    else alunosSelecionados.delete(idAluno);

    document.querySelectorAll('.aluno-check').forEach(outro => {
        if (String(outro.value) === idAluno) outro.checked = checkbox.checked;
    });
}

function sincronizarBusca(event) {
    const valor = event.target.value;
    ['buscaAlunosDesktop', 'buscaAlunosMobile'].forEach(id => {
        const campo = document.getElementById(id);
        if (campo && campo !== event.target) campo.value = valor;
    });
    filtrar(valor);
}

async function carregar() {
    const params = new URLSearchParams(window.location.search);
    const idInterclasse = params.get('id');

    if (idInterclasse) {
        window.SGIInterclasse.getInterclasseById(idInterclasse).then(dados => {
            const nome = dados?.nome_interclasse || 'Interclasse';
            ['nomeInterclasseEquipeAlunosMob', 'nomeInterclasseEquipeAlunosDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = nome;
            });
        }).catch(() => {});
    }
    const idTurma = params.get('id_turma');
    _idEquipe = params.get('id_equipe');
    const idCategoria = params.get('id_categoria');
    const idModalidade = params.get('id_modalidade');
    const nomeTurma = params.get('nome_turma') || '';
    const nomeModalidade = params.get('nome_modalidade') || '';

    const qVoltar = new URLSearchParams();
    if (idInterclasse) qVoltar.set('id', idInterclasse);
    if (idTurma) qVoltar.set('id_turma', idTurma);
    if (_idEquipe) qVoltar.set('id_equipe', _idEquipe);
    if (idCategoria) qVoltar.set('id_categoria', idCategoria);
    if (idModalidade) qVoltar.set('id_modalidade', idModalidade);
    if (nomeTurma) qVoltar.set('nome_turma', nomeTurma);
    if (nomeModalidade) qVoltar.set('nome_modalidade', nomeModalidade);
    const voltar = `/equipes/elenco?${qVoltar.toString()}`;
    document.getElementById('btnVoltarEquipesDesktop').href = voltar;
    const vm = document.getElementById('btnVoltarEquipesMobile');
    if (vm) vm.href = voltar;

    try {
        const ts = Date.now();

        if (idModalidade) {
            try {
                const resMod = await fetch(`/api/v1/modalidades?id_modalidade=${idModalidade}&_t=${ts}`);
                const dadosMod = await resMod.json();
                if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                    generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                } else if (dadosMod && dadosMod.genero_modalidade) {
                    generoDaModalidade = dadosMod.genero_modalidade;
                } else {
                    generoDaModalidade = 'MISTO';
                }
            } catch (e) {
                console.error("Erro ao obter gênero da modalidade:", e);
                generoDaModalidade = 'MISTO';
            }
        } else if (idCategoria) {
            try {
                const resMod = await fetch(`/api/v1/modalidades?id_categoria=${idCategoria}&_t=${ts}`);
                const dadosMod = await resMod.json();
                if (Array.isArray(dadosMod) && dadosMod.length > 0) {
                    generoDaModalidade = dadosMod[0].genero_modalidade || 'MISTO';
                }
            } catch (e) {
                generoDaModalidade = 'MISTO';
            }
        }

        const resEquipe = await fetch(`/api/v1/equipes?id_equipe=${_idEquipe}&_t=${ts}`);
        const rawEq = await resEquipe.json();
        alunosNaEquipe = Array.isArray(rawEq) ? rawEq : [];
        alunosSelecionados = new Set(alunosNaEquipe.map(aluno => String(aluno.id_usuario)));

        const generoParam = (generoDaModalidade === 'MISTO' || generoDaModalidade === 'MISTA') ? '' : `&genero=${generoDaModalidade}`;
        const res = await fetch(`/api/v1/usuarios?acao=listar_competidores&id_turma=${idTurma}${generoParam}&_t=${ts}`);
        const data = await res.json();
        alunos = (data && data.competidores) ? data.competidores : (Array.isArray(data) ? data : []);

        const termoBusca = document.getElementById('buscaAlunosDesktop')?.value
            ?? document.getElementById('buscaAlunosMobile')?.value
            ?? '';
        filtrar(termoBusca);
    } catch (error) {
        console.error("Erro ao carregar dados:", error);
        document.getElementById('listaAlunosMobile').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        document.getElementById('listaAlunosDesktop').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
    }
}

async function salvar() {
    const checks = Array.from(document.querySelectorAll('.aluno-check:checked'));
    const membros = new Set(alunosNaEquipe.map(aluno => String(aluno.id_usuario)));
    const ids = [...new Set(checks.map(item => Number(item.value)).filter(Boolean))]
        .filter(id => !membros.has(String(id)));

    if (!ids.length) {
        mostrarToast('erro', 'Selecione pelo menos um novo aluno para adicionar.');
        return;
    }

    const botoes = [
        document.getElementById('btnSalvarAlunosDesktop'),
        document.getElementById('btnSalvarAlunosMobile')
    ];

    botoes.forEach(b => { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; });

    try {
        const response = await fetch('/api/v1/equipes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                acao: 'adicionar_usuarios',
                id_equipe: Number(_idEquipe),
                usuarios: ids
            })
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Falha ao salvar.');
        mostrarToast('sucesso', 'Alterações salvas com sucesso.');
    } catch (error) {
        mostrarToast('erro', error.message);
        return;
    } finally {
        botoes.forEach(b => { b.disabled = false; b.innerHTML = '<i class=\"bi bi-check-lg\"></i>'; });
    }

    await carregar();
}

pageScope.listen(document.getElementById('btnSalvarAlunosDesktop'), 'click', salvar);
pageScope.listen(document.getElementById('btnSalvarAlunosMobile'), 'click', salvar);
pageScope.listen(document.getElementById('listaAlunosDesktop'), 'change', sincronizarSelecao);
pageScope.listen(document.getElementById('listaAlunosMobile'), 'change', sincronizarSelecao);
pageScope.listen(document.getElementById('buscaAlunosDesktop'), 'input', sincronizarBusca);
pageScope.listen(document.getElementById('buscaAlunosMobile'), 'input', sincronizarBusca);

pageScope.listen(window, 'pageshow', carregar);

return {mostrarToast, cardAluno, renderizar, filtrar, carregar, salvar};
});
