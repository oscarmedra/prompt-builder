<?php

namespace NoahMedra\PromptBuilder\Translation;

/**
 * Default, framework-free translator. Loads bundled PHP language files from
 * resources/lang/{locale}/labels.php and resolves dotted keys against the
 * nested arrays they return.
 *
 * English ('en') is the default and the fallback: any key missing in the
 * requested locale falls back to English, and any key missing there too is
 * returned verbatim so a typo is visible rather than silently blank.
 */
class ArrayTranslator implements TranslatorInterface
{
    public const FALLBACK_LOCALE = 'en';

    /** @var array<string, array<string, mixed>> loaded catalogs, keyed by locale */
    private array $catalogs = [];

    public function __construct(
        private readonly string $defaultLocale = self::FALLBACK_LOCALE,
        private readonly ?string $resourcePath = null,
    ) {
    }

    public function get(string $key, ?string $locale = null): string
    {
        $locale = $locale ?: $this->defaultLocale;

        return $this->lookup($locale, $key)
            ?? $this->lookup(self::FALLBACK_LOCALE, $key)
            ?? $key;
    }

    private function lookup(string $locale, string $key): ?string
    {
        $data = $this->catalog($locale);

        foreach (explode('.', $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }

        return is_string($data) ? $data : null;
    }

    /** @return array<string, mixed> */
    private function catalog(string $locale): array
    {
        if (!array_key_exists($locale, $this->catalogs)) {
            $file = $this->basePath() . "/{$locale}/labels.php";
            $this->catalogs[$locale] = is_file($file) ? (array) require $file : [];
        }

        return $this->catalogs[$locale];
    }

    private function basePath(): string
    {
        return $this->resourcePath ?? dirname(__DIR__, 2) . '/resources/lang';
    }
}
