<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'db_connection.php';

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : (isset($_GET['user_id']) ? intval($_GET['user_id']) : 0);
if ($user_id <= 0) {
    echo json_encode(["error" => "Invalid user_id"]);
    exit;
}

// 1️⃣ Workouts Completed (all time)
$sql = "SELECT COUNT(*) as total FROM camera_workouts WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$workoutsCompleted = $row ? intval($row['total']) : 0;

// 2️⃣ Calories Burned
$sql = "SELECT SUM(detected_reps) as total_reps FROM camera_workouts WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$totalReps = $row && $row['total_reps'] ? intval($row['total_reps']) : 0;
$caloriesBurned = $totalReps * 5;

// 3️⃣ Streak Days
$sql = "SELECT DISTINCT date FROM camera_workouts WHERE user_id = $user_id ORDER BY date DESC";
$result = $conn->query($sql);
$dates = [];
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $dates[] = $r['date'];
    }
}
$streakDays = 0;
$today = new DateTime();
foreach ($dates as $d) {
    $dateObj = new DateTime($d);
    $diff = $today->diff($dateObj)->days;
    if ($diff == $streakDays) {
        $streakDays++;
    } else {
        break;
    }
}

// 4️⃣ Weight from onboarding_data
$sql = "SELECT current_weight 
        FROM onboarding_data 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC 
        LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$weight = ($row && isset($row['current_weight'])) ? floatval($row['current_weight']) : 0;

// 5️⃣ Workouts This Week
$weekStart = date('Y-m-d', strtotime('monday this week'));
$sql = "SELECT COUNT(*) as total 
        FROM camera_workouts 
        WHERE user_id = $user_id 
        AND date >= '$weekStart'";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$workoutsThisWeek = $row ? intval($row['total']) : 0;

// 6️⃣ Weekly Goal from user_workout_plans
$sql = "SELECT plan_data 
        FROM user_workout_plans 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC 
        LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$weeklyGoal = 5; // default
$weeklyActivity = [];
$upcomingWorkouts = [];

if ($row) {
    $planJson = $row['plan_data'];
    $planData = json_decode($planJson, true);

    $daysOfWeek = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];

    // Build Weekly Activity from plan
    foreach ($daysOfWeek as $day) {
        $exercises = [];
        $isRestDay = true;

        if (!empty($planData['weekly_plan']['Week 1'][$day])) {
            $exercises = $planData['weekly_plan']['Week 1'][$day];
            $isRestDay = false;
        }

        $weeklyActivity[] = [
            "day" => substr($day, 0, 3),
            "isRestDay" => $isRestDay,
            "exercises" => $exercises
        ];
    }

    // Build Upcoming Workouts (today + tomorrow only)
    $todayIndex = date('N'); // 1 (Mon) to 7 (Sun)
    for ($i = 0; $i < 2; $i++) {
        $dayIndex = ($todayIndex - 1 + $i) % 7;
        $dayName = $daysOfWeek[$dayIndex];

        if (!empty($planData['weekly_plan']['Week 1'][$dayName])) {
            $upcomingWorkouts[] = [
                "title" => $planData['weekly_plan']['Week 1'][$dayName],
                "time" => date('c', strtotime("+$i day"))
            ];
        }
    }
}

// ✅ Final JSON
echo json_encode([
    "workoutsCompleted" => $workoutsCompleted,
    "caloriesBurned" => $caloriesBurned,
    "streakDays" => $streakDays,
    "weight" => $weight,
    "workoutsThisWeek" => $workoutsThisWeek,
    "weeklyGoal" => $weeklyGoal,
    "weeklyActivity" => $weeklyActivity,
    "upcomingWorkouts" => $upcomingWorkouts
]);

$conn->close();
?>