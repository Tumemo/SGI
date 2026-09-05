<?php

declare(strict_types=1);

namespace App\Shared\Http;

use JsonException;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    public static function json(mixed $payload, int $status = 200, array $headers = []): self
    {
        try {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $body = json_encode([
                'success' => false,
                'message' => 'Não foi possível serializar a resposta.',
            ], JSON_UNESCAPED_UNICODE) ?: '{"success":false}';
            $status = 500;
        }

        return new self($body, $status, array_merge([
            'Content-Type' => 'application/json; charset=utf-8',
        ], $headers));
    }

    public static function empty(int $status = 204, array $headers = []): self
    {
        return new self('', $status, $headers);
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
