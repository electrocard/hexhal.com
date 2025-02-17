<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Read database configuration from JSON file
$config = json_decode(file_get_contents('hexhal_info.json'), true);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input data
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $family_name = htmlspecialchars(trim($_POST['family_name']));
    $username = htmlspecialchars(strtolower(trim($_POST['username']))); // Remove spaces and convert to lowercase
    $password = $_POST['password'];
    $society = htmlspecialchars(trim($_POST['society']));
    $rank = htmlspecialchars(trim($_POST['rank']));
    $info = htmlspecialchars(trim($_POST['info']));

    // Validate fields (for example, non-empty username and password)
    if (empty($username) || empty($password) || empty($first_name) || empty($family_name)) {
        echo "Please fill all required fields.";
        exit;
    }

    // Generate user_id based on username and a random number
    $random_number = mt_rand(1, 1000000); // Generate random number between 1 and 1000000
    $user_id = $username . $random_number;

    // Hash the password securely
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Prepare and execute SQL query to insert user data
    $sql = "INSERT INTO users (user_id, username, first_name, family_name, password, society, rank, info) 
            VALUES (:user_id, :username, :first_name, :family_name, :password, :society, :rank, :info)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'user_id' => $user_id,
        'username' => $username,
        'first_name' => $first_name,
        'family_name' => $family_name,
        'password' => $password_hash,
        'society' => $society,
        'rank' => $rank,
        'info' => $info
    ]);

    // Check if registration was successful
    if ($stmt->rowCount() > 0) {
        echo "Registration successful!";
    } else {
        echo "Registration failed.";
    }
}
?>
