<?php

namespace Tests\Unit\Rendering;

use NoahMedra\PromptBuilder\Examples\Example;
use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\TextRenderer;
use PHPUnit\Framework\TestCase;

class TextRendererTest extends TestCase
{
    private function render(PromptSpec $spec): string
    {
        return (new TextRenderer())->render($spec);
    }

    public function test_an_empty_spec_renders_an_empty_string(): void
    {
        $this->assertSame('', $this->render(new PromptSpec()));
    }

    public function test_each_section_gets_its_own_header_in_english_by_default(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'Prof';
        $spec->context = 'Course';
        $spec->question = 'Why?';

        $out = $this->render($spec);

        $this->assertStringContainsString("# Role\nProf", $out);
        $this->assertStringContainsString("# Context\nCourse", $out);
        $this->assertStringContainsString("# Question\nWhy?", $out);
    }

    public function test_sections_appear_in_a_stable_order(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'Prof';
        $spec->context = 'Course';
        $spec->question = 'Q';

        $out = $this->render($spec);

        $this->assertLessThan(strpos($out, '# Context'), strpos($out, '# Role'));
        $this->assertLessThan(strpos($out, '# Question'), strpos($out, '# Context'));
    }

    public function test_must_and_must_not_are_labelled(): void
    {
        $spec = new PromptSpec();
        $spec->instructions[] = new Instruction('Cite your sources', Instruction::TYPE_MUST);
        $spec->instructions[] = new Instruction('Invent figures', Instruction::TYPE_MUST_NOT);
        $spec->instructions[] = new Instruction('Stay neutral');

        $out = $this->render($spec);

        $this->assertStringContainsString('- [Required] Cite your sources', $out);
        $this->assertStringContainsString('- [Forbidden] Invent figures', $out);
        $this->assertStringContainsString('- Stay neutral', $out);
    }

    public function test_nested_instructions_are_indented_by_depth(): void
    {
        $root = new Instruction('Root');
        $root->add('Child', Instruction::TYPE_GENERAL, function (Instruction $c) {
            $c->add('Grandchild');
        });

        $spec = new PromptSpec();
        $spec->instructions[] = $root;

        $out = $this->render($spec);

        $this->assertStringContainsString("- Root\n", $out);
        $this->assertStringContainsString("  - Child\n", $out);
        // Deepest line is last; the instruction block is rtrim()'d, so no
        // trailing newline here.
        $this->assertStringContainsString("    - Grandchild", $out);
    }

    public function test_examples_are_numbered(): void
    {
        $spec = new PromptSpec();
        $spec->examples[] = new Example('2+2', '4');
        $spec->examples[] = new Example('3+3', '6');

        $out = $this->render($spec);

        $this->assertStringContainsString('Example 1:', $out);
        $this->assertStringContainsString('Input: 2+2', $out);
        $this->assertStringContainsString('Example 2:', $out);
        $this->assertStringContainsString('Expected output: 6', $out);
    }

    public function test_output_format_section_is_rendered_verbatim(): void
    {
        $spec = new PromptSpec();
        $spec->outputFormat = '{"answer": "string"}';

        $out = $this->render($spec);

        $this->assertStringContainsString('# Output format', $out);
        $this->assertStringContainsString('{"answer": "string"}', $out);
    }

    public function test_params_are_interpolated_everywhere_text_appears(): void
    {
        $spec = new PromptSpec();
        $spec->params = ['role' => 'tutor', 'topic' => 'fractions', 'user' => ['name' => 'Sam']];
        $spec->persona = 'You are a {role}.';
        $spec->context = 'We study {topic}.';
        $spec->instructions[] = new Instruction('Greet {user.name}.');
        $spec->examples[] = new Example('Input for {user.name}', 'Output {topic}');
        $spec->question = 'Explain {topic} to {user.name}.';

        $out = $this->render($spec);

        $this->assertStringContainsString('You are a tutor.', $out);
        $this->assertStringContainsString('We study fractions.', $out);
        $this->assertStringContainsString('Greet Sam.', $out);
        $this->assertStringContainsString('Input for Sam', $out);
        $this->assertStringContainsString('Output fractions', $out);
        $this->assertStringContainsString('Explain fractions to Sam.', $out);
    }

    public function test_unknown_placeholders_are_left_untouched(): void
    {
        $spec = new PromptSpec();
        $spec->params = ['a' => 1];
        $spec->question = 'Keep {unknown} intact.';

        $this->assertStringContainsString('{unknown}', $this->render($spec));
    }

    public function test_labels_are_localized_by_the_spec_locale(): void
    {
        $spec = new PromptSpec();
        $spec->locale = 'fr';
        $spec->persona = 'Prof';
        $spec->context = 'Cours';
        $spec->instructions[] = new Instruction('Cite tes sources', Instruction::TYPE_MUST);
        $spec->instructions[] = new Instruction('Invente', Instruction::TYPE_MUST_NOT);
        $spec->examples[] = new Example('2+2', '4');
        $spec->question = 'Pourquoi ?';

        $out = $this->render($spec);

        $this->assertStringContainsString('# Rôle', $out);
        $this->assertStringContainsString('# Contexte', $out);
        $this->assertStringContainsString('# Exemples', $out);
        $this->assertStringContainsString('Exemple 1 :', $out);
        $this->assertStringContainsString('Entrée : 2+2', $out);
        $this->assertStringContainsString('Sortie attendue : 4', $out);
        $this->assertStringContainsString('[Obligatoire] Cite tes sources', $out);
        $this->assertStringContainsString('[Interdit] Invente', $out);
        $this->assertStringContainsString('# Question', $out);

        // None of the English labels should leak through.
        $this->assertStringNotContainsString('# Role', $out);
        $this->assertStringNotContainsString('[Required]', $out);
    }

    public function test_an_unknown_locale_falls_back_to_english_labels(): void
    {
        $spec = new PromptSpec();
        $spec->locale = 'xx';
        $spec->persona = 'Prof';

        $this->assertStringContainsString('# Role', $this->render($spec));
    }
}
