<?php

namespace NoahMedra\PromptBuilder\Drivers;

use Illuminate\Support\Facades\Facade;
use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\BuilderOutput;

Facade::setFacadeApplication($app);


interface DriverInterface
{
    public function process(BuilderInput $input) : BuilderOutput;
}