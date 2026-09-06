<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Domain;

final class AlunoRules
{
    public static function parsearLinhaAluno(string $linha): ?array
    {
        if (!\preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha, $m_data)) {
            return \null;
        }
        $data_nasc = $m_data[1];
        $genero = "MASC";
        if (\preg_match('/([FM])\s*\|\s*\d{2}\/\d{2}\/\d{4}/', $linha, $m_gen)) {
            $genero = $m_gen[1] === 'F' ? "FEM" : "MASC";
        } else {
            $posicao_data = \strpos($linha, $data_nasc);
            $depois_da_data = \substr($linha, $posicao_data + \strlen($data_nasc), 5);
            if (\stripos($depois_da_data, 'F') !== \false) {
                $genero = "FEM";
            }
        }
        if (\preg_match('/\|\s*\d+\s*\|\s*(.+?)\s+\S+\s*\|\s*\d{2}\/\d{2}\/\d{4}/', $linha, $m_nome)) {
            $nome_final = \trim($m_nome[1]);
        } elseif (\preg_match('/^(\d+)\s+/', $linha)) {
            $nome_bruto = \preg_replace('/^\d+\s+/', '', $linha);
            $divisor = $data_nasc;
            $partes = \explode($divisor, $nome_bruto);
            $nome_final = \trim($partes[0]);
            $nome_final = \preg_replace('/\s*[A-Z]{2}$/', '', $nome_final);
        } else {
            return \null;
        }
        $rm = "";
        if (\preg_match('/(\d{2}\/\d{2}\/\d{4})[FM]([\d.]+)/i', $linha, $m_rm)) {
            $rm = $m_rm[2];
        } elseif (\preg_match('/(\d+\.\d+)/', $linha, $m_rm_dec)) {
            $rm = $m_rm_dec[1];
        }
        if (empty($rm)) {
            return \null;
        }
        $rmNorm = \App\Modules\Participantes\Domain\MatriculaRules::normalizarRa($rm);
        if ($rmNorm === '') {
            return \null;
        }
        return ['nome' => $nome_final, 'data_nascimento' => $data_nasc, 'rm' => $rm, 'genero' => $genero];
    }
}
