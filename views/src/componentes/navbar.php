<!-- navbar mobile -->
<nav class="d-md-none order-last fixed-bottom py-2 order-lg-first bg-danger">
    <ul class="nav justify-content-around fs-1">
        <li><a href="../pages/home.php" class="text-white"><i class="bi bi-house"></i></a></li>
        <li><a href="../pages/turmas.php" class="text-white"><i class="bi bi-person"></i></a></li>
        <li><a href="../pages/ranking.php" class="text-white"><i class="bi bi-trophy"></i></a></li>
        <li><a href="../../index.php" class="text-white"><i class="bi bi-arrow-bar-right"></i></a></li>
    </ul>
</nav>

<!-- navbar desktop -->
<nav class="d-none d-lg-flex flex-column position-fixed vh-100 start-0 shadow-lg"
    style="width: 80px; z-index: 1040; background-color: #050a18;">

    <ul class="nav flex-column align-items-center justify-content-around h-100 py-4 gap-4 fs-3">
        <li>
            <a href="perfil.php" class="text-white opacity-75 hover-opacity-100">
                <i class="bi bi-person-gear"></i>
            </a>
        </li>

        <li>
            <a href="home.php" class="text-white opacity-75">
                <i class="bi bi-house"></i>
            </a>
        </li>

        <li>
            <a href="modalidades.php" class="text-white opacity-75">
                <i class="bi bi-bookmark"></i>
            </a>
        </li>

        <li>
            <a href="ranking.php" class="text-white opacity-75">
                <i class="bi bi-trophy"></i>
            </a>
        </li>

        <li>
            <a href="edicao_agenda.php" class="text-white opacity-75">
                <i class="bi bi-calendar3"></i>
            </a>
        </li>

        <li>
            <a href="turmas.php" class="text-white opacity-75">
                <i class="bi bi-people"></i>
            </a>
        </li>

        <li>
            <a href="edicao_arrecadacao.php" class="text-white opacity-75">
                <i class="bi bi-basket"></i>
            </a>
        </li>

        <li>
            <a href="#" class="text-white opacity-75">
                <i class="bi bi-award"></i>
            </a>
        </li>

        <li class="">
            <a href="../../index.php" class="text-white opacity-75">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </li>
    </ul>
</nav>

<style>
    .nav li a:hover {
        opacity: 1 !important;
        transition: 0.3s;
    }
</style>