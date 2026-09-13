<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Presentation\Web\PageController;
use App\Shared\Config\Env;

final class Kernel
{
    /** @param array<string,string> $webRoutes */
    public function __construct(
        private readonly string $root,
        private readonly RequestHandler $api,
        private readonly AssetResponder $assets,
        private readonly array $webRoutes,
    ) {
    }

    public function run(Request $request): void
    {
        $path = $request->path();
        $base = $this->basePath($request);
        Url::configure($base);
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        if (str_contains($path, "\0") || str_contains($path, '\\') || array_intersect(['.', '..'], explode('/', $path)) !== []) {
            (new Response('Requisição inválida.', 400))->send();
            return;
        }

        $protectedRoute = str_starts_with($path, '/api/v1/') || isset($this->webRoutes[$path]);
        $deprecatedRegistrationValidation = $this->isDeprecatedRegistrationValidation($request, $path);
        if ($protectedRoute && !$this->isPublicPath($path) && !$deprecatedRegistrationValidation && !str_ends_with($path, '/api/v1/logout')) {
            try {
                if (!SessionRevalidator::valid()) {
                    SessionManager::clearAuthentication();
                    if (str_starts_with($path, '/api/v1/')) {
                        Response::json(['success' => false, 'message' => 'Sessão expirada. Faça login novamente.'], 401, ['Cache-Control' => 'no-store'])->send();
                    } else {
                        Response::empty(302, ['Location' => Url::to('login'), 'Cache-Control' => 'no-store'])->send();
                    }
                    return;
                }
            } catch (\Throwable $exception) {
                error_log('Falha ao revalidar sessão: ' . $exception->getMessage());
                Response::json(['success' => false, 'message' => 'Serviço temporariamente indisponível.'], 503, ['Cache-Control' => 'no-store'])->send();
                return;
            }
        }
        if ($protectedRoute && !$this->isPublicPath($path) && !$deprecatedRegistrationValidation) {
            $studentPasswordPending = (int) ($_SESSION['nivel'] ?? -1) === 3
                && !empty($_SESSION['senha_troca_pendente']);

            if ($path === '/aluno/trocar-senha' && !$studentPasswordPending) {
                $destination = (int) ($_SESSION['nivel'] ?? -1) === 3
                    ? (empty($_SESSION['termo_aceito']) ? 'aluno/termos' : 'aluno/inicio')
                    : match ((int) ($_SESSION['nivel'] ?? -1)) {
                        0, 1 => 'edicoes',
                        2 => 'painel',
                        default => 'login',
                    };
                Response::empty(302, [
                    'Location' => Url::to($destination),
                    'Cache-Control' => 'no-store',
                ])->send();
                return;
            }

            if ($studentPasswordPending) {
                $method = strtoupper($request->method());
                $passwordPageAllowed = $path === '/aluno/trocar-senha'
                    && in_array($method, ['GET', 'HEAD'], true);
                $passwordMutationAllowed = $path === '/api/v1/senha' && $method === 'POST';
                $logoutAllowed = $path === '/api/v1/logout';
                if (!$passwordPageAllowed && !$passwordMutationAllowed && !$logoutAllowed) {
                    $redirect = Url::to('aluno/trocar-senha');
                    if (str_starts_with($path, '/api/v1/')) {
                        Response::json([
                            'success' => false,
                            'message' => 'Troque sua senha para continuar.',
                            'redirect' => $redirect,
                        ], 403, ['Cache-Control' => 'no-store'])->send();
                    } else {
                        Response::empty(302, [
                            'Location' => $redirect,
                            'Cache-Control' => 'no-store',
                        ])->send();
                    }
                    return;
                }
            }
        }
        if ($protectedRoute
            && !$this->isPublicPath($path)
            && !$deprecatedRegistrationValidation
            && !str_ends_with($path, '/api/v1/logout')
            && $this->studentTermsAreRequired($request, $path)
            && (int) ($_SESSION['nivel'] ?? -1) === 3
            && empty($_SESSION['termo_aceito'])) {
            $redirect = Url::to('aluno/termos');
            if (str_starts_with($path, '/api/v1/')) {
                Response::json([
                    'success' => false,
                    'message' => 'Aceite os termos de responsabilidade para continuar.',
                    'redirect' => $redirect,
                ], 403, ['Cache-Control' => 'no-store'])->send();
            } else {
                Response::empty(302, [
                    'Location' => $redirect,
                    'Cache-Control' => 'no-store',
                ])->send();
            }
            return;
        }
        CsrfGuard::protectCurrentApiMutation();
        if ($path === '/' || $path === '/index.php') {
            Response::empty(302, ['Location' => Url::to('login')])->send();
            return;
        }
        $request = $request->withPath($path);
        if (str_starts_with($path, '/api/v1/')) {
            $this->api->handle($request)->send();
        } elseif (isset($this->webRoutes[$path])) {
            (new PageController())->show($request, $this->root . '/' . $this->webRoutes[$path])->send();
        } else {
            $this->assets->send($request);
        }
    }

    private function basePath(Request $request): string
    {
        $configured = trim(Env::get('SGI_BASE_PATH', ''), '/');
        if ($configured !== '') {
            return '/' . $configured;
        }
        $script = str_replace('\\', '/', (string) $request->server('SCRIPT_NAME', '/index.php'));
        if ($script === $request->path()) {
            return '';
        }
        $marker = strrpos($script, '/public/index.php');
        if ($marker !== false) {
            return substr($script, 0, $marker);
        }
        return str_ends_with($script, '/index.php') ? rtrim(substr($script, 0, -10), '/') : '';
    }

    private function isPublicPath(string $path): bool
    {
        return in_array($path, ['/login', '/aluno/login', '/api/v1/login', '/api/v1/health'], true);
    }

    private function isDeprecatedRegistrationValidation(Request $request, string $path): bool
    {
        return $path === '/api/v1/usuarios'
            && strtoupper($request->method()) === 'POST'
            && (string) $request->query('acao', $request->input('acao', '')) === 'validar_inscricao';
    }

    private function studentTermsAreRequired(Request $request, string $path): bool
    {
        if (in_array($path, ['/aluno/termos', '/aluno/trocar-senha', '/api/v1/termos'], true)) {
            return false;
        }

        // O primeiro acesso precisa trocar a senha antes de aceitar os termos.
        // Deixe a mutação passar por este gate para que CsrfGuard valide o token
        // antes de o controlador processar a troca.
        if ($path === '/api/v1/senha'
            && strtoupper($request->method()) === 'POST'
            && (int) ($_SESSION['nivel'] ?? -1) === 3
            && !empty($_SESSION['senha_troca_pendente'])) {
            return false;
        }

        // Esta é a única leitura de dados liberada antes do aceite: o aluno
        // precisa conseguir abrir o regulamento para decidir se aceita.
        if ($path === '/api/v1/edicoes'
            && $request->method() === 'GET'
            && strtolower((string) $request->query('regulamento', '')) === 'true'
            && (string) $request->query('status_interclasse', '') === '1'
            && (int) $request->query('id', 0) <= 0
            && (int) $request->query('id_interclasse', 0) <= 0) {
            return false;
        }

        return true;
    }
}
