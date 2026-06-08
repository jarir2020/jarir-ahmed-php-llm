<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class AnthropicDriver implements LLMDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'claude-3-opus-20240229';
        $this->provider = $config['driver'] ?? 'anthropic';
    }

    public function chat(array $messages, array $options = []): array
    {
        $body = $this->buildBaseBody($messages);
        $body = array_merge($body, $options);

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/messages', $body);

        $data = $response->throw()->json();

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'role' => 'assistant',
            'raw' => $data,
        ];
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $body = $this->buildBaseBody($messages);
        $body['stream'] = true;
        $body = array_merge($body, $options);

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream',
        ])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/messages', $body);

        $body = $response->throw()->body();

        foreach (explode("\n", $body) as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);

                $chunk = json_decode($data, true);

                if (isset($chunk['type']) && $chunk['type'] === 'content_block_delta' && isset($chunk['delta']['text'])) {
                    yield [
                        'content' => $chunk['delta']['text'],
                        'role' => 'assistant',
                    ];
                }
            }
        }
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        $body = $this->buildBaseBody($messages);
        $body['tools'] = $tools;
        $body = array_merge($body, $options);

        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/messages', $body);

        $data = $response->throw()->json();

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'role' => 'assistant',
            'tool_calls' => $this->parseToolCalls($data),
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

    protected function buildBaseBody(array $messages): array
    {
        $body = [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => (int) ($this->config['max_tokens'] ?? 4096),
        ];

        if (! empty($this->config['temperature'])) {
            $body['temperature'] = (float) $this->config['temperature'];
        }

        $systemInstruction = $this->extractSystemInstruction($messages);

        if ($systemInstruction !== null) {
            $body['system'] = $systemInstruction;
        }

        return $body;
    }

    protected function extractSystemInstruction(array &$messages): ?string
    {
        foreach ($messages as $i => $message) {
            if (($message['role'] ?? '') === 'system') {
                $text = $message['content'] ?? '';

                unset($messages[$i]);

                return $text;
            }
        }

        return null;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.anthropic.com/v1', '/');
    }

    protected function parseToolCalls(array $data): array
    {
        $toolCalls = [];

        foreach ($data['content'] ?? [] as $block) {
            if (isset($block['type']) && $block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id' => $block['id'],
                    'type' => 'function',
                    'function' => [
                        'name' => $block['name'],
                        'arguments' => json_encode($block['input']),
                    ],
                ];
            }
        }

        return $toolCalls;
    }
}
