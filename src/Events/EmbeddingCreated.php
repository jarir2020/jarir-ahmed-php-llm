<?php

namespace JarirAhmed\PhpLlm\Events;


class EmbeddingCreated
{

    public function __construct(
        public string $provider,
        public string $model,
        public string $text,
        public array $embedding,
        public int $dimensions,
    ) {}
}
