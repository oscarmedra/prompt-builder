<?php 

namespace NoahMedra\PromptBuilder;

class BuilderInput {

    protected array $params = [];
    protected array $bs4_files = [];  // Tableau pour stocker les fichiers Base64

    // Méthode pour ajouter des fichiers Base64
    public function addBs4File(string $base64file): self
    {
        $this->bs4_files[] = $base64file;
        return $this;
    }

    // Méthode pour récupérer les fichiers Base64
    public function getBs4Files(): array
    {
        return $this->bs4_files;
    }

    // Méthode pour définir les paramètres
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }


    public function getParam(string $path){
        $keys = explode('.', $path);

        $data = $this->params;
        foreach ($keys as $key) {
            if (is_array($data)) {
                if (isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    $data = null;
                }
            }elseif (is_object($data)) {
                if (isset($data->{$key})) {
                    $data = $data->{$key};
                } else {
                    $data = null;
                }
            } else {
                $data = null;
            }
        }


        return $data;
    }

}

