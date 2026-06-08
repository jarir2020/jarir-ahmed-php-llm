<?php

namespace JarirAhmed\PhpLlm\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use JarirAhmed\PhpLlm\Client;
use JarirAhmed\PhpLlm\Generation;
use JarirAhmed\PhpLlm\Support\Config;
use JarirAhmed\PhpLlm\Support\Database;
use JarirAhmed\PhpLlm\LLM\Drivers\DeepSeekDriver;
use JarirAhmed\PhpLlm\LLM\Drivers\GroqDriver;
use JarirAhmed\PhpLlm\LLM\Drivers\OpenRouterDriver;
use JarirAhmed\PhpLlm\LLM\Drivers\AzureOpenAIDriver;

class IntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::flush();
        Database::flush();
    }

    protected function client()
    {
        return Client::create([
            'defaults' => ['llm' => 'openai', 'embedding' => 'openai'],
            'llm' => ['openai' => ['api_key' => 'test']],
        ]);
    }

    public function test_fluent_generation_returns_usage_and_cost(): void
    {
        $ai = $this->client();
        $ai->fake();

        $res = $ai->chat()->system('be terse')->message('hello')->chat();

        $this->assertSame('fake response', $res['content']);
        $this->assertArrayHasKey('usage', $res);
        $this->assertArrayHasKey('cost', $res);
        $this->assertSame('fake response', $ai->ask('hi'));
    }

    public function test_each_chat_is_isolated(): void
    {
        $ai = $this->client();
        $ai->fake();

        $a = $ai->chat()->message('first');
        $b = $ai->chat()->message('second');

        // distinct builders => no shared state leak
        $this->assertNotSame($a, $b);
        $this->assertInstanceOf(Generation::class, $a);
    }

    public function test_provider_resolution_for_new_drivers(): void
    {
        $ai = Client::create(['llm' => [
            'deepseek'   => ['api_key' => 'x'],
            'groq'       => ['api_key' => 'x'],
            'openrouter' => ['api_key' => 'x'],
            'azure'      => ['api_key' => 'x', 'base_url' => 'https://r.openai.azure.com'],
        ]]);

        $this->assertInstanceOf(DeepSeekDriver::class, $ai->llm('deepseek'));
        $this->assertInstanceOf(GroqDriver::class, $ai->llm('groq'));
        $this->assertInstanceOf(OpenRouterDriver::class, $ai->llm('openrouter'));
        $this->assertInstanceOf(AzureOpenAIDriver::class, $ai->llm('azure'));
    }

    public function test_conversation_persists_history_via_memory(): void
    {
        $ai = $this->client();
        $ai->fake();

        $chat = $ai->conversation('user-x', 'openai', 'conversation');
        $chat->say('My name is Sam.');
        $chat->say('What is my name?');

        // 2 turns * (user + assistant) = 4 messages stored
        $this->assertCount(4, $chat->history());
        $this->assertGreaterThanOrEqual(0.0, $chat->totalCost());
    }

    public function test_persistent_memory_self_heals_sqlite_table(): void
    {
        $ai = $this->client();
        $ai->fake();
        Client::useDatabase('default', new PDO('sqlite::memory:'));

        $mem = $ai->memory()->driver('persistent');
        $mem->add('s1', ['role' => 'user', 'content' => 'hi']);
        $mem->add('s1', ['role' => 'assistant', 'content' => 'yo']);

        $history = $mem->get('s1');
        $this->assertCount(2, $history);
        $this->assertSame('hi', $history[0]['content']);   // chronological order
        $this->assertSame('yo', $history[1]['content']);
    }

    public function test_structured_json_parser_is_tolerant(): void
    {
        $this->assertSame(['a' => 1], Generation::parseJson('{"a":1}'));
        $this->assertSame(['a' => 1], Generation::parseJson("```json\n{\"a\":1}\n```"));
        $this->assertSame(['x' => 'y'], Generation::parseJson('noise {"x":"y"} trailing'));
        $this->assertSame([], Generation::parseJson('not json at all'));
    }
}
