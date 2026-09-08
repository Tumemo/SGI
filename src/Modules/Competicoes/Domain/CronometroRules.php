<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Domain;

use InvalidArgumentException;

final class CronometroRules
{
    private const STATUSES = ['Agendado', 'Iniciado', 'Pausado', 'Concluido', 'Finalizado'];

    /**
     * Calcula o saldo no instante informado.
     *
     * `data_inicio_real` é um epoch inteiro nesta camada pura. O adaptador
     * converte o DATETIME do MySQL antes de chamar a regra.
     *
     * @param array<string, mixed> $estado
     */
    public static function saldoAtual(array $estado, int $agora): int
    {
        self::validarInstante($agora);
        $estado = self::normalizar($estado);
        $saldo = self::saldoBase($estado);
        if ($estado['status_jogo'] !== 'Iniciado' || $estado['data_inicio_real'] === null) {
            return $saldo;
        }

        $decorrido = max(0, $agora - $estado['data_inicio_real']);
        return max(0, $saldo - $decorrido);
    }

    /**
     * Aplica uma transição sem consultar relógio global ou persistência.
     *
     * Operações: iniciar, pausar, retomar, acrescentar, duracao, saldo e
     * concluir. O acréscimo é absoluto: o payload deve conter o novo
     * `tempo_extra_jogo`, não somente a diferença.
     *
     * @param array<string, mixed> $estado
     * @param array<string, mixed> $dados
     * @return array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null}
     */
    public static function transicionar(array $estado, string $operacao, int $agora, array $dados = []): array
    {
        self::validarInstante($agora);
        $estado = self::normalizar($estado);

        return match ($operacao) {
            'iniciar', 'retomar' => self::iniciar($estado, $agora),
            'pausar' => self::pausar($estado, $agora),
            'acrescentar' => self::acrescentar($estado, $agora, $dados),
            'duracao' => self::alterarDuracao($estado, $agora, $dados),
            'saldo' => self::salvarSaldo($estado, $agora, $dados),
            'concluir' => self::concluir($estado, $agora),
            default => throw new InvalidArgumentException('Operação de cronômetro inválida.'),
        };
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado */
    private static function iniciar(array $estado, int $agora): array
    {
        if ($estado['status_jogo'] === 'Agendado') {
            $estado['tempo_restante_jogo'] = $estado['duracao_jogo'] + $estado['tempo_extra_jogo'];
            $estado['status_jogo'] = 'Iniciado';
            $estado['data_inicio_real'] = $agora;
            return $estado;
        }
        if ($estado['status_jogo'] === 'Pausado') {
            $estado['tempo_restante_jogo'] = self::saldoBase($estado);
            $estado['status_jogo'] = 'Iniciado';
            $estado['data_inicio_real'] = $agora;
        }
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado */
    private static function pausar(array $estado, int $agora): array
    {
        if ($estado['status_jogo'] === 'Iniciado') {
            $estado['tempo_restante_jogo'] = self::saldoAtual($estado, $agora);
        } elseif ($estado['status_jogo'] === 'Agendado') {
            $estado['tempo_restante_jogo'] = self::saldoBase($estado);
        }
        if (in_array($estado['status_jogo'], ['Agendado', 'Iniciado', 'Pausado'], true)) {
            $estado['status_jogo'] = 'Pausado';
            $estado['data_inicio_real'] = null;
        }
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado @param array<string,mixed> $dados */
    private static function acrescentar(array $estado, int $agora, array $dados): array
    {
        $novoExtra = self::inteiro($dados['tempo_extra_jogo'] ?? null, 'tempo_extra_jogo', false);
        if ($novoExtra < $estado['tempo_extra_jogo']) {
            throw new InvalidArgumentException('O acréscimo do cronômetro não pode ser reduzido.');
        }
        $estado['tempo_restante_jogo'] = self::saldoAtual($estado, $agora) + ($novoExtra - $estado['tempo_extra_jogo']);
        $estado['tempo_extra_jogo'] = $novoExtra;
        self::atualizarReferencia($estado, $agora);
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado @param array<string,mixed> $dados */
    private static function alterarDuracao(array $estado, int $agora, array $dados): array
    {
        $novaDuracao = self::inteiro($dados['duracao_jogo'] ?? null, 'duracao_jogo', true);
        $estado['tempo_restante_jogo'] = max(0, self::saldoAtual($estado, $agora) + ($novaDuracao - $estado['duracao_jogo']));
        $estado['duracao_jogo'] = $novaDuracao;
        self::atualizarReferencia($estado, $agora);
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado @param array<string,mixed> $dados */
    private static function salvarSaldo(array $estado, int $agora, array $dados): array
    {
        $estado['tempo_restante_jogo'] = self::inteiro($dados['tempo_restante_jogo'] ?? null, 'tempo_restante_jogo', false);
        self::atualizarReferencia($estado, $agora);
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado */
    private static function concluir(array $estado, int $agora): array
    {
        $estado['tempo_restante_jogo'] = self::saldoAtual($estado, $agora);
        $estado['status_jogo'] = 'Concluido';
        $estado['data_inicio_real'] = null;
        return $estado;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado */
    private static function atualizarReferencia(array &$estado, int $agora): void
    {
        $estado['data_inicio_real'] = $estado['status_jogo'] === 'Iniciado' ? $agora : null;
    }

    /** @param array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} $estado */
    private static function saldoBase(array $estado): int
    {
        return $estado['tempo_restante_jogo'] ?? ($estado['duracao_jogo'] + $estado['tempo_extra_jogo']);
    }

    /** @param array<string,mixed> $estado @return array{status_jogo:string,duracao_jogo:int,tempo_extra_jogo:int,tempo_restante_jogo:int|null,data_inicio_real:int|null} */
    private static function normalizar(array $estado): array
    {
        $status = (string) ($estado['status_jogo'] ?? '');
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status de cronômetro inválido.');
        }
        $duration = self::inteiro($estado['duracao_jogo'] ?? null, 'duracao_jogo', true);
        $extra = $estado['tempo_extra_jogo'] ?? 0;
        $remaining = array_key_exists('tempo_restante_jogo', $estado) && $estado['tempo_restante_jogo'] !== null
            ? self::inteiro($estado['tempo_restante_jogo'], 'tempo_restante_jogo', false)
            : null;
        $reference = array_key_exists('data_inicio_real', $estado) && $estado['data_inicio_real'] !== null
            ? self::inteiro($estado['data_inicio_real'], 'data_inicio_real', false)
            : null;

        return [
            'status_jogo' => $status,
            'duracao_jogo' => $duration,
            'tempo_extra_jogo' => self::inteiro($extra, 'tempo_extra_jogo', false),
            'tempo_restante_jogo' => $remaining,
            'data_inicio_real' => $reference,
        ];
    }

    private static function validarInstante(int $agora): void
    {
        if ($agora < 0) {
            throw new InvalidArgumentException('O instante do cronômetro deve ser não negativo.');
        }
    }

    private static function inteiro(mixed $value, string $campo, bool $positivo): int
    {
        if (is_int($value)) {
            $normalizado = $value;
        } elseif (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            $normalizado = (int) trim($value);
        } else {
            throw new InvalidArgumentException($campo . ' deve ser um inteiro válido.');
        }
        if (($positivo && $normalizado <= 0) || (!$positivo && $normalizado < 0)) {
            throw new InvalidArgumentException($campo . ' deve ser não negativo' . ($positivo ? ' e positivo.' : '.'));
        }
        return $normalizado;
    }
}
