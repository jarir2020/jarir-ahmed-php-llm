<?php

namespace JarirAhmed\PhpLlm;

use PDO;
use JarirAhmed\PhpLlm\Support\Config;
use JarirAhmed\PhpLlm\Support\Database;

/**
 * Framework-agnostic entry point. Replaces the Laravel ServiceProvider.
 *
 *   $ai = Client::create([
 *       'defaults' => ['llm' => 'openai'],
 *       'llm' => ['openai' => ['api_key' => 'sk-...']],
 *   ]);
 *
 *   echo $ai->chat()->message('Hello')->ask();
 *
 * Any provider not overridden falls back to the packaged defaults, which in
 * turn read from environment variables.
 */
class Client
{
    /**
     * Build a ready-to-use AIClient. $overrides is deep-merged over the
     * packaged defaults and loaded into the global Config store.
     */
    public static function create(array $overrides = []): AIClient
    {
        $defaults = require __DIR__.'/../config/php-llm.php';

        Config::load(['ai' => $defaults]);

        if ($overrides !== []) {
            Config::merge(['ai' => $overrides]);
        }

        return static::build();
    }

    /** Register a PDO connection for the persistent-memory / pgvector drivers. */
    public static function useDatabase(string $name, PDO $pdo): void
    {
        Database::extend($name, $pdo);
    }

    /** Construct the manager graph from the currently loaded Config. */
    public static function build(): AIClient
    {
        $llm       = new LLM\LLMManager;
        $embedding = new Embedding\EmbeddingManager;
        $vector    = new Vector\VectorManager;
        $image     = new Image\ImageManager;
        $speech    = new Speech\SpeechManager;
        $memory    = new Memory\MemoryManager;
        $rag       = new RAG\RAGManager($llm, $embedding, $vector);

        $manager = new LlmManager($llm, $embedding, $vector, $image, $speech, $rag, $memory);

        return new AIClient($manager);
    }
}
