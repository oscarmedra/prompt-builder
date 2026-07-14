<?php

namespace NoahMedra\PromptBuilder\Rendering;

use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;

/**
 * Renders a PromptSpec as a single, human-readable string.
 *
 * Useful as-is for completion-style APIs (like Ollama's /api/generate),
 * and reused by ChatMessagesRenderer to build the "system" message for
 * chat-style APIs.
 */
class TextRenderer implements RendererInterface
{
    public function render(PromptSpec $spec): string
    {
        $sections = [];

        if ($spec->persona) {
            $sections[] = "# Rôle\n" . $this->interpolate($spec->persona, $spec->params);
        }

        if ($spec->context) {
            $sections[] = "# Contexte\n" . $this->interpolate($spec->context, $spec->params);
        }

        if (!empty($spec->examples)) {
            $lines = ['# Exemples'];
            foreach ($spec->examples as $i => $example) {
                $lines[] = sprintf(
                    "Exemple %d :\nEntrée : %s\nSortie attendue : %s",
                    $i + 1,
                    $this->interpolate($example->getInput(), $spec->params),
                    $this->interpolate($example->getOutput(), $spec->params)
                );
            }
            $sections[] = implode("\n\n", $lines);
        }

        if (!empty($spec->instructions)) {
            $lines = ['# Instructions'];
            foreach ($spec->instructions as $instruction) {
                $lines[] = rtrim($this->renderInstruction($instruction, 1, $spec->params));
            }
            $sections[] = implode("\n", $lines);
        }

        if ($spec->outputFormat) {
            $sections[] = "# Format de sortie\n"
                . "Réponds uniquement avec un JSON valide respectant strictement ce format, sans texte additionnel :\n"
                . $spec->outputFormat;
        }

        if ($spec->question) {
            $sections[] = "# Question\n" . $this->interpolate($spec->question, $spec->params);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Recurses through an instruction tree, giving every sibling the
     * SAME depth (depth + 1 is passed down, nothing is mutated/shared
     * across iterations — this is what the original buggy version got
     * wrong with its shared `++$depth` inside the closure).
     */
    private function renderInstruction(Instruction $instruction, int $depth, array $params): string
    {
        $prefix = str_repeat('  ', $depth - 1) . '- ';

        $label = match ($instruction->getType()) {
            Instruction::TYPE_MUST => '[Obligatoire] ',
            Instruction::TYPE_MUST_NOT => '[Interdit] ',
            default => '',
        };

        $text = $prefix . $label . $this->interpolate($instruction->getText(), $params) . "\n";

        foreach ($instruction->getChildren() as $child) {
            $text .= $this->renderInstruction($child, $depth + 1, $params);
        }

        return $text;
    }

    /**
     * Replaces {key} or {nested.key} with a value from $params.
     * Unknown placeholders are left untouched rather than silently
     * turned into an empty string, so typos are easy to spot.
     */
    private function interpolate(string $text, array $params): string
    {
        if (empty($params)) {
            return $text;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function ($matches) use ($params) {
            $value = $this->resolveParam($matches[1], $params);
            return $value !== null ? (string) $value : $matches[0];
        }, $text);
    }

    private function resolveParam(string $path, array $params): mixed
    {
        $data = $params;
        foreach (explode('.', $path) as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return null;
            }
            $data = $data[$key];
        }

        return $data;
    }
}
