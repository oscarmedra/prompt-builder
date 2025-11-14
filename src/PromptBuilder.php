<?php
namespace NoahMedra\PromptBuilder;

use App\Providers\PromptDriverServiceProvider;
use Closure;
use Exception;
use NoahMedra\PromptBuilder\BuilderOutput;
use NoahMedra\PromptBuilder\BuilderInput;
use NoahMedra\PromptBuilder\Drivers\HuggingFaceDriver;
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
    private $manager;
    private ?string $jsonFormat = null;
    private ?PromptDriverInterface $driver = null;
    private ?BuilderInput $input;
    private ?BuilderOutput $output;

    public function __construct()
    {   
        $this->instructions = collect([]);
        $this->manager = new HistoryManager();
        $this->driver = new OllamaDriver();
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


    public function jsonify(string $json): self
    {        
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Json format invalide.');
        }

        $this->expectJson = true;
        $this->jsonFormat = $json;
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
        $this->output = $this->driver->process($this->input);
    }



    private function getContext() {
        $context = $this->context ? "### Contexte : {$this->context}". PHP_EOL : '';


        if ($this->use_history == true) {

            $history = $this->manager->getHistory();

        
            if (!empty($history)) {
                $context .= "### Voici l'historique de vos discussions :\n";
                foreach ($history as $entry) {
                    $context .= "User: {$entry['input']}\n You: {$entry['output']}\n";
                }
            }
        }


        if($this->expectJson == true){
            $format = is_null($this->jsonFormat) ? 'Votre réponse' : $this->jsonFormat;

            $this->instruction("
                Veuillez structurer votre réponse en respectant le format JSON ci-dessous. Les données que vous allez fournir seront utilisées par une application tierce et seront probablement décodées ou traitées comme une ressource de données. Il est donc essentiel que vous respectiez le format indiqué pour garantir une bonne compatibilité avec le système cible.

                [
                    \"resume\": \"Un résumé succinct de votre réponse, contenant l'essentiel, limité à 200 caractères.\",
                    \"response\": {$format}
                ]

                ***Assurez-vous que :
                - Toutes les chaînes de texte contenant des guillemets doivent avoir les guillemets échappés (par exemple, \"votre texte\").
                - Les virgules ne doivent pas apparaître après le dernier élément dans une liste ou un objet.
            ");
        }

        return $context;
    }


    private function buildPrompt(): string
    {
        $finalPrompt = $this->getContext(); // Ajoute le contexte si nécessaire

        $this->instruction("### Attente : La réponse de l'utilisateur doit impérativement être en **JSON**, sans texte supplémentaire.

            Exemple de réponse formatée en JSON :

            {
                \"resume\": \"Un résumé concis de la réponse.\",
                \"response\": \"$this->jsonFormat\"
            }

            ". PHP_EOL);

        // Si des instructions sont définies, les inclure dans le prompt
        if (!$this->instructions->isEmpty()) {
            $finalPrompt .= "### Instructions : ". PHP_EOL;
        }

        // Ajout des instructions formatées dans le prompt
        foreach ($this->instructions as $instruction) {
            $depth = 1;
            // On assure que le format de chaque instruction est bien respecté
            $formattedInstruction = $instruction->formatToText($depth);
            
            // Ajout de l'instruction formatée au prompt final
            $finalPrompt .= $formattedInstruction;
        }

        // Si une demande est définie, l'ajouter à la fin du prompt
        $finalPrompt .= $this->ask ? "### Demande: {$this->ask}\n" : '';

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
        if(is_null($this->output)){
            $this->process();
        }
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