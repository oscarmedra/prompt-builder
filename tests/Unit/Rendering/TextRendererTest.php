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

    public function test_each_section_gets_its_own_header(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'Prof';
        $spec->context = 'Cours';
        $spec->question = 'Pourquoi ?';

        $out = $this->render($spec);

        $this->assertStringContainsString("# Rôle\nProf", $out);
        $this->assertStringContainsString("# Contexte\nCours", $out);
        $this->assertStringContainsString("# Question\nPourquoi ?", $out);
    }

    public function test_sections_appear_in_a_stable_order(): void
    {
        $spec = new PromptSpec();
        $spec->persona = 'Prof';
        $spec->context = 'Cours';
        $spec->question = 'Q';

        $out = $this->render($spec);

        $this->assertLessThan(strpos($out, '# Contexte'), strpos($out, '# Rôle'));
        $this->assertLessThan(strpos($out, '# Question'), strpos($out, '# Contexte'));
    }

    public function test_must_and_must_not_are_labelled(): void
    {
        $spec = new PromptSpec();
        $spec->instructions[] = new Instruction('Cite tes sources', Instruction::TYPE_MUST);
        $spec->instructions[] = new Instruction('Invente des chiffres', Instruction::TYPE_MUST_NOT);
        $spec->instructions[] = new Instruction('Reste neutre');

        $out = $this->render($spec);

        $this->assertStringContainsString('- [Obligatoire] Cite tes sources', $out);
        $this->assertStringContainsString('- [Interdit] Invente des chiffres', $out);
        $this->assertStringContainsString('- Reste neutre', $out);
    }

    public function test_nested_instructions_are_indented_by_depth(): void
    {
        $root = new Instruction('Racine');
        $root->add('Enfant', Instruction::TYPE_GENERAL, function (Instruction $c) {
            $c->add('Petit-enfant');
        });

        $spec = new PromptSpec();
        $spec->instructions[] = $root;

        $out = $this->render($spec);

        $this->assertStringContainsString("- Racine\n", $out);
        $this->assertStringContainsString("  - Enfant\n", $out);
        // Deepest line is last; the instruction block is rtrim()'d, so no
        // trailing newline here.
        $this->assertStringContainsString("    - Petit-enfant", $out);
    }

    public function test_examples_are_numbered(): void
    {
        $spec = new PromptSpec();
        $spec->examples[] = new Example('2+2', '4');
        $spec->examples[] = new Example('3+3', '6');

        $out = $this->render($spec);

        $this->assertStringContainsString('Exemple 1 :', $out);
        $this->assertStringContainsString("Entrée : 2+2", $out);
        $this->assertStringContainsString('Exemple 2 :', $out);
        $this->assertStringContainsString("Sortie attendue : 6", $out);
    }

    public function test_output_format_section_is_rendered_verbatim(): void
    {
        $spec = new PromptSpec();
        $spec->outputFormat = '{"answer": "string"}';

        $out = $this->render($spec);

        $this->assertStringContainsString('# Format de sortie', $out);
        $this->assertStringContainsString('{"answer": "string"}', $out);
    }

    public function test_params_are_interpolated_everywhere_text_appears(): void
    {
        $spec = new PromptSpec();
        $spec->params = ['role' => 'tuteur', 'sujet' => 'les fractions', 'user' => ['name' => 'Sam']];
        $spec->persona = 'Tu es un {role}.';
        $spec->context = 'On étudie {sujet}.';
        $spec->instructions[] = new Instruction('Salue {user.name}.');
        $spec->examples[] = new Example('Entrée pour {user.name}', 'Sortie {sujet}');
        $spec->question = 'Explique {sujet} à {user.name}.';

        $out = $this->render($spec);

        $this->assertStringContainsString('Tu es un tuteur.', $out);
        $this->assertStringContainsString('On étudie les fractions.', $out);
        $this->assertStringContainsString('Salue Sam.', $out);
        $this->assertStringContainsString('Entrée pour Sam', $out);
        $this->assertStringContainsString('Sortie les fractions', $out);
        $this->assertStringContainsString('Explique les fractions à Sam.', $out);
    }

    public function test_unknown_placeholders_are_left_untouched(): void
    {
        $spec = new PromptSpec();
        $spec->params = ['a' => 1];
        $spec->question = 'Garde {inconnu} intact.';

        $this->assertStringContainsString('{inconnu}', $this->render($spec));
    }
}
