<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testMergesFormAndJsonBodyWithJsonTakingPrecedence(): void
    {
        $request = new Request(
            'post',
            '/api/v1/items',
            [],
            ['name' => 'form', 'page' => '1'],
            [],
            ['Content-Type' => 'application/json'],
            [],
            '{"name":"json","active":true}',
        );

        self::assertSame('POST', $request->method());
        self::assertSame('/api/v1/items', $request->path());
        self::assertSame('json', $request->input('name'));
        self::assertSame('1', $request->input('page'));
        self::assertTrue($request->input('active'));
    }

    public function testReadsQueryFilesHeadersAndServerCaseInsensitively(): void
    {
        $request = new Request(
            'GET',
            '/arquivo',
            ['id' => '42'],
            [],
            ['documento' => ['name' => 'demo.pdf']],
            ['X-SGI-CSRF' => 'token'],
            ['REQUEST_ID' => 'abc'],
        );

        self::assertSame('42', $request->query('id'));
        self::assertSame('demo.pdf', $request->file('documento')['name']);
        self::assertSame('token', $request->header('x-sgi-csrf'));
        self::assertSame('abc', $request->server('REQUEST_ID'));
        self::assertSame('fallback', $request->query('missing', 'fallback'));
    }
}
