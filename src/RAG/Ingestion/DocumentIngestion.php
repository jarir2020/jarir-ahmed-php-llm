<?php

namespace JarirAhmed\PhpLlm\RAG\Ingestion;

use JarirAhmed\PhpLlm\Contracts\ChunkingStrategy;
use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\RAG\Chunking\RecursiveChunking;
use JarirAhmed\PhpLlm\RAG\Document;

class DocumentIngestion
{
    public function __construct(
        protected EmbeddingDriver $embedder,
        protected VectorDriver $vectorStore,
        protected ChunkingStrategy $strategy = new RecursiveChunking,
    ) {}

    public function ingest(Document $doc, string $collection, array $options = []): array
    {
        $chunks = $this->strategy->chunk($doc->content(), $options);
        $records = [];

        foreach ($chunks as $i => $chunk) {
            $embedded = $this->embedder->embed($chunk);
            $vector = $embedded['embedding'] ?? $embedded;
            $id = md5($collection.'_'.$i.'_'.$chunk);

            $records[] = [
                'id' => $id,
                'vector' => $vector,
                'payload' => [
                    'content' => $chunk,
                    'chunk_index' => $i,
                    'metadata' => $doc->metadata(),
                    'source' => $doc->path(),
                ],
            ];
        }

        return $this->vectorStore->upsert($collection, $records);
    }

    public function ingestFromPath(string $path, string $collection, array $options = []): array
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }

        $metadata = ['source_path' => $path, 'extension' => $extension];

        $parsed = match ($extension) {
            'csv' => $this->parseCsv($content),
            'json' => $this->parseJson($content),
            default => $content,
        };

        $text = is_string($parsed) ? $parsed : implode("\n", $parsed);

        $doc = new Document($text, $metadata, $path);

        return $this->ingest($doc, $collection, $options);
    }

    public function ingestRaw(string $content, string $collection, array $options = []): array
    {
        $doc = new Document($content, ['source' => 'raw_input']);

        return $this->ingest($doc, $collection, $options);
    }

    public function setChunkStrategy(ChunkingStrategy $strategy): static
    {
        $this->strategy = $strategy;

        return $this;
    }

    protected function parseCsv(string $content): string
    {
        $rows = array_map('str_getcsv', explode("\n", trim($content)));
        $lines = [];

        foreach ($rows as $row) {
            if (count($row) > 0 && trim($row[0]) !== '') {
                $lines[] = implode(' ', $row);
            }
        }

        return implode("\n", $lines);
    }

    protected function parseJson(string $content): string
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
