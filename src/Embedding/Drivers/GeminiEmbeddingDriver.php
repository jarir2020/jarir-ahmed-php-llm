<?php

namespace JarirAhmed\PhpLlm\Embedding\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;

class GeminiEmbeddingDriver implements EmbeddingDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    protected int $dimensionCount = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'text-embedding-004';
        $this->provider = $config['driver'] ?? 'gemini';
    }

    public function embed(string $text): array
    {
        $body = [
            'content' => [
                'parts' => [
                    ['text' => $text],
                ],
            ],
        ];

        if (! empty($this->config['dimensions'])) {
            $body['outputDimensionality'] = (int) $this->config['dimensions'];
        }

        $response = Http::timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/models/'.$this->model.':embedContent?key='.$this->config['api_key'], $body);

        $data = $response->throw()->json();

        $embedding = $data['embedding']['values'] ?? [];
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
        return array_map(fn (string $text) => $this->embed($text), $texts);
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
        return rtrim($this->config['base_url'] ?? 'https://generativelanguage.googleapis.com', '/');
    }
}
