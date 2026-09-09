<?php

declare(strict_types=1);

namespace SGITests\Integration;

use App\Modules\Acesso\Domain\InterclasseRepository;
use App\Modules\Acesso\Application\UsuarioService;
use App\Modules\Acesso\Infrastructure\LocalFotoStorage;
use App\Modules\Acesso\Infrastructure\MysqliUsuarioConsultaRepository;
use App\Modules\Acesso\Infrastructure\MysqliUsuarioManagementRepository;
use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Acesso\Presentation\Http\UsuarioController;
use App\Modules\Competicoes\Application\ResultadoService;
use App\Modules\Competicoes\Infrastructure\MysqliEquipePadraoRepositoryAdapter;
use App\Modules\Competicoes\Infrastructure\MysqliPartidaGateway;
use App\Modules\Competicoes\Presentation\Http\ResultadoController;
use App\Modules\Resultados\Infrastructure\MysqliHistoricoTurmaRepository;
use App\Modules\Resultados\Application\PontuacaoService;
use App\Modules\Resultados\Infrastructure\MysqliPodioRepository;
use App\Modules\Resultados\Presentation\Http\HistoricoTurmaController;
use App\Modules\Sincronizacao\Infrastructure\MysqliMutationStore;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\Request;
use App\Shared\Http\SessionManager;
use App\Shared\Database\MysqliTransactionRunner;
use App\Shared\Storage\StoragePaths;
use SGITests\Support\Assertions;

final class ExceptionEnvelopeTest
{
    public static function run(): void
    {
        echo "\n  [Suite 0.3: Envelopes seguros para falhas internas]\n";

        $previousHandler = set_error_handler(
            static function (int $severity, string $message, string $file, int $line): bool {
                return true;
            },
            E_WARNING,
        );
        try {
            SessionManager::start();
            $_SESSION = [
                'logado' => true,
                'id_usuario' => 1,
                'id' => 1,
                'nivel' => 1,
                'id_interclasse' => 1,
            ];

            $connection = new \mysqli(
                getenv('SGI_DB_HOST') ?: '127.0.0.1',
                getenv('SGI_DB_USER') ?: 'root',
                getenv('SGI_DB_PASSWORD') ?: '',
                'information_schema',
                (int) (getenv('SGI_DB_PORT') ?: 3306),
            );
            $connection->set_charset('utf8mb4');

            $editions = new class () implements InterclasseRepository {
                public function findActiveId(): ?int
                {
                    return 1;
                }
            };
            $access = new CompetitionAccess($editions);

            $userQueries = new MysqliUsuarioConsultaRepository($connection);
            $users = (new UsuarioController(
                $userQueries,
                new UsuarioService(
                    $userQueries,
                    new MysqliUsuarioManagementRepository($connection),
                    new LocalFotoStorage(StoragePaths::fotosUsuarios()),
                ),
                new \App\Modules\Acesso\Application\UsuarioAdministrativoService(
                    new \App\Modules\Acesso\Infrastructure\MysqliUsuarioAdministrativoRepository($connection),
                ),
                new \App\Modules\Eventos\Infrastructure\MysqliEdicaoConsultaRepository($connection),
                new \App\Modules\Eventos\Application\EdicaoService(
                    new \App\Modules\Eventos\Infrastructure\MysqliEdicaoRepository(
                        $connection,
                        new MysqliEquipePadraoRepositoryAdapter($connection),
                    ),
                ),
            ))(
                new Request('GET', '/api/v1/usuarios'),
            );
            $usersBody = self::decode($users->body());
            Assertions::assert(
                'Falha MySQLi em usuários retorna HTTP 500 e mensagem estável',
                $users->status() === 500
                && ($usersBody['status'] ?? '') === 'erro'
                && ($usersBody['mensagem'] ?? '') === 'Não foi possível processar usuário.'
                && !str_contains($users->body(), 'information_schema'),
            );

            $result = (new ResultadoController(
                new ResultadoService(
                    new MysqliPartidaGateway($connection),
                    new MysqliTransactionRunner($connection),
                    new PontuacaoService(new MysqliPodioRepository($connection)),
                ),
                $access,
                new MutationAction(new MysqliMutationStore($connection)),
            ))(new Request(
                'POST',
                '/api/v1/resultados',
                [],
                [],
                [],
                [],
                [],
                json_encode(['id_jogo' => 1, 'resultados' => [['id_equipe' => 1, 'gols' => 1]]], JSON_THROW_ON_ERROR),
            ));
            $resultBody = self::decode($result->body());
            Assertions::assert(
                'Falha MySQLi em resultado não é convertida em 404 nem exposta',
                $result->status() === 500
                && ($resultBody['success'] ?? true) === false
                && ($resultBody['message'] ?? '') === 'Não foi possível lançar o resultado.'
                && !str_contains($result->body(), 'information_schema'),
            );

            $history = (new HistoricoTurmaController(new MysqliHistoricoTurmaRepository($connection)))(
                new Request('GET', '/api/v1/historico-turma', ['id_turma' => 1, 'id_interclasse' => 1]),
            );
            $historyBody = self::decode($history->body());
            Assertions::assert(
                'Falha MySQLi em histórico não é convertida em 404 nem exposta',
                $history->status() === 500
                && ($historyBody['success'] ?? true) === false
                && ($historyBody['message'] ?? '') === 'Não foi possível consultar o histórico.'
                && !str_contains($history->body(), 'information_schema'),
            );

            $invalid = (new ResultadoController(
                new ResultadoService(
                    new MysqliPartidaGateway($connection),
                    new MysqliTransactionRunner($connection),
                    new PontuacaoService(new MysqliPodioRepository($connection)),
                ),
                $access,
                new MutationAction(new MysqliMutationStore($connection)),
            ))(new Request('POST', '/api/v1/resultados', [], [], [], [], [], '{"id_jogo":1}'));
            Assertions::assert(
                'Validação continua usando HTTP 400 sem tocar a persistência',
                $invalid->status() === 400
                && (self::decode($invalid->body())['message'] ?? '') === 'Dados insuficientes.',
            );

            $connection->close();
            $_SESSION = [];
        } finally {
            if ($previousHandler !== null) {
                restore_error_handler();
            }
        }
    }

    /** @return array<string, mixed> */
    private static function decode(string $body): array
    {
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
