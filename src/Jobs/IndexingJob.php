<?php

namespace JarirAhmed\PhpLlm\Jobs;

use JarirAhmed\PhpLlm\RAG\RAGManager;

/**
 * Framework-agnostic deferred task: ingest a document from a path into a collection.
 * Serialize the public payload onto any queue and call handle() on the worker.
 */
class IndexingJob
{
    public function __construct(
        public string $path,
        public string $collection,
        public array $options = [],
    ) {}

    public function handle(RAGManager $rag): array
    {
        return $rag->ingestion()->ingestFromPath($this->path, $this->collection, $this->options);
    }
}
