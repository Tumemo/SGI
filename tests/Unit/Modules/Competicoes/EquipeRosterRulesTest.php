<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Competicoes;

use App\Modules\Competicoes\Domain\EquipeRosterRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EquipeRosterRulesTest extends TestCase
{
    public function testAllowsEligibleActiveStudentWithoutTermsAcceptance(): void
    {
        $context = self::context();

        EquipeRosterRules::validarContexto($context['team'], $context['class'], $context['modality']);
        EquipeRosterRules::validarAluno($context['student'], $context['team'], $context['class'], $context['modality']);

        self::assertArrayNotHasKey('aceito_termo', $context['student']);
    }

    public function testRejectsInactiveAndNonStudentAccounts(): void
    {
        $context = self::context();
        try {
            EquipeRosterRules::validarAluno(
                [...$context['student'], 'status_usuario' => '0'],
                $context['team'],
                $context['class'],
                $context['modality'],
            );
            self::fail('Aluno inativo deveria ser recusado.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('ativos', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        EquipeRosterRules::validarAluno(
            [...$context['student'], 'nivel_usuario' => '1'],
            $context['team'],
            $context['class'],
            $context['modality'],
        );
    }

    public function testRejectsDifferentEditionClassGenderAndCategory(): void
    {
        $context = self::context();
        foreach ([
            [...$context['student'], 'interclasses_id_interclasse' => 99],
            [...$context['student'], 'turmas_id_turma' => 99],
            [...$context['student'], 'genero_usuario' => 'FEM'],
        ] as $student) {
            try {
                EquipeRosterRules::validarAluno($student, $context['team'], $context['class'], $context['modality']);
                self::fail('Aluno fora do escopo ou incompatível deveria ser recusado.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }

        $this->expectException(InvalidArgumentException::class);
        EquipeRosterRules::validarContexto(
            $context['team'],
            $context['class'],
            [...$context['modality'], 'categorias_id_categoria' => 9],
        );
    }

    public function testRejectsAStudentWhoseActiveModalitiesWouldExceedThree(): void
    {
        EquipeRosterRules::validarLimiteModalidades([3, 1, 2], 3);

        $this->expectException(InvalidArgumentException::class);
        EquipeRosterRules::validarLimiteModalidades([1, 2, 3], 4);
    }

    public function testCapacityBoundariesAllowLastSlotAndRejectOverflow(): void
    {
        EquipeRosterRules::validarCapacidadeEquipe(1, 1, 2);
        EquipeRosterRules::validarCapacidadeModalidade(3, 1, 2, 2);

        try {
            EquipeRosterRules::validarCapacidadeEquipe(2, 1, 2);
            self::fail('Excesso de membros deveria ser recusado.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('limite máximo', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        EquipeRosterRules::validarCapacidadeModalidade(4, 1, 2, 2);
    }

    /** @return array<string, array<string, mixed>> */
    private static function context(): array
    {
        return [
            'team' => [
                'id_equipe' => 11,
                'status_equipe' => '1',
                'modalidades_id_modalidade' => 7,
                'turmas_id_turma' => 5,
            ],
            'class' => [
                'id_turma' => 5,
                'status_turma' => '1',
                'interclasses_id_interclasse' => 2,
                'categorias_id_categoria' => 4,
            ],
            'modality' => [
                'id_modalidade' => 7,
                'status_modalidade' => '1',
                'interclasses_id_interclasse' => 2,
                'categorias_id_categoria' => 4,
                'genero_modalidade' => 'MASC',
            ],
            'student' => [
                'nivel_usuario' => '3',
                'status_usuario' => '1',
                'interclasses_id_interclasse' => 2,
                'turmas_id_turma' => 5,
                'genero_usuario' => 'MASC',
            ],
        ];
    }
}
