<?php

namespace Tests\Unit\Rendering;

use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;
use PHPUnit\Framework\TestCase;

class ChatMessagesRendererTest extends TestCase
{
    private function render(PromptSpec $spec): array
    {
        return (new ChatMessagesRenderer())->render($spec);
    }

    public function test_an_empty_spec_yields_no_messages(): void
    {
        $this->assertSame([], $this->render(new PromptSpec()));
    }

    public function test_composition_becomes_a_single_system_message(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'Prof';
        $spec->context = 'Cours';

        $messages = $this->render($spec);

        $this->assertCount(1, $messages);
        $this->assertSame('system', $messages[0]['role']);
        $this->assertStringContainsString('Prof', $messages[0]['content']);
        $this->assertStringContainsString('Cours', $messages[0]['content']);
    }

    public function test_the_question_is_the_final_user_message_and_not_in_system(): void
    {
        $spec = new PromptSpec();
        $spec->context = 'Cours';
        $spec->question = 'Quelle est la capitale ?';

        $messages = $this->render($spec);

        $last = $messages[array_key_last($messages)];
        $this->assertSame('user', $last['role']);
        $this->assertSame('Quelle est la capitale ?', $last['content']);

        // The question must not leak into the system message.
        $this->assertStringNotContainsString('Quelle est la capitale ?', $messages[0]['content']);
    }

    public function test_history_is_threaded_between_system_and_question(): void
    {
        $spec = new PromptSpec();
        $spec->context = 'Cours';
        $spec->history = [
            ['role' => 'user', 'content' => 'Bonjour'],
            ['role' => 'assistant', 'content' => 'Salut !'],
        ];
        $spec->question = 'Et après ?';

        $messages = $this->render($spec);

        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($messages, 'role'));
        $this->assertSame('Bonjour', $messages[1]['content']);
        $this->assertSame('Salut !', $messages[2]['content']);
        $this->assertSame('Et après ?', $messages[3]['content']);
    }

    public function test_a_question_only_spec_has_no_system_message(): void
    {
        $spec = new PromptSpec();
        $spec->question = 'Juste une question ?';

        $messages = $this->render($spec);

        $this->assertSame([['role' => 'user', 'content' => 'Juste une question ?']], $messages);
    }

    public function test_history_entries_default_missing_role_to_user(): void
    {
        $spec = new PromptSpec();
        $spec->context = 'x';
        $spec->history = [['content' => 'sans rôle']];

        $messages = $this->render($spec);

        $this->assertSame('user', $messages[1]['role']);
        $this->assertSame('sans rôle', $messages[1]['content']);
    }

    public function test_a_custom_system_renderer_can_be_injected(): void
    {
        $stub = new class implements RendererInterface {
            public function render(PromptSpec $spec): string|array
            {
                return 'SYSTEME FIGÉ';
            }
        };

        $spec = new PromptSpec();
        $spec->persona = 'ignoré';
        $spec->question = 'Q';

        $messages = (new ChatMessagesRenderer($stub))->render($spec);

        $this->assertSame('SYSTEME FIGÉ', $messages[0]['content']);
        $this->assertSame('Q', $messages[1]['content']);
    }
}
