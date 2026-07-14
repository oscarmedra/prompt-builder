<?php

namespace NoahMedra\PromptBuilder\Examples;

/**
 * A single few-shot example (input/output pair) shown to the model
 * to demonstrate the expected style or format of a response.
 */
class Example
{
    public function __construct(
        protected string $input,
        protected string $output,
    ) {
    }

    public function getInput(): string
    {
        return $this->input;
    }

    public function getOutput(): string
    {
        return $this->output;
    }
}
