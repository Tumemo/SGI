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
<main class="d-md-none ag-mobile sgi-agenda-mobile p-3">
    <div class="card overflow-hidden">
        <div class="bg-dark text-white d-flex align-items-center justify-content-between p-3">
            <button type="button" id="btn-prev-mobile" class="btn btn-sm btn-link link-light p-1" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></button>
            <div class="d-flex gap-2 align-items-center">
                <select id="select-mes" class="form-select form-select-sm border-0 bg-transparent text-white text-center w-auto small fw-bold" >
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
                <select id="select-ano" class="form-select form-select-sm border-0 bg-transparent text-white text-center w-auto small fw-bold" >
                </select>
            </div>
            <button type="button" id="btn-next-mobile" class="btn btn-sm btn-link link-light p-1" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></button>
        </div>
        <div class="p-3">
            <div class="d-flex text-center mb-1">
                <span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">D</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">T</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">Q</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">Q</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span>
            </div>
            <div id="calendario-grade-mobile" class="ag-cal-grid d-flex flex-wrap text-center"></div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 mb-4">
        <div class="input-group input-group-sm w-100" >
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="text" class="form-control" id="agenda-busca-mobile" placeholder="Buscar time ou modalidade...">
        </div>
        <select id="agenda-select-mod-mobile" class="form-select form-select-sm w-100" ></select>
        <select id="agenda-select-status-mobile" class="form-select form-select-sm w-100" >
            <option value="">Todos os status</option>
            <option value="Concluido">Concluídos</option>
            <option value="andamento">Em andamento</option>
            <option value="Agendado">Agendados</option>
        </select>
        <?php if ($nivelUsuarioAgenda <= 1): ?>
            <button type="button" class="btn btn-dark btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2 mt-1 btn-trigger-datas-auto">
                <i class="bi bi-calendar2-plus"></i> Agendar automaticamente
            </button>
        <?php endif; ?>
    </div>

    <div class="mb-3">
        <h3 class="h6 text-danger"><i class="bi bi-exclamation-circle me-1"></i>Aguardando agendamento</h3>
        <div id="lista-pendentes-mobile" class="vstack gap-3"></div>
    </div>
    <div id="lista-eventos-mobile" class="vstack gap-3"></div>
    <div class="d-flex justify-content-center mt-3 d-none" id="container-mostrar-todos-mobile" >
        <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos-mobile">
            <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
        </button>
    </div>
    <div class="d-flex justify-content-center mt-4">
        <a href="https://calendar.google.com" target="_blank" rel="noopener noreferrer" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i>Abrir no Google Calendar
        </a>
    </div>
</main>

<!-- ═══ DESKTOP ═══ -->
<main class="d-none d-md-block main-desktop-layout sgi-agenda-desktop pb-5">
    <div>

        <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
            <a href="<?= \App\Shared\Http\Url::to('painel') ?>" id="btnVoltarAgendaDesk" class="btn btn-primary d-inline-flex align-items-center gap-2 fw-bold px-3 py-2 text-decoration-none">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseAgenda">Interclasse</span>
            </a>
            <div class="flex-grow-1">
                <h2 class="h4 fw-bold text-body mb-0 d-flex align-items-center gap-2"><i class="bi bi-calendar3 text-danger"></i> Agenda de Jogos</h2>
                <p class="small text-body-secondary mt-1 mb-0">Calendário de confrontos e partidas do Interclasse</p>
            </div>
            <span class="badge rounded-pill text-bg-danger d-inline-flex align-items-center gap-2 ms-auto d-none" id="agenda-count-badge" >
                <i class="bi bi-fire"></i> <span id="agenda-count-text">0 jogos</span>
            </span>
        </div>

        <div class="d-flex gap-2 align-items-center flex-wrap mb-4">
            <div class="input-group input-group-sm flex-grow-1">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="text" class="form-control" id="agenda-busca" placeholder="Buscar time ou modalidade...">
            </div>
            <select id="agenda-select-mod" class="form-select form-select-sm w-auto"></select>
            <select id="agenda-select-status" class="form-select form-select-sm w-auto">
                <option value="">Todos os status</option>
                <option value="Concluido">Concluídos</option>
                <option value="andamento">Em andamento</option>
                <option value="Agendado">Agendados</option>
            </select>
            <?php if ($nivelUsuarioAgenda <= 1): ?>
                <button type="button" class="btn btn-dark btn-sm d-inline-flex align-items-center gap-2 ms-auto btn-trigger-datas-auto">
                    <i class="bi bi-calendar2-plus"></i> Agendar automaticamente
                </button>
            <?php endif; ?>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="mb-3">
                    <h3 class="h6 text-danger"><i class="bi bi-exclamation-circle me-1"></i>Aguardando agendamento</h3>
                    <div id="lista-pendentes" class="vstack gap-3"></div>
                </div>
                <div id="lista-eventos" class="vstack gap-3"></div>
                <div class="d-flex justify-content-center mt-3 d-none" id="container-mostrar-todos" >
                    <button type="button" class="btn btn-outline-secondary" id="btn-mostrar-todos">
                        <i class="bi bi-calendar3 me-1"></i>Mostrar Todos os Jogos
                    </button>
                </div>
            </div>

            <div class="col-12 col-xl-4 ag-cal-sticky">
                <div class="card overflow-hidden">
                    <div class="bg-dark text-white d-flex align-items-center justify-content-between p-3">
                        <button type="button" id="btn-prev" class="btn btn-sm btn-link link-light p-1" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></button>
                        <span id="calendario-mes" class="small fw-bold text-uppercase"></span>
                        <button type="button" id="btn-next" class="btn btn-sm btn-link link-light p-1" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="p-3">
                        <div class="d-flex text-center mb-1">
                            <span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">D</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">T</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">Q</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">Q</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span><span class="flex-fill small fw-bold text-body-secondary text-uppercase py-1">S</span>
                        </div>
                        <div id="calendario-grade" class="ag-cal-grid d-flex flex-wrap text-center"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- ═══ MODAL EDITAR JOGO INDIVIDUAL ═══ -->
<div class="modal fade" id="modalEditarJogoAgenda" tabindex="-1" aria-hidden="true">
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
                <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold small" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-primary rounded-3 fw-semibold small" id="edit-jogo-salvar" >Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ MODAL DATAS AUTOMÁTICAS (LOTE) ═══ -->
<div class="modal fade" id="modalDatasAutomaticas" tabindex="-1" aria-hidden="true">
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
                <button type="button" class="btn btn-outline-secondary rounded-3 fw-semibold small" data-bs-dismiss="modal" >Cancelar</button>
                <button type="button" class="btn btn-outline-danger rounded-3 fw-semibold small" id="seq-simular-btn"><i class="bi bi-eye me-1"></i>Calcular prévia</button>
                <button type="button" class="btn btn-primary rounded-3 fw-semibold small" id="seq-salvar-btn" disabled><i class="bi bi-check-lg me-1"></i>Confirmar agenda</button>
            </div>
        </div>
    </div>
</div>

<script type="application/json" data-sgi-config="eventos/configurar-agenda"><?= json_encode(['value2' => ($nivelUsuarioAgenda)], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script data-sgi-page src="<?= \App\Shared\Http\Assets::url('js/pages/eventos/configurar-agenda.js') ?>"></script>

<?php
include SGI_ROOT . '/resources/views/components/admin-nav.php';
require_once SGI_ROOT . '/resources/views/components/footer.php';
