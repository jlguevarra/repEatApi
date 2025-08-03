<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers");

require_once 'db_connection.php';

// Get raw JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$requiredFields = ['user_id', 'date', 'category', 'exercise_name', 'detected_reps'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Missing required field: $field"
        ]);
        exit;
    }
}

try {
    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO camera_workouts 
        (user_id, date, category, exercise_name, detected_reps, duration_seconds, accuracy_score) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $duration = $data['duration_seconds'] ?? 0;
    $accuracy = $data['accuracy_score'] ?? 0;
    
    $stmt->bind_param(
        "isssiid",
        $data['user_id'],
        $data['date'],
        $data['category'],
        $data['exercise_name'],
        $data['detected_reps'],
        $duration,
        $accuracy
    );

    // Execute and respond
    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Workout saved successfully",
            "workout_id" => $stmt->insert_id
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>