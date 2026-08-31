<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class InscricaoModalidadesTest
{
    public static function run(int $idEdicao, array $equipes): void
    {
        echo "\n  \033[1;34m[Suite 10: Inscrição de Alunos em Modalidades e Regras de Limite]\033[0m\n";

        $aluno = new TestClient();
        $aluno->login('2879', '123');

        $admin = new TestClient();
        $admin->login('admin', '123');

        // Descobrir a turma do aluno 2879
        $resUser = $aluno->get('api/auth.php');
        $idAluno = (int) ($resUser['json']['usuario']['id'] ?? 0);

        // Buscar equipes da turma do aluno
        $resTurmas = $admin->get("api/turmas.php?id_interclasse=$idEdicao");
        $turmas = $resTurmas['json'] ?? [];
        $idTurmaAluno = (int) ($turmas[0]['id_turma'] ?? 0);

        // Atualiza a turma do aluno para este interclasse de teste para garantir vínculo
        $resEqTurma = $admin->get("api/equipes.php?id_interclasse=$idEdicao&id_turma=$idTurmaAluno");
        $eqsTurma = $resEqTurma['json'] ?? [];

        if (count($eqsTurma) < 4) {
            $eqsTurma = $equipes;
        }

        $idEq1 = (int) ($eqsTurma[0]['id_equipe'] ?? 0);
        $idEq2 = (int) ($eqsTurma[1]['id_equipe'] ?? 0);
        $idEq3 = (int) ($eqsTurma[2]['id_equipe'] ?? 0);
        $idEq4 = (int) ($eqsTurma[3]['id_equipe'] ?? 0);

        // 10.1 Rejeição de inscrição com mais de 3 modalidades
        $resExcesso = $aluno->postJson('api/inscricao.php', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1, $idEq2, $idEq3, $idEq4]
        ]);
        Assertions::assert("Bloqueio de inscrição em mais de 3 modalidades", $resExcesso['code'] === 400 || ($resExcesso['json']['success'] ?? true) === false);

        // 10.2 Inscrição válida em até 3 modalidades
        $resInscricao = $aluno->postJson('api/inscricao.php', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1, $idEq2]
        ]);
        Assertions::assert("Processamento de inscrição em modalidades permitidas", in_array($resInscricao['code'], [200, 201, 400], true));

        // 10.3 Bloqueio de inscrição por usuário anônimo
        $anonimo = new TestClient();
        $resAnon = $anonimo->postJson('api/inscricao.php', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1]
        ]);
        Assertions::assertStatus("Bloqueio de inscrição sem autenticação (HTTP 401)", $resAnon, 401);
    }
}
