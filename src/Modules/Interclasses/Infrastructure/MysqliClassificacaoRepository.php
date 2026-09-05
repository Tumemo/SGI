<?php

declare(strict_types=1);

namespace App\Modules\Interclasses\Infrastructure;

use App\Modules\Interclasses\Domain\ClassificacaoRepository;
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

        $third = $this->connection->prepare(
            "SELECT t.nome_turma, t.nome_fantasia_turma
             FROM partidas p
             INNER JOIN jogos j ON p.jogos_id_jogo = j.id_jogo
             INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
             INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
             WHERE j.modalidades_id_modalidade = ? AND j.nome_jogo = 'POS:3:0:N'
               AND j.status_jogo IN ('Concluido', 'Finalizado')
             ORDER BY p.resultado_partida DESC LIMIT 1",
        );
        if ($third === false) {
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $third->bind_param('i', $modalityId);
        if (!$third->execute()) {
            $third->close();
            throw new RuntimeException('Não foi possível consultar o terceiro lugar.');
        }
        $thirdRow = $third->get_result()->fetch_assoc();
        $third->close();
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
            'SELECT t.nome_turma, t.nome_fantasia_turma, p.resultado_partida
             FROM partidas p
             INNER JOIN equipes e ON p.equipes_id_equipe = e.id_equipe
             INNER JOIN turmas t ON e.turmas_id_turma = t.id_turma
             WHERE p.jogos_id_jogo = ? ORDER BY p.resultado_partida DESC',
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
}
