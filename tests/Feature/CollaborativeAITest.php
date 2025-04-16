<?php

// tests/Feature/CollaborativeAITest.php
namespace Tests\Feature;

use Tests\TestCase;
use PromptBuilder\PromptBuilder;

class CollaborativeAITest extends TestCase
{
    public function testCollaborativeAI()
    {
        // Tester une collaboration entre plusieurs IA
        $response = PromptBuilder::make()
            ->for(['chatgpt', 'deepseek'])
            ->language('fr')
            ->tone('neutre')
            ->ask('Quels sont les défis de l\'intelligence artificielle ?')
            ->run();

        // Vérifier que la réponse des deux IA est présente
        $this->assertStringContainsString('Réponse de chatgpt', $response);
        $this->assertStringContainsString('Réponse de deepseek', $response);
    }
}