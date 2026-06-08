<?php

namespace JarirAhmed\PhpLlm\Embedding;

use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

class EmbeddingManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): EmbeddingDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.embedding', 'openai');

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

    protected function resolve(string $name): EmbeddingDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.embedding.{$name}");

        if ($config === null) {
            throw LlmException::invalidProvider($name, 'Embedding');
        }

        return match ($config['driver']) {
            'openai' => new Drivers\OpenAIEmbeddingDriver($config),
            'ollama' => new Drivers\OllamaEmbeddingDriver($config),
            'gemini' => new Drivers\GeminiEmbeddingDriver($config),
            'mistral' => new Drivers\MistralEmbeddingDriver($config),
            'cohere' => new Drivers\CohereEmbeddingDriver($config),
            default => throw LlmException::invalidProvider($name, 'Embedding'),
        };
    }
}
