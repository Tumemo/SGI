<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use App\Modules\Acesso\Domain\TermosRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class TermosService
{
    public function __construct(private readonly TermosRepository $termos)
    {
    }

    /** @return array<string, mixed>|null */
    public function consultar(int $userId): ?array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Sessão expirada ou usuário não autenticado.');
        }
        $user = $this->termos->findUser($userId);
        if ($user === null) {
            return null;
        }
        return [
            'termo_aceito' => ($user['aceito_termo'] ?? null) === 'sim',
            'exige_troca_senha' => (int) ($user['nivel_usuario'] ?? -1) === 3
                && password_verify('123', (string) ($user['senha_usuario'] ?? '')),
        ];
    }

    /** @return array{status: string, exige_troca_senha?: bool} */
    public function aceitar(int $userId): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Sessão expirada ou usuário não autenticado.');
        }
        $user = $this->termos->findUser($userId);
        if ($user === null) {
            return ['status' => 'not_found'];
        }

        $exigeTroca = (int) ($user['nivel_usuario'] ?? -1) === 3
            && password_verify('123', (string) ($user['senha_usuario'] ?? ''));
        $editionId = (int) ($user['interclasses_id_interclasse'] ?? 0);
        if ($editionId <= 0) {
            $editionId = $this->termos->findActiveEdition() ?? 0;
            if ($editionId <= 0) {
                return ['status' => 'no_edition'];
            }
            $this->termos->assignEdition($userId, $editionId);
        }
        if (($user['aceito_termo'] ?? null) === 'sim') {
            return ['status' => 'already_accepted', 'exige_troca_senha' => $exigeTroca];
        }

        $this->termos->accept($userId, $editionId, (new DateTimeImmutable())->format('Y-m-d H:i:s'));
        return ['status' => 'accepted', 'exige_troca_senha' => $exigeTroca];
    }
}
