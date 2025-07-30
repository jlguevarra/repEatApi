<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once 'db_connection.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(["success" => false, "message" => "User ID required"]);
    exit();
}

$user_id = (int)$_GET['user_id'];

try {
    $stmt = $conn->prepare("SELECT recipe_id, recipe_title, recipe_image FROM favorites WHERE user_id = ? ORDER BY saved_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $favorites = [];
    while ($row = $result->fetch_assoc()) {
        $favorites[] = $row;
    }

    echo json_encode([
        "success" => true,
        "data" => $favorites
    ]);

} catch (Exception $e) {
    error_log("Get Favorites Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to load favorites"
    ]);
}

$stmt->close();
?>