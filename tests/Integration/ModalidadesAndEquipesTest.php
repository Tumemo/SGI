<?php
declare(strict_types=1);

namespace SGITests\Integration;

use SGITests\Support\TestClient;
use SGITests\Support\Assertions;
use SGITests\Support\TestDatabase;
use mysqli;

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

        self::validarElencoAdministrativo(
            $admin,
            $idEdicao,
            (int) ($categoriaI['id_categoria'] ?? 0),
            (int) ($mods[0]['id_tipo_modalidade'] ?? 0),
        );
        self::validarTransferenciaEquipeComHistorico(
            $admin,
            $idEdicao,
            (int) ($categoriaI['id_categoria'] ?? 0),
            (int) ($mods[0]['id_tipo_modalidade'] ?? 0),
        );

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

    private static function validarElencoAdministrativo(TestClient $admin, int $editionId, int $categoryId, int $typeId): void
    {
        echo "\n  \033[1;34m[Regressão: elegibilidade do elenco administrativo]\033[0m\n";
        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($databaseName);
        $marker = bin2hex(random_bytes(6));
        $classIds = [];
        $categoryIds = [];
        $modalityIds = [];
        $teamIds = [];
        $userIds = [];
        $foreignEditionId = 0;
        $foreignCategoryId = 0;
        $foreignClassId = 0;

        try {
            $database = TestDatabase::connect($databaseName);
            try {
                $classStatement = $database->prepare(
                    "SELECT id_turma FROM turmas
                     WHERE interclasses_id_interclasse = ? AND categorias_id_categoria = ? AND status_turma = '1'
                     ORDER BY id_turma LIMIT 1",
                );
                $classStatement->bind_param('ii', $editionId, $categoryId);
                $classStatement->execute();
                $classId = (int) ($classStatement->get_result()->fetch_column() ?: 0);
                $classStatement->close();
                if ($classId <= 0) {
                    throw new \RuntimeException('A regressão de elenco exige turma ativa da categoria principal.');
                }

                $categoryB = self::createRosterCategory($database, $editionId, 'L04 categoria ' . $marker);
                $categoryIds[] = $categoryB;
                $classB = self::createRosterClass($database, $editionId, $categoryId, 'L04 outra turma ' . $marker);
                $classIds[] = $classB;
                $inactiveClass = self::createRosterClass($database, $editionId, $categoryId, 'L04 turma inativa ' . $marker, '0');
                $classIds[] = $inactiveClass;
                $foreignEditionId = self::createRosterEdition($database, 'L04 edição externa ' . $marker);
                $foreignCategoryId = self::createRosterCategory($database, $foreignEditionId, 'L04 categoria externa ' . $marker);
                $foreignClassId = self::createRosterClass($database, $foreignEditionId, $foreignCategoryId, 'L04 turma externa ' . $marker);

                $main = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 2, 4, $classId, $marker . ' principal');
                $wrongCategory = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryB, $typeId, 'MASC', 4, 4, $classId, $marker . ' categoria');
                $capacity = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 1, 4, $classId, $marker . ' capacidade');
                $second = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 4, 4, $classId, $marker . ' segunda');
                $third = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 4, 4, $classId, $marker . ' terceira');
                $fourth = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 4, 4, $classId, $marker . ' quarta');
                $inactiveModality = self::createRosterModality($database, $modalityIds, $teamIds, $editionId, $categoryId, $typeId, 'MASC', 4, 4, $classId, $marker . ' modalidade inativa', '0');
                $inactiveClassTeam = self::createRosterTeam($database, $main['modality'], $inactiveClass, 'L04 equipe em turma inativa ' . $marker);
                $teamIds[] = $inactiveClassTeam;
                $inactiveTeam = self::createRosterTeam($database, $main['modality'], $classId, 'L04 equipe inativa ' . $marker, '0');
                $teamIds[] = $inactiveTeam;

                $batchValid = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' lote válido');
                $userIds[] = $batchValid;
                $foreignStudent = self::createRosterStudent($database, $foreignEditionId, $foreignClassId, '1', '3', 'MASC', $marker . ' edição externa');
                $userIds[] = $foreignStudent;
                $inactiveStudent = self::createRosterStudent($database, $editionId, $classId, '0', '3', 'MASC', $marker . ' inativo');
                $userIds[] = $inactiveStudent;
                $wrongClassStudent = self::createRosterStudent($database, $editionId, $classB, '1', '3', 'MASC', $marker . ' outra turma');
                $userIds[] = $wrongClassStudent;
                $femaleStudent = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'FEM', $marker . ' gênero');
                $userIds[] = $femaleStudent;
                $validStudent = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' sem termos');
                $userIds[] = $validStudent;
                $inactiveTeamStudent = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' equipe inativa');
                $userIds[] = $inactiveTeamStudent;
                $inactiveModalityStudent = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' modalidade inativa');
                $userIds[] = $inactiveModalityStudent;
                $inactiveClassStudent = self::createRosterStudent($database, $editionId, $inactiveClass, '1', '3', 'MASC', $marker . ' turma inativa');
                $userIds[] = $inactiveClassStudent;
                $capacityFirst = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' vaga um');
                $userIds[] = $capacityFirst;
                $capacitySecond = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' vaga dois');
                $userIds[] = $capacitySecond;
                $maxModalitiesStudent = self::createRosterStudent($database, $editionId, $classId, '1', '3', 'MASC', $marker . ' modalidades');
                $userIds[] = $maxModalitiesStudent;
                $nonStudent = self::createRosterStudent($database, $editionId, $classId, '1', '1', 'MASC', $marker . ' colaborador');
                $userIds[] = $nonStudent;
            } finally {
                $database->close();
            }

            $atomicBatch = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $main['team'],
                'usuarios' => [$batchValid, $foreignStudent],
            ]);
            Assertions::assertStatus('Lote administrativo com aluno de outra edição é recusado', $atomicBatch, 400);
            Assertions::assert(
                'Lote inválido não grava aluno elegível enviado antes do aluno externo',
                !self::rosterHasMembership($main['team'], $batchValid),
            );

            foreach ([
                [$foreignStudent, $main['team'], 'aluno de outra edição'],
                [$inactiveStudent, $main['team'], 'aluno inativo'],
                [$wrongClassStudent, $main['team'], 'aluno de outra turma'],
                [$femaleStudent, $main['team'], 'gênero incompatível'],
                [$nonStudent, $main['team'], 'perfil que não é aluno'],
                [$validStudent, $wrongCategory['team'], 'categoria incompatível'],
                [$inactiveTeamStudent, $inactiveTeam, 'equipe inativa'],
                [$inactiveModalityStudent, $inactiveModality['team'], 'modalidade inativa'],
                [$inactiveClassStudent, $inactiveClassTeam, 'turma inativa'],
            ] as [$studentId, $teamId, $description]) {
                $response = $admin->postJson('api/v1/equipes', [
                    'acao' => 'adicionar_usuarios',
                    'id_equipe' => $teamId,
                    'usuarios' => [$studentId],
                ]);
                Assertions::assertStatus("Inclusão administrativa recusa {$description}", $response, 400);
                Assertions::assert(
                    "Recusa por {$description} não persiste vínculo",
                    !self::rosterHasMembership($teamId, $studentId),
                );
            }

            $valid = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $main['team'],
                'usuarios' => [$validStudent],
            ]);
            Assertions::assertStatus('Gestão prepara elenco antes do aceite de termos', $valid, 200);
            Assertions::assert(
                'Aluno elegível foi vinculado sem aceite de termos',
                self::rosterHasMembership($main['team'], $validStudent)
                    && !self::rosterHasAcceptedTerms($validStudent, $editionId),
            );

            $retry = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $main['team'],
                'usuarios' => [$validStudent, $validStudent],
            ]);
            Assertions::assertStatus('Reenvio e IDs duplicados mantêm sucesso idempotente', $retry, 200);
            Assertions::assert('Reenvio não duplica associação à equipe', self::rosterMembershipCount($main['team'], $validStudent) === 1);

            $firstCapacity = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $capacity['team'],
                'usuarios' => [$capacityFirst],
            ]);
            Assertions::assertStatus('Inclusão ocupa a última vaga da equipe', $firstCapacity, 200);
            $overCapacity = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $capacity['team'],
                'usuarios' => [$capacitySecond],
            ]);
            Assertions::assertStatus('Inclusão acima da capacidade é recusada', $overCapacity, 400);
            Assertions::assert(
                'Recusa por capacidade não persiste o aluno seguinte',
                !self::rosterHasMembership($capacity['team'], $capacitySecond),
            );

            foreach ([
                [$main['team'], 'modalidade um'],
                [$second['team'], 'modalidade dois'],
                [$third['team'], 'modalidade três'],
            ] as [$teamId, $description]) {
                $response = $admin->postJson('api/v1/equipes', [
                    'acao' => 'adicionar_usuarios',
                    'id_equipe' => $teamId,
                    'usuarios' => [$maxModalitiesStudent],
                ]);
                Assertions::assertStatus("Aluno pode ser preparado em {$description}", $response, 200);
            }
            $fourthModality = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $fourth['team'],
                'usuarios' => [$maxModalitiesStudent],
            ]);
            Assertions::assertStatus('Inclusão administrativa recusa a quarta modalidade', $fourthModality, 400);
            Assertions::assert(
                'Recusa pela quarta modalidade não persiste associação',
                !self::rosterHasMembership($fourth['team'], $maxModalitiesStudent),
            );
        } finally {
            self::cleanupRosterFixtures(
                $userIds,
                $teamIds,
                $modalityIds,
                $classIds,
                $categoryIds,
                $foreignClassId,
                $foreignCategoryId,
                $foreignEditionId,
            );
        }
    }

    private static function validarTransferenciaEquipeComHistorico(TestClient $admin, int $editionId, int $categoryId, int $typeId): void
    {
        echo "\n  \033[1;34m[Regressão: bloqueio de transferência estrutural de equipe]\033[0m\n";
        $databaseName = getenv('SGI_TEST_DB_NAME') ?: 'sgi_test';
        TestDatabase::assertSafeDatabaseName($databaseName);
        $marker = bin2hex(random_bytes(6));
        $classIds = [];
        $modalityIds = [];
        $teamIds = [];
        $userIds = [];
        $gameIds = [];

        try {
            $database = TestDatabase::connect($databaseName);
            try {
                $classStatement = $database->prepare(
                    "SELECT id_turma FROM turmas
                     WHERE interclasses_id_interclasse = ? AND categorias_id_categoria = ? AND status_turma = '1'
                     ORDER BY id_turma LIMIT 1",
                );
                $classStatement->bind_param('ii', $editionId, $categoryId);
                $classStatement->execute();
                $sourceClassId = (int) ($classStatement->get_result()->fetch_column() ?: 0);
                $classStatement->close();
                if ($sourceClassId <= 0) {
                    throw new \RuntimeException('A regressão de transferência exige uma turma ativa da edição e categoria.');
                }

                $targetClassId = self::createRosterClass($database, $editionId, $categoryId, 'L05 destino ' . $marker);
                $classIds[] = $targetClassId;
                $scenarios = [];
                foreach (['elenco', 'partida', 'ponto', 'podio', 'inativa', 'vazia'] as $suffix) {
                    $scenarios[$suffix] = self::createRosterModality(
                        $database,
                        $modalityIds,
                        $teamIds,
                        $editionId,
                        $categoryId,
                        $typeId,
                        'MASC',
                        20,
                        20,
                        $sourceClassId,
                        $marker . ' ' . $suffix,
                    );
                }
                $targetSameClass = self::createRosterModality(
                    $database,
                    $modalityIds,
                    $teamIds,
                    $editionId,
                    $categoryId,
                    $typeId,
                    'MASC',
                    20,
                    20,
                    $sourceClassId,
                    $marker . ' destino modalidade',
                );
                $targetBoth = self::createRosterModality(
                    $database,
                    $modalityIds,
                    $teamIds,
                    $editionId,
                    $categoryId,
                    $typeId,
                    'MASC',
                    20,
                    20,
                    $targetClassId,
                    $marker . ' destino completo',
                );

                $memberStudent = self::createRosterStudent($database, $editionId, $sourceClassId, '1', '3', 'MASC', $marker . ' integrante');
                $userIds[] = $memberStudent;
                $inactiveStudent = self::createRosterStudent($database, $editionId, $sourceClassId, '1', '3', 'MASC', $marker . ' inativo');
                $userIds[] = $inactiveStudent;
                $inactiveTeamId = (int) $scenarios['inativa']['team'];
                $database->query("UPDATE equipes SET status_equipe = '0' WHERE id_equipe = " . $inactiveTeamId);
                $memberLink = $database->prepare('INSERT INTO equipes_has_usuarios (equipes_id_equipe, usuarios_id_usuario) VALUES (?, ?)');
                $memberTeamId = (int) $scenarios['elenco']['team'];
                $memberLink->bind_param('ii', $inactiveTeamId, $inactiveStudent);
                $memberLink->execute();
                $memberLink->close();
            } finally {
                $database->close();
            }

            $memberTeamId = (int) $scenarios['elenco']['team'];
            $memberResponse = $admin->postJson('api/v1/equipes', [
                'acao' => 'adicionar_usuarios',
                'id_equipe' => $memberTeamId,
                'usuarios' => [$memberStudent],
            ]);
            Assertions::assertStatus('Fixture L05 vincula aluno elegível à equipe', $memberResponse, 200);

            $matchTeamId = (int) $scenarios['partida']['team'];
            $matchGameId = self::createTemporaryGame((int) $scenarios['partida']['modality'], 'L05 partida ' . $marker);
            $gameIds[] = $matchGameId;
            $database = TestDatabase::connect($databaseName);
            $match = $database->prepare(
                "INSERT INTO partidas (jogos_id_jogo, equipes_id_equipe, resultado_partida, status_partida)
                 VALUES (?, ?, 0, '1')",
            );
            $match->bind_param('ii', $matchGameId, $matchTeamId);
            $match->execute();
            $match->close();

            $pointTeamId = (int) $scenarios['ponto']['team'];
            $pointGameId = self::createTemporaryGame((int) $scenarios['ponto']['modality'], 'L05 ponto ' . $marker);
            $gameIds[] = $pointGameId;
            $pointStudent = $userIds[0];
            $point = $database->prepare(
                "INSERT INTO artilheiros (usuarios_id_usuario, jogos_id_jogo, partidas_id_partida,
                    equipes_id_equipe, num_gol, conta_no_placar, status_artilheiro)
                 VALUES (?, ?, NULL, ?, 1, 0, 'anulado')",
            );
            $point->bind_param('iii', $pointStudent, $pointGameId, $pointTeamId);
            $point->execute();
            $point->close();

            $podiumTeamId = (int) $scenarios['podio']['team'];
            $position = 1;
            $points = 7;
            $origin = 'L05_test';
            $podium = $database->prepare(
                'INSERT INTO pontuacoes_podio (id_interclasse, id_modalidade, posicao, id_turma, id_equipe, pontos, origem_registro)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
            );
            $podiumModality = (int) $scenarios['podio']['modality'];
            $podium->bind_param('iiiiiss', $editionId, $podiumModality, $position, $sourceClassId, $podiumTeamId, $points, $origin);
            $podium->execute();
            $podium->close();
            $database->close();

            $cases = [
                ['elenco', $memberTeamId, (int) $targetSameClass['modality'], $sourceClassId],
                ['partida', $matchTeamId, (int) $scenarios['partida']['modality'], $targetClassId],
                ['ponto histórico anulado', $pointTeamId, (int) $targetBoth['modality'], $targetClassId],
                ['crédito de pódio', $podiumTeamId, (int) $targetSameClass['modality'], $sourceClassId],
                ['equipe inativa com elenco', $inactiveTeamId, (int) $scenarios['inativa']['modality'], $targetClassId],
            ];
            foreach ($cases as [$description, $teamId, $targetModalityId, $targetTeamClassId]) {
                $before = self::transferTeamSnapshot((int) $teamId);
                $response = $admin->putJson('api/v1/equipes', [
                    'id_equipe' => $teamId,
                    'modalidades_id_modalidade' => $targetModalityId,
                    'turmas_id_turma' => $targetTeamClassId,
                ]);
                Assertions::assertStatus("Transferência estrutural com {$description} é recusada", $response, 400);
                $after = self::transferTeamSnapshot((int) $teamId);
                Assertions::assert(
                    "Recusa com {$description} preserva escopo e descendentes",
                    $after === $before,
                    json_encode(['antes' => $before, 'depois' => $after]),
                );
                self::restoreTransferTeamSnapshot((int) $teamId, $before);
            }

            $beforeNameStatus = self::transferTeamSnapshot($memberTeamId);
            $name = 'Equipe preservada L05 ' . $marker;
            $nameStatus = $admin->putJson('api/v1/equipes', [
                'id_equipe' => $memberTeamId,
                'nome_equipe' => $name,
                'status_equipe' => '0',
            ]);
            Assertions::assertStatus('Renomear e inativar equipe com elenco continua permitido', $nameStatus, 200);
            $afterNameStatus = self::transferTeamSnapshot($memberTeamId);
            Assertions::assert(
                'Alteração de nome/status preserva escopo e elenco existente',
                $afterNameStatus['modality'] === $beforeNameStatus['modality']
                    && $afterNameStatus['class'] === $beforeNameStatus['class']
                    && $afterNameStatus['status'] === '0'
                    && $afterNameStatus['name'] === $name
                    && $afterNameStatus['members'] === $beforeNameStatus['members'],
                json_encode($afterNameStatus),
            );

            $emptyTeamId = (int) $scenarios['vazia']['team'];
            $emptyMove = $admin->putJson('api/v1/equipes', [
                'id_equipe' => $emptyTeamId,
                'modalidades_id_modalidade' => (int) $targetBoth['modality'],
                'turmas_id_turma' => $targetClassId,
            ]);
            Assertions::assertStatus('Equipe vazia e sem histórico pode trocar modalidade e turma', $emptyMove, 200);
            $movedEmpty = self::transferTeamSnapshot($emptyTeamId);
            Assertions::assert(
                'Transferência válida atualiza os dois vínculos sem descendentes',
                $movedEmpty['modality'] === (int) $targetBoth['modality']
                    && $movedEmpty['class'] === $targetClassId
                    && $movedEmpty['members'] === 0
                    && $movedEmpty['matches'] === 0
                    && $movedEmpty['points'] === 0
                    && $movedEmpty['podium'] === 0,
                json_encode($movedEmpty),
            );
        } finally {
            self::cleanupTransferFixtures($userIds, $teamIds, $modalityIds, $classIds, $gameIds);
        }
    }

    /** @return array{modality:int,class:int,status:string,name:?string,members:int,matches:int,points:int,podium:int} */
    private static function transferTeamSnapshot(int $teamId): array
    {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $database->prepare(
            "SELECT e.modalidades_id_modalidade, e.turmas_id_turma, e.status_equipe, e.nome_equipe,
                    (SELECT COUNT(*) FROM equipes_has_usuarios eu WHERE eu.equipes_id_equipe = e.id_equipe) AS members,
                    (SELECT COUNT(*) FROM partidas p WHERE p.equipes_id_equipe = e.id_equipe) AS matches,
                    (SELECT COUNT(*) FROM artilheiros a WHERE a.equipes_id_equipe = e.id_equipe) AS points,
                    (SELECT COUNT(*) FROM pontuacoes_podio pp WHERE pp.id_equipe = e.id_equipe) AS podium
             FROM equipes e WHERE e.id_equipe = ? LIMIT 1",
        );
        $statement->bind_param('i', $teamId);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc() ?: [];
        $statement->close();
        $database->close();

        return [
            'modality' => (int) ($row['modalidades_id_modalidade'] ?? 0),
            'class' => (int) ($row['turmas_id_turma'] ?? 0),
            'status' => (string) ($row['status_equipe'] ?? ''),
            'name' => $row['nome_equipe'] === null ? null : (string) $row['nome_equipe'],
            'members' => (int) ($row['members'] ?? 0),
            'matches' => (int) ($row['matches'] ?? 0),
            'points' => (int) ($row['points'] ?? 0),
            'podium' => (int) ($row['podium'] ?? 0),
        ];
    }

    /** @param array{modality:int,class:int,status:string,name:?string,members:int,matches:int,points:int,podium:int} $snapshot */
    private static function restoreTransferTeamSnapshot(int $teamId, array $snapshot): void
    {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $database->prepare(
            'UPDATE equipes SET modalidades_id_modalidade = ?, turmas_id_turma = ?, status_equipe = ?, nome_equipe = ? WHERE id_equipe = ?',
        );
        $statement->bind_param('iissi', $snapshot['modality'], $snapshot['class'], $snapshot['status'], $snapshot['name'], $teamId);
        $statement->execute();
        $statement->close();
        $database->close();
    }

    /** @param list<int> $userIds @param list<int> $teamIds @param list<int> $modalityIds @param list<int> $classIds @param list<int> $gameIds */
    private static function cleanupTransferFixtures(array $userIds, array $teamIds, array $modalityIds, array $classIds, array $gameIds): void
    {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $teamList = implode(',', array_values(array_filter(array_map('intval', $teamIds))));
        $modalityList = implode(',', array_values(array_filter(array_map('intval', $modalityIds))));
        $userList = implode(',', array_values(array_filter(array_map('intval', $userIds))));
        $gameList = implode(',', array_values(array_filter(array_map('intval', $gameIds))));
        if ($teamList !== '' || $gameList !== '' || $userList !== '') {
            $database->query(
                'DELETE FROM artilheiros WHERE '
                . ($gameList !== '' ? "jogos_id_jogo IN ($gameList)" : '0=1')
                . ($teamList !== '' ? " OR equipes_id_equipe IN ($teamList)" : '')
                . ($userList !== '' ? " OR usuarios_id_usuario IN ($userList)" : ''),
            );
        }
        if ($teamList !== '' || $modalityList !== '') {
            $database->query(
                'DELETE FROM pontuacoes_podio WHERE '
                . ($teamList !== '' ? "id_equipe IN ($teamList)" : '0=1')
                . ($modalityList !== '' ? " OR id_modalidade IN ($modalityList)" : ''),
            );
        }
        if ($gameList !== '') {
            $database->query("DELETE FROM partidas WHERE jogos_id_jogo IN ($gameList)");
            $database->query("DELETE FROM jogos WHERE id_jogo IN ($gameList)");
        }
        if ($teamList !== '') {
            $database->query("DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe IN ($teamList)");
            $database->query("DELETE FROM equipes WHERE id_equipe IN ($teamList)");
        }
        if ($modalityList !== '') {
            $database->query("DELETE FROM modalidades WHERE id_modalidade IN ($modalityList)");
        }
        if ($userList !== '') {
            $database->query("DELETE FROM usuarios WHERE id_usuario IN ($userList)");
        }
        foreach ($classIds as $classId) {
            $statement = $database->prepare('DELETE FROM turmas WHERE id_turma = ?');
            $statement->bind_param('i', $classId);
            $statement->execute();
            $statement->close();
        }
        $database->close();
    }

    private static function createRosterEdition(mysqli $database, string $name): int
    {
        $statement = $database->prepare(
            "INSERT INTO interclasses (nome_interclasse, ano_interclasse, regulamento_interclasse, status_interclasse)
             VALUES (?, '2026-09-12 00:00:00', 'fixture L04', '0')",
        );
        $statement->bind_param('s', $name);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();

        return $id;
    }

    private static function createRosterCategory(mysqli $database, int $editionId, string $name): int
    {
        $statement = $database->prepare(
            "INSERT INTO categorias (nome_categoria, status_categoria, interclasses_id_interclasse) VALUES (?, '1', ?)",
        );
        $statement->bind_param('si', $name, $editionId);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();

        return $id;
    }

    private static function createRosterClass(mysqli $database, int $editionId, int $categoryId, string $suffix, string $status = '1'): int
    {
        $name = $suffix;
        $statement = $database->prepare(
            'INSERT INTO turmas (interclasses_id_interclasse, nome_turma, turno_turma, nome_fantasia_turma, status_turma, categorias_id_categoria)
             VALUES (?, ?, \'manha\', ?, ?, ?)',
        );
        $statement->bind_param('isssi', $editionId, $name, $name, $status, $categoryId);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();

        return $id;
    }

    /** @return array{modality:int,team:int} */
    private static function createRosterModality(
        mysqli $database,
        array &$modalityIds,
        array &$teamIds,
        int $editionId,
        int $categoryId,
        int $typeId,
        string $gender,
        int $maxStudents,
        int $maxTeams,
        int $classId,
        string $suffix,
        string $status = '1',
    ): array {
        $name = 'L04 ' . $suffix;
        $statement = $database->prepare(
            "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, max_equipes,
                                     status_modalidade, tipos_modalidades_id_tipo_modalidade,
                                     categorias_id_categoria, interclasses_id_interclasse)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        );
        $statement->bind_param('ssiisiii', $name, $gender, $maxStudents, $maxTeams, $status, $typeId, $categoryId, $editionId);
        $statement->execute();
        $modalityId = (int) $statement->insert_id;
        $statement->close();
        $modalityIds[] = $modalityId;
        $teamId = self::createRosterTeam($database, $modalityId, $classId, $name);
        $teamIds[] = $teamId;

        return ['modality' => $modalityId, 'team' => $teamId];
    }

    private static function createRosterTeam(mysqli $database, int $modalityId, int $classId, string $name, string $status = '1'): int
    {
        $statement = $database->prepare(
            'INSERT INTO equipes (status_equipe, modalidades_id_modalidade, turmas_id_turma, nome_equipe) VALUES (?, ?, ?, ?)',
        );
        $statement->bind_param('siis', $status, $modalityId, $classId, $name);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();

        return $id;
    }

    private static function createRosterStudent(
        mysqli $database,
        int $editionId,
        int $classId,
        string $status,
        string $level,
        string $gender,
        string $suffix,
    ): int {
        $registration = 'L04' . bin2hex(random_bytes(12));
        $name = 'Atleta ' . substr($suffix, 0, 35);
        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $statement = $database->prepare(
            "INSERT INTO usuarios (sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario,
                                  genero_usuario, data_nasc_usuario, foto_usuario, status_usuario,
                                  turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao)
             VALUES ('RM', ?, ?, ?, ?, ?, '2010-01-01', '', ?, ?, ?, NULL)",
        );
        $statement->bind_param('ssssssii', $registration, $name, $password, $level, $gender, $status, $classId, $editionId);
        $statement->execute();
        $id = (int) $statement->insert_id;
        $statement->close();

        return $id;
    }

    private static function rosterHasMembership(int $teamId, int $studentId): bool
    {
        return self::rosterMembershipCount($teamId, $studentId) > 0;
    }

    private static function rosterMembershipCount(int $teamId, int $studentId): int
    {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $database->prepare(
            'SELECT COUNT(*) FROM equipes_has_usuarios WHERE equipes_id_equipe = ? AND usuarios_id_usuario = ?',
        );
        $statement->bind_param('ii', $teamId, $studentId);
        $statement->execute();
        $count = (int) $statement->get_result()->fetch_column();
        $statement->close();
        $database->close();

        return $count;
    }

    private static function rosterHasAcceptedTerms(int $studentId, int $editionId): bool
    {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        $statement = $database->prepare(
            "SELECT 1 FROM usuarios_has_interclasses WHERE usuarios_id_usuario = ?
             AND interclasses_id_interclasse = ? AND aceito_termo = 'sim' LIMIT 1",
        );
        $statement->bind_param('ii', $studentId, $editionId);
        $statement->execute();
        $accepted = $statement->get_result()->fetch_column() !== false;
        $statement->close();
        $database->close();

        return $accepted;
    }

    /** @param list<int> $userIds @param list<int> $teamIds @param list<int> $modalityIds @param list<int> $classIds @param list<int> $categoryIds */
    private static function cleanupRosterFixtures(
        array $userIds,
        array $teamIds,
        array $modalityIds,
        array $classIds,
        array $categoryIds,
        int $foreignClassId,
        int $foreignCategoryId,
        int $foreignEditionId,
    ): void {
        $database = TestDatabase::connect(getenv('SGI_TEST_DB_NAME') ?: 'sgi_test');
        foreach ($teamIds as $teamId) {
            $statement = $database->prepare('DELETE FROM equipes_has_usuarios WHERE equipes_id_equipe = ?');
            $statement->bind_param('i', $teamId);
            $statement->execute();
            $statement->close();
            $statement = $database->prepare('DELETE FROM equipes WHERE id_equipe = ?');
            $statement->bind_param('i', $teamId);
            $statement->execute();
            $statement->close();
        }
        foreach ($modalityIds as $modalityId) {
            $statement = $database->prepare('DELETE FROM modalidades WHERE id_modalidade = ?');
            $statement->bind_param('i', $modalityId);
            $statement->execute();
            $statement->close();
        }
        foreach ($userIds as $userId) {
            $statement = $database->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            $statement->bind_param('i', $userId);
            $statement->execute();
            $statement->close();
        }
        foreach (array_filter([...$classIds, $foreignClassId]) as $classId) {
            $statement = $database->prepare('DELETE FROM turmas WHERE id_turma = ?');
            $statement->bind_param('i', $classId);
            $statement->execute();
            $statement->close();
        }
        foreach (array_filter([...$categoryIds, $foreignCategoryId]) as $categoryId) {
            $statement = $database->prepare('DELETE FROM categorias WHERE id_categoria = ?');
            $statement->bind_param('i', $categoryId);
            $statement->execute();
            $statement->close();
        }
        if ($foreignEditionId > 0) {
            $statement = $database->prepare('DELETE FROM interclasses WHERE id_interclasse = ?');
            $statement->bind_param('i', $foreignEditionId);
            $statement->execute();
            $statement->close();
        }
        $database->close();
    }
}
