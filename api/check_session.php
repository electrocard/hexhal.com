<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && isset($_SESSION['auth_token'])) {
    echo json_encode(['valid' => true]);
} else {
    echo json_encode(['valid' => false]);
}
?>
