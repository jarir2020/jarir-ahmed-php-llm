<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class OllamaDriver implements LLMDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'llama3';
        $this->provider = $config['driver'] ?? 'ollama';
    }

    public function chat(array $messages, array $options = []): array
    {
        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/api/chat', array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
            ], $options));

        $data = $response->throw()->json();

        return [
            'content' => $data['message']['content'] ?? '',
            'role' => 'assistant',
            'raw' => $data,
        ];
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $response = Http::withHeaders(['Accept' => 'text/event-stream'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/api/chat', array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'stream' => true,
            ], $options));

        $body = $response->throw()->body();

        foreach (explode("\n", $body) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $chunk = json_decode($line, true);

            if (isset($chunk['done']) && $chunk['done'] === true) {
                break;
            }

            if (isset($chunk['message']['content'])) {
                yield [
                    'content' => $chunk['message']['content'],
                    'role' => 'assistant',
                ];
            }
        }
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/api/chat', array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $tools,
                'stream' => false,
            ], $options));

        $data = $response->throw()->json();

        return [
            'content' => $data['message']['content'] ?? '',
            'role' => 'assistant',
            'tool_calls' => $data['message']['tool_calls'] ?? [],
            'raw' => $data,
        ];
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

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'http://localhost:11434', '/');
    }
}
