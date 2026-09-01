<?php

declare(strict_types=1);

/**
 * Cria um namespace opaco para os caches locais do navegador.
 *
 * Esta chave não é o PHPSESSID e pode ser exposta ao JavaScript sem revelar
 * o identificador da sessão. Para usuários autenticados ela é estável enquanto
 * a senha não mudar: assim uma fila offline continua sincronizável após a
 * sessão expirar e o mesmo mesário entrar novamente. Usuários diferentes
 * recebem namespaces distintos.
 */
function sgi_renovar_chave_cache_offline(): string
{
    $chave = bin2hex(random_bytes(32));
    $_SESSION['chave_cache_offline'] = $chave;

    return $chave;
}

/**
 * Define o namespace estável de um usuário autenticado. O hash de senha já
 * existe no banco e muda quando a credencial é trocada, invalidando de forma
 * natural os dados locais antigos daquele acesso.
 */
function sgi_definir_chave_cache_offline_usuario(int $idUsuario, string $hashSenha): string
{
    $chave = hash('sha256', 'sgi-cache-v2|' . $idUsuario . '|' . $hashSenha);
    $_SESSION['chave_cache_offline'] = $chave;

    return $chave;
}

/**
 * Obtém a chave de cache da sessão atual e cria uma chave efêmera apenas para
 * sessões legadas que ainda não tenham passado por um login autenticado.
 */
function sgi_obter_chave_cache_offline(): string
{
    $chave = $_SESSION['chave_cache_offline'] ?? null;
    if (!is_string($chave) || !preg_match('/\\A[a-f0-9]{64}\\z/D', $chave)) {
        return sgi_renovar_chave_cache_offline();
    }

    return $chave;
}
