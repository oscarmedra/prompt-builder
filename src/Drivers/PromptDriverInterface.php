<?php

namespace NoahMedra\PromptBuilder\Drivers;

use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\BuilderOutput;

interface PromptDriverInterface
{
    public function process(BuilderInput $input) : BuilderOutput;
}