<?php

namespace NoahMedra\PromptBuilder\Rendering;

use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\Concerns\InterpolatesParams;

/**
 * Renders a PromptSpec as an explicitly-tagged XML document rather than
 * Markdown headers:
 *
 *   <prompt>
 *     <persona>...</persona>
 *     <context>...</context>
 *     <instructions>
 *       <instruction>...</instruction>
 *       <must>...</must>
 *       <must-not>...</must-not>
 *     </instructions>
 *     <examples>
 *       <example><input>...</input><output>...</output></example>
 *     </examples>
 *     <output-format>...</output-format>
 *     <question>...</question>
 *   </prompt>
 *
 * Some models follow XML-delimited structure more reliably than prose
 * headers. Text content is XML-escaped, so the output is always well-formed.
 */
class XmlRenderer implements RendererInterface
{
    use InterpolatesParams;

    private const INDENT = '  ';

    public function render(PromptSpec $spec): string
    {
        $lines = ['<prompt>'];

        if ($spec->persona) {
            $lines[] = $this->tag('persona', $this->interpolate($spec->persona, $spec->params), 1);
        }

        if ($spec->context) {
            $lines[] = $this->tag('context', $this->interpolate($spec->context, $spec->params), 1);
        }

        if (!empty($spec->instructions)) {
            $lines[] = self::INDENT . '<instructions>';
            foreach ($spec->instructions as $instruction) {
                $lines[] = $this->renderInstruction($instruction, 2, $spec->params);
            }
            $lines[] = self::INDENT . '</instructions>';
        }

        if (!empty($spec->examples)) {
            $lines[] = self::INDENT . '<examples>';
            foreach ($spec->examples as $example) {
                $lines[] = self::INDENT . self::INDENT . '<example>';
                $lines[] = $this->tag('input', $this->interpolate($example->getInput(), $spec->params), 3);
                $lines[] = $this->tag('output', $this->interpolate($example->getOutput(), $spec->params), 3);
                $lines[] = self::INDENT . self::INDENT . '</example>';
            }
            $lines[] = self::INDENT . '</examples>';
        }

        if ($spec->outputFormat) {
            // Not interpolated: this is a literal JSON schema/spec.
            $lines[] = $this->tag('output-format', $spec->outputFormat, 1);
        }

        if ($spec->question) {
            $lines[] = $this->tag('question', $this->interpolate($spec->question, $spec->params), 1);
        }

        $lines[] = '</prompt>';

        return implode("\n", $lines);
    }

    private function renderInstruction(Instruction $instruction, int $depth, array $params): string
    {
        $tag = match ($instruction->getType()) {
            Instruction::TYPE_MUST => 'must',
            Instruction::TYPE_MUST_NOT => 'must-not',
            default => 'instruction',
        };

        $indent = str_repeat(self::INDENT, $depth);
        $text = $this->escape($this->interpolate($instruction->getText(), $params));
        $children = $instruction->getChildren();

        if (empty($children)) {
            return "{$indent}<{$tag}>{$text}</{$tag}>";
        }

        // Parent text sits on its own line, children nested underneath.
        $lines = ["{$indent}<{$tag}>", $indent . self::INDENT . $text];
        foreach ($children as $child) {
            $lines[] = $this->renderInstruction($child, $depth + 1, $params);
        }
        $lines[] = "{$indent}</{$tag}>";

        return implode("\n", $lines);
    }

    private function tag(string $name, string $content, int $depth): string
    {
        $indent = str_repeat(self::INDENT, $depth);
        return "{$indent}<{$name}>{$this->escape($content)}</{$name}>";
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
