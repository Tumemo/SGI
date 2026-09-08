<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Domain;

/**
 * Identidade e contexto de edição do operador autenticado.
 *
 * Este objeto é deliberadamente independente da sessão HTTP para que as
 * regras de autorização possam ser executadas por casos de uso e testes sem
 * dependerem de cookies ou do ambiente web.
 */
final readonly class ContextoOperador
{
    public function __construct(
        public int $userId,
        public int $nivel,
        public ?int $edicaoAtivaId,
    ) {
    }
}
