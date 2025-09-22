<?php
header('Content-Type: application/json');
require 'db_connection.php';

// We decode JSON body now
$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? 0;
$plan_day = $data['plan_day'] ?? '';

if (empty($user_id) || empty($plan_day)) {
    echo json_encode(['success' => false, 'message' => 'User ID and Plan Day are required.']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT exercise_name FROM workout_sessions WHERE user_id = ? AND plan_day = ?");
    $stmt->bind_param("is", $user_id, $plan_day);
    $stmt->execute();
    $result = $stmt->get_result();

    $completed_exercises = [];
    while ($row = $result->fetch_assoc()) {
        $completed_exercises[] = $row['exercise_name'];
    }

    echo json_encode(['success' => true, 'data' => $completed_exercises]);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>