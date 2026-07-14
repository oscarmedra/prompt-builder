<?php

namespace Tests\Feature;

use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;
use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\ChatMessagesRenderer;
use PHPUnit\Framework\TestCase;

/** A driver that records the PromptSpec it received instead of calling a real API. */
class RecordingDriver implements PromptDriverInterface
{
    public ?PromptSpec $received = null;

    public function process(PromptSpec $spec): BuilderOutput
    {
        $this->received = $spec;
        return new BuilderOutput('{"ok": true}');
    }
}

class PromptBuilderTest extends TestCase
{
    public function test_composition_never_touches_a_driver(): void
    {
        // No driver() call, no process() call — this must not do any I/O.
        $builder = PromptBuilder::make()
            ->context('Contexte de test')
            ->instruction('Sois concis')
            ->ask('Une question ?');

        $this->assertIsString($builder->toPrompt());
        $this->assertStringContainsString('Contexte de test', $builder->toPrompt());
        $this->assertStringContainsString('Une question ?', $builder->toPrompt());
    }

    public function test_the_driver_actually_receives_the_composed_prompt(): void
    {
        $driver = new RecordingDriver();

        PromptBuilder::make()
            ->context('Contexte important')
            ->ask('Quelle est la capitale de la France ?')
            ->driver($driver)
            ->process();

        $this->assertNotNull($driver->received);

        $messages = (new ChatMessagesRenderer())->render($driver->received);
        $rendered = implode(' ', array_column($messages, 'content'));

        $this->assertStringContainsString('Contexte important', $rendered);
        $this->assertStringContainsString('Quelle est la capitale de la France ?', $rendered);
    }

    public function test_params_are_interpolated_in_instructions_and_question(): void
    {
        $builder = PromptBuilder::make()
            ->withParams(['ton' => 'formel', 'lang' => 'anglais'])
            ->instruction('Réponds sur un ton {ton} et en {lang}.')
            ->ask('Salue {lang.absent} proprement.');

        $prompt = $builder->toPrompt();

        $this->assertStringContainsString('Réponds sur un ton formel et en anglais.', $prompt);
        // Unknown placeholders are left as-is rather than silently emptied.
        $this->assertStringContainsString('{lang.absent}', $prompt);
    }

    public function test_must_and_must_not_are_labelled_in_the_rendered_prompt(): void
    {
        $builder = PromptBuilder::make()
            ->must('Cite tes sources')
            ->mustNot('Ne jamais inventer de chiffres')
            ->ask('Analyse ces données.');

        $prompt = $builder->toPrompt();

        $this->assertStringContainsString('[Obligatoire] Cite tes sources', $prompt);
        $this->assertStringContainsString('[Interdit] Ne jamais inventer de chiffres', $prompt);
    }

    public function test_sibling_instructions_share_the_same_depth(): void
    {
        $builder = PromptBuilder::make()->instruction('Plan :', function ($ist) {
            $ist->add('Un')->add('Deux')->add('Trois');
        });

        $lines = array_values(array_filter(
            explode("\n", $builder->toPrompt()),
            fn ($line) => str_starts_with(trim($line), '- Un')
                || str_starts_with(trim($line), '- Deux')
                || str_starts_with(trim($line), '- Trois')
        ));

        $this->assertCount(3, $lines);
        // All three siblings must have IDENTICAL indentation.
        $indents = array_map(fn ($l) => strlen($l) - strlen(ltrim($l)), $lines);
        $this->assertSame($indents[0], $indents[1]);
        $this->assertSame($indents[1], $indents[2]);
    }

    public function test_expect_response_format_rejects_invalid_json(): void
    {
        $this->expectException(\Exception::class);

        PromptBuilder::make()->expectResponseFormat('{not valid json');
    }
}
