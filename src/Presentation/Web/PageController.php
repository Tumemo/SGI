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
    /**
     * Rotas administrativas que não podem ser abertas apenas por fazerem
     * parte da área comum da equipe. A autorização continua no servidor,
     * independentemente dos links ou controles exibidos pela view.
     *
     * @var array<string, list<int>>
     */
    private const PAGE_LEVELS = [
        // Administrative configuration and user management are deliberately
        // explicit. Unknown staff pages must not inherit a permissive default.
        '/colaboradores' => [0],
        '/edicoes' => [0, 1],
        '/edicoes/arrecadacao' => [0, 1],
        '/edicoes/categorias' => [0],
        '/edicoes/equipes' => [0],
        '/edicoes/locais' => [0],
        '/edicoes/modalidades' => [0],
        '/edicoes/pontuacao' => [0],
        '/edicoes/resumo' => [0, 1],
        '/edicoes/turmas' => [0, 1],
        '/equipes/alunos' => [0, 1],
        '/equipes/elenco' => [0, 1, 2],
        '/turmas' => [0, 1],
        '/turmas/alunos' => [0],

        // Operational pages intentionally remain available to the mesário.
        '/painel' => [0, 1, 2],
        '/edicoes/agenda' => [0, 1, 2],
        '/jogos' => [0, 1, 2],
        '/jogos/placar' => [0, 1, 2],
        '/chaveamento' => [0, 1, 2],
        '/modalidades' => [0, 1, 2],
        '/modalidades/detalhes' => [0, 1, 2],
        '/ocorrencias' => [0, 1, 2],
        '/perfil' => [0, 1, 2],
        '/ranking' => [0, 1],
        '/categorias' => [0, 1],
    ];

    public function show(Request $request, string $template): Response
    {
        SessionManager::start();
        $path = $request->path();
        $public = in_array($path, ['/login', '/aluno/login'], true);
        if (!$public) {
            $levels = self::PAGE_LEVELS[$path] ?? (str_starts_with($path, '/aluno/') ? [3] : []);
            $level = (int) ($_SESSION['nivel'] ?? -1);
            if (!in_array($level, $levels, true)) {
                // Uma sessão já autenticada não deve ser enviada para /login:
                // a própria página de login redireciona usuários autenticados,
                // o que criava um loop ao tentar abrir uma rota proibida.
                $destino = match ($level) {
                    3 => 'aluno/inicio',
                    2 => 'painel',
                    0, 1 => 'edicoes',
                    default => 'login',
                };
                return new Response('', 302, ['Location' => Url::to($destino)]);
            }
        }
        $profile = in_array($path, ['/perfil', '/aluno/perfil'], true);
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
        if (in_array($path, ['/aluno/modalidades', '/aluno/jogos'], true)) {
            $data = (new MysqliPortalAlunoRepository(ConnectionFactory::get()))->context((int) ($_SESSION['id'] ?? 0), (int) $request->query('id', 0));
        }
        return (new ViewRenderer())->render($template, $data);
    }
}
