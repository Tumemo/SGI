<?php

declare (strict_types=1);

namespace App\Modules\Competicoes\Domain;

final class EquipeRules
{
    /**
     * Helpers de regra de negócio para equipes (RF01/RF03/RF05).
     *
     * Convenção de nome de equipe: "{Nome base da modalidade} - {Número}".
     * A equipe com sufixo "- 1" é a Equipe Padrão da turma/modalidade.
     */
    /**
     * Remove o sufixo de gênero do nome da modalidade para compor o nome da equipe.
     * Ex.: "Futsal - MA" => "Futsal"; "Corrida - FE" => "Corrida".
     */
    public static function nomeBaseModalidade(string $nome): string
    {
        $base = \trim($nome);
        $sufixos = ['MA', 'MI', 'FE', 'MASC', 'FEM', 'MISTO', 'MISTA'];
        $pattern = '/^(.*?)\s*-\s*(' . \implode('|', $sufixos) . ')$/i';
        if (\preg_match($pattern, $base, $m)) {
            return \trim($m[1]);
        }
        return $base;
    }
    /**
     * Monta o nome padrão da equipe: "{Modalidade} - {Número}".
     */
    public static function nomeEquipePadrao(string $nomeModalidade, int $numero): string
    {
        return \App\Modules\Competicoes\Domain\EquipeRules::nomeBaseModalidade($nomeModalidade) . ' - ' . $numero;
    }
    /**
     * Monta o nome da equipe com o nome da turma na frente:
     * "{Nome da turma} {Modalidade} - {Número}". Ex.: "6EF Futsal - 2".
     */
    public static function nomeEquipeTurma(?string $nomeTurma, string $nomeModalidade, int $numero): string
    {
        $nome = \App\Modules\Competicoes\Domain\EquipeRules::nomeEquipePadrao($nomeModalidade, $numero);
        if ($nomeTurma !== \null && $nomeTurma !== '') {
            return $nomeTurma . ' ' . $nome;
        }
        return $nome;
    }
    /**
     * Extrai o número do sufixo de uma equipe no padrão "{...} - {N}". Retorna null se não houver.
     */
    public static function numeroEquipe(?string $nomeEquipe): ?int
    {
        if ($nomeEquipe === \null || $nomeEquipe === '') {
            return \null;
        }
        if (\preg_match('/-\s*(\d+)\s*$/', $nomeEquipe, $m)) {
            return (int) $m[1];
        }
        return \null;
    }
}
