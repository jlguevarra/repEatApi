<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['workout_id']) && isset($data['note'])) {
    $workout_id = $data['workout_id'];
    $note = $data['note'];

    // First check if the workout exists
    $check_sql = "SELECT workout_id FROM workouts WHERE workout_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $workout_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Workout not found"
        ]);
        exit();
    }

    // Update only the note field
    $sql = "UPDATE workouts SET note = ? WHERE workout_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $note, $workout_id);
        if ($stmt->execute()) {
            echo json_encode([
                "success" => true, 
                "message" => "Note saved successfully."
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Failed to save note: " . $stmt->error
            ]);
        }
    } else {
        echo json_encode([
            "success" => false, 
            "message" => "Failed to prepare statement: " . $conn->error
        ]);
    }
} else {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields: workout_id and note are required"
    ]);
}

$conn->close();
?>