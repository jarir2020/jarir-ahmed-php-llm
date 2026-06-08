<?php

namespace JarirAhmed\PhpLlm\Memory\Drivers;

use JarirAhmed\PhpLlm\Contracts\MemoryDriver;
use JarirAhmed\PhpLlm\Support\SessionStore;

class SessionMemoryDriver implements MemoryDriver
{
    public function __construct(
        protected array $config = [],
    ) {}

    public function add(string $sessionId, array $message): void
    {
        $key = "ai_memory_{$sessionId}";
        $messages = SessionStore::get($key, []);
        $messages[] = $message;
        SessionStore::put($key, $messages);
    }

    public function get(string $sessionId, int $limit = 10): array
    {
        $key = "ai_memory_{$sessionId}";
        $messages = SessionStore::get($key, []);
        $limit = $this->config['limit'] ?? $limit;

        return array_slice($messages, -$limit);
    }

    public function clear(string $sessionId): void
    {
        SessionStore::forget("ai_memory_{$sessionId}");
    }

    public function delete(string $sessionId): void
    {
        SessionStore::forget("ai_memory_{$sessionId}");
    }
}
