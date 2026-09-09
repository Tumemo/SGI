<?php

declare(strict_types=1);

namespace App\Modules\Eventos\Presentation\Http;

use App\Modules\Eventos\Application\EdicaoService;
use App\Modules\Eventos\Infrastructure\RegulamentoStorage;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Request;
use App\Shared\Http\Response;

final class EdicaoController
{
    public function __construct(private readonly EdicaoService $service, private readonly RegulamentoStorage $storage)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        $uploaded = null;
        try {
            if ($request->method() === 'POST' && $request->query('acao') === 'publicar_ranking') {
                if (($denied = AccessGuard::authorize([0])) !== null) {
                    return $denied;
                }
                $this->service->publicarRanking(
                    (int) $request->query('id', 0),
                    (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
                );
                return Response::json(['success' => true, 'message' => 'Ranking publicado com sucesso.']);
            }
            if ($request->method() === 'GET') {
                $filters = [
                    'detalhes' => $request->query('regulamento') === 'true' || $request->query('id_interclasse') || $request->query('id'),
                    'id_interclasse' => (int) $request->query('id_interclasse', $request->query('id', 0)),
                    'ano' => (int) $request->query('ano', 0),
                    'status_interclasse' => (string) $request->query('status_interclasse', ''),
                    'ranking_publicado' => (string) $request->query('ranking_publicado', ''),
                    'busca' => trim((string) $request->query('busca', '')),
                ];
                if ((int) ($_SESSION['nivel'] ?? -1) === 2) {
                    $activeEdition = (int) ($_SESSION['id_interclasse'] ?? 0);
                    if ($activeEdition <= 0) {
                        return Response::json(['success' => false, 'message' => 'Nenhuma edição ativa.'], 403);
                    }
                    $filters['id_interclasse'] = $activeEdition;
                    $filters['detalhes'] = true;
                }
                return Response::json($this->service->listar($filters));
            }
            if (($denied = AccessGuard::requireWrite()) !== null) {
                return $denied;
            }
            $payload = $request->allInput();
            $id = (int) $request->query('id', 0);
            if ($id > 0) {
                $upload = $request->file('pdf_regulamento');
                if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $uploaded = $this->storage->save($upload);
                    $payload['regulamento_interclasse'] = $uploaded;
                }
                $this->service->atualizar($id, $payload);
                return Response::json(['success' => true, 'message' => 'Atualizado com sucesso!']);
            }
            return Response::json(['success' => true, ...$this->service->criar($payload)]);
        } catch (\InvalidArgumentException $exception) {
            if ($uploaded !== null) {
                $this->storage->remove($uploaded);
            }
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        } catch (\Throwable $exception) {
            if ($uploaded !== null) {
                $this->storage->remove($uploaded);
            }
            throw $exception;
        }
    }
}
