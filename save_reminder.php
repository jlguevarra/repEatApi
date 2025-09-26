<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'], $data['title'], $data['reminder_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'] ?? null;
$user_id = intval($data['user_id']);
$title = $data['title'];
$notes = $data['notes'] ?? '';
$reminder_date = $data['reminder_date'];

if ($id) { // Update
    $stmt = $conn->prepare("UPDATE reminders SET title = ?, notes = ?, reminder_date = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssii", $title, $notes, $reminder_date, $id, $user_id);
} else { // Create
    $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, notes, reminder_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $notes, $reminder_date);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reminder saved successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>