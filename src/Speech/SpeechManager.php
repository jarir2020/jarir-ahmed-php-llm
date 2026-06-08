<?php

namespace JarirAhmed\PhpLlm\Speech;

use JarirAhmed\PhpLlm\Contracts\SpeechDriver;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

class SpeechManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): SpeechDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.speech', 'openai');

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

    protected function resolve(string $name): SpeechDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.speech.{$name}");

        if ($config === null) {
            throw LlmException::invalidProvider($name, 'Speech');
        }

        return match ($config['driver']) {
            'openai' => new Drivers\OpenAISpeechDriver($config),
            default => throw LlmException::invalidProvider($name, 'Speech'),
        };
    }
}
