<?php

namespace NoahMedra\PromptBuilder\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;

/**
 * Sends a PromptSpec to a local Ollama /api/chat endpoint.
 *
 * Fixes vs. the original implementation:
 *  - actually renders and sends the built prompt (it previously sent a
 *    hardcoded "Bonjour toi" message, discarding everything the caller
 *    composed with PromptBuilder)
 *  - uses a proper chat "messages" array via ChatMessagesRenderer instead
 *    of flattening everything into one user message
 *  - drops the incorrect `Content-Type: application/x-www-form-urlencoded`
 *    header on what is actually a JSON request body
 *  - has a configurable timeout instead of blocking indefinitely if
 *    Ollama is unreachable
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
