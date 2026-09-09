<?php

declare (strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

final class OfflineSession
{
    /**
     * Cria um namespace opaco para os caches locais do navegador.
     *
     * Esta chave não é o PHPSESSID e pode ser exposta ao JavaScript sem revelar
     * o identificador da sessão. Para usuários autenticados ela é estável enquanto
     * a senha não mudar: assim uma fila offline continua sincronizável após a
     * sessão expirar e o mesmo mesário entrar novamente. Usuários diferentes
     * recebem namespaces distintos.
     */
    public static function renovarChaveCacheOffline(): string
    {
        $chave = \bin2hex(\random_bytes(32));
        $_SESSION['chave_cache_offline'] = $chave;
        return $chave;
    }
    /**
     * Define o namespace estável de um usuário autenticado. O hash de senha já
     * existe no banco e muda quando a credencial é trocada, invalidando de forma
     * natural os dados locais antigos daquele acesso.
     */
    public static function definirChaveCacheOfflineUsuario(int $idUsuario, string $hashSenha): string
    {
        $chave = \hash('sha256', 'sgi-cache-v2|' . $idUsuario . '|' . $hashSenha);
        $_SESSION['chave_cache_offline'] = $chave;
        return $chave;
    }
    /**
     * Obtém a chave de cache da sessão atual e cria uma chave efêmera quando a
     * sessão ainda não foi inicializada.
     */
    public static function obterChaveCacheOffline(): string
    {
        $chave = $_SESSION['chave_cache_offline'] ?? \null;
        if (!\is_string($chave) || !\preg_match('/\A[a-f0-9]{64}\z/D', $chave)) {
            return self::renovarChaveCacheOffline();
        }
        return $chave;
    }
}
