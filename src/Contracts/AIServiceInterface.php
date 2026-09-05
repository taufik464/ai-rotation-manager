<?php

namespace YourVendor\AIRotationManager\Contracts;

interface AIServiceInterface
{
    /**
     * Generate text for a prompt.
     *
     * The manager passes the selected key in the options array as api_key.
     */
    public function generate(string $prompt, array $options = []): string;
}
