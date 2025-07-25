<?php
header("Content-Type: application/json");
require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['workout_id'])) {
    echo json_encode(['success' => false, 'message' => 'Workout ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE workouts SET
        category = ?,
        exercise_name = ?,
        sets = ?,
        reps = ?,
        note = ?
        WHERE workout_id = ?");
    
    $success = $stmt->execute([
        $data['muscle_group'],
        $data['exercise_name'],
        $data['sets'],
        $data['reps'],
        $data['note'] ?? '',
        $data['workout_id']
    ]);
    
    echo json_encode([
        'success' => (bool)$success,
        'message' => $success ? 'Workout updated' : 'Failed to update workout'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>