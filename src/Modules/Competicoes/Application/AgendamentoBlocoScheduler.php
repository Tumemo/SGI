<?php

declare(strict_types=1);

namespace App\Modules\Competicoes\Application;

use InvalidArgumentException;

/**
 * Calcula uma programação determinística sem gravar no banco.
 * As regras de recursos e dependências ficam aqui para serem usadas pela
 * simulação, pela confirmação e pelos testes sem depender de HTTP.
 */
final class AgendamentoBlocoScheduler
{
    /**
     * @param list<array<string,mixed>> $matches
     * @param list<array<string,mixed>> $windows
     * @param list<array<string,mixed>> $fixed
     * @param array<string,mixed> $options
     * @return array{proposta:list<array<string,mixed>>,pendencias:list<array<string,mixed>>,resumo:array<string,int>}
     */
    public function simulate(array $matches, array $windows, array $fixed, array $options = []): array
    {
        $duration = $this->positiveInt($options['duracao_min'] ?? 60, 'duracao_min');
        $changeover = $this->nonNegativeInt($options['intervalo_troca_min'] ?? 0, 'intervalo_troca_min');
        $rest = $this->nonNegativeInt($options['descanso_min'] ?? 0, 'descanso_min');
        $normalisedWindows = $this->windows($windows);
        if ($normalisedWindows === []) {
            throw new InvalidArgumentException('Informe ao menos uma janela de agendamento.');
        }

        $occupancy = [];
        foreach ($fixed as $reservation) {
            $item = $this->reservation($reservation, $changeover);
            if ($item !== null) {
                $occupancy[] = $item;
            }
        }

        $remaining = [];
        foreach ($matches as $index => $match) {
            $key = trim((string) ($match['chave_tag'] ?? $match['nome_jogo'] ?? $match['id_jogo'] ?? ''));
            if ($key === '') {
                throw new InvalidArgumentException('Cada confronto precisa de uma identificação.');
            }
            $remaining[$key] = array_merge($match, [
                'chave_tag' => $key,
                '_ordem' => $index,
                '_duracao' => $this->positiveInt($match['duracao_min'] ?? $duration, 'duracao_min'),
                '_dependencias' => array_values(array_filter(array_map('strval', (array) ($match['dependencias'] ?? [])))),
                '_participantes' => array_values(array_filter(array_map('strval', (array) ($match['participantes'] ?? [])))),
            ]);
        }

        $scheduled = [];
        $proposal = [];
        $pending = [];
        $guard = 0;
        while ($remaining !== [] && $guard++ <= count($matches) + 1) {
            $eligible = [];
            foreach ($remaining as $key => $match) {
                $dependencies = $match['_dependencias'];
                $dependencyEnd = $this->dependencyEnd($dependencies, $scheduled, $match);
                if ($dependencyEnd['missing'] !== []) {
                    continue;
                }
                $match['_after'] = $dependencyEnd['end'] > 0 ? $dependencyEnd['end'] + $rest : 0;
                $match['_after_data'] = $dependencyEnd['data'];
                $eligible[$key] = $match;
            }
            if ($eligible === []) {
                foreach ($remaining as $match) {
                    $missing = [];
                    foreach ($match['_dependencias'] as $dependency) {
                        if (!isset($scheduled[$dependency])) {
                            $missing[] = $dependency;
                        }
                    }
                    $pending[] = $this->pending($match, $missing === [] ? 'Não foi encontrado horário compatível com as restrições.' : 'Dependência ainda sem programação.', $missing);
                }
                break;
            }

            uasort($eligible, static function (array $a, array $b): int {
                $priority = ((int) ($a['prioridade'] ?? 0)) <=> ((int) ($b['prioridade'] ?? 0));
                if ($priority !== 0) {
                    return $priority;
                }
                $duration = ((int) $b['_duracao']) <=> ((int) $a['_duracao']);
                return $duration !== 0 ? $duration : ((int) $a['_ordem']) <=> ((int) $b['_ordem']);
            });

            foreach ($eligible as $key => $match) {
                $candidate = $this->findCandidate($match, $normalisedWindows, $occupancy, $changeover);
                if ($candidate === null) {
                    $pending[] = $this->pending($match, 'Não há janela ou local livre para este confronto.', []);
                    unset($remaining[$key]);
                    continue;
                }
                $entry = array_merge($match, [
                    'data_jogo' => $candidate['data'],
                    'inicio_jogo' => $this->time($candidate['start']),
                    'termino_jogo' => $this->time($candidate['end']),
                    'duracao_jogo' => (int) $match['_duracao'] * 60,
                    'locais_id_local' => $candidate['local'],
                    'intervalo_troca_min' => $changeover,
                    'descanso_min' => $rest,
                ]);
                unset($entry['_ordem'], $entry['_duracao'], $entry['_dependencias'], $entry['_participantes'], $entry['_after'], $entry['_after_data']);
                $proposal[] = $entry;
                $scheduled[$key] = ['end' => $candidate['end'], 'data' => $candidate['data']];
                $occupancy[] = [
                    'data' => $candidate['data'],
                    'start' => $candidate['start'],
                    'end' => $candidate['end'] + $changeover,
                    'local' => $candidate['local'],
                    'participants' => $match['_participantes'],
                ];
                unset($remaining[$key]);
            }
        }

        return [
            'proposta' => $proposal,
            'pendencias' => $pending,
            'resumo' => [
                'selecionados' => count($matches),
                'encaixados' => count($proposal),
                'pendentes' => count($pending),
                'conflitos_fixos' => 0,
            ],
        ];
    }

