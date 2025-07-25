<?php
header("Content-Type: application/json");
require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['workout_id'])) {
    echo json_encode(['success' => false, 'message' => 'Workout ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM workouts WHERE workout_id = ?");
    $stmt->bind_param("i", $data['workout_id']);
    $success = $stmt->execute();
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Workout deleted' : 'Failed to delete workout'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>