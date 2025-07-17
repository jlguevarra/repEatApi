<?php
header('Content-Type: application/json');
require 'db_connection.php';

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

$response = [
    'success' => false,
    'message' => 'Profile data not found.'
];

if ($user) {
    // Get onboarding data
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

    if ($onboarding) {
        // keep has_injury as '1' or '0' (string)
        $onboarding['has_injury'] = (string) $onboarding['has_injury'];

        $response = [
            'success' => true,
            'data' => array_merge($user, $onboarding)
        ];
    } else {
        // User exists but onboarding data not found → return defaults
        $response = [
            'success' => true,
            'data' => array_merge($user, [
                'current_weight' => '',
                'target_weight' => '',
                'height' => '',
                'goal' => '',
                'preferred_sets' => '',
                'preferred_reps' => '',
                'has_injury' => '0',
                'injury_details' => '',
                'diet_preference' => '',
                'allergies' => '',
                'body_type' => ''
            ])
        ];
    }
}

echo json_encode($response);

$conn->close();
?>
