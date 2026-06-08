<?php

namespace JarirAhmed\PhpLlm\Events;


class MessageSending
{

    public function __construct(
        public string $provider,
        public string $model,
        public array $messages,
        public array $options,
    ) {}
}
