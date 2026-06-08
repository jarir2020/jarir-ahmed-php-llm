<?php

namespace JarirAhmed\PhpLlm\Testing;

use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class LlmFake implements EmbeddingDriver, LLMDriver
{
    protected string $model = 'fake';

    protected string $provider = 'fake';

    public function chat(array $messages, array $options = []): array
    {
        return [
            'content' => 'fake response',
            'role' => 'assistant',
        ];
    }

    public function stream(array $messages, array $options = []): iterable
    {
        yield ['content' => 'fake ', 'role' => 'assistant'];
        yield ['content' => 'response', 'role' => 'assistant'];
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        return $this->chat($messages, $options);
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function embed(string $text): array
    {
        return [
            'embedding' => array_fill(0, 1536, 0.1),
            'dimensions' => 1536,
            'model' => $this->model,
            'provider' => $this->provider,
        ];
    }

    public function embedBatch(array $texts): array
    {
        return array_map(fn (string $text) => $this->embed($text), $texts);
    }

    public function dimensions(): int
    {
        return 1536;
    }
}
