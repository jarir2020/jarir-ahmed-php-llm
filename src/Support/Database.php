<?php

namespace JarirAhmed\PhpLlm\Support;

use PDO;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

/**
 * Minimal PDO connection registry. Replaces Laravel's DB facade for the
 * persistent-memory and pgvector drivers.
 *
 * Register connections one of two ways:
 *   Database::extend('pgsql', $pdoInstance);                 // bring your own PDO
 *   // or define DSN credentials in config under ai.database.connections.{name}:
 *   // ['dsn' => 'pgsql:host=...;dbname=...', 'username' => '...', 'password' => '...']
 */
class Database
{
    /** @var array<string,PDO> */
    protected static array $connections = [];

    /** Register a ready-made PDO instance under a connection name. */
    public static function extend(string $name, PDO $pdo): void
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        static::$connections[$name] = $pdo;
    }

    /** Resolve a PDO connection by name, lazily building it from config if needed. */
    public static function connection(string $name): PDO
    {
        if (isset(static::$connections[$name])) {
            return static::$connections[$name];
        }

        $config = Config::get("ai.database.connections.{$name}");

        if (! is_array($config) || empty($config['dsn'])) {
            throw LlmException::configurationError(
                "No database connection '{$name}'. Register one with Database::extend('{$name}', \$pdo) ".
                "or define ai.database.connections.{$name}.dsn in config."
            );
        }

        $pdo = new PDO(
            $config['dsn'],
            $config['username'] ?? null,
            $config['password'] ?? null,
            $config['options'] ?? [],
        );

        static::extend($name, $pdo);

        return static::$connections[$name];
    }

    public static function flush(): void
    {
        static::$connections = [];
    }
}
