<?php

declare(strict_types=1);

// O bootstrap é o único ponto de entrada do autoloader para o código legado.
// Os endpoints atuais podem continuar usando require_once sem duplicar a
// inicialização quando forem migrados para controllers.
require_once dirname(__DIR__) . '/bootstrap/autoload.php';
