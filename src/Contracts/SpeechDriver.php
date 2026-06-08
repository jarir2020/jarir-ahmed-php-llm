<?php

namespace JarirAhmed\PhpLlm\Contracts;

interface SpeechDriver
{
    public function transcribe(string $audio, array $options = []): array;

    public function synthesize(string $text, array $options = []): string;

    public function setModel(string $model): static;

    public function getModel(): string;
}
