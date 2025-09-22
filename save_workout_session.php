<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? '';
$exercise = $data['exercise'] ?? '';
$completed_reps = $data['completed_reps'] ?? 0;
$target_reps = $data['target_reps'] ?? 0;
$duration_seconds = $data['duration_seconds'] ?? 0;
$calories_burned = $data['calories_burned'] ?? 0; // NEW: Get calories burned
$date = $data['date'] ?? date('Y-m-d');
$plan_day = $data['plan_day'] ?? '';

if (empty($user_id) || empty($exercise)) {
    echo json_encode(["success" => false, "message" => "User ID and exercise are required"]);
    exit;
}

try {
    // UPDATED: SQL to include calories_burned
    $stmt = $conn->prepare("INSERT INTO workout_sessions (user_id, plan_day, exercise_name, completed_reps, target_reps, duration_seconds, calories_burned, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // UPDATED: bind_param to include the new integer for calories
    $stmt->bind_param("issiiiis", $user_id, $plan_day, $exercise, $completed_reps, $target_reps, $duration_seconds, $calories_burned, $date);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Workout session saved successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save workout session"]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
?>