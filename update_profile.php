<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = $_POST;

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($data['user_id']);
$name = $conn->real_escape_string($data['name']);
$current_weight = $conn->real_escape_string($data['current_weight']);
$target_weight = $conn->real_escape_string($data['target_weight']);
$goal = $conn->real_escape_string($data['goal']);
$sets = $conn->real_escape_string($data['sets']);
$reps = $conn->real_escape_string($data['reps']);
$has_injury = isset($data['has_injury']) ? intval($data['has_injury']) : 0;
$injury_details = $conn->real_escape_string($data['injury_details'] ?? '');

$conn->begin_transaction();

try {
    // Update users table
    $stmt1 = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt1->bind_param("si", $name, $user_id);
    $stmt1->execute();

    // Update onboarding_data table
    $stmt2 = $conn->prepare("UPDATE onboarding_data SET current_weight = ?, target_weight = ?, goal = ?, preferred_sets = ?, preferred_reps = ?, has_injury = ?, injury_details = ? WHERE user_id = ?");
    $stmt2->bind_param("sssssssi", $current_weight, $target_weight, $goal, $sets, $reps, $has_injury, $injury_details, $user_id);
    $stmt2->execute();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed.']);
}

$conn->close();
?>
