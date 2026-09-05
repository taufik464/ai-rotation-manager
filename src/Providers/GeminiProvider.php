<?php

namespace YourVendor\AIRotationManager\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use YourVendor\AIRotationManager\Contracts\AIServiceInterface;
use YourVendor\AIRotationManager\Services\AIServiceException;

class GeminiProvider implements AIServiceInterface
{
    public function __construct(private array $config = []) {}

    public function generate(string $prompt, array $options = []): string
    {
        $apiKey = (string) ($options['api_key'] ?? '');
        $model = $options['model'] ?? $this->config['model'] ?? 'gemini-1.5-flash';
        $payload = [
            'contents' => [[
                'parts' => [['text' => $prompt]],
            ]],
        ];

        if (array_key_exists('temperature', $options)) {
            $payload['generationConfig']['temperature'] = $options['temperature'];
        }
        if (array_key_exists('max_tokens', $options)) {
            $payload['generationConfig']['maxOutputTokens'] = $options['max_tokens'];
        }

        $response = $this->request($apiKey)->post(
            rtrim($this->config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta', '/')
                . '/models/' . rawurlencode($model) . ':generateContent',
            $payload
        )->wait();

        $this->ensureSuccessful($response);
        $content = $response->json('candidates.0.content.parts.0.text');

        if (!is_string($content)) {
            throw new AIServiceException('Gemini returned an invalid response payload.');
        }

        return $content;
    }

    private function request(string $apiKey): PendingRequest
    {
        return Http::async()
            ->withQueryParameters(['key' => $apiKey])
            ->acceptJson()
            ->timeout((int) ($this->config['timeout'] ?? 30));
    }

    private function ensureSuccessful(Response $response): void
    {
        if (!$response->successful()) {
            throw new AIServiceException(
                'Gemini request failed with HTTP ' . $response->status() . '.',
                $response->status()
            );
        }
    }
}
