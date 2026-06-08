<?php

namespace JarirAhmed\PhpLlm\Events;


class DocumentIndexed
{

    public function __construct(
        public string $collection,
        public array $document,
        public int $chunkCount,
    ) {}
}
