<?php

namespace NoahMedra\PromptBuilder\Drivers\Laravel;

use Exception;
use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;

/**
 * Laravel-flavoured Ollama driver, built on the Http facade.
 *
 * Behaviourally identical to the framework-agnostic Drivers\OllamaDriver,
 * but useful when you're already in a Laravel app and want to assert on
 * the outgoing request with Http::fake() in your tests. It requires a
 * booted application (the Http facade must be bound), so it will NOT work
 * in a plain PHP script — use Drivers\OllamaDriver there.
 */
class OllamaDriver implements PromptDriverInterface
{
    public function __construct(
        private readonly string $model = 'llama3.1',
        private readonly string $endpoint = 'http://localhost:11434/api/chat',
        private readonly RendererInterface $renderer = new ChatMessagesRenderer(),
        private readonly int $timeoutSeconds = 30,
    ) {
    }

    public function process(PromptSpec $spec): BuilderOutput
    {
        $messages = $this->renderer->render($spec);

        try {
            $response = Http::acceptJson()
                ->timeout($this->timeoutSeconds)
                ->post($this->endpoint, [
                    'model' => $this->model,
                    'stream' => false,
                    'messages' => $messages,
                ]);

            if ($response->failed()) {
                throw new Exception($response->body());
            }

            return new BuilderOutput($response->body());
        } catch (Exception $e) {
            return new BuilderOutput(json_encode(['error' => $e->getMessage()]));
        }
    }
}
