window.SGIPage.mount("competicoes/equipe-alunos", function (pageConfig, pageScope) {

let alunos = [];
let alunosNaEquipe = [];
let generoDaModalidade = 'MISTO';
let _idEquipe = null;

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

function cardAluno(aluno) {
    const estaNaEquipe = alunosNaEquipe.some(a => a.id_usuario === aluno.id_usuario);
    const semInscricao = Number(aluno.inscrito || 0) === 0;
    const badge = semInscricao ? '<span class="badge-sem-inscricao">Sem inscrição</span>' : '';
    return `
        <label class="aluno-card-item ${semInscricao ? 'sem-inscricao' : ''}">
            <div>
                <strong>${esc(aluno.nome_usuario)}</strong>${badge}
                <div class="text-muted small">${esc(aluno.matricula_usuario)} (${aluno.genero_usuario || 'Não informado'})</div>
            </div>
            <input class="form-check-input aluno-check" type="checkbox" value="${aluno.id_usuario}" ${estaNaEquipe ? 'checked' : ''}>
        </label>
    `;
}

function renderizar(lista) {
    const mobile = document.getElementById('listaAlunosMobile');
    const desktop = document.getElementById('listaAlunosDesktop');

    if (!lista.length) {
        const msg = '<div class="aluno-empty"><div class="empty-icon"><i class="bi bi-people"></i></div><h5>Nenhum aluno disponível</h5><p>Nenhum aluno foi encontrado para esta turma com o gênero compatível com a modalidade.</p></div>';
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

        const generoParam = (generoDaModalidade === 'MISTO' || generoDaModalidade === 'MISTA') ? '' : `&genero=${generoDaModalidade}`;
        const res = await fetch(`/api/v1/usuarios?acao=listar_competidores&id_turma=${idTurma}${generoParam}&_t=${ts}`);
        const data = await res.json();
        alunos = (data && data.competidores) ? data.competidores : (Array.isArray(data) ? data : []);

        renderizar(alunos);
    } catch (error) {
        console.error("Erro ao carregar dados:", error);
        document.getElementById('listaAlunosMobile').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
        document.getElementById('listaAlunosDesktop').innerHTML = '<p class="text-danger text-center">Erro ao carregar alunos.</p>';
    }
}

async function salvar() {
    const checks = Array.from(document.querySelectorAll('.aluno-check:checked'));
    const ids = checks.map(item => Number(item.value)).filter(Boolean);

    if (!ids.length) {
        mostrarToast('erro', 'Selecione pelo menos um aluno.');
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

pageScope.listen(window, 'pageshow', carregar);

return {mostrarToast, cardAluno, renderizar, filtrar, carregar, salvar};
});
