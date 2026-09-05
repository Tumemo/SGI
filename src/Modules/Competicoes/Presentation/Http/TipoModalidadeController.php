<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Interclasses\Application\TipoModalidadeNaoEncontradoException;
use App\Modules\Interclasses\Application\TipoModalidadeService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use Throwable;

final class TipoModalidadeController
{
    public function __construct(private readonly TipoModalidadeService $service)
    {
    }

    /** @param array<string, string> $parameters */
    public function __invoke(Request $request, array $parameters = []): Response
    {
        $authorization = AccessGuard::authorize([0, 1, 2, 3]);
        if ($authorization !== null) {
            return $authorization;
        }

        try {
            return match ($request->method()) {
                'GET' => $this->list($request),
                'POST' => $this->create($request),
                'PUT' => $this->update($request),
                default => Response::json([
                    'success' => false,
                    'message' => 'Método não permitido',
                ], 405),
            };
        } catch (\InvalidArgumentException $exception) {
            return Response::json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 400);
        } catch (TipoModalidadeNaoEncontradoException) {
            return Response::json([
                'success' => false,
                'message' => 'Tipo de modalidade não encontrado.',
            ], 404);
        } catch (Throwable $exception) {
            error_log('Falha em TipoModalidadeController: ' . $exception->getMessage());

            return Response::json([
                'success' => false,
                'message' => 'Não foi possível processar o tipo de modalidade.',
            ], 500);
        }
    }

    private function list(Request $request): Response
    {
        return Response::json($this->service->listar([
            'id_tipo_modalidade' => (int) $request->query('id_tipo_modalidade', 0),
            'busca' => trim((string) $request->query('busca', '')),
        ]));
    }

    private function create(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $id = $this->service->criar($request->allInput());

        return Response::json([
            'success' => true,
            'message' => 'Tipo de modalidade cadastrado com sucesso!',
            'id_tipo_modalidade' => $id,
        ], 201);
    }

    private function update(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $this->service->atualizar($request->allInput());

        return Response::json([
            'success' => true,
            'message' => 'Tipo de modalidade atualizado com sucesso!',
        ]);
    }
}
