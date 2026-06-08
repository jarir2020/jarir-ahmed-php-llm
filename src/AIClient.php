<?php

namespace JarirAhmed\PhpLlm;

use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Contracts\ImageDriver;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;
use JarirAhmed\PhpLlm\Contracts\SpeechDriver;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;

/**
 * The hub returned by Client::create(). Gives you fresh request builders and
 * direct access to every capability manager.
 *
 *   $ai = Client::create([...]);
 *   $ai->chat()->message('Hi')->ask();          // fluent generation
 *   $ai->ask('Hi');                              // one-shot shortcut
 *   $ai->vector('qdrant')->search(...);          // capability managers
 */
class AIClient
{
    public function __construct(
        protected LlmManager $manager,
    ) {}

    /** Start a fresh chat/generation builder. */
    public function chat(): Generation
    {
        return new Generation($this->manager);
    }

    /** Alias of chat() that reads better for non-conversational generation. */
    public function generate(): Generation
    {
        return new Generation($this->manager);
    }

    /** One-shot prompt -> string. */
    public function ask(string $prompt, ?string $provider = null): string
    {
        $gen = $this->chat()->message($prompt);

        if ($provider !== null) {
            $gen->provider($provider);
        }

        return $gen->ask();
    }

    /** One-shot embedding of a single string. */
    public function embed(string $text, ?string $provider = null): array
    {
        return $this->embedding($provider)->embed($text);
    }

    /** A multi-turn conversation that persists through a memory driver. */
    public function conversation(string $sessionId, ?string $provider = null, ?string $memoryDriver = null): Conversation
    {
        return new Conversation($this->manager, $sessionId, $provider, $memoryDriver);
    }

    // ---- capability managers / drivers ----

    public function llm(?string $provider = null): LLMDriver
    {
        return $this->manager->llm($provider);
    }

    public function embedding(?string $provider = null): EmbeddingDriver
    {
        return $this->manager->embedding($provider);
    }

    public function vector(?string $provider = null): VectorDriver
    {
        return $this->manager->vector($provider);
    }

    public function image(?string $provider = null): ImageDriver
    {
        return $this->manager->image($provider);
    }

    public function speech(?string $provider = null): SpeechDriver
    {
        return $this->manager->speech($provider);
    }

    public function memory(?string $driver = null): Memory\MemoryManager
    {
        return $this->manager->memory();
    }

    public function rag(): RAG\RAGManager
    {
        return $this->manager->rag();
    }

    public function agent(?string $provider = null): Agent\Agent
    {
        return $this->manager->agent($provider);
    }

    /** The underlying manager graph (escape hatch / advanced use). */
    public function manager(): LlmManager
    {
        return $this->manager;
    }

    /** Swap all common drivers for an offline fake. Returns the fake for assertions. */
    public function fake(): Testing\LlmFake
    {
        $fake = new Testing\LlmFake;

        foreach (['openai', 'anthropic', 'gemini', 'ollama', 'grok', 'mistral', 'cohere', 'deepseek', 'groq', 'openrouter', 'azure'] as $p) {
            $this->manager->llm->extend($p, fn () => $fake);
        }

        foreach (['openai', 'ollama', 'gemini', 'mistral', 'cohere'] as $p) {
            $this->manager->embedding->extend($p, fn () => $fake);
        }

        return $fake;
    }
}
