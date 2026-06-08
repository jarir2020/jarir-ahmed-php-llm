<?php

namespace JarirAhmed\PhpLlm\Support;

use Psr\Http\Message\ResponseInterface;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

/**
 * Thin wrapper over a PSR-7 response exposing the Laravel-style accessors the
 * drivers use: json(), body(), status(), successful(), failed(), throw().
 */
class Response
{
    protected ?string $cachedBody = null;

    public function __construct(
        protected ResponseInterface $response,
    ) {}

    public function status(): int
    {
        return $this->response->getStatusCode();
    }

    public function body(): string
    {
        return $this->cachedBody ??= (string) $this->response->getBody();
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $decoded = json_decode($this->body(), true);

        if ($key === null) {
            return $decoded ?? [];
        }

        $segment = $decoded;
        foreach (explode('.', $key) as $part) {
            if (is_array($segment) && array_key_exists($part, $segment)) {
                $segment = $segment[$part];
            } else {
                return $default;
            }
        }

        return $segment;
    }

    public function header(string $name): ?string
    {
        $values = $this->response->getHeader($name);

        return $values[0] ?? null;
    }

    public function successful(): bool
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    public function clientError(): bool
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function serverError(): bool
    {
        return $this->status() >= 500;
    }

    /** Throw an LlmException carrying the provider's error body on non-2xx. */
    public function throw(): static
    {
        if ($this->failed()) {
            throw LlmException::apiError('http', "HTTP {$this->status()}: {$this->body()}");
        }

        return $this;
    }

    public function toPsr(): ResponseInterface
    {
        return $this->response;
    }
}
