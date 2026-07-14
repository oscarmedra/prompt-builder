<?php

namespace Tests\Unit;

use Exception;
use NoahMedra\PromptBuilder\Instructions\Instruction;
use NoahMedra\PromptBuilder\PromptBuilder;
use NoahMedra\PromptBuilder\PromptSpec;
use NoahMedra\PromptBuilder\Rendering\RendererInterface;
use PHPUnit\Framework\TestCase;

/**
 * Pure composition tests: every builder method should only mutate the
 * internal PromptSpec, with no driver and no I/O.
 */
class PromptBuilderTest extends TestCase
{
    public function test_make_returns_fresh_independent_instances(): void
    {
        $a = PromptBuilder::make();
        $b = PromptBuilder::make();

        $this->assertInstanceOf(PromptBuilder::class, $a);
        $this->assertNotSame($a, $b);
        $this->assertNotSame($a->getSpec(), $b->getSpec());
    }

    public function test_getSpec_returns_the_same_underlying_spec(): void
    {
        $builder = PromptBuilder::make();
        $this->assertSame($builder->getSpec(), $builder->getSpec());
        $this->assertInstanceOf(PromptSpec::class, $builder->getSpec());
    }

    public function test_scalar_setters_populate_the_spec(): void
    {
        $spec = PromptBuilder::make()
            ->persona('Prof')
            ->context('Cours')
            ->ask('Q ?')
            ->getSpec();

        $this->assertSame('Prof', $spec->persona);
        $this->assertSame('Cours', $spec->context);
        $this->assertSame('Q ?', $spec->question);
    }

    public function test_instruction_must_and_must_not_push_typed_instructions(): void
    {
        $spec = PromptBuilder::make()
            ->instruction('neutre')
            ->must('obligatoire')
            ->mustNot('interdit')
            ->getSpec();

        $this->assertCount(3, $spec->instructions);
        $this->assertSame(Instruction::TYPE_GENERAL, $spec->instructions[0]->getType());
        $this->assertSame(Instruction::TYPE_MUST, $spec->instructions[1]->getType());
        $this->assertSame(Instruction::TYPE_MUST_NOT, $spec->instructions[2]->getType());
        $this->assertSame('obligatoire', $spec->instructions[1]->getText());
    }

    public function test_instruction_callback_builds_children(): void
    {
        $spec = PromptBuilder::make()
            ->instruction('Racine', function (Instruction $i) {
                $i->add('Enfant A')->add('Enfant B');
            })
            ->getSpec();

        $this->assertCount(2, $spec->instructions[0]->getChildren());
    }

    public function test_example_pushes_an_example(): void
    {
        $spec = PromptBuilder::make()->example('in', 'out')->getSpec();

        $this->assertCount(1, $spec->examples);
        $this->assertSame('in', $spec->examples[0]->getInput());
        $this->assertSame('out', $spec->examples[0]->getOutput());
    }

    public function test_withParams_and_setParams_are_equivalent(): void
    {
        $this->assertSame(['a' => 1], PromptBuilder::make()->withParams(['a' => 1])->getSpec()->params);
        $this->assertSame(['b' => 2], PromptBuilder::make()->setParams(['b' => 2])->getSpec()->params);
    }

    public function test_setHistory_appends_turns(): void
    {
        $spec = PromptBuilder::make()
            ->setHistory([['role' => 'user', 'content' => 'A']])
            ->setHistory([['role' => 'assistant', 'content' => 'B']])
            ->getSpec();

        $this->assertSame([
            ['role' => 'user', 'content' => 'A'],
            ['role' => 'assistant', 'content' => 'B'],
        ], $spec->history);
    }

    public function test_when_runs_the_matching_branch(): void
    {
        $ifRan = PromptBuilder::make()->when(true, fn ($b) => $b->ask('oui'))->getSpec()->question;
        $elseRan = PromptBuilder::make()->when(false, fn ($b) => $b->ask('non'), fn ($b) => $b->ask('else'))->getSpec()->question;

        $this->assertSame('oui', $ifRan);
        $this->assertSame('else', $elseRan);
    }

    public function test_expectResponseFormat_stores_valid_json(): void
    {
        $spec = PromptBuilder::make()->expectResponseFormat('{"a":"b"}')->getSpec();
        $this->assertSame('{"a":"b"}', $spec->outputFormat);
    }

    public function test_expectResponseFormat_rejects_invalid_json(): void
    {
        $this->expectException(Exception::class);
        PromptBuilder::make()->expectResponseFormat('{invalid');
    }

    public function test_toPrompt_defaults_to_a_text_string(): void
    {
        $prompt = PromptBuilder::make()->context('Ctx')->ask('Q ?')->toPrompt();

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Ctx', $prompt);
        $this->assertStringContainsString('Q ?', $prompt);
    }

    public function test_toPrompt_delegates_to_a_given_renderer(): void
    {
        $renderer = new class implements RendererInterface {
            public function render(PromptSpec $spec): string|array
            {
                return ['sentinel' => $spec->question];
            }
        };

        $out = PromptBuilder::make()->ask('marqueur')->toPrompt($renderer);

        $this->assertSame(['sentinel' => 'marqueur'], $out);
    }

    public function test_toString_renders_the_text_prompt(): void
    {
        $builder = PromptBuilder::make()->context('Bonjour toString');

        $this->assertStringContainsString('Bonjour toString', (string) $builder);
    }

    public function test_driver_rejects_a_nonexistent_class(): void
    {
        $this->expectException(Exception::class);
        PromptBuilder::make()->driver('No\\Such\\Driver\\Class');
    }

    public function test_driver_rejects_a_class_not_implementing_the_interface(): void
    {
        $this->expectException(Exception::class);
        PromptBuilder::make()->driver(\stdClass::class);
    }

    public function test_getOutput_is_null_before_process(): void
    {
        $this->assertNull(PromptBuilder::make()->getOutput());
    }
}
