<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Pluggable key/value session store for the session memory driver.
 *
 * Defaults to PHP's native $_SESSION (auto-starting a session if the SAPI
 * allows it). Outside a web request, or in tests, it transparently falls back
 * to a static in-memory array, so the session driver never fatals on CLI.
 *
 * Swap in any PSR-16-ish store via SessionStore::use($store) where $store
 * implements get(string $key, mixed $default): mixed, put(string $key, mixed $value): void
 * and forget(string $key): void.
 */
class SessionStore
{
    protected static ?object $custom = null;

    /** @var array<string,mixed> */
    protected static array $fallback = [];

    public static function use(?object $store): void
    {
        static::$custom = $store;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (static::$custom !== null) {
            return static::$custom->get($key, $default);
        }

        if (static::nativeAvailable()) {
            return $_SESSION[$key] ?? $default;
        }

        return static::$fallback[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        if (static::$custom !== null) {
            static::$custom->put($key, $value);
            return;
        }

        if (static::nativeAvailable()) {
            $_SESSION[$key] = $value;
            return;
        }

        static::$fallback[$key] = $value;
    }

    public static function forget(string $key): void
    {
        if (static::$custom !== null) {
            static::$custom->forget($key);
            return;
        }

        if (static::nativeAvailable()) {
            unset($_SESSION[$key]);
            return;
        }

        unset(static::$fallback[$key]);
    }

    protected static function nativeAvailable(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
            @session_start();
        }

        return session_status() === PHP_SESSION_ACTIVE;
    }
}
