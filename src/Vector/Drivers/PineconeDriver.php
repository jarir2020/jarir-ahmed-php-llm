<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

class PineconeDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    protected ?string $indexHost = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        return ['status' => 'not_supported'];
    }

    public function deleteCollection(string $name): bool
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->delete($this->baseUrl()."/databases/{$name}");

        return $response->throw()->successful();
    }

    public function listCollections(): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->get($this->baseUrl().'/databases');

        return $response->throw()->json();
    }

    public function upsert(string $collection, array $records): array
    {
        $vectors = [];

        foreach ($records as $record) {
            $vectors[] = [
                'id' => $record['id'],
                'values' => $record['values'] ?? $record['vector'],
                'metadata' => $record['metadata'] ?? $record['payload'] ?? [],
            ];
        }

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->indexHost().'/vectors/upsert', [
                'vectors' => $vectors,
            ]);

        return $response->throw()->json();
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->indexHost().'/query', [
                'vector' => $vector,
                'topK' => $options['top_k'] ?? 10,
                'includeMetadata' => $options['include_metadata'] ?? true,
                'includeValues' => $options['include_values'] ?? false,
            ]);

        return $response->throw()->json();
    }

    public function delete(string $collection, string|array $id): bool
    {
        $ids = is_array($id) ? $id : [$id];

        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->indexHost().'/vectors/delete', [
                'ids' => $ids,
            ]);

        return $response->throw()->successful();
    }

    public function count(string $collection): int
    {
        $response = Http::withHeaders($this->headers())
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->indexHost().'/describe_index_stats');

        $data = $response->throw()->json();

        return $data['totalVectorCount'] ?? 0;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['host'] ?? 'https://api.pinecone.io', '/');
    }

    protected function indexHost(): string
    {
        if ($this->indexHost) {
            return $this->indexHost;
        }

        $this->indexHost = rtrim(
            $this->config['index_host'] ?? $this->config['host'] ?? 'https://api.pinecone.io',
            '/'
        );

        return $this->indexHost;
    }

    protected function headers(): array
    {
        return [
            'Api-Key' => $this->config['api_key'] ?? '',
        ];
    }
}
