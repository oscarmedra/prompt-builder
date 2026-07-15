<?php

namespace NoahMedra\PromptBuilder\Translation;

/**
 * Resolves a renderer label (e.g. 'section.role', 'label.must') into a
 * localized string.
 *
 * This abstraction is what keeps the rendering layer framework-free: the
 * default implementation (ArrayTranslator) reads bundled PHP language files
 * with no dependency on Laravel, while an optional Laravel-backed
 * implementation (Translation\Laravel\LaravelTranslator) plugs the package
 * into the app's own translation system.
 */
interface TranslatorInterface
{
    /**
     * @param string      $key    Dotted label key, e.g. 'section.context'
     * @param string|null $locale Target locale; null means the translator's default
     */
    public function get(string $key, ?string $locale = null): string;
}
