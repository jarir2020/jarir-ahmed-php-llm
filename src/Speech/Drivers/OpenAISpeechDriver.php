<?php

namespace JarirAhmed\PhpLlm\Speech\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\SpeechDriver;

class OpenAISpeechDriver implements SpeechDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'whisper-1';
        $this->provider = $config['driver'] ?? 'openai';
    }

    public function transcribe(string $audio, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->attach('audio', fopen($audio, 'r'), basename($audio))
            ->post($this->baseUrl().'/audio/transcriptions', array_merge([
                'model' => $this->model,
            ], $options));

        $data = $response->throw()->json();

        return [
            'text' => $data['text'] ?? '',
        ];
    }

    public function synthesize(string $text, array $options = []): string
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/audio/speech', array_merge([
                'model' => $this->model,
                'input' => $text,
                'voice' => 'alloy',
            ], $options));

        return $response->throw()->body();
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    protected function baseUrl(): string
    {
        return rtrim($this->config['base_url'] ?? 'https://api.openai.com/v1', '/');
    }
}
