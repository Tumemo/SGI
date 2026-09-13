<?php

declare (strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Application\SenhaService;

final class SenhaController
{
    public function __construct(private readonly SenhaService $service)
    {
    }
    public function __invoke(\App\Shared\Http\Request $request): \App\Shared\Http\Response
    {
        $status = 200;
        $headers = ['Cache-Control' => 'no-store'];
        $query = $request->allQuery();
        $post = $request->allInput();
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        if (($denied = \App\Shared\Http\AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        if (($request->method()) !== 'POST') {
            $status = 405;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Método não permitido.'], $status, $headers);
        }
        \App\Shared\Http\SessionManager::start();
        $idUsuario = (int) ($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
        $authVersion = (int) ($_SESSION['auth_version'] ?? 0);
        $trocaInicial = (int) ($_SESSION['nivel'] ?? -1) === 3 && !empty($_SESSION['senha_troca_pendente']);
        $payload = $request->allInput();
        try {
            $senhaAtual = (string) ($payload['senha_atual'] ?? '');
            $this->service->trocar(
                $idUsuario,
                (string) ($payload['nova_senha'] ?? ''),
                (string) ($payload['confirmar_senha'] ?? ''),
                $senhaAtual,
                $trocaInicial,
                $authVersion,
            );
            $_SESSION['exige_troca_senha'] = false;
            $_SESSION['senha_troca_pendente'] = false;
            $_SESSION['auth_version'] = $authVersion + 1;
            $response = ['success' => true, 'message' => 'Senha alterada com sucesso!'];
            if ((int) ($_SESSION['nivel'] ?? -1) === 3) {
                $destino = empty($_SESSION['termo_aceito']) ? 'aluno/termos' : 'aluno/inicio';
                $response['redirect'] = \App\Shared\Http\Url::to($destino);
            }
            return \App\Shared\Http\Response::json($response, $status, $headers);
        } catch (\InvalidArgumentException $exception) {
            $status = $idUsuario > 0 ? 200 : 401;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => $exception->getMessage()], $status, $headers);
        } catch (\Throwable $exception) {
            error_log('Falha ao trocar senha: ' . $exception->getMessage());
            $status = 500;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Não foi possível alterar a senha. Tente novamente.'], $status, $headers);
        }
    }
}
