<?php

namespace NoahMedra\PromptBuilder;

use Closure;
use Illuminate\Support\Collection;

class ConditionBuilder
{
    protected $text;
    protected Closure $callback;   
    public Collection $instructions; 
    
    public function __construct(string $conditionStr)
    {
        $this->text = $conditionStr;
        $this->instructions = collect([]);
    }


    public function instruction(string $instructionText, ?Closure $callback = null): self
    {
        $ist = new InstructionBuilder($instructionText);
        if($callback instanceof Closure){
            $callback($ist);
        }

        $this->instructions->push($ist);
        return $this;
    }



    public function getInstructions() : Collection{
        return $this->instructions;
    }


    public function getText() : string{
        return $this->text;
    }


    public function formatToText(int $depth) : string{
        $text = str_repeat(" ", $depth) .'***->' . $this->text. PHP_EOL;
        $this->instructions->each(function($ist) use(&$text, $depth){
            $text .= $ist->formatToText(++$depth);
        });

        return $text;
    }
}
