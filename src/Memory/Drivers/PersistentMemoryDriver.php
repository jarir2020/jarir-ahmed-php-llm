<?php

namespace JarirAhmed\PhpLlm\Memory\Drivers;

use PDO;
use JarirAhmed\PhpLlm\Contracts\MemoryDriver;
use JarirAhmed\PhpLlm\Support\Database;

/**
 * PDO-backed long-term memory. Self-healing: the backing table is created
 * automatically on first use if it does not exist (works on sqlite/mysql/pgsql).
 */
class PersistentMemoryDriver implements MemoryDriver
{
    protected string $table;

    protected int $limit;

    protected string $connectionName;

    protected bool $ensured = false;

    public function __construct(
        protected array $config = [],
    ) {
        $this->table = $config['table'] ?? 'ai_memories';
        $this->limit = $config['limit'] ?? 100;
        $this->connectionName = $config['connection'] ?? 'default';
    }

    public function add(string $sessionId, array $message): void
    {
        $pdo = $this->pdo();

        $stmt = $pdo->prepare(
            "INSERT INTO {$this->table} (session_id, role, content, created_at) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $sessionId,
            $message['role'] ?? 'user',
            $message['content'] ?? '',
            date('Y-m-d H:i:s'),
        ]);
    }

    public function get(string $sessionId, int $limit = 10): array
    {
        $pdo = $this->pdo();
        $take = (int) $this->limit;

        $stmt = $pdo->prepare(
            "SELECT role, content FROM {$this->table} WHERE session_id = ? ORDER BY id DESC LIMIT {$take}"
        );
        $stmt->execute([$sessionId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stored newest-first; return chronological order.
        return array_reverse(array_map(fn ($row) => [
            'role' => $row['role'],
            'content' => $row['content'],
        ], $rows));
    }

    public function clear(string $sessionId): void
    {
        $this->delete($sessionId);
    }

    public function delete(string $sessionId): void
    {
        $stmt = $this->pdo()->prepare("DELETE FROM {$this->table} WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }

    protected function pdo(): PDO
    {
        $pdo = Database::connection($this->connectionName);

        if (! $this->ensured) {
            $this->ensureTable($pdo);
            $this->ensured = true;
        }

        return $pdo;
    }

    protected function ensureTable(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $autoId = match ($driver) {
            'mysql' => 'BIGINT AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => 'BIGSERIAL PRIMARY KEY',
            default => 'INTEGER PRIMARY KEY AUTOINCREMENT', // sqlite
        };

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$this->table} (
                id {$autoId},
                session_id VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL,
                content TEXT NOT NULL,
                created_at VARCHAR(32) NULL
            )"
        );

        $pdo->exec("CREATE INDEX IF NOT EXISTS {$this->table}_session_idx ON {$this->table} (session_id)");
    }
}
