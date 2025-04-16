<?php
require 'vendor/autoload.php';

use PromptBuilder\PromptBuilder;

PromptBuilder::make()
    ->for("ollama")  // maintenant une seule IA à la fois
    ->language('fr')
    ->tone('pédagogique')
    ->context("Tu es un expert en éducation.")
    ->ask("Donne une liste de 10 questions de révision sur le calcul intégral que les etudiants devrions répondre.")
    ->withParameters([
        'number' => 10,
        'questionType' => 'QCM, QRM',
        'topic' => 'les intégrales',
        'audience' => 'étudiants en licence',
    ])
    ->expectJson()
    ->handle(function ($collection) {
        $collection->each(function($item){
            dump($item);
        });
    })
    ->run();