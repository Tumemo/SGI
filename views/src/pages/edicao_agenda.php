<?php
$titulo = "Agenda";
$textTop = "Agenda";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class="bg-light min-vh-100 d-md-none p-3" style="padding-top: 5rem;">
    <div class="card border-0 shadow-sm rounded-4 mx-auto mb-4" style="max-width: 450px;">
        <div class="card-body p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button id="btn-prev-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-left" style="font-size: 1.2rem;"></i>
                </button>
                
                <div class="d-flex gap-2">
                    <select id="select-mes" class="form-select form-select-sm border-0 bg-light fw-bold text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
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
                    
                    <select id="select-ano" class="form-select form-select-sm border-0 bg-light fw-bold text-center" style="border-radius: 8px; cursor: pointer; padding-right: 1.8rem; box-shadow: none;">
                        </select>
                </div>

                <button id="btn-next-mobile" class="btn btn-link text-dark p-0 text-decoration-none">
                    <i class="bi bi-chevron-right" style="font-size: 1.2rem;"></i>
                </button>
            </div>

            <div class="d-flex justify-content-between text-muted mb-2 text-center fw-bold" style="font-size: 0.85rem;">
                <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                <span style="width: 14%;">S</span>
            </div>

            <div id="calendario-grade-mobile" class="d-flex flex-wrap text-center">
                </div>
            
        </div>
    </div>

    <div id="lista-eventos-mobile" class="d-flex flex-column gap-3 mx-auto" style="max-width: 450px;">
        </div>
</main>


