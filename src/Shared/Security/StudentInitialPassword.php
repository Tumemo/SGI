<?php

declare(strict_types=1);

namespace App\Shared\Security;

/** Public credential used only to initialize or reset student accounts. */
final class StudentInitialPassword
{
    public const VALUE = 'sesi-senai';

    private function __construct()
    {
    }
}
