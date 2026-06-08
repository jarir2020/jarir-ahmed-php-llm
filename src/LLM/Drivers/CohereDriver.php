<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class CohereDriver implements LLMDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'command-r-plus';
        $this->provider = $config['driver'] ?? 'cohere';
    }

    public function chat(array $messages, array $options = []): array
    {
        $payload = $this->buildChatPayload($messages, $options);

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat', $payload);

        $data = $response->throw()->json();

        return [
            'content' => $data['text'] ?? '',
            'role' => 'assistant',
            'raw' => $data,
        ];
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $payload = $this->buildChatPayload($messages, $options);
        $payload['stream'] = true;

        $response = Http::withToken($this->config['api_key'])
            ->withHeaders(['Accept' => 'text/event-stream'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat', $payload);

        $body = $response->throw()->body();

        foreach (explode("\n", $body) as $line) {
            $line = trim($line);

            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);

                $chunk = json_decode($data, true);

                if (isset($chunk['text'])) {
                    yield [
                        'content' => $chunk['text'],
                        'role' => 'assistant',
                    ];
                }
            }
        }
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        $payload = $this->buildChatPayload($messages, $options);
        $payload['tools'] = $this->formatTools($tools);

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/chat', $payload);

        $data = $response->throw()->json();

        return [
            'content' => $data['text'] ?? '',
            'role' => 'assistant',
            'tool_calls' => $data['tool_calls'] ?? [],
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
        return rtrim($this->config['base_url'] ?? 'https://api.cohere.com/v1', '/');
    }

    protected function buildChatPayload(array $messages, array $options): array
    {
        $systemInstruction = $this->extractSystemInstruction($messages);

        $messages = array_values($messages);

        $lastMessage = end($messages);
        $chatHistory = array_slice($messages, 0, -1);

        $payload = array_merge([
            'model' => $this->model,
            'message' => $lastMessage['content'] ?? '',
        ], $options);

        if (! empty($this->config['temperature'])) {
            $payload['temperature'] = (float) $this->config['temperature'];
        }

        if ($systemInstruction !== null) {
            $payload['preamble'] = $systemInstruction;
        }

        if (! empty($chatHistory)) {
            $payload['chat_history'] = array_map(function (array $message): array {
                return [
                    'role' => $message['role'] ?? 'user',
                    'message' => $message['content'] ?? '',
                ];
            }, $chatHistory);
        }

        return $payload;
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

    protected function formatTools(array $tools): array
    {
        return array_map(function (array $tool): array {
            return [
                'name' => $tool['function']['name'],
                'description' => $tool['function']['description'] ?? '',
                'parameterDefinitions' => $this->formatParameters($tool['function']['parameters'] ?? []),
            ];
        }, $tools);
    }

    protected function formatParameters(array $parameters): array
    {
        $definitions = [];

        foreach ($parameters['properties'] ?? [] as $name => $prop) {
            $definitions[$name] = [
                'description' => $prop['description'] ?? '',
                'type' => $prop['type'] ?? 'string',
                'required' => in_array($name, $parameters['required'] ?? []),
            ];
        }

        return $definitions;
    }
}
