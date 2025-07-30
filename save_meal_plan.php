<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your database connection
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

// Validate required fields
if (!isset($data['user_id']) || !isset($data['meal_plan'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: user_id or meal_plan"
    ]);
    exit();
}

$user_id = (int)$data['user_id'];

// ✅ Ensure time_frame is always 'day' or 'week'
$time_frame = isset($data['time_frame']) && in_array($data['time_frame'], ['day', 'week']) 
    ? $data['time_frame'] 
    : 'day'; // Default to 'day' if missing or invalid

$start_date = null;

// ✅ Only set start_date if time_frame is 'week' AND date is provided
if ($time_frame === 'week' && !empty($data['start_date'])) {
    $date = strtotime($data['start_date']);
    if ($date === false) {
        error_log("Invalid start_date provided: " . $data['start_date']);
    } else {
        $start_date = date('Y-m-d', $date);
    }
}

$meal_plan_json = $data['meal_plan'];

try {
    // Check if user already has a saved meal plan
    $stmt = $conn->prepare("SELECT id FROM meal_plans WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing plan
        $update = $conn->prepare("UPDATE meal_plans SET time_frame = ?, start_date = ?, meal_plan = ?, updated_at = NOW() WHERE user_id = ?");
        $update->bind_param("sssi", $time_frame, $start_date, $meal_plan_json, $user_id);
        $update->execute();
    } else {
        // Insert new plan
        $insert = $conn->prepare("INSERT INTO meal_plans (user_id, time_frame, start_date, meal_plan) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $user_id, $time_frame, $start_date, $meal_plan_json);
        $insert->execute();
    }

    echo json_encode([
        "success" => true,
        "message" => "Meal plan saved successfully."
    ]);

} catch (Exception $e) {
    error_log("Save Meal Plan Error: " . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Failed to save meal plan: " . $e->getMessage()
    ]);
}

// Close statements (safely)
if (isset($stmt) && $stmt) $stmt->close();
if (isset($update) && $update) $update->close();
if (isset($insert) && $insert) $insert->close();

// Do not close $conn — it's managed by db_connection.php
?>