<?php

declare(strict_types=1);

use App\Modules\Interclasses\Application\CategoriaService;
use App\Modules\Interclasses\Application\LocalService;
use App\Modules\Interclasses\Application\ModalidadeService;
use App\Modules\Interclasses\Application\RankingService;
use App\Shared\Http\HealthController;
use App\Modules\Interclasses\Application\TipoModalidadeService;
use App\Modules\Interclasses\Infrastructure\MysqliCategoriaRepository;
use App\Modules\Interclasses\Infrastructure\MysqliLocalRepository;
use App\Modules\Interclasses\Infrastructure\MysqliModalidadeRepository;
use App\Modules\Interclasses\Infrastructure\MysqliRankingRepository;
use App\Modules\Interclasses\Infrastructure\MysqliTipoModalidadeRepository;
use App\Modules\Competicoes\Presentation\Http\TipoModalidadeController;
use App\Modules\Competicoes\Presentation\Http\ModalidadeController;
use App\Modules\Eventos\Presentation\Http\CategoriaController;
use App\Modules\Eventos\Presentation\Http\LocalController;
use App\Modules\Resultados\Presentation\Http\RankingController;
use App\Shared\Http\Router;

$router = new Router();
$router->get('/api/v1/health', new HealthController());
$withDatabase = static function (callable $factory): callable {
    return static function (\App\Shared\Http\Request $request, array $parameters) use ($factory): \App\Shared\Http\Response {
        /** @var mysqli $conn */
        $conn = require dirname(__DIR__) . '/config/db.php';
        $controller = $factory($conn);

        return $controller($request, $parameters);
    };
};

$router->add(['GET', 'POST', 'PUT'], '/api/v1/tipos-modalidade', $withDatabase(
    static fn (mysqli $conn): TipoModalidadeController => new TipoModalidadeController(
        new TipoModalidadeService(new MysqliTipoModalidadeRepository($conn)),
    ),
));
$router->add(['GET', 'POST', 'PUT', 'DELETE'], '/api/v1/categorias', $withDatabase(
    static fn (mysqli $conn): CategoriaController => new CategoriaController(
        new CategoriaService(new MysqliCategoriaRepository($conn)),
    ),
));
$router->add(['GET', 'POST', 'PUT', 'DELETE'], '/api/v1/locais', $withDatabase(
    static fn (mysqli $conn): LocalController => new LocalController(
        new LocalService(new MysqliLocalRepository($conn)),
    ),
));
$router->add(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], '/api/v1/modalidades', $withDatabase(
    static fn (mysqli $conn): ModalidadeController => new ModalidadeController(
        new ModalidadeService(new MysqliModalidadeRepository($conn)),
    ),
));
$router->add(['GET', 'PUT', 'OPTIONS'], '/api/v1/ranking', $withDatabase(
    static fn (mysqli $conn): RankingController => new RankingController(
        new RankingService(new MysqliRankingRepository($conn)),
    ),
));

return $router;
