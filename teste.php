<?php

require 'vendor/autoload.php';

use NoahMedra\PromptBuilder\PromptBuilder;

$driver = new RecordingDriver();

PromptBuilder::make()
    ->context('Contexte important')
    ->ask('Quelle est la capitale de la France ?')
    ->driver($driver)
    ->process();
echo $driver->received;