    /** @param list<array<string,mixed>> $windows @return list<array{data:string,start:int,end:int,local:int}> */
    private function windows(array $windows): array
    {
        if (count($windows) > 31) {
            throw new InvalidArgumentException('Informe no máximo 31 janelas de agendamento por bloco.');
        }
        $result = [];
        foreach ($windows as $window) {
            $date = trim((string) ($window['data'] ?? $window['data_jogo'] ?? ''));
            $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $start = $this->clock((string) ($window['inicio'] ?? $window['inicio_jogo'] ?? ''));
            $end = $this->clock((string) ($window['fim'] ?? $window['termino'] ?? $window['termino_jogo'] ?? ''));
            if ($dateObject === false || $dateObject->format('Y-m-d') !== $date || $start === null || $end === null || $end <= $start) {
                throw new InvalidArgumentException('Cada janela precisa ter data, início e término válidos.');
            }
            $locals = (array) ($window['locais'] ?? $window['locais_id_local'] ?? []);
            if ($locals === [] && isset($window['local'])) {
                $locals = [$window['local']];
            }
            foreach ($locals as $local) {
                if ((int) $local <= 0) {
                    throw new InvalidArgumentException('O local da janela é inválido.');
                }
                $result[] = ['data' => $date, 'start' => $start, 'end' => $end, 'local' => (int) $local];
            }
        }
        usort($result, static fn (array $a, array $b): int => [$a['data'], $a['start'], $a['local']] <=> [$b['data'], $b['start'], $b['local']]);
        return $result;
    }

    /** @return array{data:string,start:int,end:int,local:int,participants:list<string>}|null */
    private function reservation(array $reservation, int $changeover): ?array
    {
        $date = trim((string) ($reservation['data_jogo'] ?? $reservation['data_reserva'] ?? ''));
        $start = $this->clock((string) ($reservation['inicio_jogo'] ?? $reservation['inicio_reserva'] ?? ''));
        $end = $this->clock((string) ($reservation['termino_jogo'] ?? $reservation['termino_reserva'] ?? ''));
        $local = (int) ($reservation['locais_id_local'] ?? $reservation['id_local'] ?? 0);
        if ($date === '' || $start === null || $end === null || $local <= 0) {
            return null;
        }
        return ['data' => $date, 'start' => $start, 'end' => $end + $changeover, 'local' => $local, 'participants' => array_map('strval', (array) ($reservation['participantes'] ?? []))];
    }

