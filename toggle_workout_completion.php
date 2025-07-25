<?php
header("Content-Type: application/json");
require_once 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['workout_id'])) {
    echo json_encode(['success' => false, 'message' => 'Workout ID is required']);
    exit;
}

try {
    $status = isset($data['is_completed']) && $data['is_completed'] ? 'complete' : 'planned';
    $stmt = $conn->prepare("UPDATE workouts SET status = ? WHERE workout_id = ?");
    $stmt->bind_param("si", $status, $data['workout_id']);
    $success = $stmt->execute();
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Status updated' : 'Failed to update',
        'new_status' => $status
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