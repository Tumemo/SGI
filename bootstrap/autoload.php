<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('SGI_ROOT')) {
    define('SGI_ROOT', dirname(__DIR__));
}
\App\Shared\Config\EnvLoader::load(SGI_ROOT . '/.env');
