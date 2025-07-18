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
$height = floatval($data['height'] ?? 0);
$has_injury = isset($data['has_injury']) ? intval($data['has_injury']) : 0;

// Improved injury details handling
$injury_details_raw = trim($data['injury_details'] ?? '');
$injury_details_clean = $conn->real_escape_string($injury_details_raw);

if ($has_injury == 0) {
    $injury_details_clean = 'None';
} elseif ($has_injury == 1 && empty($injury_details_raw)) {
    $injury_details_clean = 'Knee Pain'; // Default injury if none specified
}

$goal = isset($data['goal']) ? $conn->real_escape_string(trim($data['goal'])) : '';
$body_type = isset($data['body_type']) ? $conn->real_escape_string(trim($data['body_type'])) : 'Unknown';

// Automatically adjust goal if applicable
$goal_lower = strtolower($goal);
if (in_array($goal_lower, ['muscle gain', 'weight loss'])) {
    if ($current_weight > $target_weight) {
        $goal = 'Weight Loss';
    } elseif ($current_weight < $target_weight) {
        $goal = 'Muscle Gain';
    }
}

$conn->begin_transaction();

try {
    $check = $conn->prepare("SELECT id FROM onboarding_data WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
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
            "dddisssi", 
            $current_weight, 
            $target_weight, 
            $height, 
            $has_injury, 
            $injury_details_clean, 
            $goal, 
            $body_type, 
            $user_id
        );
    } else {
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
            $injury_details_clean, 
            $goal, 
            $body_type
        );
    }

    $stmt->execute();
    $conn->commit();

    // Return updated data in response
    echo json_encode([
        'success' => true,
        'message' => 'Physical stats updated successfully.',
        'data' => [
            'current_weight' => (string)$current_weight,
            'target_weight' => (string)$target_weight,
            'height' => (string)$height,
            'has_injury' => (string)$has_injury,
            'injury_details' => $injury_details_clean,
            'goal' => $goal,
            'body_type' => $body_type
        ]
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

$conn->close();
?>