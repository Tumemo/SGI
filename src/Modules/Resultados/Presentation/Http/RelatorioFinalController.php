<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Presentation\Http;

use App\Modules\Resultados\Application\RelatorioFinalNaoEncontradoException;
use App\Modules\Resultados\Application\RelatorioFinalService;
use App\Modules\Resultados\Infrastructure\DompdfRelatorioFinalRenderer;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use Throwable;

final class RelatorioFinalController
{
    public function __construct(
        private readonly RelatorioFinalService $service,
        private readonly DompdfRelatorioFinalRenderer $pdf,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->method() !== 'GET') {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1])) !== null) {
            return $denied;
        }

        $editionId = (int) $request->query('id_interclasse', 0);
        if ($editionId <= 0) {
            return Response::json(['success' => false, 'message' => 'Selecione um interclasse.'], 400);
        }

        try {
            return Response::json(['success' => true, 'relatorio' => $this->service->obter($editionId)]);
        } catch (RelatorioFinalNaoEncontradoException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
        } catch (Throwable $exception) {
            error_log('Falha ao carregar relatório final do interclasse: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível carregar o relatório.'], 500);
        }
    }

    public function pdf(Request $request): Response
    {
        if ($request->method() !== 'GET') {
            return Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
        }
        if (($denied = AccessGuard::authorize([0, 1])) !== null) {
            return $denied;
        }

        $editionId = (int) $request->query('id_interclasse', 0);
        if ($editionId <= 0) {
            return Response::json(['success' => false, 'message' => 'Selecione um interclasse.'], 400);
        }

        try {
            $content = $this->pdf->render($this->service->obter($editionId));
            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="relatorio-interclasse-' . $editionId . '.pdf"',
                'Content-Length' => (string) strlen($content),
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (RelatorioFinalNaoEncontradoException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 404);
        } catch (Throwable $exception) {
            error_log('Falha ao exportar PDF do relatório final: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível gerar o PDF do relatório.'], 500);
        }
    }
}
