<?php

namespace NoahMedra\PromptBuilder\Translation\Laravel;

use Illuminate\Contracts\Translation\Translator;
use NoahMedra\PromptBuilder\Translation\TranslatorInterface;

/**
 * Optional bridge to Laravel's translation system. Reads labels from the
 * 'promptbuilder' translation namespace (registered by
 * PromptBuilderServiceProvider), so applications can publish and override
 * the bundled strings, and a null locale follows the app's current locale.
 *
 * This is the ONLY translator that depends on the framework — the default
 * ArrayTranslator keeps the rendering layer framework-free.
 */
class LaravelTranslator implements TranslatorInterface
{
    public function __construct(private readonly Translator $translator)
    {
    }

    public function get(string $key, ?string $locale = null): string
    {
        $namespacedKey = "promptbuilder::labels.{$key}";
        $line = $this->translator->get($namespacedKey, [], $locale);

        // On a miss, Laravel echoes back the full namespaced key. Normalize
        // that to the short key so this bridge behaves like ArrayTranslator
        // (visible key, never blank).
        if (!is_string($line) || $line === $namespacedKey) {
            return $key;
        }

        return $line;
    }
}
