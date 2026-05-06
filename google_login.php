<?php
// google_login.php
session_start();
include 'db_conn.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get raw input
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

if (!$data || !isset($data->token)) {
    echo json_encode(["status" => "error", "message" => "No token provided"]);
    exit;
}

$token = $data->token;

// VERIFY TOKEN USING GOOGLE API
$google_api_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $token;
$options = [
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => 'Accept: application/json'
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents($google_api_url, false, $context);

if ($response === false) {
    echo json_encode(["status" => "error", "message" => "Failed to verify token with Google"]);
    exit;
}

$user = json_decode($response);

if (isset($user->error)) {
    echo json_encode(["status" => "error", "message" => $user->error_description ?? "Invalid token"]);
    exit;
}

if (isset($user->email)) {
    $email = $conn->real_escape_string($user->email);
    $name = $conn->real_escape_string($user->name);
    $google_id = $conn->real_escape_string($user->sub);
    $avatar = $conn->real_escape_string($user->picture);

    // Check if user exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    
    if (!$check) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    if ($check->num_rows == 0) {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, google_id, avatar) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("ssss", $name, $email, $google_id, $avatar);
        
        if (!$stmt->execute()) {
            echo json_encode(["status" => "error", "message" => "Insert failed: " . $stmt->error]);
            exit;
        }
        $user_id = $conn->insert_id;
        $stmt->close();
    } else {
        $row = $check->fetch_assoc();
        $user_id = $row['id'];
        $name = $row['username'];
        $avatar = $row['avatar'] ?? $avatar;
    }
    
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_avatar'] = $avatar;

    echo json_encode([
        "status" => "success",
        "user" => [
            "id" => $user_id,
            "name" => $name,
            "email" => $email,
            "avatar" => $avatar
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid token from Google"]);
}
?>