<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer le contenu du paragraphe avec la classe hidden_p
    $user_id = $_POST["user_id"];
    $society = $_POST["society"];
    $current_history = $_POST["current_history"];

    
    echo "Chemin de l'history society/" . $society . "/" .$user_id . $current_history;
} else {
    echo "Erreur : méthode de requête incorrecte.";
}

// Fonction pour ajouter un message
function ajouter_message($texte) {
    // Charger le fichier JSON
    $fichier = 'society/hexhal/eliocammarata828097/history_1.json';
    if (!file_exists($fichier)) {
        echo "Le fichier conversation.json n'existe pas.";
        return;
    }
    $conversation = json_decode(file_get_contents($fichier), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur de décodage JSON : " . json_last_error_msg();
        return;
    }

    // Ajouter le nouveau message de l'utilisateur/
    $nouveau_message_utilisateur = array("role" => "user", "content" => $texte);
    $conversation["messages"][] = $nouveau_message_utilisateur;
    
    // Écrire le fichier JSON mis à jour (avec le nouveau message utilisateur)
    if (file_put_contents($fichier, json_encode($conversation, JSON_PRETTY_PRINT)) === false) {
        echo "Erreur lors de l'écriture du fichier history.json.";
        return;
    }
    
    // Faire la requête CURL
    $url = 'http://localhost:11434/api/chat';
    $headers = array('Content-Type: application/json');
    $data = json_encode($conversation);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Vérifier la réponse de la requête
    if ($http_code == 200) {
        // Imprimer la réponse de la requête pour voir sa structure
        echo "Réponse de la requête CURL :\n";
        echo $response;
        
        // Extraire la réponse JSON de la requête
        $reponse_json = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Erreur de décodage JSON de la réponse : " . json_last_error_msg();
            return;
        }

        // Ajouter la réponse de l'assistant au fichier JSON
        $contenu_message_assistant = $reponse_json["message"]["content"];
        
        // Créer un message pour l'assistant dans le format JSON
        $nouveau_message_assistant = array("role" => "assistant", "content" => $contenu_message_assistant);
        $conversation["messages"][] = $nouveau_message_assistant;
        
        // Écrire le fichier JSON mis à jour (avec la réponse de l'assistant)
        if (file_put_contents($fichier, json_encode($conversation, JSON_PRETTY_PRINT)) === false) {
            echo "Erreur lors de l'écriture du fichier history.json.";
            return;
        }
        
        echo "Réponse ajoutée au fichier JSON avec succès !\n";
    } else {
        echo "Erreur lors de l'envoi de la requête : $http_code\n";
        echo "Réponse du serveur : $response\n";
    }
    
    curl_close($ch);
    return $response;
}

// Exemple d'utilisation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $texte_recu = $_POST["question"];
    ajouter_message($texte_recu);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Document Query</title>
</head>
<body>
    <h1>Document Query</h1>
    <p>Ask Something</p>
    <form method="post">
        <label for="question">Question:</label><br>
        <textarea id="question" name="question" rows="4" cols="50"></textarea><br>
        <input type="submit" value="Envoyer">
    </form>
</body>
</html>
