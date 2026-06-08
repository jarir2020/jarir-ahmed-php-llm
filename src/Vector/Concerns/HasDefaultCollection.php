<?php

namespace JarirAhmed\PhpLlm\Vector\Concerns;

trait HasDefaultCollection
{
    public function defaultCollection(?string $collection = null): string
    {
        return $collection ?? $this->config['collection'] ?? \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.collection', 'default');
    }
}
