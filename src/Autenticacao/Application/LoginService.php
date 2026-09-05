<?php

declare(strict_types=1);

namespace App\Autenticacao\Application;

use App\Autenticacao\Domain\InterclasseRepository;
use App\Autenticacao\Domain\UsuarioRepository;

final class LoginService
{
    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly InterclasseRepository $interclasses,
    ) {
    }

    /**
     * @return array{usuario: array<string, mixed>, interclasse_ativo: ?int, exige_troca_senha: bool}|null
     */
    public function autenticar(string $matricula, string $senha): ?array
    {
        $interclasseAtivo = $this->interclasses->findActiveId();
        $usuario = $this->usuarios->findActiveByMatricula($matricula, $interclasseAtivo);

        if ($usuario === null || !password_verify($senha, (string) ($usuario['senha_usuario'] ?? ''))) {
            return null;
        }

        $nivel = (int) ($usuario['nivel_usuario'] ?? -1);

        return [
            'usuario' => $usuario,
            'interclasse_ativo' => $interclasseAtivo,
            'exige_troca_senha' => $nivel === 3
                && password_verify('123', (string) ($usuario['senha_usuario'] ?? '')),
        ];
    }
}
