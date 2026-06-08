<?php

namespace JarirAhmed\PhpLlm\RAG\Chunking;

use JarirAhmed\PhpLlm\Contracts\ChunkingStrategy;

class SlidingWindowChunking implements ChunkingStrategy
{
    public function chunk(string $text, array $options = []): array
    {
        $windowSize = $options['window_size'] ?? 500;
        $stride = $options['stride'] ?? 250;
        $length = mb_strlen($text);
        $chunks = [];

        if ($length <= $windowSize) {
            return [$text];
        }

        $start = 0;
        while ($start < $length) {
            $end = min($start + $windowSize, $length);
            $chunks[] = mb_substr($text, $start, $end - $start);

            if ($end >= $length) {
                break;
            }

            $start += $stride;

            if ($start >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
