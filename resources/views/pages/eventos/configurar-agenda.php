<?php
$tituloPagina = 'SGI - Agenda';
$titulo = 'Agenda';
$mostrarVoltar = true;
$urlVoltar = './dashboard.php';
include SGI_ROOT . '/resources/views/components/admin-head.php';
include SGI_ROOT . '/resources/views/components/admin-header.php';
$paginaAtiva = 'agenda';
$nivelUsuarioAgenda = (int)($_SESSION['nivel'] ?? -1);
?>

<!-- ═══ MOBILE ═══ -->
<main class="d-md-none ag-mobile p-3">
    <div class="ag-cal-card">
        <div class="ag-cal-header">
            <button type="button" id="btn-prev-mobile" class="ag-cal-nav"><i class="bi bi-chevron-left"></i></button>
            <div class="d-flex gap-2 align-items-center">
                <select id="select-mes" class="form-select form-select-sm border-0 text-white text-center sgi-inline-6eb73c6d" >
                    <option value="0">Jan</option>
                    <option value="1">Fev</option>
                    <option value="2">Mar</option>
                    <option value="3">Abr</option>
                    <option value="4">Mai</option>
                    <option value="5">Jun</option>
                    <option value="6">Jul</option>
                    <option value="7">Ago</option>
                    <option value="8">Set</option>
                    <option value="9">Out</option>
                    <option value="10">Nov</option>
                    <option value="11">Dez</option>
                </select>
                <select id="select-ano" class="form-select form-select-sm border-0 text-white text-center sgi-inline-6eb73c6d" >
                </select>
            </div>
            <button type="button" id="btn-next-mobile" class="ag-cal-nav"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="ag-cal-body">
            <div class="ag-cal-weekdays">
                <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
            </div>
            <div id="calendario-grade-mobile" class="ag-cal-grid"></div>
        </div>
    </div>

    <div class="ag-filter-bar justify-content-center">
        <div class="ag-search sgi-inline-4273f5af" >
            <i class="bi bi-search"></i>
            <input type="text" id="agenda-busca-mobile" placeholder="Buscar time ou modalidade...">
        </div>
        <select id="agenda-select-mod-mobile" class="form-select form-select-sm sgi-inline-4273f5af" ></select>
        <select id="agenda-select-status-mobile" class="form-select form-select-sm sgi-inline-4273f5af" >
            <option value="">Todos os status</option>
            <option value="Concluido">Concluídos</option>
            <option value="andamento">Em andamento</option>
            <option value="Agendado">Agendados</option>
        </select>
        <?php if ($nivelUsuarioAgenda <= 1): ?>
            <button type="button" class="ag-btn-auto w-100 justify-content-center mt-1 btn-trigger-datas-auto">
                <i class="bi bi-magic"></i> Datas Automáticas
            </button>
        <?php endif; ?>
    </div>

    <div id="lista-eventos-mobile" class="ag-event-list"></div>
    <div class="ag-show-all sgi-inline-7830d708" id="container-mostrar-todos-mobile" >
        <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos-mobile">
            <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
        </button>
    </div>
    <div class="ag-gcal">
        <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir no Google Calendar
        </a>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout ag-page">
    <div class="ag-desktop-layout">

        <div class="ag-header-row">
            <a href="./dashboard.php" id="btnVoltarAgendaDesk" class="ag-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseAgenda">Interclasse</span>
            </a>
            <div class="ag-header__text">
                <h2><i class="bi bi-calendar3"></i> Agenda de Jogos</h2>
                <p>Calendário de confrontos e partidas do Interclasse</p>
            </div>
            <span class="ag-badge-count sgi-inline-7830d708" id="agenda-count-badge" >
                <i class="bi bi-fire"></i> <span id="agenda-count-text">0 jogos</span>
            </span>
        </div>

        <div class="ag-filter-bar">
            <div class="ag-search">
                <i class="bi bi-search"></i>
                <input type="text" id="agenda-busca" placeholder="Buscar time ou modalidade...">
            </div>
            <select id="agenda-select-mod" class="sgi-inline-5c041da1"></select>
            <select id="agenda-select-status">
                <option value="">Todos os status</option>
                <option value="Concluido">Concluídos</option>
                <option value="andamento">Em andamento</option>
                <option value="Agendado">Agendados</option>
            </select>
            <?php if ($nivelUsuarioAgenda <= 1): ?>
                <button type="button" class="ag-btn-auto ms-auto btn-trigger-datas-auto">
                    <i class="bi bi-magic"></i> Datas Automáticas
                </button>
            <?php endif; ?>
        </div>

        <div class="ag-desktop-grid">
            <div>
                <div id="lista-eventos" class="ag-event-list"></div>
                <div class="ag-show-all sgi-inline-7830d708" id="container-mostrar-todos" >
                    <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos">
                        <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
                    </button>
                </div>
            </div>

            <div class="ag-cal-sticky">
                <div class="ag-cal-card">
                    <div class="ag-cal-header">
                        <button type="button" id="btn-prev" class="ag-cal-nav"><i class="bi bi-chevron-left"></i></button>
                        <span id="calendario-mes"></span>
                        <button type="button" id="btn-next" class="ag-cal-nav"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="ag-cal-body">
                        <div class="ag-cal-weekdays">
                            <span>D</span><span>S</span><span>T</span><span>Q</span><span>Q</span><span>S</span><span>S</span>
                        </div>
                        <div id="calendario-grade" class="ag-cal-grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ═══ MODAL EDITAR JOGO INDIVIDUAL ═══ -->
<div class="modal fade ag-modal" id="modalEditarJogoAgenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajustar data, horário e local</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3" id="edit-jogo-titulo"></p>
                <div class="mb-3">
                    <label class="form-label">Data do jogo</label>
                    <input type="date" class="form-control" id="edit-jogo-data">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Início</label>
                        <input type="time" class="form-control" id="edit-jogo-inicio">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Término</label>
                        <input type="time" class="form-control" id="edit-jogo-fim">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Local</label>
                    <select class="form-select" id="edit-jogo-local"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary sgi-inline-2add2726" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-danger sgi-inline-2add2726" id="edit-jogo-salvar" >Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL DATAS AUTOMÁTICAS (LOTE) ═══ -->
<div class="modal fade ag-modal" id="modalDatasAutomaticas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-magic text-danger me-2"></i>Agendamento Automático</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Defina a data e o horário inicial. O sistema agendará em sequência todos os jogos da modalidade de acordo com a ordem do chaveamento.</p>
                <div class="mb-3">
                    <label class="form-label">Modalidade</label>
                    <select class="form-select" id="auto-modalidade"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Data dos jogos</label>
                    <input type="date" class="form-control" id="auto-data">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Horário de Início (1º Jogo)</label>
                        <input type="time" class="form-control" id="auto-inicio" value="08:00">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Duração/Jogo (Minutos)</label>
                        <input type="number" class="form-control" id="auto-duracao" min="5" step="5" value="60">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Local das Partidas</label>
                    <select class="form-select" id="auto-local"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary sgi-inline-2add2726" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-danger sgi-inline-2add2726" id="auto-salvar-btn" >
                    <i class="bi bi-check-lg me-1"></i>Gerar e Aplicar Datas
                </button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="eventos/configurar-agenda"><?= json_encode(['value2' => ($nivelUsuarioAgenda)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-agenda.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
