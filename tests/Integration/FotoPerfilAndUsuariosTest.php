<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use CURLFile;

class FotoPerfilAndUsuariosTest
{
    public static function run(): void
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

        @unlink($tmpImg);
    }
}
