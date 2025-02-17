<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$society = $_SESSION['society'];
$firstName = $_SESSION['first_name']; // exemple de valeur pour firstName
$lastName = $_SESSION['family_name']; // exemple de valeur pour familyName

// Chemin vers le fichier society_info.json
$society_info_file = "society/$society/society_info.json";

// Vérifier si le fichier society_info.json existe
if (file_exists($society_info_file)) {
    // Charger le contenu de society_info.json
    $society_info_json = file_get_contents($society_info_file);
    // Décoder le JSON en tableau associatif
    $society_info = json_decode($society_info_json, true);

    // Vérifier si le décodage JSON est réussi
    if ($society_info === null) {
        echo "Erreur: Impossible de décoder society_info.json.";
        exit();
    }
} else {
    echo "Erreur: society_info.json non trouvé.";
    exit();
}

// Chemin vers le fichier historylist.json
$history_list_file = "society/$society/$user_id/historylist.json";


// Vérifier si le fichier historylist.json existe
if (file_exists($history_list_file)) {
    // Charger le contenu de historylist.json
    $history_list_json = file_get_contents($history_list_file);
    // Décoder le JSON en tableau associatif
    $history_list = json_decode($history_list_json, true);

    // Vérifier si le décodage JSON est réussi
    if ($history_list === null) {
        echo "Erreur: Impossible de décoder historylist.json.";
        exit();
    }
} else {
    echo "Erreur: historylist.json non trouvé.";
    exit();
}


//Set des variables

// Liste des fichiers de conversation
$histories = $history_list['history'];

// Récupérer le type d'acceptation (image ou text)
$accept_type = $society_info['accept'] ?? 'text';
$server_ip = $society_info['server_ip'] ?? 'text';




function message_text($texte, $current_history) {
    echo '<script type="text/javascript"> loadMessage(selectedTab); </script>';
    // Charger le fichier JSON
    $fichier = "society" . "/" . $GLOBALS['society'] . "/" . $GLOBALS['user_id'] . "/" . $current_history;
    if (!file_exists($fichier)) {
        echo "Le fichier conversation.json n'existe pas. $fichier";
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
    echo '<script type="text/javascript"> loadMessage(selectedTab); </script>'; 
    curl_close($ch);
    return $response;
}

function message_image($texte, $image_base64, $current_history) {
    echo '<script type="text/javascript"> loadMessage(selectedTab); </script>';
    // Charger le fichier JSON
    $fichier = "society" . "/" . $GLOBALS['society'] . "/" . $GLOBALS['user_id'] . "/" . $current_history;
    if (!file_exists($fichier)) {
        echo "Le fichier history.json n'existe pas.";
        return;
    }
    $history = json_decode(file_get_contents($fichier), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur de décodage JSON : " . json_last_error_msg();
        return;
    }

    // Ajouter le nouveau message de l'utilisateur avec ou sans image
    if ($image_base64 === null) {
        $nouveau_message_utilisateur = array("role" => "user", "content" => $texte);
    } else {
        $nouveau_message_utilisateur = array("role" => "user", "content" => $texte, "images" => [$image_base64]);
    }
    $history["messages"][] = $nouveau_message_utilisateur;
    
    // Écrire le fichier JSON mis à jour (avec le nouveau message utilisateur)
    if (file_put_contents($fichier, json_encode($history, JSON_PRETTY_PRINT)) === false) {
        echo "Erreur lors de l'écriture du fichier history.json.";
        return;
    }
    
    // Faire la requête CURL
    $url = 'http://localhost:11434/api/chat';
    $headers = array('Content-Type: application/json');
    $data = json_encode($history);
    
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
        $contenu_message_assistant = $reponse_json["message"];
        $history["messages"][] = $contenu_message_assistant;
        
        // Écrire le fichier JSON mis à jour (avec la réponse de l'assistant)
        if (file_put_contents($fichier, json_encode($history, JSON_PRETTY_PRINT)) === false) {
            echo "Erreur lors de l'écriture du fichier history.json.";
            return;
        }
        
        echo "Réponse ajoutée au fichier JSON avec succès !\n";
    } else {
        echo "Erreur lors de l'envoi de la requête : $http_code\n";
        echo "Réponse du serveur : $response\n";
    }
    echo '<script type="text/javascript"> loadMessage(selectedTab); </script>'; 
    curl_close($ch);
    return $contenu_message_assistant["content"];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['form'])) {
        ob_start();
        $form = $_POST['form'];
        if ($form == 'text') {
            message_text($_POST['question'], $_POST['current_history']);
        } elseif ($form == 'image') {
            $texte_recu = $_POST["question"];
            $image_base64 = null;
    
            if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
                $image_data = file_get_contents($_FILES["image"]["tmp_name"]);
                $image_base64 = base64_encode($image_data);
            }
    
            message_image($texte_recu, $image_base64, $_POST['current_history']);
        } else {
            echo "Formulaire non reconnu.";
        }
        $output = ob_get_clean();
        echo json_encode(['result' => $output]);
        exit;
    } else {
        echo json_encode(['result' => 'Aucun formulaire soumis.']);
        exit;
    }
}
?>