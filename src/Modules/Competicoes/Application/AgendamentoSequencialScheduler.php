<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Coordena a agenda operacional em sessões sucessivas.
 *
 * A primeira sessão começa numa terça-feira e as próximas alternam entre
 * quinta-feira e terça-feira. O limite de término é diário: um jogo que não
 * couber até esse horário fica pendente para a próxima sessão informada.
 */
final class AgendamentoSequencialScheduler
{
    public const DEFAULT_CUTOFF = '11:30';
    public const DEFAULT_START = '08:00';
    public const DEFAULT_INTERVAL = 10;

    public function __construct(
        private readonly AgendamentoBlocoScheduler $scheduler = new AgendamentoBlocoScheduler(),
    ) {
    }

    /**
     * @param list<array<string,mixed>> $matches
     * @param list<array<string,mixed>> $days
     * @param list<array<string,mixed>> $fixed
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function simulate(array $matches, array $days, array $fixed, array $options = []): array
    {
        $normalisedDays = $this->days($days);
        if ($normalisedDays === []) {
            throw new InvalidArgumentException('Informe ao menos um dia de jogos.');
        }

        $interval = $this->interval($options['intervalo_troca_min'] ?? self::DEFAULT_INTERVAL);
        $schedulerOptions = [
            'duracao_min' => $options['duracao_min'] ?? 60,
            'intervalo_troca_min' => $interval,
            'descanso_min' => 0,
        ];
        $windows = array_map(static fn (array $day): array => [
            'data' => $day['data'],
            'inicio' => $day['inicio'],
            'fim' => $day['fim'],
            'locais' => [$day['local']],
        ], $normalisedDays);

        $result = $this->scheduler->simulate($matches, $windows, $fixed, $schedulerOptions);
        $lastDay = $normalisedDays[count($normalisedDays) - 1];
        $hasPending = $result['pendencias'] !== [];
        $result['dias'] = $normalisedDays;
        $result['intervalo_troca_min'] = $interval;
        $result['limite_termino_padrao'] = self::DEFAULT_CUTOFF;
        $result['proximo_dia_sugerido'] = $hasPending ? $this->nextSessionDate($lastDay['data']) : null;
        $result['proximo_inicio_sugerido'] = $hasPending ? $lastDay['inicio'] : null;
        $result['proximo_termino_sugerido'] = $hasPending ? self::DEFAULT_CUTOFF : null;
        return $result;
    }

    public function interval(mixed $value): int
    {
        $interval = filter_var($value, FILTER_VALIDATE_INT);
        if ($interval === false || $interval < self::DEFAULT_INTERVAL) {
            throw new InvalidArgumentException('O intervalo entre jogos deve ser de pelo menos 10 minutos.');
        }
        return (int) $interval;
    }

    /** Retorna a próxima data da cadência terça → quinta → terça. */
    public function nextSessionDate(string $date): string
    {
        $current = $this->date($date);
        $weekday = (int) $current->format('N');
        $target = $weekday === 2 ? 4 : 2;
        $distance = ($target - $weekday + 7) % 7;
        if ($distance === 0) {
            $distance = 7;
        }
        return $current->modify('+' . $distance . ' days')->format('Y-m-d');
    }

    /** Retorna a próxima terça-feira a partir da data informada, inclusive. */
    public function firstSessionDate(?string $from = null): string
    {
        $current = $from === null ? new DateTimeImmutable('today', new DateTimeZone('America/Sao_Paulo')) : $this->date($from);
        $weekday = (int) $current->format('N');
        $distance = (2 - $weekday + 7) % 7;
        return $current->modify('+' . $distance . ' days')->format('Y-m-d');
    }

    /** @param list<array<string,mixed>> $days @return list<array{data:string,inicio:string,fim:string,local:int}> */
    private function days(array $days): array
    {
        if (count($days) > 31) {
            throw new InvalidArgumentException('Informe no máximo 31 sessões de jogos.');
        }

        $normalised = [];
        foreach ($days as $index => $day) {
            $data = trim((string) ($day['data'] ?? $day['data_jogo'] ?? ''));
            $start = trim((string) ($day['inicio'] ?? $day['inicio_jogo'] ?? self::DEFAULT_START));
            $end = trim((string) ($day['fim'] ?? $day['termino'] ?? $day['termino_jogo'] ?? self::DEFAULT_CUTOFF));
            $local = (int) ($day['local'] ?? $day['id_local'] ?? 0);
            $date = $this->date($data);
            $startMinutes = $this->clock($start);
            $endMinutes = $this->clock($end);
            if ($startMinutes === null || $endMinutes === null || $endMinutes <= $startMinutes) {
                throw new InvalidArgumentException('Cada sessão precisa ter início e término válidos.');
            }
            if ($local <= 0) {
                throw new InvalidArgumentException('Informe um local válido para cada sessão.');
            }
            $weekday = (int) $date->format('N');
            if ($index === 0 && $weekday !== 2) {
                throw new InvalidArgumentException('A primeira sessão de jogos deve começar numa terça-feira.');
            }
            if ($index > 0) {
                $previous = $normalised[$index - 1]['data'];
                $expected = $this->nextSessionDate($previous);
                if ($data !== $expected) {
                    throw new InvalidArgumentException('A próxima sessão deve ser a quinta-feira após a terça-feira, alternando depois entre terça e quinta.');
                }
            }
            $normalised[] = [
                'data' => $date->format('Y-m-d'),
                'inicio' => $this->time($startMinutes),
                'fim' => $this->time($endMinutes),
                'local' => $local,
            ];
        }

        return $normalised;
    }

    private function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/Sao_Paulo'));
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Informe uma data de sessão válida.');
        }
        return $date;
    }

    private function clock(string $value): ?int
    {
        if (!preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', trim($value), $match)) {
            return null;
        }
        $hour = (int) $match[1];
        $minute = (int) $match[2];
        return $hour < 24 && $minute < 60 ? $hour * 60 + $minute : null;
    }

    private function time(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }
}
