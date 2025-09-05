<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection
require_once 'db_connection.php';

// Accept user_id
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

// Check if user already has a saved plan in weekly_plans table
$check_sql = "SELECT plan, sets, reps, goal FROM weekly_plans WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // User has a saved plan, return it
    $row = $check_result->fetch_assoc();
    
    // Check if plan is valid JSON
    $plan_data = json_decode($row['plan'], true);
    if ($plan_data !== null) {
        echo json_encode([
            "success" => true,
            "plan" => $plan_data,
            "daily_exercises" => $plan_data,
            "sets" => $row['sets'],
            "reps" => $row['reps'],
            "goal" => $row['goal'],
            "user_id" => $user_id,
            "from_saved" => true
        ]);
        
        $check_stmt->close();
        $conn->close();
        exit;
    }
}

$check_stmt->close();

// If no saved plan found, generate a new one
// Function to get user's goal from database
function getUserGoalFromDatabase($user_id, $conn) {
    try {
        $stmt = $conn->prepare("SELECT goal FROM onboarding_data WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return strtolower(trim($row['goal']));
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    }
}

// Get user's goal from database
$goal = getUserGoalFromDatabase($user_id, $conn);

// Default to muscle gain if goal not found in database
if (!$goal) {
    $goal = 'muscle gain';
}

// Normalize goal names
if (strpos($goal, 'muscle') !== false || strpos($goal, 'gain') !== false || strpos($goal, 'build') !== false) {
    $goal = 'muscle gain';
} elseif (strpos($goal, 'weight') !== false || strpos($goal, 'loss') !== false || strpos($goal, 'lose') !== false || strpos($goal, 'fat') !== false) {
    $goal = 'weight loss';
} else {
    // Default fallback
    $goal = 'muscle gain';
}

// Function to fetch exercises by equipment
function fetchExercisesByEquipment($equipment) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://exercise-db-fitness-workout-gym.p.rapidapi.com/exercises/equipment/".urlencode($equipment),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: exercise-db-fitness-workout-gym.p.rapidapi.com",
            "x-rapidapi-key: a9c9d6b17dmshd3c160ab17328e9p11d188jsnfb0ca8e49f76"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err || $httpCode !== 200) {
        error_log("API Error: " . $err);
        return null;
    }

    return json_decode($response, true);
}

// Function to fetch exercises by muscle
function fetchExercisesByMuscle($muscle) {
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://exercise-db-fitness-workout-gym.p.rapidapi.com/exercises/target/".urlencode($muscle),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
            "x-rapidapi-host: exercise-db-fitness-workout-gym.p.rapidapi.com",
            "x-rapidapi-key: a9c9d6b17dmshd3c160ab17328e9p11d188jsnfb0ca8e49f76"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err || $httpCode !== 200) {
        error_log("API Error: " . $err);
        return null;
    }

    return json_decode($response, true);
}

// Get exercises based on goal
if ($goal === 'muscle gain') {
    $exercises = fetchExercisesByEquipment('dumbbell');
    
    if (!$exercises || count($exercises) < 35) {
        $majorMuscles = ['chest', 'back', 'shoulders', 'quadriceps', 'biceps', 'triceps', 'abdominals'];
        $allExercises = [];
        
        foreach ($majorMuscles as $muscle) {
            $muscleExercises = fetchExercisesByMuscle($muscle);
            if ($muscleExercises) {
                $allExercises = array_merge($allExercises, $muscleExercises);
            }
        }
        $exercises = $allExercises;
    }
} else {
    $exercises = fetchExercisesByEquipment('body_only');
    $dumbbellExercises = fetchExercisesByEquipment('dumbbell');
    if ($dumbbellExercises) {
        $exercises = array_merge($exercises ?: [], $dumbbellExercises);
    }
}

