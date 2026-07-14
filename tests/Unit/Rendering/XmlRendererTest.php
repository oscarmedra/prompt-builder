<?php

namespace Tests\Unit\Rendering;

use NoahMedra\PromptBuilder\Examples\Example;
use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\XmlRenderer;
use PHPUnit\Framework\TestCase;

class XmlRendererTest extends TestCase
{
    private function render(PromptSpec $spec): string
    {
        return (new XmlRenderer())->render($spec);
    }

    private function parse(string $xml): \SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($doc, 'malformed XML: ' . $xml);
        $this->assertSame([], $errors, 'libxml reported errors');

        return $doc;
    }

    private function fullSpec(): PromptSpec
    {
        $structure = new Instruction('Structure ta réponse');
        $structure->add('Commence par un rappel')->add('Puis un exemple');

        $spec = new PromptSpec();
        $spec->persona = 'Professeur';
        $spec->context = 'Cours de maths';
        $spec->instructions[] = new Instruction('Réponds en français', Instruction::TYPE_MUST);
        $spec->instructions[] = new Instruction('Ne donne pas la réponse finale', Instruction::TYPE_MUST_NOT);
        $spec->instructions[] = $structure;
        $spec->examples[] = new Example('2 + 2', '4');
        $spec->outputFormat = '{"answer":"string"}';
        $spec->params = ['ton' => 'calme'];
        $spec->question = 'Explique les fractions sur un ton {ton}.';

        return $spec;
    }

    public function test_empty_spec_is_a_well_formed_empty_prompt(): void
    {
        $doc = $this->parse($this->render(new PromptSpec()));

        $this->assertSame('prompt', $doc->getName());
        $this->assertSame(0, $doc->count());
    }

    public function test_full_spec_is_well_formed(): void
    {
        $doc = $this->parse($this->render($this->fullSpec()));
        $this->assertSame('prompt', $doc->getName());
    }

    public function test_sections_map_to_expected_tags(): void
    {
        $doc = $this->parse($this->render($this->fullSpec()));

        $this->assertSame('Professeur', (string) $doc->persona);
        $this->assertSame('Cours de maths', (string) $doc->context);
        $this->assertSame('Réponds en français', (string) $doc->instructions->must);
        $this->assertSame('Ne donne pas la réponse finale', (string) $doc->instructions->{'must-not'});
        $this->assertSame('2 + 2', (string) $doc->examples->example->input);
        $this->assertSame('4', (string) $doc->examples->example->output);
        $this->assertSame('{"answer":"string"}', (string) $doc->{'output-format'});
    }

    public function test_params_are_interpolated_before_escaping(): void
    {
        $doc = $this->parse($this->render($this->fullSpec()));

        $this->assertStringContainsString('sur un ton calme', (string) $doc->question);
    }

    public function test_special_characters_round_trip_through_the_parser(): void
    {
        $spec = new PromptSpec();
        $spec->context = 'Compare a < b && c > d, cite "x" et \'y\'';
        $spec->question = 'R&D ?';

        $doc = $this->parse($this->render($spec));

        $this->assertSame('Compare a < b && c > d, cite "x" et \'y\'', (string) $doc->context);
        $this->assertSame('R&D ?', (string) $doc->question);
    }

    public function test_nested_children_render_as_nested_tags(): void
    {
        $doc = $this->parse($this->render($this->fullSpec()));

        $children = $doc->instructions->instruction->instruction;
        $this->assertCount(2, $children);
        $this->assertSame('Commence par un rappel', (string) $children[0]);
        $this->assertSame('Puis un exemple', (string) $children[1]);
    }
}
