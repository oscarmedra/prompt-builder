<?php

namespace NoahMedra\PromptBuilder\Instructions;

use Closure;

/**
 * A single instruction node in a prompt.
 *
 * This class only holds DATA (text, type, children). It knows nothing
 * about how it will eventually be turned into text — that's the job of
 * a Renderer (see NoahMedra\PromptBuilder\Rendering).
 */
class Instruction
{
    public const TYPE_GENERAL = 'general';
    public const TYPE_MUST = 'must';
    public const TYPE_MUST_NOT = 'must_not';

    /** @var Instruction[] */
    protected array $children = [];

    public function __construct(
        protected string $text,
        protected string $type = self::TYPE_GENERAL,
    ) {
    }

    /**
     * Add a nested sub-instruction. Returns $this (the parent) so several
     * ->add() calls append siblings at the same depth, e.g.:
     *
     *   $instruction->add('Sois concis')->add('Évite le jargon');
     *
     * Pass a $callback to go one level deeper on that specific child.
     */
    public function add(string $text, string $type = self::TYPE_GENERAL, ?Closure $callback = null): self
    {
        $child = new self($text, $type);

        if ($callback instanceof Closure) {
            $callback($child);
        }

        $this->children[] = $child;

        return $this;
    }

    public function when(bool $condition, Closure $ifTrue, ?Closure $ifFalse = null): self
    {
        if ($condition) {
            $ifTrue($this);
        } elseif ($ifFalse) {
            $ifFalse($this);
        }

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /** @return Instruction[] */
    public function getChildren(): array
    {
        return $this->children;
    }
}
