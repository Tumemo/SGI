<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\AgendaRevisaoException;
use App\Modules\Competicoes\Infrastructure\MysqliAgendamentoBlocoRepository;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class AgendamentoBlocoController
{
    public function __construct(
        private readonly MysqliAgendamentoBlocoRepository $repository,
        private readonly CompetitionAccess $access,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!in_array($request->method(), ['GET', 'POST'], true)) {
            return Response::json(['success' => false, 'message' => 'Método não permitido. Utilize GET ou POST.'], 405);
        }
        if ($request->method() === 'GET') {
            if (($denied = $this->access->authorize()) !== null) {
                return $denied;
            }
            $edition = (int) $request->query('id_interclasse', $_SESSION['id_interclasse'] ?? 0);
            if ($edition <= 0) {
                return Response::json(['success' => false, 'message' => 'Informe a edição do interclasse.'], 422);
            }
            if (($denied = $this->access->authorize($edition)) !== null) {
                return $denied;
            }
            $modality = $request->query('id_modalidade');
            $modalityId = $modality === null || $modality === '' ? null : (int) $modality;
            if ($modalityId !== null && $modalityId <= 0) {
                return Response::json(['success' => false, 'message' => 'A modalidade informada é inválida.'], 422);
            }

            return Response::json($this->repository->listReservations($edition, $modalityId));
        }
        if (($denied = AccessGuard::requireWrite()) !== null) {
            return $denied;
        }
        $data = $request->allInput();
        try {
            $edition = (int) ($data['id_interclasse'] ?? 0);
            if ($edition <= 0) {
                $edition = (int) ($_SESSION['id_interclasse'] ?? 0);
            }
            if ($edition <= 0) {
                return Response::json(['success' => false, 'message' => 'Informe a edição do interclasse.'], 422);
            }
            if (($denied = $this->access->authorize($edition)) !== null) {
                return $denied;
            }
            $action = (string) ($data['acao'] ?? 'simular');
            if ($action === 'simular') {
                $result = $this->repository->simulate($data, $edition);
                return Response::json(array_merge(['success' => true, 'message' => 'Prévia calculada.'], $result['resultado']));
            }
            if ($action === 'confirmar') {
                $userId = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
                return Response::json($this->repository->confirm($data, $edition, $userId));
            }
            return Response::json(['success' => false, 'message' => 'Ação de agendamento inválida.'], 422);
        } catch (AgendaRevisaoException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 409);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            error_log('Falha no agendamento em bloco: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível processar o agendamento em bloco.'], 500);
        }
    }
}
