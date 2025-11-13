<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use NoahMedra\PromptBuilder\Drivers\DriverFactory;
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;  // Exemple de driver par défaut
use App\Drivers\MonDriver;  // Exemple de driver personnalisé

class PromptDriverServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Enregistrement de la factory des drivers
        $this->app->singleton(DriverFactory::class, function ($app) {
            $factory = new DriverFactory();

            // Enregistrement des drivers par défaut
            $factory->registerCustomDriver('ollama', new OllamaDriver());

            // L'utilisateur peut aussi ajouter ses propres drivers personnalisés
            // Exemple : $factory->registerCustomDriver('monModel', new MonDriver());

            return $factory;
        });
    }


    public function registerCustomDriver()

    public function register()

    public function boot()
    {
        // Vous pouvez aussi publier des fichiers de configuration si nécessaire
        // Exemple : publication de la configuration des drivers
        $this->publishes([
            __DIR__ . '/../config/prompt-drivers.php' => config_path('prompt-drivers.php'),
        ], 'config');
    }
}
