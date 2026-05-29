<?php
$titulo = "Chaveamentos";
$textTop = "Chaveamentos";
$btnVoltar = true;


require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>


<style>
    body {
        background: #f4f6f9;
    }


    .section-title {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1f2937;
    }


    .card-custom {
        background: white;
        border: none;
        border-radius: 22px;
        box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }


    .pontuacao-card {
        transition: .2s ease;
        min-height: 180px;
    }


    .pontuacao-card:hover {
        transform: translateY(-4px);
    }


    .pontuacao-badge {
        font-size: .78rem;
        padding: 8px 14px;
        border-radius: 5px;
        color: white;
        font-weight: 700;
    }


    .pontuacao-numero {
        font-size: 3rem;
        font-weight: 700;
        color: #111827;
    }


    .btn-pontos {
        border: none;
        background: transparent;
        font-size: 1.5rem;
        color: #6b7280;
    }


    .btn-pontos:hover {
        color: #dc2626;
    }


    .filtro-box {
        background: white;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 6px 18px rgba(0,0,0,.05);
    }


    .table-custom thead {
        background: #f8fafc;
    }


    .table-custom th {
        color: #6b7280;
        font-size: .9rem;
        font-weight: 600;
    }


    .status {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: .8rem;
        font-weight: 600;
    }


    .status-success {
        background: #dcfce7;
        color: #166534;
    }


    .btn-gerar {
        background: #E30613;
        border: none;
        padding: 12px 24px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
    }


    .btn-gerar:hover {
        background: #bb0812;
    }


    .search-input {
        border-radius: 12px;
        padding: 10px 14px;
    }
</style>


<main class="main-desktop-layout py-4">


    <div class="container-fluid" style="max-width: 92%;">


        <div class="mb-5">
            <a href="#"
               class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 shadow-sm text-decoration-none rounded-3">
                <i class="bi bi-arrow-left-circle"></i>
                Interclasse 2025
            </a>


            <h2 class="section-title d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3"></i>
                Gerenciamento de Chaveamentos
            </h2>


            <p class="text-muted mb-0">
                Configure pontuações, filtre modalidades e acompanhe os jogos.
            </p>
        </div>


        <div class="row g-4 mb-5">


            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #e2b714;">


                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-trophy fs-4" style="color:#e2b714;"></i>


                        <span class="pontuacao-badge" style="background:#e2b714;">
                            1º LUGAR
                        </span>
                    </div>


                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>


                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', -1)">
                                <i class="bi bi-dash"></i>
                            </button>


                            <span class="pontuacao-numero" id="pontos-1">
                                10
                            </span>


                            <button class="btn-pontos" onclick="alterarPontos('pontos-1', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>


                </div>
            </div>


            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #9ca3af;">


                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award fs-4" style="color:#9ca3af;"></i>


                        <span class="pontuacao-badge" style="background:#9ca3af;">
                            2º LUGAR
                        </span>
                    </div>


                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>


                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', -1)">
                                <i class="bi bi-dash"></i>
                            </button>


                            <span class="pontuacao-numero" id="pontos-2">
                                7
                            </span>


                            <button class="btn-pontos" onclick="alterarPontos('pontos-2', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>


                </div>
            </div>


            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #b87333;">


                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-award-fill fs-4" style="color:#b87333;"></i>


                        <span class="pontuacao-badge" style="background:#b87333;">
                            3º LUGAR
                        </span>
                    </div>


                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            PONTOS
                        </small>


                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', -1)">
                                <i class="bi bi-dash"></i>
                            </button>


                            <span class="pontuacao-numero" id="pontos-3">
                                5
                            </span>


                            <button class="btn-pontos" onclick="alterarPontos('pontos-3', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>


                </div>
            </div>


            <div class="col-12 col-md-6 col-xl-3">
                <div class="card-custom pontuacao-card p-4 d-flex flex-column justify-content-between"
                     style="border-bottom: 6px solid #dc2626;">


                    <div class="d-flex justify-content-between align-items-center">
                        <i class="bi bi-heart-fill fs-4 text-danger"></i>


                        <span class="pontuacao-badge bg-danger">
                            ARRECADAÇÃO
                        </span>
                    </div>


                    <div class="text-center">
                        <small class="text-muted fw-semibold">
                            MULTIPLICADOR
                        </small>


                        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', -1)">
                                <i class="bi bi-dash"></i>
                            </button>


                            <span class="pontuacao-numero" id="pontos-arr">
                                2
                            </span>


                            <button class="btn-pontos" onclick="alterarPontos('pontos-arr', 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>


                </div>
            </div>


        </div>


        <div class="filtro-box mb-4">


            <div class="row g-3 align-items-end">


                <div class="col-lg-3">
                    <label class="form-label fw-semibold">
                        Modalidade
                    </label>


                    <select class="form-select">
                        <option>Todas as modalidades</option>


                        <?php foreach ($modalidades as $mod): ?>
                            <option value="<?= $mod['id']; ?>">
                                <?= htmlspecialchars($mod['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>            


                 


                <div class="col-lg-3 d-grid">
                    <button class="btn-gerar">
                        <i class="bi bi-diagram-3-fill me-2"></i>
                        Gerar Chaveamento
                    </button>
                </div>


            </div>


        </div>




           
<div class="card card-custom">
    <div class="card-body">


        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold">Jogos Realizados</h4>
                <span class="text-muted">Histórico de partidas concluídas.</span>
            </div>
            <input type="text" class="form-control" placeholder="Buscar partida..." style="width:300px">
        </div>


        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Partida</th>
                        <th>Data Início</th>
                        <th>Data Término</th>
                        <th>Placar</th>
                        <th>Vencedor</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($jogos) && count($jogos) > 0): ?>
                        <?php foreach ($jogos as $jogo): ?>
                            <tr>
                                <td><?= htmlspecialchars($jogo['partida']); ?></td>
                                <td><?= !empty($jogo['data_inicio']) ? date('d/m/Y H:i', strtotime($jogo['data_inicio'])) : '---'; ?></td>
                                <td><?= !empty($jogo['data_termino']) ? date('d/m/Y H:i', strtotime($jogo['data_termino'])) : '---'; ?></td>
                                <td><?= htmlspecialchars($jogo['placar']); ?></td>
                                <td><?= htmlspecialchars($jogo['vencedor']); ?></td>
                                <td>
                                    <span class="status status-success">
                                        <?= htmlspecialchars($jogo['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-link btn-edit p-0" title="Editar Partida" data-id="<?= (int)$jogo['id']; ?>">
                                        <i class="fa-solid fa-pen-to-square fa-lg"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Nenhum jogo realizado encontrado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


    </div>
</div>


</main>


<script>
    function alterarPontos(idElemento, valor) {
        const elemento = document.getElementById(idElemento);


        let atual = parseInt(elemento.innerText);


        if (atual + valor >= 0) {
            elemento.innerText = atual + valor;
        }
    }
</script>


<?php
require_once '../componentes/footer.php';
?>
