<?php

namespace NoahMedra\PromptBuilder\Drivers;

interface DriverInterface
{
    public function sendPrompt(string $prompt, array $parameters = []): string;
}