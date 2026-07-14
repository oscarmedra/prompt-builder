<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\Drivers\Laravel\OllamaDriver;
use NoahMedra\PromptBuilder\PromptBuilder;
use Tests\TestCase;

/**
 * The Laravel-flavoured driver, tested with Http::fake() inside a booted
 * app — the scenario this variant exists for.
 */
class LaravelOllamaDriverTest extends TestCase
{
    public function test_it_sends_the_composed_prompt_and_wraps_the_faked_response(): void
    {
        Http::fake([
            'localhost:11434/*' => Http::response([
                'message' => ['role' => 'assistant', 'content' => 'Paris'],
            ], 200),
        ]);

        $builder = PromptBuilder::make()
            ->persona('Professeur de géographie')
            ->context('Quiz sur les capitales')
            ->ask('Quelle est la capitale de la France ?')
            ->driver(new OllamaDriver())
            ->process();

        $this->assertSame('Paris', $builder->getOutput()->get('message.content'));

        Http::assertSent(function ($request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return $request->url() === 'http://localhost:11434/api/chat'
                && $request['model'] === 'llama3.1'
                && str_contains($body, 'Professeur de géographie')
                && str_contains($body, 'Quiz sur les capitales')
                && str_contains($body, 'Quelle est la capitale de la France ?');
        });
    }

    public function test_a_failed_response_becomes_an_error_output(): void
    {
        Http::fake([
            'localhost:11434/*' => Http::response('boom', 500),
        ]);

        $builder = PromptBuilder::make()
            ->ask('Capitale ?')
            ->driver(new OllamaDriver())
            ->process();

        $this->assertStringContainsString('boom', $builder->getOutput()->get('error'));
    }
}
