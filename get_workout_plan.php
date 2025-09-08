<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection
include 'db_connection.php';

// Initialize response array
$response = ["success" => false, "message" => "Unknown error"];

try {
    // Check if user_id is provided
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response["message"] = "User ID is required";
        echo json_encode($response);
        exit;
    }

    $user_id = $_POST['user_id'];

    // Get the user's workout plan
    $sql = "SELECT goal, sets, reps, plan_data FROM user_workout_plans WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response["message"] = "Workout plan not found";
        echo json_encode($response);
        exit;
    }
    
    $plan_data = $result->fetch_assoc();
    $weekly_plan = json_decode($plan_data['plan_data'], true);
    
    $response = [
        "success" => true,
        "goal" => $plan_data['goal'],
        "sets" => $plan_data['sets'],
        "reps" => $plan_data['reps'],
        "weekly_plan" => $weekly_plan['weekly_plan'],
        "message" => "Workout plan retrieved successfully"
    ];
    
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