<main class="d-none d-md-block min-vh-100" style="margin-left: 80px; background-color: #f2f2f2; padding: 40px;">

    <button type="button" class="btn btn-danger d-flex align-items-center mb-4 border-0 shadow-sm" style="border-radius: 4px; padding: 8px 15px;">
        <i class="bi bi-arrow-left-circle-fill me-2"></i>
        <span class="fw-bold" style="font-size: 0.9rem;">Interclasse 2026</span>
    </button>

    <div class="row">
        <div class="col-lg-6 pe-lg-5">
            <h2 class="fw-bold text-dark mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-calendar3"></i> Agenda
            </h2>

            <div id="lista-eventos" class="d-flex flex-column gap-3">
                </div>
        </div>

        <div class="col-lg-6 d-flex justify-content-center align-items-start mt-5 mt-lg-0">
            <div class="bg-white shadow-sm rounded-3 overflow-hidden" style="width: 100%; max-width: 380px;">
                <div class="bg-dark text-white d-flex justify-content-between align-items-center py-3 px-4 fw-bold text-uppercase" style="letter-spacing: 3px;">
                    <i class="bi bi-chevron-left" id="btn-prev" style="cursor: pointer; font-size: 1.2rem;"></i>
                    <span id="calendario-mes"></span>
                    <i class="bi bi-chevron-right" id="btn-next" style="cursor: pointer; font-size: 1.2rem;"></i>
                </div>

                <div class="p-4">
                    <div class="d-flex justify-content-between text-dark fw-bold mb-3 text-center">
                        <span style="width: 14%;">D</span><span style="width: 14%;">S</span><span style="width: 14%;">T</span>
                        <span style="width: 14%;">Q</span><span style="width: 14%;">Q</span><span style="width: 14%;">S</span>
                        <span style="width: 14%;">S</span>
                    </div>

                    <div id="calendario-grade" class="d-flex flex-wrap text-center">
                        </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    // Memória central do calendário
    let dataNavegacao = new Date();

    document.addEventListener('DOMContentLoaded', function() {
        inicializarAnos();
        atualizarTelas(); // Desenha tanto mobile quanto desktop
        carregarAgenda(); // Busca os eventos do banco para as duas telas

        // Controles Desktop
        document.getElementById('btn-prev').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() - 1);
            atualizarTelas();
        });
        document.getElementById('btn-next').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() + 1);
            atualizarTelas();
        });

        // Controles Mobile
        document.getElementById('btn-prev-mobile').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() - 1);
            atualizarTelas();
        });
        document.getElementById('btn-next-mobile').addEventListener('click', () => {
            dataNavegacao.setMonth(dataNavegacao.getMonth() + 1);
            atualizarTelas();
        });

        // Dropdowns Mobile
        document.getElementById('select-mes').addEventListener('change', (e) => {
            dataNavegacao.setMonth(parseInt(e.target.value));
            atualizarTelas();
        });
        document.getElementById('select-ano').addEventListener('change', (e) => {
            dataNavegacao.setFullYear(parseInt(e.target.value));
            atualizarTelas();
        });
    });

    // Mantém as interfaces sincronizadas
    function atualizarTelas() {
        gerarCalendarioVisual(); // Desktop
        gerarCalendarioMobile(); // Mobile
        atualizarSelects();      // Ajusta dropdowns
    }

    function inicializarAnos() {
        const selectAno = document.getElementById('select-ano');
        const anoAtual = new Date().getFullYear();
        for (let i = anoAtual - 2; i <= anoAtual + 3; i++) {
            selectAno.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }

    function atualizarSelects() {
        document.getElementById('select-mes').value = dataNavegacao.getMonth();
        document.getElementById('select-ano').value = dataNavegacao.getFullYear();
    }

    function gerarCalendarioVisual() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date(); 

        const nomesMeses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        document.getElementById('calendario-mes').innerText = nomesMeses[mesNavegacao] + ' ' + anoNavegacao;

        const grade = document.getElementById('calendario-grade');
        grade.innerHTML = '';

        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();

        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div style="width: 14%; height: 40px;"></div>`;
        }

        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = (dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear());
            const hojeClass = isHoje ? 'fw-bold text-danger border border-danger rounded-circle' : '';

            grade.innerHTML += `
            <div class="d-flex align-items-center justify-content-center ${hojeClass}" 
                 style="width: 14%; height: 40px; cursor: pointer; font-size: 0.9rem;">
                ${dia}
            </div>`;
        }
    }

    function gerarCalendarioMobile() {
        const mesNavegacao = dataNavegacao.getMonth();
        const anoNavegacao = dataNavegacao.getFullYear();
        const hojeReal = new Date();

        const grade = document.getElementById('calendario-grade-mobile');
        grade.innerHTML = '';

        const primeiroDiaMes = new Date(anoNavegacao, mesNavegacao, 1).getDay();
        const diasNoMes = new Date(anoNavegacao, mesNavegacao + 1, 0).getDate();

        for (let i = 0; i < primeiroDiaMes; i++) {
            grade.innerHTML += `<div style="width: 14%; height: 40px;"></div>`;
        }

        for (let dia = 1; dia <= diasNoMes; dia++) {
            const isHoje = (dia === hojeReal.getDate() && mesNavegacao === hojeReal.getMonth() && anoNavegacao === hojeReal.getFullYear());
            const classeHoje = isHoje ? 'bg-dark text-white rounded-circle' : 'text-dark';
            
            grade.innerHTML += `
            <div class="d-flex align-items-center justify-content-center" style="width: 14%; height: 40px; margin-bottom: 5px;">
                <span class="${classeHoje} d-flex align-items-center justify-content-center fw-bold" style="width: 35px; height: 35px; font-size: 0.95rem;">
                    ${dia}
                </span>
            </div>`;
        }
    }

    async function carregarAgenda() {
        const containerDesk = document.getElementById('lista-eventos');
        const containerMob = document.getElementById('lista-eventos-mobile');

        try {
            const response = await fetch('api_agenda.php');
            if (!response.ok) throw new Error();

            const eventos = await response.json();
            containerDesk.innerHTML = '';
            containerMob.innerHTML = '';

            if (eventos.length === 0) {
                const msgVazia = '<p class="text-muted text-center w-100">Nenhum evento para este mês.</p>';
                containerDesk.innerHTML = msgVazia;
                containerMob.innerHTML = msgVazia;
                return;
            }

            eventos.forEach(evento => {
                const dataObj = new Date(evento.data_evento + 'T00:00:00');
                const diaNum = dataObj.toLocaleDateString('pt-BR', { day: '2-digit' });
                const diaSem = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });

                const cardHtml = `
                <div class="card bg-white border-0 shadow-sm rounded-3 p-3 position-relative w-100 mb-3">
                    <i class="bi bi-record-circle text-danger position-absolute top-0 end-0 m-3 fs-5"></i>
                    <div class="card-body p-0">
                        <h5 class="fw-bold text-dark mb-1">${evento.titulo}</h5>
                        <p class="text-muted mb-1" style="font-size: 0.8rem;">${diaNum}, ${diaSem}</p>
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">${evento.hora_inicio} - ${evento.hora_fim}</p>
                    </div>
                    <i class="bi bi-alarm text-muted position-absolute bottom-0 end-0 m-3" style="font-size: 1rem;"></i>
                </div>`;

                containerDesk.innerHTML += cardHtml;
                containerMob.innerHTML += cardHtml;
            });
        } catch (e) {
            const erroMsg = '<p class="text-muted text-center w-100">Ainda não há jogos marcados.</p>';
            containerDesk.innerHTML = erroMsg;
            containerMob.innerHTML = erroMsg;
        }
    }
</script>

<?php
require_once '../componentes/navbar.php';
?>