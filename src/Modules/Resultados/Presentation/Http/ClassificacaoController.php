<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Presentation\Http;

use App\Modules\Resultados\Application\ClassificacaoService;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ClassificacaoController
{
    public function __construct(private readonly ClassificacaoService $service)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1])) !== null) {
            return $denied;
        }
        try {
            $result = $this->service->gerar((int) $request->query('id_modalidade', 0));
            return Response::json(['success' => true, 'modalidade_id' => $result['modalidade_id'], 'podio' => $result['podio']]);
        } catch (\InvalidArgumentException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }
}
