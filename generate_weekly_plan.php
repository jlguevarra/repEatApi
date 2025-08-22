<?php
header("Content-Type: application/json");
require_once "db_connection.php";

$response = ["success" => false, "message" => ""];

// Make sure user_id is provided
if (!isset($_GET['user_id'])) {
    $response["message"] = "Missing user_id";
    echo json_encode($response);
    exit;
}

$user_id = intval($_GET['user_id']);

// Check if plan already exists for this week
$startOfWeek = date("Y-m-d", strtotime("monday this week"));
$endOfWeek   = date("Y-m-d", strtotime("sunday this week"));

$check = $conn->prepare("SELECT * FROM weekly_plan WHERE user_id=? AND week_start=? AND week_end=?");
$check->bind_param("iss", $user_id, $startOfWeek, $endOfWeek);
$check->execute();
$result = $check->get_result();

if ($result && $result->num_rows > 0) {
    // Already exists → return it
    $plan = [];
    while ($row = $result->fetch_assoc()) {
        $plan[] = $row;
    }
    $response["success"] = true;
    $response["plan"] = $plan;
    echo json_encode($response);
    exit;
}

// Otherwise → generate a new plan
// Get user onboarding data
$onboarding = $conn->prepare("SELECT goal, body_type FROM onboarding_data WHERE user_id=? ORDER BY created_at DESC LIMIT 1");
$onboarding->bind_param("i", $user_id);
$onboarding->execute();
$onboardingResult = $onboarding->get_result();

if (!$onboardingResult || $onboardingResult->num_rows == 0) {
    $response["message"] = "No onboarding data found for user.";
    echo json_encode($response);
    exit;
}

$userData = $onboardingResult->fetch_assoc();
$goal = strtolower($userData["goal"]);
$body_type = strtolower($userData["body_type"]);

// Example exercises database (this could later come from a real `exercises` table)
$exercisePool = [
    "weight_loss" => ["Jumping Jacks", "Burpees", "Mountain Climbers", "Push-ups", "Plank"],
    "muscle_gain" => ["Bench Press", "Squats", "Deadlifts", "Pull-ups", "Shoulder Press"],
    "endurance"   => ["Running", "Cycling", "Rowing", "Jump Rope", "Lunges"]
];

// Pick exercises depending on goal
$selectedExercises = isset($exercisePool[$goal]) ? $exercisePool[$goal] : $exercisePool["endurance"];

// Auto-assign reps & sets (example logic, you can adjust)
$defaultSets = ($goal == "muscle_gain") ? 4 : 3;
$defaultReps = ($goal == "weight_loss") ? 15 : 10;

// Generate 7-day plan
$plan = [];
$days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];

$insert = $conn->prepare("INSERT INTO weekly_plan (user_id, week_start, week_end, day, exercise, sets, reps) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($days as $day) {
    $exercise = $selectedExercises[array_rand($selectedExercises)];
    $insert->bind_param("issssii", $user_id, $startOfWeek, $endOfWeek, $day, $exercise, $defaultSets, $defaultReps);
    $insert->execute();

    $plan[] = [
        "day" => $day,
        "exercise" => $exercise,
        "sets" => $defaultSets,
        "reps" => $defaultReps
    ];
}

$response["success"] = true;
$response["message"] = "New weekly plan generated.";
$response["plan"] = $plan;

echo json_encode($response);
?>
