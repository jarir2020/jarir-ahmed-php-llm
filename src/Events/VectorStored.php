<?php

namespace JarirAhmed\PhpLlm\Events;


class VectorStored
{

    public function __construct(
        public string $provider,
        public string $collection,
        public int $recordCount,
    ) {}
}
