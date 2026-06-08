<?php

namespace JarirAhmed\PhpLlm\Exceptions;

class LlmException extends \RuntimeException
{
    public static function invalidProvider(string $provider, string $type): self
    {
        return new self("Invalid {$type} provider: '{$provider}'.");
    }

    public static function unsupportedFeature(string $feature, string $provider): self
    {
        return new self("Feature '{$feature}' is not supported by provider '{$provider}'.");
    }

    public static function configurationError(string $message): self
    {
        return new self($message);
    }

    public static function apiError(string $provider, string $message): self
    {
        return new self("[{$provider}] API error: {$message}");
    }

    public static function embeddingFailed(string $message): self
    {
        return new self("Embedding failed: {$message}");
    }

    public static function vectorStoreError(string $message): self
    {
        return new self("Vector store error: {$message}");
    }
}
