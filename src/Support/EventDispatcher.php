<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Dead-simple synchronous event dispatcher (PSR-14 friendly in spirit).
 *
 * Lets observability features (cost/token tracking, logging) hook into the
 * lifecycle without coupling to any framework's event bus. Listeners are keyed
 * by event class name; a wildcard '*' listener receives every event.
 */
class EventDispatcher
{
    /** @var array<string,callable[]> */
    protected static array $listeners = [];

    /** Register a listener for an event class (or '*' for all events). */
    public static function listen(string $event, callable $listener): void
    {
        static::$listeners[$event][] = $listener;
    }

    /** Dispatch an event object to its listeners. Returns the same object. */
    public static function dispatch(object $event): object
    {
        $class = $event::class;

        foreach (static::$listeners[$class] ?? [] as $listener) {
            $listener($event);
        }

        foreach (static::$listeners['*'] ?? [] as $listener) {
            $listener($event);
        }

        return $event;
    }

    public static function forget(string $event): void
    {
        unset(static::$listeners[$event]);
    }

    public static function flush(): void
    {
        static::$listeners = [];
    }
}
