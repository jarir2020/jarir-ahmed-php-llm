<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface ChunkingStrategy
{
    public function chunk(string $text, array $options = []): array;
}
