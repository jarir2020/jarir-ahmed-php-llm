<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

class QdrantDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->put($this->baseUrl()."/collections/{$name}", [
                'vectors' => [
                    'size' => $dimensions,
                    'distance' => $options['distance'] ?? 'Cosine',
                ],
            ]);

        return $response->throw()->json();
    }

    public function deleteCollection(string $name): bool
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->delete($this->baseUrl()."/collections/{$name}");

        return $response->throw()->successful();
    }

    public function listCollections(): array
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl().'/collections');

        return $response->throw()->json();
    }

    public function upsert(string $collection, array $records): array
    {
        $points = [];

        foreach ($records as $record) {
            $points[] = [
                'id' => $record['id'],
                'vector' => $record['values'] ?? $record['vector'],
                'payload' => $record['metadata'] ?? $record['payload'] ?? [],
            ];
        }

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->put($this->baseUrl()."/collections/{$collection}/points", [
                'points' => $points,
            ]);

        return $response->throw()->json();
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/collections/{$collection}/points/search", [
                'vector' => $vector,
                'limit' => $options['top_k'] ?? 10,
                'with_payload' => $options['with_payload'] ?? true,
                'with_vector' => $options['with_vector'] ?? false,
            ]);

        return $response->throw()->json();
    }

    public function delete(string $collection, string|array $id): bool
    {
        $ids = is_array($id) ? $id : [$id];

        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/collections/{$collection}/points/delete", [
                'points' => $ids,
            ]);

        return $response->throw()->successful();
    }

    public function count(string $collection): int
    {
        $response = Http::withToken($this->config['api_key'] ?? '')
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl()."/collections/{$collection}/points/count");

        $data = $response->throw()->json();

        return $data['result']['count'] ?? 0;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['host'] ?? 'http://localhost:6333', '/');
    }
}
