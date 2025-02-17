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


?>
<?php echo "$user_id $society"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Gestion des Onglets</title>
    <script>
        function sendRequest(action, society, userId, file, newName = '') {
            const params = new URLSearchParams({
                action: action,
                society: society,
                user_id: userId,
                file: file,
                new_name: newName
            });

            fetch('context_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                alert(JSON.stringify(data));
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteTab() {
            const society = document.getElementById('deleteSociety').value;
            const userId = document.getElementById('deleteUserId').value;
            const file = document.getElementById('deleteFile').value;
            sendRequest('delete', society, userId, file);
        }

        function renameTab() {
            const society = document.getElementById('renameSociety').value;
            const userId = document.getElementById('renameUserId').value;
            const file = document.getElementById('renameFile').value;
            const newName = document.getElementById('newName').value;
            sendRequest('rename', society, userId, file, newName);
        }
    </script>
</head>
<body>
    <h1>Tester la Gestion des Onglets</h1>

    <h2>Supprimer un Onglet</h2>
    <form onsubmit="deleteTab(); return false;">
        <label for="deleteSociety">Society:</label>
        <input type="text" id="deleteSociety" name="society" value="<?php echo "$society"; ?>"><br>
        <label for="deleteUserId">User ID:</label>
        <input type="text" id="deleteUserId" name="user_id" value="<?php echo "$user_id"; ?>"><br>
        <label for="deleteFile">File:</label>
        <input type="text" id="deleteFile" name="file" ><br>
        <button type="submit">Supprimer</button>
    </form>

    <h2>Renommer un Onglet</h2>
    <form onsubmit="renameTab(); return false;">
        <label for="renameSociety">Society:</label>
        <input type="text" id="renameSociety" name="society" value="<?php echo "$society"; ?>"><br>
        <label for="renameUserId">User ID:</label>
        <input type="text" id="renameUserId" name="user_id" value="<?php echo "$user_id"; ?>"><br>
        <label for="renameFile">File:</label>
        <input type="text" id="renameFile" name="file" ><br>
        <label for="newName">New Name:</label>
        <input type="text" id="newName" name="new_name" ><br>
        <button type="submit">Renommer</button>
    </form>
</body>
</html>
