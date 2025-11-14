<?php

namespace NoahMedra\PromptBuilder\Drivers;

use Illuminate\Support\Facades\Facade;
use NoahMedra\PromptBuilder\BuilderInput;

Facade::setFacadeApplication($app);


interface DriverInterface
{
    public function process(BuilderInput $input);
}