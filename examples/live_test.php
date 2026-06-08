<?php
// Live end-to-end check. Reads keys from environment ONLY (no secrets on disk).
require __DIR__ . '/../vendor/autoload.php';

use JarirAhmed\PhpLlm\Client;

function line(string $s): void { echo $s . PHP_EOL; }
function run(string $label, callable $fn): void {
    try { $fn(); }
    catch (\Throwable $e) { line("  [$label] ERROR: " . $e->getMessage()); }
}

$ai = Client::create([
    'defaults' => ['llm' => 'groq'],
    'llm' => [
        'groq'     => ['api_key' => getenv('GROQ_API_KEY')],
        'openai'   => ['api_key' => getenv('OPENAI_API_KEY'), 'model' => 'gpt-4o-mini'],
        'gemini'   => ['api_key' => getenv('GEMINI_API_KEY')],
        // OpenAI-compatible providers wired purely via config (driver => openai):
        'cerebras' => ['driver' => 'openai', 'api_key' => getenv('CEREBRAS_API_KEY'),
                       'base_url' => 'https://api.cerebras.ai/v1', 'model' => 'gpt-oss-120b'],
        'sambanova'=> ['driver' => 'openai', 'api_key' => getenv('SAMBANOVA_API_KEY'),
                       'base_url' => 'https://api.sambanova.ai/v1', 'model' => 'Meta-Llama-3.3-70B-Instruct'],
    ],
]);

line('== chat + usage + cost ==');
foreach (['groq', 'openai', 'gemini', 'cerebras', 'sambanova'] as $p) {
    run($p, function () use ($ai, $p) {
        $r = $ai->chat()->provider($p)->message('Reply with exactly: pong')->chat();
        $u = $r['usage']['total_tokens'] ?? 0;
        $c = $r['cost'] ?? 0;
        printf("  %-10s -> %-20s | tokens=%-4d cost=\$%.6f | %.2fs%s", $p,
            '"' . trim($r['content']) . '"', $u, $c, $r['latency'] ?? 0, PHP_EOL);
    });
}

line('== streaming (groq) ==');
run('groq-stream', function () use ($ai) {
    echo '  ';
    foreach ($ai->chat()->provider('groq')->message('Count 1 to 5, space separated.')->stream() as $chunk) {
        echo $chunk['content'];
    }
    echo PHP_EOL;
});

line('== structured output (groq, json schema) ==');
run('groq-structured', function () use ($ai) {
    $data = $ai->chat()->provider('groq')
        ->message('Extract: "Ada Lovelace, born 1815, mathematician."')
        ->structured([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'born' => ['type' => 'integer'],
                'role' => ['type' => 'string'],
            ],
            'required' => ['name', 'born', 'role'],
            'additionalProperties' => false,
        ]);
    line('  ' . json_encode($data));
});

line('== one-shot embed (openai) ==');
run('openai-embed', function () use ($ai) {
    $e = $ai->embed('hello world', 'openai');
    line('  dims=' . ($e['dimensions'] ?? 0) . ' first=' . round($e['embedding'][0] ?? 0, 5));
});

line('DONE');
