<?php

namespace YourVendor\AIRotationManager\Tests;

use Illuminate\Support\Facades\Http;
use YourVendor\AIRotationManager\Facades\AIRotation;

class AIRotationTest extends TestCase
{
    public function test_it_rotates_to_the_next_groq_key_after_a_rate_limit(): void
    {
        config()->set('ai-rotation.providers.groq.api_keys', ['groq-key-1', 'groq-key-2']);
        config()->set('ai-rotation.providers.gemini.api_keys', ['gemini-key-1']);
        config()->set('ai-rotation.cooldown_seconds', 300);

        Http::fake([
            'api.groq.com/*' => Http::sequence()
                ->pushStatus(429)
                ->push(['choices' => [['message' => ['content' => 'from key two']]],], 200),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'from gemini']]]]],
            ], 200),
        ]);

        $result = AIRotation::generate('Say hello');

        $this->assertSame('from key two', $result);
        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
                && $request->header('Authorization') === ['Bearer groq-key-2'];
        });
    }

    public function test_it_falls_back_to_gemini_when_all_groq_keys_are_rate_limited(): void
    {
        config()->set('ai-rotation.providers.groq.api_keys', ['groq-key-1']);
        config()->set('ai-rotation.providers.gemini.api_keys', ['gemini-key-1']);

        Http::fake([
            'api.groq.com/*' => Http::response([], 429),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'gemini fallback']]]]],
            ], 200),
        ]);

        $this->assertSame('gemini fallback', AIRotation::generate('Fallback please'));
        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && str_contains($request->url(), 'key=gemini-key-1');
        });
    }

    //membuat sebuah konten tentang barang
    public function test_it_generates_content_about_a_product(): void
    {
        config()->set('ai-rotation.providers.groq.api_keys', ['groq-key-1']);
        config()->set('ai-rotation.providers.gemini.api_keys', ['gemini-key-1']);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'This is a great product!']],],
            ], 200),
        ]);

        $result = AIRotation::generate('Write a product description for a new gadget.');

        $this->assertSame('This is a great product!', $result);
        Http::assertSentCount(1);
    }
}
