<?php
$titulo = "Nova Edição - Modalidades";
$textTop = "Modalidades";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>

<main class=" position-relative d-md-none" style="margin-bottom: 120px;">
    <section class="m-auto d-flex flex-column gap-3 mt-5" style="width: 90%;">
        <section class="rounded bg-white">
            <form id="formMobile" class="input-group my-3 m-auto" style="width: 90%;">
                <input type="text" id="inputModalidadeMobile" placeholder="Nome da nova modalidade" class="form-control" style="font-size: 0.7rem;" required>
                <button type="submit" class="btn btn-danger" id="btnAdicionarMobile">Adicionar</button>
            </form>
            <section class="my-3 m-auto" style="width: 90%;" id="listarModalidadesMobile">
            </section>
        </section>
    </section>
    <a href="./edicao_pontuacao.php" id="btnContinuarMobile" class="btn btn-danger position-absolute position-fixed translate-middle" style="width: max-content;  top: 87%; left: 50%; z-index: 10; cursor: pointer;">Continuar</a>
</main>

<main class="d-none d-md-block main-desktop-layout">
    <div class="container-fluid px-0" style="max-width: 90%;">
        
        <div class="mb-5">
            <a class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold mb-4 px-3 py-2 border-0 text-decoration-none" style="background-color: #ed1c24; border-radius: 6px;" id="btnVoltarNovaModalidade" href="./dashboard.php">
                <i class="bi bi-arrow-left-circle fs-5"></i> <span id="nomeInterclasseNovaModalidade">Interclasse</span>
            </a>
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2 mt-2">
                <i class="bi bi-trophy fs-5"></i> Modalidades
            </h5>
        </div>

        <div class="row g-4 mb-5" id="listaModalidades">
            <p class="text-muted small">(Carregando modalidades...)</p>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-end align-items-center gap-3 mt-5 pt-3">
            <span class="text-muted fs-6">Não tem a modalidade que você quer?</span>
            
            <button class="btn bg-white fw-semibold rounded-3 px-4 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="color: #ed1c24; border: 1px solid #ed1c24;" data-bs-toggle="modal" data-bs-target="#exampleModal">
                <i class="bi bi-plus-circle"></i> Adicionar
            </button>
            
            <a href="#" id="btnContinuar" class="text-decoration-none">
                <button class="btn fw-semibold rounded-3 px-4 py-2 text-white shadow-sm" style="background-color: #ed1c24; border: 1px solid #ed1c24;">
                    Continuar
                </button>
            </a>
        </div>

    </div>
