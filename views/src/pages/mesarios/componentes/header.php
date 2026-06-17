<!-- header mobile -->
<section class="d-md-none position-relative" style="height: 120px;">
    <?php if ($mostrarVoltar ?? true): ?>
    <a href="<?= htmlspecialchars($urlVoltar ?? './home.php') ?>" class="bi bi-arrow-left position-absolute text-white fs-3 text-decoration-none" style="top: 20px; left: 20px; z-index: 10;"></a>
    <?php endif; ?>
    <img src="../../../public/images/banner-global.png" alt="Banner" class="w-100 object-fit-cover" style="height: 100%;">
    <?php if (!empty($titulo)): ?>
    <h2 class="position-absolute top-50 start-50 translate-middle text-white m-0 fw-bold"><?= htmlspecialchars($titulo) ?></h2>
    <?php endif; ?>
</section>
<script>
    window.SGIInterclasse = (() => {
        const endpoints = {
            home: './home.php',
            ranking: './ranking.php',
            categorias: './categorias.php',
            turmas: './turmas.php',
            agenda: './agenda.php',
            pontuacoes: './pontuacao.php',
            dashboard: './dashboard.php',
            turmas: './turmas.php'
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

        const getInterclasses = async () => {
            if (cache) return cache;
            const response = await fetch('../../../../api/interclasse.php?regulamento=true');
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

        const updatePageTitle = (nomeInterclasse) => {
            const base = document.body.dataset.defaultTitle || 'SGI - Mesário';
            document.title = nomeInterclasse ? `SGI - Mesário - ${nomeInterclasse}` : `SGI - Mesário`;
        };

        return {
            endpoints, getInterclasses, getActiveInterclasse, getInterclasseById, updatePageTitle
        };
    })();
</script>
