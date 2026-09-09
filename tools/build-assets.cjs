const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'public/assets');
const bundlesFile = path.join(root, 'tools/css-bundles.json');
for (const directory of ['resources/js', 'resources/css', 'resources/images', 'node_modules/bootstrap', 'node_modules/axios']) {
    if (!fs.existsSync(path.join(root, directory))) throw new Error(`Dependência ausente: ${directory}. Execute npm ci.`);
}
if (fs.existsSync(output)) {
    const expected = path.join(fs.realpathSync(root), 'public', 'assets');
    if (fs.lstatSync(output).isSymbolicLink() || fs.realpathSync(output) !== expected) throw new Error('Diretório de saída inesperado.');
    fs.rmSync(output, { recursive: true });
}
fs.mkdirSync(output, { recursive: true });
// CSS fonte é organizado em fragments e não deve ser publicado como dezenas
// de folhas independentes. Apenas os bundles abaixo entram em public/assets.
for (const directory of ['js', 'images']) {
    fs.cpSync(path.join(root, 'resources', directory), path.join(output, directory), { recursive: true });
}
fs.mkdirSync(path.join(output, 'css'), { recursive: true });

// Componha as folhas públicas a partir de uma ordem explícita. Os arquivos
// de origem continuam separados para facilitar manutenção e auditoria da
// cascata; as páginas carregam somente o pacote do próprio contexto.
const bundles = JSON.parse(fs.readFileSync(bundlesFile, 'utf8'));
for (const [name, entries] of Object.entries(bundles)) {
    if (!Array.isArray(entries) || entries.length === 0) throw new Error(`Bundle CSS inválido: ${name}`);
    const parts = entries.map((entry) => {
        const normalized = path.normalize(entry);
        const source = path.resolve(root, normalized);
        if (!source.startsWith(path.resolve(root, 'resources/css') + path.sep) || !fs.existsSync(source)) {
            throw new Error(`Fonte CSS inválida ou ausente: ${entry}`);
        }
        return `/* source: ${entry.replaceAll('\\', '/')} */\n${fs.readFileSync(source, 'utf8')}`;
    });
    fs.writeFileSync(path.join(output, 'css', `${name}.css`), parts.join('\n\n'));
}
const vendors = {
    'bootstrap': 'bootstrap/dist',
    'bootstrap-icons': 'bootstrap-icons/font',
    'fontawesome': '@fortawesome/fontawesome-free',
    'axios': 'axios/dist'
};
for (const [name, source] of Object.entries(vendors)) {
    const destination = path.join(output, 'vendor', name);
    const packageRoot = path.join(root, 'node_modules', source);
    if (name === 'fontawesome') {
        for (const dir of ['css', 'webfonts']) fs.cpSync(path.join(packageRoot, dir), path.join(destination, dir), { recursive: true });
    } else if (name === 'axios') {
        fs.mkdirSync(destination, { recursive: true });
        fs.copyFileSync(path.join(packageRoot, 'axios.min.js'), path.join(destination, 'axios.min.js'));
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
