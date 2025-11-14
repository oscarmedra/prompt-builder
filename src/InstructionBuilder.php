<?php


namespace NoahMedra\PromptBuilder;

use Closure;
use Illuminate\Support\Collection;

use function PHPUnit\Framework\callback;

class InstructionBuilder
{
    protected string $text;   // Le texte de l'instruction
    protected Closure $callback;         // Le callback pour gérer les conditions
    protected Collection $instructions;
    
    public function __construct(string $instructionText)
    {
        $this->text = $instructionText;
        $this->instructions = collect([]);
    }


    public function when(bool $condition, Closure $ifc, ?Closure $elsec = null): self{
        if ($condition) {
            $ifc($this);
        } elseif ($elsec) {
            $elsec($this);
        }
        
        return $this;
    }


    public function formatToText(int $depth) : string{
        $text = str_repeat("  ", $depth) .'-'. $this->text.PHP_EOL;

        $this->instructions->each(function($ist) use(&$text, $depth){
            $text .= $ist->formatToText(++$depth);

            $text.PHP_EOL;
        });

        return $text;
    }



    public function instruction(string $instructionText, ?Closure $callback = null): self
    {
        $ist = new InstructionBuilder($instructionText);

        if ($callback instanceof Closure) {
            $callback($ist); 
        }

        $this->instructions->push($ist);
        return $this;
    }


    public function getText() : string{
        return $this->text;
    }
}
