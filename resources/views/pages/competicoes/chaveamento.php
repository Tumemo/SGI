<?php
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');

include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'chaveamento';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$isNivel3 = $nivelUsuario === 3;
$isNivel2 = $nivelUsuario === 2;
$podeGerar = !$isNivel2 && !$isNivel3;
?>

<main class="d-md-none kv-page p-4" >
    <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarChaveamentoMob" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" >
        <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseChaveamentoMob">Interclasse</span>
    </a>
    <div class="mb-3">
        <h4 class="kv-title fs-4" >Chaveamento</h4>
    </div>

    <div class="kv-stats mb-4" >
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--modalidades"><i class="bi bi-trophy"></i></div>
            <div class="kv-stat__info">
                <div class="kv-stat__number" id="statModalidadesMob">0</div>
                <div class="kv-stat__label">Modalidades</div>
            </div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--jogos"><i class="fa-solid fa-volleyball"></i></div>
            <div class="kv-stat__info">
                <div class="kv-stat__number" id="statJogosMob">0</div>
                <div class="kv-stat__label">Jogos</div>
            </div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--campeoes"><i class="bi bi-award"></i></div>
            <div class="kv-stat__info">
                <div class="kv-stat__number" id="statCampeoesMob">0</div>
                <div class="kv-stat__label">Campeões</div>
            </div>
        </div>
        <div class="kv-stat">
            <div class="kv-stat__icon kv-stat__icon--pendentes"><i class="bi bi-hourglass-split"></i></div>
            <div class="kv-stat__info">
                <div class="kv-stat__number" id="statPendentesMob">0</div>
                <div class="kv-stat__label">Pendentes</div>
            </div>
        </div>
    </div>

    <div class="kv-gen-card">
        <div class="kv-gen-card__header">
            <div class="kv-gen-card__title"><i class="bi bi-diagram-3 me-2 text-primary" ></i><?php echo $podeGerar ? 'Gerar novo chaveamento' : 'Visualizar chaveamento'; ?></div>
            <div class="kv-gen-card__desc"><?php echo $podeGerar ? 'Selecione uma modalidade para gerar automaticamente o chaveamento.' : 'Selecione uma modalidade para visualizar a árvore do torneio.'; ?></div>
        </div>
        <div class="kv-gen-card__row">
            <div id="kvs-wrap-selectModalidadeMob" class="kvs-wrap w-100 sgi-u-min-width-0" ></div>
            <select class="kv-gen-card__select d-none" id="selectModalidadeMob">
                <option value="">Selecione uma modalidade</option>
            </select>
            <?php if ($podeGerar): ?>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="btnGerarChaveamentoMob">
                <i class="bi bi-diagram-3-fill"></i> Gerar
            </button>
            <?php endif; ?>
        </div>
        <div id="msgChaveamentoMob" class="kv-alert d-none" ></div>
    </div>

    <div id="bracketAreaMob" class="kv-empty">
        <div class="kv-empty__icon"><i class="bi bi-diagram-3"></i></div>
        <div class="kv-empty__title">Nenhum chaveamento disponível</div>
        <div class="kv-empty__desc">Selecione uma modalidade acima para <?php echo $podeGerar ? 'gerar ou ' : ''; ?>visualizar um chaveamento.</div>
    </div>

    <div id="secaoJogosMob" class="mt-4">
        <div class="kv-table-card">
            <div class="kv-table-card__header">
                <div class="kv-table-card__title">Jogos Realizados</div>
                <div class="kv-table-card__desc">Histórico de partidas concluídas.</div>
            </div>
            <div class="kv-filters flex-column" >
                <div id="kvs-wrap-filtroModalidadeJogosMob" class="kvs-wrap w-100 sgi-u-min-width-0" ></div>
                 <select class="kv-filter-select d-none w-100" id="filtroModalidadeJogosMob" >
                    <option value="">Todas modalidades</option>
                </select>
                 <select class="kv-filter-select w-100" id="filtroCategoriaJogosMob" >
                    <option value="">Todas categorias</option>
                </select>
                 <input type="text" class="kv-filter-input w-100" placeholder="Buscar partida..." id="inputBuscaJogoMob" >
            </div>
            <div class="table-responsive">
                <table class="table sgi-table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Partida</th>
                            <th>Modalidade</th>
                            <th>Data</th>
                            <th>Tempo</th>
                            <th>Acréscimos</th>
                            <th>Artilheiro/Destaque</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyJogosMob">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Carregando jogos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<main class="d-none d-md-block kv-page main-desktop-layout">

    <div class="container-fluid mw-100" >

        <div class="kv-header">
            <div class="kv-header__left">
                <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltar" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 border-0 text-decoration-none" >
                    <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseChaveamento">Interclasse</span>
                </a>
            </div>
            <div class="kv-header__right d-grid d-sm-flex">
                <?php if ($podeGerar): ?>
                <button class="btn btn-primary d-inline-flex align-items-center gap-2" id="btnGerarChaveamento">
                    <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="kv-stats">
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--modalidades"><i class="bi bi-trophy"></i></div>
                <div class="kv-stat__info">
                    <div class="kv-stat__number" id="statModalidades">0</div>
                    <div class="kv-stat__label">Modalidades</div>
                </div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--jogos"><i class="fa-solid fa-volleyball"></i></div>
                <div class="kv-stat__info">
                    <div class="kv-stat__number" id="statJogos">0</div>
                    <div class="kv-stat__label">Jogos</div>
                </div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--campeoes"><i class="bi bi-award"></i></div>
                <div class="kv-stat__info">
                    <div class="kv-stat__number" id="statCampeoes">0</div>
                    <div class="kv-stat__label">Campeões definidos</div>
                </div>
            </div>
            <div class="kv-stat">
                <div class="kv-stat__icon kv-stat__icon--pendentes"><i class="bi bi-hourglass-split"></i></div>
                <div class="kv-stat__info">
                    <div class="kv-stat__number" id="statPendentes">0</div>
                    <div class="kv-stat__label">Jogos pendentes</div>
                </div>
            </div>
        </div>

        <div class="kv-gen-card">
            <div class="kv-gen-card__header">
                <div class="kv-gen-card__title"><i class="bi bi-diagram-3 me-2 text-primary" ></i><?php echo $podeGerar ? 'Gerar novo chaveamento' : 'Visualizar chaveamento'; ?></div>
                <div class="kv-gen-card__desc"><?php echo $podeGerar ? 'Selecione uma modalidade para gerar automaticamente o chaveamento.' : 'Selecione uma modalidade para visualizar a árvore do torneio.'; ?></div>
            </div>
            <div class="kv-gen-card__row">
                <div id="kvs-wrap-selectModalidade" class="kvs-wrap"></div>
                <select class="kv-gen-card__select d-none" id="selectModalidade">
                    <option value="">Selecione uma modalidade</option>
                </select>
            </div>
            <?php if ($podeGerar): ?>
            <div class="kv-gen-card__note">⚠ Não há possibilidade de gerar um segundo chaveamento.Tome cuidado!</div>
            <?php endif; ?>
            <div id="msgChaveamento"></div>
            <div id="linkVerArvore" class="d-none mt-2" >
                <a href="#" id="btnVerArvore" class="kv-link-btn">
                    <i class="bi bi-diagram-3-fill"></i> Ver árvore do chaveamento
                </a>
            </div>
        </div>

        <div id="faseTimeline" class="kv-phase-timeline d-none"></div>

        <div id="bracketArea">
            <div class="kv-empty">
                <div class="kv-empty__icon"><i class="bi bi-diagram-3"></i></div>
                <div class="kv-empty__title">Nenhum chaveamento disponível</div>
                <div class="kv-empty__desc">Selecione uma modalidade acima para <?php echo $podeGerar ? 'gerar ou ' : ''; ?>visualizar um chaveamento.</div>
                <?php if ($podeGerar): ?>
                <button class="kv-empty__btn" onclick="kvs_focus('selectModalidade');">
                    <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div id="secaoJogos" class="mt-4">
            <div class="kv-table-card">
                <div class="kv-table-card__header">
                    <div class="kv-table-card__title">Jogos Realizados</div>
                    <div class="kv-table-card__desc">Histórico de partidas concluídas.</div>
                </div>
                <div class="kv-filters">
                    <input type="text" class="kv-filter-input" placeholder="Buscar partida..." id="inputBuscaJogo">
                    <div id="kvs-wrap-filtroModalidadeJogos" class="kvs-wrap"></div>
                    <select class="kv-filter-select d-none" id="filtroModalidadeJogos">
                        <option value="">Todas modalidades</option>
                    </select>
                    <select class="kv-filter-select" id="filtroCategoriaJogos">
                        <option value="">Todas categorias</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table sgi-table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Partida</th>
                                <th>Modalidade</th>
                                <th>Data</th>
                                <th>Tempo</th>
                                <th>Acréscimos</th>
                                <th>Artilheiro/Destaque</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyJogos">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Carregando jogos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade kv-modal" id="modalEditarJogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar Jogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formEditarJogo" onsubmit="return salvarEdicaoJogo(event)">
                <div class="modal-body">
                    <input type="hidden" id="editIdJogo">
                    <div class="modal-summary" id="editResumoPartida">
                        <div class="modal-summary__label">Partida</div>
                        <div class="modal-summary__value" id="editNomePartida">---</div>
                        <div class="modal-summary__sub" id="editModalidadePartida"></div>
                    </div>
                    <div id="editConcluidoBanner" class="edit-concluido-banner d-none" >
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Este jogo já foi <strong>finalizado</strong>. Alterar o resultado pode afetar o chaveamento.</span>
                    </div>
                    <div class="edit-modal-grid">
                        <div class="edit-modal-grid__col">
                            <div id="editTeamsSection" class="d-none">
                                <label class="form-label">Equipes e Placar</label>
                                <div id="editTeamsList"></div>
                                <div id="editWinnerSection" class="mt-3 grid gap-2 d-none" >
                                    <label class="form-label">Vencedor</label>
                                    <div id="editWinnerOptions"></div>
                                </div>
                            </div>
                        </div>
                        <div class="edit-modal-grid__col">
                            <div class="mb-3">
                                <label class="form-label">Data</label>
                                <input type="date" class="form-control" id="editDataJogo" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Início</label>
                                    <input type="time" class="form-control" id="editInicioJogo">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Término</label>
                                    <input type="time" class="form-control" id="editTerminoJogo">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Local</label>
                                <select class="form-select" id="editLocalJogo">
                                    <option value="">Selecione um local</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" id="editStatusJogo">
                                    <option value="Aguardando">Aguardando</option>
                                    <option value="Agendado">Agendado</option>
                                    <option value="Iniciado">Em Andamento</option>
                                    <option value="Concluido">Concluído</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="msgEditarJogo" class="small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarJogo">
                        <i class="bi bi-check-lg me-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="competicoes/chaveamento"><?= json_encode(['value0' => ($podeGerar), 'value3' => ($nivelUsuario)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/competicoes/chaveamento.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
?>