// Fallback exercises
$fallbackExercises = [
    ['name' => 'Push-ups', 'target' => 'Chest', 'equipment' => 'Body Only'],
    ['name' => 'Dumbbell Bench Press', 'target' => 'Chest', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Shoulder Press', 'target' => 'Shoulders', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Rows', 'target' => 'Back', 'equipment' => 'Dumbbell'],
    ['name' => 'Bodyweight Squats', 'target' => 'Quadriceps', 'equipment' => 'Body Only'],
    ['name' => 'Lunges', 'target' => 'Quadriceps', 'equipment' => 'Body Only'],
    ['name' => 'Plank', 'target' => 'Abs', 'equipment' => 'Body Only'],
    ['name' => 'Dumbbell Bicep Curls', 'target' => 'Biceps', 'equipment' => 'Dumbbell'],
    ['name' => 'Tricep Dips', 'target' => 'Triceps', 'equipment' => 'Body Only'],
    ['name' => 'Dumbbell Flyes', 'target' => 'Chest', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Pullover', 'target' => 'Back', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Lateral Raises', 'target' => 'Shoulders', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Front Raises', 'target' => 'Shoulders', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Shrugs', 'target' => 'Traps', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Tricep Extension', 'target' => 'Triceps', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Hammer Curls', 'target' => 'Biceps', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Concentration Curls', 'target' => 'Biceps', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Step Ups', 'target' => 'Quadriceps', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Calf Raises', 'target' => 'Calves', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Russian Twists', 'target' => 'Abs', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Side Bends', 'target' => 'Obliques', 'equipment' => 'Dumbbell'],
    ['name' => 'Burpees', 'target' => 'Full Body', 'equipment' => 'Body Only'],
    ['name' => 'Jumping Jacks', 'target' => 'Cardio', 'equipment' => 'Body Only'],
    ['name' => 'Mountain Climbers', 'target' => 'Core', 'equipment' => 'Body Only'],
    ['name' => 'High Knees', 'target' => 'Cardio', 'equipment' => 'Body Only'],
    ['name' => 'Jump Squats', 'target' => 'Legs', 'equipment' => 'Body Only'],
    ['name' => 'Walking Lunges', 'target' => 'Legs', 'equipment' => 'Body Only'],
    ['name' => 'Glute Bridges', 'target' => 'Glutes', 'equipment' => 'Body Only'],
    ['name' => 'Leg Raises', 'target' => 'Abs', 'equipment' => 'Body Only'],
    ['name' => 'Bicycle Crunches', 'target' => 'Abs', 'equipment' => 'Body Only'],
    ['name' => 'Push-up to Renegade Row', 'target' => 'Full Body', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Thruster', 'target' => 'Full Body', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Snatch', 'target' => 'Full Body', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Swing', 'target' => 'Full Body', 'equipment' => 'Dumbbell'],
    ['name' => 'Dumbbell Clean and Press', 'target' => 'Full Body', 'equipment' => 'Dumbbell']
];

if (!$exercises || count($exercises) < 35) {
    $exercises = $fallbackExercises;
}

// Shuffle and select 35 exercises
shuffle($exercises);
$selectedExercises = array_slice($exercises, 0, 35);

// Set reps and sets
$sets = $goal === 'muscle gain' ? 4 : 3;
$reps = $goal === 'muscle gain' ? 8 : 15;

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$plan = [];
$dailyExercises = [];

for ($i = 0; $i < 7; $i++) {
    $dayExercises = [];
    for ($j = 0; $j < 5; $j++) {
        $index = ($i * 5) + $j;
        if (isset($selectedExercises[$index]['name'])) {
            $equipmentInfo = isset($selectedExercises[$index]['equipment']) ? " ({$selectedExercises[$index]['equipment']})" : "";
            $dayExercises[] = $selectedExercises[$index]['name'] . $equipmentInfo;
        }
    }
    $dailyExercises[$days[$i]] = $dayExercises;
    $plan[$days[$i]] = implode(", ", $dayExercises);
}

// Save the generated plan to database directly
$save_sql = "INSERT INTO weekly_plans (user_id, week_number, plan, sets, reps, goal) VALUES (?, ?, ?, ?, ?, ?)";
$save_stmt = $conn->prepare($save_sql);
$week_number = 1; // Set appropriate week number
$save_stmt->bind_param("iisiss", $user_id, $week_number, json_encode($dailyExercises), $sets, $reps, $goal);

if ($save_stmt->execute()) {
    error_log("✅ Plan saved successfully for user: $user_id");
} else {
    error_log("❌ Error saving plan: " . $save_stmt->error);
}

$save_stmt->close();

// Response
$response = [
    "success" => true,
    "plan" => $plan,
    "daily_exercises" => $dailyExercises,
    "sets" => $sets,
    "reps" => $reps,
    "goal" => $goal,
    "user_id" => $user_id,
    "from_saved" => false
];

echo json_encode($response);
$conn->close();
?>