<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Presentation\Http;

use App\Modules\Resultados\Application\TurmaHistoricoNaoEncontradaException;
use App\Modules\Resultados\Infrastructure\MysqliHistoricoTurmaRepository;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class HistoricoTurmaController
{
    public function __construct(private readonly MysqliHistoricoTurmaRepository $repository)
    {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() !== 'GET') {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        $classId = (int) $request->query('id_turma', 0);
        $editionId = (int) $request->query('id_interclasse', 0);
        if ($classId <= 0 || $editionId <= 0) {
            return Response::json(['success' => false, 'message' => 'id_turma e id_interclasse são obrigatórios.'], 400);
        }
        if ((int) ($_SESSION['nivel'] ?? -1) === 3) {
            $userId = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
            if (!$this->repository->studentBelongsToClass($userId, $classId, $editionId)) {
                return Response::json(['success' => false, 'message' => 'Você não tem acesso ao histórico desta turma.'], 403);
            }
        }
        try {
            return Response::json($this->repository->find($classId, $editionId));
        } catch (TurmaHistoricoNaoEncontradaException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
        } catch (\mysqli_sql_exception $exception) {
            error_log('Falha de persistência ao consultar histórico da turma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível consultar o histórico.'], 500);
        } catch (\RuntimeException $exception) {
            error_log('Falha ao consultar histórico da turma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível consultar o histórico.'], 500);
        } catch (\Throwable $exception) {
            error_log('Falha ao consultar histórico da turma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível consultar o histórico.'], 500);
        }
    }

}
