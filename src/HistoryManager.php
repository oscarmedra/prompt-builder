<?php
namespace NoahMedra\PromptBuilder;

class HistoryManager {
    private array $history = [];
    private string $historyFile;

    public function __construct() {
        if (!isset($_SESSION['history_file'])) {
            // Si ce n'est pas le cas, on génère un nouveau chemin vers un fichier d'historique
            $_SESSION['history_file'] = __DIR__ . '/storage/history_' . session_id() . '.json';
        }

        // Définir le chemin vers le fichier d'historique (utilise la session pour retrouver le fichier)
        $this->historyFile = $_SESSION['history_file'];


        // Charger l'historique depuis le fichier si il existe
        $this->loadHistory();
    }

    // Charger l'historique depuis le fichier
    private function loadHistory(): void {
        if (file_exists($this->historyFile)) {
            $json = file_get_contents($this->historyFile);
            $this->history = json_decode($json, true);
        }
    }

    // Sauvegarder l'historique dans le fichier
    private function saveHistory(): void {
        file_put_contents($this->historyFile, json_encode($this->history, JSON_PRETTY_PRINT));
    }

    // Ajouter un nouveau prompt et réponse à l'historique
    public function addToHistory(string $input, string $response): void {
        $this->history[] = ['id' => count($this->history) + 1, 'input' => $input, 'response' => $response];
        $this->saveHistory();  // Sauvegarder l'historique après chaque ajout
    }


    public function getHistory(): array {
        return $this->history;
    }
}
