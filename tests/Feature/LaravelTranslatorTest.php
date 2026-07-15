<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use NoahMedra\PromptBuilder\Translation\Laravel\LaravelTranslator;
use Tests\TestCase;

/**
 * The optional Laravel translation bridge, exercised in a booted app where
 * the service provider has registered the 'promptbuilder' translation
 * namespace.
 */
class LaravelTranslatorTest extends TestCase
{
    private function translator(): LaravelTranslator
    {
        return new LaravelTranslator($this->app['translator']);
    }

    public function test_it_reads_bundled_labels_from_the_promptbuilder_namespace(): void
    {
        $t = $this->translator();

        $this->assertSame('Rôle', $t->get('section.role', 'fr'));
        $this->assertSame('Rol', $t->get('section.role', 'es'));
        $this->assertSame('必须', $t->get('label.must', 'zh'));
    }

    public function test_a_null_locale_follows_the_app_locale(): void
    {
        $this->app->setLocale('de');

        $this->assertSame('Kontext', $this->translator()->get('section.context'));
    }

    public function test_a_missing_key_returns_the_key(): void
    {
        $this->assertSame('section.unknown', $this->translator()->get('section.unknown', 'en'));
    }

    public function test_it_can_drive_a_text_renderer_end_to_end(): void
    {
        $renderer = new TextRenderer($this->translator());

        $prompt = $renderer->render(
            PromptBuilder::make()
                ->context('Cours')
                ->must('Cite tes sources')
                ->language('fr')
                ->ask('Pourquoi ?')
                ->getSpec()
        );

        $this->assertStringContainsString('# Contexte', $prompt);
        $this->assertStringContainsString('[Obligatoire] Cite tes sources', $prompt);
        $this->assertStringContainsString('# Question', $prompt);
    }

    public function test_published_lang_files_can_override_bundled_strings(): void
    {
        // Simulate an app overriding one line for the 'promptbuilder' namespace.
        $this->app['translator']->addLines(
            ['labels.section.role' => 'Persona'],
            'en',
            'promptbuilder'
        );

        $this->assertSame('Persona', $this->translator()->get('section.role', 'en'));
    }
}
