<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('SGI_ROOT')) {
    define('SGI_ROOT', dirname(__DIR__));
}
\App\Shared\Config\EnvLoader::load(SGI_ROOT . '/.env');

$debug = \App\Shared\Config\Env::get('SGI_APP_DEBUG', '0') === '1';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
