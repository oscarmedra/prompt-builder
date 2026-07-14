<?php

namespace Tests\Unit\History;

use NoahMedra\PromptBuilder\History\HistoryStoreInterface;
use NoahMedra\PromptBuilder\History\InMemoryHistoryStore;
use PHPUnit\Framework\TestCase;

class InMemoryHistoryStoreTest extends TestCase
{
    public function test_it_is_a_history_store(): void
    {
        $this->assertInstanceOf(HistoryStoreInterface::class, new InMemoryHistoryStore());
    }

    public function test_a_new_store_is_empty(): void
    {
        $this->assertSame([], (new InMemoryHistoryStore())->all());
    }

    public function test_push_appends_role_content_pairs_in_order(): void
    {
        $store = new InMemoryHistoryStore();
        $store->push('user', 'Bonjour');
        $store->push('assistant', 'Salut !');

        $this->assertSame([
            ['role' => 'user', 'content' => 'Bonjour'],
            ['role' => 'assistant', 'content' => 'Salut !'],
        ], $store->all());
    }

    public function test_it_can_be_seeded_via_the_constructor(): void
    {
        $seed = [['role' => 'user', 'content' => 'déjà là']];
        $store = new InMemoryHistoryStore($seed);

        $this->assertSame($seed, $store->all());

        $store->push('assistant', 'suite');
        $this->assertCount(2, $store->all());
    }

    public function test_clear_empties_the_store(): void
    {
        $store = new InMemoryHistoryStore([['role' => 'user', 'content' => 'x']]);
        $store->clear();

        $this->assertSame([], $store->all());
    }
}
