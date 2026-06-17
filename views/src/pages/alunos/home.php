<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regulamento - Interclasse 2026</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #ffffff;
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        .app-container {
            display: flex;
            width: 100vw;
            height: 100vh;
            background-color: #ffffff;
            overflow: hidden;
        }

        /* --- SIDEBAR COM ESPAÇAMENTO JUSTIFY-AROUND --- */
        .sidebar {
            width: 70px;
            background: #e60012;
            display: flex;
            flex-direction: column;
            justify-content: space-around; /* Distribuído igualmente */
            align-items: center;
            padding: 25px 0;
            height: 100vh;
            flex-shrink: 0;
        }

        .menu-icons {
            display: flex;
            flex-direction: column;
            gap: 30px; /* Mantém consistência interna se necessário */
            align-items: center;
        }

        /* Se quiser que TODOS os itens (incluindo logout) sigam o mesmo fluxo do space-around diretamente na sidebar */
        .sidebar a {
            color: white;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
            opacity: 0.7;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ÍCONES EM TAMANHO 25PX X 25PX */
        .sidebar a i {
            font-size: 25px;
            width: 25px;
            height: 25px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .sidebar a:hover, .sidebar a.active {
            opacity: 1;
            transform: scale(1.1);
        }

        /* --- CONTEÚDO PRINCIPAL --- */
        .main-content {
            flex: 1;
            padding: 40px 50px;
            display: flex;
            flex-direction: column;
            background-color: #fcfcfc;
            overflow-y: auto;
            height: 100vh;
        }

        .badge-interclasse {
            background-color: #e60012;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 22px;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            width: fit-content;
            margin-bottom: 30px;
            text-decoration: none;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 28px;
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 12px;
            position: relative;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #e60012;
            border-radius: 2px;
        }

        .card-download {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            padding: 45px 40px;
            text-align: center;
            max-width: 760px;
            width: 100%;
            margin: auto;
        }

        .pdf-icon-container {
            width: 80px;
            height: 80px;
            background-color: #fff1f2;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px auto;
        }

        .pdf-icon-container i {
            color: #e60012;
            font-size: 36px;
        }

        .download-link-block {
            text-decoration: none;
            display: inline-block;
            margin-bottom: 10px;
        }

        .download-title {
            font-size: 20px;
            color: #000000;
            font-weight: 700;
            transition: color 0.2s ease;
        }

        .download-link-block:hover .download-title {
            color: #e60012;
        }

        .download-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 30px;
        }

        .card-divider {
            height: 1px;
            background-color: #f1f5f9;
            margin-bottom: 30px;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 14px;
            margin-bottom: 30px;
            cursor: pointer;
            text-align: left;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
            font-size: 14px;
            color: #334155;
            line-height: 1.5;
        }

        .checkbox-label input {
            display: none;
        }

        .checkbox-custom {
            width: 24px;
            height: 24px;
            border: 2px solid #cbd5e1;
            border-radius: 6px;
            background-color: #fff;
            flex-shrink: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.2s;
            margin-top: 2px;
        }

        .checkbox-label input:checked + .checkbox-custom {
            background-color: #e60012;
            border-color: #e60012;
        }

        .checkbox-label input:checked + .checkbox-custom::after {
            content: "✓";
            color: white;
            font-size: 15px;
            font-weight: bold;
            display: block;
        }

        .btn-submit {
            background-color: #e60012;
            color: #ffffff;
            border: none;
            padding: 14px 40px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(230, 0, 18, 0.2);
        }

        .btn-submit:hover {
            background-color: #cc0010;
        }

        .btn-submit:active {
            transform: scale(0.98);
        }

        .card-footer-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
            font-size: 12px;
            color: #64748b;
        }

        .card-footer-info i {
            color: #10b981;
            font-size: 14px;
        }

        /* --- GRID DE MODALIDADES --- */
        .modal-modalidades{
    max-width: 900px;
}
.modalidades-container{
    padding: 20px;
}
.modal-content{
    padding:20px;
}

