# jarir-ahmed/php-llm

A **framework-agnostic** unified AI/LLM toolkit for PHP — one fluent API over many LLM, embedding and vector-database providers, plus RAG, agents, memory, **cost/token tracking** and **structured output**.

> This package merges and extends [`manik/neuro`](https://github.com/dev-manik-mia/neuro) and [`manik/cortex`](https://github.com/dev-manik-mia/cortex) (which are the same Laravel package under two names) into a single library with **zero framework coupling** — no `laravel/framework` dependency. Works in Laravel, Symfony, WordPress, Slim, or plain PHP.

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net) [![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

---

## What changed vs. neuro/cortex

| Area | neuro / cortex | php-llm |
|------|----------------|---------|
| Framework | Requires `laravel/framework` | **None** — pure PHP + Guzzle |
| Bootstrap | ServiceProvider + Facade | `Client::create()` factory |
| HTTP | `Illuminate\Support\Facades\Http` | Guzzle-backed `Support\Http` (same fluent API) + retry + true SSE streaming |
| Config | Laravel `config()` | `Support\Config` (dot-access, env fallback) |
| DB (memory/pgvector) | `Illuminate\...\DB` | raw `PDO` with **self-healing** tables |
| Session memory | Laravel session | `SessionStore` ($_SESSION / in-memory / pluggable) |
| Cost tracking | flags only | **`Pricing` + `Usage`**: real USD estimates per call |
| Structured output | — | **JSON-schema `->structured()`** (native or instruct+parse) |
| Providers | 7 LLMs | **+ DeepSeek, Groq, Azure OpenAI, OpenRouter** |
| Fluent client | shared, stateful builder | **fresh `Generation` per call** + `ask()`, `conversation()` |

## Install

```bash
composer require jarir-ahmed/php-llm
```

Requires PHP 8.3+. `ext-pdo` is only needed for the persistent-memory and pgvector drivers.

## Quick start

```php
use JarirAhmed\PhpLlm\Client;

$ai = Client::create([
    'defaults' => ['llm' => 'openai'],
    'llm' => ['openai' => ['api_key' => 'sk-...']],
]);

// one-shot
echo $ai->ask('Explain Laravel in one line.');

// fluent — a fresh, isolated request each time
$res = $ai->chat()
    ->provider('openai')->model('gpt-4o')
    ->system('You are terse.')
    ->message('Capital of France?')
    ->chat();

echo $res['content'];                 // "Paris."
echo $res['usage']['total_tokens'];   // 27
echo '$' . $res['cost'];              // estimated USD cost
```

Any value you don't override falls back to the packaged defaults, which read from environment variables (e.g. `OPENAI_API_KEY`). So in production you can often just do `Client::create()`.

## LLM providers

| Provider | Key | Notes |
|----------|-----|-------|
| OpenAI | `openai` | |
| Anthropic | `anthropic` | |
| Google Gemini | `gemini` | |
| Ollama | `ollama` | local, no key |
| xAI Grok | `grok` | |
| Mistral | `mistral` | |
| Cohere | `cohere` | |
| **DeepSeek** | `deepseek` | new |
| **Groq** | `groq` | new |
| **Azure OpenAI** | `azure` | new — set `base_url` (endpoint) + `deployment` |
| **OpenRouter** | `openrouter` | new — optional `referer`/`title` headers |

```php
$ai = Client::create([
    'defaults' => ['llm' => 'groq'],
    'llm' => ['groq' => ['api_key' => getenv('GROQ_API_KEY')]],
]);
echo $ai->ask('Why is the LPU fast?');
```

## Cost & token tracking

Every `chat()` result carries normalized `usage` and an estimated `cost` (USD). Prices live in `Pricing` and are overridable:

```php
use JarirAhmed\PhpLlm\Pricing\Pricing;
use JarirAhmed\PhpLlm\Support\Usage;

Pricing::set('my-finetune', 5.00, 15.00);   // $/1M input, $/1M output
$usd = Pricing::estimate('gpt-4o', new Usage(1_000_000, 1_000_000)); // 12.5
$breakdown = Pricing::breakdown('gpt-4o', new Usage(1000, 500));
```

Hook every call for logging/metering:

```php
use JarirAhmed\PhpLlm\Support\EventDispatcher;
use JarirAhmed\PhpLlm\Events\MessageReceived;

EventDispatcher::listen(MessageReceived::class, function (MessageReceived $e) {
    error_log("{$e->provider} {$e->model} took {$e->latency}s, cost \${$e->response['cost']}");
});
```

## Structured output (JSON)

```php
$person = $ai->chat()
    ->message('Extract: "Ada Lovelace, born 1815, mathematician."')
    ->structured([
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'born' => ['type' => 'integer'],
            'role' => ['type' => 'string'],
        ],
        'required' => ['name', 'born'],
    ]);

// ['name' => 'Ada Lovelace', 'born' => 1815, 'role' => 'mathematician']
```

Uses native `response_format` JSON-schema on OpenAI-compatible providers, and an instruction + tolerant parser everywhere else.

## Streaming

```php
foreach ($ai->chat()->message('Write a haiku')->stream() as $chunk) {
    echo $chunk['content'];
    flush();
}
```

OpenAI-family drivers stream true Server-Sent Events token-by-token via the Guzzle stream body.

## Conversations with memory

```php
$chat = $ai->conversation('user-42', 'openai', 'conversation');
echo $chat->say('My name is Sam.');
echo $chat->say('What is my name?');     // remembers "Sam"
echo $chat->totalCost();
```

Memory drivers: `session` (web), `conversation` (in-process), `persistent` (PDO, self-creating table).

```php
use JarirAhmed\PhpLlm\Client;

Client::useDatabase('default', new PDO('sqlite:' . __DIR__ . '/ai.sqlite'));
$ai->memory()->driver('persistent')->add('user-42', ['role' => 'user', 'content' => 'hi']);
```

## Embeddings, vectors & RAG

```php
$vec = $ai->embed('text to embed');                       // ['embedding'=>[...], 'dimensions'=>1536]

$ai->vector('qdrant')->createCollection('docs', 1536);
$ai->vector('qdrant')->upsert('docs', [
    ['id' => '1', 'vector' => $vec['embedding'], 'payload' => ['text' => '...']],
]);

// RAG
$ai->rag()->ingestion()->ingestFromPath('handbook.md', 'docs');
$answer = $ai->rag()->collection('docs')->question('What is the refund policy?')->answer();
echo $answer['answer'];
```

Vector drivers: `qdrant`, `pinecone`, `pgvector` (PDO), `weaviate`, `milvus`, `chroma`.

## Agents & tool calling

```php
$agent = $ai->agent('openai')
    ->tool('get_time', fn () => date('c'), 'Current time')
    ->maxSteps(5);

$result = $agent->run('What time is it?');
echo $result['response'];
```

## Using inside a framework

Nothing framework-specific is required. In Laravel/Symfony just build the client once in a service/container binding:

```php
$this->app->singleton(\JarirAhmed\PhpLlm\AIClient::class, fn () => \JarirAhmed\PhpLlm\Client::create([
    'llm' => ['openai' => ['api_key' => config('services.openai.key')]],
]));
```

You can also plug your framework's session and DB in: `SessionStore::use($yourStore)` and `Client::useDatabase('default', $pdo)`.

## Testing without network

```php
$ai = Client::create(['llm' => ['openai' => ['api_key' => 'test']]]);
$ai->fake();                              // all providers return deterministic fakes
$this->assertSame('fake response', $ai->ask('anything'));
```

For driver-level tests, inject a Guzzle mock: `PendingRequest::useClient($guzzleWithMockHandler)`.

## Architecture

```
Client::create() ── AIClient (hub)
                      ├─ chat()/generate() ─ Generation (fluent, per-request: usage+cost+structured)
                      ├─ conversation()    ─ Conversation (memory-backed multi-turn)
                      └─ managers ─ LLM / Embedding / Vector / Image / Speech / RAG / Memory / Agent
                                       └─ Drivers  (Support\Http → Guzzle)
Support: Config · Http/PendingRequest/Response · Database(PDO) · SessionStore · EventDispatcher · Env · Usage
Pricing: USD cost tables + estimate/breakdown
```

## Credits

Built on the excellent work of **Manik Mia** (`manik/neuro`, `manik/cortex`). This package re-architects that code to be framework-agnostic and adds cost tracking, structured output, extra providers, and a polished fluent client.

## License

MIT.
