<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;

class AlunosPortalTest
{
    public static function run(): void
    {
        echo "\n  \033[1;34m[Suite 8: Portal do Aluno, Termos e Inscrições]\033[0m\n";

        $aluno = new TestClient();
        $aluno->login('2879', '123');

        // 8.1 Aceitar termos de participação
        $resAceite = $aluno->postJson('api/concordarTermos.php', []);
        Assertions::assert("Aceite digital de termos de participação esportiva", ($resAceite['json']['success'] ?? false) === true);

        // 8.2 Consultar status do termo
        $resStatusTermo = $aluno->get('api/concordarTermos.php');
        Assertions::assertStatus("Consulta de termos (HTTP 200)", $resStatusTermo, 200);
        Assertions::assert("Termo marcado como aceito (termo_aceito: true)", ($resStatusTermo['json']['termo_aceito'] ?? false) === true);

        // 8.3 Acessar páginas do portal do aluno
        $paginas = ['home.php', 'modalidade.php', 'jogos.php', 'termos.php', 'perfil.php'];
        foreach ($paginas as $p) {
            $resPage = $aluno->get("views/src/pages/alunos/$p");
            Assertions::assertStatus("Renderização da tela de aluno [$p]", $resPage, 200);
        }
    }
}
