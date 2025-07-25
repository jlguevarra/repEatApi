<?php
header("Content-Type: application/json");
require_once 'db_connection.php';

// Validate user input
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT 
        workout_id, 
        user_id, 
        date,
        category as muscle_group,
        exercise_name,
        sets,
        reps,
        note,
        status,
        created_at
        FROM workouts 
        WHERE user_id = ?
        ORDER BY date DESC");
        
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $workouts = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $workouts
    ], JSON_NUMERIC_CHECK);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>