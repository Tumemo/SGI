<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Domain;

final class ChaveamentoRules
{
    /**
     * Chaveamento mata-mata: metadados compactos em nome_jogo (VARCHAR 45).
     * Formatos: MM:{largura_fase}:{slot}:{N|B}, PL:{modalidade}:MM:... (legado)
     * e PL:{modalidade}:{turma}:MM:{largura_fase}:{slot}:{N|B}.
     * - largura_fase: 8,4,2 (oitavas→8 … final→2). O campeão é o vencedor da
     *   final e não é modelado como uma partida solo adicional.
     * - slot: 0-based dentro da fase
     * - N = confronto normal; B = bye (uma equipe, jogo já concluído)
     */
    public static function tag(int $larguraFase, int $slot, string $kind): string
    {
        return 'MM:' . $larguraFase . ':' . $slot . ':' . $kind;
    }
    /** @return array{largura:int, slot:int, kind:string, posicao?:int, planejado?:bool, modalidade?:int, turma?:int, formato_legado?:bool}|null */
    public static function parse(?string $nomeJogo): ?array
    {
        if (!\is_string($nomeJogo)) {
            return \null;
        }
        if (\preg_match('/^MM:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
            return ['largura' => (int) $m[1], 'slot' => (int) $m[2], 'kind' => $m[3]];
        }
        if (\preg_match('/^PL:(\d+):MM:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
            return ['largura' => (int) $m[2], 'slot' => (int) $m[3], 'kind' => $m[4], 'planejado' => true, 'modalidade' => (int) $m[1], 'formato_legado' => true];
        }
        if (\preg_match('/^PL:(\d+):(-?\d+):MM:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
            return [
                'largura' => (int) $m[3],
                'slot' => (int) $m[4],
                'kind' => $m[5],
                'planejado' => true,
                'modalidade' => (int) $m[1],
                'turma' => (int) $m[2],
            ];
        }
        if (\preg_match('/^POS:(\d+):(\d+):([NB])$/', $nomeJogo, $m)) {
            return ['largura' => 0, 'slot' => (int) $m[2], 'kind' => $m[3], 'posicao' => (int) $m[1]];
        }
        return \null;
    }
    public static function proximaLargura(int $largura): int
    {
        return (int) \max(1, $largura / 2);
    }
    public static function slotPai(int $slot): int
    {
        return (int) \floor($slot / 2);
    }
    public static function slotIrmao(int $slot): int
    {
        return $slot % 2 === 0 ? $slot + 1 : $slot - 1;
    }
    public static function proximoPow2(int $n): int
    {
        if ($n <= 1) {
            return 2;
        }
        $p = 1;
        while ($p < $n) {
            $p *= 2;
        }
        return $p;
    }
    public static function nomeFasePt(int $largura): string
    {
        return match ($largura) {
            16 => 'Oitavas de final',
            8 => 'Quartas de final',
            4 => 'Semifinal',
            2 => 'Final',
            1 => 'Campeão',
            default => 'Fase ' . $largura,
        };
    }
    public static function jogoEstaEncerrado(string $status): bool
    {
        return $status === 'Concluido' || $status === 'Finalizado';
    }
    /**
     * Vencedor por maior resultado_partida; empate → menor id_equipe.
     *
     * @param list<array{equipes_id_equipe:int, resultado_partida:int}> $partidas
     */
    public static function vencedorDePartidas(array $partidas): ?int
    {
        if ($partidas === []) {
            return \null;
        }
        if (\count($partidas) === 1) {
            return (int) $partidas[0]['equipes_id_equipe'];
        }
        \usort($partidas, static function (array $x, array $y): int {
            if ((int) $x['resultado_partida'] !== (int) $y['resultado_partida']) {
                return (int) $y['resultado_partida'] <=> (int) $x['resultado_partida'];
            }
            return (int) $x['equipes_id_equipe'] <=> (int) $y['equipes_id_equipe'];
        });
        return (int) $partidas[0]['equipes_id_equipe'];
    }
    /* ==========================================================================
       DISPUTAS DE POSIÇÃO (3º, 4º lugares)
       ========================================================================== */
    public static function nomeFasePosicao(int $posicao): string
    {
        return match ($posicao) {
            3 => 'Disputa de 3º lugar',
            5 => 'Disputa de 5º lugar',
            default => 'Disputa de ' . $posicao . 'º lugar',
        };
    }
    /** @param list<array{equipes_id_equipe:int, resultado_partida:int}> $partidas */
    public static function perdedorDePartidas(array $partidas): ?int
    {
        if (\count($partidas) < 2) {
            return \null;
        }
        \usort($partidas, static function (array $x, array $y): int {
            if ((int) $x['resultado_partida'] !== (int) $y['resultado_partida']) {
                return (int) $y['resultado_partida'] <=> (int) $x['resultado_partida'];
            }
            return (int) $x['equipes_id_equipe'] <=> (int) $y['equipes_id_equipe'];
        });
        return (int) $partidas[1]['equipes_id_equipe'];
    }

    /**
     * Deriva o terceiro colocado sem criar uma partida adicional: é o
     * perdedor da semifinal vencida pelo campeão.
     *
     * Semifinais por bye não produzem perdedor e, portanto, não geram uma
     * classificação automática de terceiro lugar.
     *
     * @param list<array{kind:string,partidas:list<array{equipes_id_equipe:int,resultado_partida:int}>}> $semifinais
     */
    public static function terceiroLugarDoCampeao(?int $campeao, array $semifinais): ?int
    {
        if ($campeao === null || $campeao <= 0) {
            return null;
        }

        $terceiro = null;
        foreach ($semifinais as $semifinal) {
            if ($semifinal['kind'] === 'B') {
                continue;
            }
            $partidas = $semifinal['partidas'];
            if (count($partidas) < 2) {
                continue;
            }
            $vencedor = self::vencedorDePartidas($partidas);
            if ($vencedor !== $campeao) {
                continue;
            }
            $perdedor = self::perdedorDePartidas($partidas);
            if ($perdedor === null || $perdedor === $campeao) {
                continue;
            }
            if ($terceiro !== null && $terceiro !== $perdedor) {
                return null;
            }
            $terceiro = $perdedor;
        }

        return $terceiro;
    }
}
