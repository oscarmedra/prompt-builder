<?php

namespace PromptBuilder\Drivers;

interface DriverInterface
{
    public function sendPrompt(string $prompt, array $parameters = []): string;
}