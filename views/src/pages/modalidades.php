<?php 
$titulo = "Basquete";
$textTop = "Basquete";
$btnVoltar = true;
require_once '../componentes/navbar.php';
require_once '../componentes/header.php';
?>


<main class="bg-light min-vh-100 p-3" style="padding-top: 2rem;">
    
    <div class="mx-auto" style="max-width: 450px;">
        
        <div class="d-flex gap-2 mb-4 overflow-auto pb-1" style="white-space: nowrap; scrollbar-width: none;">
            <button class="btn border border-secondary-subtle rounded-3 px-3 py-1" style="font-size: 0.85rem; background-color: #e6e6e6; color: #333;">
                Categoria I
            </button>
            <button class="btn bg-white border border-secondary-subtle rounded-3 px-3 py-1" style="font-size: 0.85rem; color: #555;">
                Categoria II
            </button>
            <button class="btn bg-white border border-secondary-subtle rounded-3 px-3 py-1" style="font-size: 0.85rem; color: #555;">
                Aluno destaque
            </button>
        </div>

        <div class="d-flex flex-column gap-3">
            
            <div class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Ana Clara</h6>
                
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-trash"></i> Remover
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-star"></i> Destacar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Geovana</h6>
                
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-trash"></i> Remover
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-star"></i> Destacar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Lara</h6>
                
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-trash"></i> Remover
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-star"></i> Destacar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="bg-white border-0 shadow-sm rounded-3 p-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">Braien</h6>
                
                <div class="dropdown">
                    <i class="bi bi-three-dots-vertical text-muted fs-5" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false"></i>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-3 p-2" style="background-color: #f8f9fa; min-width: 120px;">
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-trash"></i> Remover
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-muted d-flex align-items-center gap-2 rounded-2" href="#" style="font-size: 0.85rem;">
                                <i class="bi bi-star"></i> Destacar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</main>

<?php 

require_once '../componentes/footer.php';
?>