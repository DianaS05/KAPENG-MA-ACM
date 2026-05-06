<?php
session_start();
include 'db_conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Submit a review
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["status" => "error", "message" => "Please login to submit a review"]);
        exit;
    }
    
    $cafe_id = intval($_POST['cafe_id']);
    $rating = intval($_POST['rating']);
    $comment = $conn->real_escape_string($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Anonymous User';
    
    // Check if user already reviewed this cafe
    $check = $conn->prepare("SELECT id FROM reviews WHERE cafe_id = ? AND user_id = ?");
    $check->bind_param("ii", $cafe_id, $user_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing review
        $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE cafe_id = ? AND user_id = ?");
        $stmt->bind_param("isii", $rating, $comment, $cafe_id, $user_id);
    } else {
        // Insert new review
        $stmt = $conn->prepare("INSERT INTO reviews (cafe_id, user_id, user_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $cafe_id, $user_id, $user_name, $rating, $comment);
    }
    
    if ($stmt->execute()) {
        // Update average rating for cafe
        $update_rating = $conn->prepare("UPDATE cafes SET rating = (SELECT AVG(rating) FROM reviews WHERE cafe_id = ?) WHERE id = ?");
        $update_rating->bind_param("ii", $cafe_id, $cafe_id);
        $update_rating->execute();
        $update_rating->close();
        
        echo json_encode(["status" => "success", "message" => "Review submitted successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Could not submit review"]);
    }
    $stmt->close();
    $check->close();
    
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['cafe_id'])) {
    // Get reviews for a cafe
    $cafe_id = intval($_GET['cafe_id']);
    
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE cafe_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $cafe_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    echo json_encode(["status" => "success", "reviews" => $reviews]);
    $stmt->close();
}

$conn->close();
?>