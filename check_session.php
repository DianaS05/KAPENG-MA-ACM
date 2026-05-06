<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'] ?? '',
            'avatar' => $_SESSION['user_avatar'] ?? ''
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No active session']);
}
?>