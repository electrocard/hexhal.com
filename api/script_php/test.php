<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Récupération des données POST
$action = $_POST['action'] ?? '';
$society = $_POST['society'] ?? '';
$user_id = $_POST['user_id'] ?? '';
$file = $_POST['file'] ?? '';
$new_name = $_POST['new_name'] ?? '';

// Vérification que les données essentielles sont présentes
if (empty($action) || empty($society) || empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

// Chemin du fichier d'historique
$historyListPath = "../society/$society/$user_id/historylist.json";

// Vérification de l'existence du fichier d'historique
if (!file_exists($historyListPath)) {
    echo json_encode(['status' => 'error', 'message' => 'History list file not found.']);
    exit;
}

// Chargement du contenu du fichier d'historique
$historyList = json_decode(file_get_contents($historyListPath), true);

// Traitement en fonction de l'action
if ($action === 'delete') {
    // Supprimer l'onglet
    foreach ($historyList['history'] as $key => $tab) {
        if ($tab['file'] === $file) {
            unset($historyList['history'][$key]);
            // Suppression du fichier associé
            $filename = "../society/$society/$user_id/$file";
            if (file_exists($filename)) {
                unlink($filename);
            }
            $historyList['history'] = array_values($historyList['history']); // Ré-indexer les clés du tableau
            file_put_contents($historyListPath, json_encode($historyList, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success', 'message' => 'Tab and file deleted successfully.']);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Tab not found.']);
} elseif ($action === 'rename') {
    // Renommer l'onglet (mettre à jour seulement l'indexation)
    foreach ($historyList['history'] as $key => $tab) {
        if ($tab['file'] === $file) {
            $historyList['history'][$key]['name'] = $new_name;
            file_put_contents($historyListPath, json_encode($historyList, JSON_PRETTY_PRINT));
            echo json_encode(['status' => 'success', 'message' => 'Tab renamed successfully.']);
            exit;
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Tab not found.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
