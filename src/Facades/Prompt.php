<?php
namespace PromptBuilder\Facades;

use Illuminate\Support\Facades\Facade;

class Prompt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'promptbuilder';
    }
}