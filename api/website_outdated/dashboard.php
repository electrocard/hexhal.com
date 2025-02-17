<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <link rel="stylesheet" type="text/css" href="css/context.css">
    <link rel="stylesheet" type="text/css" href="css/delete_button.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <script src="javascript/context.js"></script>


</head>
<body>
<div id="contextMenu" class="context-menu">
    <div id="contextInfo" style="padding: 8px;"></div>
    <button id="deleteButton" onclick="deleteItem()">Supprimer</button>
    <button id="renameButton" onclick="renameItem()">Renommer</button>
</div>

    <?php
    function raccourcir_texte($texte, $longueur_max = 2, $suffixe = '...') {
        if (mb_strlen($texte) <= $longueur_max) {
            return $texte;
        } else {
            return mb_substr($texte, 0, $longueur_max) . $suffixe;
        }
    }
    ?>
    <button class="user-info-button" id="user-info-btn"><img class="icon-conv" src="https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=<?php echo htmlspecialchars($firstName ?? ''); ?>+<?php echo htmlspecialchars($lastName ?? ''); ?>"></button>
    
    <div class="sidebar">
        <button id="fixed-tab" class="embeding-tab">Embeding</button>
        <button class="embeding-tab" id="add-tab">Nouveau Chat</button>
        <?php foreach ($histories as $index => $history): ?>

        <button class="tab-button" data-file="<?php echo htmlspecialchars($history['file']); ?>" value="<?php echo htmlspecialchars($history['file']); ?>" oncontextmenu="showContextMenu(event)"><?php echo htmlspecialchars(raccourcir_texte($history['name'], 20)); ?></button>
        
    
        <?php endforeach; ?>
    </div>
    <div class="content">
        <div class="chat-container" id="chat-container">
            Conversation de l'onglet sélectionné
            <div id="current-history-name" style="display: none;">


</div>
</div>
    <div id="form" class="form">
    <?php if ($accept_type == "image"): ?>

<form class="input-container" action="request_image.php" method="post" enctype="multipart/form-data">
    <input type="file" name="image" accept="image/*" required onchange="loadFile(event)">
    <input type="text" id="question" name="question" required placeholder="Entree Texte">
    <input type="hidden" name="fichier_json" id="fichier_json">
    <button type="submit">Envoyer</button>
</form>

<?php else: ?>

<form class="input-container" method="post" enctype="multipart/form-data">
    <input type="text" id="question" name="question" required placeholder="Entree Texte">
    <input type="hidden" name="fichier_json" id="fichier_json">
    <button type="submit">Envoyer</button>
    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>"> <!-- Ajout d'un champ hidden avec le contenu -->
    <input type="hidden" name="society" value="<?php echo $society; ?>">
    <input type="hidden" name="server_ip" value="<?php echo $server_ip; ?>">

</form>

<?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="user-info-modal" class="modal">
        <div class="modal-content">
            <span class="close" id="close-modal">&times;</span>
            <p>Nom : "<?php echo htmlspecialchars($user_id); ?>"</p>
            <p>Société : "<?php echo htmlspecialchars($society_info['society'] ?? ''); ?>"</p>
            <p>Model : "<?php echo htmlspecialchars($society_info['model_name'] ?? ''); ?>"</p>
            <p>Accepte les requêtes : "<?php echo htmlspecialchars($society_info['accept'] ?? ''); ?>"</p>
        </div>
    </div>

