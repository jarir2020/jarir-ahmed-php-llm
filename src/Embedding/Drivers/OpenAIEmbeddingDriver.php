<?php

namespace JarirAhmed\PhpLlm\Embedding\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;

class OpenAIEmbeddingDriver implements EmbeddingDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    protected int $dimensionCount = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'text-embedding-3-small';
        $this->provider = $config['driver'] ?? 'openai';
    }

    public function embed(string $text): array
    {
        $body = [
            'input' => $text,
            'model' => $this->model,
        ];

        if (! empty($this->config['dimensions'])) {
            $body['dimensions'] = (int) $this->config['dimensions'];
        }

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/embeddings', $body);

        $data = $response->throw()->json();

        $embedding = $data['data'][0]['embedding'];
        $this->dimensionCount = count($embedding);

        return [
            'embedding' => $embedding,
            'dimensions' => $this->dimensionCount,
            'model' => $this->model,
            'provider' => $this->provider,
        ];
    }

    public function embedBatch(array $texts): array
    {
        $body = [
            'input' => $texts,
            'model' => $this->model,
        ];

        if (! empty($this->config['dimensions'])) {
            $body['dimensions'] = (int) $this->config['dimensions'];
        }

        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/embeddings', $body);

        $data = $response->throw()->json();

        $rows = $data['data'] ?? [];
        usort($rows, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        return array_map(fn ($row) => $row['embedding'], $rows);
    }

    public function dimensions(): int
    {
        return $this->dimensionCount;
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

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');
    }
}
