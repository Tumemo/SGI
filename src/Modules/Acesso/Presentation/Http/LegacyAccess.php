<?php

declare (strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

final class LegacyAccess
{
    public static function iniciarSessao(): void
    {
        \App\Shared\Http\SessionManager::start();
    }
    public static function requerNivel(array $niveisPermitidos): void
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::iniciarSessao();
        if (!isset($_SESSION['nivel'])) {
            \http_response_code(401);
            echo \json_encode(["success" => \false, "message" => "Usuário não autenticado."]);
            exit;
        }
        if (!\in_array((int) $_SESSION['nivel'], $niveisPermitidos, \true)) {
            \http_response_code(403);
            echo \json_encode(["success" => \false, "message" => "Acesso não autorizado para este nível."]);
            exit;
        }
    }
    public static function requerEscrita(): void
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0, 1]);
    }
    public static function requerOperacaoJogo(): void
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0, 1, 2]);
    }
    public static function requerExclusao(): void
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0]);
    }
    public static function requerAcessoTurma(\mysqli $conn, int $idTurma, int $idInterclasse): void
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::requerNivel([0, 1, 2, 3]);
        if ((int) ($_SESSION['nivel'] ?? -1) !== 3) {
            return;
        }
        $idUsuario = (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
        $statement = $conn->prepare('SELECT 1 FROM usuarios WHERE id_usuario = ? AND turmas_id_turma = ? AND interclasses_id_interclasse = ? LIMIT 1');
        $statement->bind_param('iii', $idUsuario, $idTurma, $idInterclasse);
        $statement->execute();
        $canAccess = $statement->get_result()->fetch_assoc() !== \null;
        $statement->close();
        if ($canAccess) {
            return;
        }
        \http_response_code(403);
        echo \json_encode(['success' => \false, 'message' => 'Você não tem acesso ao histórico desta turma.'], \JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function garantirInterclasseAtivo(\mysqli $conn): ?int
    {
        \App\Modules\Acesso\Presentation\Http\LegacyAccess::iniciarSessao();
        $nivel = (int) ($_SESSION['nivel'] ?? -1);
        if ($nivel !== 2) {
            return \App\Modules\Eventos\Infrastructure\MysqliEdicaoConsulta::buscarInterclasseAtivo($conn);
        }
        $ativo = \App\Modules\Eventos\Infrastructure\MysqliEdicaoConsulta::buscarInterclasseAtivo($conn);
        if ($ativo === \null) {
            \http_response_code(403);
            echo \json_encode(["success" => \false, "message" => "Nenhuma edição de interclasse está ativa no momento. Entre em contato com o administrador."]);
            exit;
        }
        $_SESSION['id_interclasse'] = $ativo;
        return $ativo;
    }
}
