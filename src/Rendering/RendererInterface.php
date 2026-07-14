<?php

namespace NoahMedra\PromptBuilder\Rendering;

use NoahMedra\PromptBuilder\PromptSpec;

/**
 * Turns a PromptSpec into whatever shape a target model/API expects.
 *
 * Implementations decide the format entirely: a single string for
 * completion-style APIs, a role-based message array for chat APIs,
 * XML tags for models that respond better to that structure, etc.
 * PromptBuilder and PromptSpec never need to know which one is used.
 *
 * @return string|array<int, array{role: string, content: string}>
 */
interface RendererInterface
{
    public function render(PromptSpec $spec): string|array;
}
