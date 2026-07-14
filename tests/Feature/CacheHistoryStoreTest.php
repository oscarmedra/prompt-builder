<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\History\Laravel\CacheHistoryStore;
use NoahMedra\PromptBuilder\PromptBuilder;
use Tests\TestCase;

/**
 * Exercises the optional Laravel-backed history store against a real
 * (booted) cache repository provided by Testbench.
 */
class CacheHistoryStoreTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
    }

    public function test_push_and_all_round_trip_through_the_cache(): void
    {
        $store = new CacheHistoryStore('conv-1');
        $store->push('user', 'Bonjour');
        $store->push('assistant', 'Salut !');

        $this->assertSame([
            ['role' => 'user', 'content' => 'Bonjour'],
            ['role' => 'assistant', 'content' => 'Salut !'],
        ], $store->all());
    }

    public function test_two_stores_with_different_ids_are_isolated(): void
    {
        (new CacheHistoryStore('conv-a'))->push('user', 'A');
        (new CacheHistoryStore('conv-b'))->push('user', 'B');

        $this->assertSame('A', (new CacheHistoryStore('conv-a'))->all()[0]['content']);
        $this->assertSame('B', (new CacheHistoryStore('conv-b'))->all()[0]['content']);
    }

    public function test_clear_empties_the_conversation(): void
    {
        $store = new CacheHistoryStore('conv-clear');
        $store->push('user', 'x');
        $store->clear();

        $this->assertSame([], $store->all());
    }

    public function test_prompt_builder_persists_a_turn_via_the_cache_store(): void
    {
        $store = new CacheHistoryStore('conv-builder');

        PromptBuilder::make()
            ->useHistory($store)
            ->driver(new CannedDriver('Paris'))
            ->ask('Capitale de la France ?')
            ->process();

        // A fresh store instance pointed at the same conversation id sees it.
        $reloaded = new CacheHistoryStore('conv-builder');
        $this->assertSame([
            ['role' => 'user', 'content' => 'Capitale de la France ?'],
            ['role' => 'assistant', 'content' => 'Paris'],
        ], $reloaded->all());
    }
}
