<?php


namespace NoahMedra\PromptBuilder;

use Closure;
use Illuminate\Support\Collection;

use function PHPUnit\Framework\callback;

class InstructionBuilder
{
    protected string $text;   // Le texte de l'instruction
    protected Closure $callback;         // Le callback pour gérer les conditions
    protected Collection $conditions;
    
    public function __construct(string $instructionText)
    {
        $this->text = $instructionText;
        $this->conditions = collect([]);
    }


    // Ajout de la méthode `when()`
    public function when(string $condition, Closure $ifcallback, ?Closure $elscallback = null): self
    {
        // Si la condition est remplie, on applique le callback
        if ($condition) {
            $cb = new ConditionBuilder('Si ' . $condition);
            $ifcallback($cb);
            // $this->conditions[$condition] =  $cb->instructions;
            $this->conditions->push($cb);
        }


        if (!is_null($elscallback) && $elscallback instanceof Closure) {
            $cb = new ConditionBuilder('Sinon, ');
            $elscallback($cb);
            $this->conditions->push($cb);
        }
        
        return $this;
    }


    public function getConditions() : Collection{
        return $this->conditions;
    }


    public function formatToText(int $depth) : string{
        $text = str_repeat(" ", $depth) .'-'. $this->text.PHP_EOL;

        $this->conditions->each(function($cb) use(&$text, $depth){
            $text .= $cb->formatToText(++$depth);

            $text.PHP_EOL;
        });

        return $text;
    }


    public function getText() : string{
        return $this->text;
    }
}
