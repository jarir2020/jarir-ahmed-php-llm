<?php

namespace JarirAhmed\PhpLlm\RAG;

use JarirAhmed\PhpLlm\Contracts\EmbeddingDriver;
use JarirAhmed\PhpLlm\Contracts\LLMDriver;
use JarirAhmed\PhpLlm\Contracts\VectorDriver;

class RAGPipeline
{
    protected string $collection;

    protected string $question;

    protected int $topK;

    protected float $minScore;

    public function __construct(
        protected LLMDriver $llm,
        protected EmbeddingDriver $embedder,
        protected VectorDriver $vectorStore,
    ) {
        $this->topK = (int) \JarirAhmed\PhpLlm\Support\Config::get('ai.rag.top_k', 5);
        $this->minScore = (float) \JarirAhmed\PhpLlm\Support\Config::get('ai.rag.min_score', 0.0);
    }

    public function collection(string $name): static
    {
        $this->collection = $name;

        return $this;
    }

    public function question(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function topK(int $k): static
    {
        $this->topK = $k;

        return $this;
    }

    public function minScore(float $score): static
    {
        $this->minScore = $score;

        return $this;
    }

    public function answer(): array
    {
        $results = $this->search();

        $context = $this->formatContext($results);

        $messages = [
            ['role' => 'system', 'content' => "You are a helpful AI assistant. Use the following context to answer the user's question. If you cannot find the answer in the context, say so.\n\nContext:\n{$context}"],
            ['role' => 'user', 'content' => $this->question],
        ];

        $response = $this->llm->chat($messages);

        return [
            'answer' => $response['content'] ?? $response['message']['content'] ?? '',
            'sources' => array_map(fn (array $r): array => [
                'content' => $r['payload']['content'] ?? '',
                'score' => $r['score'] ?? 0,
                'metadata' => $r['payload']['metadata'] ?? [],
            ], $results),
            'model' => $this->llm->getModel(),
        ];
    }

    public function search(): array
    {
        $vector = $this->embedder->embed($this->question);

        return $this->query($this->collection, $vector, [
            'top_k' => $this->topK,
            'min_score' => $this->minScore,
        ]);
    }

    public function query(string $collection, array $vector, array $options = []): array
    {
        $options['top_k'] ??= $this->topK;
        $options['min_score'] ??= $this->minScore;

        return $this->vectorStore->search($collection, $vector, $options);
    }

    protected function formatContext(array $results): string
    {
        $parts = [];

        foreach ($results as $i => $result) {
            $content = $result['payload']['content'] ?? '';
            $score = $result['score'] ?? 0;
            $parts[] = "[Document {$i}] (relevance: {$score})\n{$content}";
        }

        return implode("\n\n", $parts);
    }
}
