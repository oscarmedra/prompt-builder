<?php

use Illuminate\Support\Facades\Http;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\DriverInterface;
use SebastianBergmann\CodeCoverage\Node\Builder;

class OllamaDriver implements DriverInterface{


    public function process(Builder $input){
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