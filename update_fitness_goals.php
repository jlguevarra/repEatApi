<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = $_POST;

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($data['user_id']);
$goal = $conn->real_escape_string($data['goal']);
$sets = $conn->real_escape_string($data['sets']);
$reps = $conn->real_escape_string($data['reps']);

try {
    $stmt = $conn->prepare("
        UPDATE onboarding_data 
        SET goal = ?, preferred_sets = ?, preferred_reps = ? 
        WHERE user_id = ?
    ");
    $stmt->bind_param("sssi", $goal, $sets, $reps, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Fitness goals updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made or user not found.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

$conn->close();
?>
