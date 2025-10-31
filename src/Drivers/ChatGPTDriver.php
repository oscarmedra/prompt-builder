<?php

namespace NoahMedra\PromptBuilder\Drivers;

use Illuminate\Support\Facades\Http;

class ChatGPTDriver implements DriverInterface
{


    protected string $apiKey;
    protected string $apiUrl;

    public function __construct(?string $apiKey)
    {
        $this->apiKey = $apiKey ?? $this->apiKey = config('prompt-builder.drivers.chatgpt.api_key');
        $this->apiUrl = 'https://api.openai.com/v1/completions'; // URL de l'API OpenAI
    }
    


    public function sendPrompt(string $prompt, array $parameters = []): string
    {
        // Utilisation de l'API OpenAI pour envoyer la requête au modèle ChatGPT
        $data = [
            'model' => 'gpt-3.5-turbo', // Ou un autre modèle si nécessaire
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $parameters['temperature'] ?? 0.7, // Exemple de paramètre optionnel
        ];

        $response = $this->callAPI($data);

        return $response['choices'][0]['message']['content'] ?? 'Pas de réponse';
    }



    protected function callAPI(array $data)
    {
        // Exemple de fonction pour appeler l'API avec cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}
