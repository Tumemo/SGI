<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\ContextoOperador;

final class EdicaoAccessPolicy
{
    /**
     * @throws AcessoEdicaoNegadoException when the operator cannot access the resource edition
     */
    public function assertAllowed(ContextoOperador $operator, ?int $resourceEditionId): void
    {
        if ($resourceEditionId === null || $resourceEditionId <= 0) {
            throw new AcessoEdicaoNegadoException();
        }

        if (in_array($operator->nivel, [0, 1], true)) {
            return;
        }

        if ($operator->nivel === 2
            && $operator->edicaoAtivaId !== null
            && $operator->edicaoAtivaId > 0
            && $operator->edicaoAtivaId === $resourceEditionId) {
            return;
        }

        throw new AcessoEdicaoNegadoException();
    }

    public function allows(ContextoOperador $operator, ?int $resourceEditionId): bool
    {
        try {
            $this->assertAllowed($operator, $resourceEditionId);
            return true;
        } catch (AcessoEdicaoNegadoException) {
            return false;
        }
    }
}
