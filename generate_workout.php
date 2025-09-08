<?php
// Turn off all error reporting to prevent any output before headers
error_reporting(0);

// Set headers first to prevent any previous output
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Initialize response array
$response = ["success" => false, "message" => "Unknown error"];

try {
    // Include database connection
    include 'db_connection.php';
    
    // Check if user_id is provided
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response["message"] = "User ID is required";
        echo json_encode($response);
        exit;
    }

    $user_id = $_POST['user_id'];

    // Check if user already has a workout plan
    $check_sql = "SELECT id FROM user_workout_plans WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $response["message"] = "User already has a workout plan";
        $response["has_plan"] = true;
        echo json_encode($response);
        exit;
    }
    
    $check_stmt->close();

    // Fetch user's goal from onboarding_data table
    $sql = "SELECT goal FROM onboarding_data WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $response["message"] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        $response["message"] = "Execution error: " . $stmt->error;
        echo json_encode($response);
        exit;
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response["message"] = "User onboarding data not found";
        echo json_encode($response);
        exit;
    }

    $onboarding_data = $result->fetch_assoc();
    $goal = strtolower(trim($onboarding_data['goal']));
    
    // Handle different variations of goal values
    $goal_mappings = [
        "weight_loss" => ["weight loss", "lose weight", "weightloss", "fat loss", "slim down"],
        "muscle_gain" => ["muscle gain", "gain muscle", "build muscle", "musclegrowth", "bulk up"]
    ];
    
    // Determine the correct goal category
    $detected_goal = "muscle_gain"; // Default
    
    foreach ($goal_mappings as $category => $variations) {
        foreach ($variations as $variation) {
            if (strpos($goal, $variation) !== false) {
                $detected_goal = $category;
                break 2;
            }
        }
    }
    
    // Use the detected goal
    $goal = $detected_goal;

    // Define workout parameters based on goal
    $workout_params = [
        "weight_loss" => [
            "sets" => 3,
            "reps" => 12,
            "rest_days" => 2,
            "workout_days" => 5
        ],
        "muscle_gain" => [
            "sets" => 4,
            "reps" => 8,
            "rest_days" => 1,
            "workout_days" => 6
        ]
    ];

    $sets = $workout_params[$goal]["sets"];
    $reps = $workout_params[$goal]["reps"];
    $rest_days = $workout_params[$goal]["rest_days"];
    $workout_days = $workout_params[$goal]["workout_days"];

    // Define dumbbell exercises by category
    $exercises = [
        "push" => [
            "Dumbbell Bench Press",
            "Dumbbell Shoulder Press",
            "Dumbbell Flyes",
            "Dumbbell Triceps Extension",
            "Dumbbell Pullover"
        ],
        "pull" => [
            "Dumbbell Rows",
            "Dumbbell Bicep Curls",
            "Dumbbell Hammer Curls",
            "Dumbbell Shrugs",
            "Dumbbell Reverse Flyes"
        ],
        "legs" => [
            "Dumbbell Squats",
            "Dumbbell Lunges",
            "Dumbbell Deadlifts",
            "Dumbbell Calf Raises",
            "Dumbbell Step-ups"
        ],
        "core" => [
            "Dumbbell Russian Twists",
            "Dumbbell Side Bends",
            "Dumbbell Wood Chops",
            "Dumbbell Sit-ups",
            "Dumbbell Windmills"
        ]
    ];

    // Generate 4-week (28-day) workout plan
    $weekly_plan = [];
    $current_day = 1;
    $workout_types = ["push", "pull", "legs", "core"];

    for ($week = 1; $week <= 4; $week++) {
        $week_key = "Week $week";
        $weekly_plan[$week_key] = [];
        
        for ($day = 1; $day <= 7; $day++) {
            $day_name = date('l', strtotime("Sunday +$day days"));
            
            // Add rest days based on goal
            if ($goal == "weight_loss" && ($day == 4 || $day == 7)) {
                $weekly_plan[$week_key][$day_name] = ["Rest Day"];
                continue;
            } elseif ($goal == "muscle_gain" && $day == 4) {
                $weekly_plan[$week_key][$day_name] = ["Rest Day"];
                continue;
            }
            
            // Select workout type in rotation
            $workout_type = $workout_types[($current_day - 1) % 4];
            
            // Select 4-5 exercises for the day
            $day_exercises = [];
            $available_exercises = $exercises[$workout_type];
            shuffle($available_exercises);
            
            $exercise_count = ($goal == "weight_loss") ? 4 : 5;
            for ($i = 0; $i < $exercise_count; $i++) {
                if (isset($available_exercises[$i])) {
                    $day_exercises[] = $available_exercises[$i];
                }
            }
            
            $weekly_plan[$week_key][$day_name] = $day_exercises;
            $current_day++;
        }
    }

    // Save the workout plan to database
    $plan_data = json_encode([
        "weekly_plan" => $weekly_plan,
        "generated_at" => date("Y-m-d H:i:s")
    ]);
    
    $insert_sql = "INSERT INTO user_workout_plans (user_id, goal, sets, reps, plan_data) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    
    if (!$insert_stmt) {
        $response["message"] = "Database error: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    $insert_stmt->bind_param("isiis", $user_id, $goal, $sets, $reps, $plan_data);
    
    if (!$insert_stmt->execute()) {
        $response["message"] = "Failed to save workout plan: " . $insert_stmt->error;
        echo json_encode($response);
        exit;
    }
    
    $insert_stmt->close();

    // Prepare success response
    $response = [
        "success" => true,
        "goal" => $goal,
        "sets" => $sets,
        "reps" => $reps,
        "weekly_plan" => $weekly_plan,
        "message" => "Workout plan generated and saved successfully"
    ];

} catch (Exception $e) {
    $response["message"] = "Exception: " . $e->getMessage();
}

// Ensure only JSON is output
echo json_encode($response);

// Close connections if they exist
if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
exit;
?>