<?php

declare (strict_types=1);

namespace App\Modules\Eventos\Infrastructure;

final class MysqliEdicaoConsulta
{
    /**
     * Retorna o ID do interclasse com status = '1', ou null se nenhum estiver ativo.
     */
    public static function buscarInterclasseAtivo(\mysqli $conn): ?int
    {
        $res = $conn->query("SELECT id_interclasse FROM interclasses WHERE status_interclasse = '1' ORDER BY id_interclasse DESC LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            return \null;
        }
        $row = $res->fetch_assoc();
        return (int) $row['id_interclasse'];
    }
    /**
     * Verifica se um interclasse específico está ativo.
     */
    public static function interclasseEstaAtivo(\mysqli $conn, int $idInterclasse): bool
    {
        $stmt = $conn->prepare("SELECT status_interclasse FROM interclasses WHERE id_interclasse = ? LIMIT 1");
        if (!$stmt) {
            return \false;
        }
        $stmt->bind_param('i', $idInterclasse);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return isset($row['status_interclasse']) && $row['status_interclasse'] === '1';
    }
    /**
     * Verifica se o interclasse vinculado a um usuário está inativo (encerrado).
     * Usado para proteger dados de edições passadas (RNF06).
     */
    public static function usuarioInterclasseEncerrado(\mysqli $conn, int $idUsuario): bool
    {
        $stmt = $conn->prepare("SELECT i.status_interclasse\r\n         FROM usuarios u\r\n         JOIN interclasses i ON u.interclasses_id_interclasse = i.id_interclasse\r\n         WHERE u.id_usuario = ? LIMIT 1");
        if (!$stmt) {
            return \true;
        }
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !isset($row['status_interclasse']) || $row['status_interclasse'] === '0';
    }
}
