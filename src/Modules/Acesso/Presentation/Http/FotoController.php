<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Application\FotoService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class FotoController
{
    public function __construct(private readonly FotoService $service)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        if ($request->method() === 'GET') {
            $id = (int) $request->query('user_id', 0);
            $currentUserId = (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
            if ((int) ($_SESSION['nivel'] ?? -1) > 1 && $id !== $currentUserId) {
                return Response::json(['success' => false, 'message' => 'Acesso não autorizado.'], 403);
            }
            return $id <= 0 ? Response::json(['erro' => 'user_id inválido'], 400)
                : Response::json(['success' => true, 'user_id' => $id, 'foto_usuario' => $this->service->find($id)]);
        }
        $id = (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
        try {
            if ($request->method() === 'DELETE') {
                $this->service->remove($id);
                $_SESSION['foto_usuario'] = '';
                return Response::json(['success' => true, 'mensagem' => 'Foto removida.']);
            }
            if ($request->method() === 'POST') {
                $file = $request->file('foto');
                if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    return Response::json(['success' => false, 'mensagem' => 'Erro no upload do arquivo.'], 400);
                }
                $filename = $this->service->replace($id, (string) ($file['tmp_name'] ?? ''));
                $_SESSION['foto_usuario'] = $filename;
                return Response::json(['success' => true, 'mensagem' => 'Foto atualizada!', 'arquivo' => $filename]);
            }
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'mensagem' => $exception->getMessage()], 400);
        }
        return Response::json(['erro' => 'Método não permitido'], 405);
    }
}
