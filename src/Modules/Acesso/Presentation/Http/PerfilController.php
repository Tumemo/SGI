<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Application\PerfilService;
use App\Modules\Acesso\Domain\PerfilRepository;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Storage\StoragePaths;

final class PerfilController
{
    public function __construct(private readonly PerfilRepository $profiles, private readonly PerfilService $service)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        $id = (int) ($_SESSION['id'] ?? 0);
        if ($request->input('acao') === 'remover_foto') {
            $photo = (string) ($this->profiles->find($id)['foto_usuario'] ?? '');
            $this->profiles->setPhoto($id, null);
            if ($photo !== '') {
                $path = StoragePaths::fotosUsuarios() . DIRECTORY_SEPARATOR . basename($photo);
                if (is_file($path)) {
                    unlink($path);
                }
            }
            $_SESSION['foto_usuario'] = null;
            return Response::json(['success' => true, 'mensagem' => 'Foto removida.']);
        }
        try {
            $name = trim((string) $request->input('nome_usuario', ''));
            $this->service->update($id, $name, (string) $request->input('senha_atual', ''), (string) $request->input('nova_senha', ''));
            $_SESSION['nome'] = $name;
            if ((string) $request->input('nova_senha', '') !== '') {
                $_SESSION['auth_version'] = (int) ($_SESSION['auth_version'] ?? 1) + 1;
            }
            return Response::json(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()]);
        }
    }
}
