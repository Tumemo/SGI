<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Presentation\Http;

use App\Modules\Eventos\Application\LocalNaoEncontradoException;
use App\Modules\Eventos\Application\LocalService;
use App\Modules\Eventos\Application\LocalVinculadoException;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use Throwable;

final class LocalController
{
    public function __construct(private readonly LocalService $service)
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
            if ($request->method() === 'GET' && (int) ($_SESSION['nivel'] ?? -1) === 2
                && (int) ($_SESSION['id_interclasse'] ?? 0) <= 0) {
                return Response::json(['success' => false, 'message' => 'Nenhuma edição ativa.'], 403);
            }
            return match ($request->method()) {
                'GET' => Response::json([
                    'success' => true,
                    'data' => $this->service->listar([
                        'id_local' => (int) $request->query('id_local', 0),
                        'disponivel' => (string) $request->query('disponivel', ''),
                        'busca' => trim((string) $request->query('busca', '')),
                        'id_interclasse' => (int) ($_SESSION['nivel'] ?? -1) === 2
                            ? (int) ($_SESSION['id_interclasse'] ?? 0)
                            : (int) $request->query('id_interclasse', 0),
                    ]),
                ]),
                'POST' => $this->create($request),
                'PUT' => $this->update($request),
                'DELETE' => $this->delete($request),
                default => Response::json(['success' => false, 'message' => 'Método não permitido'], 405),
            };
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (LocalNaoEncontradoException) {
            return Response::json(['success' => false, 'message' => 'Local não encontrado.'], 404);
        } catch (LocalVinculadoException) {
            return Response::json([
                'success' => false,
                'message' => 'Não é possível excluir este local pois existem jogos vinculados a ele.',
            ], 409);
        } catch (\Throwable $exception) {
            error_log('Falha em LocalController: ' . $exception->getMessage());

            return Response::json(['success' => false, 'message' => 'Não foi possível processar o local.'], 500);
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
            'message' => 'Local cadastrado com sucesso!',
            'id_local' => $id,
        ], 201);
    }

    private function update(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $this->service->atualizar($request->allInput());

        return Response::json(['success' => true, 'message' => 'Local atualizado com sucesso!']);
    }

    private function delete(Request $request): Response
    {
        $authorization = AccessGuard::authorize([0]);
        if ($authorization !== null) {
            return $authorization;
        }

        $payload = $request->allInput();
        $this->service->excluir((int) ($payload['id_local'] ?? $request->query('id_local', 0)));

        return Response::json(['success' => true, 'message' => 'Local excluído com sucesso.']);
    }
}
