<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\ChaveamentoService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ChaveamentoController
{
    public function __construct(private readonly ChaveamentoService $service, private readonly CompetitionAccess $access)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        $individual = $request->input('tipo_modalidade', $request->query('tipo_modalidade')) === 'individual';
        try {
            if ($request->method() === 'GET') {
                return Response::json($this->service->consultar(
                    (int) $request->query('id_modalidade', 0),
                    $individual,
                    (string) $request->query('acao', $individual ? 'ranking' : 'arvore'),
                ));
            }
            $denied = $individual ? $this->access->authorize() : AccessGuard::requireWrite();
            if ($denied !== null) {
                return $denied;
            }
            $id = (int) $request->input('id_modalidade', 0);
            if ($id <= 0) {
                throw new \InvalidArgumentException('Informe o ID da modalidade.');
            }
            if ($individual && (int) $_SESSION['nivel'] === 2 && $this->service->edition($id) !== (int) $_SESSION['id_interclasse']) {
                return Response::json(['success' => false, 'message' => 'Mesários só podem registrar resultados da edição ativa.'], 403);
            }
            $ranking = $request->input('ranking');
            return Response::json($this->service->gerar($id, $individual, is_array($ranking) ? $ranking : null));
        } catch (\mysqli_sql_exception $exception) {
            error_log('Falha de persistência no chaveamento: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível processar o chaveamento.'], 500);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }
}
