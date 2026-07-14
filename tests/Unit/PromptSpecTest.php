<?php

namespace Tests\Unit;

use NoahMedra\PromptBuilder\Examples\Example;
use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;
use PHPUnit\Framework\TestCase;

class PromptSpecTest extends TestCase
{
    public function test_a_fresh_spec_is_empty(): void
    {
        $spec = new PromptSpec();

        $this->assertNull($spec->persona);
        $this->assertNull($spec->context);
        $this->assertNull($spec->outputFormat);
        $this->assertNull($spec->question);
        $this->assertSame([], $spec->instructions);
        $this->assertSame([], $spec->examples);
        $this->assertSame([], $spec->params);
        $this->assertSame([], $spec->history);
    }

    public function test_fields_are_public_and_assignable(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'P';
        $spec->context = 'C';
        $spec->question = 'Q';
        $spec->outputFormat = '{}';
        $spec->params = ['a' => 1];
        $spec->instructions[] = new Instruction('do');
        $spec->examples[] = new Example('in', 'out');
        $spec->history[] = ['role' => 'user', 'content' => 'hi'];

        $this->assertSame('P', $spec->persona);
        $this->assertSame('C', $spec->context);
        $this->assertSame('Q', $spec->question);
        $this->assertSame('{}', $spec->outputFormat);
        $this->assertSame(['a' => 1], $spec->params);
        $this->assertCount(1, $spec->instructions);
        $this->assertInstanceOf(Instruction::class, $spec->instructions[0]);
        $this->assertCount(1, $spec->examples);
        $this->assertInstanceOf(Example::class, $spec->examples[0]);
        $this->assertSame([['role' => 'user', 'content' => 'hi']], $spec->history);
    }

    public function test_clone_is_shallow_but_isolates_scalar_reassignment(): void
    {
        // ChatMessagesRenderer relies on cloning a spec and nulling its
        // question without disturbing the original.
        $spec = new PromptSpec();
        $spec->question = 'original';

        $copy = clone $spec;
        $copy->question = null;

        $this->assertSame('original', $spec->question);
        $this->assertNull($copy->question);
    }
}
