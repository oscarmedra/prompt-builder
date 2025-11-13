<?php

use NoahMedra\PromptBuilder\Drivers\OllamaDriver;
use NoahMedra\PromptBuilder\PromptBuilder;

require 'vendor/autoload.php';

// $prompt = PromptBuilder::make()
//     ->for("ollama")  
//     ->language('fr')
//     ->tone('motivant')
//     ->useHistory()  
//     ->context("Agit comme un docteur en modélisation mathématique et calculs scientifique") 
//     ->instruction('Fournir les information tel quel ont été demandé.')

//     ->ask("Donne moi un liste de 10 question ?")
//     ->jsonify(json_encode([
//         [
//             'text' => 'libelle de la question',
//             'reponses' => [
//                 'text' => 'libelle de la réponse',
//                 'is_valid' => "si c'est vraie ou faux",
//                 'point' => "Le nombre de point de l'etudiant"
//             ]
//         ]
//     ]))
//     ->run();
$builder = new PromptBuilder();

$builder
    ->driver(OllamaDriver::class);
$builder
    ->ask("Donne 10 question pour un sujet d'evalution modélisatio mathématique et calculs scientifique")
    ->jsonify(json_encode([
        [
            'name' => 'son prénom',
            'role' => "ce qu'il fait dans la vie"
        ], [
            'name' => 'son prénom',
            'role' => "ce qu'il fait dans la vie"
        ]
        ]));

$builder->process();

dd($builder);

$output = $builder->getOutput();



dd($output->toArray());



    // ->handle(function ($collection) {
    //     $collection->each(function($item){
    //         dump($item);
    //     });
    // })
    // ->run();