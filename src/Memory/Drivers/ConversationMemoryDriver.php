<?php

namespace JarirAhmed\PhpLlm\Memory\Drivers;

use JarirAhmed\PhpLlm\Contracts\MemoryDriver;

class ConversationMemoryDriver implements MemoryDriver
{
    protected static array $storage = [];

    public function __construct(
        protected array $config = [],
    ) {}

    public function add(string $sessionId, array $message): void
    {
        static::$storage[$sessionId][] = $message;
    }

    public function get(string $sessionId, int $limit = 10): array
    {
        $messages = static::$storage[$sessionId] ?? [];
        $limit = $this->config['limit'] ?? $limit;

        return array_slice($messages, -$limit);
    }

    public function clear(string $sessionId): void
    {
        static::$storage[$sessionId] = [];
    }

    public function delete(string $sessionId): void
    {
        unset(static::$storage[$sessionId]);
    }
}
