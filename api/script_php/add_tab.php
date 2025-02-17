<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Lire la configuration de la base de données à partir d'un fichier JSON
$config = json_decode(file_get_contents('../hexhal_info.json'), true);

$host = $config['server_address'];
$port = $config['port'];
$user = $config['user'];
$pass = $config['password'];
$db = $config['database'];

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Assurez-vous que $society et $user_id sont définis (peut-être depuis une session ou une autre source)
$society = $_SESSION['society'] ?? 'default_society';
$user_id = $_SESSION['user_id'] ?? 'default_user';

// Lire les informations de la société depuis society_info.json
$societyInfoPath = "../society/$society/society_info.json";
if (!file_exists($societyInfoPath)) {
    die("Le fichier society_info.json est introuvable.");
}
$societyInfo = json_decode(file_get_contents($societyInfoPath), true);
$model = $societyInfo['model'] ?? 'unknown_model';

// Étape 1 : Créer une nouvelle table
$tableName = 'new_table';
$sql = "CREATE TABLE IF NOT EXISTS $tableName (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$pdo->exec($sql);
echo "Table $tableName créée avec succès.<br>";

// Étape 2 : Ajouter un nouveau fichier history_*.json
$newHistoryFile = "history_" . uniqid() . ".json";
$historyFilePath = "../society/$society/$user_id/" . $newHistoryFile;

// Créer les répertoires si nécessaire
if (!is_dir("../society/$society/$user_id/")) {
    mkdir("../society/$society/$user_id/", 0777, true);
}

// Créer un nouveau fichier history_*.json avec le modèle et un contenu initial
$initialContent = json_encode([
    "model" => $model,
    "messages" => [],
    "stream" => false
]);
file_put_contents($historyFilePath, $initialContent);

echo "Nouveau fichier $historyFilePath créé avec succès.<br>";

// Étape 3 : Mettre à jour historylist.json
$historyListFile = "../society/$society/$user_id/historylist.json";

// Lire le contenu actuel de historylist.json ou initialiser un tableau vide s'il n'existe pas
if (file_exists($historyListFile)) {
    $historyListContent = json_decode(file_get_contents($historyListFile), true);
} else {
    $historyListContent = ["history" => []];
}

// Ajouter le nouveau fichier dans le tableau avec un nom par défaut
$historyListContent['history'][] = ["name" => "Nouveau Chat", "file" => $newHistoryFile];

// Écrire le contenu mis à jour dans historylist.json
file_put_contents($historyListFile, json_encode($historyListContent));

echo "Fichier historylist.json mis à jour avec succès.";
?>
