<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use CURLFile;

class TurmasAndPdfImportTest
{
    public static function run(int $idEdicao): int
    {
        echo "\n  \033[1;34m[Suite 3: Turmas e Importação de Alunos via PDF]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 3.1 Consultar turmas geradas automaticamente
        $resTurmas = $admin->get("api/turmas.php?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de turmas da edição (HTTP 200)", $resTurmas, 200);
        $turmas = $resTurmas['json'] ?? [];
        Assertions::assert("Total de 7 turmas padrão geradas (6EF ao 3EMA)", count($turmas) === 7, "Total: " . count($turmas));

        $turmaAlvo = $turmas[0] ?? null;
        $idTurma = (int) ($turmaAlvo['id_turma'] ?? 0);
        Assertions::assert("ID válido para turma de teste", $idTurma > 0);

        // 3.2 Upload de PDF de alunos
        $pdfPath = dirname(__DIR__, 2) . '/docs/lista_alunos/6EFB.pdf';
        if (!file_exists($pdfPath)) {
            $pdfPath = 'C:/xampp/htdocs/SGI/docs/lista_alunos/6EFB.pdf';
        }

        if (file_exists($pdfPath) && $idTurma > 0) {
            $cfile = new CURLFile($pdfPath, 'application/pdf', '6EFB.pdf');
            $resUpload = $admin->postForm('api/upload_turma_pdf.php', [
                'pdf_arquivo' => $cfile,
                'id_turma' => (string) $idTurma,
                'id_interclasse' => (string) $idEdicao
            ]);
            Assertions::assertJsonSuccess("Upload e extração automática de alunos via PDF", $resUpload);

            // 3.3 Listar competidores cadastrados
            $resAlunos = $admin->get("api/usuarios.php?acao=listar_competidores&id_turma=$idTurma&id_interclasse=$idEdicao");
            Assertions::assertStatus("Listagem de competidores da turma (HTTP 200)", $resAlunos, 200);
            $competidores = $resAlunos['json']['competidores'] ?? [];
            Assertions::assert("Alunos inseridos no banco de dados (esperado >= 20)", count($competidores) >= 20, "Total: " . count($competidores));

            // Validação dos dados do primeiro aluno
            $primeiro = $competidores[0] ?? [];
            Assertions::assert("Aluno possui nome válido", !empty($primeiro['nome_usuario']));
            Assertions::assert("Aluno possui matrícula/RM", !empty($primeiro['matricula_usuario']));
            Assertions::assert("Aluno cadastrado com nível 3 (competidor)", (string)($primeiro['nivel_usuario'] ?? '') === '3');
        } else {
            Assertions::assert("Arquivo de PDF disponível para teste", false, "6EFB.pdf não localizado");
        }

        return $idTurma;
    }
}
