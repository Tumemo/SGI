const { execFileSync } = require('node:child_process');
const path = require('node:path');

module.exports = async () => {
    if (process.env.SGI_BROWSER_REQUIRES_DATABASE !== '0' && process.env.SGI_TEST_DB_RUNTIME !== 'container') {
        throw new Error(
            'Os testes de navegador dependentes do SGI exigem um banco em container. '
            + 'Use tools/test-docker.ps1/.sh ou tools/test-local.ps1.',
        );
    }

    if (process.env.SGI_E2E_RESET !== '1') return;

    const php = process.env.SGI_PHP_PATH || 'C:/xampp/php/php.exe';
    const suite = path.resolve(__dirname, '..', 'run_all.php');
    execFileSync(php, [suite], {
        cwd: path.resolve(__dirname, '..', '..'),
        stdio: 'inherit',
        timeout: 120_000
    });
};
