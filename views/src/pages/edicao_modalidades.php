<?php
$tituloPagina = 'SGI - Modalidades';
$titulo = 'Modalidades';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<!-- main mobile -->
<main class="position-relative d-md-none" style="margin-bottom: 90px;">
    <div class="p-3">
        <div class="modalidades-toolbar modalidades-toolbar--mobile">
            <a href="./dashboard.php" id="btnVoltarModalidadesMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseModalidadesMob">Interclasse</span>
            </a>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button type="button" class="btn btn-destaques d-inline-flex align-items-center justify-content-center fw-bold px-3 py-2" style="font-size: 1rem;" data-bs-toggle="modal" data-bs-target="#modalDestaques" title="Alunos Destaques">
                    <span>⭐</span>
                </button>
                <?php if ($nivelUsuario === 0): ?>
                <button type="button" class="btn btn-danger d-inline-flex align-items-center justify-content-center fw-bold px-3 py-2 border-0" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#exampleModal" title="Nova Modalidade">
                    <i class="bi bi-plus-lg"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <header class="modalidades-head modalidades-head--mobile">
            <h1 class="modalidades-head__title">Modalidades</h1>
            <p class="modalidades-head__sub">Gerencie as modalidades do interclasse e navegue para os detalhes de cada uma.</p>
        </header>

        <div id="listaModalidadesMobile">
            <p class="text-muted text-center mt-4">(Carregando modalidades...)</p>
        </div>
    </div>

    <div class="position-fixed bottom-0 start-0 w-100 p-3 d-none" id="barraContinuarMobile" style="z-index: 20; background: linear-gradient(transparent, #f8f9fa 35%);">
        <a href="#" id="btnContinuarMobile" class="btn btn-danger w-100 fw-bold text-white disabled" aria-disabled="true">Continuar</a>
    </div>
</main>


<!-- main desktop -->
<main class="d-none d-md-block main-desktop-layout">

    <div class="modalidades-toolbar">
            <a href="./dashboard.php" id="btnVoltarModalidades" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" style="background-color:#E30613;border-radius:6px;padding:8px 16px;">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseModalidades">Interclasse</span>
            </a>
        <div class="d-flex align-items-center gap-3 flex-shrink-0 flex-wrap">
            <button type="button" class="btn btn-destaques d-inline-flex align-items-center gap-2 fw-bold px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalDestaques">
                <span>⭐</span> Alunos Destaques
            </button>
            <?php if ($nivelUsuario === 0): ?>
            <button type="button" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-plus-lg"></i> Nova Modalidade
            </button>
            <?php endif; ?>
            <a href="#" id="btnContinuarDesktop" class="btn btn-danger fw-bold px-4 py-2 d-inline-flex align-items-center gap-2 text-white text-decoration-none disabled d-none" aria-disabled="true">
                Continuar
            </a>
        </div>
    </div>

    <header class="modalidades-head">
        <h1 class="modalidades-head__title">Modalidades</h1>
        <p class="modalidades-head__sub">Gerencie as modalidades do interclasse e navegue para os detalhes de cada uma.</p>
    </header>

    <div id="listaModalidadesDesktop">
        <p class="text-muted">(Carregando modalidades...)</p>
    </div>

</main>


<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border border-0">
                <h1 class="modal-title fs-5 text-danger" id="exampleModalLabel">Criar nova Modalidade</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaModalidade">
                    <div class="mb-3">
                        <label for="inputNomeModalidade" class="form-label fw-medium">Nome da Modalidade:</label>
                        <input type="text" class="form-control" id="inputNomeModalidade" placeholder="Ex: Futsal" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Gênero:</label>
                        <select class="form-select" id="inputGeneroModalidade" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 12" id="inputMaxInscritos" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Máx. de Equipes por Turma (Opcional):</label>
                        <input type="number" class="form-control" placeholder="Ex: 3" id="inputMaxEquipes" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Tipo de Modalidade:</label>
                        <select class="form-select" id="inputTipoModalidade" required>
                            <option value="" disabled selected>Carregando tipos...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Categoria:</label>
                        <select class="form-select" id="inputCategoriaModalidade" required>
                            <option value="" disabled selected>Carregando categorias...</option>
                        </select>
                    </div>
                    <div id="caixaMensagemModalidade" class="mt-3"></div>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarModalidade">Criar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Alunos Destaques -->
