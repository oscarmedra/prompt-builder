<?php

namespace NoahMedra\PromptBuilder\Drivers;

use NoahMedra\PromptBuilder\BuilderInput;

interface DriverInterface
{
    public function process(BuilderInput $input);
}