<?php

namespace NoahMedra\PromptBuilder\Drivers;

class OllamaDriver implements DriverInterface
{
    protected string $apiUrl;
    protected string $model;

    public function __construct(string $model = 'mistral')
    {
        $this->apiUrl = 'http://localhost:11434/api/generate';
        $this->model = $model;
    }

    public function sendPrompt(string $prompt, array $parameters = []): string
    {
        $data = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
        ];

        // Ajout de paramètres facultatifs
        if (isset($parameters['temperature'])) {
            $data['temperature'] = $parameters['temperature'];
        }

        $response = $this->callAPI($data);

        return $response['response'] ?? 'Pas de réponse d’Ollama';
    }

    protected function callAPI(array $data): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Erreur cURL : ' . $error);
        }

        return json_decode($result, true);
    }
}