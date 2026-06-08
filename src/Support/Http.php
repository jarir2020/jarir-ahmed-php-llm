<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Framework-agnostic HTTP entry point.
 *
 * Provides the small fluent subset of Laravel's Http facade that the drivers
 * rely on (withToken / withHeaders / timeout / attach / get/post/put/delete),
 * backed by Guzzle. All driver code that used Illuminate\Support\Facades\Http
 * works unchanged against this class.
 */
class Http
{
    public static function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        return (new PendingRequest)->withToken($token, $type);
    }

    public static function withHeaders(array $headers): PendingRequest
    {
        return (new PendingRequest)->withHeaders($headers);
    }

    public static function timeout(int $seconds): PendingRequest
    {
        return (new PendingRequest)->timeout($seconds);
    }

    public static function attach(string $name, mixed $contents, ?string $filename = null): PendingRequest
    {
        return (new PendingRequest)->attach($name, $contents, $filename);
    }

    public static function retry(int $times, int $sleepMilliseconds = 0): PendingRequest
    {
        return (new PendingRequest)->retry($times, $sleepMilliseconds);
    }

    public static function baseUrl(string $url): PendingRequest
    {
        return (new PendingRequest)->baseUrl($url);
    }

    /** Escape hatch for code that wants a clean builder. */
    public static function make(): PendingRequest
    {
        return new PendingRequest;
    }
}
