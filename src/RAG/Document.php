<?php

namespace JarirAhmed\PhpLlm\RAG;

class Document
{
    public function __construct(
        protected string $content,
        protected array $metadata = [],
        protected ?string $path = null,
    ) {}

    public function content(): string
    {
        return $this->content;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function path(): ?string
    {
        return $this->path;
    }
}
