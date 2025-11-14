<?php 

namespace NoahMedra\PromptBuilder;

class BuilderOutput{
    private string $output;
    private $data = null;

    
    public function __construct(string $output) {
        $this->output = $output;
        if($this->isValidJson()){
            $this->data = json_decode($this->output, true);
            $this->data = json_decode($output);
        }
    }




    public function get(string $path){
        $keys = explode('.', $path);

        $data = $this->data;
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

    private function isValidJson(){
        json_decode($this->output);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}     