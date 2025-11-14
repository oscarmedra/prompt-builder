<?php 

namespace NoahMedra\PromptBuilder;

class BuilderOutput{
    protected string $output;
    protected $data;

    
    public function __construct(string $output) {
        $this->output = $output;
        if($this->isValidJson()){
            $this->data = json_decode($this->output, true);
        }
        $this->data = json_decode($output);
    }




    public function get(string $path){
        $keys = explode('.', $path);

        foreach ($keys as $key) {
            if (is_array($data)) {
                if (isset($data[$key])) {
                    $data = $data[$key];
                } else {
                    return null;
                }
            }elseif (is_object($data)) {
                if (isset($data->{$key})) {
                    $data = $data->{$key};
                } else {
                    return null; 
                }
            } else {
                return null;
            }
        }
    }

    private function isValidJson(){
        json_decode($this->output);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}     