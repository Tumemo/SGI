<?php

declare (strict_types=1);

namespace App\Modules\Eventos\Infrastructure;

final class MysqliLocalPadraoRepository
{
    /**
     * Cria locais padrão vinculados a uma edição do interclasse.
     */
    public static function criarLocaisPadraoInterclasse(\mysqli $conn, int $idInterclasse): void
    {
        $padroes = ['Quadra', 'Biblioteca', 'Pátio 1', 'Pátio 2'];
        $sql = 'INSERT INTO locais (nome_local, disponivel_local, status_local, interclasses_id_interclasse)
            SELECT ?, \'1\', \'1\', ?
            WHERE NOT EXISTS (
                SELECT 1 FROM locais
                WHERE nome_local = ? AND interclasses_id_interclasse = ?
            )';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        foreach ($padroes as $nome) {
            $stmt->bind_param('sisi', $nome, $idInterclasse, $nome, $idInterclasse);
            $stmt->execute();
        }
        $stmt->close();
    }
}
