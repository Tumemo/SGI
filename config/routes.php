<?php

declare(strict_types=1);

use App\Modules\Eventos\Application\CategoriaService;
use App\Modules\Eventos\Application\LocalService;
use App\Modules\Competicoes\Application\ModalidadeService;
use App\Modules\Resultados\Application\RankingService;
use App\Shared\Http\HealthController;
use App\Modules\Competicoes\Application\TipoModalidadeService;
use App\Modules\Eventos\Infrastructure\MysqliCategoriaRepository;
use App\Modules\Eventos\Infrastructure\MysqliLocalRepository;
use App\Modules\Competicoes\Infrastructure\MysqliModalidadeRepository;
use App\Modules\Resultados\Infrastructure\MysqliRankingRepository;
use App\Modules\Competicoes\Infrastructure\MysqliTipoModalidadeRepository;
use App\Modules\Competicoes\Presentation\Http\TipoModalidadeController;
use App\Modules\Competicoes\Presentation\Http\ModalidadeController;
use App\Modules\Eventos\Presentation\Http\CategoriaController;
use App\Modules\Eventos\Presentation\Http\LocalController;
use App\Modules\Resultados\Presentation\Http\RankingController;
use App\Shared\Http\Router;

$router = new Router();
$router->get('/api/v1/health', new HealthController());
$router->get('/api/v1/session', [new \App\Modules\Acesso\Presentation\Http\SessionController(), 'show']);
$router->add(['GET', 'POST'], '/api/v1/logout', [new \App\Modules\Acesso\Presentation\Http\SessionController(), 'logout']);
$withDatabase = static function (callable $factory): callable {
    return static function (\App\Shared\Http\Request $request, array $parameters) use ($factory): \App\Shared\Http\Response {
        /** @var mysqli $conn */
        $conn = require dirname(__DIR__) . '/config/db.php';
        $controller = $factory($conn);

        return $controller($request, $parameters);
    };
};

