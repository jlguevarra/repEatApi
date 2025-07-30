<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id']) || !isset($data['recipe_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing user_id or recipe_id"
    ]);
    exit();
}

$user_id = (int)$data['user_id'];
$recipe_id = (int)$data['recipe_id'];

try {
    $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?");
    $stmt->bind_param("ii", $user_id, $recipe_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Removed from favorites"
        ]);
    } else {
        throw new Exception("Database error");
    }
} catch (Exception $e) {
    error_log("Remove Favorite Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to remove favorite"
    ]);
}

$stmt->close();
?>