<script>
    document.getElementById('add-tab').addEventListener('click', function() {
        // Utiliser fetch pour faire la requête AJAX
        fetch('script_php/add_tab.php') // Change 'external.php' avec le chemin de ton fichier PHP
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors de l\'exécution du fichier PHP: ' + response.statusText);
                }
                return response.text();
            })
            .then(data => {
                console.log('Exécution PHP réussie:', data);
                // Recharger la page après l'exécution réussie
                location.reload();
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    });



    var loadFile = function(event) {
    var output = document.getElementById('output');
    output.src = URL.createObjectURL(event.target.files[0]);
    output.onload = function() {
      URL.revokeObjectURL(output.src) // free memory
    }
    };

    var clearImage = function() {
    var output = document.getElementById('output');
    output.src = ''; // Clear the image source
    }   ;




    document.addEventListener('DOMContentLoaded', function() {
        var fixedTabButton = document.getElementById('fixed-tab');
        var buttons = document.querySelectorAll('.sidebar button[data-file]');
        var chatContainer = document.getElementById('chat-container');
        var formContainer = document.getElementById('form');
        var hiddenInput = document.getElementById('fichier_json');
        var userInfoButton = document.getElementById('user-info-btn');
        var modal = document.getElementById('user-info-modal');
        var closeModal = document.getElementById('close-modal');
    
function loadMessages(file) {
    var timestamp = new Date().getTime();
    fetch(`society/<?php echo $society; ?>/<?php echo $user_id; ?>/${file}?_=${timestamp}`)
        .then(response => response.json())
        .then(data => {
            chatContainer.innerHTML = '';
            data.messages.forEach(function(message) {
                var p = document.createElement('p');
                var content = '';

                if (message.role === 'assistant') {
                    if (message.images && message.images.length > 0) {
                        var imagesHTML = message.images.map(image => `<img style="max-width: 200px" src="data:image/jpg;base64,${image}" style="max-width: 100%;">`).join('<br>');
                        content = `<div class="single_message"><img class="icon-conv" src="resource/icon/assistant.png"><div class="message_bubble">${message.content}<br>${imagesHTML}</div></div>`;
                    } else {
                        content = `<div class="single_message"><img src="resource/icon/assistant.png" class="icon-conv"><div class="message_bubble">${message.content}</div></div>`;
                    }
                } else {
                    if (message.images && message.images.length > 0) {
                        var imagesHTML = message.images.map(image => `<img style="max-width: 200px" src="data:image/jpg;base64,${image}" style="max-width: 100%;">`).join('<br>');
                        content = `<div class="single_message"><img class="icon-conv" src="https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=<?php echo htmlspecialchars($firstName ?? ''); ?>+<?php echo htmlspecialchars($lastName ?? ''); ?>"><div class="message_bubble">${message.content}<br>${imagesHTML}</div></div>`;
                    } else {
                        content = `<div class="single_message"><img class="icon-conv" src="https://ui-avatars.com/api/?background=0D8ABC&color=fff&name=<?php echo htmlspecialchars($firstName ?? ''); ?>+<?php echo htmlspecialchars($lastName ?? ''); ?>"><div class="message_bubble">${message.content}<br></div>`;
                    }
                }

                p.innerHTML = content;
                chatContainer.appendChild(p);
            });

                var formContent = `
                    <img id="output" style="max-width:300px"><br>
                    <?php if ($accept_type == 'image'): ?>
                    <button onclick="resetFileControl()">Réinitialiser</button>
                    <form id="form2" class="input-container" method="post" action="request_text_image.php" onsubmit="submitForm(event)">
                        <input id="fileControl" type="file" name="image" accept="image/*" required onchange="loadFile(event)">
                        <input type="text" id="question" name="question" required placeholder="Entree Texte">
                        <input type="hidden" name="current_history" value="${file}">
                        <input type="hidden" name="form" value="image">
                        <button type="submit" formmethod="post">Envoyer</button>
                    </form>
                    <?php else: ?>
                        <div id="result"></div>
                    <form id="form1" class="input-container" method="post" action="request_text_image.php" onsubmit="submitForm(event)">
                        <input type="text" id="question" name="question" required placeholder="Entree Texte">
                        <button type="submit" formmethod="post">Envoyer</button>
                        <input type="hidden" name="current_history" value="${file}">
                        <input type="hidden" name="form" value="text">
                    </form>
                    <?php endif; ?>
                `;

                formContainer.innerHTML = '';
                var p = document.createElement('p');
                p.innerHTML = formContent;
                formContainer.appendChild(p);
            })
            .catch(error => {
                console.error('Error fetching the conversation:', error);
            });
    }


        // Enregistrer l'onglet sélectionné dans le localStorage
        function saveSelectedTab(file) {
            localStorage.setItem('selectedTab', file);
        }

        // Récupérer l'onglet sélectionné depuis le localStorage
        function getSelectedTab() {
            return localStorage.getItem('selectedTab');
        }

        // Définir l'onglet sélectionné
        function setSelectedTab(file) {
            buttons.forEach(button => {
                if (button.getAttribute('data-file') === file) {
                    button.click();
                }
            });
        }

        fixedTabButton.addEventListener('click', function() {
            chatContainer.innerHTML = '<p>Un autre code html</p>';
            hiddenInput.value = '';

            var embedForm = `
                    <form id="form2" class="input-container" method="post" enctype="multipart/form-data">
                        <input type="text" id="question" name="question" required placeholder="Entree Recherches">
                        <input type="hidden" name="fichier_json" id="fichier_json">
                        <input type="hidden" name="current_history" value="embed">
                        <input type="hidden" name="form" value="image">
                        <button type="submit" formmethod="post">Envoyer</button>
                    </form>
                    `;

                formContainer.innerHTML = '';
                var p = document.createElement('p');
                p.innerHTML = embedForm;
                formContainer.appendChild(p);
        
        });

        // Afficher la fenêtre modale
        userInfoButton.addEventListener('click', function() {
            modal.style.display = 'block';
        });

        // Fermer la fenêtre modale
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Fermer la fenêtre modale en cliquant en dehors de celle-ci
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var file = this.getAttribute('data-file');
                loadMessages(file);
                saveSelectedTab(file);
            });
        });

        // Charger l'onglet sélectionné ou le premier onglet par défaut
        var selectedTab = getSelectedTab();
        if (selectedTab) {
            setSelectedTab(selectedTab);
        } else if (buttons.length > 0) {
            buttons[0].click();
        }



    setInterval(function() {
        loadTabs();
    }, 10000); // 10000 ms = 10 secondes

    });

    window.onbeforeunload = null;

    function envoyerFormulaire(event, formId, resultId) {
        event.preventDefault(); // Empêche le rechargement de la page

        let formData = new FormData(document.getElementById(formId));
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById(resultId).innerHTML = data.result;
            history.replaceState(null, '', window.location.href.split('?')[0]); // Supprime les paramètres de l'URL
        })
        .catch(error => console.error('Erreur:', error));
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('form1').addEventListener('submit', (event) => envoyerFormulaire(event, 'form1', 'resultat1'));
        document.getElementById('form2').addEventListener('submit', (event) => envoyerFormulaire(event, 'form2', 'resultat2'));
    });
</script>


</body>
</html>


<script>
        function envoyerFormulaire(event, formId, resultId) {
            event.preventDefault(); // Empêche le rechargement de la page

            let formData = new FormData(document.getElementById(formId));
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById(resultId).innerHTML = data.result;
                history.replaceState(null, '', window.location.href.split('?')[0]); // Supprime les paramètres de l'URL
            })
            .catch(error => console.error('Erreur:', error));
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('form1').addEventListener('submit', (event) => envoyerFormulaire(event, 'form1', 'resultat1'));
            document.getElementById('form2').addEventListener('submit', (event) => envoyerFormulaire(event, 'form2', 'resultat2'));
        });
</script>