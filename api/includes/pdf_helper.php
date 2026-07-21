<?php

declare(strict_types=1);

require_once __DIR__ . '/usuario_validacao.php';

use Smalot\PdfParser\Parser;

function sgi_extrair_linhas_do_pdf(string $caminhoPdf): array
{
    $parser = new Parser();
    $pdf = $parser->parseFile($caminhoPdf);
    $texto = $pdf->getText();
    $linhas = explode("\n", $texto);
    $alunos = [];

    foreach ($linhas as $linha) {
        $linha_limpa = trim($linha);
        if (empty($linha_limpa)) continue;

        $resultado = sgi_parsear_linha_aluno($linha_limpa);
        if ($resultado !== null) {
            $alunos[] = $resultado;
        }
    }

    return $alunos;
}

function sgi_parsear_linha_aluno(string $linha): ?array
{
    if (!preg_match('/(\d{2}\/\d{2}\/\d{4})/', $linha, $m_data)) {
        return null;
    }
    $data_nasc = $m_data[1];

    $genero = "MASC";
    if (preg_match('/([FM])\s*\|\s*\d{2}\/\d{2}\/\d{4}/', $linha, $m_gen)) {
        $genero = ($m_gen[1] === 'F') ? "FEM" : "MASC";
    } elseif (!empty($data_nasc)) {
        $posicao_data = strpos($linha, $data_nasc);
        $depois_da_data = substr($linha, $posicao_data + strlen($data_nasc), 5);
        if (stripos($depois_da_data, 'F') !== false) {
            $genero = "FEM";
        }
    }

    if (preg_match('/\|\s*\d+\s*\|\s*(.+?)\s+\S+\s*\|\s*\d{2}\/\d{2}\/\d{4}/', $linha, $m_nome)) {
        $nome_final = trim($m_nome[1]);
    } elseif (preg_match('/^(\d+)\s+/', $linha)) {
        $nome_bruto = preg_replace('/^\d+\s+/', '', $linha);
        $divisor = $data_nasc;
        $partes = explode($divisor, $nome_bruto);
        $nome_final = trim($partes[0]);
        $nome_final = preg_replace('/\s*[A-Z]{2}$/', '', $nome_final);
    } else {
        return null;
    }

    $rm = "";
    if (preg_match('/^([\d.]+)[\s|]+/', $linha, $m_rm_dot)) {
        $rm = $m_rm_dot[1];
    } elseif (preg_match('/(\d+\.\d+)/', $linha, $m_rm_dec)) {
        $rm = $m_rm_dec[1];
    }

    if (empty($rm)) return null;

    $rmNorm = sgi_normalizar_ra($rm);
    if ($rmNorm === '') return null;

    return [
        'nome'            => $nome_final,
        'data_nascimento' => $data_nasc,
        'rm'              => $rm,
        'genero'          => $genero,
    ];
}

function sgi_pdf_para_csv(string $caminhoPdf, string $caminhoCsv): bool
{
    $alunos = sgi_extrair_linhas_do_pdf($caminhoPdf);

    if (empty($alunos)) {
        return false;
    }

    $fp = fopen($caminhoCsv, 'w');
    if (!$fp) {
        return false;
    }

    fputcsv($fp, ['nome', 'rm', 'data_nascimento', 'genero']);

    foreach ($alunos as $aluno) {
        fputcsv($fp, [
            $aluno['nome'],
            $aluno['rm'],
            $aluno['data_nascimento'],
            $aluno['genero'],
        ]);
    }

    fclose($fp);
    return true;
}

function sgi_extrair_alunos_do_csv(string $caminhoCsv): array
{
    $alunos = [];

    if (!file_exists($caminhoCsv)) {
        return $alunos;
    }

    $fp = fopen($caminhoCsv, 'r');
    if (!$fp) {
        return $alunos;
    }

    fgetcsv($fp);

    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) < 4) continue;

        $nome = trim($row[0]);
        $rm = trim($row[1]);
        $data_nasc = trim($row[2]);
        $genero = trim($row[3]);

        if (empty($nome) || empty($rm)) continue;

        $rmNorm = sgi_normalizar_ra($rm);
        if ($rmNorm === '') continue;

        $alunos[] = [
            'nome'            => $nome,
            'rm'              => $rm,
            'data_nascimento' => $data_nasc,
            'genero'          => $genero,
        ];
    }

    fclose($fp);
    return $alunos;
}

function sgi_extrair_alunos_do_pdf(string $caminhoPdf, string $nomeTurma): array
{
    $alunos = sgi_extrair_linhas_do_pdf($caminhoPdf);

    foreach ($alunos as &$aluno) {
        $aluno['turma'] = $nomeTurma;
    }
    unset($aluno);

    return $alunos;
}

function sgi_inserir_alunos_na_turma(mysqli $conn, array $alunos, int $idTurma, int $idInterclasse): array
{
    $cadastrados = 0;
    $erros = [];
    $duplicados = 0;

    $sqlUser = 'INSERT INTO usuarios (
        sigla_usuario, matricula_usuario, nome_usuario, senha_usuario, nivel_usuario, genero_usuario, data_nasc_usuario,
        foto_usuario, status_usuario, turmas_id_turma, interclasses_id_interclasse, chave_usuario_edicao
    ) VALUES (\'RM\', ?, ?, ?, \'3\', ?, ?, \'default.jpg\', \'1\', ?, ?, ?)';

    $sqlExiste = 'SELECT id_usuario FROM usuarios WHERE chave_usuario_edicao = ? LIMIT 1';

    $conn->begin_transaction();
    try {
        $stmtU = $conn->prepare($sqlUser);
        if (!$stmtU) {
            throw new RuntimeException('Falha ao preparar inserção: ' . $conn->error);
        }

        $stmtExiste = $conn->prepare($sqlExiste);
        if (!$stmtExiste) {
            throw new RuntimeException('Falha ao verificar duplicidade: ' . $conn->error);
        }

        foreach ($alunos as $aluno) {
            $nome = trim((string) ($aluno['nome'] ?? ''));
            $rm = sgi_normalizar_ra($aluno['rm'] ?? '');
            $dataRaw = $aluno['data_nascimento'] ?? '';
            $dataNasc = sgi_parse_data_nascimento(is_string($dataRaw) ? $dataRaw : '');
            $genero = (isset($aluno['genero']) && strtoupper((string) $aluno['genero']) === 'FEM') ? 'FEM' : 'MASC';

            if ($nome === '' || $rm === '' || $dataNasc === null) {
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

            $senhaHash = password_hash('123', PASSWORD_DEFAULT);
            $stmtU->bind_param(
                'sssssiis',
                $rm,
                $nome,
                $senhaHash,
                $genero,
                $dataNasc,
                $idTurma,
                $idInterclasse,
                $chaveEdicao
            );

            if (!$stmtU->execute()) {
                if ($stmtU->errno === 1062) {
                    $duplicados++;
                    continue;
                }
                throw new RuntimeException($stmtU->error ?: 'Erro ao inserir competidor.');
            }
            $cadastrados++;
        }

        $stmtExiste->close();
        $stmtU->close();
        $conn->commit();

        return [
            'status' => 'sucesso',
            'cadastrados' => $cadastrados,
            'duplicados' => $duplicados,
            'erros' => $erros,
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'status' => 'erro',
            'mensagem' => 'Erro na importação: ' . $e->getMessage(),
        ];
    }
}
