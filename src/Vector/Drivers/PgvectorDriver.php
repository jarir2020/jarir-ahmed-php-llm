<?php

namespace JarirAhmed\PhpLlm\Vector\Drivers;

use PDO;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;
use JarirAhmed\PhpLlm\Support\Database;
use JarirAhmed\PhpLlm\Vector\Concerns\HasDefaultCollection;

/**
 * pgvector driver backed by a raw PDO (pgsql) connection. One table per
 * collection (prefixed), created on demand. Requires the `vector` extension:
 *   CREATE EXTENSION IF NOT EXISTS vector;
 */
class PgvectorDriver implements VectorDriver
{
    use HasDefaultCollection;

    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    protected function pdo(): PDO
    {
        return Database::connection($this->config['connection'] ?? 'pgsql');
    }

    public function createCollection(string $name, int $dimensions, array $options = []): array
    {
        $table = $this->tableName($name);
        $schema = $this->config['schema'] ?? 'public';
        $pdo = $this->pdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$schema}.{$table} (
            {$table}_id bigint GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            collection varchar(255) NOT NULL,
            external_id varchar(255) NOT NULL,
            embedding vector({$dimensions}) NOT NULL,
            metadata jsonb DEFAULT '{}'::jsonb,
            created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE INDEX IF NOT EXISTS {$table}_collection_idx ON {$schema}.{$table} (collection)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS {$table}_external_id_idx ON {$schema}.{$table} (external_id)");

        return ['status' => 'created', 'table' => $table];
    }

    public function deleteCollection(string $name): bool
    {
        $table = $this->tableName($name);
        $schema = $this->config['schema'] ?? 'public';

        $this->pdo()->exec("DROP TABLE IF EXISTS {$schema}.{$table}");

        return true;
    }

    public function listCollections(): array
    {
        $schema = $this->config['schema'] ?? 'public';

        $stmt = $this->pdo()->prepare(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = 'BASE TABLE'"
        );
        $stmt->execute([$schema]);

        return array_map(fn ($row) => $row['table_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function upsert(string $collection, array $records): array
    {
        $table = $this->tableName($collection);
        $schema = $this->config['schema'] ?? 'public';
        $pdo = $this->pdo();
        $ids = [];

        foreach ($records as $record) {
            $vector = '['.implode(',', $record['values'] ?? $record['vector']).']';
            $metadata = json_encode($record['metadata'] ?? $record['payload'] ?? []);

            $find = $pdo->prepare(
                "SELECT {$table}_id FROM {$schema}.{$table} WHERE external_id = ? AND collection = ? LIMIT 1"
            );
            $find->execute([$record['id'], $collection]);
            $existing = $find->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $upd = $pdo->prepare(
                    "UPDATE {$schema}.{$table} SET embedding = ?, metadata = ?::jsonb WHERE {$table}_id = ?"
                );
                $upd->execute([$vector, $metadata, $existing["{$table}_id"]]);
                $ids[] = $existing["{$table}_id"];
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO {$schema}.{$table} (collection, external_id, embedding, metadata)
                     VALUES (?, ?, ?, ?::jsonb) RETURNING {$table}_id"
                );
                $ins->execute([$collection, $record['id'], $vector, $metadata]);
                $ids[] = $ins->fetchColumn();
            }
        }

        return ['status' => 'upserted', 'ids' => $ids];
    }

    public function search(string $collection, array $vector, array $options = []): array
    {
        $table = $this->tableName($collection);
        $schema = $this->config['schema'] ?? 'public';
        $limit = (int) ($options['top_k'] ?? 10);
        $vectorStr = '['.implode(',', $vector).']';

        $stmt = $this->pdo()->prepare(
            "SELECT {$table}_id, external_id, metadata, embedding <-> ? AS distance
             FROM {$schema}.{$table}
             WHERE collection = ?
             ORDER BY embedding <-> ?
             LIMIT {$limit}"
        );
        $stmt->execute([$vectorStr, $collection, $vectorStr]);

        return array_map(fn ($row) => [
            'id' => $row["{$table}_id"],
            'external_id' => $row['external_id'],
            'metadata' => json_decode($row['metadata'] ?? '{}', true),
            'distance' => (float) $row['distance'],
            'score' => 1 - (float) $row['distance'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function delete(string $collection, string|array $id): bool
    {
        $table = $this->tableName($collection);
        $schema = $this->config['schema'] ?? 'public';
        $ids = is_array($id) ? $id : [$id];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare(
            "DELETE FROM {$schema}.{$table} WHERE collection = ? AND external_id IN ({$placeholders})"
        );
        $stmt->execute([$collection, ...$ids]);

        return true;
    }

    public function count(string $collection): int
    {
        $table = $this->tableName($collection);
        $schema = $this->config['schema'] ?? 'public';

        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*) FROM {$schema}.{$table} WHERE collection = ?"
        );
        $stmt->execute([$collection]);

        return (int) $stmt->fetchColumn();
    }

    protected function tableName(string $name): string
    {
        $prefix = $this->config['table_prefix'] ?? 'vector_';

        return $prefix.preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }
}
