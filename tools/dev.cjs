'use strict';

const fs = require('node:fs');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
const {createServer} = require('node:http');

const root = path.resolve(__dirname, '..');
const phpHost = process.env.SGI_DEV_HOST || '127.0.0.1';
const phpPort = parsePort(process.env.SGI_DEV_PORT, 8080);
const reloadHost = '127.0.0.1';
const reloadPort = 35729;
const phpCommand = process.env.SGI_DEV_PHP_PATH || 'php';
const assetRoots = [
    'resources/js',
    'resources/css',
    'resources/scss',
    'resources/images',
];

let buildTimer = null;
let reloadTimer = null;
let buildInProgress = false;
let buildPending = false;

function parsePort(value, fallback) {
    const parsed = Number.parseInt(value || '', 10);
    return Number.isInteger(parsed) && parsed > 0 && parsed < 65536 ? parsed : fallback;
}

function normalizePath(value) {
    return value.replaceAll('\\', '/').replace(/^\.\//, '').replace(/^\/+/, '');
}

function isAssetSource(relativePath) {
    const normalized = normalizePath(relativePath);
    return assetRoots.some((directory) => normalized === directory || normalized.startsWith(`${directory}/`));
}

function runBuild() {
    if (buildInProgress) {
        buildPending = true;
        return false;
    }

    buildInProgress = true;
    const result = spawnSync(process.execPath, [path.join(root, 'tools/build-assets.cjs')], {
        cwd: root,
        env: process.env,
        stdio: 'inherit',
    });
    buildInProgress = false;

    if (result.error || result.status !== 0) {
        const message = result.error ? result.error.message : `processo terminou com código ${result.status}`;
        console.error(`[sgi] build falhou: ${message}`);
    } else {
        broadcastReload('assets');
    }

    if (buildPending) {
        buildPending = false;
        scheduleBuild('alteração durante o build');
    }

    return !result.error && result.status === 0;
}

function scheduleBuild(reason) {
    if (buildTimer !== null) clearTimeout(buildTimer);
    buildTimer = setTimeout(() => {
        buildTimer = null;
        console.log(`[sgi] recompilando assets (${reason})...`);
        runBuild();
    }, 150);
}

function scheduleReload(reason) {
    if (reloadTimer !== null) clearTimeout(reloadTimer);
    reloadTimer = setTimeout(() => {
        reloadTimer = null;
        broadcastReload(reason);
    }, 150);
}

function createLiveReloadServer() {
    const clients = new Set();
    const server = createServer((request, response) => {
        const requestUrl = new URL(request.url || '/', `http://${request.headers.host || `${reloadHost}:${reloadPort}`}`);

        response.setHeader('Access-Control-Allow-Origin', '*');
        if (requestUrl.pathname === '/events' && request.method === 'GET') {
            response.writeHead(200, {
                'Cache-Control': 'no-cache, no-transform',
                Connection: 'keep-alive',
                'Content-Type': 'text/event-stream; charset=utf-8',
            });
            response.write(': connected\n\n');
            clients.add(response);
            request.on('close', () => clients.delete(response));
            return;
        }

        if (requestUrl.pathname === '/health' && request.method === 'GET') {
            response.writeHead(200, {'Content-Type': 'text/plain; charset=utf-8'});
            response.end('ok');
            return;
        }

        response.writeHead(404, {'Content-Type': 'text/plain; charset=utf-8'});
        response.end('Not found');
    });

    const heartbeat = setInterval(() => {
        for (const client of clients) client.write(': heartbeat\n\n');
    }, 30000);

    server.on('close', () => clearInterval(heartbeat));
    server.on('error', (error) => {
        console.error(`[sgi] servidor de live reload falhou: ${error.message}`);
        shutdown(1);
    });
    server.listen(reloadPort, reloadHost, () => {
        console.log(`[sgi] live reload disponível em http://${reloadHost}:${reloadPort}`);
    });

    return {clients, server};
}

let liveReloadClients = new Set();

function broadcastReload(reason) {
    const payload = JSON.stringify({reason, timestamp: Date.now()});
    for (const client of liveReloadClients) {
        try {
            client.write(`event: reload\ndata: ${payload}\n\n`);
        } catch {
            liveReloadClients.delete(client);
        }
    }
}

function watchTree(directory, onChange) {
    const watchers = new Map();

    const addDirectory = (currentDirectory) => {
        const resolvedDirectory = path.resolve(currentDirectory);
        if (watchers.has(resolvedDirectory)) return;

        let entries;
        try {
            entries = fs.readdirSync(resolvedDirectory, {withFileTypes: true});
        } catch {
            return;
        }

        let watcher;
        try {
            watcher = fs.watch(resolvedDirectory, (eventType, filename) => {
                const name = filename ? filename.toString() : '';
                const changedPath = name ? path.join(resolvedDirectory, name) : resolvedDirectory;
                const relativePath = normalizePath(path.relative(root, changedPath));

                try {
                    if (fs.statSync(changedPath).isDirectory()) {
                        if (eventType === 'rename') {
                            addDirectory(changedPath);
                        }
                        return;
                    }
                } catch {
                    // O arquivo pode ter sido removido entre o evento e a leitura.
                }

                onChange(relativePath, eventType);
            });
        } catch (error) {
            throw new Error(`não foi possível observar ${resolvedDirectory}: ${error.message}`);
        }

        watchers.set(resolvedDirectory, watcher);
        for (const entry of entries) {
            if (entry.isDirectory()) addDirectory(path.join(resolvedDirectory, entry.name));
        }
    };

    addDirectory(directory);
    return () => {
        for (const watcher of watchers.values()) watcher.close();
        watchers.clear();
    };
}

let phpProcess = null;
let stopRequested = false;
let stopWatchers = () => {};
let liveReloadServer = null;

function shutdown(exitCode) {
    if (stopRequested) return;
    stopRequested = true;
    if (buildTimer !== null) clearTimeout(buildTimer);
    if (reloadTimer !== null) clearTimeout(reloadTimer);
    stopWatchers();
    if (liveReloadServer !== null) liveReloadServer.close();
    for (const client of liveReloadClients) client.end();
    if (phpProcess !== null && !phpProcess.killed) phpProcess.kill();
    process.exit(exitCode);
}

function start() {
    console.log('[sgi] build inicial dos assets...');
    if (!runBuild()) {
        shutdown(1);
        return;
    }

    const liveReload = createLiveReloadServer();
    liveReloadClients = liveReload.clients;
    liveReloadServer = liveReload.server;

    stopWatchers = [
        watchTree(path.join(root, 'resources'), (relativePath) => {
            if (isAssetSource(relativePath)) scheduleBuild(relativePath);
            else scheduleReload(relativePath);
        }),
        watchTree(path.join(root, 'src'), (relativePath) => scheduleReload(relativePath)),
        watchTree(path.join(root, 'config'), (relativePath) => scheduleReload(relativePath)),
    ].reduce((stop, current) => () => {
        stop();
        current();
    }, () => {});

    phpProcess = spawn(phpCommand, ['-S', `${phpHost}:${phpPort}`, '-t', 'public', 'public/index.php'], {
        cwd: root,
        env: process.env,
        stdio: 'inherit',
    });
    phpProcess.on('error', (error) => {
        console.error(`[sgi] não foi possível iniciar o PHP: ${error.message}`);
        shutdown(1);
    });
    phpProcess.on('exit', (code, signal) => {
        if (!stopRequested) {
            console.error(`[sgi] servidor PHP terminou (${signal || `código ${code}`}).`);
            shutdown(code || 1);
        }
    });

    console.log(`[sgi] aplicação disponível em http://${phpHost}:${phpPort}/`);
    console.log('[sgi] altere arquivos em resources/, src/ ou config/; o navegador será recarregado automaticamente.');
}

if (require.main === module) {
    process.once('SIGINT', () => shutdown(0));
    process.once('SIGTERM', () => shutdown(0));
    try {
        start();
    } catch (error) {
        console.error(`[sgi] ${error.message}`);
        shutdown(1);
    }
}

module.exports = {isAssetSource, normalizePath, parsePort};
