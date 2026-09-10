const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');
const bundles = JSON.parse(fs.readFileSync(path.join(root, 'tools', 'css-bundles.json'), 'utf8'));

function readTree(directory, extensions, files = []) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
        const file = path.join(directory, entry.name);
        if (entry.isDirectory()) {
            readTree(file, extensions, files);
        } else if (extensions.some((extension) => entry.name.endsWith(extension))) {
            files.push(file);
        }
    }
    return files;
}

test('CSS bundles share one themed Bootstrap entrypoint', () => {
    assert.deepEqual(bundles['bootstrap-theme'], ['resources/scss/bootstrap-theme.scss']);
    assert.deepEqual(bundles.shared, ['resources/scss/shared.scss', 'resources/css/source/utilities.css']);
    assert.ok(bundles.admin.includes('resources/css/source/admin.css'));
    assert.ok(bundles.aluno.includes('resources/css/source/aluno-shared.css'));
    assert.ok(bundles.login.includes('resources/css/source/login.css'));
});

test('asset build publishes only Bootstrap JavaScript beside the themed CSS', () => {
    const build = fs.readFileSync(path.join(root, 'tools', 'build-assets.cjs'), 'utf8');
    assert.match(build, /name === 'bootstrap'[\s\S]*bootstrap\.bundle\.min\.js/);
});

test('context heads do not load Bootstrap vendor CSS alongside the theme', () => {
    const heads = [
        path.join(root, 'resources', 'views', 'components', 'admin-head.php'),
        path.join(root, 'resources', 'views', 'components', 'aluno-head.php'),
        path.join(root, 'resources', 'views', 'pages', 'acesso', 'login.php'),
    ].map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    assert.equal((heads.match(/vendor\/bootstrap\/css\/bootstrap\.min\.css/g) || []).length, 0);
    assert.ok((heads.match(/css\/bootstrap-theme\.css/g) || []).length >= 3);
    assert.ok((heads.match(/css\/shared\.css/g) || []).length >= 2);
    assert.doesNotMatch(fs.readFileSync(path.join(root, 'resources', 'views', 'pages', 'acesso', 'login.php'), 'utf8'), /css\/shared\.css/);
});

test('shared contexts load the Bootstrap feedback helper', () => {
    const adminHead = fs.readFileSync(path.join(root, 'resources', 'views', 'components', 'admin-head.php'), 'utf8');
    const alunoHead = fs.readFileSync(path.join(root, 'resources', 'views', 'components', 'aluno-head.php'), 'utf8');
    const helper = fs.readFileSync(path.join(root, 'resources', 'js', 'shared', 'bootstrap-feedback.js'), 'utf8');
    assert.match(adminHead, /js\/shared\/bootstrap-feedback\.js/);
    assert.match(alunoHead, /js\/shared\/bootstrap-feedback\.js/);
    assert.match(helper, /Toast\.getOrCreateInstance/);
    assert.match(helper, /textContent = mensagem/);
});

