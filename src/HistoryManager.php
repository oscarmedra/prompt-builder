<?php 

namespace NoahMedra\PromptBuilder;

use Illuminate\Support\Facades\Storage;

class HistoryManager {
    private array $history = [];
    private string $historyFile;

    public function __construct() {
        // Définir le chemin vers le fichier d'historique dans le répertoire de stockage
        $this->historyFile = storage_path('app/history_' . session_id() . '.json');

        // Charger l'historique depuis le fichier s'il existe
        $this->loadHistory();
    }

    // Charger l'historique depuis le fichier
    private function loadHistory(): void {
        // Vérifier si le fichier existe et le lire
        if (Storage::exists($this->historyFile)) {
            $json = Storage::get($this->historyFile);
            $this->history = json_decode($json, true);
        }
    }

    // Sauvegarder l'historique dans le fichier
    private function saveHistory(): void {
        // Sauvegarder l'historique dans le fichier JSON
        Storage::put($this->historyFile, json_encode($this->history, JSON_PRETTY_PRINT));
    }

    // Ajouter un nouveau prompt et réponse à l'historique
    public function addToHistory(string $input, string $response): void {
        $this->history[] = [
            'id' => count($this->history) + 1,
            'input' => $input,
            'output' => $response
        ];
        $this->saveHistory();  // Sauvegarder l'historique après chaque ajout
    }

    // Récupérer l'historique
    public function getHistory(): array {
        return $this->history;
    }
}
