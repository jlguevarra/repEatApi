<?php
header('Content-Type: application/json');
require 'db_connection.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id === 0) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, title, notes, reminder_date FROM reminders WHERE user_id = ? ORDER BY reminder_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$reminders = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $reminders]);

$stmt->close();
$conn->close();
?>