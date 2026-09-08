<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use App\Modules\Competicoes\Domain\CronometroRepository;
use App\Modules\Competicoes\Domain\CronometroRules;
use Closure;
use InvalidArgumentException;

final class CronometroService
{
    private const MAX_EPOCH_MS = 4102444800000;

    private readonly Closure $clock;

    public function __construct(
        private readonly CronometroRepository $repository,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? static fn (): int => time();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{snapshot:array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null},agora:int}
     */
    public function atualizar(int $gameId, array $data): array
    {
        if ($gameId <= 0) {
            throw new InvalidArgumentException('O ID do jogo é obrigatório.');
        }
        $state = $this->repository->findForUpdate($gameId);
        if ($state === null) {
            throw new JogoNaoEncontradoException('Jogo não encontrado.');
        }
        $agora = ($this->clock)();
        if (!is_int($agora) || $agora < 0) {
            throw new InvalidArgumentException('O relógio do cronômetro é inválido.');
        }

        // Jogos criados pelas rotas legadas podem ainda não ter duração. O
        // cliente atual envia snapshot v2 junto com a duração, portanto a
        // compatibilização precisa ocorrer antes de qualquer uma das duas
        // formas de aplicar a mutação.
        $state = $this->prepararBaseLegada($state, $data);
        $hasSnapshot = array_key_exists('cronometro', $data);
        $snapshot = $hasSnapshot
            ? $this->aplicarSnapshot($state, $data, $agora)
            : $this->aplicarStatus($state, $data, $agora);

        $hasTimerField = $hasSnapshot;
        if (array_key_exists('duracao_jogo', $data)) {
            $snapshot = CronometroRules::transicionar($snapshot, 'duracao', $agora, $data);
            $hasTimerField = true;
        }
        if (array_key_exists('tempo_extra_jogo', $data)) {
            $snapshot = CronometroRules::transicionar($snapshot, 'acrescentar', $agora, $data);
            $hasTimerField = true;
        }
        if (array_key_exists('tempo_restante_jogo', $data)) {
            $snapshot = CronometroRules::transicionar($snapshot, 'saldo', $agora, $data);
            $hasTimerField = true;
        }
        if (!$hasTimerField && !array_key_exists('status_jogo', $data)) {
            throw new InvalidArgumentException('Nenhum dado de cronômetro foi enviado.');
        }

        $this->repository->save($gameId, $snapshot);
        return ['snapshot' => $snapshot, 'agora' => $agora];
    }

    /** @param array<string,mixed> $state @param array<string,mixed> $data @return array<string,mixed> */
    private function prepararBaseLegada(array $state, array $data): array
    {
        if ($state['duracao_jogo'] === null && array_key_exists('duracao_jogo', $data)) {
            $state['duracao_jogo'] = $data['duracao_jogo'];
        }
        if ($state['tempo_extra_jogo'] === null && array_key_exists('tempo_extra_jogo', $data)) {
            $state['tempo_extra_jogo'] = $data['tempo_extra_jogo'];
        }
        return $state;
    }

    /**
     * @param array{snapshot:array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null},agora:int} $result
     * @return array<string,mixed>
     */
    public function response(array $result): array
    {
        $snapshot = $result['snapshot'];
        $reference = $snapshot['data_inicio_real'];
        return [
            'cronometro' => [
                'versao' => 2,
                'status_jogo' => $snapshot['status_jogo'],
                'duracao_jogo' => $snapshot['duracao_jogo'],
                'tempo_extra_jogo' => $snapshot['tempo_extra_jogo'],
                'saldo_segundos' => $snapshot['tempo_restante_jogo'],
                'referencia_epoch_ms' => $reference === null ? null : $reference * 1000,
                'servidor_epoch_ms' => $result['agora'] * 1000,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $data
     * @return array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null}
     */
    private function aplicarStatus(array $state, array $data, int $agora): array
    {
        if (!array_key_exists('status_jogo', $data)) {
            return CronometroRules::transicionar($state, 'duracao', $agora, [
                'duracao_jogo' => $state['duracao_jogo'],
            ]);
        }
        if (!is_string($data['status_jogo']) || trim($data['status_jogo']) === '') {
            throw new InvalidArgumentException('Status de cronômetro inválido.');
        }
        return match ($data['status_jogo']) {
            'Iniciado' => CronometroRules::transicionar($state, 'retomar', $agora),
            'Pausado' => CronometroRules::transicionar($state, 'pausar', $agora),
            'Concluido' => CronometroRules::transicionar($state, 'concluir', $agora),
            'Agendado' => $this->keepScheduled($state, $agora),
            default => throw new InvalidArgumentException('Status de cronômetro inválido.'),
        };
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $data
     * @return array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null}
     */
    private function aplicarSnapshot(array $state, array $data, int $agora): array
    {
        if (!is_array($data['cronometro'])) {
            throw new InvalidArgumentException('O cronômetro deve ser um objeto.');
        }
        $payload = $data['cronometro'];
        if ($this->inteiro($payload['versao'] ?? null, 'cronometro.versao', false) !== 2) {
            throw new InvalidArgumentException('A versão do cronômetro deve ser 2.');
        }
        $saldo = $this->inteiro($payload['saldo_segundos'] ?? null, 'cronometro.saldo_segundos', false);
        $referenceMs = $this->inteiro($payload['referencia_epoch_ms'] ?? null, 'cronometro.referencia_epoch_ms', false);
        if ($referenceMs > self::MAX_EPOCH_MS) {
            throw new InvalidArgumentException('A referência do cronômetro está fora do limite.');
        }
        $reference = intdiv($referenceMs, 1000);
        $status = $data['status_jogo'] ?? $state['status_jogo'];
        if (!is_string($status)) {
            throw new InvalidArgumentException('Status de cronômetro inválido.');
        }
        if ($status === 'Pausado' || $status === 'Concluido') {
            $state['status_jogo'] = $status;
            $state['tempo_restante_jogo'] = $saldo;
            $state['data_inicio_real'] = null;
            return $state;
        }
        if ($status === 'Iniciado') {
            $state['status_jogo'] = 'Iniciado';
            $state['tempo_restante_jogo'] = max(0, $saldo - max(0, $agora - $reference));
            $state['data_inicio_real'] = $agora;
            return $state;
        }
        throw new InvalidArgumentException('Snapshot v2 exige jogo iniciado, pausado ou concluído.');
    }

    /** @param array<string,mixed> $state @return array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} */
    private function keepScheduled(array $state, int $agora): array
    {
        if ($state['status_jogo'] !== 'Agendado') {
            throw new InvalidArgumentException('Um jogo em andamento não pode voltar a agendado.');
        }
        return CronometroRules::transicionar($state, 'duracao', $agora, [
            'duracao_jogo' => $state['duracao_jogo'],
        ]);
    }

    private function inteiro(mixed $value, string $field, bool $positive): int
    {
        if (is_int($value)) {
            $normalised = $value;
        } elseif (is_string($value) && preg_match('/^\d+$/', trim($value)) === 1) {
            $normalised = (int) trim($value);
        } else {
            throw new InvalidArgumentException($field . ' deve ser um inteiro válido.');
        }
        if (($positive && $normalised <= 0) || (!$positive && $normalised < 0)) {
            throw new InvalidArgumentException($field . ' deve ser não negativo' . ($positive ? ' e positivo.' : '.'));
        }
        return $normalised;
    }
}
