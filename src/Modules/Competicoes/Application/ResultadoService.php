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

    /** @param array<mixed> $results @param array<mixed> $points @return array<string, mixed> */
    public function lancar(int $gameId, ?string $tag, int $modalityId, array $results, array $points = [], int $operatorId = 0): array
    {
        $results = array_values($results);
        if ($results === []) {
            throw new InvalidArgumentException('Nenhuma equipe foi informada para o resultado.');
        }
        $teamIds = [];
        $scores = [];
        $normalizedResults = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                throw new InvalidArgumentException('Cada item do resultado deve representar uma equipe.');
            }
            $teamId = self::normalizarIdEquipe($result['id_equipe'] ?? null);
            if ($teamId <= 0) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
            }
            if (in_array($teamId, $teamIds, true)) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas e distintas.');
            }
            $teamIds[] = $teamId;
            $score = PlacarService::normalizarPontuacao(array_key_exists('gols', $result) ? $result['gols'] : 0);
            $scores[] = $score;
            $normalizedResults[] = array_replace($result, ['id_equipe' => $teamId, 'gols' => $score]);
        }
        $results = $normalizedResults;

        $points = array_values($points);
        foreach ($points as $point) {
            if (!is_array($point)) {
                throw new InvalidArgumentException('Cada ponto offline deve representar um evento válido.');
            }
        }
        if ($points !== [] && $operatorId <= 0) {
            throw new InvalidArgumentException('Operador inválido para sincronizar pontos offline.');
        }
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
            if ($closed) {
                (new PlacarService())->validarAlteracao($scores);
            } else {
                (new PlacarService())->validarFinalizacao($scores);
            }
            $oldWinner = $closed
                ? ChaveamentoRules::vencedorDePartidas($this->repository->carregarPartidas($resolvedGameId))
                : null;
            if ($this->pontos !== null && ($gameId < 0 || $points !== [])) {
                $this->pontos->garantirPartidas($resolvedGameId, $results);
                if ($points !== []) {
                    $this->pontos->persistirPontosOffline($resolvedGameId, $points, $operatorId, (string) $state['status_jogo']);
                }
            }
            if ($this->pontos !== null) {
                $this->pontos->validarPlacarVinculado($resolvedGameId, $results);
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

    private static function normalizarIdEquipe(mixed $id): int
    {
        if (is_int($id)) {
            $normalized = $id;
        } elseif (is_string($id)) {
            $digits = trim($id);
            if (preg_match('/^[0-9]+$/D', $digits) !== 1) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
            }
            $digits = ltrim($digits, '0');
            $digits = $digits === '' ? '0' : $digits;
            if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, '2147483647') > 0)) {
                throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
            }
            $normalized = (int) $digits;
        } else {
            throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
        }

        if ($normalized <= 0 || $normalized > 2147483647) {
            throw new InvalidArgumentException('As equipes do resultado devem ser válidas.');
        }

        return $normalized;
    }
}
