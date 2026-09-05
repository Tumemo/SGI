<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Presentation\Http;

use App\Modules\Interclasses\Application\CategoriaInativaException;
use App\Modules\Interclasses\Application\CategoriaNaoEncontradaException;
use App\Modules\Interclasses\Application\CategoriaService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use Throwable;

final class CategoriaController
{
    public function __construct(private readonly CategoriaService $service)
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
                'GET' => Response::json($this->service->listar([
                    'id_categoria' => (int) $request->query('id_categoria', 0),
                    'id_interclasse' => (int) $request->query('id_interclasse', 0),
                    'busca' => trim((string) $request->query('busca', '')),
                ])),
                'POST' => $this->create($request),
                'PUT' => $this->update($request),
                'DELETE' => $this->delete($request),
                default => Response::json(['success' => false, 'message' => 'Método não permitido'], 405),
            };
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (CategoriaNaoEncontradaException) {
            return Response::json(['success' => false, 'message' => 'Categoria não encontrada.'], 404);
        } catch (CategoriaInativaException) {
            return Response::json([
                'success' => false,
                'message' => 'Esta categoria está desativada e não pode ser alterada.',
            ], 403);
        } catch (Throwable $exception) {
            error_log('Falha em CategoriaController: ' . $exception->getMessage());

            return Response::json(['success' => false, 'message' => 'Não foi possível processar a categoria.'], 500);
        }
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
            'message' => 'Categoria cadastrada com sucesso!',
            'id_categoria' => $id,
        ], 201);
    }

    private function update(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $this->service->atualizar($request->allInput());

        return Response::json(['success' => true, 'message' => 'Categoria atualizada com sucesso!']);
    }

    private function delete(Request $request): Response
    {
        $authorization = AccessGuard::authorize([0]);
        if ($authorization !== null) {
            return $authorization;
        }

        $payload = $request->allInput();
        $this->service->excluir((int) ($payload['id_categoria'] ?? $request->query('id_categoria', 0)));

        return Response::json(['success' => true, 'message' => 'Categoria excluída com sucesso!']);
    }
}
