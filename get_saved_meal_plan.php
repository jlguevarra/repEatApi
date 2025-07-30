<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// No space or HTML before this

require_once 'db_connection.php';

if (!isset($_GET['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "User ID is required."
    ]);
    exit();
}

$user_id = (int)$_GET['user_id'];

try {
    $stmt = $conn->prepare("SELECT time_frame, start_date, meal_plan FROM meal_plans WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            "success" => true,
            "data" => [
                "time_frame" => $row['time_frame'],
                "start_date" => $row['start_date'],
                "meal_plan" => $row['meal_plan']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "data" => null
        ]);
    }
} catch (Exception $e) {
    error_log("Get Saved Meal Plan Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error."
    ]);
}

$stmt->close();
?>