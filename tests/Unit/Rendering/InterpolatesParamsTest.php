<?php

namespace Tests\Unit\Rendering;

use NoahMedra\PromptBuilder\Rendering\Concerns\InterpolatesParams;
use PHPUnit\Framework\TestCase;

class InterpolatesParamsTest extends TestCase
{
    /** Exposes the protected trait methods for testing in isolation. */
    private function subject(): object
    {
        return new class {
            use InterpolatesParams;

            public function interp(string $text, array $params): string
            {
                return $this->interpolate($text, $params);
            }

            public function resolve(string $path, array $params): mixed
            {
                return $this->resolveParam($path, $params);
            }
        };
    }

    public function test_no_params_returns_text_unchanged(): void
    {
        $this->assertSame('Salut {nom}', $this->subject()->interp('Salut {nom}', []));
    }

    public function test_simple_and_nested_placeholders(): void
    {
        $s = $this->subject();
        $params = ['nom' => 'Sam', 'user' => ['role' => 'admin']];

        $this->assertSame('Salut Sam', $s->interp('Salut {nom}', $params));
        $this->assertSame('Rôle : admin', $s->interp('Rôle : {user.role}', $params));
    }

    public function test_multiple_placeholders_in_one_string(): void
    {
        $out = $this->subject()->interp('{a}-{b}-{a}', ['a' => 'X', 'b' => 'Y']);
        $this->assertSame('X-Y-X', $out);
    }

    public function test_unknown_placeholder_is_preserved(): void
    {
        $this->assertSame('{inconnu}', $this->subject()->interp('{inconnu}', ['a' => 1]));
    }

    public function test_numeric_values_are_cast_to_string(): void
    {
        $this->assertSame('Total : 42', $this->subject()->interp('Total : {n}', ['n' => 42]));
    }

    public function test_hyphenated_tokens_are_not_treated_as_placeholders(): void
    {
        // The pattern only accepts [a-zA-Z0-9_.], so {a-b} is left as-is.
        $this->assertSame('{a-b}', $this->subject()->interp('{a-b}', ['a-b' => 'nope']));
    }

    public function test_resolve_param_returns_null_for_missing_or_scalar_descent(): void
    {
        $s = $this->subject();

        $this->assertSame('admin', $s->resolve('user.role', ['user' => ['role' => 'admin']]));
        $this->assertNull($s->resolve('user.missing', ['user' => ['role' => 'admin']]));
        $this->assertNull($s->resolve('user.role.deeper', ['user' => ['role' => 'admin']]));
    }
}
