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
$date = $data['date'] ?? date('Y-m-d');
$plan_day = $data['plan_day'] ?? ''; // NEW: Get plan_day from request

if (empty($user_id) || empty($exercise)) {
    echo json_encode(["success" => false, "message" => "User ID and exercise are required"]);
    exit;
}

try {
    // Updated SQL to include plan_day
    $stmt = $conn->prepare("INSERT INTO workout_sessions (user_id, plan_day, exercise_name, completed_reps, target_reps, duration_seconds, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issiiis", $user_id, $plan_day, $exercise, $completed_reps, $target_reps, $duration_seconds, $date);
    
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