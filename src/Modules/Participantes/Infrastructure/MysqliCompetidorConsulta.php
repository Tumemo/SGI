<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

final class MysqliCompetidorConsulta
{
    /**
     * RF05: cruza RA e data de nascimento na base importada (competidor ativo).
     * Filtra obrigatoriamente pelo interclasse ativo ($idInterclasse).
     */
    public static function buscarCompetidorPorRaEData(\mysqli $conn, string $raNormalizado, string $dataYmd, int $idInterclasse): ?array
    {
        if ($raNormalizado === '' || $idInterclasse <= 0) {
            return \null;
        }
        $sql = 'SELECT id_usuario, nome_usuario, matricula_usuario, senha_usuario, nivel_usuario,
                   mesario_usuario, competidor_usuario, sigla_usuario, foto_usuario
            FROM usuarios
            WHERE matricula_usuario = ?
              AND data_nasc_usuario = ?
              AND interclasses_id_interclasse = ?
              AND competidor_usuario = \'3\'
              AND status_usuario = \'1\'
            LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return \null;
        }
        $stmt->bind_param('ssi', $raNormalizado, $dataYmd, $idInterclasse);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: \null;
    }
}