.modalidades-grid{
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    max-width: 100%;
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
            <a href="home.php" class="active"><i class="bi bi-house"></i></a>
            <a href="ranking.php"><i class="bi bi-trophy"></i></a>
            <a href="teste.php"><i class="bi bi-calendar3"></i></a>
            <a href="notificacoes.php"><i class="bi bi-bell"></i></a>
            <a href="#" class="logout"><i class="bi bi-box-arrow-right"></i></a>
        </aside>

        <main class="main-content">
            
             <h1 class="fs-2">Edições do interclasse</h1>

      

        <div>
            <div class="row mt-4 bg-danger rounded-3 shadow text-white py-3 fs-5 fw-medium px-2">
                <div class="col-4">Edição interclasse</div>
                <div class="col-4 text-center">Ano</div>
                <div class="col-4 text-center">Status</div>
            </div>

            <div class="mt-2" id="listaDesktop">
                 <p class="text-center text-muted mt-5">(Carregando...)</p>
            </div>
        </div>

    </section>

           <div class="modal fade" id="modalRegulamento" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                  
                    Regulamento
                </h5>
            </div>

            <div class="modal-body">

                <section class="card-download">

                    <div class="pdf-icon-container">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </div>

                    <a href="#" download="regulamento_interclasse_2026.pdf" class="download-link-block">
                        <h2 class="download-title">Acessar PDF do regulamento</h2>
                    </a>

                    <p class="download-subtitle">
                        Leia o documento completo para participar com segurança e estar por dentro de todas as regras.
                    </p>

                    <div class="card-divider"></div>

                    <label class="checkbox-label">
                        <input type="checkbox" id="terms-checkbox">
                        <span class="checkbox-custom"></span>
                        <span>
                            Declaro que li e concordo com os termos de participação e regulamento apresentados.
                        </span>
                    </label>

                    <button type="button"
                            id="btn-submit-terms"
                            class="btn-submit"
                            onclick="validarEAvancar()">
                        <i class="bi bi-file-earmark-text"></i>
                        Li e concordo com os termos
                    </button>

                    <div class="card-footer-info">
                        <i class="bi bi-check-circle"></i>
                        <span>
                            Sua participação só será confirmada após a concordância com o regulamento.
                        </span>
                    </div>

                </section>

            </div>

        </div>
    </div>
</div>



<div class="modal fade" id="modalModalidades" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Escolha suas modalidades
                </h5>
            </div>

            <div class="modal-body">

            
          
<br>

            <h1 class="page-title">
               <i class="bi bi-trophy"></i> Modalidades
            </h1>
            <br>
            <br>

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
                <br>
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

    <script>

        async function carregarInterclasses() {
    try {

        const response = await fetch('../../../../api/interclasses.php');

        const dados = await response.json();

        const lista = document.getElementById('listaDesktop');

        lista.innerHTML = '';

        dados.forEach(interclasse => {

            lista.innerHTML += `
                <div class="row bg-white border rounded-3 shadow-sm py-3 px-2 mb-2 align-items-center">

                    <div class="col-4 fw-semibold">
                        ${interclasse.nome_interclasse}
                    </div>

                    <div class="col-4 text-center">
                        ${interclasse.ano}
                    </div>

                    <div class="col-4 text-center">

                        <button
                            class="btn btn-danger btn-sm"
                            onclick="abrirInterclasse(${interclasse.id_interclasse})">

                            Participar

                        </button>

                    </div>

                </div>
            `;
        });

    } catch (erro) {

        console.error(erro);

        document.getElementById('listaDesktop').innerHTML = `
            <div class="alert alert-danger">
                Erro ao carregar interclasses
            </div>
        `;
    }
}

window.addEventListener('load', carregarInterclasses);
        </script>

            </div>

        </div>
    </div>
</div>

      
    <script>


// window.addEventListener('load', () => 
// {

//     const modal = new bootstrap.Modal(
//         document.getElementById('modalRegulamento')
//     );

//     modal.show();

// });
window.addEventListener('load', () => {

    const aceitou = localStorage.getItem('regulamentoAceito');

    if (!aceitou) {

        const modal = new bootstrap.Modal(
            document.getElementById('modalRegulamento')
        );

        modal.show();
    }

});



;
</script>
<script>
function validarEAvancar() {

    const checkbox = document.getElementById('terms-checkbox');

    if (!checkbox.checked) {
        alert('Você precisa aceitar o regulamento.');
        return;
    }

    // Salva que aceitou
    localStorage.setItem('regulamentoAceito', 'true');

    const modalReg = bootstrap.Modal.getInstance(
        document.getElementById('modalRegulamento')
    );

    modalReg.hide();

    // Abre o modal de modalidades
    const modalMod = new bootstrap.Modal(
        document.getElementById('modalModalidades')
    );

    modalMod.show();
}
</script>
    

</body>
</html>