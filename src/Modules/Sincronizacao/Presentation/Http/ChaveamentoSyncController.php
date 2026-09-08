<?php

declare(strict_types=1);

namespace App\Modules\Sincronizacao\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Sincronizacao\Infrastructure\MysqliChaveamentoSyncGateway;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ChaveamentoSyncController
{
    public function __construct(
        private readonly MysqliChaveamentoSyncGateway $gateway,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return Response::json(['success' => false, 'message' => 'Método não permitido. Utilize POST.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
            return $denied;
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $data = $request->allInput();
        $modalityId = (int) ($data['id_modalidade'] ?? 0);
        $type = (string) ($data['tipo_modalidade'] ?? '');
        if ($modalityId <= 0 || $type === '') {
            return Response::json(['success' => false, 'message' => 'Informe o ID e o tipo da modalidade.'], 400);
        }
        $edition = $this->gateway->editionOfModality($modalityId);
        if ($edition === null) {
            return Response::json(['success' => false, 'message' => 'Modalidade não encontrada.'], 404);
        }
        if (($denied = $this->access->authorize($edition)) !== null) {
            return $denied;
        }
        return $this->mutations->run($request, 'sincronizar_chaveamento', function () use ($modalityId, $type, $data): Response {
            try {
                return Response::json($this->gateway->sync($modalityId, $type, $data));
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => 'Erro durante a sincronização: ' . $exception->getMessage()], 400);
            } catch (\App\Modules\Competicoes\Application\ModalidadeNaoEncontradaException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
            } catch (\mysqli_sql_exception $exception) {
                error_log('Falha de persistência na sincronização de chaveamento: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível sincronizar o chaveamento.'], 500);
            } catch (\RuntimeException $exception) {
                error_log('Falha na sincronização de chaveamento: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível sincronizar o chaveamento.'], 500);
            } catch (\Throwable $exception) {
                error_log('Falha na sincronização de chaveamento: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível sincronizar o chaveamento.'], 500);
            }
        });
    }
}
