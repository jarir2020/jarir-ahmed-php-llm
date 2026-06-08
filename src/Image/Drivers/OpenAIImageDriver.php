<?php

namespace JarirAhmed\PhpLlm\Image\Drivers;

use JarirAhmed\PhpLlm\Support\Http;
use JarirAhmed\PhpLlm\Contracts\ImageDriver;

class OpenAIImageDriver implements ImageDriver
{
    protected array $config;

    protected string $model;

    protected string $provider;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->model = $config['model'] ?? 'dall-e-3';
        $this->provider = $config['driver'] ?? 'openai';
    }

    public function generate(string $prompt, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->post($this->baseUrl().'/images/generations', array_merge([
                'prompt' => $prompt,
                'model' => $this->model,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ], $options));

        $data = $response->throw()->json();

        return [
            'url' => $data['data'][0]['url'] ?? '',
            'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
        ];
    }

    public function edit(string $image, string $prompt, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->attach('image', fopen($image, 'r'), basename($image))
            ->post($this->baseUrl().'/images/edits', array_merge([
                'prompt' => $prompt,
                'model' => $this->model,
            ], $options));

        $data = $response->throw()->json();

        return [
            'url' => $data['data'][0]['url'] ?? '',
            'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
        ];
    }

    public function variations(string $image, array $options = []): array
    {
        $response = Http::withToken($this->config['api_key'])
            ->timeout($this->config['timeout'] ?? 30)
            ->attach('image', fopen($image, 'r'), basename($image))
            ->post($this->baseUrl().'/images/variations', array_merge([
                'model' => $this->model,
            ], $options));

        $data = $response->throw()->json();

        return [
            'url' => $data['data'][0]['url'] ?? '',
            'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
        ];
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
