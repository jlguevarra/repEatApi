<?php
error_reporting(0);


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$response = ["success" => false, "message" => "Unknown error"];

try {
    include 'db_connection.php';

    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response["message"] = "User ID is required";
        echo json_encode($response);
        exit;
    }

    $user_id = $_POST['user_id'];
    $current_week_index = isset($_POST['current_week_index']) ? (int)$_POST['current_week_index'] : 0;
    $current_day_index = isset($_POST['current_day_index']) ? (int)$_POST['current_day_index'] : 0;
    $completed_today = isset($_POST['completed_today']) ? (bool)$_POST['completed_today'] : false;

    $sql = "UPDATE user_workout_plans 
            SET current_week_index = ?, current_day_index = ?, completed_today = ?, last_updated = CURRENT_TIMESTAMP
            WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $response["message"] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param("iiii", $current_week_index, $current_day_index, $completed_today, $user_id);

    if ($stmt->execute()) {
        $response["success"] = true;
        $response["message"] = "Workout progress updated successfully";
    } else {
        $response["message"] = "Failed to update workout progress: " . $stmt->error;
    }

    $stmt->close();

} catch (Exception $e) {
    $response["message"] = "Exception: " . $e->getMessage();
}

echo json_encode($response);

if (isset($conn)) {
    $conn->close();
}
exit;
?>