<?php

// packages/promot-builder/tests/Unit/ChatGPTDriverTest.php

namespace Tests\Unit;

use PromptBuilder\Drivers\ChatGPTDriver;
use PHPUnit\Framework\TestCase;

class ChatGPTDriverTest extends TestCase
{
    public function test_sendPrompt()
    {
        $driver = new ChatGPTDriver(config('api_keys.chatgpt'));

        $response = $driver->sendPrompt('Quelle est la capitale de la France ?');

        // Vérifie si la réponse contient "Paris"
        $this->assertStringContainsString('Paris', $response);
    }
}