<?php

namespace JarirAhmed\PhpLlm\Tests;

use PHPUnit\Framework\TestCase;
use JarirAhmed\PhpLlm\Support\Config;
use JarirAhmed\PhpLlm\Support\Usage;
use JarirAhmed\PhpLlm\Pricing\Pricing;
use JarirAhmed\PhpLlm\Support\EventDispatcher;

class SupportTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::flush();
        EventDispatcher::flush();
    }

    public function test_config_dot_access_and_defaults(): void
    {
        Config::load(['ai' => ['llm' => ['openai' => ['api_key' => 'sk-1']]]]);

        $this->assertSame('sk-1', Config::get('ai.llm.openai.api_key'));
        $this->assertSame('fallback', Config::get('ai.llm.missing.key', 'fallback'));
        $this->assertTrue(Config::has('ai.llm.openai'));
        $this->assertFalse(Config::has('ai.nope'));
    }

    public function test_config_deep_merge_preserves_siblings(): void
    {
        Config::load(['ai' => ['llm' => ['openai' => ['api_key' => 'a', 'model' => 'gpt-4o']]]]);
        Config::merge(['ai' => ['llm' => ['openai' => ['api_key' => 'b']]]]);

        $this->assertSame('b', Config::get('ai.llm.openai.api_key'));
        $this->assertSame('gpt-4o', Config::get('ai.llm.openai.model')); // sibling kept
    }

    public function test_usage_normalizes_each_provider_shape(): void
    {
        $openai = Usage::fromRaw(['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15]]);
        $this->assertSame(15, $openai->totalTokens);

        $anthropic = Usage::fromRaw(['usage' => ['input_tokens' => 8, 'output_tokens' => 2]]);
        $this->assertSame(10, $anthropic->totalTokens);

        $gemini = Usage::fromRaw(['usageMetadata' => ['promptTokenCount' => 3, 'candidatesTokenCount' => 7, 'totalTokenCount' => 10]]);
        $this->assertSame(10, $gemini->totalTokens);
    }

    public function test_pricing_estimate_and_prefix_match(): void
    {
        $cost = Pricing::estimate('gpt-4o', new Usage(1_000_000, 1_000_000));
        $this->assertEqualsWithDelta(12.5, $cost, 0.0001);

        // dated id resolves to the gpt-4o prefix
        $dated = Pricing::estimate('gpt-4o-2024-08-06', new Usage(1_000_000, 0));
        $this->assertEqualsWithDelta(2.5, $dated, 0.0001);

        $this->assertSame(0.0, Pricing::estimate('totally-unknown-model', new Usage(1000, 1000)));
    }

    public function test_event_dispatcher_invokes_listeners(): void
    {
        $seen = [];
        EventDispatcher::listen('*', function ($e) use (&$seen) {
            $seen[] = $e::class;
        });

        EventDispatcher::dispatch(new \JarirAhmed\PhpLlm\Events\VectorStored('qdrant', 'docs', 3));

        $this->assertContains(\JarirAhmed\PhpLlm\Events\VectorStored::class, $seen);
    }
}
