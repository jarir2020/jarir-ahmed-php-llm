<?php

namespace JarirAhmed\PhpLlm\LLM;

use JarirAhmed\PhpLlm\Contracts\LLMDriver;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

class LLMManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): LLMDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.llm', 'openai');

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

    protected function resolve(string $name): LLMDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.llm.{$name}");

        if ($config === null) {
            throw LlmException::invalidProvider($name, 'LLM');
        }

        return match ($config['driver']) {
            'openai' => new Drivers\OpenAIDriver($config),
            'anthropic' => new Drivers\AnthropicDriver($config),
            'gemini' => new Drivers\GeminiDriver($config),
            'ollama' => new Drivers\OllamaDriver($config),
            'grok' => new Drivers\GrokDriver($config),
            'mistral' => new Drivers\MistralDriver($config),
            'cohere' => new Drivers\CohereDriver($config),
            'deepseek' => new Drivers\DeepSeekDriver($config),
            'groq' => new Drivers\GroqDriver($config),
            'openrouter' => new Drivers\OpenRouterDriver($config),
            'azure' => new Drivers\AzureOpenAIDriver($config),
            default => throw LlmException::invalidProvider($name, 'LLM'),
        };
    }
}
