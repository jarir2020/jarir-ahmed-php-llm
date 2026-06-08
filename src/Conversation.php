<?php

namespace JarirAhmed\PhpLlm;

/**
 * Stateful multi-turn conversation backed by a memory driver. History is loaded
 * before each turn and the new user/assistant pair is persisted after.
 *
 *   $chat = $ai->conversation('user-42', 'openai', 'conversation');
 *   echo $chat->say('My name is Sam.');
 *   echo $chat->say('What is my name?');   // remembers "Sam"
 */
class Conversation
{
    protected ?string $system = null;

    protected array $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

    protected float $cost = 0.0;

    public function __construct(
        protected LlmManager $manager,
        protected string $sessionId,
        protected ?string $provider = null,
        protected ?string $memoryDriver = null,
    ) {}

    public function system(string $content): static
    {
        $this->system = $content;

        return $this;
    }

    /** Send a message, get the assistant reply string, persist the exchange. */
    public function say(string $message): string
    {
        $memory = $this->manager->memory()->driver($this->memoryDriver);
        $history = $memory->get($this->sessionId);

        $gen = (new Generation($this->manager));

        if ($this->provider !== null) {
            $gen->provider($this->provider);
        }

        if ($this->system !== null) {
            $gen->system($this->system);
        }

        $gen->messages(array_merge(
            $this->system !== null ? [['role' => 'system', 'content' => $this->system]] : [],
            $history,
            [['role' => 'user', 'content' => $message]],
        ));

        $response = $gen->chat();
        $reply = $response['content'] ?? '';

        $this->accumulate($response);

        $memory->add($this->sessionId, ['role' => 'user', 'content' => $message]);
        $memory->add($this->sessionId, ['role' => 'assistant', 'content' => $reply]);

        return $reply;
    }

    public function history(): array
    {
        return $this->manager->memory()->driver($this->memoryDriver)->get($this->sessionId);
    }

    public function clear(): void
    {
        $this->manager->memory()->driver($this->memoryDriver)->clear($this->sessionId);
    }

    /** Cumulative token usage for this conversation object's lifetime. */
    public function totalUsage(): array
    {
        return $this->usage;
    }

    /** Cumulative estimated USD cost for this conversation object's lifetime. */
    public function totalCost(): float
    {
        return round($this->cost, 6);
    }

    protected function accumulate(array $response): void
    {
        foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $key) {
            $this->usage[$key] += $response['usage'][$key] ?? 0;
        }

        $this->cost += $response['cost'] ?? 0.0;
    }
}
