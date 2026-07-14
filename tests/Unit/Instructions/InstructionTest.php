<?php

namespace Tests\Unit\Instructions;

use NoahMedra\PromptBuilder\Instructions\Instruction;
use PHPUnit\Framework\TestCase;

class InstructionTest extends TestCase
{
    public function test_defaults_to_general_type_and_no_children(): void
    {
        $instruction = new Instruction('Sois concis');

        $this->assertSame('Sois concis', $instruction->getText());
        $this->assertSame(Instruction::TYPE_GENERAL, $instruction->getType());
        $this->assertSame([], $instruction->getChildren());
    }

    public function test_type_can_be_set(): void
    {
        $this->assertSame(Instruction::TYPE_MUST, (new Instruction('x', Instruction::TYPE_MUST))->getType());
        $this->assertSame(Instruction::TYPE_MUST_NOT, (new Instruction('x', Instruction::TYPE_MUST_NOT))->getType());
    }

    public function test_add_returns_the_parent_so_chained_adds_are_siblings(): void
    {
        $parent = new Instruction('Plan');
        $returned = $parent->add('Un')->add('Deux')->add('Trois');

        $this->assertSame($parent, $returned, 'add() must return the parent for sibling chaining');

        $children = $parent->getChildren();
        $this->assertCount(3, $children);
        $this->assertSame(['Un', 'Deux', 'Trois'], array_map(fn ($c) => $c->getText(), $children));

        // Siblings, not nested: none of them has children.
        foreach ($children as $child) {
            $this->assertSame([], $child->getChildren());
        }
    }

    public function test_add_with_callback_nests_one_level_deeper(): void
    {
        $parent = new Instruction('Racine');
        $parent->add('Enfant', Instruction::TYPE_GENERAL, function (Instruction $child) {
            $child->add('Petit-enfant');
        });

        $childrenLevel1 = $parent->getChildren();
        $this->assertCount(1, $childrenLevel1);

        $grandChildren = $childrenLevel1[0]->getChildren();
        $this->assertCount(1, $grandChildren);
        $this->assertSame('Petit-enfant', $grandChildren[0]->getText());
    }

    public function test_add_can_set_a_child_type(): void
    {
        $parent = new Instruction('Racine');
        $parent->add('Interdit', Instruction::TYPE_MUST_NOT);

        $this->assertSame(Instruction::TYPE_MUST_NOT, $parent->getChildren()[0]->getType());
    }

    public function test_when_true_runs_the_if_branch(): void
    {
        $instruction = new Instruction('Base');
        $called = false;

        $returned = $instruction->when(true, function (Instruction $i) use (&$called) {
            $called = true;
            $i->add('Ajouté');
        });

        $this->assertTrue($called);
        $this->assertSame($instruction, $returned);
        $this->assertCount(1, $instruction->getChildren());
    }

    public function test_when_false_runs_the_else_branch_when_provided(): void
    {
        $instruction = new Instruction('Base');
        $branch = null;

        $instruction->when(
            false,
            function () use (&$branch) { $branch = 'if'; },
            function () use (&$branch) { $branch = 'else'; },
        );

        $this->assertSame('else', $branch);
    }

    public function test_when_false_without_else_is_a_noop(): void
    {
        $instruction = new Instruction('Base');
        $instruction->when(false, function (Instruction $i) {
            $i->add('ne doit pas apparaître');
        });

        $this->assertSame([], $instruction->getChildren());
    }
}
