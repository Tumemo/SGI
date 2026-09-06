<?php

declare(strict_types=1);

namespace App\Modules\Participantes\Presentation\Http;

use App\Modules\Participantes\Application\ImportacaoTurmaService;
use App\Modules\Participantes\Infrastructure\TurmaPdfStorage;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class ImportacaoTurmaController
{
    public function __construct(private readonly ImportacaoTurmaService $service, private readonly TurmaPdfStorage $storage)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::requireWrite()) !== null) {
            return $denied;
        }
        try {
            $file = $request->file('pdf_arquivo', $request->file('pdf'));
            if (!is_array($file)) {
                throw new \InvalidArgumentException('Nenhum arquivo enviado. Campo esperado: pdf_arquivo');
            }
            $class = (int) $request->input('id_turma', 0);
            $edition = (int) $request->input('id_interclasse', 0);
            $this->service->validateDestination($class, $edition);
            return Response::json($this->storage->process($file, $class, fn (string $path): array => $this->service->importar($path, $class, $edition)));
        } catch (\InvalidArgumentException $exception) {
            // Legacy upload clients inspect success, including for validation errors.
            return Response::json(['success' => false, 'message' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            error_log('Falha ao importar PDF da turma: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível importar os alunos.']);
        }
    }
}
