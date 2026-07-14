<?php

namespace Tests\Unit\Examples;

use NoahMedra\PromptBuilder\Examples\Example;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_it_exposes_input_and_output(): void
    {
        $example = new Example('Résous x + 2 = 5', 'x = 3');

        $this->assertSame('Résous x + 2 = 5', $example->getInput());
        $this->assertSame('x = 3', $example->getOutput());
    }
}
