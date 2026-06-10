<?php

namespace JarirAhmed\PhpLlm\Bridge;

use JarirAhmed\PhpLlm\AIClient;
use JarirAhmed\PhpLlm\Client;
use Nemesis\Core\Config;
use Nemesis\Core\ServiceProvider;

/**
 * Auto-discovered service provider for the Nemesis framework (lazy, opt-in).
 *
 * Discovered via composer.json `extra.nemesis.providers`. Registers a lazy
 * binding only — the LLM client is built on first resolve, reading config/llm.php
 * if present (otherwise environment variables). Dormant in vendor/ until used.
 *
 *   $ai = app('llm');                 // AIClient, built on first use
 *   echo $ai->ask('Hello');
 */
class NemesisServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->container->singleton(AIClient::class, function () {
            $config = Config::get('llm');

            return Client::create(is_array($config) ? $config : []);
        });

        $this->container->singleton('llm', fn ($c) => $c->make(AIClient::class));
    }
}
