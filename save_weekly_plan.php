<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection.php";

// Log the received data for debugging
error_log("Received save request: " . print_r($_POST, true));

$user_id   = $_POST['user_id'] ?? null;
$week      = $_POST['week'] ?? null;
$plan_json = $_POST['plan_json'] ?? null;
$sets      = $_POST['sets'] ?? null;
$reps      = $_POST['reps'] ?? null;
$goal      = $_POST['goal'] ?? null;

if (!$user_id || !$week || !$plan_json || !$sets || !$reps || !$goal) {
    error_log("Missing fields: user_id=$user_id, week=$week, sets=$sets, reps=$reps, goal=$goal");
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

// Extract week number from "Week X" format
$week_number = 1; // default
if (preg_match('/Week\s+(\d+)/i', $week, $matches)) {
    $week_number = (int)$matches[1];
}

// Insert or update if same (user_id, week_number) already exists
$stmt = $conn->prepare("
    INSERT INTO weekly_plans (user_id, week_number, plan, sets, reps, goal) 
    VALUES (?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        plan = VALUES(plan),
        sets = VALUES(sets),
        reps = VALUES(reps),
        goal = VALUES(goal),
        created_at = NOW()
");

$stmt->bind_param("iisiss", $user_id, $week_number, $plan_json, $sets, $reps, $goal);

if ($stmt->execute()) {
    error_log("Plan saved successfully for user $user_id, week $week_number");
    echo json_encode(["success" => true, "message" => "Plan saved/updated successfully"]);
} else {
    error_log("DB error: " . $stmt->error);
    echo json_encode(["success" => false, "message" => "DB error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>