<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

include 'db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['user_id'], $data['date'], $data['note'])) {
    $user_id = $data['user_id'];
    $date = $data['date'];
    $note = $data['note'];

    $sql = "INSERT INTO workouts (user_id, date, note) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $date, $note);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Note saved successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to save note: " . $stmt->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Failed to prepare statement: " . $conn->error]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing required fields: user_id, date, and note are required."]);
}

$conn->close();
?>
