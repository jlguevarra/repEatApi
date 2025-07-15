<?php
header('Content-Type: application/json');
require 'db_connection.php';

$data = $_POST;

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($data['user_id']);
$current_weight = floatval($data['current_weight'] ?? 0);
$target_weight = floatval($data['target_weight'] ?? 0);
$height = floatval($data['height'] ?? 0); // already in cm
$has_injury = isset($data['has_injury']) ? intval($data['has_injury']) : 0;
$injury_details = isset($data['injury_details']) ? $conn->real_escape_string(trim($data['injury_details'])) : '';
$goal = isset($data['goal']) ? $conn->real_escape_string(trim($data['goal'])) : '';
$body_type = isset($data['body_type']) ? $conn->real_escape_string(trim($data['body_type'])) : 'Unknown';

// Ensure injury_details is empty if has_injury is 0
if (!$has_injury) {
    $injury_details = '';
} elseif ($injury_details === '' || $injury_details === '0') {
    // fallback if has_injury is 1 but injury_details was sent blank
    $injury_details = 'Unspecified';
}

// Automatically adjust goal based on weights
$goal_lower = strtolower($goal);
if (in_array($goal_lower, ['muscle gain', 'weight loss'])) {
    if ($current_weight > $target_weight) {
        $goal = 'Weight Loss';
    } elseif ($current_weight < $target_weight) {
        $goal = 'Muscle Gain';
    }
}
// (Endurance and General Fitness remain untouched)

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
                height = ?, 
                has_injury = ?, 
                injury_details = ?, 
                goal = ?, 
                body_type = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param(
            "dddsissi", 
            $current_weight, 
            $target_weight, 
            $height, 
            $has_injury, 
            $injury_details, 
            $goal, 
            $body_type, 
            $user_id
        );
        $stmt->execute();
    } else {
        // Insert if not exists
        $stmt = $conn->prepare("
            INSERT INTO onboarding_data 
                (user_id, current_weight, target_weight, height, has_injury, injury_details, goal, body_type) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "idddisss", 
            $user_id, 
            $current_weight, 
            $target_weight, 
            $height, 
            $has_injury, 
            $injury_details, 
            $goal, 
            $body_type
        );
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
