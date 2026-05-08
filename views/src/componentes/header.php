<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - <?php echo $titulo ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="../styles/style.css">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
</head>
<body data-default-title="<?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?>">
    
<header class="position-relative">
    <?php 
    if($btnVoltar){
        echo '<a href="./home.php" data-back-link="true"><span class="position-absolute m-4 translate-middle" style="z-index: 10; " id="btnVoltar"><i class="bi bi-arrow-left fs-1 text-white"></i></span></a>';
    } else{
        echo "";
    }
    ?>
    <!-- header mobile -->
    <section class="d-md-none" style="height: 120px;">
        <a href="perfil.php"><span class="position-absolute m-4 translate-middle text-white fs-2" style="z-index: 10; top: 3%; right: -20px;" id="btnVoltar"><i class="bi bi-person-gear"></i></span></a>
        <img src="../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100 object-fit-cover" style="height: 100%;">
        <h1 class="position-absolute top-50 start-50 translate-middle text-white w-100 text-center"><?php echo $textTop ?></h1>
    </section>

    <!-- header desktop -->
    <section class="d-none d-md-flex shadow-lg" style="height: 150px;">
        <img src="../../public/images/banner-global-desktop.png" alt="Imagens de alunos do SESI" class="w-100 object-fit-cover">
        <img src="../../public/images/banner-global-desktop-frente.png" alt="sombra da imagem de alunos do SESI" class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
    </section>
</header>
<script>
    window.SGIInterclasse = (() => {
        const endpoints = {
            home: './home.php',
            ranking: './ranking.php',
            categorias: './edicao_categorias.php',
            turmas: './turmas.php',
            agenda: './edicao_agenda.php',
            arrecadacao: './edicao_arrecadacao.php',
            pontuacoes: './edicao_pontuacao.php?modo=view'
        };
        let cache = null;

        const sortByMostRecent = (lista) => {
            return [...lista].sort((a, b) => {
                const da = new Date(a.ano_interclasse || '1900-01-01').getTime();
                const db = new Date(b.ano_interclasse || '1900-01-01').getTime();
                return db - da;
            });
        };

        const toYear = (dataStr) => {
            if (!dataStr) return '';
            const year = String(dataStr).split('-')[0];
            return year || '';
        };

        const getInterclasses = async () => {
            if (cache) return cache;
            const response = await fetch('../../../api/interclasse.php?regulamento=true');
            if (!response.ok) throw new Error('Falha ao carregar interclasses');
            const data = await response.json();
            cache = Array.isArray(data) ? sortByMostRecent(data) : [];
            return cache;
        };

        const getActiveInterclasse = async () => {
            const lista = await getInterclasses();
            const ativos = lista.filter(item => String(item.status_interclasse) === '1');
            if (ativos.length > 1) {
                console.warn('Mais de um interclasse ativo encontrado. Será usado o mais recente.');
            }
            return ativos[0] || null;
        };

        const getInterclasseById = async (id) => {
            const lista = await getInterclasses();
            return lista.find(item => String(item.id_interclasse) === String(id)) || null;
        };

        const buildLinkTo = (key, idInterclasse) => {
            const base = endpoints[key] || endpoints.home;
            if (!idInterclasse) return base;
            const separador = base.includes('?') ? '&' : '?';
            return `${base}${separador}id=${idInterclasse}`;
        };

        const setupBackLinks = (fallbackPath = './home.php') => {
            document.querySelectorAll('[data-back-link="true"]').forEach((el) => {
                el.addEventListener('click', (event) => {
                    event.preventDefault();
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        window.location.href = fallbackPath;
                    }
                });
            });
        };

        const applyActiveNavbarLinks = async () => {
            const ativo = await getActiveInterclasse();
            const id = ativo?.id_interclasse;
            document.querySelectorAll('[data-active-link]').forEach((link) => {
                const key = link.getAttribute('data-active-link');
                if (!id) {
                    link.href = '#';
                    link.classList.add('disabled');
                    link.style.pointerEvents = 'none';
                    link.style.opacity = '0.45';
                    return;
                }
                link.href = buildLinkTo(key, id);
                link.classList.remove('disabled');
                link.style.pointerEvents = '';
                link.style.opacity = '';
            });
        };

        const updatePageTitle = (nomeInterclasse) => {
            const base = document.body.dataset.defaultTitle || 'SGI';
            document.title = nomeInterclasse ? `SGI - ${base} - ${nomeInterclasse}` : `SGI - ${base}`;
        };

        return {
            endpoints,
            toYear,
            getInterclasses,
            getActiveInterclasse,
            getInterclasseById,
            buildLinkTo,
            setupBackLinks,
            applyActiveNavbarLinks,
            updatePageTitle
        };
    })();

    document.addEventListener('DOMContentLoaded', () => {
        window.SGIInterclasse.setupBackLinks();
        window.SGIInterclasse.applyActiveNavbarLinks().catch((error) => {
            console.error('Falha ao configurar navbar dinâmica:', error);
        });
    });
</script>