</main>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content border-0 shadow-lg rounded-4 p-2">

            <div class="modal-header border-0 pb-0 justify-content-center">
                <h5 class="modal-title fw-bold text-center w-100" style="color: #ed1c24;">
                    CRIAR NOVA MODALIDADE
                </h5>
            </div>

            <form id="formDesktop">
                <div class="modal-body pt-3 pb-3">

                    <div class="mb-3">
                        <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Nome da modalidade:</label>
                        <input type="text" class="form-control form-control-lg shadow-sm rounded-3 text-secondary" placeholder="Ex: Futsal" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputNomeModalidade" required>
                    </div>

                    <div class="mb-3">
                        <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Gênero:</label>
                        <select class="form-select form-select-lg shadow-sm rounded-3 text-secondary" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputGeneroModalidade" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="MASC">Masculino (M)</option>
                            <option value="FEM">Feminino (F)</option>
                            <option value="MISTO">Misto</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Máx. de Inscritos (Opcional):</label>
                        <input type="number" class="form-control form-control-lg shadow-sm rounded-3 text-secondary" placeholder="Ex: 12" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputMaxInscritos" min="0">
                    </div>

                    <div class="mb-3">
                        <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Tipo de Modalidade:</label>
                        <select class="form-select form-select-lg shadow-sm rounded-3 text-secondary" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputTipoModalidade" required>
                            <option value="" disabled selected>Selecione um tipo...</option>
                            <option value="1">Esporte de Quadra</option>
                            <option value="2">E-sports</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="text-dark mb-1 fw-medium" style="font-size: 0.95rem;">Categoria:</label>
                        <select class="form-select form-select-lg shadow-sm rounded-3 text-secondary" style="font-size: 0.95rem; border: 1px solid #dee2e6;" id="inputCategoriaModalidade" required>
                            <option value="" disabled selected>Selecione uma categoria...</option>
                            <option value="1">Ensino Médio</option>
                            <option value="2">Ensino Fundamental</option>
                        </select>
                    </div>

                    <div id="msgModalidadeDesktop" class="mt-2 text-center"></div>
                </div>

                <div class="modal-footer border-0 pt-0 pb-3 justify-content-end gap-2">
                    <button type="button" class="btn bg-white fw-semibold rounded-3 px-4 py-2" data-bs-dismiss="modal" style="color: #ed1c24; border: 1px solid #ed1c24;">
                        Cancelar
                    </button>
                    <button type="submit" class="btn fw-semibold rounded-3 px-4 py-2 text-white" style="background-color: #ed1c24; border: 1px solid #ed1c24;" id="btnAdicionarDesktop">
                        Criar
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    // 1. Pegar o ID do Interclasse que veio pela URL
    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');

    // 2. Repassar o ID para a página de Regulamentos
    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) return;
        const btnMob = document.getElementById('btnContinuarMobile');
        const btnDesk = document.getElementById('btnContinuar');
        if (btnMob) btnMob.href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        if (btnDesk) btnDesk.href = `./edicao_pontuacao.php?id=${idInterclasse}`;
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        if (dados?.nome_interclasse) {
            document.getElementById('nomeInterclasseNovaModalidade').innerText = dados.nome_interclasse;
            document.getElementById('btnVoltarNovaModalidade').href = `./dashboard.php?id=${idInterclasse}`;
            window.SGIInterclasse.updatePageTitle(dados.nome_interclasse);
        }
    }

    // 3. Função para listar as modalidades
    async function ListarModalidades() {
        try {
            const response = await fetch(`../../../api/modalidades.php?id_interclasse=${idInterclasse}`);
            const data = await response.json();

            const containerDesktop = document.getElementById('listaModalidades');
            const containerMobile = document.getElementById('listarModalidadesMobile');

            if(containerDesktop) containerDesktop.innerHTML = '';
            if(containerMobile) containerMobile.innerHTML = '';

            if (data && data.length > 0) {
                data.forEach(modalidade => {
                    const nome = modalidade.nome_modalidade;

                    if(containerDesktop) {
                        containerDesktop.innerHTML += `
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="bg-white border-0 shadow-sm rounded-3 p-3 position-relative d-flex align-items-center justify-content-center" style="cursor: pointer; min-height: 70px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-trophy fs-5 text-dark"></i>
                                        <span class="fw-bold text-dark fs-6">${nome}</span>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted position-absolute" style="right: 1.25rem;"></i>
                                </div>
                            </div>
                        `;
                    }

                    if(containerMobile) {
                        containerMobile.innerHTML += `
                            <h2 class="bg-secondary-subtle mb-2 fs-6 p-2 ps-4 w-100 rounded m-auto">${nome}</h2>
                        `;
                    }
                });
            } else {
                if(containerDesktop) containerDesktop.innerHTML = '<p class="text-muted w-100 mt-3">Nenhuma modalidade adicionada ainda.</p>';
                if(containerMobile) containerMobile.innerHTML = '<p class="text-muted w-100 mt-3 text-center">Nenhuma modalidade adicionada.</p>';
            }
        } catch (error) {
            console.error("Erro ao listar modalidades:", error);
        }
    }

    // 4. Lógica para criar a modalidade mandando os dados EXATOS que o PHP pede
    async function criarModalidade(dados, btnSubmit) {
        try {
            btnSubmit.disabled = true;

            const response = await fetch('../../../api/modalidades.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados) // CORREÇÃO: Enviando a variável "dados" correta
            });

            const result = await response.json();

            if (response.ok && result.success) {
                ListarModalidades(); // Atualiza a lista na tela
                return true;
            } else {
                alert("Erro ao criar modalidade: " + (result.message || "Verifique os dados informados."));
                return false;
            }
        } catch (error) {
            console.error("Erro na requisição:", error);
            alert("Erro ao conectar com o servidor.");
            return false;
        } finally {
            btnSubmit.disabled = false;
        }
    }

    // 5. Submit do Desktop
    const formDesktop = document.getElementById('formDesktop');
    if(formDesktop) {
        formDesktop.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Monta o objeto com os dados exatos exigidos pela API
            const dadosModalidade = {
                interclasses_id_interclasse: parseInt(idInterclasse),
                nome_modalidade: document.getElementById('inputNomeModalidade').value,
                genero_modalidade: document.getElementById('inputGeneroModalidade').value,
                max_inscrito_modalidade: parseInt(document.getElementById('inputMaxInscritos').value) || 0,
                tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
                categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
            };

            const btn = document.getElementById('btnAdicionarDesktop');
            const msg = document.getElementById('msgModalidadeDesktop');

            const sucesso = await criarModalidade(dadosModalidade, btn);
            
            if (sucesso) {
                formDesktop.reset(); // Limpa todos os campos do formulário
                msg.innerHTML = `<p class="text-success fw-bold text-center mt-2 mb-0">Modalidade Adicionada!</p>`;

                setTimeout(() => {
                    const modalEl = document.getElementById('exampleModal');
                    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modal.hide();
                    msg.innerHTML = '';
                }, 1000);
            }
        });
    }

    // 6. Submit do Mobile (AVISO: Vai falhar na API se não enviar Gênero, Tipo e Categoria)
    const formMobile = document.getElementById('formMobile');
    if(formMobile) {
        formMobile.addEventListener('submit', async (e) => {
            e.preventDefault();
            alert("Atenção: A versão mobile precisa ter os campos de Gênero, Tipo e Categoria para a API aceitar!");
            
            /* O código abaixo precisaria buscar os valores dos selects no mobile também
            const dadosMobile = {
                nome_modalidade: document.getElementById('inputModalidadeMobile').value,
                // Faltam os outros campos aqui...
            };
            const btn = document.getElementById('btnAdicionarMobile');
            const sucesso = await criarModalidade(dadosMobile, btn);
            if (sucesso) formMobile.reset();
            */
        });
    }

    // Inicia a listagem
    window.onload = async () => {
        await resolverInterclasse();
        await ListarModalidades();
    };
</script>

<?php
require_once '../componentes/footer.php';
?>