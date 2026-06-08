<?php

namespace JarirAhmed\PhpLlm\Events;


class MessageReceived
{

    public function __construct(
        public string $provider,
        public string $model,
        public array $response,
        public float $latency,
    ) {}
}
