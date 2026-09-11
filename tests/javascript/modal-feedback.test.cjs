const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '../..');
const jsRoot = path.join(root, 'resources', 'js');

function jsFiles(dir) {
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
        const file = path.join(dir, entry.name);
        return entry.isDirectory() ? jsFiles(file) : (entry.name.endsWith('.js') ? [file] : []);
    });
}

test('application JavaScript does not reintroduce native alert or confirm calls', () => {
    const nativeCall = /(^|[^.\w$])(?:alert|confirm|prompt)\s*\(/;
    const offenders = jsFiles(jsRoot)
        .filter(file => !file.endsWith(path.join('shared', 'bootstrap-feedback.js')))
        .filter(file => nativeCall.test(fs.readFileSync(file, 'utf8')))
        .map(file => path.relative(root, file));
    assert.deepEqual(offenders, []);
});

test('shared feedback exposes modal APIs and constructs message content safely', () => {
    const source = fs.readFileSync(path.join(jsRoot, 'shared', 'bootstrap-feedback.js'), 'utf8');
    assert.match(source, /global\.SGI\.alert\s*=/);
    assert.match(source, /global\.SGI\.confirm\s*=/);
    assert.match(source, /textContent\s*=\s*options\.mensagem/);
    assert.match(source, /data-bs-backdrop.*static/);
});
