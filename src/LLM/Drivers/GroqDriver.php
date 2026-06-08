<?php

namespace JarirAhmed\PhpLlm\LLM\Drivers;

/**
 * Groq's LPU API is OpenAI-API-compatible.
 */
class GroqDriver extends OpenAIDriver
{
    protected function defaultModel(): string
    {
        return 'llama-3.3-70b-versatile';
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.groq.com/openai/v1', '/');
    }
}
