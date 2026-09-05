<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/api/v1/health', static fn (): Response => Response::json(['ok' => true]));

        $response = $router->handle(new Request('GET', '/api/v1/health'));

        self::assertSame(200, $response->status());
        self::assertSame('{"ok":true}', $response->body());
    }

    public function testDispatchesRouteParametersAndDecodesThem(): void
    {
        $router = new Router();
        $router->get('/api/v1/items/{item_id}', static fn (Request $request, array $parameters): Response => Response::json([
            'id' => $parameters['item_id'],
        ]));

        $response = $router->handle(new Request('GET', '/api/v1/items/a%2Fb'));

        self::assertSame(200, $response->status());
        self::assertSame('{"id":"a\\/b"}', $response->body());
    }

    public function testReturnsNotFoundForUnknownRoute(): void
    {
        $router = new Router();

        $response = $router->handle(new Request('GET', '/api/v1/missing'));

        self::assertSame(404, $response->status());
    }

    public function testReturnsMethodNotAllowedAndAllowHeader(): void
    {
        $router = new Router();
        $router->get('/api/v1/health', static fn (): Response => Response::json(['ok' => true]));

        $response = $router->handle(new Request('POST', '/api/v1/health'));

        self::assertSame(405, $response->status());
        self::assertSame('GET', $response->headers()['Allow']);
    }
}
