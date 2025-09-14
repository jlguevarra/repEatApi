<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include 'db_connection.php';

$response = ["success" => false, "message" => "Unknown error"];

try {
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response["message"] = "User ID is required";
        echo json_encode($response);
        exit;
    }

    $user_id = $_POST['user_id'];

    // First, check if the table has the new columns
    $check_columns_sql = "SHOW COLUMNS FROM user_workout_plans LIKE 'current_week_index'";
    $check_columns_result = $conn->query($check_columns_sql);
    $has_progress_columns = $check_columns_result->num_rows > 0;

    if ($has_progress_columns) {
        // Use the new columns if they exist
        $sql = "SELECT goal, sets, reps, plan_data, current_week_index, current_day_index, completed_today 
                FROM user_workout_plans WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    } else {
        // Fall back to the old columns if new ones don't exist
        $sql = "SELECT goal, sets, reps, plan_data FROM user_workout_plans WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    }
    
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
    $plan_json = json_decode($plan_data['plan_data'], true);

    // Initialize variables
    $currentWeekIndex = 0;
    $currentDayIndex = 0;
    $completedToday = false;

    if ($has_progress_columns && isset($plan_data['current_week_index']) && isset($plan_data['current_day_index'])) {
        // Use stored progress if available
        $currentWeekIndex = (int)$plan_data['current_week_index'];
        $currentDayIndex = (int)$plan_data['current_day_index'];
        $completedToday = (bool)$plan_data['completed_today'];
    } else {
        // Calculate current week/day index based on generated_at (fallback)
        $generated_at = isset($plan_json['generated_at']) ? $plan_json['generated_at'] : null;
        
        if ($generated_at) {
            $startDate = new DateTime($generated_at);
            $today = new DateTime();
            $diffDays = $startDate->diff($today)->days;

            $currentWeekIndex = floor($diffDays / 7); // 0-based
            $currentDayIndex = $diffDays % 7;         // 0-based
        }

        // Check if user has completed today's workout
        $todayDate = date('Y-m-d');
        $check_sql = "SELECT COUNT(*) as total FROM camera_workouts WHERE user_id = ? AND date = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $user_id, $todayDate);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result ? $check_result->fetch_assoc() : null;
        $completedToday = $check_row && $check_row['total'] > 0;
        $check_stmt->close();
    }

    $response = [
        "success" => true,
        "goal" => $plan_data['goal'],
        "sets" => $plan_data['sets'],
        "reps" => $plan_data['reps'],
        "weekly_plan" => $plan_json['weekly_plan'],
        "completedToday" => $completedToday,
        "currentWeekIndex" => $currentWeekIndex,
        "currentDayIndex" => $currentDayIndex,
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