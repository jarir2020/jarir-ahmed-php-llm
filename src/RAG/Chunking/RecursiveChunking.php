<?php

namespace JarirAhmed\PhpLlm\RAG\Chunking;

use JarirAhmed\PhpLlm\Contracts\ChunkingStrategy;

class RecursiveChunking implements ChunkingStrategy
{
    protected array $separators = ["\n\n", "\n", '.', ' ', ''];

    public function chunk(string $text, array $options = []): array
    {
        $chunkSize = $options['chunk_size'] ?? 1000;
        $overlap = $options['chunk_overlap'] ?? 200;
        $chunks = [];

        $this->splitRecursive($text, $this->separators, $chunkSize, $overlap, $chunks);

        return $chunks;
    }

    protected function splitRecursive(string $text, array $separators, int $chunkSize, int $overlap, array &$chunks): void
    {
        $separator = array_shift($separators);

        if ($separator === '') {
            $this->splitByCharacter($text, $chunkSize, $overlap, $chunks);

            return;
        }

        $parts = explode($separator, $text);
        $current = '';

        foreach ($parts as $part) {
            $candidate = $current === '' ? $part : $current.$separator.$part;

            if (mb_strlen($candidate) <= $chunkSize) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
            }

            if (mb_strlen($part) <= $chunkSize) {
                $current = $part;
            } else {
                $this->splitRecursive($part, $separators, $chunkSize, $overlap, $chunks);
                $current = '';
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }
    }

    protected function splitByCharacter(string $text, int $chunkSize, int $overlap, array &$chunks): void
    {
        $length = mb_strlen($text);

        if ($length <= $chunkSize) {
            $chunks[] = $text;

            return;
        }

        $start = 0;
        while ($start < $length) {
            $chunks[] = mb_substr($text, $start, $chunkSize);
            $start += ($chunkSize - $overlap);
        }
    }
}
