<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['recipe_id']) || !isset($data['recipe_title'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required data"
    ]);
    exit();
}

$user_id = (int)$data['user_id'];
$recipe_id = (int)$data['recipe_id'];
$recipe_title = $conn->real_escape_string($data['recipe_title']);
$recipe_image = isset($data['recipe_image']) ? $conn->real_escape_string($data['recipe_image']) : null;

try {
    $stmt = $conn->prepare("INSERT INTO favorites (user_id, recipe_id, recipe_title, recipe_image) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE recipe_image = VALUES(recipe_image)");
    $stmt->bind_param("iiss", $user_id, $recipe_id, $recipe_title, $recipe_image);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Recipe added to favorites"
        ]);
    } else {
        throw new Exception("Database error");
    }
} catch (Exception $e) {
    error_log("Add Favorite Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to save favorite"
    ]);
}

$stmt->close();
?>