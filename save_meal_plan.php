<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'db_connection.php';

// Get raw POST data
$input = file_get_contents("php://input");
if (!$input) {
    echo json_encode([
        "success" => false,
        "message" => "No data received."
    ]);
    exit();
}

$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input."
    ]);
    exit();
}

if (!isset($data['user_id']) || !isset($data['meal_plan'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: user_id or meal_plan"
    ]);
    exit();
}

$user_id = (int)$data['user_id'];
$time_frame = isset($data['time_frame']) && in_array($data['time_frame'], ['day', 'week']) ? $data['time_frame'] : 'day';
$start_date = null;

if ($time_frame === 'week' && !empty($data['start_date'])) {
    $start_date = date('Y-m-d', strtotime($data['start_date']));
}

$meal_plan_json = $data['meal_plan'];

try {
    $stmt = $conn->prepare("SELECT id FROM meal_plans WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $update = $conn->prepare("UPDATE meal_plans SET time_frame = ?, start_date = ?, meal_plan = ?, updated_at = NOW() WHERE user_id = ?");
        $update->bind_param("sssi", $time_frame, $start_date, $meal_plan_json, $user_id);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO meal_plans (user_id, time_frame, start_date, meal_plan) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $user_id, $time_frame, $start_date, $meal_plan_json);
        $insert->execute();
    }

    echo json_encode(["success" => true, "message" => "Meal plan saved."]);

} catch (Exception $e) {
    error_log("Save Error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Save failed."]);
}

// Close statements
if (isset($stmt)) $stmt->close();
if (isset($update)) $update->close();
if (isset($insert)) $insert->close();
?>