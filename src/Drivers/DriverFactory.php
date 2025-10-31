<?php

namespace NoahMedra\PromptBuilder\Drivers;

use InvalidArgumentException;

class DriverFactory
{
    /**
     * Crée et retourne l'instance du driver approprié basé sur le modèle.
     */
    public function make(string $model): DriverInterface
    {
        switch ($model) {
            case 'chatgpt':
                return new ChatGPTDriver(config('services.openai.api_key')); // Exemple avec la clé API dans config
            case 'bing':
                // return new BingDriver(config('services.bing.api_key'));
            case 'bard':
                // return new BardDriver(config('services.bard.api_key'));
            case 'deepseek':
                // Crée et retourne l'instance pour DeepSeek
                return new DeepSeekDriver(config('services.deepseek.api_key'));
            case 'ollama':
                // Crée et retourne l'instance pour DeepSeek
                return new OllamaDriver();
            default:
                throw new InvalidArgumentException("Modèle non supporté: {$model}");
        }
    }
}