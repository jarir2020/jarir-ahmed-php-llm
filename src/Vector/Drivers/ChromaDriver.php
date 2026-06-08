<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

class ChromaDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    protected array $collectionCache = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/api/v1/collections', [
                'name' => $name,
                'metadata' => $options['metadata'] ?? [
                    'hnsw:space' => $options['distance'] ?? 'cosine',
                    'dimension' => $dimensions,
                ],
            ]);

        $data = $response->throw()->json();

        $this->collectionCache[$name] = $data['id'] ?? $data['uuid'] ?? null;

        return $data;
    }

    public function deleteCollection(string $name): bool
    {
        $id = $this->resolveCollectionId($name);

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->delete($this->baseUrl()."/api/v1/collections/{$id}");

        unset($this->collectionCache[$name]);

        return $response->throw()->successful();
    }

    public function listCollections(): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl().'/api/v1/collections');

        return $response->throw()->json();
    }

    public function upsert(string $collection, array $records): array
    {
        $id = $this->resolveCollectionId($collection);

        $ids = [];
        $embeddings = [];
        $metadatas = [];

        foreach ($records as $record) {
            $ids[] = $record['id'];
            $embeddings[] = $record['values'] ?? $record['vector'];
            $metadatas[] = $record['metadata'] ?? $record['payload'] ?? [];
        }

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/api/v1/collections/{$id}/add", [
                'ids' => $ids,
                'embeddings' => $embeddings,
                'metadatas' => $metadatas,
            ]);

        return $response->throw()->json();
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $id = $this->resolveCollectionId($collection);

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/api/v1/collections/{$id}/query", [
                'query_embeddings' => [$vector],
                'n_results' => $options['top_k'] ?? 10,
                'include' => $options['include'] ?? ['metadatas', 'distances'],
            ]);

        return $response->throw()->json();
    }

    public function delete(string $collection, string|array $id): bool
    {
        $collectionId = $this->resolveCollectionId($collection);

        $ids = is_array($id) ? $id : [$id];

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/api/v1/collections/{$collectionId}/delete", [
                'ids' => $ids,
            ]);

        return $response->throw()->successful();
    }

    public function count(string $collection): int
    {
        $id = $this->resolveCollectionId($collection);

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl()."/api/v1/collections/{$id}");

        $data = $response->throw()->json();

        return $data['count'] ?? 0;
    }

    protected function resolveCollectionId(string $name): string
    {
        if (isset($this->collectionCache[$name])) {
            return $this->collectionCache[$name];
        }

        $collections = $this->listCollections();

        foreach ($collections as $collection) {
            if (($collection['name'] ?? '') === $name) {
                $id = $collection['id'] ?? $collection['uuid'] ?? $name;

                $this->collectionCache[$name] = $id;

                return $id;
            }
        }

        return $name;
    }

    protected function headers(): array
    {
        $headers = [];

        if (! empty($this->config['api_key'])) {
            $headers['Authorization'] = 'Bearer '.$this->config['api_key'];
        }

        return $headers;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['host'] ?? 'http://localhost:8000', '/');
    }
}
