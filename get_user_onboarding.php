<?php
header('Content-Type: application/json');
require_once 'db_connection.php'; 

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$userId = intval($data->user_id);

$stmt = $conn->prepare("SELECT preferred_sets, preferred_reps FROM onboarding_data WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'User onboarding data not found']);
}

$stmt->close();
$conn->close();
?>
