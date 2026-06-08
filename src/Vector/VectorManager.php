<?php

namespace JarirAhmed\PhpLlm\Vector;

use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

class VectorManager
{
    protected array $drivers = [];

    public function __construct(
        protected $app = null,
    ) {}

    public function driver(?string $name = null): VectorDriver
    {
        $name ??= \JarirAhmed\PhpLlm\Support\Config::get('ai.defaults.vector', 'qdrant');

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->resolve($name);
        }

        return $this->drivers[$name];
    }

    public function extend(string $name, callable $resolver): static
    {
        $this->drivers[$name] = $resolver($this->app);

        return $this;
    }

    protected function resolve(string $name): VectorDriver
    {
        $config = \JarirAhmed\PhpLlm\Support\Config::get("ai.vector.{$name}");

        if ($config === null) {
            throw LlmException::invalidProvider($name, 'Vector');
        }

        return match ($config['driver']) {
            'qdrant' => new Drivers\QdrantDriver($config),
            'pinecone' => new Drivers\PineconeDriver($config),
            'pgvector' => new Drivers\PgvectorDriver($config),
            'weaviate' => new Drivers\WeaviateDriver($config),
            'milvus' => new Drivers\MilvusDriver($config),
            'chroma' => new Drivers\ChromaDriver($config),
            default => throw LlmException::invalidProvider($name, 'Vector'),
        };
    }
}
