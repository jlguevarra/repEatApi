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

    $goal_mappings = [
        "weight_loss" => ["weight loss", "lose weight", "weightloss", "fat loss", "slim down"],
        "muscle_gain" => ["muscle gain", "gain muscle", "build muscle", "musclegrowth", "bulk up"]
    ];

    $detected_goal = "muscle_gain";

    foreach ($goal_mappings as $category => $variations) {
        foreach ($variations as $variation) {
            if (strpos($goal, $variation) !== false) {
                $detected_goal = $category;
                break 2;
            }
        }
    }

    $goal = $detected_goal;

    $workout_params = [
        "weight_loss" => [
            "sets" => 3,
            "reps" => 12,
            "rest_days_count" => 2
        ],
        "muscle_gain" => [
            "sets" => 4,
            "reps" => 8,
            "rest_days_count" => 1
        ]
    ];

    $sets = $workout_params[$goal]["sets"];
    $reps = $workout_params[$goal]["reps"];
    $rest_days_count = $workout_params[$goal]["rest_days_count"];

    $exercises = [
        "push" => [
            "Dumbbell Bench Press", "Dumbbell Shoulder Press", "Dumbbell Flyes",
            "Dumbbell Triceps Extension", "Dumbbell Pullover"
        ],
        "pull" => [
            "Dumbbell Rows", "Dumbbell Bicep Curls", "Dumbbell Hammer Curls",
            "Dumbbell Shrugs", "Dumbbell Reverse Flyes"
        ],
        "legs" => [
            "Dumbbell Squats", "Dumbbell Lunges", "Dumbbell Deadlifts",
            "Dumbbell Calf Raises", "Dumbbell Step-ups"
        ],
        "core" => [
            "Dumbbell Russian Twists", "Dumbbell Side Bends", "Dumbbell Wood Chops",
            "Dumbbell Sit-ups", "Dumbbell Windmills"
        ]
    ];

    $weekly_plan = [];
    $workout_day_counter = 1;
    $workout_types = ["push", "pull", "legs", "core"];

    mt_srand(crc32($user_id)); // Seed for consistent generation

    for ($week = 1; $week <= 4; $week++) {
        $week_key = "Week $week";
        $weekly_plan[$week_key] = [];

        $available_days = range(2, 7); // Day 1 is always active

        do {
            $rest_days = [];

            if ($rest_days_count == 1) {
                $rest_days[] = $available_days[array_rand($available_days)];
            } else {
                $random_indices = array_rand($available_days, $rest_days_count);
                foreach ((array) $random_indices as $index) {
                    $rest_days[] = $available_days[$index];
                }
            }

            sort($rest_days);

            $has_consecutive = false;
            for ($i = 0; $i < count($rest_days) - 1; $i++) {
                if ($rest_days[$i + 1] - $rest_days[$i] === 1) {
                    $has_consecutive = true;
                    break;
                }
            }
        } while ($has_consecutive && $rest_days_count > 1);

        for ($day = 1; $day <= 7; $day++) {
            $day_label = "Day $day";

            if (in_array($day, $rest_days)) {
                $weekly_plan[$week_key][$day_label] = ["Rest Day"];
                continue;
            }

            $workout_type = $workout_types[($workout_day_counter - 1) % 4];
            $available_exercises = $exercises[$workout_type];
            shuffle($available_exercises);

            $exercise_count = ($goal == "weight_loss") ? 4 : 5;
            $day_exercises = array_slice($available_exercises, 0, $exercise_count);

            $weekly_plan[$week_key][$day_label] = $day_exercises;
            $workout_day_counter++;
        }
    }

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

echo json_encode($response);

if (isset($stmt)) {
    $stmt->close();
}
if (isset($conn)) {
    $conn->close();
}
exit;
?>