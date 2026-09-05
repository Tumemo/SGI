<?php

declare(strict_types=1);

namespace App\Shared\Http;

interface Middleware
{
    public function process(Request $request, RequestHandler $next): Response;
}
