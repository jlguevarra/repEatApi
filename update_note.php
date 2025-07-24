<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['workout_id'], $data['note'])) {
    $workout_id = $data['workout_id'];
    $note = $data['note'];

    $sql = "UPDATE workouts SET note = ? WHERE workout_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("si", $note, $workout_id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Note updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Error updating note: " . $stmt->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing parameters: workout_id and note are required."]);
}

$conn->close();
?>
