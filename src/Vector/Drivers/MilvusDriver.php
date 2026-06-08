<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

class MilvusDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/collections/create', [
                'collectionName' => $name,
                'dimension' => $dimensions,
                'primaryFieldName' => 'id',
                'idType' => 'Int64',
                'vectorFieldName' => 'vector',
                'metricType' => 'COSINE',
                'autoId' => false,
                'enableDynamicField' => true,
            ]);

        return $response->throw()->json();
    }

    public function deleteCollection(string $name): bool
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/collections/drop', [
                'collectionName' => $name,
            ]);

        return $response->throw()->successful();
    }

    public function listCollections(): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/collections/list');

        $data = $response->throw()->json();

        return $data['data'] ?? [];
    }

    public function upsert(string $collection, array $records): array
    {
        $rows = [];

        foreach ($records as $record) {
            $payload = $record['metadata'] ?? $record['payload'] ?? [];
            $row = array_merge(
                is_array($payload) ? $payload : [],
                [
                    'id' => (int) $record['id'],
                    'vector' => $record['values'] ?? $record['vector'],
                ],
            );

            $rows[] = $row;
        }

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/entities/insert', [
                'collectionName' => $collection,
                'data' => $rows,
            ]);

        return $response->throw()->json();
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/entities/search', [
                'collectionName' => $collection,
                'data' => [$vector],
                'annsField' => 'vector',
                'limit' => $options['top_k'] ?? 10,
                'outputFields' => $options['with_payload'] ?? ['*'],
                'searchParams' => [
                    'metricType' => $options['metric_type'] ?? 'COSINE',
                ],
            ]);

        return $response->throw()->json();
    }

    public function delete(string $collection, string|array $id): bool
    {
        $ids = is_array($id) ? $id : [$id];

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/entities/delete', [
                'collectionName' => $collection,
                'filter' => 'id in ['.implode(',', array_map(fn ($v) => (int) $v, $ids)).']',
            ]);

        return $response->throw()->successful();
    }

    public function count(string $collection): int
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/v2/vectordb/collections/get_stats', [
                'collectionName' => $collection,
            ]);

        $data = $response->throw()->json();

        return $data['data']['row_count'] ?? $data['row_count'] ?? 0;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.($this->config['api_key'] ?? ''),
            'Content-Type' => 'application/json',
        ];
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['host'] ?? 'http://localhost:19530', '/');
    }
}
