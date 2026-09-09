<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Shared\Http\AssetResponder;
use App\Shared\Http\ExceptionMiddleware;
use App\Shared\Http\Kernel;
use App\Shared\Http\MiddlewareStack;

return new Kernel(
    SGI_ROOT,
    new MiddlewareStack([new ExceptionMiddleware()], require SGI_ROOT . '/config/routes.php'),
    new AssetResponder(SGI_ROOT),
    require SGI_ROOT . '/config/routes/web.php',
);
