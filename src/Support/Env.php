<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Environment variable reader with type coercion. Replaces Laravel's env()
 * helper so the default config file works outside Laravel. Reads from $_ENV,
 * $_SERVER and getenv() in that order. Pairs well with vlucas/phpdotenv if you
 * load a .env yourself.
 */
class Env
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}
