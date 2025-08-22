<?php
header("Content-Type: application/json");
require_once "db_connection.php";

if (!isset($_GET['user_id'])) {
    echo json_encode(["success" => false, "message" => "Missing user_id"]);
    exit;
}

$user_id = intval($_GET['user_id']);
$week_number = date("W");

$sql = "SELECT day_of_week, exercise_name, sets, reps, status 
        FROM weekly_plan 
        WHERE user_id = ? AND week_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $week_number);
$stmt->execute();
$result = $stmt->get_result();

$plan = [];
while ($row = $result->fetch_assoc()) {
    $plan[] = $row;
}

echo json_encode(["success" => true, "plan" => $plan]);
?>
