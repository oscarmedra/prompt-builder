<?php
// packages/promot-builder/tests/Feature/PromptBuilderTest.php

// tests/Unit/PromptBuilderTest.php
namespace Tests\Unit;

use PromptBuilder\PromptBuilder;
use PHPUnit\Framework\TestCase;

class PromptBuilderTest extends TestCase
{
    public function testSimplePrompt()
    {
        // Test de base pour vérifier si le prompt fonctionne
        $promptBuilder = PromptBuilder::make()
            ->for(['chatgpt'])  // Modèle IA
            ->language('fr')
            ->tone('neutre')
            ->ask('Quel est le sens de la vie ?');

        // Supposons que la réponse de l'IA contient le terme "vie"
        $response = $promptBuilder->run();
        $this->assertStringContainsString('la vie', $response);
    }

    public function testMultipleModels()
    {
        // Test avec plusieurs modèles
        $promptBuilder = PromptBuilder::make()
            ->for(['chatgpt', 'deepseek'])  // Deux modèles
            ->language('fr')
            ->tone('neutre')
            ->ask('Quel est l\'impact de l\'IA sur l\'économie ?');

        // Supposons que les deux IA répondent d'une manière spécifique
        $response = $promptBuilder->run();
        $this->assertStringContainsString('Réponse de chatgpt', $response);
        $this->assertStringContainsString('Réponse de deepseek', $response);
    }
}