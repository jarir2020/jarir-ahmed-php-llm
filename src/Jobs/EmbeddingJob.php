<?php

namespace JarirAhmed\PhpLlm\Jobs;

use JarirAhmed\PhpLlm\Embedding\EmbeddingManager;

/**
 * Framework-agnostic deferred task. Serialize the public payload onto any queue
 * (Laravel, Symfony Messenger, a raw RabbitMQ worker, ...) and call handle()
 * with an EmbeddingManager when the worker picks it up.
 */
class EmbeddingJob
{
    public function __construct(
        public string $text,
        public string $collection,
        public array $options = [],
    ) {}

    public function handle(EmbeddingManager $embedding): array
    {
        return $embedding->driver()->embed($this->text);
    }
}
