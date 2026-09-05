<?php

namespace YourVendor\AIRotationManager\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Container\Container;
use Throwable;
use YourVendor\AIRotationManager\Contracts\AIServiceInterface;

class AIRotationManager
{
    public function __construct(
        private Container $container,
        private CacheRepository $cache,
        private array $config = []
    ) {}

    public function generate(string $prompt, array $options = []): string
    {
        $providers = $this->config['priority'] ?? array_keys($this->config['providers'] ?? []);
        $lastException = null;

        foreach ($providers as $providerName) {
            $providerConfig = $this->config['providers'][$providerName] ?? null;
            if (!is_array($providerConfig)) {
                continue;
            }

            $keys = $this->availableKeys($providerName, $providerConfig['api_keys'] ?? []);
            foreach ($keys as $key) {
                try {
                    $provider = $this->resolveProvider($providerConfig);
                    return $provider->generate($prompt, array_merge(
                        $this->config['default_options'] ?? [],
                        $options,
                        ['api_key' => $key]
                    ));
                } catch (Throwable $exception) {
                    $lastException = $exception;
                    $status = $this->statusCode($exception);
                    if (!$this->isRotatableStatus($status)) {
                        throw $exception;
                    }

                    $this->cooldown($providerName, (string) $key);
                }
            }
        }

        if ($lastException !== null) {
            throw $lastException;
        }

        throw new AIServiceException('No AI provider has an available API key.');
    }

    /** @return array<int, string> */
    private function availableKeys(string $providerName, array|string $keys): array
    {
        if (is_string($keys)) {
            $keys = explode(',', $keys);
        }

        $keys = array_values(array_filter(array_map('trim', $keys)));
        if ($keys === []) {
            return [];
        }

        $cursorKey = $this->cursorCacheKey($providerName);
        $cursor = (int) $this->cache->get($cursorKey, 0);
        $orderedKeys = [];
        $now = time();

        for ($offset = 0, $count = count($keys); $offset < $count; $offset++) {
            $index = ($cursor + $offset) % $count;
            $key = $keys[$index];
            $cooldownUntil = (int) $this->cache->get($this->cooldownCacheKey($providerName, $key), 0);
            if ($cooldownUntil <= $now) {
                $orderedKeys[] = $key;
            }
        }

        return $orderedKeys;
    }

    private function resolveProvider(array $providerConfig): AIServiceInterface
    {
        $class = $providerConfig['class'] ?? null;
        if (!is_string($class) || !is_a($class, AIServiceInterface::class, true)) {
            throw new AIServiceException('Configured AI provider must implement AIServiceInterface.');
        }

        return $this->container->make($class, ['config' => $providerConfig]);
    }

    private function cooldown(string $providerName, string $key): void
    {
        $seconds = max(0, (int) ($this->config['cooldown_seconds'] ?? 60));
        $this->cache->put($this->cooldownCacheKey($providerName, $key), time() + $seconds, $seconds);
        $this->cache->increment($this->cursorCacheKey($providerName));
    }

    private function isRotatableStatus(?int $status): bool
    {
        return $status === 429 || $status === 401 || ($status !== null && $status >= 500 && $status <= 599);
    }

    private function statusCode(Throwable $exception): ?int
    {
        if ($exception instanceof AIServiceException) {
            return $exception->statusCode();
        }

        return null;
    }

    private function cursorCacheKey(string $providerName): string
    {
        return 'ai-rotation:' . $providerName . ':cursor';
    }

    private function cooldownCacheKey(string $providerName, string $key): string
    {
        return 'ai-rotation:' . $providerName . ':cooldown:' . hash('sha256', $key);
    }
}
