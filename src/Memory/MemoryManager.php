<?php

namespace JarirAhmed\PhpLlm\Memory;

use JarirAhmed\PhpLlm\Contracts\MemoryDriver;
use JarirAhmed\PhpLlm\Memory\Drivers\ConversationMemoryDriver;
use JarirAhmed\PhpLlm\Memory\Drivers\PersistentMemoryDriver;
use JarirAhmed\PhpLlm\Memory\Drivers\SessionMemoryDriver;

class MemoryManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): MemoryDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.memory.default', 'session');

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    public function extend(string $name, callable $resolver): static
    {
        $this->drivers[$name] = $resolver($this->app);

        return $this;
    }

    protected function resolve(string $name): MemoryDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.memory.drivers.{$name}");

        return match ($config['driver']) {
            'session' => new SessionMemoryDriver($config),
            'conversation' => new ConversationMemoryDriver($config),
            'persistent' => new PersistentMemoryDriver($config),
            default => throw new \InvalidArgumentException("Unknown memory driver: {$name}"),
        };
    }
}
