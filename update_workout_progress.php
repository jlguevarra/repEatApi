<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
include 'db_connection.php';

if (!isset($_POST['user_id']) || !isset($_POST['current_week_index']) || !isset($_POST['current_day_index'])) {
    echo json_encode(["success" => false, "message" => "Required parameters are missing."]);
    exit;
}

$user_id = $_POST['user_id'];
$current_week_index = (int)$_POST['current_week_index'];
$current_day_index = (int)$_POST['current_day_index'];

try {
    // This query correctly targets the user_workout_plans table
    $stmt = $conn->prepare("
        UPDATE user_workout_plans 
        SET current_week_index = ?, current_day_index = ?
        WHERE user_id = ?
    ");
    
    $stmt->bind_param("iii", $current_week_index, $current_day_index, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Workout progress updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "No plan found for the user to update."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to execute update."]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "An exception occurred: " . $e->getMessage()]);
}

$conn->close();
?>