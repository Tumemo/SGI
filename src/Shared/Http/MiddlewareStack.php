<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class MiddlewareStack implements RequestHandler
{
    /** @var list<Middleware> */
    private array $middlewares;

    /**
     * @param list<Middleware> $middlewares
     */
    public function __construct(array $middlewares, private readonly RequestHandler $handler)
    {
        $this->middlewares = $middlewares;
    }

    public function handle(Request $request): Response
    {
        return $this->dispatch(0, $request);
    }

    /**
     * Avança na cadeia. Público apenas para o próximo handler interno criado
     * pela própria pilha; controladores devem chamar handle().
     */
    public function dispatch(int $index, Request $request): Response
    {
        if (!isset($this->middlewares[$index])) {
            return $this->handler->handle($request);
        }

        $middleware = $this->middlewares[$index];
        $next = new class ($this, $index + 1) implements RequestHandler {
            public function __construct(private readonly MiddlewareStack $stack, private readonly int $index)
            {
            }

            public function handle(Request $request): Response
            {
                return $this->stack->dispatch($this->index, $request);
            }
        };

        return $middleware->process($request, $next);
    }
}
