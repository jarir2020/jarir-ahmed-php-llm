<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

/**
 * OpenRouter is OpenAI-API-compatible and routes to many upstream models.
 * Optional HTTP-Referer / X-Title headers improve app ranking on their site.
 */
class OpenRouterDriver extends OpenAIDriver
{
    protected function defaultModel(): string
    {
        return 'openai/gpt-4o';
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://openrouter.ai/api/v1', '/');
    }

    protected function extraHeaders(): array
    {
        $headers = [];

        if (! empty($this->config['referer'])) {
            $headers['HTTP-Referer'] = $this->config['referer'];
        }

        if (! empty($this->config['title'])) {
            $headers['X-Title'] = $this->config['title'];
        }

        return $headers;
    }
}
