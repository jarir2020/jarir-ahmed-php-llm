<?php

namespace JarirAhmed\PhpLlm\Image;

use JarirAhmed\PhpLlm\Contracts\ImageDriver;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

class ImageManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): ImageDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.image', 'openai');

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

    protected function resolve(string $name): ImageDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.image.{$name}");

        if ($config === null) {
            throw LlmException::invalidProvider($name, 'Image');
        }

        return match ($config['driver']) {
            'openai' => new Drivers\OpenAIImageDriver($config),
            default => throw LlmException::invalidProvider($name, 'Image'),
        };
    }
}
