<?php
$tituloPagina = 'SGI - Colaboradores';
$titulo = 'Colaboradores';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
$cssExtra = '
/* ═══ Colaboradores Modern ═══ */
.col-page{padding-bottom:5rem}
.col-wrap{width:100%;padding:0 2rem}
@media(max-width:575.98px){.col-wrap{padding:0 1rem}}

/* Header */
.col-header{margin-bottom:1.75rem}
.col-header__top{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap}
.col-header__title{font-size:1.75rem;font-weight:800;color:#111827;letter-spacing:-.03em;margin:0;line-height:1.2}
.col-header__sub{font-size:.88rem;color:#6B7280;margin:.3rem 0 0;font-weight:400}
.col-add-btn{display:inline-flex;align-items:center;gap:.4rem;background:#E30613;color:#fff;border:none;border-radius:10px;padding:.55rem 1.15rem;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .15s;box-shadow:0 2px 8px rgba(227,6,19,.25);white-space:nowrap}
.col-add-btn:hover{background:#C50510;transform:translateY(-1px);box-shadow:0 4px 14px rgba(227,6,19,.35)}

/* Stats */
.col-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1.75rem}
@media(max-width:767.98px){.col-stats{grid-template-columns:repeat(2,1fr)}}
@media(max-width:575.98px){.col-stats{grid-template-columns:1fr 1fr;gap:.5rem}}
.col-stat{background:#fff;border:1px solid #F0F0F0;border-radius:14px;padding:1rem 1.15rem;display:flex;align-items:center;gap:.75rem;transition:transform .15s,box-shadow .15s}
.col-stat:hover{transform:translateY(-1px);box-shadow:0 2px 10px rgba(0,0,0,.05)}
.col-stat__icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem}
.col-stat__icon--total{background:#FEF2F2;color:#E30613}
.col-stat__icon--admin{background:#FEE2E2;color:#DC2626}
.col-stat--mesario{background:#EFF6FF;color:#2563EB}
.col-stat__icon--colab{background:#F3F4F6;color:#6B7280}
.col-stat__icon--org{background:#ECFDF5;color:#059669}
.col-stat__num{font-size:1.35rem;font-weight:800;color:#111827;line-height:1}
.col-stat__label{font-size:.72rem;color:#9CA3AF;font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-top:.1rem}

/* Toolbar */
.col-toolbar{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1.25rem}
.col-search{position:relative;flex:1;min-width:200px}
.col-search__input{width:100%;border:1.5px solid #E5E7EB;border-radius:10px;font-size:.85rem;color:#374151;background:#fff;padding:.6rem .85rem .6rem 2.5rem;transition:border-color .15s,box-shadow .15s}
.col-search__input:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08);outline:none}
.col-search__input::placeholder{color:#9CA3AF}
.col-search__icon{position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#9CA3AF;font-size:.9rem;pointer-events:none}
.col-filters{display:flex;gap:.4rem;flex-wrap:wrap}
.col-chip{border:1.5px solid #E5E7EB;background:#fff;color:#6B7280;border-radius:50px;padding:.35rem .85rem;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap}
.col-chip:hover{border-color:#D1D5DB;background:#F9FAFB}
.col-chip--active{background:#E30613;color:#fff;border-color:#E30613}
.col-chip--active:hover{background:#C50510;border-color:#C50510}

/* Cards */
.col-list{display:flex;flex-direction:column;gap:.65rem}
.col-card{background:#fff;border:1px solid #F0F0F0;border-radius:14px;padding:1.15rem 1.25rem;display:flex;align-items:center;gap:1rem;transition:all .2s;position:relative;overflow:hidden}
.col-card::before{content:"";position:absolute;top:0;left:0;width:4px;height:100%;border-radius:0 2px 2px 0;transition:width .2s}
.col-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.06)}
.col-card:hover::before{width:5px}
.col-card--admin::before{background:#DC2626}
.col-card--mesario::before{background:#2563EB}
.col-card--colab::before{background:#9CA3AF}
.col-card--organizador::before{background:#059669}
.col-card--coordenador::before{background:#7C3AED}

/* Avatar */
.col-avatar{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.95rem;color:#fff;flex-shrink:0;text-transform:uppercase}
.col-avatar--admin{background:linear-gradient(135deg,#FEE2E2,#FECACA);color:#DC2626}
.col-avatar--mesario{background:linear-gradient(135deg,#DBEAFE,#BFDBFE);color:#2563EB}
.col-avatar--colab{background:linear-gradient(135deg,#F3F4F6,#E5E7EB);color:#6B7280}
.col-avatar--organizador{background:linear-gradient(135deg,#D1FAE5,#A7F3D0);color:#059669}
.col-avatar--coordenador{background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED}

/* Info */
.col-info{flex:1;min-width:0}
.col-info__name{font-size:.95rem;font-weight:700;color:#111827;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.col-info__meta{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-top:.2rem}
.col-info__detail{font-size:.78rem;color:#9CA3AF;display:inline-flex;align-items:center;gap:.25rem}
.col-info__detail i{font-size:.7rem}
.col-role{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:700;border-radius:6px;padding:.2rem .55rem;letter-spacing:.02em;white-space:nowrap}
.col-role--admin{background:#FEE2E2;color:#991B1B}
.col-role--mesario{background:#DBEAFE;color:#1E40AF}
.col-role--colab{background:#F3F4F6;color:#374151}
.col-role--organizador{background:#D1FAE5;color:#065F46}
.col-role--coordenador{background:#EDE9FE;color:#5B21B6}
.col-role i{font-size:.65rem}

/* Actions */
.col-actions{display:flex;gap:.35rem;flex-shrink:0}
.col-action{width:34px;height:34px;border-radius:10px;border:1.5px solid #F0F0F0;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:all .15s;color:#6B7280;font-size:.85rem}
.col-action:hover{border-color:#E5E7EB;background:#F9FAFB}
.col-action--edit:hover{color:#2563EB;border-color:#BFDBFE;background:#EFF6FF}
.col-action--delete:hover{color:#DC2626;border-color:#FECACA;background:#FEF2F2}

/* Empty state */
.col-empty{text-align:center;padding:4rem 1.5rem;color:#9CA3AF}
.col-empty i{font-size:3rem;display:block;margin-bottom:.75rem;color:#D1D5DB}
.col-empty p{margin:0 0 1rem;font-size:.9rem}

/* Loading */
.col-loading{text-align:center;padding:3rem;color:#9CA3AF;font-size:.88rem}
.col-loading .spinner-border{width:1.5rem;height:1.5rem;border-width:.15rem}

/* Modal */
.col-modal .modal-content{border:none;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
.col-modal .modal-header{border:none;padding:1.25rem 1.5rem .5rem}
.col-modal .modal-title{font-size:1.05rem;font-weight:700}
.col-modal .modal-body{padding:.5rem 1.5rem 1.25rem}
.col-modal .form-label{font-size:.78rem;font-weight:600;color:#6B7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem}
.col-modal .form-control,.col-modal .form-select{border-radius:10px;border:1.5px solid #E5E7EB;font-size:.875rem;padding:.55rem .85rem;transition:border-color .15s,box-shadow .15s}
.col-modal .form-control:focus,.col-modal .form-select:focus{border-color:#E30613;box-shadow:0 0 0 3px rgba(227,6,19,.08)}

/* Responsive mobile */
@media(max-width:767.98px){
    .col-card{flex-wrap:wrap;gap:.75rem}
    .col-actions{width:100%;justify-content:flex-end;margin-top:.25rem}
    .col-info__meta{gap:.35rem}
}
';
include 'componentes/head.php';
include 'componentes/header.php';
$paginaAtiva = 'dashboard';
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none" style="padding-top:5.5rem;padding-bottom:6rem;">
    <div class="col-wrap">
        <a href="./dashboard.php" id="btnVoltarColabMobile" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#ed1c24;border-radius:6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsMobile">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalMob">-</div><div class="col-stat__label">Colaboradores</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminMob">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioMob">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabMob">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar" style="flex-direction:column;align-items:stretch;">
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabMob" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosMob">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresMobile">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout col-page">
    <div class="col-wrap">
        <a href="./dashboard.php" id="btnVoltarColabDesk" class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color:#ed1c24;border-radius:6px;">
            <i class="bi bi-arrow-left-circle fs-5"></i> Voltar
        </a>

        <div class="col-header">
            <div class="col-header__top">
                <div>
                    <h1 class="col-header__title">Colaboradores</h1>
                    <p class="col-header__sub">Gerencie todos os usuários responsáveis pelo interclasse.</p>
                </div>
                <button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador">
                    <i class="bi bi-plus-lg"></i> Adicionar colaborador
                </button>
            </div>
        </div>

        <div class="col-stats" id="statsDesktop">
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--total"><i class="bi bi-people-fill"></i></div><div><div class="col-stat__num" id="statTotalDesk">-</div><div class="col-stat__label">Colaboradores</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--admin"><i class="bi bi-shield-fill"></i></div><div><div class="col-stat__num" id="statAdminDesk">-</div><div class="col-stat__label">Admins</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat--mesario"><i class="bi bi-clipboard-check"></i></div><div><div class="col-stat__num" id="statMesarioDesk">-</div><div class="col-stat__label">Mesários</div></div></div>
            <div class="col-stat"><div class="col-stat__icon col-stat__icon--colab"><i class="bi bi-person"></i></div><div><div class="col-stat__num" id="statColabDesk">-</div><div class="col-stat__label">Colaboradores</div></div></div>
        </div>

        <div class="col-toolbar">
            <div class="col-search">
                <i class="bi bi-search col-search__icon"></i>
                <input type="text" class="col-search__input" id="buscaColabDesk" placeholder="Pesquisar colaborador...">
            </div>
            <div class="col-filters" id="filtrosDesk">
                <button class="col-chip col-chip--active" data-filtro="todos">Todos</button>
            </div>
        </div>

        <div class="col-list" id="listaColaboradoresDesktop">
            <div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>
        </div>
    </div>
</main>

<!-- ═══ MODAL ADICIONAR ═══ -->
<div class="modal fade col-modal" id="modalAdicionarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus text-danger me-2"></i>Adicionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="novoNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email / Matrícula</label>
                        <input type="text" class="form-control" id="novoNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="text" class="form-control" id="novaSenhaColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="novoGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoAdminColaborador">
                        <label class="form-check-label" for="novoAdminColaborador">Administrador</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoMesarioColaborador" checked>
                        <label class="form-check-label" for="novoMesarioColaborador">Mesário</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="tipoParticipante" id="novoColaborador">
                        <label class="form-check-label" for="novoColaborador">Colaborador</label>
                    </div>
                    <div id="msgNovoColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600;font-size:.85rem;">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarColaborador" style="border-radius:10px;font-weight:700;font-size:.85rem;">Cadastrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL EDITAR ═══ -->
<div class="modal fade col-modal" id="modalEditarColaborador" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square text-danger me-2"></i>Editar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarColaborador">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" id="editNomeColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Matrícula / NIF</label>
                        <input type="text" class="form-control" id="editNifColaborador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nova senha <small class="text-muted">(deixe em branco para manter)</small></label>
                        <input type="text" class="form-control" id="editSenhaColaborador">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gênero</label>
                        <select class="form-select" id="editGeneroColaborador">
                            <option value="MASC">Masculino</option>
                            <option value="FEM">Feminino</option>
                        </select>
                    </div>
                    <div id="msgEditarColaborador" class="text-center mb-2"></div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius:10px;font-weight:600;font-size:.85rem;">Cancelar</button>
                        <button type="submit" class="btn btn-danger" id="btnSalvarEdicaoColaborador" style="border-radius:10px;font-weight:700;font-size:.85rem;">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const paramsColab = new URLSearchParams(window.location.search);
    const idInterclasseColab = paramsColab.get('id');
    let colaboradoresData = [];
    let filtroNivelAtual = 'todos';
    let buscaAtual = '';

    (async () => {
        const ic = idInterclasseColab
            ? await window.SGIInterclasse.getInterclasseById(idInterclasseColab)
            : await window.SGIInterclasse.getActiveInterclasse();
        if (ic) {
            const nome = ic.nome_interclasse || 'Voltar';
            const href = `./dashboard.php?id=${ic.id_interclasse}`;
            ['btnVoltarColabMobile', 'btnVoltarColabDesk'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.href = href;
                    el.innerHTML = `<i class="bi bi-arrow-left-circle fs-5"></i> ${nome}`;
                }
            });
        }
    })();

    let editColaboradorId = null;

    function roleClass(nivel) {
        const map = { '0': 'admin', '1': 'colab', '2': 'mesario' };
        return map[String(nivel)] || 'colab';
    }

    function roleName(nivel) {
        const map = { '0': 'Administrador', '1': 'Colaborador', '2': 'Mesário' };
        return map[String(nivel)] || 'Colaborador';
    }

    function roleIcon(nivel) {
        const map = { '0': 'bi-shield-fill', '1': 'bi-person', '2': 'bi-clipboard-check' };
        return map[String(nivel)] || 'bi-person';
    }

    function avatarInitial(nome) {
        return (nome || '?').charAt(0).toUpperCase();
    }

    function cardColaborador(item) {
        const rc = roleClass(item.nivel_usuario);
        const rn = roleName(item.nivel_usuario);
        const ri = roleIcon(item.nivel_usuario);
        const nivel = String(item.nivel_usuario);

        return `
            <div class="col-card col-card--${rc}" data-id="${item.id_usuario}" data-nivel="${nivel}">
                <div class="col-avatar col-avatar--${rc}">${avatarInitial(item.nome_usuario)}</div>
                <div class="col-info">
                    <p class="col-info__name">${esc(item.nome_usuario)}</p>
                    <div class="col-info__meta">
                        <span class="col-info__detail"><i class="bi bi-hash"></i>${esc(item.matricula_usuario || '')}</span>
                        <span class="col-role col-role--${rc}"><i class="bi ${ri}"></i>${rn}</span>
                    </div>
                </div>
                <div class="col-actions">
                    <button type="button" class="col-action col-action--edit" data-editar="${item.id_usuario}" title="Editar"><i class="bi bi-pencil"></i></button>
                    ${nivel !== '0' ? `<button type="button" class="col-action col-action--delete" data-remover="${item.id_usuario}" title="Excluir"><i class="bi bi-trash"></i></button>` : ''}
                </div>
            </div>`;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function renderizarEstatisticas(lista) {
        const total = lista.length;
        const admins = lista.filter(c => String(c.nivel_usuario) === '0').length;
        const mesarios = lista.filter(c => String(c.nivel_usuario) === '2').length;
        const colabs = lista.filter(c => String(c.nivel_usuario) === '1').length;

        [['statTotalMob','statTotalDesk'], ['statAdminMob','statAdminDesk'], ['statMesarioMob','statMesarioDesk'], ['statColabMob','statColabDesk']].forEach(([mob, desk]) => {
            const m = document.getElementById(mob);
            const d = document.getElementById(desk);
            if (m) m.textContent = [total, admins, mesarios, colabs][[['statTotalMob','statTotalDesk'], ['statAdminMob','statAdminDesk'], ['statMesarioMob','statMesarioDesk'], ['statColabMob','statColabDesk']].findIndex(x => x[0] === mob)];
            if (d) d.textContent = [total, admins, mesarios, colabs][[['statTotalMob','statTotalDesk'], ['statAdminMob','statAdminDesk'], ['statMesarioMob','statMesarioDesk'], ['statColabMob','statColabDesk']].findIndex(x => x[0] === mob)];
        });
    }

    function montarFiltros(lista) {
        const niveis = [...new Set(lista.map(c => String(c.nivel_usuario)))].sort();
        const nomes = { '0': 'Administrador', '1': 'Colaborador', '2': 'Mesário' };
        ['filtrosMob', 'filtrosDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerHTML = '<button class="col-chip col-chip--active" data-filtro="todos">Todos</button>';
            niveis.forEach(n => {
                el.innerHTML += `<button class="col-chip" data-filtro="${n}">${nomes[n] || 'Nível ' + n}</button>`;
            });
            el.querySelectorAll('.col-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    filtroNivelAtual = chip.dataset.filtro;
                    el.querySelectorAll('.col-chip').forEach(c => c.classList.remove('col-chip--active'));
                    chip.classList.add('col-chip--active');
                    aplicarFiltros();
                });
            });
        });
    }

    function aplicarFiltros() {
        let lista = [...colaboradoresData];
        if (filtroNivelAtual !== 'todos') {
            lista = lista.filter(c => String(c.nivel_usuario) === filtroNivelAtual);
        }
        if (buscaAtual) {
            const b = buscaAtual.toLowerCase();
            lista = lista.filter(c =>
                (c.nome_usuario || '').toLowerCase().includes(b) ||
                (c.matricula_usuario || '').toLowerCase().includes(b) ||
                roleName(c.nivel_usuario).toLowerCase().includes(b)
            );
        }

        const html = lista.length
            ? lista.map(cardColaborador).join('')
            : '<div class="col-empty"><i class="bi bi-people"></i><p>Nenhum colaborador encontrado.</p><button class="col-add-btn" data-bs-toggle="modal" data-bs-target="#modalAdicionarColaborador"><i class="bi bi-plus-lg"></i> Adicionar colaborador</button></div>';

        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        if (desk) desk.innerHTML = html;
        if (mob) mob.innerHTML = html;
        vincularEventosLista();
    }

    async function carregarColaboradores() {
        const desk = document.getElementById('listaColaboradoresDesktop');
        const mob = document.getElementById('listaColaboradoresMobile');
        const loading = '<div class="col-loading"><div class="spinner-border text-danger me-2"></div>Carregando colaboradores...</div>';
        if (desk) desk.innerHTML = loading;
        if (mob) mob.innerHTML = loading;

        try {
            const response = await fetch('../../../api/usuarios.php?acao=listar_colaboradores');
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao listar colaboradores.');
            const lista = resultado.colaboradores || [];
            colaboradoresData = lista;
            renderizarEstatisticas(lista);
            montarFiltros(lista);
            aplicarFiltros();
        } catch (error) {
            const msg = `<div class="col-empty"><i class="bi bi-exclamation-triangle"></i><p class="text-danger">${error.message}</p></div>`;
            if (desk) desk.innerHTML = msg;
            if (mob) mob.innerHTML = msg;
        }
    }

    function vincularEventosLista() {
        document.querySelectorAll('[data-remover]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!confirm('Remover este colaborador?')) return;
                const id = btn.getAttribute('data-remover');
                const body = new URLSearchParams();
                body.append('acao', 'excluir_colaborador');
                body.append('id_usuario', id);

                const resp = await fetch('../../../api/usuarios.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });
                const json = await resp.json();
                if (json.status !== 'sucesso') {
                    alert(json.mensagem || 'Erro ao remover.');
                    return;
                }
                await carregarColaboradores();
            });
        });

        document.querySelectorAll('[data-editar]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-editar'));
                const colab = colaboradoresData.find(c => c.id_usuario === id);
                if (!colab) return;

                editColaboradorId = id;
                document.getElementById('editNomeColaborador').value = colab.nome_usuario || '';
                document.getElementById('editNifColaborador').value = colab.matricula_usuario || '';
                document.getElementById('editSenhaColaborador').value = '';
                document.getElementById('editGeneroColaborador').value = colab.genero_usuario || 'MASC';
                document.getElementById('msgEditarColaborador').innerHTML = '';

                const modal = new bootstrap.Modal(document.getElementById('modalEditarColaborador'));
                modal.show();
            });
        });
    }

    document.getElementById('buscaColabDesk').addEventListener('input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabMob').value = e.target.value;
        aplicarFiltros();
    });
    document.getElementById('buscaColabMob').addEventListener('input', (e) => {
        buscaAtual = e.target.value;
        document.getElementById('buscaColabDesk').value = e.target.value;
        aplicarFiltros();
    });

    document.getElementById('formNovoColaborador').addEventListener('submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarColaborador');
        const msg = document.getElementById('msgNovoColaborador');
        const nome = document.getElementById('novoNomeColaborador').value.trim();
        const matricula = document.getElementById('novoNifColaborador').value.trim();
        const senha = document.getElementById('novaSenhaColaborador').value.trim();
        const admin = document.getElementById('novoAdminColaborador').checked;
        const mesario = document.getElementById('novoMesarioColaborador').checked;
        const genero = document.getElementById('novoGeneroColaborador').value;

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            msg.innerHTML = '';

            const body = new URLSearchParams();
            body.append('acao', 'cadastrar_usuario');
            body.append('nome_usuario', nome);
            body.append('matricula_usuario', matricula);
            body.append('senha_usuario', senha);
            body.append('data_nasc_usuario', '2000-01-01');
            body.append('is_admin_clicado', admin ? '1' : '0');
            body.append('is_mesario_clicado', mesario ? '1' : '0');
            body.append('sigla_usuario', 'SS');
            body.append('genero_usuario', genero);

            const response = await fetch('../../../api/usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao cadastrar.');

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador cadastrado com sucesso.</p>';
            document.getElementById('formNovoColaborador').reset();
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalAdicionarColaborador')).hide(), 700);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Cadastrar';
        }
    });

    document.getElementById('formEditarColaborador').addEventListener('submit', async (event) => {
        event.preventDefault();
        const btn = document.getElementById('btnSalvarEdicaoColaborador');
        const msg = document.getElementById('msgEditarColaborador');

        const nome = document.getElementById('editNomeColaborador').value.trim();
        const matricula = document.getElementById('editNifColaborador').value.trim();
        const senha = document.getElementById('editSenhaColaborador').value.trim();
        const genero = document.getElementById('editGeneroColaborador').value;

        if (!nome || !matricula) {
            msg.innerHTML = '<p class="text-danger fw-bold mb-0">Nome e matrícula são obrigatórios.</p>';
            return;
        }

        try {
            btn.disabled = true;
            btn.innerText = 'Salvando...';
            msg.innerHTML = '';

            const body = new URLSearchParams();
            body.append('acao', 'atualizar_dados_colaborador');
            body.append('id_usuario', String(editColaboradorId));
            body.append('nome_usuario', nome);
            body.append('matricula_usuario', matricula);
            body.append('genero_usuario', genero);
            if (senha) body.append('senha_usuario', senha);

            const response = await fetch('../../../api/usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const resultado = await response.json();
            if (resultado.status !== 'sucesso') throw new Error(resultado.mensagem || 'Falha ao atualizar.');

            await carregarColaboradores();
            msg.innerHTML = '<p class="text-success fw-bold mb-0">Colaborador atualizado.</p>';
            setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('modalEditarColaborador')).hide(), 700);
        } catch (error) {
            msg.innerHTML = `<p class="text-danger fw-bold mb-0">${error.message}</p>`;
        } finally {
            btn.disabled = false;
            btn.innerText = 'Salvar';
        }
    });

    window.addEventListener('load', carregarColaboradores);
</script>

<?php
include 'componentes/nav.php';
require_once '../componentes/footer.php';
?>
