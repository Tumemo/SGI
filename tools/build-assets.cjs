const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const sass = require('sass');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'public/assets');
const bundlesFile = path.join(root, 'tools/css-bundles.json');
const cssOnly = process.argv.includes('--css-only');
for (const directory of ['resources/js', 'resources/css', 'resources/scss', 'resources/images', 'node_modules/bootstrap', 'node_modules/axios']) {
    if (!fs.existsSync(path.join(root, directory))) throw new Error(`Dependência ausente: ${directory}. Execute npm ci.`);
}
const compileSource = (entry) => {
    const normalized = path.normalize(entry);
    const source = path.resolve(root, normalized);
    const allowedRoots = [path.resolve(root, 'resources/css'), path.resolve(root, 'resources/scss')];
    if (!allowedRoots.some((allowedRoot) => source.startsWith(allowedRoot + path.sep)) || !fs.existsSync(source)) {
        throw new Error(`Fonte CSS inválida ou ausente: ${entry}`);
    }
    if (path.extname(source).toLowerCase() === '.scss') {
        return sass.compile(source, {loadPaths: [path.join(root, 'node_modules')], style: 'compressed', quietDeps: true})
            .css.replace(/^\uFEFF/, '');
    }
    return fs.readFileSync(source, 'utf8');
};

const bundles = JSON.parse(fs.readFileSync(bundlesFile, 'utf8'));
const compiledBundles = {};
for (const [name, entries] of Object.entries(bundles)) {
    if (!Array.isArray(entries) || entries.length === 0) throw new Error(`Bundle CSS inválido: ${name}`);
    compiledBundles[name] = entries.map((entry) => `/* source: ${entry.replaceAll('\\', '/') } */\n${compileSource(entry)}`).join('\n');
}

if (fs.existsSync(output)) {
    const expected = path.join(fs.realpathSync(root), 'public', 'assets');
    if (fs.lstatSync(output).isSymbolicLink() || fs.realpathSync(output) !== expected) throw new Error('Diretório de saída inesperado.');
} else {
    fs.mkdirSync(output, { recursive: true });
}
// CSS fonte é organizado em fragments e não deve ser publicado como dezenas
// de folhas independentes. Apenas os bundles abaixo entram em public/assets.
function copyTree(source, destination) {
    fs.mkdirSync(destination, {recursive: true});
    for (const entry of fs.readdirSync(source, {withFileTypes: true})) {
        const from = path.join(source, entry.name);
        const to = path.join(destination, entry.name);
        if (entry.isDirectory()) copyTree(from, to);
        if (entry.isFile()) copyIfChanged(from, to);
    }
}

function copyIfChanged(source, destination) {
    const destinationExists = fs.existsSync(destination);
    const changed = !destinationExists
        || crypto.createHash('sha256').update(fs.readFileSync(source)).digest('hex')
            !== crypto.createHash('sha256').update(fs.readFileSync(destination)).digest('hex');
    if (!changed) return;
    try {
        fs.copyFileSync(source, destination);
    } catch (error) {
        // Um servidor local pode manter um asset aberto no Windows. Nesse
        // caso, preserve o arquivo servido e permita que o restante do build
        // termine; em CI/produção o arquivo é atualizado normalmente.
        if (!['EPERM', 'EBUSY', 'EACCES'].includes(error.code)) throw error;
    }
}

if (!cssOnly) {
    for (const directory of ['js', 'images']) {
        copyTree(path.join(root, 'resources', directory), path.join(output, directory));
    }
}
fs.mkdirSync(path.join(output, 'css'), { recursive: true });

// Componha as folhas públicas a partir de uma ordem explícita. Os arquivos
// de origem continuam separados para facilitar manutenção e auditoria da
// cascata; as páginas carregam somente o pacote do próprio contexto.
for (const [name, css] of Object.entries(compiledBundles)) {
    fs.writeFileSync(path.join(output, 'css', `${name}.css`), css);
}
const vendors = {
    'bootstrap': 'bootstrap/dist',
    'bootstrap-icons': 'bootstrap-icons/font',
    'fontawesome': '@fortawesome/fontawesome-free',
    'axios': 'axios/dist'
};
if (!cssOnly) for (const [name, source] of Object.entries(vendors)) {
    const destination = path.join(output, 'vendor', name);
    const packageRoot = path.join(root, 'node_modules', source);
    if (name === 'fontawesome') {
        for (const dir of ['css', 'webfonts']) fs.cpSync(path.join(packageRoot, dir), path.join(destination, dir), { recursive: true });
    } else if (name === 'axios') {
        fs.mkdirSync(destination, { recursive: true });
        fs.copyFileSync(path.join(packageRoot, 'axios.min.js'), path.join(destination, 'axios.min.js'));
    } else if (name === 'bootstrap') {
        // O CSS do Bootstrap é compilado em bootstrap-theme.css; publique
        // somente o bundle JavaScript usado pelos componentes interativos.
        const legacyCss = path.join(destination, 'css');
        if (fs.existsSync(legacyCss)) fs.rmSync(legacyCss, { recursive: true, force: true });
        fs.mkdirSync(path.join(destination, 'js'), { recursive: true });
        fs.copyFileSync(path.join(packageRoot, 'js', 'bootstrap.bundle.min.js'), path.join(destination, 'js', 'bootstrap.bundle.min.js'));
    } else {
        fs.cpSync(packageRoot, destination, { recursive: true });
    }
    const packageName = name === 'fontawesome' ? '@fortawesome/fontawesome-free' : name;
    for (const license of ['LICENSE', 'LICENSE.txt', 'LICENSE.md']) {
        const file = path.join(root, 'node_modules', packageName, license);
        if (fs.existsSync(file)) fs.copyFileSync(file, path.join(destination, license));
    }
}
const manifest = {};
function walk(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name))) {
        const file = path.join(directory, entry.name);
        if (entry.isDirectory()) walk(file);
        else manifest[path.relative(output, file).replaceAll('\\', '/')] = crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');
    }
}
walk(output);
delete manifest['manifest.json'];
fs.writeFileSync(path.join(output, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
console.log(`Assets preparados: ${Object.keys(manifest).length} arquivos.`);
