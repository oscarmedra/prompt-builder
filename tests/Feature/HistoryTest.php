<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\History\InMemoryHistoryStore;
use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use PHPUnit\Framework\TestCase;

/** A driver that returns a canned assistant reply and records what it got. */
class CannedDriver implements PromptDriverInterface
{
    public ?PromptSpec $received = null;

    public function __construct(private readonly string $reply = 'Réponse simulée')
    {
    }

    public function process(PromptSpec $spec): BuilderOutput
    {
        $this->received = $spec;

        return new BuilderOutput(json_encode([
            'message' => ['role' => 'assistant', 'content' => $this->reply],
        ]));
    }
}

class HistoryTest extends TestCase
{
    public function test_process_records_the_question_and_the_assistant_reply(): void
    {
        $store = new InMemoryHistoryStore();

        PromptBuilder::make()
            ->useHistory($store)
            ->driver(new CannedDriver('Paris'))
            ->ask('Quelle est la capitale de la France ?')
            ->process();

        $this->assertSame([
            ['role' => 'user', 'content' => 'Quelle est la capitale de la France ?'],
            ['role' => 'assistant', 'content' => 'Paris'],
        ], $store->all());
    }

    public function test_a_second_turn_actually_receives_the_first_exchange(): void
    {
        $store = new InMemoryHistoryStore();

        // Turn 1
        PromptBuilder::make()
            ->useHistory($store)
            ->driver(new CannedDriver('Paris'))
            ->ask('Quelle est la capitale de la France ?')
            ->process();

        // Turn 2 — a brand new builder, same store.
        $secondDriver = new CannedDriver('Environ 2,1 millions.');
        PromptBuilder::make()
            ->useHistory($store)
            ->driver($secondDriver)
            ->ask('Et sa population ?')
            ->process();

        // Prove the model on turn 2 was actually SENT turn 1's exchange,
        // not just that no exception was thrown.
        $messages = (new ChatMessagesRenderer())->render($secondDriver->received);
        $rendered = implode("\n", array_column($messages, 'content'));

        $this->assertStringContainsString('Quelle est la capitale de la France ?', $rendered);
        $this->assertStringContainsString('Paris', $rendered);
        $this->assertStringContainsString('Et sa population ?', $rendered);

        // And the roles are threaded through as real chat turns.
        $roles = array_column($messages, 'role');
        $this->assertContains('assistant', $roles);
    }

    public function test_without_use_history_nothing_is_recorded(): void
    {
        $store = new InMemoryHistoryStore();

        // No useHistory() call: the store must stay untouched.
        PromptBuilder::make()
            ->driver(new CannedDriver('x'))
            ->ask('Une question ?')
            ->process();

        $this->assertSame([], $store->all());
    }

    public function test_raw_response_is_stored_when_reply_is_not_chat_shaped(): void
    {
        $store = new InMemoryHistoryStore();

        // Driver returns a non-chat JSON payload -> falls back to raw body.
        $driver = new class implements PromptDriverInterface {
            public function process(PromptSpec $spec): BuilderOutput
            {
                return new BuilderOutput('{"ok": true}');
            }
        };

        PromptBuilder::make()->useHistory($store)->driver($driver)->ask('Q')->process();

        $this->assertSame('{"ok": true}', $store->all()[1]['content']);
    }
}
