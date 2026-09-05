<?php

declare(strict_types=1);

namespace App\Shared\Http;

use LogicException;

/**
 * Roteador pequeno e determinístico para o monólito modular.
 *
 * Os endpoints legados continuam podendo ser atendidos pelo front controller;
 * as novas rotas não dependem de includes procedurais.
 */
final class Router implements RequestHandler
{
    /** @var list<array{methods: list<string>, pattern: string, regex: string, handler: callable}> */
    private array $routes = [];

    /**
     * @param list<string>|string $methods
     * @param callable(Request, array<string, string>): Response $handler
     */
    public function add(array|string $methods, string $pattern, callable $handler): self
    {
        $normalisedMethods = array_map(
            static fn (string $method): string => strtoupper($method),
            is_array($methods) ? $methods : [$methods],
        );

        $this->routes[] = [
            'methods' => $normalisedMethods,
            'pattern' => $pattern,
            'regex' => $this->compile($pattern),
            'handler' => $handler,
        ];

        return $this;
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function get(string $pattern, callable $handler): self
    {
        return $this->add('GET', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function post(string $pattern, callable $handler): self
    {
        return $this->add('POST', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function put(string $pattern, callable $handler): self
    {
        return $this->add('PUT', $pattern, $handler);
    }

    /** @param callable(Request, array<string, string>): Response $handler */
    public function delete(string $pattern, callable $handler): self
    {
        return $this->add('DELETE', $pattern, $handler);
    }

    public function handle(Request $request): Response
    {
        $allowedMethods = [];
        foreach ($this->routes as $route) {
            $matches = [];
            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            $allowedMethods = array_merge($allowedMethods, $route['methods']);
            if (!in_array($request->method(), $route['methods'], true)) {
                continue;
            }

            $parameters = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $parameters[$key] = rawurldecode((string) $value);
                }
            }

            $response = ($route['handler'])($request, $parameters);
            if (!$response instanceof Response) {
                throw new LogicException('Os controladores devem retornar uma instância de Response.');
            }

            return $response;
        }

        if ($allowedMethods !== []) {
            return Response::json([
                'success' => false,
                'message' => 'Método não permitido.',
            ], 405, ['Allow' => implode(', ', array_values(array_unique($allowedMethods)))]);
        }

        return Response::json([
            'success' => false,
            'message' => 'Rota não encontrada.',
        ], 404);
    }

    private function compile(string $pattern): string
    {
        $trimmed = trim($pattern, '/');
        if ($trimmed === '') {
            return '#^/?$#D';
        }

        $parts = explode('/', $trimmed);
        $compiled = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $part, $match) === 1) {
                $compiled[] = '(?P<' . $match[1] . '>[^/]+)';
                continue;
            }
            $compiled[] = preg_quote($part, '#');
        }

        return '#^/' . implode('/', $compiled) . '/?$#D';
    }
}
