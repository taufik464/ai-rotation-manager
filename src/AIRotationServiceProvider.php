<?php

namespace YourVendor\AIRotationManager;

use Illuminate\Support\ServiceProvider;
use YourVendor\AIRotationManager\Services\AIRotationManager;

class AIRotationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-rotation.php', 'ai-rotation');

        $this->app->singleton(AIRotationManager::class, function ($app) {
            return new AIRotationManager(
                $app,
                $app->make('cache.store'),
                $app['config']->get('ai-rotation', [])
            );
        });

        $this->app->alias(AIRotationManager::class, 'ai-rotation');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ai-rotation.php' => config_path('ai-rotation.php'),
            ], 'ai-rotation-config');
        }
    }
}
