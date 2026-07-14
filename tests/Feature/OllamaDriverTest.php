<?php

namespace Tests\Feature;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;
use NoahMedra\PromptBuilder\PromptBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The framework-agnostic Guzzle driver, tested with a MockHandler so it
 * makes no real network call and needs no booted Laravel app.
 */
class OllamaDriverTest extends TestCase
{
    public function test_it_sends_the_actual_composed_prompt_to_ollama(): void
    {
        $transactions = [];
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'message' => ['role' => 'assistant', 'content' => 'Paris'],
            ])),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($transactions));
        $client = new Client(['handler' => $stack]);

        $driver = new OllamaDriver(
            model: 'llama3.1',
            endpoint: 'http://localhost:11434/api/chat',
            client: $client,
        );

        PromptBuilder::make()
            ->persona('Professeur de géographie')
            ->context('Quiz sur les capitales')
            ->ask('Quelle est la capitale de la France ?')
            ->driver($driver)
            ->process();

        $this->assertCount(1, $transactions);
        $request = $transactions[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $payload = json_decode((string) $request->getBody(), true);
        $this->assertSame('llama3.1', $payload['model']);
        $this->assertFalse($payload['stream']);

        // The whole point: the composed prompt is really in the request,
        // not a hardcoded message.
        $sent = json_encode($payload['messages'], JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Professeur de géographie', $sent);
        $this->assertStringContainsString('Quiz sur les capitales', $sent);
        $this->assertStringContainsString('Quelle est la capitale de la France ?', $sent);
    }

    public function test_the_response_body_is_wrapped_in_the_output(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['message' => ['content' => 'Paris']])),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $builder = PromptBuilder::make()
            ->ask('Capitale ?')
            ->driver(new OllamaDriver(client: $client))
            ->process();

        $this->assertSame('Paris', $builder->getOutput()->get('message.content'));
    }

    public function test_network_failure_is_captured_as_an_error_output(): void
    {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection refused',
                new \GuzzleHttp\Psr7\Request('POST', 'http://localhost:11434/api/chat')
            ),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $builder = PromptBuilder::make()
            ->ask('Capitale ?')
            ->driver(new OllamaDriver(client: $client))
            ->process();

        $this->assertStringContainsString('Connection refused', $builder->getOutput()->get('error'));
    }
}
