<?php

require __DIR__ . '/../vendor/autoload.php';

use JarirAhmed\PhpLlm\Client;
use JarirAhmed\PhpLlm\Pricing\Pricing;
use JarirAhmed\PhpLlm\Support\Usage;
use JarirAhmed\PhpLlm\RAG\Chunking\RecursiveChunking;

$ai = Client::create([
    'defaults' => ['llm' => 'openai', 'embedding' => 'openai'],
    'llm' => ['openai' => ['api_key' => 'test']],
]);

$ai->fake();

// 1. fluent generation + one-shot shortcut
echo 'ask = ' . $ai->chat()->system('be terse')->message('hello')->ask() . PHP_EOL;
echo 'shortcut = ' . $ai->ask('hi there') . PHP_EOL;

// 2. usage + cost surfaced on the result array
$res = $ai->chat()->message('hello')->chat();
echo 'usage keys = ' . implode(',', array_keys($res['usage'])) . PHP_EOL;
echo 'cost present = ' . (isset($res['cost']) ? 'yes' : 'no') . PHP_EOL;

// 3. pricing math (independent of network)
$cost = Pricing::estimate('gpt-4o', new Usage(1_000_000, 1_000_000));
echo 'gpt-4o 1M+1M = $' . $cost . PHP_EOL; // 2.50 + 10.00 = 12.5

// 4. one-shot embedding
echo 'embed dims = ' . $ai->embed('hi')['dimensions'] . PHP_EOL;

// 5. conversation with memory
$chat = $ai->conversation('user-1', 'openai', 'conversation');
$chat->say('My name is Sam.');
$chat->say('and again');
echo 'history turns = ' . count($chat->history()) . PHP_EOL;

// 6. new providers resolve to the right driver classes (fresh, un-faked client)
$ai2 = Client::create(['llm' => [
    'deepseek' => ['api_key' => 'x'], 'groq' => ['api_key' => 'x'],
    'openrouter' => ['api_key' => 'x'], 'azure' => ['api_key' => 'x', 'base_url' => 'https://x.openai.azure.com'],
]]);
foreach (['deepseek', 'groq', 'openrouter', 'azure'] as $p) {
    $cls = (new ReflectionClass($ai2->llm($p)))->getShortName();
    echo "provider {$p} -> {$cls}" . PHP_EOL;
}

// 7. chunking still works
$chunks = (new RecursiveChunking())->chunk(str_repeat('word ', 500), ['chunk_size' => 100, 'chunk_overlap' => 20]);
echo 'chunks = ' . count($chunks) . PHP_EOL;

echo 'OK' . PHP_EOL;
