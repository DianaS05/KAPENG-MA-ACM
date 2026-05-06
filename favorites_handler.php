<?php
session_start();
header('Content-Type: application/json');
include 'db_conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user's favorites
    $sql = "SELECT c.* FROM favorites f 
            JOIN cafes c ON f.cafe_id = c.id 
            WHERE f.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'favorites' => $favorites]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or remove favorite
    $action = $_POST['action'] ?? '';
    $cafe_id = $_POST['cafe_id'] ?? 0;
    
    if ($action === 'add') {
        $sql = "INSERT IGNORE INTO favorites (user_id, cafe_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $cafe_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Added to favorites']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add']);
        }
        
    } elseif ($action === 'remove') {
        $sql = "DELETE FROM favorites WHERE user_id = ? AND cafe_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $cafe_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Removed from favorites']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to remove']);
        }
        
    } elseif ($action === 'clear') {
        // NEW: Clear all favorites for current user
        $sql = "DELETE FROM favorites WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'All favorites cleared']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to clear favorites']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
}

$conn->close();
?>