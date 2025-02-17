// Injecter les variables PHP dans le JavaScript
var society = "<?php echo $society; ?>";
var user_id = "<?php echo $user_id; ?>";
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
    // Utiliser society, user_id et currentButtonValue pour récupérer les valeurs actuelles
    var filename = currentButtonValue;
    deleteTab(society, user_id, filename);
}

// Fonction pour renommer l'élément (appel AJAX vers PHP)
function renameItem() {
    // Utiliser society, user_id et currentButtonValue pour récupérer les valeurs actuelles
    var filename = currentButtonValue;
    var newName = prompt("Entrez le nouveau nom :");
    if (newName) {
        renameTab(society, user_id, filename, newName);
    }
}

// Exemple de fonction pour charger les onglets (à ajuster selon tes besoins)
function loadTabs() {
    console.log('Tabs loaded'); // Placeholder function
}

// Exécuter le script de gestion et charger les onglets après 1 seconde
async function manageTabsAndLoad() {
    await fetch('../script_php/context_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'add', // Ajoute une nouvelle tab si nécessaire
            society: society,
            user_id: user_id
        })
    });
    setTimeout(() => {
        loadTabs();
    }, 1000);
}

// Fonction pour supprimer un onglet via AJAX
function deleteTab(society, userId, file) {
    fetch('script_php/context_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'delete',
            society: society,
            user_id: userId,
            file: file
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log(data.message);
            // Actualiser les onglets
            loadTabs();
        } else {
            console.error(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Fonction pour renommer un onglet via AJAX
function renameTab(society, userId, file, newName) {
    fetch('manage_tabs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
            action: 'rename',
            society: society,
            user_id: userId,
            file: file,
            new_name: newName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log(data.message);
            // Actualiser les onglets
            loadTabs();
        } else {
            console.error(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}
