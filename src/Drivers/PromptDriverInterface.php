<?php

namespace NoahMedra\PromptBuilder\Drivers;

use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\PromptSpec;

/**
 * A driver is only responsible for EXECUTION: taking a PromptSpec,
 * rendering it into whatever shape its target API needs (usually via
 * a Rendering\RendererInterface it owns/injects itself), sending it,
 * and wrapping the result in a BuilderOutput.
 *
 * It must never assume a pre-rendered string is handed to it — that
 * was the bug in the original OllamaDriver, which ignored the built
 * prompt entirely and sent a hardcoded message instead.
 */
interface PromptDriverInterface
{
    public function process(PromptSpec $spec): BuilderOutput;
}