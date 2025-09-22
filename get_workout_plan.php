<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
include 'db_connection.php';

if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(["success" => false, "message" => "User ID is required"]);
    exit;
}

$user_id = $_POST['user_id'];

try {
    // This is the only query you need. It gets the plan and the correct, saved progress.
    $sql = "SELECT goal, sets, reps, plan_data, current_week_index, current_day_index 
            FROM user_workout_plans 
            WHERE user_id = ? 
            ORDER BY id DESC 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Workout plan not found"]);
        exit;
    }

    $plan_data = $result->fetch_assoc();

    // Directly use the data fetched from the database
    $response = [
        "success" => true,
        "goal" => $plan_data['goal'],
        "sets" => (int)$plan_data['sets'],
        "reps" => (int)$plan_data['reps'],
        "weekly_plan" => json_decode($plan_data['plan_data'], true)['weekly_plan'], // Make sure to decode the plan_data
        "currentWeekIndex" => (int)$plan_data['current_week_index'],
        "currentDayIndex" => (int)$plan_data['current_day_index'],
        "message" => "Workout plan retrieved successfully"
    ];

    $stmt->close();

} catch (Exception $e) {
    $response = ["success" => false, "message" => "Exception: " . $e->getMessage()];
}

echo json_encode($response);
$conn->close();
?>