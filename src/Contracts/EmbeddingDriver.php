<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface EmbeddingDriver
{
    public function embed(string $text): array;

    public function embedBatch(array $texts): array;

    public function dimensions(): int;

    public function setModel(string $model): static;

    public function getModel(): string;
}
