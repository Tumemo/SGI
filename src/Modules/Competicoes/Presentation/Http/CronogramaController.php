<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Competicoes\Application\CronogramaService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class CronogramaController
{
    public function __construct(private readonly CronogramaService $service)
    {
    }

    public function __invoke(Request $request): Response
    {
        $data = $request->allInput();
        $edition = (int) ($data['id_interclasse'] ?? $request->query('id_interclasse', $_SESSION['id_interclasse'] ?? 0));
        if ($edition <= 0) {
            return Response::json(['success' => false, 'message' => 'O ID da edição é obrigatório.'], 422);
        }
        if ($request->method() === 'GET') {
            if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
                return $denied;
            }
            if (($denied = $this->authorizeEdition($edition)) !== null) {
                return $denied;
            }
            try {
                return Response::json($this->service->estado($edition));
            } catch (\InvalidArgumentException $exception) {
                return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
            } catch (\Throwable $exception) {
                error_log('Falha ao consultar cronograma: ' . $exception->getMessage());
                return Response::json(['success' => false, 'message' => 'Não foi possível consultar o cronograma.'], 500);
            }
        }
        if (!in_array($request->method(), ['POST', 'PUT'], true)) {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1])) !== null) {
            return $denied;
        }
        if (($denied = $this->authorizeEdition($edition)) !== null) {
            return $denied;
        }
        try {
            $userId = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
            $action = (string) ($data['acao'] ?? 'estado');
            $result = match ($action) {
                'ativar_planejamento', 'ativar' => $this->service->ativar($edition, $userId),
                'preparar_equipes', 'preparar' => $this->service->preparar($edition, $userId),
                'gerar_rascunho', 'gerar' => $this->service->gerar($edition, $userId, $data),
                'publicar' => $this->service->publicar($edition, $userId, $data),
                'abrir_inscricoes', 'abrir' => $this->service->abrir($edition, $userId, $data),
                'encerrar_inscricoes', 'encerrar' => $this->service->fechar($edition, $userId, $data),
                default => throw new \InvalidArgumentException('Ação de cronograma inválida.'),
            };
            return Response::json($result);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'code' => 'CRONOGRAMA_INVALIDO', 'message' => $exception->getMessage()], 422);
        } catch (\Throwable $exception) {
            error_log('Falha ao processar cronograma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'code' => 'CRONOGRAMA_ERRO', 'message' => 'Não foi possível processar o cronograma.'], 500);
        }
    }

    private function authorizeEdition(int $edition): ?Response
    {
        $level = (int) ($_SESSION['nivel'] ?? -1);
        if (in_array($level, [2, 3], true) && (int) ($_SESSION['id_interclasse'] ?? 0) !== $edition) {
            return Response::json(['success' => false, 'message' => 'A edição solicitada não está disponível para esta sessão.'], 403);
        }
        return null;
    }
}
