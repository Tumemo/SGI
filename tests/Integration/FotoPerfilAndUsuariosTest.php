<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use CURLFile;

class FotoPerfilAndUsuariosTest
{
    public static function run(int $idTurma, int $idEdicao): void
    {
        echo "\n  \033[1;34m[Suite 12: Gestão de Fotos de Perfil e Usuários]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 12.1 Consultar dados do usuário atual autenticado
        $resAuth = $admin->get('api/auth.php');
        Assertions::assertStatus("Consulta de sessão autenticada (HTTP 200)", $resAuth, 200);
        $user = $resAuth['json']['usuario'] ?? [];
        $idUser = (int) ($user['id'] ?? 0);
        Assertions::assert("Identificação de usuário autenticado", $idUser > 0);

        // 12.2 Testar upload de foto de perfil
        $tmpImg = sys_get_temp_dir() . '/test_avatar.png';
        // Cria imagem PNG 1x1 em bytes válidos
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpImg, $pngContent);

        $cfile = new CURLFile($tmpImg, 'image/png', 'test_avatar.png');
        $resUpload = $admin->postForm('api/foto.php', [
            'foto' => $cfile
        ]);
        Assertions::assert("Upload de foto de perfil (PNG)", in_array($resUpload['code'], [200, 201], true) && ($resUpload['json']['success'] ?? false) === true);

        // 12.3 Consultar foto de perfil
        $resFoto = $admin->get("api/foto.php?user_id=$idUser");
        Assertions::assertStatus("Consulta de foto de perfil (HTTP 200)", $resFoto, 200);

        // 12.4 Remover foto de perfil
        $resDelete = $admin->deleteJson('api/foto.php');
        Assertions::assertJsonSuccess("Exclusão de foto de perfil retorna confirmação válida", $resDelete);
        $after = $admin->get("api/foto.php?user_id=$idUser");
        Assertions::assert('Foto removida não permanece referenciada no perfil', ($after['json']['foto_usuario'] ?? null) === '');

        // 12.5 Cobrir as ações administrativas que antes ficavam no arquivo procedural.
        $staff = $admin->postJson('api/usuarios.php?acao=cadastrar_usuario', [
            'nome_usuario' => 'Colaborador de contrato',
            'matricula_usuario' => '991234',
            'senha_usuario' => 'senhaSegura123',
            'data_nasc_usuario' => '1990-01-01',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Cadastro de colaborador pela rota modular', $staff);
        $staffId = (int) ($staff['json']['id_usuario'] ?? 0);
        Assertions::assert('Cadastro de colaborador retorna identificador', $staffId > 0);
        $role = $admin->postJson('api/usuarios.php?acao=atualizar_colaborador', [
            'id_usuario' => $staffId,
            'is_mesario_clicado' => '1',
        ]);
        Assertions::assertJsonSuccess('Atualização de papel do colaborador', $role);
        $details = $admin->postJson('api/usuarios.php?acao=atualizar_dados_colaborador', [
            'id_usuario' => $staffId,
            'nome_usuario' => 'Colaborador atualizado',
            'matricula_usuario' => '991234',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Atualização de dados do colaborador', $details);
        $removeStaff = $admin->postJson('api/usuarios.php?acao=excluir_colaborador', ['id_usuario' => $staffId]);
        Assertions::assertJsonSuccess('Exclusão de colaborador pela rota modular', $removeStaff);

        $student = $admin->postJson('api/usuarios.php?acao=criar_aluno', [
            'nome_usuario' => 'Aluno de contrato',
            'matricula_usuario' => '991235',
            'data_nasc_usuario' => '2010-02-03',
            'genero_usuario' => 'MASC',
            'turmas_id_turma' => $idTurma,
        ]);
        Assertions::assertJsonSuccess('Cadastro de aluno pela rota modular', $student);
        $studentId = (int) ($student['json']['id_usuario'] ?? 0);
        Assertions::assert('Cadastro de aluno retorna identificador', $studentId > 0);
        $editStudent = $admin->postJson('api/usuarios.php?acao=editar_aluno', [
            'id_usuario' => $studentId,
            'nome_usuario' => 'Aluno de contrato atualizado',
            'matricula_usuario' => '991235',
            'data_nasc_usuario' => '2010-02-03',
            'genero_usuario' => 'MASC',
        ]);
        Assertions::assertJsonSuccess('Edição de aluno pela rota modular', $editStudent);
        $resetStudent = $admin->postJson('api/usuarios.php?acao=resetar_senha_aluno', ['id_usuario' => $studentId]);
        Assertions::assertJsonSuccess('Redefinição de senha de aluno', $resetStudent);
        $removeStudent = $admin->postJson('api/usuarios.php?acao=excluir_aluno', ['id_usuario' => $studentId]);
        Assertions::assertJsonSuccess('Exclusão de aluno pela rota modular', $removeStudent);

        @unlink($tmpImg);
    }
}
