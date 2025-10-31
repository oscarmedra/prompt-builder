<?php
namespace NoahMedra\PromptBuilder;

use Closure;
use Illuminate\Support\Facades\App;

class PromptBuilder
{
    protected string $model = 'ollama';    // Un seul modèle par défaut
    protected string $language = 'fr';
    protected string $tone = 'neutre';
    protected string $prompt;
    protected array $callbacks = [];
    protected bool $expectJson = true;
    protected array $handlers = [];
    protected string $context = '';
    protected array $parameters = [];



    public function withParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public static function make(): self
    {
        return new self();
    }

    public function handle(Closure $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    public function expectJson(bool $expect = true): self
    {
        $this->expectJson = $expect;
        return $this;
    }

    public function for(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function language(string $lang): self
    {
        $this->language = $lang;
        return $this;
    }

    public function tone(string $tone): self
    {
        $this->tone = $tone;
        return $this;
    }

    public function ask(string $question): self
    {
        $this->prompt = $question;
        return $this;
    }

    public function then(Closure $callback): self
    {
        $this->callbacks[] = $callback;
        return $this;
    }

    public function context(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function run()
    {
        $response = app(Drivers\DriverFactory::class)
            ->make($this->model)
            ->sendPrompt($this->buildPrompt());
        // Préparer le dataset JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Réponse invalide du modèle IA.");
        }

        // Passage par les callbacks
        foreach ($this->callbacks as $callback) {
            $response = $callback($response);
        }

        // Préparer le dataset JSON
        $data = json_decode($response, true);
        $dataset = collect($data)->map(fn($item) => (object) $item);

        // Appeler tous les handlers
        foreach ($this->handlers as $handler) {
            $handler($dataset);
        }

        return $response;
    }

    protected function buildPrompt(): string
    {
        $formatted = $this->prompt;
        foreach ($this->parameters as $key => $value) {
            $formatted = str_replace("{" . $key . "}", $value, $formatted);
        }

        $contextPart = $this->context ? "[Contexte : {$this->context}]\n" : '';
        $prompt = "{$contextPart}{$formatted} (Langue: {$this->language}, Ton: {$this->tone})";
        if ($this->expectJson) {
            $prompt .= "\nRéponds uniquement en JSON, sans texte supplémentaire.";
        }
        return $prompt;
    }
}