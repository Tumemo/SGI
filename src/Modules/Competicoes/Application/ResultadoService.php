<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\ResultadoRepository;
use App\Modules\Competicoes\Domain\PontoRepository;
use App\Modules\Competicoes\Domain\ChaveamentoRules;
use App\Modules\Competicoes\Domain\TipoCompeticaoRules;
use App\Modules\Resultados\Application\PontuacaoService;
use App\Shared\Application\TransactionRunner;
use InvalidArgumentException;

final class ResultadoService
{
    public function __construct(
        private readonly ResultadoRepository $repository,
        private readonly TransactionRunner $transactions,
        private readonly PontuacaoService $pontuacao,
        private readonly ?PontoRepository $pontos = null,
    ) {
    }

    /** @return array{game_id:int,modality_id:int,edition_id:int,tag:?string} */
    public function inspecionar(int $gameId, ?string $tag, int $modalityId): array
    {
        return $this->repository->inspectResult($gameId, $tag, $modalityId);
    }

    /** @param list<array<string, mixed>> $results @return array<string, mixed> */
    public function lancar(int $gameId, ?string $tag, int $modalityId, array $results, array $points = [], int $operatorId = 0): array
    {
        $results = array_values(array_filter($results, 'is_array'));
        if ($results === []) {
            throw new InvalidArgumentException('Nenhuma equipe foi informada para o resultado.');
        }
        $teamIds = [];
        foreach ($results as $result) {
            if ((int) ($result['id_equipe'] ?? 0) <= 0) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
            }
            $teamId = (int) $result['id_equipe'];
            if (in_array($teamId, $teamIds, true)) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas e distintas.');
            }
            $teamIds[] = $teamId;
            if (array_key_exists('gols', $result) && !is_numeric($result['gols'])) {
                throw new InvalidArgumentException('O resultado da partida deve ser numérico.');
            }
            if ((int) ($result['gols'] ?? 0) < 0) {
                throw new InvalidArgumentException('O resultado da partida não pode ser negativo.');
            }
        }

        $scores = array_map(static fn (array $result): int => (int) ($result['gols'] ?? 0), $results);

        $points = array_values(array_filter($points, 'is_array'));
        return $this->transactions->run(function () use ($gameId, $tag, $modalityId, $results, $scores, $points, $operatorId): array {
            $resolvedGameId = $this->repository->resolveAndValidate($gameId, $tag, $modalityId, $results);
            if ($resolvedGameId <= 0) {
                throw new \RuntimeException('Não foi possível identificar o jogo no servidor.');
            }
            $state = $this->repository->lockGame($resolvedGameId);
            $tipoCompeticao = TipoCompeticaoRules::resolve($state);
            if ($tipoCompeticao === null) {
                throw new InvalidArgumentException('O tipo da modalidade não está configurado.');
            }
            if ($tipoCompeticao === TipoCompeticaoRules::INDIVIDUAL) {
                throw new InvalidArgumentException('Modalidades individuais devem ser concluídas pelo lançamento do pódio.');
            }
            $closed = ChaveamentoRules::jogoEstaEncerrado($state['status_jogo']);
            $oldWinner = $closed
                ? ChaveamentoRules::vencedorDePartidas($this->repository->carregarPartidas($resolvedGameId))
                : null;
            if ($this->pontos !== null && ($gameId < 0 || $points !== [])) {
                $this->pontos->garantirPartidas($resolvedGameId, $results);
                if ($points !== []) {
                    $this->pontos->persistirPontosOffline($resolvedGameId, array_map(
                        static function (array $point) use ($operatorId): array {
                            if (!isset($point['registrado_por']) && $operatorId > 0) {
                                $point['registrado_por'] = $operatorId;
                            }
                            return $point;
                        },
                        $points,
                    ));
                }
            }
            if ($this->pontos !== null) {
                $this->pontos->validarPlacarVinculado($resolvedGameId, $results);
            }
            if ($closed) {
                (new PlacarService())->validarAlteracao($scores);
            } else {
                (new PlacarService())->validarFinalizacao($scores);
            }
            if ($this->pontos === null || !$this->pontos->exigeVinculo($resolvedGameId)) {
                $this->repository->persistirPlacar($resolvedGameId, $results);
            }
            if (!$closed) {
                $this->repository->concluirJogo($resolvedGameId);
                $this->repository->avancarChaveamento($resolvedGameId);
                $this->pontuacao->reconciliarJogo($resolvedGameId);
            } else {
                $newWinner = ChaveamentoRules::vencedorDePartidas($this->repository->carregarPartidas($resolvedGameId));
                if ($oldWinner !== null && $newWinner !== null && $oldWinner !== $newWinner) {
                    $meta = ChaveamentoRules::parse($state['nome_jogo']);
                    if ($meta !== null && ($meta['largura'] > 1 || (int) ($meta['posicao'] ?? 0) === 3)) {
                        $this->pontuacao->reconciliarJogo($resolvedGameId, true);
                        if ($meta['largura'] > 1) {
                            $this->repository->reconstruirChaveamento($state['modalidade_id'], $meta['largura']);
                            $this->pontuacao->invalidarSemOrigemAtual($state['interclasse_id'], $state['modalidade_id']);
                        }
                    }
                }
            }

            return ['success' => true, 'message' => 'Resultado lançado!', 'id_jogo' => $resolvedGameId];
        });
    }
}
