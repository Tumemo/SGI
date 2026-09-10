<?php
$titulo = 'Agenda';
$mostrarVoltar = true;
$urlVoltar = \App\Shared\Http\Url::to('painel');
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
                <select id="select-mes" class="form-select form-select-sm border-0 text-white text-center sgi-u-w-auto-text-82rem-weight-700" >
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
                <select id="select-ano" class="form-select form-select-sm border-0 text-white text-center sgi-u-w-auto-text-82rem-weight-700" >
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
        <div class="ag-search sgi-u-maxw-260px" >
            <i class="bi bi-search"></i>
            <input type="text" id="agenda-busca-mobile" placeholder="Buscar time ou modalidade...">
        </div>
        <select id="agenda-select-mod-mobile" class="form-select form-select-sm sgi-u-maxw-260px" ></select>
        <select id="agenda-select-status-mobile" class="form-select form-select-sm sgi-u-maxw-260px" >
            <option value="">Todos os status</option>
            <option value="Concluido">Concluídos</option>
            <option value="andamento">Em andamento</option>
            <option value="Agendado">Agendados</option>
        </select>
        <?php if ($nivelUsuarioAgenda <= 1): ?>
            <button type="button" class="ag-btn-auto w-100 justify-content-center mt-1 btn-trigger-datas-auto">
                <i class="bi bi-calendar2-plus"></i> Agendar automaticamente
            </button>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <h3 class="h6 text-danger"><i class="bi bi-exclamation-circle me-1"></i>Aguardando agendamento</h3>
        <div id="lista-pendentes-mobile" class="ag-event-list"></div>
    </div>
    <div id="lista-eventos-mobile" class="ag-event-list"></div>
    <div class="ag-show-all d-none" id="container-mostrar-todos-mobile" >
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
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarAgendaDesk" class="ag-btn-interclasse">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseAgenda">Interclasse</span>
            </a>
            <div class="ag-header__text">
                <h2><i class="bi bi-calendar3"></i> Agenda de Jogos</h2>
                <p>Calendário de confrontos e partidas do Interclasse</p>
            </div>
            <span class="ag-badge-count d-none" id="agenda-count-badge" >
                <i class="bi bi-fire"></i> <span id="agenda-count-text">0 jogos</span>
            </span>
        </div>

        <div class="ag-filter-bar">
            <div class="ag-search">
                <i class="bi bi-search"></i>
                <input type="text" id="agenda-busca" placeholder="Buscar time ou modalidade...">
            </div>
            <select id="agenda-select-mod" class="sgi-u-maxw-280px"></select>
            <select id="agenda-select-status">
                <option value="">Todos os status</option>
                <option value="Concluido">Concluídos</option>
                <option value="andamento">Em andamento</option>
                <option value="Agendado">Agendados</option>
            </select>
            <?php if ($nivelUsuarioAgenda <= 1): ?>
                <button type="button" class="ag-btn-auto ms-auto btn-trigger-datas-auto">
                    <i class="bi bi-calendar2-plus"></i> Agendar automaticamente
                </button>
            <?php endif; ?>
        </div>

        <div class="ag-desktop-grid">
            <div>
                <div class="mb-3">
                    <h3 class="h6 text-danger"><i class="bi bi-exclamation-circle me-1"></i>Aguardando agendamento</h3>
                    <div id="lista-pendentes" class="ag-event-list"></div>
                </div>
                <div id="lista-eventos" class="ag-event-list"></div>
                <div class="ag-show-all d-none" id="container-mostrar-todos" >
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
                <button type="button" class="btn btn-outline-secondary sgi-u-radius-10px-weight-600-text-85rem" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-primary sgi-u-radius-10px-weight-600-text-85rem" id="edit-jogo-salvar" >Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL DATAS AUTOMÁTICAS (LOTE) ═══ -->
<div class="modal fade ag-modal" id="modalDatasAutomaticas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-calendar2-plus text-danger me-2"></i>Agendamento automático</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">Defina o primeiro jogo. O sistema agenda a chave na ordem correta, usando terça-feira e depois quinta-feira, sem ultrapassar 11h30. Se ainda houver jogos, o próximo dia será solicitado automaticamente.</p>
                <div class="mb-3">
                    <label class="form-label">Modalidade</label>
                    <select class="form-select" id="auto-modalidade"></select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Primeiro dia (terça-feira)</label>
                        <input type="date" class="form-control" id="seq-data">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Horário do primeiro jogo</label>
                        <input type="time" class="form-control" id="seq-inicio" value="08:00">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Limite para terminar os jogos</label>
                        <input type="time" class="form-control" id="seq-fim" value="11:30">
                        <div class="form-text">Valor inicial: 11h30. Nenhum jogo ultrapassará este horário.</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Local</label>
                        <select class="form-select" id="seq-local"></select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Duração média de cada jogo (minutos)</label>
                    <input type="number" class="form-control" id="seq-duracao" min="1" step="1" value="60">
                    <div class="form-text">O intervalo entre jogos será fixado em 10 minutos.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prévia</label>
                    <div id="seq-previa" class="small border rounded p-2 bg-light">Preencha os dados e clique em “Calcular prévia”.</div>
                </div>
                <div id="seq-proximo-dia" class="border rounded p-2 mb-2 d-none">
                    <div class="fw-semibold mb-2">Ainda há jogos. Informe a próxima sessão:</div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Próximo dia</label>
                            <input type="date" class="form-control" id="seq-proxima-data">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Horário inicial</label>
                            <input type="time" class="form-control" id="seq-proxima-inicio" value="08:00">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Limite</label>
                            <input type="time" class="form-control" id="seq-proxima-fim" value="11:30">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="seq-adicionar-dia"><i class="bi bi-calendar-plus me-1"></i>Adicionar dia e recalcular</button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary sgi-u-radius-10px-weight-600-text-85rem" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-outline-danger sgi-u-radius-10px-weight-600-text-85rem" id="seq-simular-btn"><i class="bi bi-eye me-1"></i>Calcular prévia</button>
                <button type="button" class="btn btn-primary sgi-u-radius-10px-weight-600-text-85rem" id="seq-salvar-btn" disabled><i class="bi bi-check-lg me-1"></i>Confirmar agenda</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="eventos/configurar-agenda"><?= json_encode(['value2' => ($nivelUsuarioAgenda)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-agenda.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
