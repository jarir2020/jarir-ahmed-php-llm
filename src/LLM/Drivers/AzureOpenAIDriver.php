<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Support\PendingRequest;

/**
 * Azure OpenAI Service. Same request/response shape as OpenAI, but auth uses an
 * `api-key` header and the URL is deployment + api-version based:
 *   {endpoint}/openai/deployments/{deployment}/chat/completions?api-version=...
 */
class AzureOpenAIDriver extends OpenAIDriver
{
    protected function defaultModel(): string
    {
        return $this->config['deployment'] ?? 'gpt-4o';
    }

    protected function pending(): PendingRequest
    {
        return Http::withHeaders([
            'api-key'      => $this->config['api_key'] ?? '',
            'Content-Type' => 'application/json',
        ])->timeout($this->config['timeout'] ?? 60);
    }

    protected function endpoint(string $path): string
    {
        $endpoint = rtrim($this->config['base_url'] ?? '', '/');
        $deployment = $this->config['deployment'] ?? $this->model;
        $version = $this->config['api_version'] ?? '2024-08-01-preview';

        return "{$endpoint}/openai/deployments/{$deployment}{$path}?api-version={$version}";
    }

    protected function payload(array $messages, array $options): array
    {
        // Azure infers the model from the deployment in the URL; don't send it.
        $payload = parent::payload($messages, $options);
        unset($payload['model']);

        return $payload;
    }
}
