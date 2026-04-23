<?php
$titulo = "Colaboradores";
$textTop = "Colaboradores";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>


<main class="bg-light min-vh-100 p-3" style="padding-top: 2rem;">

    <style>
        .accordion-button:not(.collapsed) {
            background-color: #fff !important;
            color: #212529 !important;
            box-shadow: none !important;
        }

        .accordion-button:focus {
            box-shadow: none !important;
        }

        .accordion-button::after {
            background-size: 1rem;
        }
    </style>

    <div class="mx-auto" style="max-width: 450px;">

        <button class="btn btn-danger mb-4 px-3 py-2 fw-bold d-flex align-items-center gap-2 border-0" style="border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#AdicionarColaboradorModal">
            <i class="bi bi-plus-circle"></i> Adicionar
        </button>

        <div class="accordion d-flex flex-column gap-3" id="accordionColaboradores">

            <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingUm">
                    <button class="accordion-button collapsed bg-white p-3 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUm" aria-expanded="false" aria-controls="collapseUm">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Gilmara Beraldo</h6>
                            <small class="text-muted" style="font-size: 0.9rem;">Administradora, mesária</small>
                        </div>
                    </button>
                </h2>
                <div id="collapseUm" class="accordion-collapse collapse" aria-labelledby="headingUm" data-bs-parent="#accordionColaboradores">
                    <div class="accordion-body pt-0 bg-white position-relative">
                        <div class="form-check mb-2">
                            <input class="form-check-input bg-dark border-dark shadow-none" type="checkbox" id="checkAdmin1" checked>
                            <label class="form-check-label text-muted" for="checkAdmin1" style="font-size: 0.9rem;">Administrador</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bg-dark border-dark shadow-none" type="checkbox" id="checkMesario1" checked>
                            <label class="form-check-label text-muted" for="checkMesario1" style="font-size: 0.9rem;">Mesário</label>
                        </div>

                        <i class="bi bi-trash text-dark position-absolute bottom-0 end-0 m-3 fs-5" style="cursor: pointer;"></i>
                    </div>
                </div>
            </div>

            <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="headingDois">
                    <button class="accordion-button collapsed bg-white p-3 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDois" aria-expanded="false" aria-controls="collapseDois">
                        <div>
                            <h6 class="mb-0 fw-bold text-dark">Gabriel Godoy</h6>
                            <small class="text-muted" style="font-size: 0.8rem;">Administrador, mesário</small>
                        </div>
                    </button>
                </h2>
                <div id="collapseDois" class="accordion-collapse collapse" aria-labelledby="headingDois" data-bs-parent="#accordionColaboradores">
                    <div class="accordion-body pt-0 bg-white position-relative">
                        <div class="form-check mb-2">
                            <input class="form-check-input bg-dark border-dark shadow-none" type="checkbox" id="checkAdmin2" checked>
                            <label class="form-check-label text-muted" for="checkAdmin2" style="font-size: 0.9rem;">Administrador</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input bg-dark border-dark shadow-none" type="checkbox" id="checkMesario2" checked>
                            <label class="form-check-label text-muted" for="checkMesario2" style="font-size: 0.9rem;">Mesário</label>
                        </div>

                        <i class="bi bi-trash text-dark position-absolute bottom-0 end-0 m-3 fs-5" style="cursor: pointer;"></i>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>





<!-- modal de adicionar (escolher as opções: aluno ou cadastrar) -->
<div class="modal fade" id="adicionarColaboradorModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-0">
            <div class="modal-header border border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <h2 class="modal-title fs-5 text-danger text-center">Adicionar Colaborador</h2>
                <form id="formulario">
                    <div class="d-flex justify-content-center gap-3 mt-5">
                        <p class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#addAlunoDropdownModal">Aluno</p>
                        <p class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addColaboradorModal">Cadastrar</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer border border-0">
            </div>
        </div>
    </div>
</div>



<!-- modal adicionar colaborador -->
<div class="modal fade" id="addColaboradorModal" tabindex="-1" aria-labelledby="addColaboradorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg border-0 bg-light p-1">
      <div class="modal-header border-0 pb-0">
        <h3 class="modal-title w-100 text-center fw-bold text-danger" id="addColaboradorModalLabel">
          Adicionar colaborador
        </h3>
      </div>
      <div class="modal-body py-4">
        <form id="colaboradorForm">
          <div class="mb-3">
            <input type="text" class="form-control rounded border-secondary py-1" id="nomeColaborador" placeholder="Nome" required>
          </div>
          <div class="mb-3">
            <input type="email" class="form-control rounded border-secondary py-1" id="emailColaborador" placeholder="Email" required>
          </div>
          <div class="mb-3">
            <input type="text" class="form-control rounded border-secondary py-1" id="nifColaborador" placeholder="NIF" required>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 d-flex justify-content-center gap-3 pt-0">
        <button type="button" class="btn btn-outline-danger rounded px-2 py-1 fw-semibold" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="submit" form="colaboradorForm" class="btn btn-danger rounded px-2 py-1 fw-semibold bg-danger border border-danger" style="background-color: #ed1c24; border-color: #ed1c24;">
          Cadastrar
        </button>
      </div>
    </div>
  </div>
</div>






<!-- modal de adicionar aluno -->
 <div class="modal fade" id="addAlunoDropdownModal" tabindex="-1" aria-labelledby="addColaboradorDropdownModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg border-0 bg-white p-1">
      
      <div class="modal-header border-0 pb-0">
        <h3 class="modal-title w-100 text-center fw-bold" id="addColaboradorDropdownModalLabel" style="color: #ed1c24;">
          Adicionar colaborador
        </h3>
      </div>
      
      <div class="modal-body py-4">
        <form id="colaboradorDropdownForm">
          
          <div class="mb-3">
            <select class="form-select rounded py-1 text-muted" style="border-color: #b0b0b0; box-shadow: none; cursor: pointer;" aria-label="Selecione a Sala">
              <option selected disabled>Sala</option>
              <option value="1">Sala A</option>
              <option value="2">Sala B</option>
            </select>
          </div>
          
          <div class="mb-3">
            <select class="form-select rounded py-1 text-muted" style="border-color: #b0b0b0; box-shadow: none; cursor: pointer;" aria-label="Selecione o Aluno">
              <option selected disabled>Aluno</option>
              <option value="1">João Silva</option>
              <option value="2">Maria Souza</option>
            </select>
          </div>
          
          <div class="mb-3">
            <select class="form-select rounded py-1 text-muted" style="border-color: #b0b0b0; box-shadow: none; cursor: pointer;" aria-label="Selecione o Mesário">
              <option selected disabled>Mesário</option>
              <option value="1">Gilmara Beraldo</option>
              <option value="2">Gabriel Godoy</option>
            </select>
          </div>

        </form>
      </div>
      
      <div class="modal-footer border-0 d-flex justify-content-center gap-3 pt-0">
        <button type="button" class="btn rounded px-2 py-1 fw-semibold bg-white text-danger border border-danger" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="submit" form="colaboradorDropdownForm" class="btn rounded px-2 py-1 fw-semibold text-white border border-danger bg-danger">
          Cadastrar
        </button>
      </div>
      
    </div>
  </div>
</div>



<?php

require_once '../componentes/footer.php';
?>