<div class="modal fade" id="modalDestaques" tabindex="-1" aria-labelledby="modalDestaquesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header border-0">
                <h5 class="modal-title text-danger fw-bold" id="modalDestaquesLabel"><i class="bi bi-star-fill me-2" style="color:#f5b301;"></i>Alunos Destaques</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="corpoDestaques">
                <p class="text-muted small">(Carregando destaques...)</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    const idCategoria = urlParams.get('id_categoria');
    const modo = urlParams.get('modo') || 'view';
    let modalidadeSelecionada = null;

    /* ── HELPERS ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    // TRAVA DE SEGURANÇA e Configuração dos Botões "Voltar"
    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            const msg = '<p class="text-muted mt-4 text-center w-100">Nenhum interclasse ativo.</p>';
            document.getElementById('listaModalidadesMobile').innerHTML = msg;
            document.getElementById('listaModalidadesDesktop').innerHTML = msg;
            window.location.href = "home.php";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const nome = dados?.nome_interclasse || 'Interclasse';
        ['nomeInterclasseModalidades', 'nomeInterclasseModalidadesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = nome;
        });
        window.SGIInterclasse.updatePageTitle(nome);
        atualizarBotaoContinuar();
        return idInterclasse;
    }

    function atualizarBotaoContinuar() {
        const botaoDesktop = document.getElementById('btnContinuarDesktop');
        const botaoMobile = document.getElementById('btnContinuarMobile');
        const barraMobile = document.getElementById('barraContinuarMobile');

        [botaoDesktop, botaoMobile].forEach((botao) => {
            if (!botao) return;
            botao.href = `./edicao_pontuacao.php?id=${idInterclasse}&modo=create${modalidadeSelecionada ? `&id_modalidade=${modalidadeSelecionada}` : ''}`;
            const disabled = !modalidadeSelecionada;
            botao.classList.toggle('disabled', disabled);
            botao.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        });

        if (botaoDesktop) botaoDesktop.classList.toggle('d-none', modo === 'view');
        if (barraMobile) barraMobile.classList.toggle('d-none', modo === 'view');

        const destinoVoltar = modo === 'view'
            ? `./dashboard.php?id=${idInterclasse}`
            : `./edicao_categorias.php?id=${idInterclasse}&modo=create`;
        ['btnVoltarModalidades', 'btnVoltarModalidadesMobile'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = destinoVoltar;
        });
    }

    /* ── RENDER CARD ── */
    function renderizarCard(modalidade) {
        const destino = `./modalidade_detalhes.php?id=${modalidade.id_modalidade}`;
        const genero = modalidade.genero_modalidade || '';
        const generoLabel = genero === 'MASC' ? 'Masculino' : genero === 'FEM' ? 'Feminino' : genero === 'MISTO' ? 'Misto' : genero;
        const qtdEquipes = Number(modalidade.qtd_equipes) || 0;
        const tipo = modalidade.nome_tipo_modalidade || '';

        return `
            <div class="col">
                <a href="${destino}" class="modalidade-card-simples" data-id="${modalidade.id_modalidade}" aria-label="Ver detalhes de ${esc(modalidade.nome_modalidade)}">
                    <div class="modalidade-card-topo">
                        <div class="modalidade-icone"><i class="bi bi-trophy"></i></div>
                        <div class="modalidade-titulo">
                            <h5 class="modalidade-nome">${esc(modalidade.nome_modalidade)}</h5>
                            <small class="modalidade-sub">${esc(tipo)}${tipo && genero ? ' · ' : ''}${esc(generoLabel)}</small>
                        </div>
                    </div>
                    <div class="modalidade-badges">
                        <span class="modalidade-badge modalidade-badge--accent"><i class="bi bi-people"></i> ${qtdEquipes} equipe${qtdEquipes !== 1 ? 's' : ''}</span>
                        ${modalidade.max_inscrito_modalidade ? `<span class="modalidade-badge"><i class="bi bi-person-lines-fill"></i> Máx. ${esc(modalidade.max_inscrito_modalidade)}</span>` : ''}
                    </div>
                    <span class="modalidade-cta">
                        Ver detalhes <i class="bi bi-arrow-right"></i>
                    </span>
                </a>
            </div>`;
    }

    /* ── RENDER POR CATEGORIA ── */
    function renderizarModalidades(modalidades) {
        const divMobile = document.getElementById('listaModalidadesMobile');
        const divDesktop = document.getElementById('listaModalidadesDesktop');
        if (!divMobile || !divDesktop) return;

        if (!modalidades.length) {
            const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
            divMobile.innerHTML = msgVazia;
            divDesktop.innerHTML = msgVazia;
            return;
        }

        const grupos = {};
        modalidades.forEach(m => {
            const chave = m.nome_categoria || 'Sem categoria';
            if (!grupos[chave]) grupos[chave] = [];
            grupos[chave].push(m);
        });

        let html = '';
        Object.entries(grupos).forEach(([catNome, lista]) => {
            html += `
                <section class="modalidades-categoria mb-5">
                    <div class="modalidade-categoria-header">
                        <h2 class="modalidade-categoria-nome">${esc(catNome)}</h2>
                        <span class="modalidade-categoria-count" title="Total de modalidades">${lista.length}</span>
                    </div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 g-lg-4">
                        ${lista.map(renderizarCard).join('')}
                    </div>
                </section>`;
        });

        divMobile.innerHTML = html;
        divDesktop.innerHTML = html;
        ligarEventosCards();
    }

    /* ── EVENTOS DOS CARDS ── */
    function ligarEventosCards() {
        document.querySelectorAll('.modalidade-card-simples').forEach((card) => {
            card.addEventListener('click', (e) => {
                if (modo === 'create') {
                    e.preventDefault();
                    selecionarModalidade(Number(card.dataset.id));
                }
            });
        });
    }

    function selecionarModalidade(id) {
        modalidadeSelecionada = Number(id);
        document.querySelectorAll('.modalidade-card-simples').forEach((card) => {
            card.classList.toggle('is-selected', Number(card.dataset.id) === modalidadeSelecionada);
        });
        atualizarBotaoContinuar();
    }

    // 1. FUNÇÃO: Listar as modalidades já existentes (Cards)
    async function carregarModalidades() {
        try {
            const filtroCategoria = idCategoria ? `&id_categoria=${idCategoria}` : '';
            const response = await axios.get(`../../../api/modalidades.php?x=1${filtroCategoria}`);
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidades = modalidades.filter((item) => String(item.interclasses_id_interclasse) === String(idInterclasse));
            renderizarModalidades(modalidades);
        } catch (error) {
            console.error("Erro ao carregar lista:", error);
            const msgErro = '<p class="text-muted mt-4 text-center w-100">Erro ao carregar modalidades.</p>';
            document.getElementById('listaModalidadesMobile').innerHTML = msgErro;
            document.getElementById('listaModalidadesDesktop').innerHTML = msgErro;
        }
    }

    // 2. FUNÇÃO: Preencher o Select de TIPOS (Vem da api/tipoModalidade.php)
    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('../../../api/tipoModalidade.php');
            const tipos = response.data;
            selectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += `<option value="${tipo.id_tipo_modalidade}">${tipo.nome_tipo_modalidade}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar tipos:", error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 3. FUNÇÃO: Preencher o Select de CATEGORIAS (Vem da api/categorias.php)
    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get(`../../../api/categorias.php?id_interclasse=${idInterclasse}`);
            const categorias = response.data;
            selectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            categorias.forEach((cat) => {
                const selected = idCategoria && String(idCategoria) === String(cat.id_categoria) ? 'selected' : '';
                selectCat.innerHTML += `<option value="${cat.id_categoria}" ${selected}>${cat.nome_categoria}</option>`;
            });
        } catch (error) {
            console.error("Erro ao carregar categorias:", error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    // 4. EVENTO: Enviar Formulário de Criação
    document.getElementById('formNovaModalidade').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');

        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_modalidade: document.getElementById('inputNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('inputGeneroModalidade').value,
            max_inscrito_modalidade: parseInt(document.getElementById('inputMaxInscritos').value) || 0,
            max_equipes: (() => { const v = document.getElementById('inputMaxEquipes').value; return v === '' ? null : parseInt(v); })(),
            tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
            categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
        };

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = "Salvando...";
            const res = await axios.post('../../../api/modalidades.php', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = `<p class="text-success text-center fw-bold">Criada com sucesso!</p>`;
                document.getElementById('formNovaModalidade').reset();
                modalidadeSelecionada = Number(res.data.id);
                carregarModalidades();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('exampleModal')).hide();
                    caixaMensagem.innerHTML = "";
                }, 1000);
            }
        } catch (error) {
            caixaMensagem.innerHTML = `<p class="text-danger text-center fw-bold">Erro ao salvar.</p>`;
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = "Criar";
        }
    });

    /* ── ALUNOS DESTAQUES ── */
    function montarItemDestaque(a) {
        const temFoto = a.foto_usuario && !/^default\.(jpg|jpeg|png|gif|webp)$/i.test(a.foto_usuario);
        const fotoHtml = temFoto
            ? `<img src="../../../uploads/fotosUsuarios/${encodeURIComponent(a.foto_usuario)}" alt="${esc(a.nome_usuario)}" onerror="this.classList.add('d-none');this.nextElementSibling.classList.remove('d-none');">`
            : '';
        const iconeHtml = `<span class="${temFoto ? 'd-none' : ''}"><i class="bi bi-star-fill"></i></span>`;
        const turma = a.nome_fantasia_turma || a.nome_turma || 'Sem turma';
        const gols = Number(a.total_gols) || 0;

        return `
            <div class="destaque-item">
                <div class="destaque-avatar">${fotoHtml}${iconeHtml}</div>
                <div class="destaque-info">
                    <div class="destaque-nome">${esc(a.nome_usuario)} <i class="bi bi-star-fill"></i></div>
                    <div class="destaque-sub">${esc(a.nome_categoria || 'Sem categoria')} · ${esc(turma)}</div>
                </div>
                <div class="destaque-gols">${gols}<small>gol${gols !== 1 ? 's' : ''}</small></div>
            </div>`;
    }

    async function carregarDestaques() {
        const corpo = document.getElementById('corpoDestaques');
        corpo.innerHTML = `<div class="text-center py-5">
            <div class="spinner-border text-danger" role="status"></div>
            <p class="text-muted mt-3 mb-0">Carregando destaques...</p>
        </div>`;

        try {
            const res = await axios.get(`../../../api/artilheiro.php?acao=destaques_modalidades&id_interclasse=${idInterclasse}`);
            const raw = res.data && res.data.data !== undefined ? res.data.data : res.data;
            const lista = Array.isArray(raw) ? raw : [];

            if (!lista.length) {
                corpo.innerHTML = '<p class="text-muted text-center py-4">Nenhum aluno destaque registrado para este interclasse ainda.</p>';
                return;
            }

            const grupos = {};
            lista.forEach(d => {
                const chave = d.nome_modalidade || 'Sem modalidade';
                if (!grupos[chave]) grupos[chave] = [];
                grupos[chave].push(d);
            });

            let html = '';
            Object.entries(grupos).forEach(([modalidade, alunos]) => {
                html += `<div class="destaque-group-title"><i class="bi bi-trophy-fill"></i>${esc(modalidade)}</div>`;
                html += alunos.map(montarItemDestaque).join('');
            });
            corpo.innerHTML = html;
        } catch (error) {
            console.error('Erro ao carregar destaques:', error);
            corpo.innerHTML = '<p class="text-danger text-center py-4">Erro ao carregar os destaques.</p>';
        }
    }

    document.getElementById('modalDestaques').addEventListener('show.bs.modal', carregarDestaques);

    // 5. INICIALIZAÇÃO: Onde a mágica acontece
    window.addEventListener('load', async () => {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
        atualizarBotaoContinuar();
    });
</script>



<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
