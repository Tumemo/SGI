<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testCreatesJsonResponseWithStatusAndContentType(): void
    {
        $response = Response::json(['mensagem' => 'Olá'], 201, ['Cache-Control' => 'no-store']);

        self::assertSame(201, $response->status());
        self::assertSame('{"mensagem":"Olá"}', $response->body());
        self::assertSame('application/json; charset=utf-8', $response->headers()['Content-Type']);
        self::assertSame('no-store', $response->headers()['Cache-Control']);
    }

    public function testCreatesEmptyResponse(): void
    {
        $response = Response::empty();

        self::assertSame(204, $response->status());
        self::assertSame('', $response->body());
    }
}
