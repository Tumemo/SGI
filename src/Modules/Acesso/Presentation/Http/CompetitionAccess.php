<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Application\AcessoEdicaoNegadoException;
use App\Modules\Acesso\Application\EdicaoAccessPolicy;
use App\Modules\Acesso\Domain\InterclasseRepository;
use App\Modules\Acesso\Domain\ContextoOperador;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Response;
use App\Shared\Http\SessionManager;

final class CompetitionAccess
{
    public function __construct(
        private readonly InterclasseRepository $editions,
        private readonly EdicaoAccessPolicy $policy = new EdicaoAccessPolicy(),
    ) {
    }

    public function context(): ContextoOperador
    {
        SessionManager::start();

        $level = (int) ($_SESSION['nivel'] ?? -1);
        $active = $this->editions->findActiveId();

        if ($level === 2) {
            if ($active === null) {
                unset($_SESSION['id_interclasse']);
            } else {
                $_SESSION['id_interclasse'] = $active;
            }
        }

        return new ContextoOperador(
            (int) ($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0),
            $level,
            $active,
        );
    }

    public function authorize(?int $resourceEditionId = null): ?Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
            return $denied;
        }

        $context = $this->context();
        if ($context->nivel === 2 && $context->edicaoAtivaId === null) {
            return Response::json(['success' => false, 'message' => 'Nenhuma edição de interclasse está ativa no momento. Entre em contato com o administrador.'], 403);
        }

        if ($resourceEditionId !== null) {
            try {
                $this->policy->assertAllowed($context, $resourceEditionId);
            } catch (AcessoEdicaoNegadoException) {
                return Response::json(['success' => false, 'message' => 'O operador não está autorizado a acessar a edição solicitada.'], 403);
            }
        }

        return null;
    }
}
