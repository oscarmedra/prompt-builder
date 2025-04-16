<?php
namespace PromptBuilder;

use Illuminate\Support\ServiceProvider;

class promptBuilderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('promptbuilder', function ($app) {
            return new PromptBuilder();
        });
    }

    public function boot()
    {
        // Publier le fichier de configuration si nécessaire
        $this->publishes([
            __DIR__ . '/../config/prompt-builder.php' => config_path('prompt-builder.php'),
        ], 'config');
    }
}