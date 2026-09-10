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

        $admin = new TestClient();
        $admin->login('admin', '123');

        // Buscar uma turma da edição recém-criada. Alunos são registros por
        // edição; não mover o aluno legado 2879 de outra edição para este
        // cenário, pois isso mascararia a chave composta de matrícula.
        $resTurmas = $admin->get("api/v1/turmas?id_interclasse=$idEdicao");
        $turmas = $resTurmas['json'] ?? [];
        $idTurmaAluno = (int) ($turmas[0]['id_turma'] ?? 0);

        $matricula = '9' . date('ymdHis') . random_int(10, 99);
        $novoAluno = $admin->postJson('api/v1/usuarios?acao=criar_aluno', [
            'nome_usuario' => 'Aluno de Inscrição',
            'matricula_usuario' => $matricula,
            'data_nasc_usuario' => '2010-01-01',
            'genero_usuario' => 'MASC',
            'turmas_id_turma' => $idTurmaAluno,
        ]);
        Assertions::assertStatus('Aluno de teste vinculado à edição', $novoAluno, 200);
        $idAluno = (int) ($novoAluno['json']['id_usuario'] ?? $novoAluno['json']['id'] ?? 0);
        $senha = (string) ($novoAluno['json']['senha_temporaria'] ?? '');
        $aluno = new TestClient();
        $loginAluno = $aluno->login($matricula, $senha);
        Assertions::assertJsonSuccess('Aluno de teste autenticado para inscrição', $loginAluno);
        Assertions::assertJsonSuccess(
            'Aluno de teste aceita os termos antes da inscrição',
            $aluno->postJson('api/v1/termos', []),
        );

        $resEqTurma = $admin->get("api/v1/equipes?id_interclasse=$idEdicao&id_turma=$idTurmaAluno");
        $eqsTurma = $resEqTurma['json'] ?? [];

        if (count($eqsTurma) < 4) {
            $eqsTurma = $equipes;
        }

        $idEq1 = (int) ($eqsTurma[0]['id_equipe'] ?? 0);
        $idEq2 = (int) ($eqsTurma[1]['id_equipe'] ?? 0);
        $idEq3 = (int) ($eqsTurma[2]['id_equipe'] ?? 0);
        $idEq4 = (int) ($eqsTurma[3]['id_equipe'] ?? 0);

        // 10.1 Rejeição de inscrição com mais de 3 modalidades
        $resExcesso = $aluno->postJson('api/v1/inscricoes', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1, $idEq2, $idEq3, $idEq4]
        ]);
        Assertions::assert("Bloqueio de inscrição em mais de 3 modalidades", $resExcesso['code'] === 400 || ($resExcesso['json']['success'] ?? true) === false);

        // 10.2 Inscrição válida em até 3 modalidades
        $resInscricao = $aluno->postJson('api/v1/inscricoes', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1, $idEq2]
        ]);
        Assertions::assertStatus("Inscrição válida retorna sucesso", $resInscricao, 200);
        Assertions::assert("Inscrição válida confirma sucesso no corpo", ($resInscricao['json']['success'] ?? false) === true);
        foreach ([$idEq1, $idEq2] as $idEquipe) {
            $membros = $admin->get('api/v1/equipes?id_equipe=' . $idEquipe);
            $encontrado = false;
            foreach (($membros['json'] ?? []) as $membro) {
                if ((int) ($membro['id_usuario'] ?? 0) === $idAluno) {
                    $encontrado = true;
                    break;
                }
            }
            Assertions::assert("Inscrição persistida na equipe {$idEquipe}", $encontrado);
        }

        // 10.3 Bloqueio de inscrição por usuário anônimo
        $anonimo = new TestClient();
        $resAnon = $anonimo->postJson('api/v1/inscricoes', [
            'id_interclasse' => $idEdicao,
            'id_equipes' => [$idEq1]
        ]);
        Assertions::assertStatus("Bloqueio de inscrição sem autenticação (HTTP 401)", $resAnon, 401);
    }
}
