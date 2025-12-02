<?php
namespace NoahMedra\PromptBuilder;

use Closure;
use Exception;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\Drivers\OllamaDriver;
use NoahMedra\PromptBuilder\Drivers\PromptDriverInterface;

class PromptBuilder
{
    protected $params = [];
    protected string $ask;
    protected bool $expectJson = false;
    protected string $context = '';
    private $instructions;
    protected bool $use_history = false; 
    protected $history = [];
    private $manager;
    private ?string $jsonFormat = null;
    private ?PromptDriverInterface $driver = null;
    private ?BuilderInput $input;
    private ?BuilderOutput $output;

    public function __construct()
    {   
        $this->instructions = collect([]);
        $this->manager = new HistoryManager();
        $this->driver = new \NoahMedra\PromptBuilder\Drivers\OllamaDriver();

    }



    /**
     * Permet de définir dynamiquement quel driver utiliser.
     *
     * @param string $driverClass
     * @return $this
     */
    public function driver(string $driverClass): self
    {
        if (!class_exists($driverClass) || !is_subclass_of($driverClass, PromptDriverInterface::class)) {
            throw new \Exception("Le driver spécifié n'existe pas ou ne respecte pas l'interface : {$driverClass}");
        }

        $this->driver = new $driverClass();  // Instanciation dynamique

        return $this;
    }
    

    

    public static function make(): self
    {
        return new self();
    }



    public function useHistory(bool $status = true) : self{
        $this->use_history = $status;
        return $this;
    }

    public function withParams(array $params){
        $this->params = $params;
        return $this;
    }

    // Méthode pour ajouter des instructions directement
    public function instruction(string $instructionText, ?Closure $callback = null): self{
        $instruction = new InstructionBuilder($instructionText);
        if($callback instanceof Closure){
            $callback($instruction);
        }
        $this->instructions->push($instruction);
        return $this;
    }


    public function expectResponseFormat(string $format): self
    {        
        $decoded = json_decode($format, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Json format invalide.');
        }

        $this->expectJson = true;
        $this->jsonFormat = $format;
        return $this;
    }


    public function setHistory(array $history){
        $this->use_history = true;
        $this->history = [
            ...($this->history ?? []),
            ...($history)
        ];

        return $this;
    }

    public function ask(string $question): self
    {
        $this->ask = $question;
        return $this;
    }


    public function context(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function process()
    {
        $prompt = $this->buildPrompt();
        $this->input = new BuilderInput($prompt);
        $this->input->setParams($this->params);
        $this->input->setHistory($this->history);
        $this->output = $this->driver->process($this->input);
    }



    private function getContext(): string{
        // Si un contexte est défini, on l'ajoute
        $context = $this->context ? "[#]:Voici le contexte : {$this->context}" . PHP_EOL : '';

        // Si l'historique des conversations doit être utilisé
        // if ($this->use_history === true) {
        //     // $history = $this->manager->getHistory();
        //     $history = $this->history;

        //     // Si l'historique est non vide, on l'ajoute au contexte
        //     if (!empty($history)) {
        //         $context .= "[#]:Voici l'historique de vos discussions :\n";
        //         foreach ($history as $entry) {
        //             $context .= "User: {$entry['input']}\nYou: {$entry['output']}\n";
        //         }
        //     }
        // }

        // Si le format JSON est attendu, on ajoute une instruction pour cela
        if ($this->expectJson === true) {
            $format = $this->jsonFormat;

            // Ajout des instructions pour garantir un format JSON correct
            $this->instruction(
                "Vous devez absolument structurer votre réponse en JSON valide. 
                Ce JSON sera traité par une application tierce et décodé automatiquement ; 
                le moindre écart de format entraînera une erreur de parsing.",
                function($ist) use ($format) {

                    $ist->add("Toutes les chaînes de texte contenant des guillemets doivent être échappées correctement (par exemple : \"texte\")");
                    $ist->add("Aucune virgule ne doit apparaître après le dernier élément d'une liste ou d'un objet JSON");
                    $ist->add("Le respect strict du format est obligatoire, car le résultat sera décodé par une fonction JSON et doit donc être parfaitement valide");
                    $ist->add("Voici le format JSON EXACT attendu : $format");
                }
            );
        }

        return $context;
    }

    private function buildPrompt(): string
    {
        // Commence par ajouter le contexte si nécessaire
        $finalPrompt = $this->getContext();

        // Si des instructions sont définies, on les inclut dans le prompt
        if (!$this->instructions->isEmpty()) {
            $finalPrompt .= "[#]:Voici les instructions que vous devez impérativement respecter : " . PHP_EOL;
            
            // Ajouter chaque instruction, formatée pour assurer qu'elle est bien suivie
            foreach ($this->instructions as $instruction) {
                $depth = 1;  // Si tu as une logique de profondeur, tu peux ajuster cette variable
                $formattedInstruction = $instruction->formatToText($depth);

                // Ajout de l'instruction formatée au prompt final
                $finalPrompt .= $formattedInstruction . PHP_EOL;
            }
        }

        // Si une question est définie, elle est ajoutée à la fin du prompt
        if ($this->ask) {
            $finalPrompt .= "[#]:Voici la question à laquelle vous devez répondre, en respectant les instructions et le contexte : {$this->ask}" . PHP_EOL;
        }

        return $finalPrompt;
    }


    public function when(bool $condition, Closure $ifc, ?Closure $elsec = null): self{
        if ($condition) {
            $ifc($this);
        } elseif ($elsec) {
            $elsec($this);
        }
        
        return $this;
    }



    public function setInput(BuilderInput $input) : self{
        $this->input = $input;
        return $this;
    }


    public function getOutput() : BuilderOutput{
        return $this->output;
    }


    public function getInput() : BuilderInput{
        return $this->input;
    }


    public function setParams(array $params){
        $this->params = $params;
        return $this;
    }
}