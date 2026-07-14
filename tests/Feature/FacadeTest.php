<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\Facades\PromptBuilder;
use NoahMedra\PromptBuilder\PromptBuilder as ConcretePromptBuilder;
use Tests\TestCase;

/**
 * Proves the Facade -> ServiceProvider -> container wiring actually works
 * inside a booted Laravel application (via Orchestra Testbench), which is
 * the one thing plain PHPUnit tests can't exercise.
 */
class FacadeTest extends TestCase
{
    public function test_the_binding_is_registered(): void
    {
        $this->assertTrue($this->app->bound('promptbuilder'));
        $this->assertInstanceOf(ConcretePromptBuilder::class, $this->app->make('promptbuilder'));
    }

    public function test_facade_make_composes_a_prompt_in_a_booted_app(): void
    {
        $prompt = PromptBuilder::make()
            ->persona('Assistant de test')
            ->context('Contexte via la façade')
            ->must('Réponds en français')
            ->ask('Une question via la façade ?')
            ->toPrompt();

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Assistant de test', $prompt);
        $this->assertStringContainsString('Contexte via la façade', $prompt);
        $this->assertStringContainsString('[Obligatoire] Réponds en français', $prompt);
        $this->assertStringContainsString('Une question via la façade ?', $prompt);
    }

    public function test_each_facade_make_returns_an_isolated_builder(): void
    {
        $first = PromptBuilder::make()->ask('Première ?')->toPrompt();
        $second = PromptBuilder::make()->ask('Seconde ?')->toPrompt();

        // No shared mutable state leaking between two facade call-chains.
        $this->assertStringContainsString('Première ?', $first);
        $this->assertStringNotContainsString('Première ?', $second);
        $this->assertStringContainsString('Seconde ?', $second);
    }
}
