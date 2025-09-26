<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$user_id = $data['user_id'] ?? null;

if (!$id || !$user_id) {
    echo json_encode(['success' => false, 'message' => 'Reminder ID and User ID are required.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM reminders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Reminder deleted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting reminder or not found.']);
}

$stmt->close();
$conn->close();
?>