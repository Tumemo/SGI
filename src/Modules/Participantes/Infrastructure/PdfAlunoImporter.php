<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

final class PdfAlunoImporter
{
    /**
     * O parser mantém partes descompactadas do documento em memória. Um
     * limite próprio evita que um PDF grande derrube o processo PHP antes de
     * o controlador conseguir devolver o envelope JSON da API.
     */
    public const MAX_PDF_BYTES = 10 * 1024 * 1024;

    public static function extrairLinhasDoPdf(string $caminhoPdf): array
    {
        $tamanho = @filesize($caminhoPdf);
        if ($tamanho === false) {
            throw new \RuntimeException('Não foi possível ler o PDF enviado.');
        }
        if ($tamanho > self::MAX_PDF_BYTES) {
            throw new \InvalidArgumentException('O PDF excede o limite de 10 MB. Reduza o arquivo e tente novamente.');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($caminhoPdf);
        $texto = $pdf->getText();
        $linhas = \explode("\n", $texto);
        $alunos = [];
        foreach ($linhas as $linha) {
            $linha_limpa = \trim($linha);
            if (empty($linha_limpa)) {
                continue;
            }
            $resultado = \App\Modules\Participantes\Domain\AlunoRules::parsearLinhaAluno($linha_limpa);
            if ($resultado !== \null) {
                $alunos[] = $resultado;
            }
        }
        return $alunos;
    }
    public static function pdfParaCsv(string $caminhoPdf, string $caminhoCsv): bool
    {
        $alunos = \App\Modules\Participantes\Infrastructure\PdfAlunoImporter::extrairLinhasDoPdf($caminhoPdf);
        if (empty($alunos)) {
            return \false;
        }
        $fp = \fopen($caminhoCsv, 'w');
        if (!$fp) {
            return \false;
        }
        \fputcsv($fp, ['nome', 'rm', 'data_nascimento', 'genero'], ',', '"', '\\');
        foreach ($alunos as $aluno) {
            \fputcsv($fp, [$aluno['nome'], $aluno['rm'], $aluno['data_nascimento'], $aluno['genero']], ',', '"', '\\');
        }
        \fclose($fp);
        return \true;
    }
    public static function extrairAlunosDoCsv(string $caminhoCsv): array
    {
        $alunos = [];
        if (!\file_exists($caminhoCsv)) {
            return $alunos;
        }
        $fp = \fopen($caminhoCsv, 'r');
        if (!$fp) {
            return $alunos;
        }
        \fgetcsv($fp, null, ',', '"', '\\');
        while (($row = \fgetcsv($fp, null, ',', '"', '\\')) !== \false) {
            if (\count($row) < 4) {
                continue;
            }
            $nome = \trim($row[0]);
            $rm = \trim($row[1]);
            $data_nasc = \trim($row[2]);
            $genero = \trim($row[3]);
            if (empty($nome) || empty($rm)) {
                continue;
            }
            $rmNorm = \App\Modules\Participantes\Domain\MatriculaRules::normalizarRa($rm);
            if ($rmNorm === '') {
                continue;
            }
            $alunos[] = ['nome' => $nome, 'rm' => $rm, 'data_nascimento' => $data_nasc, 'genero' => $genero];
        }
        \fclose($fp);
        return $alunos;
    }
    public static function extrairAlunosDoPdf(string $caminhoPdf, string $nomeTurma): array
    {
        $alunos = \App\Modules\Participantes\Infrastructure\PdfAlunoImporter::extrairLinhasDoPdf($caminhoPdf);
        foreach ($alunos as &$aluno) {
            $aluno['turma'] = $nomeTurma;
        }
        unset($aluno);
        return $alunos;
    }
    public static function inserirAlunosNaTurma(\mysqli $conn, array $alunos, int $idTurma, int $idInterclasse): array
    {
        $cadastrados = 0;
        $erros = [];
        $duplicados = 0;
        $sqlUser = 'INSERT INTO usuarios (
        sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario,
        senha_troca_pendente, foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao
    ) VALUES (\'RM\', ?, ?, ?, \'3\', ?, ?, 1, \'default.jpg\', \'1\', ?, ?, ?)';
        $sqlExiste = 'SELECT id_usuario FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1';
        $conn->begin_transaction();
        try {
            $stmtU = $conn->prepare($sqlUser);
            if (!$stmtU) {
                throw new \RuntimeException('Falha ao preparar inserção: ' . $conn->error);
            }
            $stmtExiste = $conn->prepare($sqlExiste);
            if (!$stmtExiste) {
                throw new \RuntimeException('Falha ao verificar duplicidade: ' . $conn->error);
            }
            foreach ($alunos as $aluno) {
                $nome = \trim((string) ($aluno['nome'] ?? ''));
                $rm = \App\Modules\Participantes\Domain\MatriculaRules::normalizarRa($aluno['rm'] ?? '');
                $dataRaw = $aluno['data_nascimento'] ?? '';
                $dataNasc = \App\Modules\Participantes\Domain\MatriculaRules::parseDataNascimento(\is_string($dataRaw) ? $dataRaw : '');
                $genero = isset($aluno['genero']) && \strtoupper((string) $aluno['genero']) === 'FEM' ? 'FEM' : 'MASC';
                if ($nome === '' || $rm === '' || $dataNasc === \null) {
                    $erros[] = "Dados inválidos para RM {$aluno['rm']}: nome ou data ausente.";
                    continue;
                }
                $chaveEdicao = $rm . '-' . $idInterclasse;
                $stmtExiste->bind_param('s', $chaveEdicao);
                $stmtExiste->execute();
                $jaCadastrado = $stmtExiste->get_result()->fetch_assoc();
                if ($jaCadastrado) {
                    $duplicados++;
                    continue;
                }
                $senhaHash = \password_hash(\App\Shared\Security\StudentInitialPassword::VALUE, \PASSWORD_DEFAULT);
                $stmtU->bind_param('sssssiis', $rm, $nome, $senhaHash, $genero, $dataNasc, $idTurma, $idInterclasse, $chaveEdicao);
                if (!$stmtU->execute()) {
                    if ($stmtU->errno === 1062) {
                        $duplicados++;
                        continue;
                    }
                    throw new \RuntimeException($stmtU->error ?: 'Erro ao inserir competidor.');
                }
                $cadastrados++;
            }
            $stmtExiste->close();
            $stmtU->close();
            $conn->commit();
            return ['status' => 'sucesso', 'cadastrados' => $cadastrados, 'duplicados' => $duplicados, 'erros' => $erros];
        } catch (\Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
