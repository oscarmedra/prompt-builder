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

    public function boot(): void
    {
        // Register the bundled label translations under the 'promptbuilder'
        // namespace so they're available as trans('promptbuilder::labels.*')
        // and usable by Translation\Laravel\LaravelTranslator.
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'promptbuilder');

        // Let applications publish and override the strings if they want to.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/promptbuilder'),
            ], 'promptbuilder-lang');
        }
    }
}
