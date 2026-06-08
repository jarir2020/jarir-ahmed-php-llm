<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class GrokDriver implements LLMDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'grok-1';
        $this->provider = $config['driver'] ?? 'grok';
    }

    public function chat(array $messages, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat/completions', array_merge([
                'model' => $this->model,
                'messages' => $messages,
            ], $options));

        $data = $response->throw()->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'role' => 'assistant',
            'raw' => $data,
        ];
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $response = Http::withToken($this->config['api_key'])
            ->withHeaders(['Accept' => 'text/event-stream'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat/completions', array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'stream' => true,
            ], $options));

        $body = $response->throw()->body();

        foreach (explode("\n", $body) as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);

                if ($data === '[DONE]') {
                    break;
                }

                $chunk = json_decode($data, true);

                if (isset($chunk['choices'][0]['delta']['content'])) {
                    yield [
                        'content' => $chunk['choices'][0]['delta']['content'],
                        'role' => 'assistant',
                    ];
                }
            }
        }
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat/completions', array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'tools' => $tools,
            ], $options));

        $data = $response->throw()->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'role' => 'assistant',
            'tool_calls' => $data['choices'][0]['message']['tool_calls'] ?? [],
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
        return rtrim($this->config['base_url'] ?? 'https://api.x.ai/v1', '/');
    }
}
