<?php 

namespace NoahMedra\PromptBuilder;

class BuilderInput
{
    public $input;
    public $params;

    public function __construct(string $input)
    {
        $this->input = $input;
    }

    /**
     * Retourne le texte du prompt fourni.
     *
     * @return string
     */
    public function getPromptText()
    {
        return $this->input;
    }

    /**
     * Retourne les paramètres supplémentaires.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }




    /**
     * Retourne les paramètres supplémentaires.
     *
     * @return array
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }
}