    /** @param array<string,mixed> $match @param list<array{data:string,start:int,end:int,local:int}> $windows @param list<array<string,mixed>> $occupancy @return array{data:string,start:int,end:int,local:int}|null */
    private function findCandidate(array $match, array $windows, array $occupancy, int $changeover): ?array
    {
        $after = (int) ($match['_after'] ?? 0);
        $afterDate = trim((string) ($match['_after_data'] ?? ''));
        $participants = $match['_participantes'];
        $best = null;
        foreach ($windows as $window) {
            if ($afterDate !== '' && $window['data'] < $afterDate) {
                continue;
            }
            $start = $window['start'];
            if ($afterDate === '' || $window['data'] === $afterDate) {
                $start = max($start, $after);
            }
            while ($start + (int) $match['_duracao'] <= $window['end']) {
                $end = $start + (int) $match['_duracao'];
                $conflict = false;
                foreach ($occupancy as $used) {
                    if ($used['data'] !== $window['data']) {
                        continue;
                    }
                    $sameLocal = (int) $used['local'] === (int) $window['local'];
                    $sameParticipant = $participants !== [] && array_intersect($participants, $used['participants']) !== [];
                    $overlap = $start < (int) $used['end'] && $end + $changeover > (int) $used['start'];
                    if ($overlap && ($sameLocal || $sameParticipant)) {
                        $start = max($start + 1, (int) $used['end']);
                        $conflict = true;
                        break;
                    }
                }
                if (!$conflict) {
                    $candidate = ['data' => $window['data'], 'start' => $start, 'end' => $end, 'local' => $window['local']];
                    if ($best === null || [$candidate['data'], $candidate['start'], $candidate['local']] < [$best['data'], $best['start'], $best['local']]) {
                        $best = $candidate;
                    }
                    break;
                }
            }
        }
        return $best;
    }

    /** @param list<string> $dependencies @param array<string,array{end:int,data:string}> $scheduled @param array<string,mixed> $match @return array{end:int,data:string,missing:list<string>} */
    private function dependencyEnd(array $dependencies, array $scheduled, array $match): array
    {
        $missing = [];
        $end = 0;
        $data = '';
        $known = (array) ($match['dependencias_terminos'] ?? []);
        foreach ($dependencies as $dependency) {
            $term = null;
            if (isset($scheduled[$dependency])) {
                $term = $scheduled[$dependency];
            } elseif (isset($known[$dependency])) {
                $term = $known[$dependency];
            } else {
                $missing[] = $dependency;
                continue;
            }
            $termData = is_array($term) ? trim((string) ($term['data'] ?? '')) : '';
            $termEnd = is_array($term) ? (int) ($term['end'] ?? 0) : (int) $term;
            if ($termData > $data || ($termData === $data && $termEnd > $end)) {
                $data = $termData;
                $end = $termEnd;
            }
        }
        return ['end' => $end, 'data' => $data, 'missing' => $missing];
    }

    /** @param array<string,mixed> $match @param list<string> $dependencies @return array<string,mixed> */
    private function pending(array $match, string $reason, array $dependencies): array
    {
        return ['id_jogo' => $match['id_jogo'] ?? null, 'chave_tag' => $match['chave_tag'], 'motivo' => $reason, 'dependencias' => $dependencies];
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

    private function positiveInt(mixed $value, string $field): int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT);
        if ($result === false || $result <= 0) {
            throw new InvalidArgumentException($field . ' deve ser um número inteiro positivo.');
        }
        return (int) $result;
    }

    private function nonNegativeInt(mixed $value, string $field): int
    {
        $result = filter_var($value, FILTER_VALIDATE_INT);
        if ($result === false || $result < 0) {
            throw new InvalidArgumentException($field . ' deve ser um número inteiro não negativo.');
        }
        return (int) $result;
    }
}
