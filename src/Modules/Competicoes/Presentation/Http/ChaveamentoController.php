<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Presentation\Http;

use App\Modules\Acesso\Presentation\Http\CompetitionAccess;
use App\Modules\Competicoes\Application\ChaveamentoService;
use App\Modules\Competicoes\Domain\TipoCompeticaoRules;
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
        if ($request->method() === 'GET' && (int) ($_SESSION['nivel'] ?? -1) === 3) {
            return Response::json(['success' => false, 'message' => 'A classificação será liberada após a premiação.'], 403);
        }
        $body = $request->allInput();
        $requestedIndividual = ($body['tipo_modalidade'] ?? $request->query('tipo_modalidade')) === 'individual';
        try {
            $id = (int) ($body['id_modalidade'] ?? $request->query('id_modalidade', 0));
            if ($id <= 0) {
                throw new \InvalidArgumentException('Informe o ID da modalidade.');
            }
            $modality = $this->service->modalidade($id);
            if ($modality === null) {
                throw new \InvalidArgumentException('Modalidade não encontrada.');
            }
            $tipo = TipoCompeticaoRules::resolve($modality);
            if ($tipo === null) {
                throw new \InvalidArgumentException('O tipo da modalidade não está configurado.');
            }
            if ((int) ($_SESSION['nivel'] ?? -1) === 2) {
                $edition = $this->service->edition($id);
                if ($edition === null) {
                    return Response::json(['success' => false, 'message' => 'Modalidade fora da edição ativa.'], 403);
                }
                if (($denied = $this->access->authorize($edition)) !== null) {
                    return $denied;
                }
            }
            $isIndividual = $tipo === TipoCompeticaoRules::INDIVIDUAL;
            if ($requestedIndividual && !$isIndividual) {
                throw new \InvalidArgumentException('A modalidade informada não é individual.');
            }
            $individual = $isIndividual;
            if ($request->method() === 'GET') {
                return Response::json($this->service->consultar(
                    $id,
                    $individual,
                    (string) $request->query('acao', $individual ? 'ranking' : 'arvore'),
                ));
            }
            $denied = $individual ? $this->access->authorize() : AccessGuard::requireWrite();
            if ($denied !== null) {
                return $denied;
            }
            if ($individual && (int) $_SESSION['nivel'] === 2 && $this->service->edition($id) !== (int) $_SESSION['id_interclasse']) {
                return Response::json(['success' => false, 'message' => 'Mesários só podem registrar resultados da edição ativa.'], 403);
            }
            $rankingInformado = array_key_exists('ranking', $body);
            $ranking = $rankingInformado && is_array($body['ranking']) ? $body['ranking'] : null;
            $gameId = isset($body['id_jogo']) && is_numeric($body['id_jogo']) ? (int) $body['id_jogo'] : null;
            return Response::json($this->service->gerar($id, $individual, $ranking, $rankingInformado, $gameId));
        } catch (\mysqli_sql_exception $exception) {
            error_log('Falha de persistência no chaveamento: ' . $exception->getMessage());
            return Response::json(['success' => false, 'message' => 'Não foi possível processar o chaveamento.'], 500);
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return Response::json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }
}
