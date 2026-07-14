<?php

namespace Tests\Unit;

use NoahMedra\PromptBuilder\BuilderOutput;
use PHPUnit\Framework\TestCase;

class BuilderOutputTest extends TestCase
{
    public function test_getRaw_returns_the_untouched_string(): void
    {
        $output = new BuilderOutput('not json at all');

        $this->assertSame('not json at all', $output->getRaw());
    }

    public function test_get_reads_a_top_level_json_key(): void
    {
        $output = new BuilderOutput(json_encode(['model' => 'llama3.1']));

        $this->assertSame('llama3.1', $output->get('model'));
    }

    public function test_get_reads_a_nested_dotted_path(): void
    {
        $output = new BuilderOutput(json_encode([
            'message' => ['role' => 'assistant', 'content' => 'Paris'],
        ]));

        $this->assertSame('Paris', $output->get('message.content'));
        $this->assertSame('assistant', $output->get('message.role'));
    }

    public function test_get_returns_null_for_a_missing_path(): void
    {
        $output = new BuilderOutput(json_encode(['a' => ['b' => 1]]));

        $this->assertNull($output->get('a.c'));
        $this->assertNull($output->get('does.not.exist'));
    }

    public function test_get_returns_null_when_the_body_is_not_json(): void
    {
        $output = new BuilderOutput('plain text');

        $this->assertNull($output->get('anything'));
    }

    public function test_get_descending_into_a_scalar_returns_null(): void
    {
        $output = new BuilderOutput(json_encode(['a' => 'scalar']));

        // 'a' is a string, so 'a.b' cannot resolve.
        $this->assertNull($output->get('a.b'));
    }

    public function test_it_decodes_json_arrays(): void
    {
        $output = new BuilderOutput(json_encode(['items' => ['x', 'y', 'z']]));

        $this->assertSame('y', $output->get('items.1'));
    }
}
