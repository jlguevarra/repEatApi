<?php
header('Content-Type: application/json');
require 'db_connection.php';

// Sanitize inputs
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$diet_preference = isset($_POST['diet_preference']) ? trim($_POST['diet_preference']) : '';
$allergies = isset($_POST['allergies']) ? trim($_POST['allergies']) : '';

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID.'
    ]);
    exit;
}

// Update onboarding_data table
$stmt = $conn->prepare("
    UPDATE onboarding_data 
    SET diet_preference = ?, allergies = ? 
    WHERE user_id = ?
");

$stmt->bind_param("ssi", $diet_preference, $allergies, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Diet preference and allergies updated successfully.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating data: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
