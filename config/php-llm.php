<?php

use JarirAhmed\PhpLlm\Support\Env;

/**
 * Default configuration for jarir-ahmed/php-llm.
 *
 * Client::create($overrides) deep-merges your overrides on top of this and
 * loads the result under the "ai" config root. Values fall back to environment
 * variables so the same file works in any framework or plain PHP.
 */
return [

    'defaults' => [
        'llm'        => Env::get('AI_DEFAULT_LLM', 'openai'),
        'embedding'  => Env::get('AI_DEFAULT_EMBEDDING', 'openai'),
        'vector'     => Env::get('AI_DEFAULT_VECTOR', 'qdrant'),
        'image'      => Env::get('AI_DEFAULT_IMAGE', 'openai'),
        'speech'     => Env::get('AI_DEFAULT_SPEECH', 'openai'),
        'collection' => Env::get('AI_DEFAULT_VECTOR_COLLECTION', 'default'),
    ],

    'llm' => [
        'openai' => [
            'driver'      => 'openai',
            'api_key'     => Env::get('OPENAI_API_KEY'),
            'base_url'    => Env::get('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model'       => Env::get('OPENAI_LLM_MODEL', 'gpt-4o'),
            'temperature' => (float) Env::get('OPENAI_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('OPENAI_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('OPENAI_TIMEOUT', 60),
        ],

        'anthropic' => [
            'driver'      => 'anthropic',
            'api_key'     => Env::get('ANTHROPIC_API_KEY'),
            'base_url'    => Env::get('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
            'model'       => Env::get('ANTHROPIC_LLM_MODEL', 'claude-3-5-sonnet-20241022'),
            'temperature' => (float) Env::get('ANTHROPIC_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('ANTHROPIC_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('ANTHROPIC_TIMEOUT', 60),
        ],

        'gemini' => [
            'driver'      => 'gemini',
            'api_key'     => Env::get('GEMINI_API_KEY'),
            'base_url'    => Env::get('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model'       => Env::get('GEMINI_LLM_MODEL', 'gemini-2.5-flash'),
            'temperature' => (float) Env::get('GEMINI_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('GEMINI_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('GEMINI_TIMEOUT', 60),
        ],

        'ollama' => [
            'driver'      => 'ollama',
            'base_url'    => Env::get('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model'       => Env::get('OLLAMA_LLM_MODEL', 'llama3'),
            'temperature' => (float) Env::get('OLLAMA_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('OLLAMA_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('OLLAMA_TIMEOUT', 120),
        ],

        'grok' => [
            'driver'      => 'grok',
            'api_key'     => Env::get('XAI_API_KEY'),
            'base_url'    => Env::get('XAI_BASE_URL', 'https://api.x.ai/v1'),
            'model'       => Env::get('XAI_LLM_MODEL', 'grok-2'),
            'temperature' => (float) Env::get('XAI_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('XAI_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('XAI_TIMEOUT', 60),
        ],

        'mistral' => [
            'driver'      => 'mistral',
            'api_key'     => Env::get('MISTRAL_API_KEY'),
            'base_url'    => Env::get('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            'model'       => Env::get('MISTRAL_LLM_MODEL', 'mistral-large-latest'),
            'temperature' => (float) Env::get('MISTRAL_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('MISTRAL_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('MISTRAL_TIMEOUT', 60),
        ],

        'cohere' => [
            'driver'      => 'cohere',
            'api_key'     => Env::get('COHERE_API_KEY'),
            'base_url'    => Env::get('COHERE_BASE_URL', 'https://api.cohere.ai/v1'),
            'model'       => Env::get('COHERE_LLM_MODEL', 'command-r-plus'),
            'temperature' => (float) Env::get('COHERE_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('COHERE_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('COHERE_TIMEOUT', 60),
        ],

        // ---- New OpenAI-compatible providers added by php-llm ----
        'deepseek' => [
            'driver'      => 'deepseek',
            'api_key'     => Env::get('DEEPSEEK_API_KEY'),
            'base_url'    => Env::get('DEEPSEEK_BASE_URL', 'https://api.deepseek.com/v1'),
            'model'       => Env::get('DEEPSEEK_LLM_MODEL', 'deepseek-chat'),
            'temperature' => (float) Env::get('DEEPSEEK_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('DEEPSEEK_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('DEEPSEEK_TIMEOUT', 60),
        ],

        'groq' => [
            'driver'      => 'groq',
            'api_key'     => Env::get('GROQ_API_KEY'),
            'base_url'    => Env::get('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'model'       => Env::get('GROQ_LLM_MODEL', 'llama-3.3-70b-versatile'),
            'temperature' => (float) Env::get('GROQ_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('GROQ_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('GROQ_TIMEOUT', 60),
        ],

        'openrouter' => [
            'driver'      => 'openrouter',
            'api_key'     => Env::get('OPENROUTER_API_KEY'),
            'base_url'    => Env::get('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
            'model'       => Env::get('OPENROUTER_LLM_MODEL', 'openai/gpt-4o'),
            'temperature' => (float) Env::get('OPENROUTER_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('OPENROUTER_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('OPENROUTER_TIMEOUT', 60),
            'referer'     => Env::get('OPENROUTER_REFERER'),
            'title'       => Env::get('OPENROUTER_TITLE'),
        ],

        'azure' => [
            'driver'      => 'azure',
            'api_key'     => Env::get('AZURE_OPENAI_API_KEY'),
            'base_url'    => Env::get('AZURE_OPENAI_ENDPOINT'), // https://{resource}.openai.azure.com
            'deployment'  => Env::get('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
            'api_version' => Env::get('AZURE_OPENAI_API_VERSION', '2024-08-01-preview'),
            'model'       => Env::get('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
            'temperature' => (float) Env::get('AZURE_OPENAI_TEMPERATURE', 0.7),
            'max_tokens'  => (int) Env::get('AZURE_OPENAI_MAX_TOKENS', 4096),
            'timeout'     => (int) Env::get('AZURE_OPENAI_TIMEOUT', 60),
        ],
    ],

    'embedding' => [
        'openai' => [
            'driver'     => 'openai',
            'api_key'    => Env::get('OPENAI_API_KEY'),
            'base_url'   => Env::get('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model'      => Env::get('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
            'dimensions' => (int) Env::get('OPENAI_EMBEDDING_DIMENSIONS', 1536),
            'timeout'    => (int) Env::get('OPENAI_TIMEOUT', 60),
        ],
        'ollama' => [
            'driver'     => 'ollama',
            'base_url'   => Env::get('OLLAMA_BASE_URL', 'http://localhost:11434'),
            'model'      => Env::get('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
            'dimensions' => (int) Env::get('OLLAMA_EMBEDDING_DIMENSIONS', 768),
            'timeout'    => (int) Env::get('OLLAMA_TIMEOUT', 120),
        ],
        'gemini' => [
            'driver'     => 'gemini',
            'api_key'    => Env::get('GEMINI_API_KEY'),
            'base_url'   => Env::get('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model'      => Env::get('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
            'dimensions' => (int) Env::get('GEMINI_EMBEDDING_DIMENSIONS', 768),
            'timeout'    => (int) Env::get('GEMINI_TIMEOUT', 60),
        ],
        'mistral' => [
            'driver'     => 'mistral',
            'api_key'    => Env::get('MISTRAL_API_KEY'),
            'base_url'   => Env::get('MISTRAL_BASE_URL', 'https://api.mistral.ai/v1'),
            'model'      => Env::get('MISTRAL_EMBEDDING_MODEL', 'mistral-embed'),
            'dimensions' => (int) Env::get('MISTRAL_EMBEDDING_DIMENSIONS', 1024),
            'timeout'    => (int) Env::get('MISTRAL_TIMEOUT', 60),
        ],
        'cohere' => [
            'driver'     => 'cohere',
            'api_key'    => Env::get('COHERE_API_KEY'),
            'base_url'   => Env::get('COHERE_BASE_URL', 'https://api.cohere.ai/v1'),
            'model'      => Env::get('COHERE_EMBEDDING_MODEL', 'embed-english-v3.0'),
            'dimensions' => (int) Env::get('COHERE_EMBEDDING_DIMENSIONS', 1024),
            'timeout'    => (int) Env::get('COHERE_TIMEOUT', 60),
        ],
    ],

    'vector' => [
        'qdrant' => [
            'driver'     => 'qdrant',
            'host'       => Env::get('QDRANT_HOST', 'http://localhost:6333'),
            'api_key'    => Env::get('QDRANT_API_KEY'),
            'collection' => Env::get('QDRANT_COLLECTION'),
            'timeout'    => (int) Env::get('QDRANT_TIMEOUT', 30),
        ],
        'pinecone' => [
            'driver'      => 'pinecone',
            'api_key'     => Env::get('PINECONE_API_KEY'),
            'environment' => Env::get('PINECONE_ENVIRONMENT'),
            'index_host'  => Env::get('PINECONE_INDEX_HOST'),
            'collection'  => Env::get('PINECONE_COLLECTION'),
            'timeout'     => (int) Env::get('PINECONE_TIMEOUT', 30),
        ],
        'pgvector' => [
            'driver'       => 'pgvector',
            'connection'   => Env::get('PGVECTOR_CONNECTION', 'pgsql'),
            'schema'       => Env::get('PGVECTOR_SCHEMA', 'public'),
            'table_prefix' => Env::get('PGVECTOR_TABLE_PREFIX', 'vector_'),
            'dimensions'   => (int) Env::get('PGVECTOR_DIMENSIONS', 1536),
            'collection'   => Env::get('PGVECTOR_COLLECTION'),
        ],
        'weaviate' => [
            'driver'     => 'weaviate',
            'host'       => Env::get('WEAVIATE_HOST', 'http://localhost:8080'),
            'api_key'    => Env::get('WEAVIATE_API_KEY'),
            'collection' => Env::get('WEAVIATE_COLLECTION'),
            'timeout'    => (int) Env::get('WEAVIATE_TIMEOUT', 30),
        ],
        'milvus' => [
            'driver'     => 'milvus',
            'host'       => Env::get('MILVUS_HOST', 'http://localhost:19530'),
            'api_key'    => Env::get('MILVUS_TOKEN'),
            'username'   => Env::get('MILVUS_USERNAME'),
            'password'   => Env::get('MILVUS_PASSWORD'),
            'collection' => Env::get('MILVUS_COLLECTION'),
            'timeout'    => (int) Env::get('MILVUS_TIMEOUT', 30),
        ],
        'chroma' => [
            'driver'     => 'chroma',
            'host'       => Env::get('CHROMA_HOST', 'http://localhost:8000'),
            'tenant'     => Env::get('CHROMA_TENANT', 'default'),
            'database'   => Env::get('CHROMA_DATABASE', 'default'),
            'collection' => Env::get('CHROMA_COLLECTION'),
            'timeout'    => (int) Env::get('CHROMA_TIMEOUT', 30),
        ],
    ],

    'image' => [
        'openai' => [
            'driver'   => 'openai',
            'api_key'  => Env::get('OPENAI_API_KEY'),
            'base_url' => Env::get('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model'    => Env::get('OPENAI_IMAGE_MODEL', 'dall-e-3'),
            'size'     => Env::get('OPENAI_IMAGE_SIZE', '1024x1024'),
            'quality'  => Env::get('OPENAI_IMAGE_QUALITY', 'standard'),
            'timeout'  => (int) Env::get('OPENAI_TIMEOUT', 60),
        ],
    ],

    'speech' => [
        'openai' => [
            'driver'   => 'openai',
            'api_key'  => Env::get('OPENAI_API_KEY'),
            'base_url' => Env::get('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'model'    => Env::get('OPENAI_SPEECH_MODEL', 'tts-1'),
            'voice'    => Env::get('OPENAI_SPEECH_VOICE', 'alloy'),
            'timeout'  => (int) Env::get('OPENAI_TIMEOUT', 60),
        ],
    ],

    'memory' => [
        'default' => Env::get('AI_DEFAULT_MEMORY', 'session'),
        'drivers' => [
            'session'      => ['driver' => 'session', 'limit' => (int) Env::get('AI_MEMORY_LIMIT', 20)],
            'conversation' => ['driver' => 'conversation', 'limit' => (int) Env::get('AI_MEMORY_LIMIT', 50)],
            'persistent'   => [
                'driver'     => 'persistent',
                'connection' => Env::get('AI_MEMORY_DB_CONNECTION', 'default'),
                'table'      => Env::get('AI_MEMORY_TABLE', 'ai_memories'),
                'limit'      => (int) Env::get('AI_MEMORY_LIMIT', 100),
            ],
        ],
    ],

    'rag' => [
        'default_chunk_strategy' => Env::get('AI_RAG_CHUNK_STRATEGY', 'recursive'),
        'chunk_size'             => (int) Env::get('AI_RAG_CHUNK_SIZE', 1000),
        'chunk_overlap'          => (int) Env::get('AI_RAG_CHUNK_OVERLAP', 200),
        'top_k'                  => (int) Env::get('AI_RAG_TOP_K', 5),
        'min_score'              => (float) Env::get('AI_RAG_MIN_SCORE', 0.0),
    ],

    'agent' => [
        'default_max_steps' => (int) Env::get('AI_AGENT_MAX_STEPS', 10),
        'default_llm'       => Env::get('AI_DEFAULT_LLM', 'openai'),
    ],

    // PDO connections used by the persistent-memory and pgvector drivers.
    // Either define a DSN here, or call Database::extend('name', $pdo) at runtime.
    'database' => [
        'connections' => [
            'default' => [
                'dsn'      => Env::get('AI_DB_DSN'), // e.g. sqlite:/path/ai.sqlite, mysql:host=...;dbname=...
                'username' => Env::get('AI_DB_USERNAME'),
                'password' => Env::get('AI_DB_PASSWORD'),
            ],
            'pgsql' => [
                'dsn'      => Env::get('AI_PGSQL_DSN'), // pgsql:host=localhost;port=5432;dbname=...
                'username' => Env::get('AI_PGSQL_USERNAME'),
                'password' => Env::get('AI_PGSQL_PASSWORD'),
            ],
        ],
    ],

    // Cost & token observability (new in php-llm).
    'observability' => [
        'track_cost'    => (bool) Env::get('AI_TRACK_COST', false),
        'track_tokens'  => (bool) Env::get('AI_TRACK_TOKENS', false),
        'track_latency' => (bool) Env::get('AI_TRACK_LATENCY', false),
    ],
];
