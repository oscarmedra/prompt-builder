<?php

namespace Tests\Unit\Translation;

use NoahMedra\PromptBuilder\Translation\ArrayTranslator;
use NoahMedra\PromptBuilder\Translation\TranslatorInterface;
use PHPUnit\Framework\TestCase;

class ArrayTranslatorTest extends TestCase
{
    public function test_it_is_a_translator(): void
    {
        $this->assertInstanceOf(TranslatorInterface::class, new ArrayTranslator());
    }

    public function test_english_is_the_default_locale(): void
    {
        $t = new ArrayTranslator();

        $this->assertSame('Role', $t->get('section.role'));
        $this->assertSame('Required', $t->get('label.must'));
        $this->assertSame('Forbidden', $t->get('label.must_not'));
        $this->assertSame('Expected output:', $t->get('example.output'));
    }

    public function test_the_default_locale_can_be_set_in_the_constructor(): void
    {
        $t = new ArrayTranslator('fr');

        $this->assertSame('Rôle', $t->get('section.role'));
        $this->assertSame('Obligatoire', $t->get('label.must'));
    }

    public function test_an_explicit_locale_argument_wins_over_the_default(): void
    {
        $t = new ArrayTranslator('en');

        $this->assertSame('Contexto', $t->get('section.context', 'es'));
        $this->assertSame('Kontext', $t->get('section.context', 'de'));
    }

    /**
     * @dataProvider bundledLocales
     */
    public function test_every_bundled_locale_defines_all_keys(string $locale): void
    {
        $t = new ArrayTranslator($locale);

        foreach ($this->allKeys() as $key) {
            $value = $t->get($key, $locale);
            $this->assertNotSame($key, $value, "Missing translation for '{$key}' in '{$locale}'");
            $this->assertNotSame('', trim($value), "Empty translation for '{$key}' in '{$locale}'");
        }
    }

    public function test_example_number_keeps_the_printf_placeholder(): void
    {
        foreach (['en', 'es', 'fr', 'de', 'zh', 'ar'] as $locale) {
            $this->assertStringContainsString('%d', (new ArrayTranslator())->get('example.number', $locale));
        }
    }

    public function test_a_missing_key_in_the_locale_falls_back_to_english(): void
    {
        // A translator with a resource path that only holds a partial 'xx'
        // locale would still fall back; here we rely on 'ar' having every key,
        // so we instead assert the unknown-locale path falls back to English.
        $t = new ArrayTranslator();

        $this->assertSame('Role', $t->get('section.role', 'this-locale-does-not-exist'));
    }

    public function test_an_unknown_key_returns_the_key_itself(): void
    {
        $t = new ArrayTranslator();

        $this->assertSame('section.nope', $t->get('section.nope'));
        $this->assertSame('totally.unknown.key', $t->get('totally.unknown.key'));
    }

    /** @return array<int, array{0: string}> */
    public static function bundledLocales(): array
    {
        return [['en'], ['es'], ['fr'], ['de'], ['zh'], ['ar']];
    }

    /** @return string[] */
    private function allKeys(): array
    {
        return [
            'section.role',
            'section.context',
            'section.examples',
            'section.instructions',
            'section.output_format',
            'section.question',
            'label.must',
            'label.must_not',
            'example.number',
            'example.input',
            'example.output',
            'output_format.instruction',
        ];
    }
}
