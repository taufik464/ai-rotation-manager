<?php

namespace YourVendor\AIRotationManager\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use YourVendor\AIRotationManager\Contracts\AIServiceInterface;
use YourVendor\AIRotationManager\Services\AIServiceException;

class GroqProvider implements AIServiceInterface
{
    public function __construct(private array $config = []) {}

    public function generate(string $prompt, array $options = []): string
    {
        $apiKey = $options['api_key'] ?? null;
        $model = $options['model'] ?? $this->config['model'] ?? 'llama-3.1-8b-instant';
        $payload = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];

        foreach (['temperature', 'max_tokens', 'top_p', 'stop'] as $option) {
            if (array_key_exists($option, $options)) {
                $payload[$option] = $options[$option];
            }
        }

        $response = $this->request($apiKey)->post(
            rtrim($this->config['base_url'] ?? 'https://api.groq.com/openai/v1', '/') . '/chat/completions',
            $payload
        )->wait();

        $this->ensureSuccessful($response);
        $content = $response->json('choices.0.message.content');

        if (!is_string($content)) {
            throw new AIServiceException('Groq returned an invalid response payload.');
        }

        return $content;
    }

    private function request(?string $apiKey): PendingRequest
    {
        return Http::async()
            ->withToken((string) $apiKey)
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 30));
    }

    private function ensureSuccessful(Response $response): void
    {
        if (!$response->successful()) {
            throw new AIServiceException(
                'Groq request failed with HTTP ' . $response->status() . '.',
                $response->status()
            );
        }
    }
}
