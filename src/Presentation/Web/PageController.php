<?php

declare(strict_types=1);

namespace App\Presentation\Web;

use App\Modules\Acesso\Application\PerfilService;
use App\Modules\Acesso\Infrastructure\MysqliPerfilRepository;
use App\Modules\Acesso\Presentation\Http\PerfilController;
use App\Modules\Participantes\Infrastructure\MysqliPortalAlunoRepository;
use App\Shared\Database\ConnectionFactory;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Http\SessionManager;
use App\Shared\Http\Url;
use App\Shared\Http\ViewRenderer;

final class PageController
{
    public function show(Request $request, string $template): Response
    {
        SessionManager::start();
        $path = $request->path();
        $public = in_array($path, ['/views/index.php', '/views/src/pages/alunos/login.php'], true);
        if (!$public) {
            $levels = str_contains($path, '/alunos/') ? [3] : [0, 1, 2];
            if (!in_array((int) ($_SESSION['nivel'] ?? -1), $levels, true)) {
                return new Response('', 302, ['Location' => Url::to('views/index.php')]);
            }
        }
        $profile = str_ends_with($path, '/perfil.php');
        if (!in_array($request->method(), $profile ? ['GET', 'HEAD', 'POST'] : ['GET', 'HEAD'], true)) {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        $data = [];
        if ($profile) {
            $repository = new MysqliPerfilRepository(ConnectionFactory::get());
            if ($request->method() === 'POST') {
                return (new PerfilController($repository, new PerfilService($repository)))($request);
            }
            $data['usuarioPerfil'] = $repository->find((int) ($_SESSION['id'] ?? 0)) ?? [];
            unset($data['usuarioPerfil']['senha_usuario']);
            $data['sessionId'] = (int) ($_SESSION['id'] ?? 0);
        }
        if (in_array($path, ['/views/src/pages/alunos/modalidade.php', '/views/src/pages/alunos/jogos.php'], true)) {
            $data = (new MysqliPortalAlunoRepository(ConnectionFactory::get()))->context((int) ($_SESSION['id'] ?? 0), (int) $request->query('id', 0));
        }
        return (new ViewRenderer())->render($template, $data);
    }
}
