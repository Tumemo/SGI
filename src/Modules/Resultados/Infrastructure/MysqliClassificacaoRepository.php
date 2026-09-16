<?php

declare(strict_types=1);

namespace App\Modules\Resultados\Infrastructure;

use App\Modules\Competicoes\Domain\ChaveamentoRules;
use App\Modules\Resultados\Domain\ClassificacaoRepository;
use mysqli;
use RuntimeException;

final class MysqliClassificacaoRepository implements ClassificacaoRepository
{
    public function __construct(private readonly mysqli $connection)
    {
    }

    public function podium(int $modalityId): array
    {
        $final = $this->connection->prepare(
            "SELECT id_jogo FROM jogos
             WHERE modalidades_id_modalidade = ? AND nome_jogo LIKE 'MM:2:%'
               AND status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY id_jogo DESC LIMIT 1",
        );
        if ($final === false) {
            throw new RuntimeException('Não foi possível consultar a classificação.');
        }
        $final->bind_param('i', $modalityId);
        if (!$final->execute()) {
            $final->close();
            throw new RuntimeException('Não foi possível consultar a classificação.');
        }
        $finalRow = $final->get_result()->fetch_assoc();
        $final->close();
        if (!$finalRow) {
            return [];
        }

        $podium = [];
        $positions = $this->teamsFromGame((int) $finalRow['id_jogo']);
        if (isset($positions[0])) {
            $podium[] = [
                'posicao' => 1,
                'equipe' => $positions[0]['nome_turma'],
                'fantasia' => $positions[0]['nome_fantasia_turma'],
                'status' => 'Campeão',
            ];
        }
        if (isset($positions[1])) {
            $podium[] = [
                'posicao' => 2,
                'equipe' => $positions[1]['nome_turma'],
                'fantasia' => $positions[1]['nome_fantasia_turma'],
                'status' => 'Vice-Campeão',
            ];
        }

        $thirdTeamId = $this->thirdTeamIdFromFinal((int) $finalRow['id_jogo'], $modalityId);
        $thirdRow = $thirdTeamId === null ? $this->legacyThirdFromModality($modalityId) : $this->teamFromId($thirdTeamId, $modalityId);
        if ($thirdRow) {
            $podium[] = [
                'posicao' => 3,
                'equipe' => $thirdRow['nome_turma'],
                'fantasia' => $thirdRow['nome_fantasia_turma'],
                'status' => '3º Lugar',
            ];
        }
        return $podium;
    }

    /** @return list<array<string, mixed>> */
    private function teamsFromGame(int $gameId): array
    {
        $statement = $this->connection->prepare(
            'SELECT e.id_equipe, t.nome_turma, t.nome_fantasia_turma, p.resultado_partida
             FROM partidas p
             INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
             INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
             WHERE p.jogos_id_jogo = ? ORDER BY p.resultado_partida DESC, e.id_equipe ASC',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar equipes da final.');
        }
        $statement->bind_param('i', $gameId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar equipes da final.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();
        return $rows;
    }

    private function thirdTeamIdFromFinal(int $finalId, int $modalityId): ?int
    {
        $finalTeams = $this->teamsFromGame($finalId);
        $finalParts = array_map(static fn (array $team): array => [
            'equipes_id_equipe' => (int) $team['id_equipe'],
            'resultado_partida' => (int) $team['resultado_partida'],
        ], $finalTeams);
        $champion = ChaveamentoRules::vencedorDePartidas($finalParts);
        if ($champion === null) {
            return null;
        }

        $statement = $this->connection->prepare(
            "SELECT j.id_jogo, j.nome_jogo, j.status_jogo,
                    p.equipes_id_equipe, p.resultado_partida
             FROM jogos j INNER JOIN partidas p ON p.jogos_id_jogo = j.id_jogo
             WHERE j.modalidades_id_modalidade = ? AND j.nome_jogo LIKE 'MM:4:%'
               AND j.status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY j.id_jogo ASC, p.id_partida ASC",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar as semifinais.');
        }
        $statement->bind_param('i', $modalityId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar as semifinais.');
        }
        $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
        $statement->close();

        $semifinais = [];
        foreach ($rows as $row) {
            $meta = ChaveamentoRules::parse((string) $row['nome_jogo']);
            if ($meta === null || $meta['largura'] !== 4 || isset($meta['posicao'])) {
                continue;
            }
            $id = (int) $row['id_jogo'];
            $semifinais[$id] ??= ['kind' => $meta['kind'], 'partidas' => []];
            $semifinais[$id]['partidas'][] = [
                'equipes_id_equipe' => (int) $row['equipes_id_equipe'],
                'resultado_partida' => (int) $row['resultado_partida'],
            ];
        }

        return ChaveamentoRules::terceiroLugarDoCampeao($champion, array_values($semifinais));
    }

    /** @return array{nome_turma:string,nome_fantasia_turma:string}|null */
    private function teamFromId(int $teamId, int $modalityId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT t.nome_turma, t.nome_fantasia_turma
             FROM equipes e INNER JOIN turmas t ON t.id_turma = e.turmas_id_turma
             WHERE e.id_equipe = ? AND e.modalidades_id_modalidade = ? LIMIT 1',
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $statement->bind_param('ii', $teamId, $modalityId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }

    /** @return array{nome_turma:string,nome_fantasia_turma:string}|null */
    private function legacyThirdFromModality(int $modalityId): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT t.nome_turma, t.nome_fantasia_turma
             FROM partidas p INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
             INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
             INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
             WHERE j.modalidades_id_modalidade = ? AND j.nome_jogo = 'POS:3:0:N'
               AND j.status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY p.resultado_partida DESC, p.equipes_id_equipe ASC LIMIT 1",
        );
        if ($statement === false) {
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $statement->bind_param('i', $modalityId);
        if (!$statement->execute()) {
            $statement->close();
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $row = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        return $row;
    }
}
