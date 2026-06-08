<?php

namespace JarirAhmed\PhpLlm\Jobs;

use JarirAhmed\PhpLlm\RAG\Document;
use JarirAhmed\PhpLlm\RAG\RAGManager;

/**
 * Framework-agnostic deferred task: chunk + ingest raw content into a collection.
 * Serialize the public payload onto any queue and call handle() on the worker.
 */
class ChunkingJob
{
    public function __construct(
        public string $content,
        public string $collection,
        public array $options = [],
    ) {}

    public function handle(RAGManager $rag): array
    {
        return $rag->ingestion()->ingest(
            new Document($this->content),
            $this->collection,
            $this->options,
        );
    }
}
