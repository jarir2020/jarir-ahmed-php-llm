<?php

namespace JarirAhmed\PhpLlm\RAG\Chunking;

use JarirAhmed\PhpLlm\Contracts\ChunkingStrategy;

class SemanticChunking implements ChunkingStrategy
{
    public function chunk(string $text, array $options = []): array
    {
        $separators = [
            "/\n#{1,6}\s.+/",    // Markdown headings
            "/\n={3,}\s*$/m",     // Markdown === underline headings
            "/\n-{3,}\s*$/m",     // Markdown --- underline headings
            "/\n---\s*\n/",       // Horizontal rules
            "/\n\*\*\*\s*\n/",    // Horizontal rules (alt)
            "/\n\n\n+/",          // Multiple blank lines
            "/\n\n/",             // Double newlines
        ];

        $sections = [$text];

        foreach ($separators as $pattern) {
            $newSections = [];
            foreach ($sections as $section) {
                $parts = preg_split($pattern, $section);
                array_push($newSections, ...$parts);
            }
            $sections = $newSections;
        }

        return array_values(array_filter(array_map('trim', $sections), fn (string $s): bool => $s !== ''));
    }
}