$router->post('/api/v1/login', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Acesso\Presentation\Http\LoginController(new \App\Modules\Acesso\Application\LoginService(
        new \App\Modules\Acesso\Infrastructure\MysqliUsuarioRepository($conn),
        new \App\Modules\Acesso\Infrastructure\MysqliInterclasseRepository($conn),
    )),
));
$router->get('/api/v1/classificacao', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Resultados\Presentation\Http\ClassificacaoController(new \App\Modules\Resultados\Application\ClassificacaoService(new \App\Modules\Resultados\Infrastructure\MysqliClassificacaoRepository($conn))),
));
$router->add(['GET', 'POST'], '/api/v1/edicoes', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Eventos\Presentation\Http\EdicaoController(
        new \App\Modules\Eventos\Application\EdicaoService(new \App\Modules\Eventos\Infrastructure\MysqliEdicaoRepository($conn)),
        new \App\Modules\Eventos\Infrastructure\RegulamentoStorage(\App\Shared\Storage\StoragePaths::regulamentos()),
    ),
));
$router->post('/api/v1/senha', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Acesso\Presentation\Http\SenhaController(new \App\Modules\Acesso\Application\SenhaService(new \App\Modules\Acesso\Infrastructure\MysqliSenhaRepository($conn))),
));
$router->add(['GET', 'POST', 'PUT'], '/api/v1/artilheiros', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Competicoes\Presentation\Http\ArtilheiroController(
        new \App\Modules\Competicoes\Application\ArtilheiroService(new \App\Modules\Competicoes\Infrastructure\MysqliArtilheiroRepository($conn)),
        new \App\Modules\Competicoes\Infrastructure\MysqliArtilheiroQueries($conn),
        new \App\Modules\Acesso\Presentation\Http\CompetitionAccess(new \App\Modules\Acesso\Infrastructure\MysqliInterclasseRepository($conn)),
        new \App\Modules\Sincronizacao\Presentation\Http\MutationAction(new \App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore($conn)),
    ),
));
$router->add(['GET', 'POST'], '/api/v1/termos', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Acesso\Presentation\Http\TermosController(new \App\Modules\Acesso\Application\TermosService(new \App\Modules\Acesso\Infrastructure\MysqliTermosRepository($conn))),
));
$router->add(['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], '/api/v1/turmas', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Participantes\Presentation\Http\TurmaController(new \App\Modules\Participantes\Application\TurmaService(new \App\Modules\Participantes\Infrastructure\MysqliTurmaRepository($conn))),
));
$router->add(['GET', 'POST', 'DELETE', 'OPTIONS'], '/api/v1/arrecadacao', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Resultados\Presentation\Http\ArrecadacaoController(new \App\Modules\Resultados\Application\ArrecadacaoService(new \App\Modules\Resultados\Infrastructure\MysqliArrecadacaoRepository($conn))),
));
$router->add(['GET', 'POST', 'PUT'], '/api/v1/tipos-modalidade', $withDatabase(
    static fn (mysqli $conn): TipoModalidadeController => new TipoModalidadeController(
        new TipoModalidadeService(new MysqliTipoModalidadeRepository($conn)),
    ),
));
$router->add(['GET', 'POST', 'DELETE'], '/api/v1/foto', $withDatabase(
    static fn (mysqli $conn): \App\Modules\Acesso\Presentation\Http\FotoController => new \App\Modules\Acesso\Presentation\Http\FotoController(
        new \App\Modules\Acesso\Application\FotoService(
            new \App\Modules\Acesso\Infrastructure\MysqliPerfilRepository($conn),
            new \App\Modules\Acesso\Infrastructure\LocalFotoStorage(\App\Shared\Storage\StoragePaths::fotosUsuarios()),
        ),
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

$router->add(['GET', 'POST', 'DELETE', 'OPTIONS'], '/api/v1/ocorrencias-turmas', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Disciplina\Presentation\Http\OcorrenciaTurmaController(
        new \App\Modules\Disciplina\Application\OcorrenciaTurmaService(new \App\Modules\Disciplina\Infrastructure\MysqliOcorrenciaTurmaRepository($conn)),
        new \App\Modules\Acesso\Presentation\Http\CompetitionAccess(new \App\Modules\Acesso\Infrastructure\MysqliInterclasseRepository($conn)),
        new \App\Modules\Sincronizacao\Presentation\Http\MutationAction(new \App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore($conn)),
    ),
));

$router->add(['GET', 'POST', 'PUT'], '/api/v1/ocorrencias', $withDatabase(
    static fn (mysqli $conn) => new \App\Modules\Disciplina\Presentation\Http\OcorrenciaController(
        new \App\Modules\Disciplina\Application\OcorrenciaService(new \App\Modules\Disciplina\Infrastructure\MysqliOcorrenciaRepository($conn)),
        new \App\Modules\Disciplina\Infrastructure\MysqliOcorrenciaQueries($conn),
        new \App\Modules\Acesso\Presentation\Http\CompetitionAccess(new \App\Modules\Acesso\Infrastructure\MysqliInterclasseRepository($conn)),
        new \App\Modules\Sincronizacao\Presentation\Http\MutationAction(new \App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore($conn)),
    ),
));

$teamController = static fn (mysqli $conn) => new \App\Modules\Competicoes\Presentation\Http\EquipeController(
    new \App\Modules\Competicoes\Application\EquipeService(new \App\Modules\Competicoes\Infrastructure\MysqliEquipeRepository($conn)),
    new \App\Modules\Competicoes\Infrastructure\MysqliEquipeGateway($conn),
);
$router->add(['GET', 'POST', 'PUT', 'DELETE'], '/api/v1/equipes', $withDatabase($teamController));
$router->add(['POST', 'OPTIONS'], '/api/v1/equipes/gerar', $withDatabase(
    static fn (mysqli $conn) => [$teamController($conn), 'generate'],
));

return $router;
