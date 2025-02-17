<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutons avec menus contextuels</title>
    <style>
        .context-menu {
            position: absolute;
            background-color: #fff;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        .context-menu button {
            display: block;
            width: 100%;
            padding: 8px 16px;
            text-align: left;
            border: none;
            background: none;
            cursor: pointer;
        }
        .context-menu button:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>

<!-- Boutons avec la même classe et attributs value -->
<button class="tab_button" value="history_1.json" oncontextmenu="showContextMenu(event)">Bouton 1</button>
<button class="tab_button" value="history_2.json" oncontextmenu="showContextMenu(event)">Bouton 2</button>
<button class="tab_button" value="history_3.json" oncontextmenu="showContextMenu(event)">Bouton 3</button>

<!-- Menu contextuel (initialement caché) -->
<div id="contextMenu" class="context-menu">
    <div id="contextInfo" style="padding: 8px;"></div>
    <button onclick="deleteItem()">Supprimer</button>
    <button onclick="renameItem()">Renommer</button>
</div>

<!-- Script JavaScript -->
<script>
    var currentButtonValue;

    // Fonction pour afficher le menu contextuel au clic droit
    function showContextMenu(event) {
        event.preventDefault(); // Empêche le menu contextuel par défaut du navigateur
        var contextMenu = document.getElementById('contextMenu');
        var contextInfo = document.getElementById('contextInfo');
        var button = event.target; // Le bouton sur lequel on a cliqué

        // Enregistre la valeur du bouton actuellement cliqué
        currentButtonValue = button.value;

        // Affiche la valeur du bouton dans la fenêtre contextuelle
        contextInfo.innerHTML = 'Nom du fichier : ' + currentButtonValue;

        contextMenu.style.display = 'block';
        contextMenu.style.left = event.pageX + 'px';
        contextMenu.style.top = event.pageY + 'px';

        // Cacher le menu contextuel lorsqu'on clique à l'extérieur
        document.addEventListener('click', function hideContextMenu() {
            contextMenu.style.display = 'none';
            document.removeEventListener('click', hideContextMenu);
        });
    }

    // Fonction pour supprimer l'élément (appel AJAX vers PHP)
    function deleteItem() {
        var filename = currentButtonValue;
        // Appel AJAX vers PHP pour supprimer l'élément avec le nom du fichier
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'delete.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    console.log('Élément supprimé avec succès:', filename);
                    // Tu peux ajouter ici du code pour mettre à jour l'interface utilisateur si nécessaire
                } else {
                    console.error('Erreur lors de la suppression de l\'élément:', xhr.status);
                }
            }
        };
        xhr.send('filename=' + encodeURIComponent(filename)); // Envoyer le nom du fichier ou d'autres données nécessaires
    }

    // Fonction pour renommer l'élément (appel AJAX vers PHP)
    function renameItem() {
        var filename = currentButtonValue;
        // Appel AJAX vers PHP pour renommer l'élément avec le nom du fichier
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'rename.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    console.log('Élément renommé avec succès:', filename);
                    // Tu peux ajouter ici du code pour mettre à jour l'interface utilisateur si nécessaire
                } else {
                    console.error('Erreur lors du renommage de l\'élément:', xhr.status);
                }
            }
        };
        xhr.send('filename=' + encodeURIComponent(filename)); // Envoyer le nom du fichier ou d'autres données nécessaires
    }
</script>

</body>
</html>
