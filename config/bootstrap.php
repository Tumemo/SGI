<?php

declare(strict_types=1);

// Compatibilidade para scripts internos que ainda precisam carregar o autoloader.
// O fluxo HTTP usa exclusivamente bootstrap/app.php e os controladores versionados.
require_once dirname(__DIR__) . '/bootstrap/autoload.php';
