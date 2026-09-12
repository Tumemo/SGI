<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;

class ModalidadesAndEquipesTest
{
    public static function run(int $idEdicao): array
    {
        echo "\n  \033[1;34m[Suite 4: Categorias, Modalidades e Equipes]\033[0m\n";

        $admin = new TestClient();
        $admin->login('admin', '123');

        // 4.1 Categorias
        $resCat = $admin->get("api/v1/categorias?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de categorias (HTTP 200)", $resCat, 200);
        $cats = $resCat['json'] ?? [];
        Assertions::assert("Criação de 2 categorias escolares (I e II)", count($cats) === 2);

        $categoriaI = $cats[0] ?? [];
        $categoriaII = $cats[1] ?? [];
        $duplicada = $admin->postJson('api/v1/categorias', [
            'nome_categoria' => (string) ($categoriaI['nome_categoria'] ?? 'Categoria I'),
            'interclasses_id_interclasse' => $idEdicao,
        ]);
        Assertions::assertStatus('Categoria duplicada na mesma edição retorna conflito', $duplicada, 409);
        $aposDuplicada = $admin->get("api/v1/categorias?id_interclasse=$idEdicao");
        Assertions::assert('Tentativa de categoria duplicada não altera a lista', ($aposDuplicada['json'] ?? null) === $cats);

        $renomeada = $admin->putJson('api/v1/categorias', [
            'id_categoria' => (int) ($categoriaI['id_categoria'] ?? 0),
            'nome_categoria' => (string) ($categoriaII['nome_categoria'] ?? 'Categoria II'),
        ]);
        Assertions::assertStatus('Renomear categoria para nome já utilizado retorna conflito', $renomeada, 409);
        $categoriaPreservada = $admin->get('api/v1/categorias?id_categoria=' . (int) ($categoriaI['id_categoria'] ?? 0));
        $categoriaPreservadaDados = $categoriaPreservada['json'][0] ?? [];
        Assertions::assert('Conflito de renomeação não altera a categoria original', ($categoriaPreservadaDados['nome_categoria'] ?? null) === ($categoriaI['nome_categoria'] ?? null));

        // 4.2 Modalidades
        $resMod = $admin->get("api/v1/modalidades?id_interclasse=$idEdicao");
        Assertions::assertStatus("Consulta de modalidades (HTTP 200)", $resMod, 200);
        $mods = $resMod['json'] ?? [];
        Assertions::assert("Total de modalidades padrão (esperado >= 10)", count($mods) >= 10, "Total: " . count($mods));

        self::validarEntradasDeLimites(
            $admin,
            $idEdicao,
            (int) ($categoriaI['id_categoria'] ?? 0),
            (int) ($mods[0]['id_tipo_modalidade'] ?? 0),
            (int) ($mods[0]['id_modalidade'] ?? 0),
        );

        // 4.3 Encontrar modalidade com 4 equipes para confrontos
        $modEscolhida = null;
        $equipesEscolhidas = [];
        foreach ($mods as $m) {
            $idM = (int) $m['id_modalidade'];
            $resEq = $admin->get("api/v1/equipes?id_modalidade=$idM");
            $eqs = $resEq['json'] ?? [];
            if (count($eqs) >= 4) {
                $modEscolhida = $m;
                $equipesEscolhidas = $eqs;
                break;
            }
        }

        Assertions::assert("Localização de modalidade mata-mata com pelo menos 4 equipes", $modEscolhida !== null && count($equipesEscolhidas) >= 4);

        if ($modEscolhida !== null) {
            self::validarEscopoEdicaoCategoria(
                $admin,
                $idEdicao,
                (int) ($categoriaI['id_categoria'] ?? 0),
                (int) ($mods[0]['id_tipo_modalidade'] ?? 0),
                (int) $modEscolhida['id_modalidade'],
            );
        }

        // 4.4 Verificar vínculo turma x modalidade nas equipes
        $eq1 = $equipesEscolhidas[0] ?? [];
        Assertions::assert("Equipe possui id_turma vinculado", !empty($eq1['turmas_id_turma']));
        Assertions::assert("Equipe possui nome de turma atribuído", !empty($eq1['nome_turma']));

        $generated = $admin->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]);
        Assertions::assertJsonSuccess('Geração de equipes pela rota versionada', $generated);
        $beforeRepeat = $admin->get("api/v1/equipes?id_interclasse=$idEdicao");
        $repeated = $admin->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]);
        $afterRepeat = $admin->get("api/v1/equipes?id_interclasse=$idEdicao");
        Assertions::assert('Repetir geração pela URL antiga não duplica equipes', ($repeated['json']['success'] ?? false) && $beforeRepeat['json'] === $afterRepeat['json']);
        $anonymous = new TestClient();
        Assertions::assertStatus('Geração de equipes exige autenticação', $anonymous->postJson('api/v1/equipes/gerar', ['id_interclasse' => $idEdicao]), 401);

        return [
            'modalidade' => $modEscolhida,
            'equipes' => $equipesEscolhidas
        ];
    }

    private static function validarEntradasDeLimites(TestClient $admin, int $editionId, int $categoryId, int $typeId, int $existingModalityId): void
    {
        $invalid = [
            ['max_inscrito_modalidade', -1],
            ['max_inscrito_modalidade', -0.5],
            ['max_inscrito_modalidade', '1.5'],
            ['max_inscrito_modalidade', true],
            ['max_inscrito_modalidade', [1]],
            ['max_inscrito_modalidade', 2147483648],
            ['max_equipes', 'abc'],
            ['max_equipes', -0.5],
            ['max_equipes', 1.5],
            ['max_equipes', true],
            ['max_equipes', [1]],
            ['max_equipes', 2147483648],
            ['status_modalidade', '2'],
        ];
        foreach ($invalid as [$field, $value]) {
            $name = 'N09-' . bin2hex(random_bytes(8));
            $payload = [
                'nome_modalidade' => $name,
                'genero_modalidade' => 'MASC',
                'max_inscrito_modalidade' => 10,
                'max_equipes' => 2,
                'tipos_modalidades_id_tipo_modalidade' => $typeId,
                'status_modalidade' => '0',
                'categorias_id_categoria' => $categoryId,
                'interclasses_id_interclasse' => $editionId,
                $field => $value,
            ];
            try {
                $response = $admin->postJson('api/v1/modalidades', $payload);
                Assertions::assertStatus("POST modalidade rejeita {$field} inválido", $response, 400);
                $createdId = self::modalityIdByName($name);
                Assertions::assert("POST inválido {$field} não persiste modalidade", $createdId === null);
            } finally {
                self::deleteModalityByName($name);
            }
        }

        $before = self::modalityLimits($existingModalityId);
        Assertions::assert('Fixture para testar atualização parcial de limites existe', $before !== null);
        if ($before === null) {
            return;
        }
        foreach ($invalid as [$field, $value]) {
            try {
                $response = $admin->putJson('api/v1/modalidades', [
                    'id_modalidade' => $existingModalityId,
                    $field => $value,
                ]);
                Assertions::assertStatus("PUT modalidade rejeita {$field} inválido", $response, 400);
                Assertions::assert("PUT inválido {$field} preserva valores existentes", self::modalityLimits($existingModalityId) === $before);
            } finally {
                self::restoreModalityLimits($existingModalityId, $before);
            }
        }

        $accepted = [
            ['max_inscrito_modalidade', null, 0],
            ['max_inscrito_modalidade', '', 0],
            ['max_inscrito_modalidade', 0, 0],
            ['max_inscrito_modalidade', '0', 0],
            ['max_inscrito_modalidade', 1, 1],
            ['max_inscrito_modalidade', 2147483647, 2147483647],
            ['max_equipes', null, 0],
            ['max_equipes', '', 0],
            ['max_equipes', 0, 0],
            ['max_equipes', '0', 0],
            ['max_equipes', 1, 1],
            ['max_equipes', 2147483647, 2147483647],
            ['status_modalidade', 0, '0'],
            ['status_modalidade', 1, '1'],
            ['status_modalidade', '0', '0'],
            ['status_modalidade', '1', '1'],
        ];
        foreach ($accepted as [$field, $value, $expectedValue]) {
            try {
                $response = $admin->putJson('api/v1/modalidades', [
                    'id_modalidade' => $existingModalityId,
                    $field => $value,
                ]);
                Assertions::assertStatus("PUT modalidade aceita entrada válida em {$field}", $response, 200);
                $expected = $before;
                $expected[$field] = $expectedValue;
                Assertions::assert("PUT mantém a normalização válida de {$field}", self::modalityLimits($existingModalityId) === $expected);
            } finally {
                self::restoreModalityLimits($existingModalityId, $before);
            }
        }
    }

    private static function modalityIdByName(string $name): ?int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('SELECT id_modalidade FROM modalidades WHERE nome_modalidade = ? LIMIT 1');
        $statement->bind_param('s', $name);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        $connection->close();

        return $value === false ? null : (int) $value;
    }

    private static function deleteModalityByName(string $name): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('DELETE FROM modalidades WHERE nome_modalidade = ?');
        $statement->bind_param('s', $name);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    /** @return array{max_inscrito_modalidade:?int,max_equipes:?int,status_modalidade:string}|null */
    private static function modalityLimits(int $id): ?array
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('SELECT max_inscrito_modalidade, max_equipes, status_modalidade FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        $connection->close();
        if ($row === null) {
            return null;
        }

        return [
            'max_inscrito_modalidade' => $row['max_inscrito_modalidade'] === null ? null : (int) $row['max_inscrito_modalidade'],
            'max_equipes' => $row['max_equipes'] === null ? null : (int) $row['max_equipes'],
            'status_modalidade' => (string) $row['status_modalidade'],
        ];
    }

    /** @param array{max_inscrito_modalidade:?int,max_equipes:?int,status_modalidade:string} $limits */
    private static function restoreModalityLimits(int $id, array $limits): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('UPDATE modalidades SET max_inscrito_modalidade = ?, max_equipes = ?, status_modalidade = ? WHERE id_modalidade = ?');
        $students = $limits['max_inscrito_modalidade'];
        $teams = $limits['max_equipes'];
        $status = $limits['status_modalidade'];
        $statement->bind_param('iisi', $students, $teams, $status, $id);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    private static function validarEscopoEdicaoCategoria(TestClient $admin, int $editionA, int $categoryA, int $typeId, int $modalityWithTeams): void
    {
        $marker = bin2hex(random_bytes(8));
        $mismatchedName = "N10 mismatch {$marker}";
        $editionB = 0;
        $categoryB = 0;
        $inactiveCategoryB = 0;
        $candidateId = null;
        $existingScope = self::modalityScope($modalityWithTeams);
        $candidateName = "N10 candidate {$marker}";
        Assertions::assert('Fixture de modalidade vinculada para testar transferência existe', $existingScope !== null);
        if ($existingScope === null) {
            return;
        }

        try {
            $editionB = self::createTemporaryEdition("N10-{$marker}");
            $categoryB = self::createTemporaryCategory($editionB, "N10-{$marker}");
            $inactiveCategoryB = self::createTemporaryCategory($editionB, "N10 inactive {$marker}", '0');
            $mismatchedCreate = [
                'nome_modalidade' => $mismatchedName,
                'genero_modalidade' => 'MASC',
                'max_inscrito_modalidade' => 10,
                'max_equipes' => 2,
                'tipos_modalidades_id_tipo_modalidade' => $typeId,
                'status_modalidade' => '1',
                'categorias_id_categoria' => $categoryB,
                'interclasses_id_interclasse' => $editionA,
            ];
            try {
                $response = $admin->postJson('api/v1/modalidades', $mismatchedCreate);
                Assertions::assertStatus('POST rejeita modalidade ligada a categoria de outra edição', $response, 400);
                Assertions::assert('POST de edição/categoria incompatíveis não persiste modalidade', self::modalityIdByName($mismatchedName) === null);
            } finally {
                self::deleteModalityByName($mismatchedName);
            }

            $inactiveCategoryName = "N10 inactive category {$marker}";
            try {
                $response = $admin->postJson('api/v1/modalidades', [
                    ...$mismatchedCreate,
                    'nome_modalidade' => $inactiveCategoryName,
                    'categorias_id_categoria' => $inactiveCategoryB,
                    'interclasses_id_interclasse' => $editionB,
                ]);
                Assertions::assertStatus('POST rejeita categoria inativa segundo o fluxo atual', $response, 400);
                Assertions::assert('POST com categoria inativa não persiste modalidade', self::modalityIdByName($inactiveCategoryName) === null);
            } finally {
                self::deleteModalityByName($inactiveCategoryName);
            }

            $missingEditionName = "N10 missing edition {$marker}";
            try {
                $response = $admin->postJson('api/v1/modalidades', [
                    ...$mismatchedCreate,
                    'nome_modalidade' => $missingEditionName,
                    'categorias_id_categoria' => $categoryA,
                    'interclasses_id_interclasse' => 2147483647,
                ]);
                Assertions::assertStatus('POST rejeita edição inexistente antes da gravação', $response, 400);
                Assertions::assert('POST com edição inexistente não persiste modalidade', self::modalityIdByName($missingEditionName) === null);
            } finally {
                self::deleteModalityByName($missingEditionName);
            }

            $candidate = $admin->postJson('api/v1/modalidades', [
                ...$mismatchedCreate,
                'nome_modalidade' => $candidateName,
                'categorias_id_categoria' => $categoryA,
            ]);
            Assertions::assertStatus('POST cria modalidade com edição e categoria correspondentes', $candidate, 201);
            $candidateId = self::modalityIdByName($candidateName);
            Assertions::assert('Modalidade sem descendentes da fixture de escopo foi criada', $candidateId !== null);
            if ($candidateId === null) {
                return;
            }

            $candidateScope = self::modalityScope($candidateId);
            Assertions::assert('Fixture temporária de transferência possui vínculos consultáveis', $candidateScope !== null);
            if ($candidateScope === null) {
                return;
            }

            foreach ([
                ['categorias_id_categoria' => $categoryB],
                ['interclasses_id_interclasse' => $editionB],
            ] as $partialUpdate) {
                try {
                    $response = $admin->putJson('api/v1/modalidades', ['id_modalidade' => $candidateId, ...$partialUpdate]);
                    Assertions::assertStatus('PUT parcial rejeita edição/categoria incompatíveis no estado final', $response, 400);
                    Assertions::assert('PUT parcial inválido preserva ambos os vínculos', self::modalityScope($candidateId) === $candidateScope);
                } finally {
                    self::restoreModalityScope($candidateId, $candidateScope);
                }
            }

            try {
                $response = $admin->putJson('api/v1/modalidades', [
                    'id_modalidade' => $candidateId,
                    'categorias_id_categoria' => $categoryB,
                    'interclasses_id_interclasse' => $editionB,
                ]);
                Assertions::assertStatus('PUT permite trocar os dois vínculos juntos sem descendentes', $response, 200);
                Assertions::assert('PUT grava o par final correspondente', self::modalityScope($candidateId) === [
                    'categorias_id_categoria' => $categoryB,
                    'interclasses_id_interclasse' => $editionB,
                ]);
            } finally {
                self::restoreModalityScope($candidateId, $candidateScope);
            }

            try {
                $response = $admin->putJson('api/v1/modalidades', [
                    'id_modalidade' => $modalityWithTeams,
                    'categorias_id_categoria' => $categoryB,
                    'interclasses_id_interclasse' => $editionB,
                ]);
                Assertions::assertStatus('PUT bloqueia troca de edição quando já há equipes vinculadas', $response, 400);
                Assertions::assert('Bloqueio da transferência preserva os vínculos originais', self::modalityScope($modalityWithTeams) === $existingScope);
            } finally {
                self::restoreModalityScope($modalityWithTeams, $existingScope);
            }

            $gameId = self::createTemporaryGame($candidateId, "N10 {$marker}");
            try {
                self::assertTransferBlocked($admin, $candidateId, $categoryB, $editionB, $candidateScope, 'jogo vinculado');
            } finally {
                self::deleteTemporaryGame($gameId);
            }

            $classId = self::classIdForCategory($editionA, $categoryA);
            Assertions::assert('Fixture de turma compatível existe para testar crédito', $classId !== null);
            if ($classId !== null) {
                $creditId = self::createTemporaryPodiumCredit($editionA, $candidateId, $classId);
                try {
                    self::assertTransferBlocked($admin, $candidateId, $categoryB, $editionB, $candidateScope, 'crédito de pódio vinculado');
                } finally {
                    self::deleteTemporaryPodiumCredit($creditId);
                }
            }

            $reservationId = self::createTemporaryReservation($editionA, $candidateId, "N10:{$marker}");
            try {
                self::assertTransferBlocked($admin, $candidateId, $categoryB, $editionB, $candidateScope, 'reserva de agenda vinculada');
            } finally {
                self::deleteTemporaryReservation($reservationId);
            }
        } finally {
            if ($candidateId !== null) {
                self::deleteModalityByName($candidateName);
            }
            if ($editionB > 0) {
                self::deleteCategoriesAndEdition(array_filter([$categoryB, $inactiveCategoryB]), $editionB);
            }
        }
    }

    private static function createTemporaryEdition(string $name): int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare("INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse) VALUES (?, '2026-09-12 00:00:00', '', '0')");
        $statement->bind_param('s', $name);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        $connection->close();

        return $id;
    }

    private static function createTemporaryCategory(int $editionId, string $suffix, string $status = '1'): int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $name = 'N10 Category ' . $suffix;
        $statement = $connection->prepare('INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, ?, ?)');
        $statement->bind_param('ssi', $name, $status, $editionId);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        $connection->close();

        return $id;
    }

    /** @param list<int> $categoryIds */
    private static function deleteCategoriesAndEdition(array $categoryIds, int $editionId): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $category = $connection->prepare('DELETE FROM categorias WHERE id_categoria = ?');
        foreach ($categoryIds as $categoryId) {
            $category->bind_param('i', $categoryId);
            $category->execute();
        }
        $category->close();
        $edition = $connection->prepare('DELETE FROM interclasses WHERE id_interclasse = ?');
        $edition->bind_param('i', $editionId);
        $edition->execute();
        $edition->close();
        $connection->close();
    }

    /** @return array{categorias_id_categoria:int,interclasses_id_interclasse:int}|null */
    private static function modalityScope(int $id): ?array
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('SELECT categorias_id_categoria, interclasses_id_interclasse FROM modalidades WHERE id_modalidade = ? LIMIT 1');
        $statement->bind_param('i', $id);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        $connection->close();
        if ($row === null) {
            return null;
        }

        return [
            'categorias_id_categoria' => (int) $row['categorias_id_categoria'],
            'interclasses_id_interclasse' => (int) $row['interclasses_id_interclasse'],
        ];
    }

    /** @param array{categorias_id_categoria:int,interclasses_id_interclasse:int} $scope */
    private static function restoreModalityScope(int $id, array $scope): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('UPDATE modalidades SET categorias_id_categoria = ?, interclasses_id_interclasse = ? WHERE id_modalidade = ?');
        $category = $scope['categorias_id_categoria'];
        $edition = $scope['interclasses_id_interclasse'];
        $statement->bind_param('iii', $category, $edition, $id);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    private static function assertTransferBlocked(TestClient $admin, int $modalityId, int $targetCategoryId, int $targetEditionId, array $expectedScope, string $relatedRecord): void
    {
        try {
            $response = $admin->putJson('api/v1/modalidades', [
                'id_modalidade' => $modalityId,
                'categorias_id_categoria' => $targetCategoryId,
                'interclasses_id_interclasse' => $targetEditionId,
            ]);
            Assertions::assertStatus("PUT bloqueia transferência com {$relatedRecord}", $response, 400);
            Assertions::assert("Bloqueio com {$relatedRecord} preserva os vínculos", self::modalityScope($modalityId) === $expectedScope);
        } finally {
            self::restoreModalityScope($modalityId, $expectedScope);
        }
    }

    private static function createTemporaryGame(int $modalityId, string $name): int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare("INSERT INTO jogos (nome_jogo, data_jogo, inicio_jogo, status_jogo, modalidades_id_modalidade, locais_id_local) VALUES (?, NULL, NULL, 'Agendado', ?, NULL)");
        $statement->bind_param('si', $name, $modalityId);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        $connection->close();
        return $id;
    }

    private static function deleteTemporaryGame(int $id): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('DELETE FROM jogos WHERE id_jogo = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    private static function classIdForCategory(int $editionId, int $categoryId): ?int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('SELECT id_turma FROM turmas WHERE interclasses_id_interclasse = ? AND categorias_id_categoria = ? LIMIT 1');
        $statement->bind_param('ii', $editionId, $categoryId);
        $statement->execute();
        $value = $statement->get_result()->fetch_column();
        $statement->close();
        $connection->close();
        return $value === false ? null : (int) $value;
    }

    private static function createTemporaryPodiumCredit(int $editionId, int $modalityId, int $classId): int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $position = 1;
        $points = 0;
        $origin = 'N10a_test';
        $statement = $connection->prepare('INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, pontos, origem_registro) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->bind_param('iiiiis', $editionId, $modalityId, $position, $classId, $points, $origin);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        $connection->close();
        return $id;
    }

    private static function deleteTemporaryPodiumCredit(int $id): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('DELETE FROM pontuacoes_podio WHERE id_pontuacao = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->close();
        $connection->close();
    }

    private static function createTemporaryReservation(int $editionId, int $modalityId, string $tag): int
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('INSERT INTO agenda_reservas (id_interclasse, id_modalidade, chave_tag) VALUES (?, ?, ?)');
        $statement->bind_param('iis', $editionId, $modalityId, $tag);
        $statement->execute();
        $id = (int) $connection->insert_id;
        $statement->close();
        $connection->close();
        return $id;
    }

    private static function deleteTemporaryReservation(int $id): void
    {
        $connection = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $connection->prepare('DELETE FROM agenda_reservas WHERE id_reserva = ?');
        $statement->bind_param('i', $id);
        $statement->execute();
        $statement->close();
        $connection->close();
    }
}
