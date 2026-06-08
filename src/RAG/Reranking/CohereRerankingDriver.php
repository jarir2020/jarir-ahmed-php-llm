<?php

namespace JarirAhmed\PhpLlm\RAG\Reranking;

use JarirAhmed\PhpLlm\Support\Http;

class CohereRerankingDriver
{
    protected string $model;

    public function __construct(
        protected string $apiKey,
        protected string $baseUrl = 'https://api.cohere.com/v1',
    ) {
        $this->apiKey = $apiKey ?: (string) \JarirAhmed\PhpLlm\Support\Config::get('ai.embedding.cohere.api_key', '');
        $this->baseUrl = $baseUrl ?: (string) \JarirAhmed\PhpLlm\Support\Config::get('ai.embedding.cohere.base_url', 'https://api.cohere.com/v1');
        $this->model = (string) \JarirAhmed\PhpLlm\Support\Config::get('ai.embedding.cohere.model', 'rerank-english-v3.0');
    }

    public function rerank(string $query, array $documents, array $options = []): array
    {
        $model = $options['model'] ?? $this->model;
        $topN = $options['top_n'] ?? count($documents);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/rerank", [
            'query' => $query,
            'documents' => $documents,
            'model' => $model,
            'top_n' => $topN,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Cohere rerank API error: '.$response->body());
        }

        return $response->json();
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
}
