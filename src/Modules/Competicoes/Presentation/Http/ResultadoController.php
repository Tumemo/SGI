<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\JogoNaoEncontradoException;
use App\Modules\Competicoes\Application\JogoResolucaoAmbiguaException;
use App\Modules\Competicoes\Application\ModalidadeNaoEncontradaException;
use App\Modules\Competicoes\Application\ResultadoService;
use App\Modules\Sincronizacao\Presentation\Http\MutationAction;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ResultadoController
{
    public function __construct(
        private readonly ResultadoService $service,
        private readonly CompetitionAccess $access,
        private readonly MutationAction $mutations,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() !== 'POST') {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
            return $denied;
        }
        if (($denied = $this->access->authorize()) !== null) {
            return $denied;
        }
        $data = $request->allInput();
        if (!isset($data['id_jogo'], $data['resultados']) || !is_array($data['resultados'])) {
            return Response::json(['success' => false, 'message' => 'Dados insuficientes.'], 400);
        }
        try {
            $context = $this->service->inspecionar(
                (int) $data['id_jogo'],
                isset($data['nome_jogo']) ? (string) $data['nome_jogo'] : null,
                (int) ($data['id_modalidade'] ?? 0),
            );
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (JogoNaoEncontradoException|ModalidadeNaoEncontradaException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
        } catch (\mysqli_sql_exception $exception) {
            error_log('Falha ao inspecionar resultado: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
        } catch (\RuntimeException $exception) {
            error_log('Falha ao inspecionar resultado: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
        }
        if (($denied = $this->access->authorize($context['edition_id'])) !== null) {
            return $denied;
        }
        return $this->mutations->run($request, 'lancar_resultado', function () use ($data): Response {
            try {
                $result = $this->service->lancar(
                    (int) $data['id_jogo'],
                    isset($data['nome_jogo']) ? (string) $data['nome_jogo'] : null,
                    (int) ($data['id_modalidade'] ?? 0),
                    array_values(array_filter($data['resultados'], 'is_array')),
                    array_values(array_filter($data['pontos'] ?? [], 'is_array')),
                    (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
                );
                return Response::json($result);
            } catch (JogoResolucaoAmbiguaException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 409);
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 422);
            } catch (JogoNaoEncontradoException|ModalidadeNaoEncontradaException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
            } catch (\mysqli_sql_exception $exception) {
                error_log('Falha de persistência ao lançar resultado: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
            } catch (\RuntimeException $exception) {
                error_log('Falha ao lançar resultado: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
            } catch (\Throwable $exception) {
                error_log('Falha ao lançar resultado: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível lançar o resultado.'], 500);
            }
        });
    }
}
