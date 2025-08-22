<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Accept user_id and goal
$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
$goal = strtolower($_POST['goal'] ?? $_GET['goal'] ?? '');

// Default to muscle gain if goal not specified but user_id is provided
if (!$goal && $user_id) {
    $goal = 'muscle gain';
}

if (!$user_id || !in_array($goal, ['muscle gain', 'weight loss'])) {
    echo json_encode(["success" => false, "message" => "Missing or invalid user_id or goal"]);
    exit;
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
    // For muscle gain: focus on major muscle groups with dumbbells
    $exercises = fetchExercisesByEquipment('dumbbell');
    
    // If no dumbbell exercises, try by major muscle groups
    if (!$exercises || count($exercises) < 35) { // Need 35 exercises for 7 days × 5 exercises
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
    // For weight loss: focus on compound movements and full body exercises
    $exercises = fetchExercisesByEquipment('body_only');
    
    // Also include some dumbbell exercises for variety
    $dumbbellExercises = fetchExercisesByEquipment('dumbbell');
    if ($dumbbellExercises) {
        $exercises = array_merge($exercises ?: [], $dumbbellExercises);
    }
}

// Fallback exercises if API fails
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

// Use fallback if API call failed or returned insufficient exercises
if (!$exercises || count($exercises) < 35) {
    $exercises = $fallbackExercises;
}

// Shuffle and select 35 exercises (7 days × 5 exercises)
shuffle($exercises);
$selectedExercises = array_slice($exercises, 0, 35);

// Set appropriate sets and reps based on goal
$sets = $goal === 'muscle gain' ? 4 : 3;
$reps = $goal === 'muscle gain' ? 8 : 15;

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$plan = [];
$dailyExercises = [];

// Group exercises by day (5 exercises per day)
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

// Prepare response
$response = [
    "success" => true,
    "plan" => $plan, // For backward compatibility
    "daily_exercises" => $dailyExercises, // New format with exercises per day
    "sets" => $sets,
    "reps" => $reps,
    "goal" => $goal
];

echo json_encode($response);