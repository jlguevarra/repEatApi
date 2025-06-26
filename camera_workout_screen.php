<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    isset($data['user_id']) &&
    isset($data['date']) &&
    isset($data['category']) &&
    isset($data['exercise_name']) &&
    isset($data['detected_reps'])
) {
    $user_id = $data['user_id'];
    $date = $data['date'];
    $category = $data['category'];
    $exercise_name = $data['exercise_name'];
    $detected_reps = $data['detected_reps'];
    $duration = isset($data['duration_seconds']) ? $data['duration_seconds'] : 0;
    $accuracy = isset($data['accuracy_score']) ? $data['accuracy_score'] : 0;

    $sql = "INSERT INTO camera_workouts (user_id, date, category, exercise_name, detected_reps, duration_seconds, accuracy_score)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param(
            "isssiid",
            $user_id,
            $date,
            $category,
            $exercise_name,
            $detected_reps,
            $duration,
            $accuracy
        );

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Workout saved successfully"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to execute statement"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing required parameters"]);
}

$conn->close();
?>
