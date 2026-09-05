<?php

declare(strict_types=1);

// O bootstrap é o único ponto de entrada do autoloader para o código legado.
// Os endpoints atuais podem continuar usando require_once sem duplicar a
// inicialização quando forem migrados para controllers.
require_once dirname(__DIR__) . '/vendor/autoload.php';

\App\Shared\Config\EnvLoader::load(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
\App\Shared\Http\CsrfGuard::protectCurrentApiMutation();
