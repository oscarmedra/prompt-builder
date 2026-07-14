<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\Rendering\XmlRenderer;
use PHPUnit\Framework\TestCase;

class XmlRendererTest extends TestCase
{
    private function build(): PromptBuilder
    {
        return PromptBuilder::make()
            ->persona('Professeur')
            ->context('Cours de maths')
            ->must('Réponds en français')
            ->mustNot('Ne donne pas la réponse finale')
            ->instruction('Structure ta réponse', function ($ist) {
                $ist->add('Commence par un rappel')->add('Puis un exemple');
            })
            ->example('2 + 2', '4')
            ->withParams(['ton' => 'calme'])
            ->ask('Explique les fractions sur un ton {ton}.');
    }

    public function test_output_is_well_formed_xml(): void
    {
        $xml = $this->build()->toPrompt(new XmlRenderer());

        // libxml throws on malformed XML when we ask it to.
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($doc, 'XmlRenderer produced malformed XML: ' . $xml);
        $this->assertSame([], $errors);
        $this->assertSame('prompt', $doc->getName());
    }

    public function test_sections_map_to_expected_tags(): void
    {
        $xml = $this->build()->toPrompt(new XmlRenderer());
        $doc = simplexml_load_string($xml);

        $this->assertSame('Professeur', (string) $doc->persona);
        $this->assertSame('Cours de maths', (string) $doc->context);
        $this->assertSame('Réponds en français', (string) $doc->instructions->must);
        $this->assertSame('Ne donne pas la réponse finale', (string) $doc->instructions->{'must-not'});
        $this->assertSame('2 + 2', (string) $doc->examples->example->input);
        $this->assertSame('4', (string) $doc->examples->example->output);
        // Param interpolation happened before escaping.
        $this->assertStringContainsString('sur un ton calme', (string) $doc->question);
    }

    public function test_special_characters_are_escaped_and_still_parse(): void
    {
        $xml = PromptBuilder::make()
            ->context('Compare a < b && c > d, cite "x" et \'y\'')
            ->ask('R&D ?')
            ->toPrompt(new XmlRenderer());

        $doc = simplexml_load_string($xml);

        $this->assertNotFalse($doc);
        // Raw angle brackets/ampersands must have been escaped, then decoded
        // back to the original text by the parser.
        $this->assertSame('Compare a < b && c > d, cite "x" et \'y\'', (string) $doc->context);
        $this->assertSame('R&D ?', (string) $doc->question);
    }

    public function test_nested_instruction_children_are_nested_tags(): void
    {
        $xml = $this->build()->toPrompt(new XmlRenderer());
        $doc = simplexml_load_string($xml);

        // The general instruction with children.
        $instruction = $doc->instructions->instruction;
        $children = $instruction->instruction;
        $this->assertCount(2, $children);
        $this->assertSame('Commence par un rappel', (string) $children[0]);
        $this->assertSame('Puis un exemple', (string) $children[1]);
    }
}
