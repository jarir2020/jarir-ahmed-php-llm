<?php

namespace JarirAhmed\PhpLlm\Support;

/**
 * Normalized token-usage value object. Knows how to read the differently
 * shaped usage blocks returned by each provider's raw payload, and can
 * accumulate across calls.
 */
class Usage
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
    ) {
        if ($this->totalTokens === 0) {
            $this->totalTokens = $this->promptTokens + $this->completionTokens;
        }
    }

    /** Build a Usage from a driver's raw provider payload (best-effort across providers). */
    public static function fromRaw(array $raw): self
    {
        // OpenAI / Groq / DeepSeek / OpenRouter / Azure / Mistral
        if (isset($raw['usage']['prompt_tokens']) || isset($raw['usage']['completion_tokens'])) {
            $u = $raw['usage'];
            return new self(
                (int) ($u['prompt_tokens'] ?? 0),
                (int) ($u['completion_tokens'] ?? 0),
                (int) ($u['total_tokens'] ?? 0),
            );
        }

        // Anthropic
        if (isset($raw['usage']['input_tokens']) || isset($raw['usage']['output_tokens'])) {
            $u = $raw['usage'];
            return new self(
                (int) ($u['input_tokens'] ?? 0),
                (int) ($u['output_tokens'] ?? 0),
            );
        }

        // Gemini
        if (isset($raw['usageMetadata'])) {
            $u = $raw['usageMetadata'];
            return new self(
                (int) ($u['promptTokenCount'] ?? 0),
                (int) ($u['candidatesTokenCount'] ?? 0),
                (int) ($u['totalTokenCount'] ?? 0),
            );
        }

        // Cohere
        if (isset($raw['meta']['billed_units'])) {
            $u = $raw['meta']['billed_units'];
            return new self(
                (int) ($u['input_tokens'] ?? 0),
                (int) ($u['output_tokens'] ?? 0),
            );
        }

        return new self;
    }

    public function add(Usage $other): self
    {
        return new self(
            $this->promptTokens + $other->promptTokens,
            $this->completionTokens + $other->completionTokens,
            $this->totalTokens + $other->totalTokens,
        );
    }

    public function toArray(): array
    {
        return [
            'prompt_tokens'     => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens'      => $this->totalTokens,
        ];
    }
}