test('legacy global Bootstrap overrides were removed from source CSS', () => {
    const admin = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    const login = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'login.css'), 'utf8');
    assert.doesNotMatch(admin, /^\*\s*\{/m);
    assert.doesNotMatch(admin, /^\.btn-danger\s*\{/m);
    assert.doesNotMatch(admin, /\.fw-bold, \.fw-bolder, \.fw-semibold/);
    assert.doesNotMatch(login, /^\.btn-danger\s*\{/m);
});

test('compiled Bootstrap theme exposes the SGI primary token without a BOM', () => {
    const source = fs.readFileSync(path.join(root, 'resources', 'scss', 'bootstrap-theme.scss'), 'utf8');
    assert.match(source, /@use\s+'bootstrap\/scss\/bootstrap'/);
    assert.match(source, /\$primary:\s+theme\.\$primary/);

    const outputPath = path.join(root, 'public', 'assets', 'css', 'bootstrap-theme.css');
    if (!fs.existsSync(outputPath)) return;
    const compiled = fs.readFileSync(outputPath, 'utf8');
    assert.equal(compiled.charCodeAt(0), 47); // the generated source comment starts with '/'
    assert.match(compiled, /--bs-primary:\s*#e30613/);
    assert.match(compiled, /--bs-body-font-family:\s*var\(--bs-font-sans-serif\)/);
    assert.match(compiled, /@import[\"']https:\/\/fonts\.googleapis\.com\/css2\?family=Inter/);
});

test('all public CSS bundles are emitted without a leading BOM', () => {
    const cssDirectory = path.join(root, 'public', 'assets', 'css');
    if (!fs.existsSync(cssDirectory)) return;
    for (const file of fs.readdirSync(cssDirectory).filter((name) => name.endsWith('.css'))) {
        const contents = fs.readFileSync(path.join(cssDirectory, file));
        assert.notEqual(contents[0], 0xef, `${file} não deve começar com BOM`);
    }
});

test('public manifest does not retain the legacy Bootstrap CSS vendor', () => {
    const manifestPath = path.join(root, 'public', 'assets', 'manifest.json');
    if (!fs.existsSync(manifestPath)) return;
    const manifest = fs.readFileSync(manifestPath, 'utf8');
    assert.doesNotMatch(manifest, /vendor\/bootstrap\/css/);
});

test('shared utility classes all have a template or JavaScript consumer', () => {
    const utilityCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'utilities.css'), 'utf8');
    const utilityNames = [...new Set([...utilityCss.matchAll(/\.(sgi-u-[A-Za-z0-9_-]+)/g)].map((match) => match[1]))];
    const sourceFiles = [
        ...readTree(path.join(root, 'resources', 'views'), ['.php', '.html']),
        ...readTree(path.join(root, 'resources', 'js'), ['.js']),
    ];
    const source = sourceFiles.map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    const unused = utilityNames.filter((name) => !new RegExp('\\b' + name + '\\b').test(source));
    assert.deepEqual(unused, [], `Utilitários sem consumidor: ${unused.join(', ')}`);
});

test('shared custom utilities are restricted to documented domain exceptions', () => {
    const utilityCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'utilities.css'), 'utf8');
    const allowed = new Set([
        'sgi-u-w-max-content-maxw-96vw-top-85',
        'sgi-u-bottom-40px-right-5-z-1050',
        'sgi-u-cursor-pointer',
        'sgi-u-col-1-1',
        'sgi-u-w-0',
        'sgi-u-h-60px-w-60px-bottom-100px',
        'sgi-u-animation-delay-calc-attr-data-sgi-index-type-number-07s',
        'sgi-u-h-8px',
        'sgi-u-w-calc-attr-data-sgi-width-type-number-1',
        'sgi-u-h-12px',
        'sgi-u-bottom-92px-right-16px-z-20',
        'sgi-u-flex-1-min-width-160px-text-align-center',
        'sgi-u-max-height-60vh-overflow-y-auto',
        'sgi-u-w-max-content-top-85-left-50',
        'sgi-u-h-120px',
        'sgi-u-top-20px-left-20px-z-10',
        'sgi-u-min-width-0',
    ]);
    const names = [...new Set([...utilityCss.matchAll(/\.(sgi-u-[A-Za-z0-9_-]+)/g)].map((match) => match[1]))];
    assert.deepEqual(names.filter((name) => !allowed.has(name)), []);
});

