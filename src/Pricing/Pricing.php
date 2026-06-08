<?php

namespace JarirAhmed\PhpLlm\Pricing;

use JarirAhmed\PhpLlm\Support\Usage;

/**
 * USD cost estimation from token usage.
 *
 * Prices are USD per 1,000,000 tokens, [input, output]. Tables are best-effort
 * snapshots — override or extend at runtime with Pricing::set('model', $in, $out).
 * Matching is exact first, then by longest known prefix (so dated model ids like
 * "gpt-4o-2024-08-06" resolve to "gpt-4o").
 */
class Pricing
{
    /** @var array<string, array{0: float, 1: float}> */
    protected static array $table = [
        // OpenAI
        'gpt-4o'                  => [2.50, 10.00],
        'gpt-4o-mini'             => [0.15, 0.60],
        'gpt-4-turbo'             => [10.00, 30.00],
        'gpt-4'                   => [30.00, 60.00],
        'gpt-3.5-turbo'           => [0.50, 1.50],
        'o1'                      => [15.00, 60.00],
        'o1-mini'                 => [1.10, 4.40],
        'text-embedding-3-small'  => [0.02, 0.0],
        'text-embedding-3-large'  => [0.13, 0.0],

        // Anthropic
        'claude-3-5-sonnet'       => [3.00, 15.00],
        'claude-3-5-haiku'        => [0.80, 4.00],
        'claude-3-opus'           => [15.00, 75.00],
        'claude-3-sonnet'         => [3.00, 15.00],
        'claude-3-haiku'          => [0.25, 1.25],

        // Google Gemini
        'gemini-2.0-flash'        => [0.10, 0.40],
        'gemini-1.5-pro'          => [1.25, 5.00],
        'gemini-1.5-flash'        => [0.075, 0.30],

        // Mistral
        'mistral-large'           => [2.00, 6.00],
        'mistral-small'           => [0.20, 0.60],

        // DeepSeek
        'deepseek-chat'           => [0.27, 1.10],
        'deepseek-reasoner'       => [0.55, 2.19],

        // Groq (Llama hosting)
        'llama-3.3-70b-versatile' => [0.59, 0.79],
        'llama-3.1-8b-instant'    => [0.05, 0.08],

        // xAI Grok
        'grok-2'                  => [2.00, 10.00],

        // Cohere
        'command-r-plus'          => [2.50, 10.00],
        'command-r'               => [0.15, 0.60],
    ];

    /** Add or override a model's price (USD per 1M tokens). */
    public static function set(string $model, float $inputPerMillion, float $outputPerMillion): void
    {
        static::$table[$model] = [$inputPerMillion, $outputPerMillion];
    }

    /** Return [input, output] USD-per-1M for a model, or null if unknown. */
    public static function rates(string $model): ?array
    {
        if (isset(static::$table[$model])) {
            return static::$table[$model];
        }

        $best = null;
        $bestLen = 0;
        foreach (static::$table as $key => $rates) {
            if (str_starts_with($model, $key) && strlen($key) > $bestLen) {
                $best = $rates;
                $bestLen = strlen($key);
            }
        }

        return $best;
    }

    /** Estimate USD cost for a model + usage. Returns 0.0 for unknown models. */
    public static function estimate(string $model, Usage $usage): float
    {
        $rates = static::rates($model);

        if ($rates === null) {
            return 0.0;
        }

        return ($usage->promptTokens / 1_000_000) * $rates[0]
             + ($usage->completionTokens / 1_000_000) * $rates[1];
    }

    /** Detailed breakdown: input/output/total cost in USD. */
    public static function breakdown(string $model, Usage $usage): array
    {
        $rates = static::rates($model) ?? [0.0, 0.0];

        $input = ($usage->promptTokens / 1_000_000) * $rates[0];
        $output = ($usage->completionTokens / 1_000_000) * $rates[1];

        return [
            'model'       => $model,
            'known'       => static::rates($model) !== null,
            'input_cost'  => round($input, 6),
            'output_cost' => round($output, 6),
            'total_cost'  => round($input + $output, 6),
            'usage'       => $usage->toArray(),
        ];
    }
}
