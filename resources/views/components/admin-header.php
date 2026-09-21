<?php
$mostrarVoltar = $mostrarVoltar ?? true;
$titulo = $titulo ?? '';
$tagTituloCompacto = ($tagTituloCompacto ?? 'h2') === 'h1' ? 'h1' : 'h2';
$nivelUsuario = (int)($_SESSION['nivel'] ?? -1);
$rotaInicialUsuario = match ($nivelUsuario) {
    0, 1 => 'edicoes',
    2 => 'painel',
    default => 'login',
};
$urlVoltar = $urlVoltar ?? \App\Shared\Http\Url::to($rotaInicialUsuario);
$idInterclasseHeader = filter_var($_GET['id'] ?? ($nivelUsuario === 2 ? ($_SESSION['id_interclasse'] ?? null) : null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idInterclasseHeader !== false && $idInterclasseHeader !== null) {
    $parametrosUrlVoltar = [];
    parse_str((string) parse_url($urlVoltar, PHP_URL_QUERY), $parametrosUrlVoltar);
    if (!isset($parametrosUrlVoltar['id'])) {
        $urlVoltar .= (str_contains($urlVoltar, '?') ? '&' : '?') . http_build_query(['id' => (int) $idInterclasseHeader]);
    }
}
$compacteCabecalho = (bool)($compacteCabecalho ?? false);
$urlVoltarMobile = $urlVoltarMobile ?? $urlVoltar;
$idVoltarMobile = (string) ($idVoltarMobile ?? 'sgiBtnVoltar');
?>
<section class="d-md-none position-relative sgi-u-h-120px<?= $compacteCabecalho ? ' sgi-compact-header' : '' ?>" >
    <?php
    $mostrarVoltarHeader = $mostrarVoltarHeader ?? true;
    if ($mostrarVoltarHeader) {
        $sgiUrlVoltar = $urlVoltarMobile;
        $sgiIdVoltar = $idVoltarMobile;
        $sgiClassVoltar = 'sgi-u-top-20px-left-20px-z-10';
        $urlVoltarRestauro = $urlVoltar ?? null;
        include SGI_ROOT . '/resources/views/components/back-button.php';
        $urlVoltar = $urlVoltarRestauro;
        unset($sgiUrlVoltar, $sgiIdVoltar, $sgiClassVoltar, $urlVoltarRestauro);
    }
    ?>
    <?php if (!empty($titulo)): ?>
    <<?= $tagTituloCompacto ?> class="position-absolute top-50 start-50 translate-middle text-black m-0 fw-bold sgi-mobile-header-title"><?= htmlspecialchars($titulo) ?></<?= $tagTituloCompacto ?>>
    <?php endif; ?>
</section>
<script>
window.SGIInterclasse = (() => {
    const basePath = window.SGI_BASE_PATH || '';
    const apiBase = (window.SGI_API_BASE || (basePath + '/api/v1/')).replace(/\/?$/, '/');
    const to = (path) => basePath + '/' + String(path).replace(/^\//, '');
    const nivel = <?= $nivelUsuario ?>;
    const metaTituloPagina = document.querySelector('meta[name="sgi-page-title"]');
    const tituloPagina = String(metaTituloPagina && metaTituloPagina.content || 'SGI').trim();

    const endpoints = {
        home: to('aluno/inicio'),
        dashboard: to('painel'),
        categorias: to('ocorrencias'),
        turmas: to('turmas'),
        equipes: to('edicoes/equipes'),
        modalidades: to('edicoes/modalidades'),
        pontuacoes: to('edicoes/pontuacao'),
        locais: to('edicoes/locais'),
        arrecadacoes: to('edicoes/arrecadacao'),
        colaboradores: to('colaboradores'),
        agenda: to('edicoes/agenda'),
        chaveamentos: to('chaveamento'),
        ranking: to('ranking')
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
        const response = await fetch(apiBase + 'edicoes?regulamento=true');
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
        const nomeEdicao = String(nomeInterclasse || '').trim();
        if (tituloPagina === 'SGI') {
            document.title = nomeEdicao ? nomeEdicao + ' | SGI' : 'SGI';
            return;
        }
        document.title = nomeEdicao && nomeEdicao !== tituloPagina
            ? tituloPagina + ' — ' + nomeEdicao + ' | SGI'
            : tituloPagina + ' | SGI';
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
        window.location.href = fallbackPath || to('aluno/inicio');
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
