<?php
header("Content-Type: application/json");
require_once 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['user_id'], $data['date'], $data['muscle_group'], $data['exercise_name'], $data['sets'], $data['reps'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO workouts 
        (user_id, date, category, exercise_name, sets, reps, note, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'planned')");
    
    $note = $data['note'] ?? '';
    $stmt->bind_param(
        "isssiis",
        $data['user_id'],
        $data['date'],
        $data['muscle_group'],
        $data['exercise_name'],
        $data['sets'],
        $data['reps'],
        $note
    );
    
    $success = $stmt->execute();
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Workout saved successfully',
            'workout_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save workout']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>