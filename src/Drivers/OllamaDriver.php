<?php

namespace NoahMedra\PromptBuilder\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;
use Throwable;

/**
 * Framework-agnostic Ollama driver: talks to a local /api/chat endpoint
 * using Guzzle directly, so it works in a plain PHP script with no booted
 * Laravel application.
 *
 * If you're inside a Laravel app and want Http::fake() in your tests, use
 * Drivers\Laravel\OllamaDriver instead — it is behaviourally identical but
 * built on the Http facade.
 *
 * A Guzzle client can be injected (mainly for testing with a MockHandler);
 * otherwise a default client is created.
 */
class OllamaDriver implements PromptDriverInterface
{
    private ClientInterface $client;

    public function __construct(
        private readonly string $model = 'llama3.1',
        private readonly string $endpoint = 'http://localhost:11434/api/chat',
        private readonly RendererInterface $renderer = new ChatMessagesRenderer(),
        private readonly int $timeoutSeconds = 30,
        ?ClientInterface $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    public function process(PromptSpec $spec): BuilderOutput
    {
        $messages = $this->renderer->render($spec);

        try {
            $response = $this->client->request('POST', $this->endpoint, [
                'headers' => ['Accept' => 'application/json'],
                'timeout' => $this->timeoutSeconds,
                'json' => [
                    'model' => $this->model,
                    'stream' => false,
                    'messages' => $messages,
                ],
            ]);

            return new BuilderOutput((string) $response->getBody());
        } catch (Throwable $e) {
            return new BuilderOutput(json_encode(['error' => $e->getMessage()]));
        }
    }
}