test('legacy duplicate component blocks remain removed', () => {
    const admin = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    const utilities = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'utilities.css'), 'utf8');
    assert.doesNotMatch(admin, /\.bracket-container\s*\{|\.bracket-wrapper\s*\{|\.placar-wrapper\s*\{|\.table-custom\s*\{/);
    assert.doesNotMatch(admin, /\.style-card\s*\{|\.colaborador-card\s*\{|\.acao-btn\s*\{|\.card-custom\s*\{/);
    assert.doesNotMatch(admin, /--vermelho\s*:/);
    assert.doesNotMatch(utilities, /sgi-u-z-1040-h-64px|sgi-u-w-80px-top-0-bottom-0/);
    assert.doesNotMatch(utilities, /sgi-u-display-none(?:-mt-10px)?\b|sgi-u-(?:w-100|maxw-100|h-100)(?!-)|sgi-u-p-24px\b/);
});

test('feedback and data cards use Bootstrap components instead of custom duplicates', () => {
    const adminCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    const alunoCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'aluno-home.css'), 'utf8');
    const sources = [
        path.join(root, 'resources', 'js', 'pages', 'participantes', 'turmas.js'),
        path.join(root, 'resources', 'js', 'pages', 'disciplina', 'ocorrencias.js'),
        path.join(root, 'resources', 'js', 'pages', 'eventos', 'configurar-arrecadacao.js'),
        path.join(root, 'resources', 'views', 'pages', 'participantes', 'turmas.php'),
        path.join(root, 'resources', 'views', 'pages', 'acesso', 'perfil.php'),
        path.join(root, 'resources', 'views', 'pages', 'aluno', 'perfil.php'),
        path.join(root, 'resources', 'views', 'pages', 'eventos', 'dashboard.php'),
    ].map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    assert.doesNotMatch(adminCss, /\.toast-sgi|\.skeleton-card|\.ocr-card(?:__|\s*\{)|\.dash-card|\.perfil-toast|\.perfil-avatar-skeleton/);
    assert.doesNotMatch(alunoCss, /\.perfil-toast|\.perfil-avatar-skeleton/);
    assert.doesNotMatch(sources, /toast-sgi|skeleton-card|ocr-card__|dash-card|perfil-toast|perfil-avatar-skeleton/);
    assert.match(sources, /toast-container/);
    assert.match(sources, /placeholder-glow/);
    assert.match(sources, /data-sgi-action="save-arrecadacao"/);
    assert.match(sources, /card h-100 p-4 text-decoration-none shadow-sm/);
});

test('native Bootstrap display and sizing utilities are used in migrated markup', () => {
    const sourceFiles = [
        ...readTree(path.join(root, 'resources', 'views'), ['.php', '.html']),
        ...readTree(path.join(root, 'resources', 'js'), ['.js']),
    ];
    const source = sourceFiles.map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    assert.doesNotMatch(source, /sgi-u-display-none(?:-mt-10px)?\b|sgi-u-(?:w-100|maxw-100|h-100)(?!-)|sgi-u-p-24px\b/);
    assert.match(source, /\bd-none\b/);
    assert.match(source, /\bw-100\b/);
    assert.match(source, /\bh-100\b/);
    assert.match(source, /\bmw-100\b/);
    assert.match(source, /\bp-4\b/);
});

test('SGI aluno design tokens are defined once in shared CSS', () => {
    const shared = fs.readFileSync(path.join(root, 'resources', 'scss', 'shared.scss'), 'utf8');
    const admin = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    const aluno = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'aluno-shared.css'), 'utf8');
    assert.match(shared, /--aluno-primary:\s+var\(--bs-primary\)/);
    assert.doesNotMatch(admin, /--aluno-primary\s*:/);
    assert.doesNotMatch(aluno, /--aluno-primary\s*:/);
});

test('danger is reserved for destructive actions', () => {
    const sourceFiles = [
        ...readTree(path.join(root, 'resources', 'views'), ['.php', '.html']),
        ...readTree(path.join(root, 'resources', 'js'), ['.js']),
    ];
    const invalid = [];
    sourceFiles.forEach((file) => {
        fs.readFileSync(file, 'utf8').split('\n').forEach((line) => {
            if (line.includes('btn-danger') && !/exclu|delete/i.test(line)) invalid.push(file);
        });
    });
    assert.deepEqual(invalid, [], 'btn-danger deve aparecer apenas em ações destrutivas');
});

test('ranking filters use Bootstrap states in PHP and JavaScript consumers', () => {
    const sources = [
        path.join(root, 'resources', 'js', 'pages', 'resultados', 'ranking.js'),
        path.join(root, 'resources', 'js', 'pages', 'aluno', 'ranking.js'),
        path.join(root, 'resources', 'css', 'source', 'admin.css'),
        path.join(root, 'resources', 'css', 'source', 'aluno-home.css'),
    ].map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    assert.match(sources, /btn-sm btn-outline-primary rounded-pill btn-categoria/);
    assert.match(sources, /classList\.toggle\('active'/);
    assert.doesNotMatch(sources, /\.btn-categoria\.(ativo|rk-cat-btn)/);
    assert.doesNotMatch(sources, /\.rk-hist-btn\s*\{/);
});

test('team actions use native Bootstrap button variants', () => {
    const adminCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    const sources = [
        path.join(root, 'resources', 'views', 'pages', 'competicoes', 'equipe-alunos.php'),
        path.join(root, 'resources', 'views', 'pages', 'competicoes', 'elenco-equipe.php'),
        path.join(root, 'resources', 'views', 'pages', 'eventos', 'configurar-equipes.php'),
        path.join(root, 'resources', 'js', 'pages', 'competicoes', 'elenco-equipe.js'),
        path.join(root, 'resources', 'js', 'pages', 'eventos', 'configurar-equipes.js'),
    ].map((file) => fs.readFileSync(file, 'utf8')).join('\n');
    assert.doesNotMatch(sources, /\bbtn-aluno\b/);
    assert.match(sources, /btn-primary/);
    assert.match(sources, /btn-outline-primary/);
    assert.doesNotMatch(adminCss, /\.btn-filter-cat\s*\{/);
});

test('portal cards use native Bootstrap actions and status badges', () => {
    const js = fs.readFileSync(path.join(root, 'resources', 'js', 'pages', 'aluno', 'home.js'), 'utf8');
    const css = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'aluno-shared.css'), 'utf8');
    assert.match(js, /btn btn-primary btn-sm/);
    assert.match(js, /badge rounded-pill text-bg-/);
    assert.doesNotMatch(js, /\bbtn-card\b|\baluno-status-badge\b/);
    assert.doesNotMatch(css, /\.aluno-card \.btn-card|\.aluno-status-badge/);
});

test('student status and enrollment actions use Bootstrap components', () => {
    const jogos = fs.readFileSync(path.join(root, 'resources', 'js', 'pages', 'aluno', 'jogos.js'), 'utf8');
    const modalidade = fs.readFileSync(path.join(root, 'resources', 'views', 'pages', 'aluno', 'modalidade.php'), 'utf8');
    const css = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'aluno-pages.css'), 'utf8');
    assert.match(jogos, /badge rounded-pill \$\{status\.classe\}/);
    assert.match(jogos, /text-bg-primary/);
    assert.match(modalidade, /class="btn btn-primary px-4 py-2"/);
    assert.doesNotMatch(css, /\.status-badge|\.status-andamento|\.btn-save\s*\{/);
});

test('competition list and bracket modal use native status and action variants', () => {
    const games = fs.readFileSync(path.join(root, 'resources', 'js', 'pages', 'competicoes', 'jogos.js'), 'utf8');
    const bracket = fs.readFileSync(path.join(root, 'resources', 'views', 'pages', 'competicoes', 'chaveamento.php'), 'utf8');
    const css = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    assert.match(games, /badge rounded-pill \$\{statusClass\}/);
    assert.match(bracket, /btn btn-primary/);
    assert.match(bracket, /btn btn-outline-secondary/);
    assert.doesNotMatch(css, /\.status-badge\s*\{|\.kv-modal \.btn-save\s*\{/);
});

test('score controls keep behavior hooks while using native Bootstrap controls', () => {
    const placar = fs.readFileSync(path.join(root, 'resources', 'js', 'pages', 'competicoes', 'placar.js'), 'utf8');
    const adminCss = fs.readFileSync(path.join(root, 'resources', 'css', 'source', 'admin.css'), 'utf8');
    assert.match(placar, /mc-action-btn--start btn btn-primary/);
    assert.match(placar, /mc-action-btn--finish btn btn-outline-danger/);
    assert.match(placar, /mc-duration-select form-select form-select-sm w-auto/);
    assert.match(placar, /mc-pause-btn btn btn-outline-secondary btn-sm/);
    assert.doesNotMatch(adminCss, /\.mc-action-btn\s*\{|\.mc-action-btn--start\s*\{|\.mc-action-btn--finish\s*\{|\.mc-duration-select\s*\{|\.mc-pause-btn\s*\{/);
});
