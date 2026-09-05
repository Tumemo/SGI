<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Interclasses\Application\ModalidadeNaoEncontradaException;
use App\Modules\Interclasses\Application\ModalidadeService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use Throwable;

final class ModalidadeController
{
    public function __construct(private readonly ModalidadeService $service)
    {
    }

    /** @param array<string, string> $parameters */
    public function __invoke(Request $request, array $parameters = []): Response
    {
        if ($request->method() === 'OPTIONS') {
            return Response::empty();
        }

        $authorization = AccessGuard::authorize([0, 1, 2, 3]);
        if ($authorization !== null) {
            return $authorization;
        }

        try {
            return match ($request->method()) {
                'GET' => $this->list($request),
                'POST' => $this->create($request),
                'PUT' => $this->update($request),
                'DELETE' => $this->delete($request),
                default => Response::json(['success' => false, 'message' => 'Método não permitido'], 405),
            };
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (ModalidadeNaoEncontradaException) {
            return Response::json(['success' => false, 'message' => 'Modalidade não encontrada.'], 404);
        } catch (Throwable $exception) {
            error_log('Falha em ModalidadeController: ' . $exception->getMessage());

            return Response::json(['success' => false, 'message' => 'Não foi possível processar a modalidade.'], 500);
        }
    }

    private function list(Request $request): Response
    {
        $filters = [
            'id_interclasse' => (int) $request->query('id_interclasse', 0),
            'id_modalidade' => (int) $request->query('id_modalidade', 0),
            'id_categoria' => (int) $request->query('id_categoria', 0),
            'id_tipo_modalidade' => (int) $request->query('id_tipo_modalidade', 0),
            'genero' => trim((string) $request->query('genero', '')),
            'id_turma' => (int) $request->query('id_turma', 0),
        ];
        if ($request->query('ano') !== null) {
            $filters['ano'] = $request->query('ano');
        }

        return Response::json($this->service->listar($filters));
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
            'message' => 'Modalidade criada!',
            'id' => $id,
            'id_modalidade' => $id,
        ], 201);
    }

    private function update(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $this->service->atualizar($request->allInput());

        return Response::json(['success' => true, 'message' => 'Modalidade atualizada com sucesso!']);
    }

    private function delete(Request $request): Response
    {
        $authorization = AccessGuard::requireWrite();
        if ($authorization !== null) {
            return $authorization;
        }

        $payload = $request->allInput();
        $this->service->excluir((int) ($payload['id_modalidade'] ?? $request->query('id_modalidade', 0)));

        return Response::json(['success' => true, 'message' => 'Modalidade excluída com sucesso!']);
    }
}
