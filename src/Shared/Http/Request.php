<?php

declare(strict_types=1);

namespace App\Shared\Http;

/**
 * Representa uma requisição HTTP sem expor superglobais ao domínio.
 */
final class Request
{
    /** @var array<string, mixed>|null */
    private ?array $body = null;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $files
     * @param array<string, string> $headers
     * @param array<string, mixed> $server
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $files = [],
        private readonly array $headers = [],
        private readonly array $server = [],
        private readonly ?string $rawBody = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($requestUri, PHP_URL_PATH);
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
                continue;
            }

            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            is_string($path) && $path !== '' ? rawurldecode($path) : '/',
            $_GET,
            $_POST,
            $_FILES,
            $headers,
            $_SERVER,
            file_get_contents('php://input') ?: null,
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key, mixed $default = null): mixed
    {
        return $this->files[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function allInput(): array
    {
        return $this->body();
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalised = strtolower($name);
        foreach ($this->headers as $headerName => $value) {
            if (strtolower($headerName) === $normalised) {
                return $value;
            }
        }

        return $default;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    private function body(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        $decoded = [];
        if (is_string($this->rawBody) && trim($this->rawBody) !== '') {
            $candidate = json_decode($this->rawBody, true);
            if (is_array($candidate)) {
                $decoded = $candidate;
            }
        }

        $this->body = array_merge($this->post, $decoded);

        return $this->body;
    }
}
