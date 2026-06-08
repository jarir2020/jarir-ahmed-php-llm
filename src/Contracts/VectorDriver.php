<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface VectorDriver
{
    public function createCollection(string $name, int $dimensions, array $options = []): array;

    public function deleteCollection(string $name): bool;

    public function listCollections(): array;

    public function upsert(string $collection, array $records): array;

    public function search(string $collection, array $vector, array $options = []): array;

    public function delete(string $collection, string|array $id): bool;

    public function count(string $collection): int;
}
