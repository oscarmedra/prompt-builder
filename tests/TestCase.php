<?php

namespace Tests;

use NoahMedra\PromptBuilder\Facades\PromptBuilder;
use NoahMedra\PromptBuilder\PromptBuilderServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for anything that needs a fully booted Laravel app
 * (service container, facades, Http::fake(), ...). Plain composition/
 * rendering tests do NOT need this and should keep extending
 * PHPUnit\Framework\TestCase directly to stay framework-free.
 */
abstract class TestCase extends Orchestra
{
    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [PromptBuilderServiceProvider::class];
    }

    /** @return array<string, class-string> */
    protected function getPackageAliases($app): array
    {
        return ['PromptBuilder' => PromptBuilder::class];
    }
}
