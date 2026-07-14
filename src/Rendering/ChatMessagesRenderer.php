<?php

namespace NoahMedra\PromptBuilder\Rendering;

use NoahMedra\PromptBuilder\PromptSpec;

/**
 * Renders a PromptSpec as an array of {role, content} messages, the
 * shape expected by chat-completion APIs (Ollama's /api/chat, OpenAI,
 * Anthropic, etc.):
 *
 *   [
 *     ['role' => 'system', 'content' => '...persona + context + instructions...'],
 *     ['role' => 'user', 'content' => '...previous turn...'],
 *     ['role' => 'assistant', 'content' => '...previous turn...'],
 *     ['role' => 'user', 'content' => '...the actual question...'],
 *   ]
 */
class ChatMessagesRenderer implements RendererInterface
{
    public function __construct(
        private readonly RendererInterface $systemRenderer = new TextRenderer(),
    ) {
    }

    /** @return array<int, array{role: string, content: string}> */
    public function render(PromptSpec $spec): array
    {
        $messages = [];

        // Everything except the question becomes the system message.
        $systemSpec = clone $spec;
        $systemSpec->question = null;

        $system = $this->systemRenderer->render($systemSpec);

        if (is_string($system) && $system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }

        foreach ($spec->history as $entry) {
            $messages[] = [
                'role' => $entry['role'] ?? 'user',
                'content' => $entry['content'] ?? '',
            ];
        }

        if ($spec->question) {
            $messages[] = ['role' => 'user', 'content' => $spec->question];
        }

        return $messages;
    }
}
