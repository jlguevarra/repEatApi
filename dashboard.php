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
$sql = "SELECT COUNT(*) as total FROM workout_sessions WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$workoutsCompleted = $row ? intval($row['total']) : 0;

// 2️⃣ Calories Burned
$sql = "SELECT SUM(completed_reps) as total_reps FROM workout_sessions WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$totalReps = $row && $row['total_reps'] ? intval($row['total_reps']) : 0;
$caloriesBurned = $totalReps * 5;

// 3️⃣ Streak Days
$sql = "SELECT DISTINCT date FROM workout_sessions WHERE user_id = $user_id ORDER BY date DESC";
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
$sql = "SELECT current_weight FROM onboarding_data WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$weight = ($row && isset($row['current_weight'])) ? floatval($row['current_weight']) : 0;

// 5️⃣ Workouts This Week
$weekStart = date('Y-m-d', strtotime('monday this week'));
$sql = "SELECT COUNT(*) as total FROM workout_sessions WHERE user_id = $user_id AND date >= '$weekStart'";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$workoutsThisWeek = $row ? intval($row['total']) : 0;

// 6️⃣ Weekly Goal and Activity
$sql = "SELECT plan_data, current_week_index, current_day_index, completed_today 
        FROM user_workout_plans 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC 
        LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;

$weeklyGoal = 5; // Default value
$weeklyActivity = [];
$upcomingWorkouts = [];

if ($row) {
    $planJson = $row['plan_data'];
    $planData = json_decode($planJson, true);

    $currentWeekIndex = intval($row['current_week_index']);
    $currentDayIndex = intval($row['current_day_index']);
    $completedToday = intval($row['completed_today']) === 1;

    $weekKey = "Week " . ($currentWeekIndex + 1);
    $dayKey = "Day " . ($currentDayIndex + 1);

    // Calculate weekly goal based on workout days (non-rest days)
    if (isset($planData['weekly_plan'][$weekKey])) {
        $weeklyGoal = 0;
        foreach ($planData['weekly_plan'][$weekKey] as $dayLabel => $exercises) {
            $isRestDay = is_array($exercises) && count($exercises) === 1 && $exercises[0] === "Rest Day";
            if (!$isRestDay) {
                $weeklyGoal++; // Count non-rest days as workout days
            }
            
            $weeklyActivity[] = [
                "day" => $dayLabel,
                "isRestDay" => $isRestDay,
                "exercises" => $isRestDay ? [] : $exercises
            ];
        }
    }

    // Show today's workout in upcomingWorkouts
    if (isset($planData['weekly_plan'][$weekKey][$dayKey])) {
        $todayExercises = $planData['weekly_plan'][$weekKey][$dayKey];
        $isRestDay = is_array($todayExercises) && count($todayExercises) === 1 && $todayExercises[0] === "Rest Day";
        
        if (!$isRestDay) {
            $upcomingWorkouts[] = [
                "title" => $todayExercises,
                "time" => "today"
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