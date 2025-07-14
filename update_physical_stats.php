<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = $_POST;

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($data['user_id']);
$current_weight = $conn->real_escape_string($data['current_weight'] ?? '');
$target_weight = $conn->real_escape_string($data['target_weight'] ?? '');
$has_injury = isset($data['has_injury']) ? intval($data['has_injury']) : 0;
$injury_details = $conn->real_escape_string($data['injury_details'] ?? '');
$goal = isset($data['goal']) ? $conn->real_escape_string(trim($data['goal'])) : '';

$conn->begin_transaction();

try {
    // Check if onboarding_data exists for this user
    $check = $conn->prepare("SELECT id FROM onboarding_data WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("
            UPDATE onboarding_data 
            SET current_weight = ?, 
                target_weight = ?, 
                has_injury = ?, 
                injury_details = ?, 
                goal = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("ssissi", $current_weight, $target_weight, $has_injury, $injury_details, $goal, $user_id);
        $stmt->execute();
    } else {
        // Insert if not exists
        $stmt = $conn->prepare("
            INSERT INTO onboarding_data 
                (user_id, current_weight, target_weight, has_injury, injury_details, goal) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ississ", $user_id, $current_weight, $target_weight, $has_injury, $injury_details, $goal);
        $stmt->execute();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Physical stats updated successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

$conn->close();
?>
