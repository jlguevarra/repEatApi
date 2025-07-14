<?php
header('Content-Type: application/json');
require 'db_connection.php'; // adjust path if needed

if (!isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($_GET['user_id']);

// Get user data
$userQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

// Get onboarding data including diet & allergies & physical stats
$onboardingQuery = $conn->prepare("
    SELECT 
        current_weight, target_weight, height, goal, preferred_sets, preferred_reps, 
        has_injury, injury_details, diet_preference, allergies, body_type
    FROM onboarding_data 
    WHERE user_id = ?
");
$onboardingQuery->bind_param("i", $user_id);
$onboardingQuery->execute();
$onboardingResult = $onboardingQuery->get_result();
$onboarding = $onboardingResult->fetch_assoc();

if ($user && $onboarding) {
    echo json_encode([
        'success' => true,
        'data' => array_merge($user, $onboarding)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Profile data not found.'
    ]);
}

$conn->close();
?>
