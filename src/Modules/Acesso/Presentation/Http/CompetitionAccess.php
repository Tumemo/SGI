<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Presentation\Http;

use App\Modules\Acesso\Domain\InterclasseRepository;
use App\Shared\Http\AccessGuard;
use App\Shared\Http\Response;

final class CompetitionAccess
{
    public function __construct(private readonly InterclasseRepository $editions)
    {
    }

    public function authorize(): ?Response
    {
        if (($denied = AccessGuard::authorize([0, 1, 2])) !== null) {
            return $denied;
        }
        if ((int) $_SESSION['nivel'] === 2) {
            $active = $this->editions->findActiveId();
            if ($active === null) {
                return Response::json(['success' => false, 'message' => 'Nenhuma edição de interclasse está ativa no momento. Entre em contato com o administrador.'], 403);
            }
            $_SESSION['id_interclasse'] = $active;
        }
        return null;
    }
}
