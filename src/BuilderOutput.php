<?php 

namespace NoahMedra\PromptBuilder;

class BuilderOutput{
    protected string $output;

    
    public function __construct(string $output) {
        $this->output = $output;
    }



    public function toArray(){
        return json_decode($this->output);
    }
}