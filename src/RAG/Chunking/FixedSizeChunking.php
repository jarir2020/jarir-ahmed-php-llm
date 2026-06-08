<?php

namespace JarirAhmed\PhpLlm\RAG\Chunking;

use JarirAhmed\PhpLlm\Contracts\ChunkingStrategy;

class FixedSizeChunking implements ChunkingStrategy
{
    public function chunk(string $text, array $options = []): array
    {
        $chunkSize = $options['chunk_size'] ?? 1000;
        $overlap = $options['chunk_overlap'] ?? 200;
        $length = mb_strlen($text);
        $chunks = [];

        if ($length <= $chunkSize) {
            return [$text];
        }

        $start = 0;
        while ($start < $length) {
            $end = $start + $chunkSize;
            $chunks[] = mb_substr($text, $start, $chunkSize);
            $start = $end - $overlap;

            if ($start >= $length) {
                break;
            }
        }

        return $chunks;
    }
}
