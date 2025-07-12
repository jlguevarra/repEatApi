<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connection.php';

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $date = isset($_GET['date']) ? $_GET['date'] : null;

    $sql = "SELECT workout_id, user_id, date, category, exercise_name, sets, reps, note
            FROM workouts
            WHERE user_id = ?";
    
    $params = [$user_id];
    
    if ($date) {
        $sql .= " AND date = ?";
        $params[] = $date;
    }
    
    $sql .= " ORDER BY date DESC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $workouts = [];

        while ($row = $result->fetch_assoc()) {
            $workouts[] = [
                'workout_id' => $row['workout_id'],
                'user_id' => $row['user_id'],
                'date' => $row['date'],
                'category' => $row['category'],
                'exercise_name' => $row['exercise_name'],
                'sets' => (int)$row['sets'],
                'reps' => (int)$row['reps'],
                'note' => $row['note'] ?? null
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $workouts
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare SQL statement.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing user_id parameter.'
    ]);
}

$conn->close();