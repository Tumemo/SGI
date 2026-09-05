<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http;

use App\Shared\Http\ExceptionMiddleware;
use App\Shared\Http\Middleware;
use App\Shared\Http\MiddlewareStack;
use App\Shared\Http\Request;
use App\Shared\Http\RequestHandler;
use App\Shared\Http\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MiddlewareStackTest extends TestCase
{
    public function testExecutesMiddlewaresInOrder(): void
    {
        $events = [];
        $middleware = static function (string $name) use (&$events): Middleware {
            return new class ($name, $events) implements Middleware {
                /** @param list<string> $events */
                public function __construct(private readonly string $name, private array &$events)
                {
                }

                public function process(Request $request, RequestHandler $next): Response
                {
                    $this->events[] = $this->name . ':before';
                    $response = $next->handle($request);
                    $this->events[] = $this->name . ':after';

                    return $response;
                }
            };
        };

        $stack = new MiddlewareStack(
            [$middleware('one'), $middleware('two')],
            new class ($events) implements RequestHandler {
                /** @param list<string> $events */
                public function __construct(private array &$events)
                {
                }

                public function handle(Request $request): Response
                {
                    $this->events[] = 'handler';

                    return Response::empty(204);
                }
            },
        );

        self::assertSame(204, $stack->handle(new Request('GET', '/'))->status());
        self::assertSame(['one:before', 'two:before', 'handler', 'two:after', 'one:after'], $events);
    }

    public function testExceptionMiddlewareConvertsUnexpectedExceptionsToJson(): void
    {
        $handler = new class () implements RequestHandler {
            public function handle(Request $request): Response
            {
                throw new RuntimeException('falha interna');
            }
        };

        $response = (new ExceptionMiddleware())->process(new Request('GET', '/api/v1/fail'), $handler);

        self::assertSame(500, $response->status());
        self::assertSame('{"success":false,"message":"Não foi possível processar a requisição."}', $response->body());
    }
}
