<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface ImageDriver
{
    public function generate(string $prompt, array $options = []): array;

    public function edit(string $image, string $prompt, array $options = []): array;

    public function variations(string $image, array $options = []): array;

    public function setModel(string $model): static;

    public function getModel(): string;
}
