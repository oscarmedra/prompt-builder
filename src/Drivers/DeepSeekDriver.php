<?php

namespace NoahMedra\PromptBuilder\Drivers;

class DeepSeekDriver implements DriverInterface
{
    protected string $apiKey;
    protected string $apiUrl;
    protected array $config;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->apiUrl = $config['endpoint']; // URL de l'API DeepSeek (à ajuster)
    }

    public function sendPrompt(string $prompt, array $parameters = []): string
    {
        // Exemple de données envoyées dans le corps de la requête
        $data = [
            'query' => $prompt,
            'language' => $parameters['language'] ?? 'fr',
            'temperature' => $parameters['temperature'] ?? 0.7, // Paramètre optionnel
            // Tu peux ajouter d'autres paramètres selon l'API de DeepSeek
        ];

        $response = $this->callAPI($data);

        // Si l'API renvoie un tableau avec une réponse
        return $response['result'] ?? 'Pas de réponse';
    }


    public function callAPI(array $data)
    {
        // Fonction pour envoyer une requête à l'API DeepSeek avec cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // Exécution de la requête
        $result = curl_exec($ch);
        curl_close($ch);

        // Retourne la réponse décodée en JSON
        return json_decode($result, true);
    }
}