<?php

namespace JarirAhmed\PhpLlm;

use JarirAhmed\PhpLlm\Pricing\Pricing;
use JarirAhmed\PhpLlm\Support\Usage;
use JarirAhmed\PhpLlm\Events\MessageSending;
use JarirAhmed\PhpLlm\Events\MessageReceived;
use JarirAhmed\PhpLlm\Support\EventDispatcher;

/**
 * A single, self-contained chat/embed request builder.
 *
 * Each call to AIClient::chat() returns a fresh Generation, so request state
 * never leaks between calls (unlike the original shared-singleton builder).
 *
 *   $ai->chat()->system('Be terse.')->message('Capital of France?')->ask();   // "Paris."
 *   $ai->chat()->message('...')->chat();        // full array incl. usage + cost
 *   $ai->chat()->message('...')->structured($schema);  // decoded JSON
 */
class Generation
{
    protected ?string $provider = null;

    protected ?string $model = null;

    protected array $messages = [];

    protected ?string $text = null;

    protected array $options = [];

    protected array $tools = [];

    public function __construct(
        protected LlmManager $manager,
    ) {}

    public function provider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function model(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function system(string $content): static
    {
        array_unshift($this->messages, ['role' => 'system', 'content' => $content]);

        return $this;
    }

    public function message(string|array $message): static
    {
        $this->messages[] = is_string($message)
            ? ['role' => 'user', 'content' => $message]
            : $message;

        return $this;
    }

    public function messages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    public function text(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function options(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function temperature(float $value): static
    {
        $this->options['temperature'] = $value;

        return $this;
    }

    public function maxTokens(int $value): static
    {
        $this->options['max_tokens'] = $value;

        return $this;
    }

    public function tools(array $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    /** Execute a chat completion. Returns content/role/raw plus usage + cost. */
    public function chat(): array
    {
        $driver = $this->resolveLlm();
        $provider = $driver->getProvider();
        $model = $driver->getModel();

        EventDispatcher::dispatch(new MessageSending($provider, $model, $this->messages, $this->options));

        $start = microtime(true);
        $response = $this->tools !== []
            ? $driver->tools($this->messages, $this->tools, $this->options)
            : $driver->chat($this->messages, $this->options);
        $latency = microtime(true) - $start;

        $usage = Usage::fromRaw($response['raw'] ?? $response);
        $response['usage'] = $usage->toArray();
        $response['cost'] = Pricing::estimate($model, $usage);
        $response['latency'] = round($latency, 4);

        EventDispatcher::dispatch(new MessageReceived($provider, $model, $response, $latency));

        return $response;
    }

    /** Convenience: run chat() and return just the assistant text. */
    public function ask(): string
    {
        return $this->chat()['content'] ?? '';
    }

    /** Stream tokens as they arrive. Yields ['content' => ..., 'role' => ...]. */
    public function stream(): iterable
    {
        $driver = $this->resolveLlm();

        return $driver->stream($this->messages, $this->options);
    }

    /**
     * Structured output. Pass a JSON schema; returns the decoded associative
     * array. Uses native JSON-schema response_format on OpenAI-compatible
     * providers, and instruction+parse fallback elsewhere.
     */
    public function structured(array $schema, string $name = 'response'): array
    {
        $driver = $this->resolveLlm();
        $provider = $driver->getProvider();

        // Native strict JSON-schema is most reliable on OpenAI + Azure.
        if (in_array($provider, ['openai', 'azure'], true)) {
            $this->options['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => ['name' => $name, 'schema' => $schema, 'strict' => true],
            ];
        } elseif (in_array($provider, ['deepseek', 'groq', 'openrouter'], true)) {
            // These OpenAI-compatible providers support json_object mode (not
            // always json_schema). json_object requires "json" in the prompt.
            $this->options['response_format'] = ['type' => 'json_object'];
            $this->system($this->schemaInstruction($schema));
        } else {
            $this->system($this->schemaInstruction($schema));
        }

        $content = $this->chat()['content'] ?? '';

        return static::parseJson($content);
    }

    protected function schemaInstruction(array $schema): string
    {
        return 'You must respond with ONLY a valid JSON object matching this JSON schema. '
            .'No prose, no markdown, no code fences. Schema: '.json_encode($schema);
    }

    public function usage(): Usage
    {
        return Usage::fromRaw($this->chat()['raw'] ?? []);
    }

    protected function resolveLlm()
    {
        $driver = $this->manager->llm($this->provider);

        if ($this->model !== null) {
            $driver->setModel($this->model);
        }

        return $driver;
    }

    /** Tolerantly decode a JSON object that may be wrapped in ``` fences. */
    public static function parseJson(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?|```$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            // last resort: grab the outermost {...}
            if (preg_match('/\{.*\}/s', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        return is_array($decoded) ? $decoded : [];
    }
}
