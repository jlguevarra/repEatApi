<?php
header("Content-Type: application/json");
include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    isset($data['user_id'], $data['date'], $data['category'], 
    $data['exercise_name'], $data['sets'], $data['reps'])
) {
    $userId = $data['user_id'];
    $date = $data['date'];
    $category = $data['category'];
    $exerciseName = $data['exercise_name'];
    $sets = $data['sets'];
    $reps = $data['reps'];
    $note = isset($data['note']) ? $data['note'] : '';
    $status = 'planned';

    $stmt = $conn->prepare("INSERT INTO workouts (user_id, date, category, exercise_name, sets, reps, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssiss", $userId, $date, $category, $exerciseName, $sets, $reps, $note, $status);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Workout planned successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to plan workout."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
}
$conn->close();
?>
