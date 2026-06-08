<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Tiny framework-agnostic configuration store with dot-notation access.
 *
 * Replaces Laravel's global config() helper. The whole package config lives
 * under the "ai" root (e.g. "ai.llm.openai.api_key") exactly as the original
 * Laravel package expected, so driver/manager call sites are unchanged.
 *
 * Populate it once at bootstrap (Client::create() does this for you):
 *   Config::load(['ai' => [...]]);
 */
class Config
{
    /** @var array<string,mixed> */
    protected static array $items = [];

    /** Replace the entire config tree. */
    public static function load(array $items): void
    {
        static::$items = $items;
    }

    /** Merge a tree into the existing config (deep merge). */
    public static function merge(array $items): void
    {
        static::$items = static::deepMerge(static::$items, $items);
    }

    /** Get a value by dot path, with a default fallback. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$items)) {
            return static::$items[$key];
        }

        $segment = static::$items;

        foreach (explode('.', $key) as $part) {
            if (is_array($segment) && array_key_exists($part, $segment)) {
                $segment = $segment[$part];
            } else {
                return $default;
            }
        }

        return $segment;
    }

    /** Set a value by dot path. */
    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &static::$items;

        foreach ($segments as $i => $part) {
            if ($i === count($segments) - 1) {
                $ref[$part] = $value;
                return;
            }

            if (! isset($ref[$part]) || ! is_array($ref[$part])) {
                $ref[$part] = [];
            }

            $ref = &$ref[$part];
        }
    }

    /** True if a dot path resolves to a non-null value. */
    public static function has(string $key): bool
    {
        return static::get($key, null) !== null;
    }

    /** Return the full tree (mainly for debugging/tests). */
    public static function all(): array
    {
        return static::$items;
    }

    public static function flush(): void
    {
        static::$items = [];
    }

    protected static function deepMerge(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && static::isAssoc($value)) {
                $base[$key] = static::deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    protected static function isAssoc(array $arr): bool
    {
        return $arr !== [] && array_keys($arr) !== range(0, count($arr) - 1);
    }
}
