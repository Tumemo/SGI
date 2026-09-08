<?php

declare(strict_types=1);

namespace App\Modules\Acesso\Application;

use RuntimeException;

final class AcessoEdicaoNegadoException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O operador não está autorizado a acessar a edição solicitada.');
    }
}
