<?php

namespace NoahMedra\PromptBuilder;

use NoahMedra\PromptBuilder\Examples\Example;
use NoahMedra\PromptBuilder\Instructions\Instruction;

/**
 * PromptSpec is the "what", never the "how" or the "where to send it".
 *
 * PromptBuilder fills this object. A Renderer turns it into text or
 * chat messages. A Driver sends that rendered output to an LLM.
 * PromptSpec itself has zero knowledge of any of that — it's plain data,
 * which is what makes it safe to render differently per target model,
 * to unit test in isolation, and to reuse across drivers.
 */
class PromptSpec
{
    public ?string $persona = null;

    public ?string $context = null;

    /** @var Instruction[] */
    public array $instructions = [];

    /** @var Example[] */
    public array $examples = [];

    public ?string $outputFormat = null;

    public ?string $question = null;

    /** Locale used to render section labels (null = renderer default, English). */
    public ?string $locale = null;

    /** Values available for {placeholder} interpolation inside text. */
    public array $params = [];

    /** @var array<int, array{role: string, content: string}> */
    public array $history = [];
}
