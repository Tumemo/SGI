const assert = require('node:assert/strict');
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { test } = require('node:test');

const root = path.resolve(__dirname, '../..');
const composePath = path.join(root, 'compose.test.yml');

function lineValue(source, key) {
    const match = source.match(new RegExp(`^\\s+${key}:\\s+(.+?)\\s*$`, 'm'));
    return match ? match[1] : null;
}

test('executores de teste usam URLs na raiz e fixam a base vazia', () => {
    const compose = fs.readFileSync(composePath, 'utf8');
    assert.equal(lineValue(compose, 'SGI_TEST_BASE_URL'), 'http://sgi-web:8099');
    assert.equal(lineValue(compose, 'SGI_BASE_URL'), 'http://sgi-web:8099/');
    assert.equal(lineValue(compose, 'SGI_BASE_PATH'), '""');

    const localRunner = fs.readFileSync(path.join(root, 'tools/test-local.ps1'), 'utf8');
    const startServer = localRunner.match(/function Start-TestServer \{([\s\S]*?)\n\}/)?.[1];
    assert.ok(startServer, 'Start-TestServer precisa existir no executor local');
    assert.match(startServer, /SGI_APP_URL\s*=\s*"\$\(\$script:baseUrl\)\/"/);
    assert.match(startServer, /SGI_BASE_URL\s*=\s*"\$\(\$script:baseUrl\)\/"/);
    assert.match(startServer, /SGI_TEST_BASE_URL\s*=\s*\$script:baseUrl/);
    assert.match(startServer, /SGI_BASE_PATH\s*=\s*''/);
    assert.match(startServer, /\$script:baseUrl\s*=\s*"http:\/\/127\.0\.0\.1:/);
});

test('Compose publica o servidor de teste apenas no loopback por padrão', () => {
    const compose = fs.readFileSync(composePath, 'utf8');
    assert.match(
        compose,
        /- "\$\{SGI_TEST_BIND_ADDRESS:-127\.0\.0\.1\}:\$\{SGI_TEST_HOST_PORT:-8099\}:8099"/,
    );
});

test('Compose quality falha quando qualquer verificação obrigatória falha', () => {
    const compose = fs.readFileSync(composePath, 'utf8');
    const start = compose.indexOf('\n  quality:');
    const end = compose.indexOf('\n  integration:', start);
    assert.ok(start >= 0 && end > start, 'serviços quality e integration precisam existir');
    const qualityService = compose.slice(start, end);
    assert.match(qualityService, /- \|\r?\n\s{8,}set -e\r?\n/);
});

test('container prepara uploads e não vaza avisos PHP para respostas JSON', () => {
    const compose = fs.readFileSync(composePath, 'utf8');
    const entrypoint = fs.readFileSync(path.join(root, 'tools/docker-test-entrypoint.sh'), 'utf8');
    const dockerfile = fs.readFileSync(path.join(root, 'Dockerfile.test'), 'utf8');
    const phpIni = fs.readFileSync(path.join(root, 'tools/docker-php.ini'), 'utf8');

    assert.match(compose, /SGI_UPLOAD_TMP_DIR:\s*\/app\/test-results\/upload-tmp/);
    assert.match(compose, /- display_errors=0\r?\n/);
    assert.match(compose, /- display_startup_errors=0\r?\n/);
    assert.match(compose, /- log_errors=1\r?\n/);
    assert.match(compose, /- error_log=\/proc\/self\/fd\/2/);
    assert.match(entrypoint, /SGI_UPLOAD_TMP_DIR/);
    assert.match(entrypoint, /upload_tmp_dir=\*\)/);
    assert.match(entrypoint, /SGI_SESSION_DIR/);
    assert.match(entrypoint, /SGI_IMPORT_DIR/);
    assert.match(dockerfile, /COPY tools\/docker-php\.ini \/usr\/local\/etc\/php\/conf\.d\/99-sgi-runtime\.ini/);
    assert.match(phpIni, /display_errors\s*=\s*0/);
    assert.match(phpIni, /display_startup_errors\s*=\s*0/);
    assert.match(phpIni, /log_errors\s*=\s*1/);
    assert.match(phpIni, /error_log\s*=\s*\/proc\/self\/fd\/2/);
});

test('Compose resolve a base vazia mesmo com .env sintético conflitante', (t) => {
    const version = spawnSync('docker', ['compose', 'version'], {
        cwd: root,
        encoding: 'utf8',
        timeout: 10_000,
    });
    if (version.error?.code === 'ENOENT' || version.status !== 0) {
        t.diagnostic('Docker Compose indisponível; a asserção permanente do valor literal vazio foi executada acima.');
        return;
    }

    const temporaryRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'sgi-compose-config-'));
    const syntheticEnv = path.join(temporaryRoot, '.env.synthetic');
    const dockerConfig = path.join(temporaryRoot, 'docker-config');
    fs.mkdirSync(dockerConfig);
    fs.writeFileSync(syntheticEnv, 'SGI_BASE_PATH=/subdiretorio-da-instalacao\n');

    try {
        const environment = { ...process.env, DOCKER_CONFIG: dockerConfig };
        delete environment.SGI_BASE_PATH;
        const result = spawnSync('docker', [
            'compose',
            '--env-file', syntheticEnv,
            '-f', composePath,
            'config',
            '--format',
            'json',
        ], {
            cwd: root,
            encoding: 'utf8',
            env: environment,
            timeout: 20_000,
        });

        assert.equal(result.status, 0, result.stderr || result.error?.message || 'docker compose config falhou');
        const resolved = JSON.parse(result.stdout);
        for (const service of ['app', 'integration', 'browser', 'visual']) {
            assert.equal(resolved.services[service].environment.SGI_BASE_PATH, '', `${service} deve resolver para a raiz`);
            assert.equal(resolved.services[service].environment.SGI_TEST_BASE_URL, 'http://sgi-web:8099');
            assert.equal(resolved.services[service].environment.SGI_BASE_URL, 'http://sgi-web:8099/');
        }
    } finally {
        fs.rmSync(temporaryRoot, { recursive: true, force: true });
    }
});
