<?php

declare(strict_types=1);

namespace App\Shared\Http;

interface RequestHandler
{
    public function handle(Request $request): Response;
}
