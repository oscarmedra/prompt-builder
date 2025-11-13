<?php
namespace NoahMedra\PromptBuilder\Facades;

use Illuminate\Support\Facades\Facade;

class PromptBuilder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'promptbuilder';
    }
}