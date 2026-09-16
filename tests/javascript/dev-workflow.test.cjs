const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');

const root = path.resolve(__dirname, '../..');
const packageJson = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
const devTool = require(path.join(root, 'tools/dev.cjs'));

test('o script de desenvolvimento inicia o fluxo local completo', () => {
    assert.equal(packageJson.scripts.dev, 'node tools/dev.cjs');
    assert.match(fs.readFileSync(path.join(root, 'tools/dev.cjs'), 'utf8'), /-S.*\$\{phpHost\}:\$\{phpPort\}/);
});

test('o watcher identifica somente fontes que exigem rebuild de assets', () => {
    assert.equal(devTool.isAssetSource('resources/scss/shared.scss'), true);
    assert.equal(devTool.isAssetSource('resources/js/pages/login.js'), true);
    assert.equal(devTool.isAssetSource('resources/views/pages/login.php'), false);
    assert.equal(devTool.isAssetSource('src/Modules/Acesso/Service.php'), false);
});

test('o cliente de live reload é publicado pelo build e carregado apenas no desenvolvimento', () => {
    const client = path.join(root, 'resources/js/dev/live-reload.js');
    assert.equal(fs.existsSync(client), true);
    for (const view of [
        'resources/views/components/admin-head.php',
        'resources/views/components/aluno-head.php',
        'resources/views/pages/acesso/login.php',
    ]) {
        const content = fs.readFileSync(path.join(root, view), 'utf8');
        assert.match(content, /SGI_APP_ENV/);
        assert.match(content, /js\/dev\/live-reload\.js/);
    }
});
