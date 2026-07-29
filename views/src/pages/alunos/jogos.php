<?php
(session_status() === PHP_SESSION_NONE) && session_start();
require_once '../../../../config/db.php';

$id_usuario = (int)($_SESSION['id'] ?? 0);

$tituloPagina = 'SGI - Jogos';
$titulo = 'Tabela de Jogos';
$mostrarVoltar = true;
$mostrarSino = true;
$urlVoltar = './home.php';

include 'componentes/head.php';
?>

<style>
    /* Reaproveitando o Design System do SGI */
    :root {
        --aluno-primary: #e30613;
        --aluno-surface: #ffffff;
        --aluno-border: #e9ecef;
        --aluno-text: #1a1a2e;
        --aluno-text-muted: #adb5bd;
        --aluno-radius-lg: 16px;
        --aluno-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .filtro-jogos {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }

    .filtro-btn {
        padding: 0.4rem 1.2rem;
        border-radius: 50px;
        border: 1.5px solid var(--aluno-border);
        background: var(--aluno-surface);
        color: var(--aluno-text);
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all var(--aluno-transition);
        white-space: nowrap;
    }

    .filtro-btn.active {
        background: var(--aluno-primary);
        border-color: var(--aluno-primary);
        color: #fff;
    }

    .jogo-card {
        background: var(--aluno-surface);
        border-radius: var(--aluno-radius-lg);
        border: 1px solid var(--aluno-border);
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all var(--aluno-transition);
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .jogo-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
    }

    .jogo-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: var(--aluno-text-muted);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-agendado { background: #e9ecef; color: #495057; }
    .status-andamento { background: #fff3cd; color: #856404; animation: pulse 2s infinite; }
    .status-finalizado { background: #d1e7dd; color: #0f5132; }

    .confronto-area {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .equipe {
        flex: 1;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .equipe-nome {
        font-weight: 600;
        color: var(--aluno-text);
        font-size: 1rem;
    }

    .placar-box {
        background: #f8f9fa;
        border: 1px solid var(--aluno-border);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--aluno-primary);
        min-width: 80px;
        text-align: center;
    }

    .vs-text {
        font-size: 0.9rem;
        color: var(--aluno-text-muted);
        font-weight: 600;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
</style>

<main class="container py-4">
    
    <div class="d-flex flex-column gap-3 mb-4">
        <h2 class="fs-5 fw-bold text-dark m-0"><i class="bi bi-calendar-event text-danger me-2"></i>Cronograma de Jogos</h2>
        <p class="text-muted small m-0">Acompanhe as datas e os resultados das partidas.</p>
    </div>

    <!-- Filtros de Status -->
    <div class="filtro-jogos mb-4">
        <button class="filtro-btn active" data-filter="all">Todos</button>
        <button class="filtro-btn" data-filter="agendado">Próximos Jogos</button>
        <button class="filtro-btn" data-filter="finalizado">Resultados</button>
    </div>

    <!-- Container dos Jogos -->
    <div id="listaJogos" class="d-flex flex-column gap-2">
        <div class="text-center text-muted py-5">
            <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
            Carregando tabela de jogos...
        </div>
    </div>

</main>

<?php
$paginaAtiva = 'jogos';
include 'componentes/nav.php';
?>

<script>
    let todosOsJogos = [];

    // Função de segurança para escapar HTML
    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    // Função para traduzir os códigos "MM:4:0:B" para nomes reais
    function traduzirNomeJogo(codigo) {
        if (!codigo || typeof codigo !== 'string') return 'Partida';
        
        // Se não for um código automático do Mata-Mata, exibe como está no banco
        if (!codigo.startsWith('MM:')) return codigo;
        
        const partes = codigo.split(':');
        const fase = partes[1]; // Quantidade de jogos na fase
        const indexJogo = parseInt(partes[2] || '0') + 1; 
        
        let nomeFase = 'Eliminatórias';
        
        if (fase === '16') nomeFase = '16-avos de Final';
        else if (fase === '8') nomeFase = 'Oitavas de Final';
        else if (fase === '4') nomeFase = 'Quartas de Final';
        else if (fase === '2') nomeFase = 'Semifinal';
        else if (fase === '1') {
            // No mata-mata, se a fase for 1 e o index do jogo for 1, costuma ser disputa de 3º lugar
            return partes[2] === '1' ? 'Disputa de 3º Lugar' : 'Final';
        }
        
        return `${nomeFase} (Jogo ${indexJogo})`;
    }

    async function inicializarJogos() {
        const container = document.getElementById('listaJogos');
        
        try {
            // 1. Descobrir o Interclasse Ativo
            const resInter = await fetch('../../../../api/interclasse.php?regulamento=true');
            const dataInter = await resInter.json();
            const listaInter = Array.isArray(dataInter) ? dataInter : [dataInter];
            const ativo = listaInter.find(i => String(i.status_interclasse) === '1');

            if (!ativo) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-calendar-x fs-1 d-block mb-2"></i>Nenhuma competição ativa no momento.</div>';
                return;
            }

            // 2. Buscar as partidas da API 
            const resJogos = await fetch(`../../../../api/partidas.php?id_interclasse=${ativo.id_interclasse}`);
            
            if (!resJogos.ok) throw new Error('Erro ao buscar partidas');
            
            const rawData = await resJogos.json();

            // 3. AGRUPAR OS DADOS: A API retorna uma linha por time, então agrupamos pelo "id_jogo"
            const jogosAgrupados = {};
            
            rawData.forEach(row => {
                if (!jogosAgrupados[row.id_jogo]) {
                    jogosAgrupados[row.id_jogo] = {
                        id_jogo: row.id_jogo,
                        // Aqui aplicamos o tradutor que limpa o MM:4:0:B
                        nome_jogo: traduzirNomeJogo(row.nome_jogo),
                        status_jogo: row.status_jogo,
                        nome_modalidade: row.nome_modalidade,
                        equipes: []
                    };
                }
                jogosAgrupados[row.id_jogo].equipes.push({
                    nome: row.nome_fantasia_turma || row.nome_turma || 'Time Desconhecido',
                    placar: row.resultado_partida
                });
            });

            todosOsJogos = Object.values(jogosAgrupados);

            if(todosOsJogos.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>Nenhum jogo agendado ainda.</div>';
                return;
            }

            renderizarJogos('all');

        } catch (error) {
            console.error("Erro ao carregar jogos:", error);
            container.innerHTML = `
                <div class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                    Erro ao carregar a tabela de jogos. Tente novamente mais tarde.
                </div>`;
        }
    }

    function renderizarJogos(filtro) {
        const container = document.getElementById('listaJogos');
        
        let jogosFiltrados = todosOsJogos;
        
        if (filtro === 'agendado') {
            jogosFiltrados = todosOsJogos.filter(j => String(j.status_jogo).toLowerCase() !== 'concluido');
        } else if (filtro === 'finalizado') {
            jogosFiltrados = todosOsJogos.filter(j => String(j.status_jogo).toLowerCase() === 'concluido');
        }

        if (jogosFiltrados.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-5">Nenhum jogo encontrado para este filtro.</div>';
            return;
        }

        container.innerHTML = jogosFiltrados.map(jogo => {
            const isFinalizado = String(jogo.status_jogo).toLowerCase() === 'concluido';
            
            let badgeClass = isFinalizado ? 'status-finalizado' : 'status-agendado';
            let badgeText = isFinalizado ? 'Finalizado' : (jogo.status_jogo || 'Agendado');

            const dataFormatada = 'Acompanhe na Quadra';

            const eqA = jogo.equipes[0] || { nome: 'A Definir', placar: '-' };
            const eqB = jogo.equipes[1] || { nome: 'A Definir', placar: '-' };

            const placarA = isFinalizado ? (eqA.placar ?? '0') : '-';
            const placarB = isFinalizado ? (eqB.placar ?? '0') : '-';

            return `
                <div class="jogo-card">
                    <div class="jogo-header">
                        <span><i class="bi bi-clock me-1"></i> ${dataFormatada}</span>
                        <span class="status-badge ${badgeClass}">${badgeText}</span>
                    </div>
                    
                    <div class="text-center small text-muted mb-3 fw-bold">
                        ${esc(jogo.nome_modalidade)} - ${esc(jogo.nome_jogo)}
                    </div>

                    <div class="confronto-area">
                        <div class="equipe">
                            <i class="bi bi-shield-fill fs-3 text-secondary"></i>
                            <span class="equipe-nome">${esc(eqA.nome)}</span>
                        </div>
                        
                        <div class="d-flex flex-column align-items-center">
                            <div class="placar-box">
                                ${esc(placarA)} <span class="vs-text mx-1">x</span> ${esc(placarB)}
                            </div>
                        </div>

                        <div class="equipe">
                            <i class="bi bi-shield-fill fs-3 text-secondary"></i>
                            <span class="equipe-nome">${esc(eqB.nome)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
            e.target.classList.add('active');
            renderizarJogos(e.target.dataset.filter);
        });
    });

    document.addEventListener('DOMContentLoaded', inicializarJogos);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>