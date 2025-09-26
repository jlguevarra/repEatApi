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

// 2️⃣ Calories Burned (all time)
$sql = "SELECT SUM(calories_burned) as total_calories FROM workout_sessions WHERE user_id = $user_id";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$caloriesBurned = $row && $row['total_calories'] ? intval($row['total_calories']) : 0;

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
if (!empty($dates)) {
    $today = new DateTime();
    $today->setTime(0, 0, 0); 

    $lastWorkoutDate = new DateTime($dates[0]);
    $lastWorkoutDate->setTime(0, 0, 0);
    $diff = $today->diff($lastWorkoutDate)->days;

    if ($diff <= 1) { 
        $streakDays = 1;
        $expectedDate = clone $lastWorkoutDate;
        $expectedDate->modify('-1 day');

        for ($i = 1; $i < count($dates); $i++) {
            $currentDate = new DateTime($dates[$i]);
            $currentDate->setTime(0, 0, 0);
            if ($currentDate == $expectedDate) {
                $streakDays++;
                $expectedDate->modify('-1 day');
            } else {
                break;
            }
        }
    }
}

// 4️⃣ Weight from onboarding_data
$sql = "SELECT current_weight FROM onboarding_data WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$weight = ($row && isset($row['current_weight'])) ? floatval($row['current_weight']) : 0;

// Initialize progress variables with default values
$workoutsThisWeek = 0; // Will represent completed days in the 4-week plan
$weeklyGoal = 28;     // Will represent total days in the 4-week plan

// 5️⃣ & 6️⃣ - Weekly Goal, Activity, and Plan Progress
$sql = "SELECT plan_data, current_week_index, current_day_index FROM user_workout_plans 
        WHERE user_id = $user_id 
        ORDER BY created_at DESC 
        LIMIT 1";
$result = $conn->query($sql);
$row = $result ? $result->fetch_assoc() : null;

$weeklyActivity = [];
$upcomingWorkouts = [];

if ($row) {
    $planJson = $row['plan_data'];
    $planData = json_decode($planJson, true);

    $currentWeekIndex = intval($row['current_week_index']);
    $currentDayIndex = intval($row['current_day_index']);

    // --- MODIFIED: 4-WEEK PLAN PROGRESS CALCULATION ---
    // The numerator is the total number of days passed in the plan.
    // (e.g., Week 1, Day 2 -> index 0, index 1 -> (0 * 7) + (1 + 1) = 2 days passed)
    $workoutsThisWeek = ($currentWeekIndex * 7) + ($currentDayIndex + 1);
    // The denominator is the total days in the 4-week plan.
    $weeklyGoal = 28;
    // --- END MODIFICATION ---

    $weekKey = "Week " . ($currentWeekIndex + 1);
    $dayKey = "Day " . ($currentDayIndex + 1);

    if (isset($planData['weekly_plan'][$weekKey])) {
        $dayCounter = 0;
        foreach ($planData['weekly_plan'][$weekKey] as $dayLabel => $exercises) {
            $isRestDay = is_array($exercises) && count($exercises) === 1 && $exercises[0] === "Rest Day";
            $isActive = ($dayCounter === $currentDayIndex);
            
            $planDayIdentifier = "Week " . ($currentWeekIndex + 1) . " - " . $dayLabel;
            $completionCheckSql = "SELECT COUNT(*) as count FROM workout_sessions WHERE user_id = $user_id AND plan_day = '" . $conn->real_escape_string($planDayIdentifier) . "'";
            $completionResult = $conn->query($completionCheckSql);
            $completionRow = $completionResult ? $completionResult->fetch_assoc() : null;
            $isCompleted = ($completionRow && intval($completionRow['count']) > 0);
            
            $weeklyActivity[] = [
                "day" => substr($dayLabel, 0, 3),
                "isRestDay" => $isRestDay,
                "exercises" => $isRestDay ? [] : $exercises,
                "isActive" => $isActive,
                "isCompleted" => $isCompleted
            ];
            $dayCounter++;
        }
    }

    if (isset($planData['weekly_plan'][$weekKey][$dayKey])) {
        $todayExercises = $planData['weekly_plan'][$weekKey][$dayKey];
        $isRestDay = is_array($todayExercises) && count($todayExercises) === 1 && $todayExercises[0] === "Rest Day";
        
        if ($isRestDay) {
            $upcomingWorkouts[] = ["title" => ["It is a rest day"], "time" => "today"];
        } else {
            $planDayIdentifier = "Week " . ($currentWeekIndex + 1) . " - " . $dayKey;
            $totalPlannedCount = count($todayExercises);
            $completedSql = "SELECT COUNT(DISTINCT exercise_name) as count FROM workout_sessions WHERE user_id = $user_id AND plan_day = '" . $conn->real_escape_string($planDayIdentifier) . "'";
            $completedResult = $conn->query($completedSql);
            $completedRow = $completedResult ? $completedResult->fetch_assoc() : null;
            $totalCompletedCount = $completedRow ? intval($completedRow['count']) : 0;

            if ($totalCompletedCount < $totalPlannedCount) {
                $upcomingWorkouts[] = ["title" => $todayExercises, "time" => "today"];
            }
        }
    }
} else {
    // If no plan, set progress to 0 and create default activity
    $workoutsThisWeek = 0; 
    $weeklyGoal = 28;
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $todayIndex = date('N') - 1;
    for ($i = 0; $i < 7; $i++) {
        $weeklyActivity[] = ["day" => $days[$i], "isRestDay" => ($i == 3 || $i == 6), "exercises" => [], "isActive" => ($i === $todayIndex), "isCompleted" => false];
    }
}

// ✅ Final JSON
echo json_encode([
    "workoutsCompleted" => $workoutsCompleted,
    "caloriesBurned" => $caloriesBurned,
    "streakDays" => $streakDays,
    "weight" => $weight,
    "workoutsThisWeek" => $workoutsThisWeek, // Now represents days completed in the 4-week plan
    "weeklyGoal" => $weeklyGoal,             // Now represents total days in the 4-week plan (28)
    "weeklyActivity" => $weeklyActivity,
    "upcomingWorkouts" => $upcomingWorkouts
]);

$conn->close();
?>