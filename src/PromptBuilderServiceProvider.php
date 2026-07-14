<?php

namespace NoahMedra\PromptBuilder;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the 'promptbuilder' container binding used by the
 * Facades\PromptBuilder facade.
 *
 * Auto-discovered by Laravel via extra.laravel.providers in composer.json,
 * so consumers get the facade working with zero configuration. Nothing in
 * the composition/rendering layer depends on this being registered — it is
 * purely the Laravel-integration convenience layer.
 */
class PromptBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // bind (not singleton): each resolution hands back a fresh builder
        // so two facade call-chains never share mutable PromptSpec state.
        $this->app->bind('promptbuilder', fn () => new PromptBuilder());
    }
}
