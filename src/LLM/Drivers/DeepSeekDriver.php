<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

/**
 * DeepSeek is OpenAI-API-compatible; only the default model and base URL differ.
 */
class DeepSeekDriver extends OpenAIDriver
{
    protected function defaultModel(): string
    {
        return 'deepseek-chat';
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.deepseek.com/v1', '/');
    }
}
