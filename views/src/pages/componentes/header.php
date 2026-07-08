<?php
$mostrarVoltar = $mostrarVoltar ?? true;
$urlVoltar = $urlVoltar ?? './home.php';
$titulo = $titulo ?? '';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
?>
<section class="d-md-none position-relative" style="height: 120px;">
    <?php if ($mostrarVoltar): ?>
    <a href="<?= htmlspecialchars($urlVoltar) ?>" class="bi bi-arrow-left position-absolute text-white fs-3 text-decoration-none" style="top: 20px; left: 20px; z-index: 10;"></a>
    <?php endif; ?>
    <img src="../../public/images/banner-global.png" alt="Banner" class="w-100 object-fit-cover" style="height: 100%;">
    <?php if (!empty($titulo)): ?>
    <h2 class="position-absolute top-50 start-50 translate-middle text-white m-0 fw-bold"><?= htmlspecialchars($titulo) ?></h2>
    <?php endif; ?>
</section>
<script>
window.SGIInterclasse = (() => {
    const basePath = '../../../';
    const nivel = <?= $nivelUsuario ?>;

    const endpoints = {
        home: './home.php',
        dashboard: './dashboard.php',
        categorias: './categorias.php',
        turmas: './turmas.php',
        equipes: './equipes.php',
        modalidades: './modalidades.php',
        pontuacoes: './pontuacoes.php',
        locais: './locais.php',
        arrecadacoes: './arrecadacoes.php',
        colaboradores: './colaboradores.php',
        agenda: './agenda.php',
        chaveamentos: './pontuacao.php',
        ranking: './ranking.php'
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
        return String(dataStr).split('-')[0] || '';
    };

    const getInterclasses = async () => {
        if (cache) return cache;
        const response = await fetch(basePath + 'api/interclasse.php?regulamento=true');
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
        return base + separador + 'id=' + idInterclasse;
    };

    const updatePageTitle = (nomeInterclasse) => {
        const base = document.body.dataset.defaultTitle || 'SGI';
        document.title = nomeInterclasse ? `SGI - ${base} - ${nomeInterclasse}` : `SGI - ${base}`;
    };

    const resolveId = async () => {
        const params = new URLSearchParams(window.location.search);
        let id = params.get('id');
        if (!id) {
            const ativo = await getActiveInterclasse();
            id = ativo?.id_interclasse || null;
            if (id) {
                window.history.replaceState(null, '', '?id=' + id);
            }
        }
        if (id) {
            const dados = await getInterclasseById(id);
            if (dados) updatePageTitle(dados.nome_interclasse);
        }
        return id;
    };

    const registrarPaginaNavegacao = () => {
        try {
            const atual = window.location.pathname + window.location.search;
            const stack = JSON.parse(sessionStorage.getItem('sgi_nav_stack') || '[]');
            if (stack[stack.length - 1] !== atual) {
                stack.push(atual);
                sessionStorage.setItem('sgi_nav_stack', JSON.stringify(stack.slice(-30)));
            }
        } catch (_) { }
    };

    const navigateBack = (fallbackPath) => {
        try {
            const stack = JSON.parse(sessionStorage.getItem('sgi_nav_stack') || '[]');
            stack.pop();
            const anterior = stack.pop();
            sessionStorage.setItem('sgi_nav_stack', JSON.stringify(stack));
            if (anterior) {
                window.location.href = anterior;
                return;
            }
        } catch (_) { }
        window.location.href = fallbackPath || './home.php';
    };

    const invalidateCache = () => { cache = null; };

    const refreshNavigation = async () => {
        invalidateCache();
    };

    return {
        endpoints, toYear, getInterclasses, getActiveInterclasse, getInterclasseById,
        buildLinkTo, updatePageTitle, resolveId, registrarPaginaNavegacao,
        navigateBack, invalidateCache, refreshNavigation
    };
})();

function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    window.SGIInterclasse.registrarPaginaNavegacao();
});
</script>
