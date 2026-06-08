<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Support\PendingRequest;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;

class OpenAIDriver implements LLMDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? $this->defaultModel();
        $this->provider = $config['driver'] ?? 'openai';
    }

    public function chat(array $messages, array $options = []): array
    {
        $data = $this->pending()
            ->post($this->endpoint('/chat/completions'), $this->payload($messages, $options))
            ->throw()->json();

        return $this->formatResponse($data);
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $payload = $this->payload($messages, array_merge($options, ['stream' => true]));

        foreach ($this->pending()->stream('POST', $this->endpoint('/chat/completions'), $payload) as $data) {
            $chunk = json_decode($data, true);

            if (isset($chunk['choices'][0]['delta']['content'])) {
                yield [
                    'content' => $chunk['choices'][0]['delta']['content'],
                    'role'    => 'assistant',
                ];
            }
        }
    }

    public function tools(array $messages, array $tools, array $options = []): array
    {
        $payload = $this->payload($messages, array_merge(['tools' => $tools], $options));

        $data = $this->pending()
            ->post($this->endpoint('/chat/completions'), $payload)
            ->throw()->json();

        return $this->formatResponse($data) + [
            'tool_calls' => $data['choices'][0]['message']['tool_calls'] ?? [],
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

    // ---- extension points for OpenAI-compatible providers ----

    protected function defaultModel(): string
    {
        return 'gpt-4o';
    }

    /** Provider-specific extra headers (e.g. OpenRouter ranking headers). */
    protected function extraHeaders(): array
    {
        return [];
    }

    /** A PendingRequest pre-configured with auth, headers and timeout. */
    protected function pending(): PendingRequest
    {
        $request = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 60);

        $headers = $this->extraHeaders();
        if ($headers !== []) {
            $request->withHeaders($headers);
        }

        return $request;
    }

    protected function payload(array $messages, array $options): array
    {
        return array_merge([
            'model'    => $this->model,
            'messages' => $messages,
        ], $options);
    }

    protected function formatResponse(array $data): array
    {
        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'role'    => 'assistant',
            'raw'     => $data,
        ];
    }

    protected function endpoint(string $path): string
    {
        return $this->baseUrl().$path;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');
    }
}
