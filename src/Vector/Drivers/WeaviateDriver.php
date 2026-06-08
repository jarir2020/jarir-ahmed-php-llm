<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

class WeaviateDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        $className = $this->className($name);

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v1/schema', [
                'class' => $className,
                'vectorizer' => $options['vectorizer'] ?? 'none',
                'vectorIndexType' => $options['vector_index_type'] ?? 'hnsw',
                'vectorIndexConfig' => $options['vector_index_config'] ?? [
                    'distance' => $options['distance'] ?? 'cosine',
                ],
                'properties' => $options['properties'] ?? [],
            ]);

        return $response->throw()->json();
    }

    public function deleteCollection(string $name): bool
    {
        $className = $this->className($name);

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->delete($this->baseUrl()."/v1/schema/{$className}");

        return $response->throw()->successful();
    }

    public function listCollections(): array
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl().'/v1/schema');

        $data = $response->throw()->json();

        return $data['classes'] ?? [];
    }

    public function upsert(string $collection, array $records): array
    {
        $className = $this->className($collection);

        $ids = [];

        foreach ($records as $record) {
            $response = Http::withToken($this->config['api_key'] ?? '')
                ->timeout($this->config['timeout'] ?? 30)
                ->post($this->baseUrl().'/v1/objects', [
                    'class' => $className,
                    'id' => $record['id'],
                    'vector' => $record['values'] ?? $record['vector'],
                    'properties' => $record['metadata'] ?? $record['properties'] ?? [],
                ]);

            $data = $response->throw()->json();
            $ids[] = $data['id'] ?? $record['id'];
        }

        return ['status' => 'upserted', 'ids' => $ids];
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $className = $this->className($collection);
        $limit = $options['top_k'] ?? 10;

        $query = <<<GQL
        {
            Get {
                {$className}(
                    nearVector: {vector: [{$this->vectorToString($vector)}]}
                    limit: {$limit}
                ) {
                    _additional {
                        id
                        distance
                    }
                }
            }
        }
        GQL;

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v1/graphql', [
                'query' => $query,
            ]);

        return $response->throw()->json();
    }

    public function delete(string $collection, string|array $id): bool
    {
        $className = $this->className($collection);

        $ids = is_array($id) ? $id : [$id];

        foreach ($ids as $singleId) {
            Http::withToken($this->config['api_key'] ?? '')
                ->timeout($this->config['timeout'] ?? 30)
                ->delete($this->baseUrl()."/v1/objects/{$className}/{$singleId}")
                ->throw();
        }

        return true;
    }

    public function count(string $collection): int
    {
        $className = $this->className($collection);

        $query = <<<GQL
        {
            Aggregate {
                {$className} {
                    meta {
                        count
                    }
                }
            }
        }
        GQL;

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v1/graphql', [
                'query' => $query,
            ]);

        $data = $response->throw()->json();

        return $data['data']['Aggregate'][$className][0]['meta']['count'] ?? 0;
    }

    protected function className(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);

        return ucfirst($name);
    }

    protected function vectorToString(array $vector): string
    {
        return implode(', ', $vector);
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['host'] ?? 'http://localhost:8080', '/');
    }
}
