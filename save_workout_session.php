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
$calories_burned = $data['calories_burned'] ?? 0;
$plan_day = $data['plan_day'] ?? '';

// Get the full timestamp string from the app
$date_from_app = $data['date'] ?? date('Y-m-d H:i:s');

// **FIX STARTS HERE**
// 1. Create a DateTime object from the app's string.
$date_obj = new DateTime($date_from_app);
// 2. Format it into the 'YYYY-MM-DD' format that MySQL needs.
$formatted_date = $date_obj->format('Y-m-d');
// **FIX ENDS HERE**

if (empty($user_id) || empty($exercise)) {
    echo json_encode(["success" => false, "message" => "User ID and exercise are required"]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO workout_sessions (user_id, plan_day, exercise_name, completed_reps, target_reps, duration_seconds, calories_burned, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Use the new $formatted_date variable in the binding
    $stmt->bind_param("issiiiis", $user_id, $plan_day, $exercise, $completed_reps, $target_reps, $duration_seconds, $calories_burned, $formatted_date);
    
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Workout session saved successfully"]);
    } else {
        // Add error logging to see what the database says
        error_log("DB Execute Error: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to save workout session"]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Also log exceptions
    error_log("PHP Exception: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}

$conn->close();
?>