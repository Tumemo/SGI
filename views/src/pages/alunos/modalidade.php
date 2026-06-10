<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modalidades - Interclasse 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #1a1a1a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            
        }

        /* Container Principal do App */
        /* Container Principal do App */
.app-container {
    display: flex;
    width: 100vw;  /* Ocupa 100% da largura da janela visual */
    height: 100vh; /* Ocupa 100% da altura da janela visual */
    background-color: #f4f4f4;
   
    overflow: hidden;
   
}

   .sidebar {
    width: 70px;
    background: #e60012;
    display: flex;
    flex-direction: column;
    justify-content: space-around;
    align-items: center;
    padding: 25px 0;
    height: 100vh;
    flex-shrink: 0;
}

.sidebar a {
    color: white;
    text-decoration: none;
    opacity: 0.7;
    transition: all .2s ease;
    display: flex;
    justify-content: center;
    align-items: center;
}

.sidebar a i {
    font-size: 25px;
    width: 25px;
    height: 25px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.sidebar a:hover,
.sidebar a.active {
    opacity: 1;
    transform: scale(1.1);
}

        /* --- CONTEÚDO PRINCIPAL --- */
        .main-content {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Botão Vermelho Superior */
        .badge-interclasse {
            background-color: #e30613;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 15px;
            border-radius: 4px;
            width: fit-content;
            margin-bottom: 25px;
            text-decoration: none;
        }

        /* Título Modalidades */
        .page-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 26px;
            color: #000000;
            font-weight: 700;
            margin-bottom: 30px;
        }

        /* --- GRID DE MODALIDADES --- */
        .modalidades-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Card de Modalidade Padrão (Não selecionado) */
        .modalidade-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            color: #000000; /* Letra preta por padrão */
            position: relative;
            user-select: none;
            transition: transform 0.15s, background-color 0.2s, color 0.2s;
        }

 .modalidade-card {
    font-size: 18px;
    font-weight: 500;
}
        .modalidade-card:hover {
            transform: translateY(-2px);
        }

        /* REQUISITO: Card Selecionado (Fundo verde, letra e ícone em branco) */
        .modalidade-card.selected {
            background-color: #5cb85c; /* Tom de verde idêntico ao da imagem */
            color: #ffffff !important; /* Letra branca forcada */
        }

        /* Checkmark redondo verde no canto superior direito do card selecionado */
        .modalidade-card.selected::after {
            content: "✓";
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background-color: #5cb85c;
            border: 2px solid #ffffff;
            color: white;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* --- ÁREA INFERIOR DE SALVAMENTO --- */
        .footer-actions {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .counter-text {
            font-size: 14px;
            color: #333333;
            font-weight: 500;
        }

        /* REQUISITO: Botão verde de salvar */
      .btn-save {
    background: linear-gradient(135deg, #e60012, #ff3344);
    color: white;
    border: none;
    padding: 14px 28px;
    min-width: 220px;
    height: 50px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    box-shadow: 0 10px 25px rgba(230, 0, 18, .25);

    transition: all .25s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(230, 0, 18, .35);
}

.btn-save:active {
    transform: scale(.98);
}

        /* Texto sutil "Confirmar" na parte inferior */
        .bottom-label {
            font-size: 14px;
            color: #b3b3b3;
            font-weight: 500;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="app-container">
        
        <aside class="sidebar">
    <a href="#"><i class="bi bi-person-gear"></i></a>

    <a href="home.php">
        <i class="bi bi-house"></i>
    </a>

    <a href="modalidades.php" class="active">
        <i class="bi bi-trophy"></i>
    </a>

    <a href="agenda.php">
        <i class="bi bi-calendar3"></i>
    </a>

    <a href="notificacao.php">
        <i class="bi bi-bell"></i>
    </a>

    <a href="#" class="logout">
        <i class="bi bi-box-arrow-right"></i>
    </a>
</aside>

        <main class="main-content">
            
            <a href="#" class="badge-interclasse">
               <i class="bi bi-arrow-left-circle-fill"></i> Interclasse 2026
              </a>

            <h1 class="page-title">
               <i class="bi bi-trophy"></i> Modalidades
            </h1>

            <div class="modalidades-grid">
                
<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Basquete
</div>

<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Futebol
</div>

<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Vôlei
</div>

<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Handebol
</div>

<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Tênis de Mesa
</div>

<div class="modalidade-card" onclick="toggleModalidade(this)">
    <i class="bi bi-trophy"></i>
    Corrida
</div>
            </div>

            <div class="footer-actions">
                <p class="counter-text">Você pode escolher até 3 modalidades</p>
                
                <button type="button" class="btn-save" onclick="salvarEscolhas()">
                    Salvar
                </button>
               

        </main>
    </div>

    <script>
        function toggleModalidade(card) {
            // Conta quantos já estão selecionados atualmente
            const selecionados = document.querySelectorAll('.modalidade-card.selected');
            
            // Se já estiver selecionado, remove a seleção livremente
            if (card.classList.contains('selected')) {
                card.classList.remove('selected');
            } else {
                // REQUISITO: Bloqueia se tentar selecionar mais do que 3
                if (selecionados.length >= 3) {
                    alert("Você só pode escolher até 3 modalidades!");
                    return;
                }
                card.classList.add('selected');
            }
        }

        function salvarEscolhas() {
            const selecionados = document.querySelectorAll('.modalidade-card.selected');
            if(selecionados.length === 0) {
                alert("Por favor, escolha pelo menos 1 modalidade antes de salvar.");
                return;
            }
            
            // Coleta os nomes das modalidades selecionadas
            let escolhidas = [];
            selecionados.forEach(card => {
                escolhidas.push(card.innerText.trim());
            });
            
            alert("Suas modalidades foram salvas com sucesso: " + escolhidas.join(", "));
            window.location.href = "home.php";
        }
        
    </script>

</body>
</html>