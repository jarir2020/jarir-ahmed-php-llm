<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface MemoryDriver
{
    public function add(string $sessionId, array $message): void;

    public function get(string $sessionId, int $limit = 10): array;

    public function clear(string $sessionId): void;

    public function delete(string $sessionId): void;
}
