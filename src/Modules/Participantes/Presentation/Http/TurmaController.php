<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Presentation\Http;

use App\Modules\Participantes\Application\TurmaDuplicadaException;
use App\Modules\Participantes\Application\TurmaNaoEncontradaException;
use App\Modules\Participantes\Application\TurmaService;
use App\Modules\Participantes\Application\TurmaVinculadaException;

final class TurmaController
{
    public function __construct(private readonly TurmaService $service)
    {
    }
    public function __invoke(\App\Shared\Http\Request $request): \App\Shared\Http\Response
    {
        $status = 200;
        $headers = [];
        $query = $request->allQuery();
        $post = $request->allInput();
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        $headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, DELETE, OPTIONS';
        $headers['Access-Control-Allow-Headers'] = 'Content-Type';
        $method = $request->method();
        $service = $this->service;
        if ($method === 'OPTIONS') {
            $status = 204;
            return \App\Shared\Http\Response::empty($status, $headers);
        }
        if (($denied = \App\Shared\Http\AccessGuard::authorize([0, 1, 2, 3])) !== null) {
            return $denied;
        }
        try {
            switch ($method) {
                case 'GET':
                    if ((int) ($_SESSION['nivel'] ?? -1) === 2) {
                        if ((int) ($_SESSION['id_interclasse'] ?? 0) <= 0) {
                            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Nenhuma edição ativa.'], 403);
                        }
                        $query['id_interclasse'] = (int) ($_SESSION['id_interclasse'] ?? 0);
                    }
                    // Sem edição explícita, não há conjunto de turmas a consultar.
                    if (!isset($query['id_interclasse']) || $query['id_interclasse'] === '') {
                        return \App\Shared\Http\Response::json([], $status, $headers);
                    }
                    return \App\Shared\Http\Response::json($service->listar(['id_interclasse' => (int) $query['id_interclasse'], 'id_turma' => (int) ($query['id_turma'] ?? 0), 'id_categoria' => (int) ($query['id_categoria'] ?? 0), 'turno' => (string) ($query['turno'] ?? ''), 'busca' => trim((string) ($query['busca'] ?? ''))]), $status, $headers);
                case 'POST':
                    if (($denied = \App\Shared\Http\AccessGuard::authorize([0, 1])) !== null) {
                        return $denied;
                    }
                    $id = $service->criar($request->allInput());
                    $status = 201;
                    return \App\Shared\Http\Response::json(['success' => true, 'message' => 'Turma cadastrada com sucesso!', 'id_turma' => $id], $status, $headers);
                case 'PUT':
                    if (($denied = \App\Shared\Http\AccessGuard::authorize([0, 1])) !== null) {
                        return $denied;
                    }
                    $service->atualizar($request->allInput());
                    return \App\Shared\Http\Response::json(['success' => true, 'message' => 'Turma atualizada com sucesso!'], $status, $headers);
                case 'DELETE':
                    if (($denied = \App\Shared\Http\AccessGuard::authorize([0])) !== null) {
                        return $denied;
                    }
                    $payload = $request->allInput();
                    $service->excluir((int) ($payload['id_turma'] ?? $query['id_turma'] ?? 0));
                    return \App\Shared\Http\Response::json(['success' => true, 'message' => 'Turma excluída com sucesso.'], $status, $headers);
                default:
                    $status = 405;
                    return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Método não permitido'], $status, $headers);
            }
        } catch (TurmaDuplicadaException) {
            $status = 409;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Já existe uma turma com este nome e período nesta edição do interclasse.'], $status, $headers);
        } catch (TurmaVinculadaException) {
            $status = 409;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Não é possível excluir esta turma pois existem registros vinculados a ela.'], $status, $headers);
        } catch (TurmaNaoEncontradaException) {
            $status = 404;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Turma não encontrada.'], $status, $headers);
        } catch (\InvalidArgumentException $exception) {
            $status = 400;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => $exception->getMessage()], $status, $headers);
        } catch (\Throwable $exception) {
            error_log('Falha ao processar turmas: ' . $exception->getMessage());
            $status = 500;
            return \App\Shared\Http\Response::json(['success' => false, 'message' => 'Não foi possível processar a turma.'], $status, $headers);
        }
    }
}
