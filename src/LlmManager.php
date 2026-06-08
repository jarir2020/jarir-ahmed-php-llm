<?php

namespace JarirAhmed\PhpLlm;

use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Contracts\ImageDriver;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;
use JarirAhmed\PhpLlm\Contracts\SpeechDriver;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;

class LlmManager
{
    protected array $llmDrivers = [];

    protected array $embeddingDrivers = [];

    protected array $vectorDrivers = [];

    protected array $imageDrivers = [];

    protected array $speechDrivers = [];

    public function __construct(
        public LLM\LLMManager $llm,
        public Embedding\EmbeddingManager $embedding,
        public Vector\VectorManager $vector,
        public Image\ImageManager $image,
        public Speech\SpeechManager $speech,
        public RAG\RAGManager $rag,
        public Memory\MemoryManager $memory,
    ) {}

    public function llm(?string $provider = null): LLMDriver
    {
        return $this->llm->driver($provider);
    }

    public function embedding(?string $provider = null): EmbeddingDriver
    {
        return $this->embedding->driver($provider);
    }

    public function vector(?string $provider = null): VectorDriver
    {
        return $this->vector->driver($provider);
    }

    public function image(?string $provider = null): ImageDriver
    {
        return $this->image->driver($provider);
    }

    public function speech(?string $provider = null): SpeechDriver
    {
        return $this->speech->driver($provider);
    }

    public function rag(): RAG\RAGManager
    {
        return $this->rag;
    }

    public function memory(?string $driver = null): Memory\MemoryManager
    {
        return $this->memory;
    }

    public function agent(?string $provider = null): Agent\Agent
    {
        return new Agent\Agent(
            $this->llm->driver($provider),
            $this->memory,
        );
    }

    public function fake(): Testing\LlmFake
    {
        return new Testing\LlmFake;
    }
}
