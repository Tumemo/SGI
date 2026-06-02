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
<?php
?>
<header class="position-relative">
    <?php
    if (!empty($btnVoltar)) {
        echo '<a href="#" data-sgi-header-back="true" class="text-white text-decoration-none"><span class="position-absolute m-4 translate-middle" style="z-index: 10;" id="btnVoltarHeader"><i class="bi bi-arrow-left-circle fs-1"></i></span></a>';
    }
    ?>
    <!-- header mobile -->
    <section class="d-md-none" style="height: 120px;">
        <a href="perfil.php"><span class="position-absolute m-4 translate-middle text-white fs-2" style="z-index: 10; top: 3%; right: -20px;" id="btnVoltar"><i class="bi bi-person-gear"></i></span></a>
        <img src="../../public/images/banner-global.png" alt="Imagens de alunos do SESI" class="w-100 object-fit-cover" style="height: 100%;">
        <h1 class="position-absolute top-50 start-50 translate-middle text-white w-100 text-center"><?php echo $textTop ?></h1>
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
                const idA = Number(a.id_interclasse) || 0;
                const idB = Number(b.id_interclasse) || 0;
                if (idB !== idA) return idB - idA;
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

        const navigateBack = (fallbackPath = './home.php') => {
            try {
                const stack = JSON.parse(sessionStorage.getItem('sgi_nav_stack') || '[]');
                stack.pop();
                const anterior = stack.pop();
                sessionStorage.setItem('sgi_nav_stack', JSON.stringify(stack));
                if (anterior) {
                    window.location.href = anterior;
                    return;
                }
            } catch (_) { /* ignora */ }
            window.location.href = fallbackPath;
        };

        const registrarPaginaNavegacao = () => {
            try {
                const atual = window.location.pathname + window.location.search;
                const stack = JSON.parse(sessionStorage.getItem('sgi_nav_stack') || '[]');
                if (stack[stack.length - 1] !== atual) {
                    stack.push(atual);
                    sessionStorage.setItem('sgi_nav_stack', JSON.stringify(stack.slice(-30)));
                }
            } catch (_) { /* ignora */ }
        };

        const beforeLeaveHandlers = [];

        const registerBeforeLeave = (handler) => {
            if (typeof handler === 'function') {
                beforeLeaveHandlers.push(handler);
            }
        };

        const runBeforeLeaveHandlers = async () => {
            for (const handler of beforeLeaveHandlers) {
                try {
                    await handler();
                } catch (err) {
                    console.error('Erro ao executar ação antes de sair:', err);
                }
            }
        };

        const setupBackLinks = (fallbackPath = './home.php') => {
            document.querySelectorAll('[data-back-link="true"], [data-sgi-header-back="true"]').forEach((el) => {
                if (el.dataset.sgiBackBound === '1') return;
                el.dataset.sgiBackBound = '1';
                el.addEventListener('click', async (event) => {
                    event.preventDefault();
                    await runBeforeLeaveHandlers();
                    navigateBack(fallbackPath);
                });
            });
        };

        const applyActiveNavbarLinks = async () => {
            const ativo = await getActiveInterclasse();
            const id = ativo?.id_interclasse;
            document.querySelectorAll('[data-active-link]').forEach((link) => {
                const key = link.getAttribute('data-active-link');
                const semInterclasse = !id;
                const permitirSemInterclasse = key === 'home';
                const href = id ? buildLinkTo(key, id) : (endpoints[key] || endpoints.home);
                link.href = href;
                if (semInterclasse && !permitirSemInterclasse) {
                    link.classList.add('disabled');
                    link.style.pointerEvents = 'none';
                    link.style.opacity = '0.45';
                    return;
                }
                link.classList.remove('disabled');
                link.style.pointerEvents = '';
                link.style.opacity = '';
            });
        };

        const invalidateCache = () => {
            cache = null;
        };

        const refreshNavigation = async () => {
            invalidateCache();
            await applyActiveNavbarLinks();
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
            navigateBack,
            registerBeforeLeave,
            runBeforeLeaveHandlers,
            registrarPaginaNavegacao,
            setupBackLinks,
            applyActiveNavbarLinks,
            invalidateCache,
            refreshNavigation,
            updatePageTitle
        };
    })();

    document.addEventListener('DOMContentLoaded', () => {
        window.SGIInterclasse.registrarPaginaNavegacao();
        window.SGIInterclasse.applyActiveNavbarLinks().catch((error) => {
            console.error('Falha ao configurar navbar dinâmica:', error);
        });
    });
    window.addEventListener('load', () => {
        window.SGIInterclasse.setupBackLinks();
    });
</script>