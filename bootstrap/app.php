<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use App\Shared\Http\AssetResponder;
use App\Shared\Http\ExceptionMiddleware;
use App\Shared\Http\Kernel;
use App\Shared\Http\MiddlewareStack;

$compatibility = require SGI_ROOT . '/config/routes/compatibility.php';

return new Kernel(
    SGI_ROOT,
    new MiddlewareStack([new ExceptionMiddleware()], require SGI_ROOT . '/config/routes.php'),
    new AssetResponder(SGI_ROOT, require SGI_ROOT . '/config/assets.php'),
    require SGI_ROOT . '/config/routes/web.php',
    $compatibility['aliases'],
);
