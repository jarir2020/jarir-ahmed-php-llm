<?php

namespace JarirAhmed\PhpLlm\Support;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use JarirAhmed\PhpLlm\Exceptions\LlmException;

/**
 * Fluent, Guzzle-backed request builder mirroring the slice of Laravel's
 * PendingRequest that the drivers use. Bodies default to JSON; calling attach()
 * switches to multipart/form-data.
 */
class PendingRequest
{
    protected array $headers = [];

    protected ?string $baseUrl = null;

    protected int $timeout = 30;

    protected array $attachments = [];

    protected int $retryTimes = 1;

    protected int $retrySleepMs = 0;

    protected bool $throwOnError = false;

    protected static ?GuzzleClient $sharedClient = null;

    /** Allow tests to inject a mock Guzzle client. */
    public static function useClient(?GuzzleClient $client): void
    {
        static::$sharedClient = $client;
    }

    public function withToken(string $token, string $type = 'Bearer'): static
    {
        $this->headers['Authorization'] = trim($type.' '.$token);

        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;

        return $this;
    }

    public function baseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');

        return $this;
    }

    public function attach(string $name, mixed $contents, ?string $filename = null): static
    {
        $part = ['name' => $name, 'contents' => $contents];

        if ($filename !== null) {
            $part['filename'] = $filename;
        }

        $this->attachments[] = $part;

        return $this;
    }

    /** Retry transient failures (timeouts / 429 / 5xx) up to $times attempts. */
    public function retry(int $times, int $sleepMilliseconds = 0): static
    {
        $this->retryTimes = max(1, $times);
        $this->retrySleepMs = max(0, $sleepMilliseconds);

        return $this;
    }

    public function throw(): static
    {
        $this->throwOnError = true;

        return $this;
    }

    public function get(string $url, array $query = []): Response
    {
        return $this->send('GET', $url, ['query' => $query]);
    }

    public function post(string $url, array $data = []): Response
    {
        return $this->send('POST', $url, $this->bodyOptions($data));
    }

    public function put(string $url, array $data = []): Response
    {
        return $this->send('PUT', $url, $this->bodyOptions($data));
    }

    public function patch(string $url, array $data = []): Response
    {
        return $this->send('PATCH', $url, $this->bodyOptions($data));
    }

    public function delete(string $url, array $data = []): Response
    {
        return $this->send('DELETE', $url, $this->bodyOptions($data));
    }

    /**
     * Stream Server-Sent Events line by line as a generator. Used by the
     * unified streaming layer so callers get tokens as they arrive instead of
     * waiting for the whole body.
     *
     * @return iterable<string> raw "data:" payloads (without the prefix)
     */
    public function stream(string $method, string $url, array $data = []): iterable
    {
        $options = $this->bodyOptions($data);
        $options['stream'] = true;
        $response = $this->dispatch($method, $url, $options);
        $stream = $response->toPsr()->getBody();

        $buffer = '';
        while (! $stream->eof()) {
            $buffer .= $stream->read(1024);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (str_starts_with($line, 'data:')) {
                    $payload = trim(substr($line, 5));
                    if ($payload !== '' && $payload !== '[DONE]') {
                        yield $payload;
                    } elseif ($payload === '[DONE]') {
                        return;
                    }
                }
            }
        }
    }

    protected function bodyOptions(array $data): array
    {
        if ($this->attachments !== []) {
            $multipart = $this->attachments;
            foreach ($data as $key => $value) {
                $multipart[] = ['name' => $key, 'contents' => is_array($value) ? json_encode($value) : (string) $value];
            }

            return ['multipart' => $multipart];
        }

        return ['json' => $data];
    }

    protected function send(string $method, string $url, array $options): Response
    {
        return $this->dispatch($method, $url, $options);
    }

    protected function dispatch(string $method, string $url, array $options): Response
    {
        if ($this->baseUrl !== null && ! preg_match('#^https?://#i', $url)) {
            $url = $this->baseUrl.'/'.ltrim($url, '/');
        }

        $client = static::$sharedClient ?? new GuzzleClient;

        $options['headers'] = array_merge($this->headers, $options['headers'] ?? []);
        $options['timeout'] = $this->timeout;
        $options['http_errors'] = false;

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryTimes) {
            $attempt++;

            try {
                $psr = $client->request($method, $url, $options);
                $response = new Response($psr);

                // Retry transient failures: 5xx and 429 (rate limit / queue full).
                if (($response->serverError() || $response->status() === 429) && $attempt < $this->retryTimes) {
                    $this->pause();
                    continue;
                }

                if ($this->throwOnError) {
                    $response->throw();
                }

                return $response;
            } catch (GuzzleException $e) {
                $lastException = $e;
                if ($attempt < $this->retryTimes) {
                    $this->pause();
                    continue;
                }
            }
        }

        throw LlmException::apiError('http', $lastException?->getMessage() ?? 'request failed');
    }

    protected function pause(): void
    {
        if ($this->retrySleepMs > 0) {
            usleep($this->retrySleepMs * 1000);
        }
    }
}
