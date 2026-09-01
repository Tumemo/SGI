const { execFileSync } = require('node:child_process');
const path = require('node:path');

module.exports = async () => {
    if (process.env.SGI_E2E_RESET !== '1') return;

    const php = process.env.SGI_PHP_PATH || 'C:/xampp/php/php.exe';
    const suite = path.resolve(__dirname, '..', 'run_all.php');
    execFileSync(php, [suite], {
        cwd: path.resolve(__dirname, '..', '..'),
        stdio: 'inherit',
        timeout: 120_000
    });
};
