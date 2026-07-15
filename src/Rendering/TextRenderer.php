<?php

namespace NoahMedra\PromptBuilder\Rendering;

use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\Concerns\InterpolatesParams;
use NoahMedra\PromptBuilder\Translation\ArrayTranslator;
use NoahMedra\PromptBuilder\Translation\TranslatorInterface;

/**
 * Renders a PromptSpec as a single, human-readable string.
 *
 * Useful as-is for completion-style APIs (like Ollama's /api/generate),
 * and reused by ChatMessagesRenderer to build the "system" message for
 * chat-style APIs.
 *
 * Section labels (# Role, [Required], Example n:, ...) are localized through
 * a TranslatorInterface. The default is the framework-free ArrayTranslator
 * (English by default); the language for a given prompt is taken from
 * PromptSpec::$locale, so PromptBuilder::language('fr') is enough to switch.
 */
class TextRenderer implements RendererInterface
{
    use InterpolatesParams;

    public function __construct(
        private readonly TranslatorInterface $translator = new ArrayTranslator(),
    ) {
    }

    public function render(PromptSpec $spec): string
    {
        $locale = $spec->locale;
        $t = fn (string $key): string => $this->translator->get($key, $locale);

        $sections = [];

        if ($spec->persona) {
            $sections[] = '# ' . $t('section.role') . "\n" . $this->interpolate($spec->persona, $spec->params);
        }

        if ($spec->context) {
            $sections[] = '# ' . $t('section.context') . "\n" . $this->interpolate($spec->context, $spec->params);
        }

        if (!empty($spec->examples)) {
            $lines = ['# ' . $t('section.examples')];
            foreach ($spec->examples as $i => $example) {
                $lines[] = sprintf($t('example.number'), $i + 1) . "\n"
                    . $t('example.input') . ' ' . $this->interpolate($example->getInput(), $spec->params) . "\n"
                    . $t('example.output') . ' ' . $this->interpolate($example->getOutput(), $spec->params);
            }
            $sections[] = implode("\n\n", $lines);
        }

        if (!empty($spec->instructions)) {
            $lines = ['# ' . $t('section.instructions')];
            foreach ($spec->instructions as $instruction) {
                $lines[] = rtrim($this->renderInstruction($instruction, 1, $spec->params, $locale));
            }
            $sections[] = implode("\n", $lines);
        }

        if ($spec->outputFormat) {
            $sections[] = '# ' . $t('section.output_format') . "\n"
                . $t('output_format.instruction') . "\n"
                . $spec->outputFormat;
        }

        if ($spec->question) {
            $sections[] = '# ' . $t('section.question') . "\n" . $this->interpolate($spec->question, $spec->params);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Recurses through an instruction tree, giving every sibling the
     * SAME depth (depth + 1 is passed down, nothing is mutated/shared
     * across iterations — this is what the original buggy version got
     * wrong with its shared `++$depth` inside the closure).
     */
    private function renderInstruction(Instruction $instruction, int $depth, array $params, ?string $locale): string
    {
        $prefix = str_repeat('  ', $depth - 1) . '- ';

        $label = match ($instruction->getType()) {
            Instruction::TYPE_MUST => '[' . $this->translator->get('label.must', $locale) . '] ',
            Instruction::TYPE_MUST_NOT => '[' . $this->translator->get('label.must_not', $locale) . '] ',
            default => '',
        };

        $text = $prefix . $label . $this->interpolate($instruction->getText(), $params) . "\n";

        foreach ($instruction->getChildren() as $child) {
            $text .= $this->renderInstruction($child, $depth + 1, $params, $locale);
        }

        return $text;
    }
}
