<?php

namespace JarirAhmed\PhpLlm\Agent;

use JarirAhmed\PhpLlm\Contracts\LLMDriver;
use JarirAhmed\PhpLlm\Memory\MemoryManager;

class Agent
{
    protected array $tools = [];

    protected int $maxSteps = 10;

    protected ?string $sessionId = null;

    public function __construct(
        protected LLMDriver $llm,
        protected MemoryManager $memory,
    ) {}

    public function tool(string $name, callable $fn, string $description = ''): static
    {
        $this->tools[] = [
            'name' => $name,
            'fn' => $fn,
            'description' => $description,
        ];

        return $this;
    }

    public function tools(array $tools): static
    {
        foreach ($tools as $tool) {
            $this->tool($tool['name'], $tool['fn'], $tool['description'] ?? '');
        }

        return $this;
    }

    public function session(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function maxSteps(int $steps): static
    {
        $this->maxSteps = $steps;

        return $this;
    }

    public function run(string $task): array
    {
        $messages = [];
        $totalSteps = 0;
        $totalTokens = [
            'prompt' => 0,
            'completion' => 0,
            'total' => 0,
        ];

        if ($this->sessionId !== null) {
            $history = $this->memory->driver()->get($this->sessionId);
            $messages = array_merge($messages, $history);
        }

        $messages[] = ['role' => 'user', 'content' => $task];

        for ($step = 0; $step < $this->maxSteps; $step++) {
            $totalSteps++;

            $toolDefinitions = array_map(fn ($tool) => [
                'name' => $tool['name'],
                'description' => $tool['description'],
            ], $this->tools);

            $response = $this->llm->tools($messages, $toolDefinitions);

            if (isset($response['usage'])) {
                $totalTokens['prompt'] += $response['usage']['prompt_tokens'] ?? 0;
                $totalTokens['completion'] += $response['usage']['completion_tokens'] ?? 0;
                $totalTokens['total'] += $response['usage']['total_tokens'] ?? 0;
            }

            if (isset($response['tool_calls']) && count($response['tool_calls']) > 0) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'] ?? '',
                    'tool_calls' => $response['tool_calls'],
                ];

                foreach ($response['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'];
                    $arguments = json_decode($toolCall['function']['arguments'], true);

                    $toolResult = $this->executeTool($toolName, $arguments);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => is_string($toolResult) ? $toolResult : json_encode($toolResult),
                    ];
                }

                continue;
            }

            $messages[] = ['role' => 'assistant', 'content' => $response['content'] ?? ''];

            if ($this->sessionId !== null) {
                $memory = $this->memory->driver();
                $memory->add($this->sessionId, ['role' => 'user', 'content' => $task]);
                $memory->add($this->sessionId, ['role' => 'assistant', 'content' => $response['content'] ?? '']);
            }

            return [
                'response' => $response['content'] ?? '',
                'steps' => $totalSteps,
                'tokens' => $totalTokens,
            ];
        }

        return [
            'response' => $messages[count($messages) - 1]['content'] ?? '',
            'steps' => $totalSteps,
            'tokens' => $totalTokens,
        ];
    }

    protected function executeTool(string $name, array $arguments): mixed
    {
        foreach ($this->tools as $tool) {
            if ($tool['name'] === $name) {
                return $tool['fn']($arguments);
            }
        }

        throw new \InvalidArgumentException("Unknown tool: {$name}");
    }
}
