<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Placar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../styles/style.css">
</head>
<body>

<main class="container py-4">
    <div class="d-flex flex-column align-items-start gap-3">
        <a href="./edicao_agenda.php" class="badge-interclasse" data-back-link="true">
            <i class="bi bi-arrow-left-circle-fill"></i>
            Voltar
        </a>
        <div class="d-flex align-items-center gap-2 text-secondary fw-bold">
            <i class="bi bi-record-circle"></i>
            <span>Placar</span>
        </div>
    </div>

    <div class="placar-container">

        <div class="time-card">
            <h3 class="fw-bold">3º Médio</h3>
            <div class="score-display">
                <button class="btn-score">-</button>
                <span class="score-number">02</span>
                <button class="btn-score" style="background: var(--inter-red); color: white;">+</button>
            </div>

            <div class="registro-geral">
                <label class="fw-bold small mb-2 d-block">Registro geral</label>
                <select class="form-select form-select-sm mb-2">
                    <option selected>Artilheiro</option>
                </select>
                <select class="form-select form-select-sm mb-3">
                    <option selected>Aluno</option>
                </select>
                <button class="btn btn-danger w-100 btn-sm fw-bold" style="background-color: var(--inter-red);">Salvar</button>
            </div>
        </div>

        <div class="text-center">
            <div class="timer-display">12:44</div>
            <div class="timer-controls align-items-center">
                <button class="btn-timer-secondary"><i class="bi bi-arrow-clockwise"></i></button>
                <button class="btn-timer-main"><i class="bi bi-pause-fill"></i></button>
                <button class="btn-timer-secondary"><i class="bi bi-skip-end-fill"></i></button>
            </div>
        </div>

        <div class="time-card">
            <h3 class="fw-bold">3º Médio</h3>
            <div class="score-display">
                <button class="btn-score">-</button>
                <span class="score-number">02</span>
                <button class="btn-score" style="background: var(--inter-red); color: white;">+</button>
            </div>

            <div class="registro-geral">
                <label class="fw-bold small mb-2 d-block">Registro geral</label>
                <select class="form-select form-select-sm mb-2">
                    <option selected>Artilheiro</option>
                </select>
                <select class="form-select form-select-sm mb-3">
                    <option selected>Aluno</option>
                </select>
                <button class="btn btn-danger w-100 btn-sm fw-bold" style="background-color: var(--inter-red);">Salvar</button>
            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('[data-back-link="true"]')?.addEventListener('click', (event) => {
        event.preventDefault();
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = './edicao_agenda.php';
        }
    });
</script>
</body>
</html>