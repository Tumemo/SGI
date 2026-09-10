<?php

declare (strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Shared\Http\SessionManager;
use App\Shared\Http\CsrfGuard;
use App\Modules\Acesso\Application\LoginService;

final class LoginController
{
    public function __construct(private readonly LoginService $service)
    {
    }
    public function __invoke(\App\Shared\Http\Request $request): \App\Shared\Http\Response
    {
        $status = 200;
        $headers = [];
        $query = $request->allQuery();
        $post = $request->allInput();
        SessionManager::start();
        $headers['Content-Type'] = 'application/json';
        $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0';
        $headers['Pragma'] = 'no-cache';
        $inputData = $request->allInput();
        $matricula = trim((string) ($inputData['matricula'] ?? $post['matricula'] ?? ''));
        $senha = (string) ($inputData['senha'] ?? $post['senha'] ?? '');
        if ($matricula === '' || $senha === '') {
            $status = 400;
            return \App\Shared\Http\Response::json(['status' => 'erro', 'mensagem' => 'Preencha todos os campos.'], $status, $headers);
        }
        try {
            $service = $this->service;
            $authenticated = $service->autenticar($matricula, $senha);
        } catch (\Throwable $exception) {
            error_log('Falha no login: ' . $exception->getMessage());
            $status = 500;
            return \App\Shared\Http\Response::json(['status' => 'erro', 'mensagem' => 'Não foi possível processar o login.'], $status, $headers);
        }
        if ($authenticated === null) {
            $status = 401;
            return \App\Shared\Http\Response::json(['status' => 'erro', 'mensagem' => 'Matrícula ou Senha incorretos.'], $status, $headers);
        }
        $usuario = $authenticated['usuario'];
        $nivel = (int) $usuario['nivel_usuario'];
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['logado'] = true;
        $_SESSION['id'] = (int) $usuario['id_usuario'];
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['nivel'] = $nivel;
        $_SESSION['auth_version'] = max(1, (int) ($usuario['auth_version'] ?? 1));
        $_SESSION['nome'] = $usuario['nome_usuario'];
        $_SESSION['matricula'] = $usuario['matricula_usuario'];
        $_SESSION['foto_usuario'] = $usuario['foto_usuario'] ?? null;
        \App\Modules\Acesso\Presentation\Http\OfflineSession::definirChaveCacheOfflineUsuario((int) $usuario['id_usuario'], (string) $usuario['senha_usuario']);
        $_SESSION['termo_aceito'] = $nivel !== 3 || (int) ($usuario['termo_aceito'] ?? 0) === 1;
        if ($nivel === 2) {
            $_SESSION['id_interclasse'] = $authenticated['interclasse_ativo'];
        } elseif ($nivel === 3) {
            $_SESSION['id_interclasse'] = (int) ($usuario['interclasses_id_interclasse'] ?? 0);
        }
        $_SESSION['exige_troca_senha'] = $authenticated['exige_troca_senha'];
        $destino = match ($nivel) {
            3 => $_SESSION['termo_aceito'] ? '/aluno/inicio' : '/aluno/termos',
            0, 1 => '/edicoes',
            2 => '/painel',
            default => '/login',
        };
        return \App\Shared\Http\Response::json(['status' => 'sucesso', 'redirect' => $destino, 'csrf_token' => CsrfGuard::token()], $status, $headers);
    }
}
