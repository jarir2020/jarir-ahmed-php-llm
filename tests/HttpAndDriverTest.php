<?php

namespace JarirAhmed\PhpLlm\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;
use JarirAhmed\PhpLlm\LLM\Drivers\OpenAIDriver;
use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Support\PendingRequest;

class HttpAndDriverTest extends TestCase
{
    /** Install a Guzzle MockHandler so no real network is used. */
    protected function mock(array $responses): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        PendingRequest::useClient(new GuzzleClient(['handler' => $stack]));
    }

    protected function tearDown(): void
    {
        PendingRequest::useClient(null);
    }

    public function test_response_json_and_throw(): void
    {
        $this->mock([new Psr7Response(200, [], json_encode(['ok' => true]))]);

        $res = Http::withToken('t')->post('https://example.test/x', ['a' => 1]);

        $this->assertTrue($res->successful());
        $this->assertSame(true, $res->json('ok'));
    }

    public function test_throw_raises_on_error_status(): void
    {
        $this->mock([new Psr7Response(401, [], 'nope')]);

        $this->expectException(\JarirAhmed\PhpLlm\Exceptions\LlmException::class);

        Http::withToken('t')->post('https://example.test/x')->throw();
    }

    public function test_retry_recovers_after_transient_500(): void
    {
        $this->mock([
            new Psr7Response(500, [], 'boom'),
            new Psr7Response(200, [], json_encode(['ok' => 1])),
        ]);

        $res = Http::make()->retry(3, 0)->post('https://example.test/x');

        $this->assertTrue($res->successful());
        $this->assertSame(1, $res->json('ok'));
    }

    public function test_retry_recovers_after_429(): void
    {
        $this->mock([
            new Psr7Response(429, [], 'slow down'),
            new Psr7Response(200, [], json_encode(['ok' => 1])),
        ]);

        $res = Http::make()->retry(3, 0)->post('https://example.test/x');

        $this->assertTrue($res->successful());
    }

    public function test_openai_driver_parses_content_and_usage(): void
    {
        $payload = [
            'choices' => [['message' => ['content' => 'Hello there', 'role' => 'assistant']]],
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 3, 'total_tokens' => 15],
        ];
        $this->mock([new Psr7Response(200, [], json_encode($payload))]);

        $driver = new OpenAIDriver(['api_key' => 'sk', 'model' => 'gpt-4o']);
        $result = $driver->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertSame('Hello there', $result['content']);
        $this->assertSame(15, $result['raw']['usage']['total_tokens']);
    }

    public function test_deepseek_driver_hits_deepseek_base_url(): void
    {
        $captured = null;
        $stack = HandlerStack::create(new MockHandler([new Psr7Response(200, [], json_encode([
            'choices' => [['message' => ['content' => 'ok']]],
        ]))]));
        $stack->push(\GuzzleHttp\Middleware::tap(function ($request) use (&$captured) {
            $captured = (string) $request->getUri();
        }));
        PendingRequest::useClient(new GuzzleClient(['handler' => $stack]));

        (new \JarirAhmed\PhpLlm\LLM\Drivers\DeepSeekDriver(['api_key' => 'x']))
            ->chat([['role' => 'user', 'content' => 'hi']]);

        $this->assertStringContainsString('api.deepseek.com', (string) $captured);
    }
}
