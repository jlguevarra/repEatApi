<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connection.php';  

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    $sql = "SELECT * FROM workouts WHERE user_id = ? ORDER BY date DESC";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $workouts = [];

        while ($row = $result->fetch_assoc()) {
            $workouts[] = [
                'workout_id' => $row['workout_id'],
                'date' => $row['date'],
                'category' => $row['category'],
                'exercise_name' => $row['exercise_name'],
                'sets' => (int)$row['sets'],
                'reps' => (int)$row['reps'],
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $workouts
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: failed to prepare statement.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing user_id parameter.'
    ]);
}

$conn->close();
?>
