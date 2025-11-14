<?php

namespace NoahMedra\PromptBuilder\Drivers;

use Exception;
use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\BuilderOutput;

class OllamaDriver implements PromptDriverInterface{


    public function process(BuilderInput $input) : BuilderOutput{
        $output = '';
        try{
            $response = Http::withHeaders([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])->post('http://localhost:11434/api/chat', [
                    'model' => 'llama3.1',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Bonjour toi"
                        ]
                    ]
                ]);


            if ($response->failed()) {
                throw new Exception($response->body());
            }

            $output = $response->body();
        }catch(Exception $e){
            $output = $e->getMessage();
        }finally{
            return new BuilderOutput($output);
        }
    }
}